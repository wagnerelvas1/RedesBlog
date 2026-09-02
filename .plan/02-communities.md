# Phase 2 — Communities & Membership

Goal: create communities, join/leave, edit community info (never the name),
grant/revoke admin (creator only), remove/ban members (admins, never the
creator), and delete a community (creator only, password-confirmed). This phase
establishes `CommunityPolicy`, which phases 3–5 depend on.

Depends on: phase 1. Blocks: phase 3+.

---

## 2.1 Database

### Migration `create_communities_table`

| column          | type                                    | notes                                                                                                                                                                                                                                                      |
| --------------- | --------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`            | `id`                                    |                                                                                                                                                                                                                                                            |
| `name`          | `citext` unique (Postgres)              | immutable identity, Reddit-style (`^[A-Za-z0-9_]{3,21}$`, max enforced by validation). **Case-insensitive** — `r/WebDev` and `r/webdev` collide. Used in the URL `/c/{name}`. Route-model-bind on this (binding resolves case-insensitively via `citext`). |
| `title`         | `string(100)`                           | human display name, editable                                                                                                                                                                                                                               |
| `description`   | `string(500)` nullable                  | editable                                                                                                                                                                                                                                                   |
| `rules`         | `text` nullable                         | markdown, editable                                                                                                                                                                                                                                         |
| `avatar_path`   | `string` nullable                       | editable, `s3` disk                                                                                                                                                                                                                                        |
| `banner_path`   | `string` nullable                       | editable, `s3` disk                                                                                                                                                                                                                                        |
| `is_private`    | `boolean` default false                 | _(reserved — keep false; no private communities in v1, but column avoids a later migration)_                                                                                                                                                               |
| `created_by`    | `foreignId` → users, `restrictOnDelete` | creator; blocks user deletion (phase 1 enforces friendlier message)                                                                                                                                                                                        |
| `members_count` | `unsignedInteger` default 0             | denormalized, maintained by `CommunityService`                                                                                                                                                                                                             |
| `posts_count`   | `unsignedInteger` default 0             | denormalized                                                                                                                                                                                                                                               |
| timestamps      |                                         |                                                                                                                                                                                                                                                            |
| `deleted_at`    | `softDeletes`                           | deletion is soft; a scheduled purge / manual cleanup removes blobs later. Keeps FK integrity for audit.                                                                                                                                                    |

> **Name immutability** is enforced three ways: not in `$fillable`, not in
> `UpdateCommunityRequest` rules, and a test asserts a PATCH with `name` is
> ignored.

### Migration `create_community_user_table` (pivot / membership)

| column                                                | type                      | notes                                                                                                                                                                                                                                                                                                                                      |
| ----------------------------------------------------- | ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `id`                                                  | `id`                      |                                                                                                                                                                                                                                                                                                                                            |
| `community_id`                                        | `foreignId` cascade       |                                                                                                                                                                                                                                                                                                                                            |
| `user_id`                                             | `foreignId` cascade       |                                                                                                                                                                                                                                                                                                                                            |
| `role`                                                | `string` default `member` | `member` \| `admin` (enum-backed in PHP)                                                                                                                                                                                                                                                                                                   |
| `is_creator`                                          | `boolean` default false   | exactly one true row per community — enforce with a **partial unique index** via `DB::statement` after `Schema::create`: `CREATE UNIQUE INDEX community_user_one_creator ON community_user (community_id) WHERE is_creator`; `down()` must `DROP INDEX IF EXISTS community_user_one_creator` (raw DDL isn't auto-reversed); never editable |
| `banned_at`                                           | `timestamp` nullable      | set when an admin bans; banned users can't rejoin                                                                                                                                                                                                                                                                                          |
| `banned_by`                                           | `foreignId` nullable      |                                                                                                                                                                                                                                                                                                                                            |
| `created_at` (as `joined_at` semantics), `updated_at` |                           | use `withTimestamps()` on the relation                                                                                                                                                                                                                                                                                                     |

Unique index `(community_id, user_id)`.

### PHP enum `app/Enums/CommunityRole.php`

```php
enum CommunityRole: string {
    case Member = 'member';
    case Admin = 'admin';
}
```

(TitleCase keys per `.ai/rules` / php rules.) Cast the pivot `role` to it.

### Models

- `app/Models/Community.php`:
    - `belongsTo created_by` as `creator()`.
    - `belongsToMany users()` `->using(CommunityMember::class)->withPivot('role','is_creator','banned_at')->withTimestamps()`.
    - `admins()` scoped relation (`wherePivot('role', 'admin')`).
    - `hasMany posts()`.
    - Route key: `getRouteKeyName(): string => 'name'`.
    - Accessors `avatarUrl`, `bannerUrl`.
    - `#[Fillable(['title','description','rules'])]` — note: NOT `name`, NOT image paths.
- `app/Models/CommunityMember.php` extends `Pivot` — casts `role` → enum,
  `banned_at` → datetime, `is_creator` → bool. Helper `isAdmin(): bool` (admin or
  creator).

### Factories & states

