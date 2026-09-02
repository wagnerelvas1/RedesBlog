# Phase 4 — Comments (nested)

Goal: comment on a post, reply to a comment (arbitrarily nested), optionally
attach a single image to a comment, edit a comment, delete a comment (author or
community admin). Render the tree with collapse/expand and "load more replies".

Depends on: phase 3. Blocks: phase 5 (comment voting).

---

## 4.1 Database

### Migration `create_comments_table`

| column                             | type                                               | notes                                                                                                                                           |
| ---------------------------------- | -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                               | `id`                                               |                                                                                                                                                 |
| `post_id`                          | `foreignId` cascade                                |                                                                                                                                                 |
| `user_id`                          | `foreignId` → users, `nullOnDelete`                | author; "[deleted]" when null                                                                                                                   |
| `parent_id`                        | `foreignId` nullable → comments, `cascadeOnDelete` | null = top-level                                                                                                                                |
| `body`                             | `text`                                             | required (image-only comment? spec says image _also_ → body still required; allow empty body only if an image is attached — enforce in request) |
| `depth`                            | `unsignedSmallInteger` default 0                   | denormalized nesting level (parent.depth + 1); `unsignedTinyInteger` also maps to `smallint` on Postgres                                        |
| `path`                             | `string(255)`                                      | materialized path `"/1/45/78/"` of ancestor ids, for subtree queries + ordering                                                                 |
| `score`                            | `integer` default 0                                | phase 5                                                                                                                                         |
| `upvotes_count`, `downvotes_count` | `unsignedInteger` default 0                        | phase 5                                                                                                                                         |
| `replies_count`                    | `unsignedInteger` default 0                        | direct children count, maintained here                                                                                                          |
| `edited_at`                        | `timestamp` nullable                               |                                                                                                                                                 |
| `deleted_at`                       | `softDeletes`                                      | soft delete so replies survive; UI shows "[removed]"                                                                                            |
| timestamps                         |                                                    |                                                                                                                                                 |

Indexes: `(post_id, parent_id, score)`, `(parent_id)`, and — for LIKE-prefix
subtree scans on Postgres — a `text_pattern_ops` index on `path`:
`CREATE INDEX comments_path_prefix ON comments (post_id, path text_pattern_ops)`
(add via `DB::statement` in the migration, drop in `down()`).

> **Max depth:** cap effective nesting at e.g. 8 for layout; deeper replies still
> allowed but rendered flattened with a "continue this thread →" link (Reddit
> behavior). Store true `depth`; the cap is a render concern.

### Attachments

Comments use the polymorphic `attachments` table (phase 0), **max 1** per
comment. `AttachmentService::attachOne` / `sync` with `keepIds` of length ≤ 1.

### Model `app/Models/Comment.php`

- `belongsTo post()`, `belongsTo author()`, `belongsTo parent()`,
  `hasMany replies() /* parent_id */`, `morphOne attachment()`, `morphMany votes()`.
- Scopes: `scopeTopLevel`, `scopeForPost`, `scopeSort($q, $sort ∈ {best,new,top,controversial,old})`.
- `#[Fillable(['body'])]`.
- `booted()`: on `creating`, set `depth` + `path` from parent; on `created`,
  increment parent `replies_count` and post `comments_count`; on `deleted`
  (soft), decrement `post.comments_count` by the subtree size (do this in the
  service, not the model, to keep it testable — prefer service).
- Appended: `edited` bool, `is_deleted` bool (for "[removed]" rendering while
  keeping children).

### Factory `CommentFactory`

States: `->onPost(Post $p)`, `->replyTo(Comment $c)`, `->withImage()`,
`->by(User $u)`, `->deleted()`.

---

## 4.2 Authorization — `app/Policies/CommentPolicy.php`

| Ability  | Rule                                                          |
| -------- | ------------------------------------------------------------- |
| `view`   | true                                                          |
| `create` | authenticated, community member, not banned, post not deleted |
| `update` | author only                                                   |
| `delete` | author OR community admin                                     |

Same per-request membership cache as `PostPolicy`.

---

## 4.3 Routes

| Method | URI                                              | Name               | Middleware                     |
| ------ | ------------------------------------------------ | ------------------ | ------------------------------ |
| GET    | `/c/{community}/posts/{post}/comments`           | `comments.index`   | — (JSON/partial for lazy load) |
| POST   | `/c/{community}/posts/{post}/comments`           | `comments.store`   | `auth`                         |
| PATCH  | `/c/{community}/posts/{post}/comments/{comment}` | `comments.update`  | `auth`, `can:update,comment`   |
| DELETE | `/c/{community}/posts/{post}/comments/{comment}` | `comments.destroy` | `auth`, `can:delete,comment`   |

`Route::scopeBindings()`. `comments.index` supports `?parent={id}` (load a
subtree) and `?sort=`. `wayfinder:generate` after.

---

## 4.4 FormRequests (`app/Http/Requests/Comment/`)

- `StoreCommentRequest`:
    - `body` `required_without:image` | string | max:10000
    - `image` nullable | image rules (single, `imageRules(1)`)
    - `parent_id` nullable | integer | `exists:comments,id` + custom rule: parent
      belongs to the same `{post}` and is not soft-deleted.
    - `authorize()` → `can('create', [Comment::class, $this->route('post')])`.
- `UpdateCommentRequest`:
    - `body` `required_without:keep_image` | max:10000
    - `image` nullable new file; `keep_image` boolean (keep the existing one)
    - `authorize()` → `can('update', $this->route('comment'))`.
