# Phase 5 — Voting

Goal: up/down vote on posts and comments. One vote per user per item, toggleable
(click up again = remove; click down when up = switch). Maintain cached
`score`, `upvotes_count`, `downvotes_count` on `posts` and `comments`, and feed
the sort orders defined in phases 3–4.

Depends on: phases 3 and 4. Blocks: nothing (final feature phase).

---

## 5.1 Database

### Migration `create_votes_table`

| column                       | type                | notes                                       |
| ---------------------------- | ------------------- | ------------------------------------------- |
| `id`                         | `id`                |                                             |
| `user_id`                    | `foreignId` cascade |                                             |
| `votable_type`, `votable_id` | `morphs('votable')` |                                             |
| `value`                      | `tinyInteger`       | `1` or `-1` (never `0` — absence = no vote) |
| timestamps                   |                     |                                             |

Unique index `(user_id, votable_type, votable_id)`.
Index `(votable_type, votable_id)`.

### Counters (already added by phases 3/4 migrations)

`posts.score / upvotes_count / downvotes_count`,
`comments.score / upvotes_count / downvotes_count`.
If phases 3/4 were implemented without them, add
`add_vote_counters_to_posts_and_comments_table` here.

### `posts.hot_rank` / ranking

Compute a Reddit-style hotness on vote change and on create:

```
sign  = score > 0 ? 1 : (score < 0 ? -1 : 0)
order = log10(max(|score|, 1))
seconds = created_at_epoch - 1735689600      // 2025-01-01 baseline
hot_rank = round(sign * order + seconds / 45000, 7)
```

Store on `posts.hot_rank`; "Hot" sort = `ORDER BY is_pinned DESC, hot_rank DESC`.
"Controversial" = high total votes with balanced ratio:
`(upvotes_count + downvotes_count) * min(up,down)/max(up,down,1)` — compute inline
in the query or store `controversy_rank`. Comments can reuse the same formulas
sans the pinned term; "Best" for comments uses the Wilson lower-bound:

```
n = up + down;  if n == 0 -> 0
phat = up / n;  z = 1.281551565545
best = (phat + z*z/(2n) - z*sqrt((phat*(1-phat) + z*z/(4n))/n)) / (1 + z*z/n)
```

Store `comments.best_rank` (nullable double) — add in this phase's migration if
not present.

### Model `app/Models/Vote.php`

- `morphTo votable()`, `belongsTo user()`.
- `#[Fillable(['value'])]`; cast `value` → int.
- No counters logic in the model — the service owns it.

### `HasVotes` trait (`app/Models/Concerns/HasVotes.php`)

Shared by `Post` and `Comment`:

- `morphMany votes()`
- `scopeWithViewerVote($q, ?User $viewer)` → adds `viewer_vote` (`-1|0|1`) via a
  correlated subquery / left join.
- No write helpers (writes go through the service).

### Factory `VoteFactory`

`->up()`, `->down()`, `->for($votable)`, `->by($user)`.

---

## 5.2 Authorization — `app/Policies/VotePolicy.php` (or gate in service)

| Ability | Rule                                                                                                                                                                                                                          |
| ------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `vote`  | authenticated; item's community not requiring membership? Spec: "upvote e downvote em postagem/comentário" for users. **Decision:** any authenticated user may vote (membership not required), but not on soft-deleted items. |