`CommunityFactory` (name via `fake()->unique()->regexify('[A-Za-z0-9_]{5,15}')`),
plus `CommunityUserFactory` or use relationship helpers in the seeder.

---

## 2.2 Authorization — `app/Policies/CommunityPolicy.php`

Register in `AppServiceProvider` or via `#[UsePolicy]` attribute on the model
(Laravel 13 supports the attribute — check sibling usage; default to attribute).

| Ability           | Rule                                                                                                                                                                     |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `viewAny`, `view` | always true (public)                                                                                                                                                     |
| `create`          | any authenticated user                                                                                                                                                   |
| `update`          | membership role admin OR creator                                                                                                                                         |
| `updateSettings`  | same as `update` (rules, images, description)                                                                                                                            |
| `manageAdmins`    | `is_creator` only                                                                                                                                                        |
| `manageMembers`   | admin OR creator — **target guard in service:** cannot act on the creator or on self-demotion of the last admin? (creator is always admin, so "last admin" can't happen) |
| `delete`          | `is_creator` only (controller also enforces `password.confirm` middleware)                                                                                               |
| `join`            | authenticated, not already a member, not banned                                                                                                                          |
| `leave`           | member AND not creator (creator cannot leave; must delete or... v1: cannot leave)                                                                                        |

Helper: `CommunityPolicy` loads the membership via a `CommunityMember` lookup
(inject `CommunityRepository`). Guard against N+1 by eager-loading the pivot when
the community is resolved for a request.

---

## 2.3 Routes — `routes/web.php` (community group)

| Method | URI                             | Name                          | Middleware                                                                   |
| ------ | ------------------------------- | ----------------------------- | ---------------------------------------------------------------------------- |
| GET    | `/communities`                  | `communities.index`           | —                                                                            |
| GET    | `/communities/create`           | `communities.create`          | `auth`                                                                       |
| POST   | `/communities`                  | `communities.store`           | `auth`                                                                       |
| GET    | `/c/{community}`                | `communities.show`            | — (this is the feed; phase 3 fills it)                                       |
| GET    | `/c/{community}/about`          | `communities.about`           | —                                                                            |
| GET    | `/c/{community}/settings`       | `communities.settings.edit`   | `auth`, `can:update,community`                                               |
| PATCH  | `/c/{community}/settings`       | `communities.settings.update` | `auth`, `can:update,community`                                               |
| DELETE | `/c/{community}`                | `communities.destroy`         | `auth`, `password.confirm`, `can:delete,community`                           |
| POST   | `/c/{community}/membership`     | `communities.join`            | `auth`                                                                       |
| DELETE | `/c/{community}/membership`     | `communities.leave`           | `auth`                                                                       |
| GET    | `/c/{community}/members`        | `communities.members.index`   | `auth`, `can:manageMembers,community`                                        |
| PATCH  | `/c/{community}/members/{user}` | `communities.members.update`  | `auth` (policy inside: manageAdmins for role changes, manageMembers for ban) |
| DELETE | `/c/{community}/members/{user}` | `communities.members.destroy` | `auth`, `can:manageMembers,community`                                        |

`./rblog artisan wayfinder:generate` after.

---

## 2.4 FormRequests (`app/Http/Requests/Community/`)

- `StoreCommunityRequest`: `name` req|string|regex:`/^[A-Za-z0-9_]{3,21}$/`|
  unique:communities|not_in:(reserved words: `admin`,`api`,`c`,`u`,`settings`…).
  The `unique` rule is case-insensitive automatically because the column is
  `citext` — no `->where` lowering needed; a test still asserts `WebDev` vs
  `webdev` collision;
  `title` req|string|max:100; `description` nullable|max:500; `rules` nullable|
  max:10000; `avatar`, `banner` nullable image rules. `authorize()`: `true`
  (auth middleware covers it).
- `UpdateCommunitySettingsRequest`: `title`, `description`, `rules`, `avatar`,
  `banner`, `remove_avatar`, `remove_banner` — **no `name`**. `authorize()` →
  `$this->user()->can('update', $this->route('community'))`.
- `UpdateMemberRequest`: `role` in `member,admin` (sometimes); `banned` boolean
  (sometimes). `authorize()`: if `role` present → `can('manageAdmins', …)`; if
  `banned` present → `can('manageMembers', …)`. Reject changes targeting the
  creator (`prohibited_if`/custom rule or in service).
- `DeleteCommunityRequest`: no body beyond what `password.confirm` handles;
  optionally require `name` typed to confirm ("type the community name") →
  `confirm_name` req|same-as-route-name. `authorize()` → `can('delete', …)`.

---

## 2.5 Repository & Service

### `app/Repositories/CommunityRepository.php`

- `create(array $attributes, User $creator): Community`
- `paginateForIndex(array $filters): LengthAwarePaginator` (search by name/title,
  sort by `members_count` / `created_at`, filter "joined"/"all")
- `forSidebar(User $user): Collection` (used by shared props)
- `membership(Community $c, User $u): ?CommunityMember`
- `members(Community $c, array $filters): LengthAwarePaginator`
- `attachMember(Community $c, User $u, CommunityRole $role, bool $isCreator = false): void`
- `updateMember(Community $c, User $u, array $pivot): void`
- `detachMember(Community $c, User $u): void`
- `incrementMembers` / `decrementMembers` (or recount).

### `app/Services/CommunityService.php`

```php
public function create(User $creator, array $data): Community;
// creates community in a transaction, stores avatar/banner, attaches creator as
// admin + is_creator, members_count = 1.

public function updateSettings(Community $c, array $data): Community;
// sync avatar/banner (add/remove), fill title/description/rules. Never touches name.

public function grantAdmin(Community $c, User $actor, User $target): void;
public function revokeAdmin(Community $c, User $actor, User $target): void;
// guard: $target is not the creator; actor passed manageAdmins policy.

public function banMember(Community $c, User $actor, User $target): void;
public function unbanMember(Community $c, User $actor, User $target): void;
// guard: $target is not the creator, not the actor.

public function join(Community $c, User $user): void;   // rejects if banned / already member
public function leave(Community $c, User $user): void;   // rejects creator
public function delete(Community $c): void;              // soft delete; enqueue blob cleanup job
```

All member-count mutations happen here. Throw domain exceptions
(`AuthorizationException` / `ValidationException` / custom
`CommunityException`) with messages surfaced as flash errors.

### Controllers

`CommunityController` (index/create/store/show/about), `CommunitySettingsController`
(edit/update/destroy), `CommunityMembershipController` (join/leave),
`CommunityMemberController` (index/update/destroy). One service call per action.

`show` returns `Inertia::render('communities/show', [...])` — post list is a
phase-3 deferred/optional prop; for now render header + membership state + empty
feed.

---

## 2.6 Frontend

Pages `resources/js/pages/communities/`:

| Page                   | Contents                                                                                                                                                                                                                            |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `index.tsx`            | searchable/sortable list of communities; "Create community" CTA; join/leave buttons inline.                                                                                                                                         |
| `create.tsx`           | name (with live availability hint + immutability warning), title, description, rules, avatar, banner. `MaskedInput` not needed; enforce the name regex client-side too.                                                             |
| `show.tsx`             | `CommunityHeader` (banner, avatar, title, `name`, members_count, join/leave, "Create post" if member, settings gear if admin). Body: post feed slot (phase 3) + right rail `AboutCard` (description, rules, creator, created date). |
| `about.tsx`            | full rules / description on its own page (mobile).                                                                                                                                                                                  |
| `settings/edit.tsx`    | tabbed: **General** (title, description, images), **Rules**, **Members** (link), **Danger** (delete → password-confirm flow + type-the-name).                                                                                       |
| `settings/members.tsx` | member table: avatar, username, role badge, joined date; actions per row: promote/demote (creator only, hidden for creator row), ban/unban. Uses `router.patch`/`router.delete` via Wayfinder.                                      |

Components: `CommunityHeader`, `CommunityCard`, `AboutCard`, `MemberRow`,
`JoinButton` (optimistic — Inertia v3 optimistic updates with rollback),
`RoleBadge`, `DangerZone`.

Guests hitting write affordances get the `GuestNotice` / redirect to `login`
with `?redirect`.

---

## 2.7 Tests (`tests/Feature/Community/`)

- `CreateCommunityTest`: guest redirected; member creates → is admin + creator,
  members_count 1; invalid/duplicate/reserved name rejected; images stored on
  faked `s3`.
- `UpdateCommunitySettingsTest`: admin updates title/description/rules/images;
  **`name` in payload is ignored**; non-admin gets 403; creator can update.
- `ManageAdminsTest`: creator promotes a member to admin; admin (non-creator)
  **cannot** promote (403); nobody can demote/ban the creator (assert guard);
  creator demotes an admin.
- `ManageMembersTest`: admin bans a member → member can't rejoin; unban restores;
  admin cannot ban creator or self.
- `MembershipTest`: join increments count; leave decrements; creator cannot
  leave; banned user cannot join.
- `DeleteCommunityTest`: requires confirmed password + correct name; only creator;
  soft-deletes; admin (non-creator) gets 403.
- `CommunityPolicyTest` (unit-ish): matrix from the roadmap.

---

## 2.8 Tasks checklist

- [ ] Migrations: communities, community_user; `CommunityRole` enum.
- [ ] Models `Community`, `CommunityMember`; factories.
- [ ] `CommunityPolicy` + registration.
- [ ] Route group + `wayfinder:generate`.
- [ ] FormRequests (Store/UpdateSettings/UpdateMember/DeleteCommunity).
- [ ] `CommunityRepository`, `CommunityService`.
- [ ] Controllers (4).
- [ ] Shared-prop sidebar now returns real communities.
- [ ] Pages + components listed in 2.6.
- [ ] Feature tests in 2.7; `pint` / `stan` / `test` green.

## 2.9 Acceptance

A user creates a community and is its immovable creator/admin; can edit
everything but the name; can promote another member who then gains admin powers
but cannot touch the creator; can ban a member; deleting the community requires
password + typing its name.