- `IndexCommentsRequest`: `sort` in `best,new,top,old,controversial`;
  `parent_id` nullable exists; `cursor` nullable.

---

## 4.5 Repository & Service

### `app/Repositories/CommentRepository.php`

- `create(Post $post, User $author, ?Comment $parent, array $attributes): Comment`
  (sets `depth`, `path`).
- `update(Comment $comment, array $attributes): Comment`
- `treeForPost(Post $post, array $filters, ?User $viewer, int $rootLimit, int $childLimitPerNode): Collection`
  — load top-level comments (paginated), plus up to N replies per node; deeper /
  overflow replies fetched on demand via `subtree`.
- `subtree(Comment $parent, array $filters, ?User $viewer): CursorPaginator`
- `withViewerVote(Builder $q, ?User $viewer): Builder`
- Efficient loading: one query per "level" using `whereIn(parent_id, ...)`, or a
  single `where('path', 'like', $post-root.'%')` query for small posts. Choose
  path-prefix for posts under ~500 comments, level-batched otherwise. Always
  eager-load `author`, `attachment`, `viewer_vote`. **No N+1.**

### `app/Services/CommentService.php`

```php
public function create(Post $post, User $author, ?Comment $parent, array $data, ?UploadedFile $image): Comment;
// transaction: create, attach image, increment parent.replies_count + post.comments_count

public function update(Comment $comment, array $data, ?UploadedFile $image, bool $keepImage): Comment;
// sync single attachment, set edited_at

public function delete(Comment $comment): void;
// soft delete. If it has non-deleted replies -> keep row (renders "[removed]"),
// decrement post.comments_count by 1. If it is a childless leaf -> soft delete and
// walk up removing fully-dead branches (optional cleanup). Recompute counts.
```

### Controllers

`CommentController` (index/store/update/destroy). `store` returns
`back()` (Inertia reloads the post page with fresh `comments` prop) OR a
partial reload of just the `comments` prop — prefer `router.reload({ only: ['comments'] })`
triggered client-side after an Inertia `post`. `index` returns
`Inertia::render` partial or a JSON payload for subtree lazy-loading via the
`useHttp` hook (Inertia v3).

---

## 4.6 Frontend

On `posts/show`:

- `CommentComposer` — top-level box (body + single image attach + preview).
  Hidden/replaced with a "log in to comment" notice for guests.
- `CommentSortTabs` — Best / New / Top / Old / Controversial.
- `CommentTree` — renders `CommentNode[]`.
- `CommentNode` — indented by depth (with a clickable left "thread line" to
  collapse), shows: author, relative time, "edited", body (markdown), optional
  image (click to lightbox), vote control (phase 5 — placeholder now), Reply,
  Edit/Delete menu (when permitted), and children.
    - Collapsed state hides the subtree behind a `[+] N replies`.
    - Beyond the render-depth cap: `Continue this thread →` links to
      `posts/show?comment={id}` (a focused view rooted at that comment).
    - `Load more replies` / `Load N more comments` buttons hit `comments.index`
      via `useHttp`, merging results (Inertia merging props / manual state).
- `CommentForm` — shared by composer, reply, and edit (mode prop).
- Optimistic insert of a new comment (Inertia v3 optimistic + rollback on error).

Deep-link: `?comment={id}` renders the tree rooted at that comment with a
"View full discussion" breadcrumb.

Components: `CommentTree`, `CommentNode`, `CommentForm`, `CommentComposer`,
`CommentSortTabs`, `CollapseToggle`, reuse `ImageUploader` (single mode),
`MarkdownContent`, `Lightbox`.

---

## 4.7 Tests (`tests/Feature/Comment/`)

- `CreateCommentTest`: member comments on a post; reply sets `parent_id`,
  `depth`, `path`; image-only comment allowed; body-only allowed; both allowed;
  `parent_id` from another post rejected; reply to soft-deleted parent rejected;
  non-member 403; guest redirected; `post.comments_count` and
  `parent.replies_count` increment.
- `UpdateCommentTest`: author edits body / swaps image / keeps image;
  `edited_at` set; non-author (incl. community admin) 403.
- `DeleteCommentTest`: author soft-deletes a leaf → gone from tree, count
  decremented; author soft-deletes a comment **with replies** → row kept, renders
  "[removed]", replies still visible, count decremented by 1; community admin
  deletes another user's comment; random member 403.
- `CommentTreeTest`: ordering per sort; `rootLimit` / `childLimitPerNode`
  respected; `subtree` pagination; deep-link rooted fetch.
- `CommentNPlusOneTest`: bounded queries rendering a tree of N nodes with author
    - attachment + viewer vote.

---

## 4.8 Tasks checklist

- [ ] `create_comments_table` migration + indexes.
- [ ] `Comment` model + scopes + `depth`/`path` on create + factory states.
- [ ] `CommentPolicy` + registration.
- [ ] Routes (scoped bindings) + `wayfinder:generate`.
- [ ] FormRequests: StoreComment, UpdateComment, IndexComments.
- [ ] `CommentRepository` (tree + subtree, no N+1), `CommentService`.
- [ ] `CommentController`.
- [ ] Frontend components in 4.6; wire into `posts/show`.
- [ ] Tests in 4.7; `pint` / `stan` / `test` green.

## 4.9 Acceptance

A member comments and replies several levels deep with an image; collapsing a
thread hides its subtree; the author edits (shows "edited"); deleting a comment
with replies leaves a "[removed]" placeholder with children intact; a community
admin can delete anyone's comment; deep threads offer "continue this thread".
