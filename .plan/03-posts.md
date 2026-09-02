# Phase 3 — Posts

Goal: create a post (with 0..N images or none), edit it, delete it (author or
community admin), save/unsave it to a personal list, pin/unpin it in the
community (admins only), and render the community feed + a single-post page.

Depends on: phase 2 (needs `Community`, membership, `CommunityPolicy`) and
phase 0 (`AttachmentService`). Blocks: phases 4 and 5.

---

## 3.1 Database

### Migration `create_posts_table`

| column                     | type                                | notes                                                                                                                                                                                                             |
| -------------------------- | ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`                       | `id`                                |                                                                                                                                                                                                                   |
| `community_id`             | `foreignId` cascade                 |                                                                                                                                                                                                                   |
| `user_id`                  | `foreignId` → users, `nullOnDelete` | author; keep post if author deletes account? v1: `restrictOnDelete` is simpler given phase-1 blocks account deletion for community creators only. **Decision: `nullOnDelete`**, render "[deleted]" author.        |
| `title`                    | `string(300)`                       | required always                                                                                                                                                                                                   |
| `body`                     | `text` nullable                     | markdown; nullable so image-only posts are valid                                                                                                                                                                  |
| `slug`                     | `string(320)`                       | `{id}-{str_slug(title)}` style; unique per community or global. **Decision: global unique** `string` built from a short id hash + slug; route as `/c/{community}/posts/{post}` binding on `id`, slug is cosmetic. |
| `is_pinned`                | `boolean` default false             | admin-controlled                                                                                                                                                                                                  |
| `pinned_at`                | `timestamp` nullable                | ordering among pinned                                                                                                                                                                                             |
| `pinned_by`                | `foreignId` nullable                |                                                                                                                                                                                                                   |
| `score`                    | `integer` default 0                 | cached `upvotes - downvotes` (phase 5 maintains)                                                                                                                                                                  |
| `upvotes_count`            | `unsignedInteger` default 0         | phase 5                                                                                                                                                                                                           |
| `downvotes_count`          | `unsignedInteger` default 0         | phase 5                                                                                                                                                                                                           |
| `comments_count`           | `unsignedInteger` default 0         | phase 4 maintains                                                                                                                                                                                                 |
| `hot_rank`                 | `double` nullable                   | optional precomputed hotness for the "Hot" sort; can defer                                                                                                                                                        |
| `edited_at`                | `timestamp` nullable                | set on update to show "edited"                                                                                                                                                                                    |
| timestamps + `softDeletes` |                                     |

Indexes: `(community_id, is_pinned, created_at)`, `(community_id, score)`,
`(user_id)`.

### Migration `create_saved_posts_table`

| column     | type                | notes                                   |
| ---------- | ------------------- | --------------------------------------- |
| `user_id`  | `foreignId` cascade |                                         |
| `post_id`  | `foreignId` cascade |                                         |
| timestamps |                     | order "saved" list by `created_at desc` |

Primary key `(user_id, post_id)` (composite, no `id`).

### Attachments

Posts use the polymorphic `attachments` table from phase 0. No new table.

### Models

- `app/Models/Post.php`:
    - `belongsTo community()`, `belongsTo author() /* user_id */`, `belongsTo pinnedBy()`.
    - `morphMany attachments()` (ordered by `position`).
    - `hasMany comments()` (top-level: `whereNull('parent_id')`), plus `allComments()`.
    - `morphMany votes()`; `belongsToMany savedByUsers()` through `saved_posts`.
    - Scopes: `scopePinnedFirst`, `scopeForCommunity`, `scopeSort($builder, $sort)`
      where `$sort ∈ {hot, new, top, controversial}`.
    - `#[Fillable(['title','body'])]` — not `community_id` (set via relation),
      not pin fields, not counts.
    - Appended: `edited` bool, `attachments` always loaded on detail.
- Route key: `id` (slug cosmetic). Consider `Post::resolveRouteBinding` to also
  match slug.

### Factory `PostFactory`

States: `->pinned()`, `->withImages(int $n = 2)` (creates `Attachment` rows +
fakes blobs, or uses `Storage::fake`), `->inCommunity(Community $c)`,
`->by(User $u)`.

---

## 3.2 Authorization — `app/Policies/PostPolicy.php`

| Ability           | Rule                                                            |
| ----------------- | --------------------------------------------------------------- |
| `viewAny`, `view` | true (public)                                                   |
| `create`          | authenticated AND member of the target community AND not banned |
| `update`          | author only (`post.user_id === user.id`)                        |
| `delete`          | author OR community admin (`CommunityMember::isAdmin()`)        |
| `restore`         | community admin (optional)                                      |
| `pin`             | community admin only                                            |
| `save`            | any authenticated user (not gated by membership)                |