Guests → 401/redirect. Author voting on own content: allowed (Reddit auto-upvotes
own posts; v1 — allow, don't auto-cast).

---

## 5.3 Routes

| Method | URI                        | Name              | Middleware |
| ------ | -------------------------- | ----------------- | ---------- |
| PUT    | `/posts/{post}/vote`       | `posts.vote`      | `auth`     |
| DELETE | `/posts/{post}/vote`       | `posts.unvote`    | `auth`     |
| PUT    | `/comments/{comment}/vote` | `comments.vote`   | `auth`     |
| DELETE | `/comments/{comment}/vote` | `comments.unvote` | `auth`     |

`PUT` body: `{ value: 1 | -1 }`. Idempotent: PUT same value twice = no-op;
PUT opposite = switch; DELETE = clear. `wayfinder:generate` after.

Rate-limit: `throttle:60,1` per user to blunt vote spam.

---

## 5.4 FormRequest — `app/Http/Requests/Vote/StoreVoteRequest.php`

- `value` `required` | `integer` | `in:-1,1`
- `authorize()` → `$this->user()->can('vote', $this->route('post') ?? $this->route('comment'))`
  (or just `true` with policy check in service; keep it in `authorize()` to match
  `.ai/rules/requests.md`).

Unvote (DELETE) carries no body → bare controller action guarded by
`auth` + a `Gate::authorize('vote', ...)` call inside. Document the
no-FormRequest exception inline (same reasoning as post save/unsave in phase 3).

---

## 5.5 Repository & Service

### `app/Repositories/VoteRepository.php`

- `find(User $user, Model $votable): ?Vote`
- `upsert(User $user, Model $votable, int $value): Vote` (`updateOrCreate`)
- `delete(User $user, Model $votable): void`
- `tallies(Model $votable): array{up:int,down:int}` — `SUM`/`COUNT` from `votes`.

### `app/Services/VoteService.php`

```php
public function cast(User $user, Post|Comment $votable, int $value): VoteResult;
public function clear(User $user, Post|Comment $votable): VoteResult;
```

Each call, in a transaction with a row lock on the votable
(`lockForUpdate`):

1. upsert / delete the `votes` row;
2. recompute `upvotes_count`, `downvotes_count` from `COUNT` (authoritative, not
   `increment` deltas — avoids drift), set `score = up - down`;
3. recompute `hot_rank` (post) / `best_rank` + controversy (comment);
4. `save()` the votable.

`VoteResult` = `{ score, upvotes_count, downvotes_count, viewer_vote }` — returned
so the frontend can update without a full reload.

> Under heavy load, step 2's `COUNT` could be swapped for atomic
> `increment/decrement` with a periodic reconciliation command. v1: keep `COUNT`,
> it is correct and simple.

### Controllers

`PostVoteController` (store/destroy), `CommentVoteController` (store/destroy).
Return `back()` for a no-JS fallback **and** the `VoteResult` as a flashed prop,
OR respond to `useHttp` XHR with JSON. Prefer: Inertia `PUT` via Wayfinder +
`preserveScroll` + partial reload of the affected item, with an **optimistic
update** (Inertia v3) that rolls back on failure.

### Backfill sorts

Update `PostRepository::feedForCommunity` / `aggregatedFeed` and
`CommentRepository::treeForPost` to actually order by the new ranks now that the
columns are populated. Add a `db:seed`-time and a
`php artisan votes:recount` command (`app/Console/Commands`) that rebuilds all
counters/ranks from the `votes` table — useful after seeding and in tests.

---

## 5.6 Frontend

- `VoteControl` component — vertical (post rail, desktop) and horizontal
  (comments, mobile post bar) variants. Props: `votableType`, `id`, `score`,
  `viewerVote`, `endpoints` (Wayfinder fns). Behavior:
    - click ▲: if `viewerVote===1` → clear; else → cast `+1`.
    - click ▼: if `viewerVote===-1` → clear; else → cast `-1`.
    - optimistic score/arrow state; revert + toast on error.
    - guest: arrows are visible but clicking opens the login prompt.
    - `score` display: compact (`1.2k`), color up=orange, down=blue, neutral=muted.
- Wire `viewer_vote` from the payload into every `PostCard` and `CommentNode`.
- `SortTabs` / `CommentSortTabs` from phases 3–4 now fully functional.

---

## 5.7 Tests (`tests/Feature/Vote/`)

- `PostVoteTest`:
    - upvote → `score 1`, `upvotes_count 1`, `viewer_vote 1`;
    - upvote again (PUT +1) → no-op, still 1;
    - DELETE → back to 0, row gone;
    - downvote after upvote → `score -1`, counts `{up:0,down:1}`;
    - two users upvote → `score 2`;
    - guest → redirect/401;
    - vote on soft-deleted post → 404/403;
    - `hot_rank` recomputed and changes feed order (`FeedSortTest` extension).
- `CommentVoteTest`: analogous; `best_rank` affects "Best" ordering; controversial
  ordering with balanced votes.
- `VoteConcurrencyTest` _(optional)_: two parallel casts don't corrupt counters
  (rely on the lock; can be a simple sequential sanity check).
- `VotesRecountCommandTest`: corrupt a counter, run `votes:recount`, assert fixed.
- Unique constraint: second vote row for same user+item is prevented (DB or
  `updateOrCreate`).

---

## 5.8 Tasks checklist

- [ ] `create_votes_table` (+ counter/rank columns if missing) migration.
- [ ] `Vote` model, `HasVotes` trait on `Post` + `Comment`, factory.
- [ ] `VotePolicy` (or service gate) + registration.
- [ ] Routes + throttle + `wayfinder:generate`.
- [ ] `StoreVoteRequest`.
- [ ] `VoteRepository`, `VoteService` (locked transaction, COUNT-based recompute).
- [ ] `PostVoteController`, `CommentVoteController`.
- [ ] `votes:recount` artisan command.
- [ ] Update Post/Comment repositories to sort by real ranks.
- [ ] `VoteControl` component + wire `viewer_vote` everywhere.
- [ ] Tests in 5.7; `pint` / `stan` / `test` green.

## 5.9 Acceptance

Votes toggle and switch correctly with one row per user per item; `score` and
counts stay accurate across multiple voters; "Hot"/"Top"/"Best"/"Controversial"
sorts visibly reorder; the vote UI updates optimistically and reverts on error;
`votes:recount` reconciles counters.
