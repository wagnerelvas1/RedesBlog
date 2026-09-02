# RedesBlog — Initial App Roadmap

> **Status:** planning. This file is the execution roadmap / index. Each numbered
> plan under `.plan/` is a self-contained work package that an AI agent can pick
> up and implement end to end. Read this file first, then the plan for the phase
> you are working on, then `.ai/rules/index.md` for the paths you will touch.

The initial application is a Reddit-like blog: communities, posts (with images),
nested comments (with images), voting, saved posts, pinned posts, and per-community
admin roles. Look and feel should evoke Reddit.

---

## 1. Tech stack (already installed — do not add without approval)

| Layer               | Choice                                                                             |
| ------------------- | ---------------------------------------------------------------------------------- |
| Backend             | Laravel 13, PHP 8.5 (`composer.json` requires `^8.3`)                              |
| Frontend            | Inertia v3 + React 19 + TypeScript                                                 |
| Styling             | Tailwind CSS v4 (`@tailwindcss/vite`), dark mode via `.dark`                       |
| Routing FE          | Laravel Wayfinder (`@/actions/*`, `@/routes/*`)                                    |
| DB                  | PostgreSQL 17 (Docker service `postgres`), admin UI: pgAdmin 4 (service `pgadmin`) |
| Queue/Cache/Session | `database` driver                                                                  |
| Tests               | Pest v5 (`./rblog test`)                                                           |
| Lint/Types          | Pint + Larastan level 7 (`./rblog pint`, `./rblog stan`)                           |

### Infra prerequisite (phase 0, before any other work)

The base image builds `pdo_mysql` only. Phase 0 rewrites `docker/Dockerfile` to
add `pdo_pgsql`/`pgsql` (+ swaps the MySQL/phpMyAdmin services for
PostgreSQL/pgAdmin and points `phpunit.xml` at a `redesblog_testing` DB). After
the Dockerfile change you MUST `./rblog build` — every later phase depends on the
Postgres driver being present. Details: `.plan/00-foundations.md` §0.0.

### Dependencies that MUST be added (require user approval before install)

Verified against `composer.lock`: neither is installed (both are only
`suggest`/`conflict` entries of `laravel/framework`).

- `league/flysystem-aws-s3-v3` — S3/MinIO driver for the `s3` disk. Needed by
  phase 0. Install: `./rblog composer require league/flysystem-aws-s3-v3`.
- `intervention/image` _(optional)_ — server-side image validation/resizing for
  uploads. If not approved, fall back to Laravel's `image` / `dimensions`
  validation rules only.
- `inputmask` (npm) — required by the spec for input masks. Install:
  `./rblog npm install inputmask`. Not a backend concern.
- _(optional, frontend)_ a markdown renderer + sanitizer (e.g. `marked` +
  `dompurify`) for `MarkdownContent` — phase 7. `league/commonmark` is already
  present server-side if a backend render is preferred instead.

Everything else needed (auth, policies, factories, `Inertia::optional`,
`password.confirm` middleware) ships with the installed framework — verified.

---

## 2. Architecture rules (apply to every phase)

From `.ai/rules/app.md` — **layered, no exceptions**:

```
Model (app/Models)          data shape: casts, relationships, scopes
Repository (app/Repositories)  queries + persistence (all Eloquent lives here)
Service (app/Services)       business rules, authorization orchestration, transactions
Controller (app/Http/Controllers)  validate (FormRequest) → call service → return Inertia/redirect
FormRequest (app/Http/Requests)    one class per input-bearing route (incl. GET w/ filters)
Policy (app/Policies)        per-model authorization gates
```

- Controllers: **no** business logic, **no** direct Eloquent. Thin.
- Every route taking query-string or body input needs a dedicated FormRequest
  (`.ai/rules/requests.md`). Authorization that depends only on the route
  (e.g. "is community admin") may live in `FormRequest::authorize()` delegating
  to a Policy; richer rules live in the Service.
- Naming: `PascalCase` classes, `camelCase` methods/vars, `snake_case` only for
  DB columns / migration keys / route params (`.ai/rules/general.md`).
- Run `./rblog pint <files>` and `./rblog stan <files>` on every PHP file you
  touch before reporting done.
- All commands go through `./rblog` (`.ai/rules/general.md`, `rblog-wrapper` skill).

### Frontend rules (`.ai/rules/js.md`)

- ES Modules only. Libraries installed from npm and imported by name — never
  CDN, never vendored files.
- Pages in `resources/js/pages`, rendered via `Inertia::render()`.
- Call the backend only through Wayfinder-generated functions
  (`wayfinder-development` skill). Regenerate with `./rblog artisan wayfinder:generate`
  (also runs automatically through the Vite plugin in dev).
- Tailwind utilities only, sorted; use the `cn()` helper in `resources/js/lib/utils.ts`.
  Activate the `tailwindcss-development` skill for any component work.

---

## 3. Domain model (target schema — full detail in `.plan/06-database-and-seeds.md`)

