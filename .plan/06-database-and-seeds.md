# Phase 6 — Database reference & mock-data seeds

Cross-cutting. This file is the **canonical schema** — when a feature phase and
this file disagree, fix one of them so they match. It also owns the seeders that
produce a realistic browsable app.

**Engine: PostgreSQL 17.** See `.plan/00-foundations.md` §0.0 for the infra swap
and the full list of Postgres vs MySQL gotchas; §6.6 below recaps the ones that
touch the schema.

---

## 6.1 Migration inventory & order

Migrations are timestamp-ordered; keep this logical order:

| Order | Migration                                       | Phase | Notes                                                                     |
| ----- | ----------------------------------------------- | ----- | ------------------------------------------------------------------------- |
| 1     | `0001_01_01_000000_create_users_table` (exists) | —     | Laravel default                                                           |
| 2     | `..._create_cache_table` (exists)               | —     |                                                                           |
| 3     | `..._create_jobs_table` (exists)                | —     | queue = database                                                          |
| 3.5   | `enable_citext_extension`                       | 0     | `CREATE EXTENSION IF NOT EXISTS citext` (runs before any `citext` column) |
| 4     | `add_profile_fields_to_users_table`             | 1     | `avatar_path`, `bio`, `username` (citext)                                 |
| 5     | `create_attachments_table`                      | 0     | polymorphic file store                                                    |
| 6     | `create_communities_table`                      | 2     |                                                                           |
| 7     | `create_community_user_table`                   | 2     | pivot + `role` + `is_creator` + ban                                       |
| 8     | `create_posts_table`                            | 3     | incl. vote-counter + `hot_rank` cols                                      |
| 9     | `create_saved_posts_table`                      | 3     | composite PK                                                              |
| 10    | `create_comments_table`                         | 4     | incl. `depth`, `path`, counter cols                                       |
| 11    | `create_votes_table`                            | 5     | polymorphic, unique per user+item                                         |
| 12    | `add_ranks_to_comments_table` _(if needed)_     | 5     | `best_rank`, controversy                                                  |

> Prefer folding the vote-counter columns into the phase 3/4 `create_*` migrations
> (fresh project, no production data). Only add separate `add_*` migrations if a
> phase already shipped without them.

## 6.2 Column-level schema (authoritative)

> **`citext` columns** (`users.username`, `users.email`, `communities.name`):
> Laravel has no `citext()` builder — create the column as `string(n)`, then in
> the same migration, after the schema block, `DB::statement('ALTER TABLE <t>
ALTER COLUMN <c> TYPE citext')`; reverse in `down()` with `TYPE varchar(n)`.
> The `enable_citext_extension` migration (§6.1 row 3.5) must run first.
> **Raw-DDL indexes** (partial unique `community_user_one_creator`, prefix
> `comments_path_prefix`): add via `DB::statement` after `Schema::create`, and
> `DROP INDEX IF EXISTS` in `down()` — raw DDL is not auto-reversed. Full
> rationale in `.plan/00-foundations.md` §0.0 and §6.6 below.

### `users` (added columns only)

`avatar_path` string nullable · `bio` string(500) nullable ·
`username` `citext` unique (case-insensitive; max 30 enforced by validation +
a `CHECK (char_length(username) <= 30)` or just the FormRequest).

### `attachments`

`id` · `attachable_type` string · `attachable_id` bigint · `disk` string default
`s3` · `path` string · `original_name` string · `mime_type` string · `size`
bigint unsigned · `width` int unsigned nullable · `height` int unsigned nullable
· `position` smallint unsigned default 0 · timestamps.
Index: `(attachable_type, attachable_id)`.

### `communities`

`id` · `name` `citext` unique (case-insensitive, max 21 via validation/CHECK) ·
`title` string(100) · `description` string(500) nullable · `rules` text nullable ·
`avatar_path` string nullable · `banner_path` string nullable · `is_private` bool
default false · `created_by` FK users restrictOnDelete · `members_count` int
unsigned default 0 · `posts_count` int unsigned default 0 · timestamps ·
softDeletes.

### `community_user`