The community-admin check needs the membership pivot — inject
`CommunityRepository` into the policy and cache per request.

---

## 3.3 Routes

| Method | URI                                | Name            | Middleware                                             |
| ------ | ---------------------------------- | --------------- | ------------------------------------------------------ |
| GET    | `/c/{community}/submit`            | `posts.create`  | `auth`, `can:create,...`* (gate in controller/request) |
| POST   | `/c/{community}/posts`             | `posts.store`   | `auth`                                                 |
| GET    | `/c/{community}/posts/{post}`      | `posts.show`    | —                                                      |
| GET    | `/c/{community}/posts/{post}/edit` | `posts.edit`    | `auth`, `can:update,post`                              |
| PATCH  | `/c/{community}/posts/{post}`      | `posts.update`  | `auth`, `can:update,post`                              |
| DELETE | `/c/{community}/posts/{post}`      | `posts.destroy` | `auth`, `can:delete,post`                              |
| PUT    | `/c/{community}/posts/{post}/pin`  | `posts.pin`     | `auth`, `can:pin,post`                                 |
| DELETE | `/c/{community}/posts/{post}/pin`  | `posts.unpin`   | `auth`, `can:pin,post`                                 |
| PUT    | `/posts/{post}/save`               | `posts.save`    | `auth`                                                 |
| DELETE | `/posts/{post}/save`               | `posts.unsave`  | `auth`                                                 |
| GET    | `/saved`                           | `posts.saved`   | `auth`                                                 |

Scoped bindings: `Route::scopeBindings()` on the community group so `{post}` must
belong to `{community}`. `wayfinder:generate` after.

Also: `HomeController` (`/`) aggregates recent/hot posts across the user's
communities (or global if guest / no memberships).

---

## 3.4 FormRequests (`app/Http/Requests/Post/`)

- `StorePostRequest`:
    - `title` req|string|min:3|max:300
    - `body` nullable|string|max:40000
    - `images` sometimes|array|max:10; `images.*` image rules (reuse
      `ValidatesAttachments::imageRules(10)`)
    - `require` rule: at least one of `body`/`images` present when you want to
      forbid empty posts? Spec allows "com imagem ou sem" → title-only is fine.
      So no cross-field requirement.
    - `authorize()`: `$this->user()->can('create', [Post::class, $this->route('community')])`.
- `UpdatePostRequest`:
    - `title`, `body` as above.
    - `existing_images` array of attachment ids to keep; `images` new files;
      combined count ≤ 10. Custom rule validates `existing_images` belong to the post.
    - `authorize()` → `can('update', $this->route('post'))`.
- `IndexPostsRequest` (feed filters, since GET-with-input needs a FormRequest):
  `sort` in `hot,new,top,controversial`; `range` in `day,week,month,year,all`
  (for "top"); `cursor`/`page` for pagination.
- `SavePostRequest` — no body; `authorize()` true (auth middleware). Or skip a
  dedicated request and use a plain controller action guarded by policy —
  **but `.ai/rules/requests.md` mandates a FormRequest for every input-bearing
  route**; save/unsave carry no input, so a bare action + `can:save,post` in the
  route is acceptable. Document this exception inline.

---

## 3.5 Repository & Service

### `app/Repositories/PostRepository.php`

- `create(Community $c, User $author, array $attributes): Post`
- `update(Post $post, array $attributes): Post`
- `feedForCommunity(Community $c, array $filters, ?User $viewer): CursorPaginator`
  — pinned posts first (only page 1), then sorted; eager-load
  `author`, `attachments`, `community`, and the viewer's vote + saved flag
  (`withExists`/`withCount` or a left join) to avoid N+1.
- `aggregatedFeed(?User $viewer, array $filters): CursorPaginator` (home)
- `savedFor(User $user, array $filters): CursorPaginator`
- `withViewerState(Builder $q, ?User $viewer): Builder` — adds `viewer_vote`,
  `is_saved`.
- `setPinned(Post $post, ?User $admin): void` / `unpin`
- `save(User $u, Post $p): void` / `unsave` (idempotent `firstOrCreate` / delete)

### `app/Services/PostService.php`