```
users ─┬─< community_user >─┬─ communities ──< posts ──< comments (self-nested via parent_id)
       │                    │                    │           │
       ├─< saved_posts >────┼────────────────────┘           │
       ├─< votes (poly: post|comment) >─────────────────────┤
       └─ authored posts / comments                          │
                                                             │
attachments (poly: post|comment) ── file paths on the "s3" disk
```

Key tables: `users` (+`avatar_path`, `bio`), `communities`, `community_user`
(pivot with `role`, `is_creator`), `posts` (+`is_pinned`, cached `score`),
`comments` (+`parent_id`, `path`/`depth`, cached `score`), `attachments` (poly),
`votes` (poly, unique per user+votable), `saved_posts` (pivot).

---

## 4. Execution phases

Implement in order. Each phase ends **green**: `./rblog pint`, `./rblog stan`,
and `./rblog test` all pass, and the feature is reachable in the UI.

| #   | Plan file                        | Delivers                                                                                                                                                                                                            | Depends on    |
| --- | -------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| 0   | `.plan/00-foundations.md`        | PostgreSQL + pgAdmin in Docker (replacing MySQL/phpMyAdmin), `citext`, S3 storage (MinIO), env, disks, `AttachmentService`, base layout, theme toggle, inputmask wrapper, shared Inertia props, layered scaffolding | —             |
| 1   | `.plan/01-authentication.md`     | Register, login, logout, profile edit, avatar upload                                                                                                                                                                | 0             |
| 2   | `.plan/02-communities.md`        | Community CRUD, membership (join/leave), admin roles, policies, password-confirmed deletion                                                                                                                         | 1             |
| 3   | `.plan/03-posts.md`              | Post CRUD w/ images, save/unsave, pin/unpin, community feed                                                                                                                                                         | 2             |
| 4   | `.plan/04-comments.md`           | Nested comments w/ images, edit, delete                                                                                                                                                                             | 3             |
| 5   | `.plan/05-voting.md`             | Up/down vote on posts and comments, score caching, sort orders                                                                                                                                                      | 3, 4          |
| 6   | `.plan/06-database-and-seeds.md` | Canonical schema reference + mock-data seeders/factories                                                                                                                                                            | cross-cutting |
| 7   | `.plan/07-frontend-ui.md`        | Page inventory, component library, responsive shell, dark mode, empty/loading states                                                                                                                                | all           |

Phases 6 and 7 are cross-cutting references: consult 6 while writing any
migration, consult 7 while building any page. They are kept separate so the
schema and the UI contract have one authoritative home instead of being smeared
across five feature plans.

---

## 5. Cross-cutting acceptance criteria

- **Auth:** guests can browse communities/posts/comments read-only; every write
  requires auth; every write is authorized by a Policy.
- **Storage:** no file is ever stored on the `local`/`public` disk. All uploads
  go to the `s3` disk (MinIO locally). DB stores `disk` + `path`; URLs are
  produced by `AttachmentService`, never hardcoded.
- **Images:** posts accept 0..N images; comments accept 0..1 image. Validated
  for mime (`jpg,jpeg,png,webp,gif`), max size (5 MB), max dimensions.
- **Permissions matrix** (enforced by policies, covered by tests):

    | Action                        | Who                                           |
    | ----------------------------- | --------------------------------------------- |
    | Edit community (not the name) | community admin                               |
    | Grant/revoke community admin  | community creator                             |
    | Remove/ban member             | community admin (never the creator)           |
    | Delete community              | community creator, with password confirmation |
    | Edit post                     | post author                                   |
    | Delete post                   | post author OR community admin                |
    | Pin/unpin post                | community admin                               |
    | Edit comment                  | comment author                                |
    | Delete comment                | comment author OR community admin             |
    | Vote                          | any authenticated member                      |
    | Save post                     | any authenticated user                        |

- **Creator invariants:** the creator's `community_user.is_creator = true` row
  can never be demoted, removed, or banned. Enforce in `CommunityService` and
  `CommunityPolicy`, cover with a test.
- **Theme:** light/dark toggle persists (localStorage `appearance`), respects
  `prefers-color-scheme` for the `system` setting, no flash on load (blade already
  reads `$appearance`).
- **Responsive:** usable at 360px (mobile), 768px (tablet), ≥1024px (desktop).
  Sidebar collapses to a drawer below `lg`.
- **Masks:** all masked inputs use the shared `inputmask` wrapper with
  `showMaskOnHover: true` and `showMaskOnFocus: true`.
- **Tests:** each phase adds feature tests (Pest) for happy path + each policy
  denial + validation failure. See `testing-best-practices` skill.

---

## 6. Definition of done for the whole roadmap

1. `./rblog fresh` (migrate:fresh --seed) produces a browsable app with several
   communities, members, posts (some with images, some pinned), nested comments,
   and votes.
2. `./rblog check` (pint + stan + full Pest suite) is green.
3. A new visitor can: register → create a community → post with an image →
   comment → vote → save a post → toggle dark mode — on a phone-width viewport.
4. All plan files either deleted or folded into `.docs/` (pt-BR) per the
   agreement in `.ai/rules/plan.md` §"After implementation".