`id` · `community_id` FK cascade · `user_id` FK cascade · `role` string default
`member` · `is_creator` bool default false · `banned_at` timestamp nullable ·
`banned_by` FK users nullable · timestamps.
Unique: `(community_id, user_id)`. Index: `(community_id, role)`.
Partial unique index: `CREATE UNIQUE INDEX community_user_one_creator
ON community_user (community_id) WHERE is_creator` (one creator per community).

### `posts`

`id` · `community_id` FK cascade · `user_id` FK users nullOnDelete · `title`
string(300) · `body` text nullable · `slug` string(320) · `is_pinned` bool
default false · `pinned_at` timestamp nullable · `pinned_by` FK users nullable ·
`score` int default 0 · `upvotes_count` int unsigned default 0 · `downvotes_count`
int unsigned default 0 · `comments_count` int unsigned default 0 · `hot_rank`
double nullable · `edited_at` timestamp nullable · timestamps · softDeletes.
Indexes: `(community_id, is_pinned, created_at)`, `(community_id, score)`,
`(community_id, hot_rank)`, `(user_id)`, `(slug)`.

### `saved_posts`

`user_id` FK cascade · `post_id` FK cascade · timestamps.
Primary key: `(user_id, post_id)`.

### `comments`

`id` · `post_id` FK cascade · `user_id` FK users nullOnDelete · `parent_id` FK
comments nullable cascadeOnDelete · `body` text · `depth` smallint unsigned
default 0 · `path` string(255) · `score` int default 0 · `upvotes_count` int
unsigned default 0 · `downvotes_count` int unsigned default 0 · `replies_count`
int unsigned default 0 · `best_rank` double nullable · `edited_at` timestamp
nullable · timestamps · softDeletes.
Indexes: `(post_id, parent_id, score)`, `(parent_id)`, plus a
`text_pattern_ops` index on `path` for LIKE-prefix subtree queries:
`CREATE INDEX comments_path_prefix ON comments (post_id, path text_pattern_ops)`.

### `votes`

`id` · `user_id` FK cascade · `votable_type` string · `votable_id` bigint ·
`value` tinyint · timestamps.
Unique: `(user_id, votable_type, votable_id)`. Index: `(votable_type, votable_id)`.

## 6.3 Factories (one per model, in `database/factories/`)

- `UserFactory` (exists) — add `username`, `bio`; states `withAvatar`.
- `AttachmentFactory` — `forImage()` sets mime/size/dimensions; `image` state
  actually puts a fake blob on `Storage::fake('s3')`.
- `CommunityFactory` — valid `name` regex; states `private` (reserved),
  `withImages`.
- `CommunityMemberFactory` _(or use relation helpers)_ — `admin()`, `creator()`,
  `banned()`.
- `PostFactory` — `pinned()`, `withImages(n)`, `inCommunity()`, `by()`, `old()`.
- `CommentFactory` — `onPost()`, `replyTo()`, `withImage()`, `by()`, `deleted()`.
- `VoteFactory` — `up()`, `down()`, `for()`, `by()`.

Every factory used in tests; states preferred over manual attribute setup
(`.ai/rules` / testing rules).

## 6.4 Seeders (`database/seeders/`)

`DatabaseSeeder` calls, in order:

1. **`UserSeeder`** — 1 known dev user
   (`dev@redesblog.test` / `password`, username `dev`), plus ~40 random users.
   Some with avatars (fake blobs on the `s3` disk — guard with
   `Storage::disk('s3')` available; in local dev MinIO is up).
2. **`CommunitySeeder`** — ~8 communities with varied `title`/`description`/
   `rules`, avatars + banners, each `created_by` a random user (that user attached
   as `is_creator` + `admin`). Names like `announcements`, `webdev`, `gaming`,
   `photography`, `askreddit_clone`, `laravel`, `funny`, `news`.
3. **`MembershipSeeder`** — each community gets 5–25 members at `member` role,
   1–3 promoted to `admin`, 0–2 `banned`. Maintain `members_count`.
4. **`PostSeeder`** — 8–30 posts per community across a spread of `created_at`
   (last 60 days). ~35% have 1–4 images (fake blobs + `attachments` rows). 1–2
   posts per community `pinned`. Set `slug`.
5. **`CommentSeeder`** — per post, 0–40 comments, nested up to depth 5
   (recursive: each comment 0–3 replies with decreasing probability). ~10% have
   an image. Set `depth`/`path`. Maintain `replies_count`, `posts.comments_count`.