```php
public function create(Community $c, User $author, array $data, array $images): Post;
// transaction: create row, AttachmentService::attachMany($post, $images, "posts/{$post->id}")

public function update(Post $post, array $data, array $keepImageIds, array $newImages): Post;
// AttachmentService::sync(...), set edited_at = now()

public function delete(Post $post): void;          // soft delete; keep blobs until purge job
public function pin(Post $post, User $admin): void;   // set is_pinned, pinned_at, pinned_by
public function unpin(Post $post): void;
public function toggleSave(User $user, Post $post): bool; // returns new saved state
```

### Controllers

`PostController` (create/store/show/edit/update/destroy), `PostPinController`
(store/destroy), `SavedPostController` (index), `PostSaveController` (store/destroy).

`show` renders `posts/show` with the post, its attachments, viewer state, and the
comment tree (phase 4 — until then, empty comments section).

---

## 3.6 Frontend

Pages `resources/js/pages/posts/`:

| Page         | Contents                                                                                                                                                                                                                                                                                                        |
| ------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `create.tsx` | community context header; title, body (textarea — markdown, phase 7 may add a preview toggle), `ImageUploader` (multi, reorder via drag, remove, previews, 10 max, 5 MB each). Submit via Wayfinder form; `<input type=file multiple>` → Inertia file upload (`forceFormData`).                                 |
| `edit.tsx`   | same form, prefilled; shows existing images with keep/remove toggles feeding `existing_images`.                                                                                                                                                                                                                 |
| `show.tsx`   | `PostCard` (full variant): vote rail, title, author + community + relative time + "edited", body (rendered markdown), image gallery/carousel, action bar (comment count, Save, Share, Pin toggle for admins, `⋯` menu with Edit/Delete for permitted users). Below: comment composer + `CommentTree` (phase 4). |

Feed rendering (used by `communities/show`, `home`, `saved`, profile posts tab):

- `PostList` — infinite scroll (Inertia v3 infinite scroll / merging props) with
  pulsing `Skeleton` cards for deferred/loading state.
- `PostCard` (compact variant) — vote rail, thumbnail, title, meta, comment count,
  save. Pinned posts get a 📌 badge and sit on top (page 1 only).
- `SortTabs` — Hot / New / Top / Controversial; "Top" reveals a range dropdown.
  Persists choice per community in `localStorage` (per phase-0 pattern).

Components: `PostCard`, `PostList`, `ImageUploader`, `ImageGallery`,
`PostActionsMenu`, `SavePostButton` (optimistic), `PinButton`, `SortTabs`,
`MarkdownContent` (safe render — use a small npm markdown lib, sanitize).

Save button available anywhere a `PostCard` renders. `/saved` page reuses
`PostList`.

---

## 3.7 Tests (`tests/Feature/Post/`)

- `CreatePostTest`: member creates text-only, image-only, text+image; images
  stored on faked `s3` + `attachments` rows + `position` set; non-member 403;
  banned member 403; guest redirected; >10 images or >5 MB rejected;
  `comments_count`/`score` default 0.
- `UpdatePostTest`: author edits title/body; `edited_at` set; add image / remove
  image via `existing_images`; non-author (even community admin) **cannot edit**
  → 403; `existing_images` id from another post rejected.
- `DeletePostTest`: author soft-deletes; community admin soft-deletes another
  user's post; random member 403; deleted post 404s on show, drops out of feed.
- `PinPostTest`: admin pins → appears first + badge; admin unpins; non-admin 403;
  pinning sets `pinned_by`/`pinned_at`.
- `SavePostTest`: save adds to `/saved`; unsave removes; idempotent; guest
  redirected; save works without community membership.
- `FeedSortTest`: pinned always first on page 1; `new` orders by `created_at`;
  `top` by `score` within range; pagination cursor stable.
- `FeedNPlusOneTest`: assert query count bounded when rendering N posts with
  author + attachments + viewer state.

---

## 3.8 Tasks checklist

- [ ] Migrations: posts, saved_posts. Indexes.
- [ ] `Post` model + scopes + factory states.
- [ ] `PostPolicy` + registration.
- [ ] Route group (scoped bindings) + `wayfinder:generate`.
- [ ] FormRequests: StorePost, UpdatePost, IndexPosts.
- [ ] `PostRepository` (with `withViewerState`), `PostService`.
- [ ] Controllers: Post, PostPin, PostSave, SavedPost, Home.
- [ ] Pages + components in 3.6; wire feed into `communities/show` and `home`.
- [ ] Tests in 3.7; `pint` / `stan` / `test` green.

## 3.9 Acceptance

A member posts with images; the post shows in the community feed with a working
vote rail placeholder; the author edits it ("edited" appears); a community admin
pins it (jumps to top) and can delete it; any user saves it and finds it under
`/saved`; guests can read everything but every write bounces to login.