6. **`VoteSeeder`** — for each post/comment, a random subset of users vote
   (skew positive: ~75% `+1`). Respect the unique constraint.
7. **Recompute pass** — call `Artisan::call('votes:recount')` (phase 5) OR inline
   recompute `score`/counts/`hot_rank`/`best_rank` so sorts look real
   immediately.

Seeder guidelines:

- Wrap each seeder in a transaction; use `->insert()` batches for votes/comments
  where factories are too slow (thousands of rows). On Postgres, `insert()` does
  not return ids — capture ids with `insertGetId` per row, or `insert ... returning`
  via `DB::statement`, when a later step needs them.
- After bulk `insert()` into a table with a serial/identity PK, no sequence fix is
  needed (identity columns track inserts); only `setval` if you ever insert
  explicit `id` values.
- Deterministic-ish: seed `fake()->seed(...)` for reproducibility? optional.
- Idempotent enough to run on a fresh DB via `./rblog fresh`.
- No external HTTP for images — generate solid-color / noise PNGs in-process
  (GD: `imagecreatetruecolor`) or ship a handful of small sample images under
  `database/seeders/stubs/` and upload copies.

## 6.5 Tests

- Test database is **Postgres** — the `redesblog_testing` DB on the `postgres`
  Docker service (created by the initdb script in phase 0 §0.0). Not SQLite — the
  `citext` columns, partial unique index, and `text_pattern_ops` index are
  Postgres-only DDL. `phpunit.xml` is edited in phase 0 to set
  `DB_CONNECTION=pgsql` + `DB_DATABASE=redesblog_testing` (no `.env.testing`).
- `MigrationsTest` — `migrate:fresh` runs clean; assert key objects exist via
  `pg_indexes` / `information_schema` (`citext` on `communities.name` and
  `users.username`; partial unique `community_user_one_creator`; `path` prefix
  index).
- Case-insensitivity: creating `r/WebDev` then `r/webdev` hits the unique index.
- `SeedersTest` — `db:seed` populates every table; invariants hold: exactly one
  `is_creator` per community; `members_count` matches pivot count;
  `posts.comments_count` matches non-deleted comments; no orphan attachments;
  every `votes` row unique per user+item.
- Keep `SeedersTest` behind `@group slow` or run with a reduced volume config so
  the suite stays fast.

## 6.6 Postgres schema recap (full rationale in `.plan/00-foundations.md` §0.0)

- `enable_citext_extension` migration runs first; `communities.name` and
  `users.username` are `citext` → case-insensitive unique + lookups, no
  `lower()` gymnastics in queries.
- `unsigned*` modifiers are cosmetic on Postgres (plain `integer`/`bigint`);
  negative-value guards live in the Service layer.
- One-creator-per-community = partial unique index, not app-only logic.
- `comments.path` LIKE-prefix queries need a `text_pattern_ops` index.
- No `string(191)` — that was a MySQL utf8mb4 index-length hack. Use
  `string(255)` / `text`.
- `boolean` columns are native; `->default(false)` is fine.
- Timestamps are `timestamp` (without tz) to match Laravel defaults; if you want
  tz-aware, switch the whole app consistently — out of scope for v1.

## 6.7 Tasks checklist

- [ ] `enable_citext_extension` migration precedes every `citext` column.
- [ ] All migrations in 6.1 exist and match 6.2 exactly.
- [ ] Postgres-specific indexes created via `DB::statement` (partial unique,
      `text_pattern_ops`) with matching `down()` drops.
- [ ] `./rblog migrate:fresh` clean.
- [ ] Factories in 6.3 with the listed states.
- [ ] Seeders in 6.4 wired into `DatabaseSeeder`.
- [ ] Image generation helper (GD or stub files) — no network.
- [ ] `./rblog fresh` yields a browsable, realistic app.
- [ ] `MigrationsTest`, `SeedersTest` green; `pint` / `stan` clean.

## 6.8 Acceptance

`./rblog fresh` then open `/` — you see multiple communities with banners,
populated feeds (some pinned posts, some with image galleries), threaded comments
with images, and non-trivial vote scores driving the Hot/Top sorts. Login as
`dev@redesblog.test` / `password` works.
