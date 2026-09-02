# Phase 0 — Foundations

Goal: everything the feature phases assume already exists. Database (PostgreSQL),
storage (S3/MinIO), the layered scaffolding pattern, the app shell (layout +
theme), shared Inertia props, and the `inputmask` wrapper. No product feature
ships here, but by the end an authenticated-or-guest user sees a styled empty
shell with a working theme toggle.

Depends on: nothing. Blocks: all other phases.

---

## 0.0 Database: PostgreSQL

The project uses **PostgreSQL 17**, not MySQL. Swap the infra before anything
else — every later migration/test assumes the `pgsql` connection.

### Docker (`docker/docker-compose.yml`)

- **Remove** the `mysql` and `phpmyadmin` services and the `mysql-data` volume.
- **Add** `postgres` + `pgadmin`:

```yaml
postgres:
    image: postgres:17-alpine
    restart: unless-stopped
    environment:
        POSTGRES_DB: ${DB_DATABASE}
        POSTGRES_USER: ${DB_USERNAME}
        POSTGRES_PASSWORD: ${DB_PASSWORD}
    ports:
        - '${FORWARD_DB_PORT:-5432}:5432'
    volumes:
        - postgres-data:/var/lib/postgresql/data
    healthcheck:
        test: ['CMD-SHELL', 'pg_isready -U ${DB_USERNAME} -d ${DB_DATABASE}']
        interval: 5s
        timeout: 5s
        retries: 20
    networks:
        - redesblog

pgadmin:
    image: dpage/pgadmin4:latest
    restart: unless-stopped
    environment:
        PGADMIN_DEFAULT_EMAIL: ${PGADMIN_EMAIL:-admin@redesblog.test}
        PGADMIN_DEFAULT_PASSWORD: ${PGADMIN_PASSWORD:-admin}
        PGADMIN_CONFIG_SERVER_MODE: 'False'
        PGADMIN_CONFIG_MASTER_PASSWORD_REQUIRED: 'False'
    ports:
        - '${FORWARD_PGADMIN_PORT:-8080}:80'
    depends_on:
        postgres:
            condition: service_healthy
    networks:
        - redesblog
```

- Add `postgres-data:` under `volumes:`.
- Point the `app` service `depends_on` at `postgres` (`condition: service_healthy`).
- _(optional)_ pre-register the connection with a mounted
  `docker/pgadmin/servers.json` so pgAdmin opens straight onto the DB.
- **Create the test database too.** Mount an init script
  `docker/postgres/initdb/10-create-test-db.sh` (runs only on first volume init):
  `psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -c "CREATE DATABASE redesblog_testing OWNER $POSTGRES_USER"`.
  Volume mount: `./postgres/initdb:/docker-entrypoint-initdb.d:ro`. If the volume
  already exists, `rblog` should also `createdb` idempotently in `cmd_dev`
  (`in_app php artisan db:create`-style — there is no such artisan command, so run
  `compose exec postgres createdb -U <user> --if-not-exists redesblog_testing || true`).

### `docker/Dockerfile` — PostgreSQL PHP extensions (BLOCKER)

The current image builds `pdo_mysql` + installs `mysql-client` only. Postgres
needs its own driver:

- Add `postgresql-dev` to the `--virtual .build-deps` apk group (compile headers).
- Add `libpq` to the permanent `apk add --no-cache` list (runtime shared lib).
- Replace `mysql-client` with `postgresql-client` in the permanent list.
- In `docker-php-ext-install`: drop `pdo_mysql`, add `pdo_pgsql pgsql`.
- After this change the image MUST be rebuilt: `./rblog build` (or `./rblog dev`
  which builds). Every later phase depends on it — do this first.

Verify inside the container: `php -m | grep -E 'pdo_pgsql|pgsql'` lists both.

### Test database (`phpunit.xml`)

Tests run against **PostgreSQL** (decided — real `citext` / partial-index / GIN
parity; SQLite cannot run the Postgres-only DDL these migrations use).

- Edit `phpunit.xml` `<php>`: delete the two lines
  `<env name="DB_CONNECTION" value="sqlite"/>` and
  `<env name="DB_DATABASE" value=":memory:"/>`; add
  `<env name="DB_CONNECTION" value="pgsql"/>` and
  `<env name="DB_DATABASE" value="redesblog_testing"/>`
  (`DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` come from `.env` / the container env).
- The `redesblog_testing` database is created by the initdb script above.
- `tests/Pest.php` already applies `RefreshDatabase` to the `Feature` suite — it
  now wraps each test in a real Postgres transaction. No test-code change needed.
- Leave `database/database.sqlite` and the sqlite-touching lines in
  `composer.json` `scripts` alone — dead but harmless.
- `./rblog test` already execs in the `app` container with `postgres` up, so no
  wrapper change beyond the test-DB creation.

### `.env.example`

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=redesblog
DB_USERNAME=redesblog
DB_PASSWORD=secret
# DB_ROOT_PASSWORD / MySQL vars removed

FORWARD_DB_PORT=5432
FORWARD_PGADMIN_PORT=8080
PGADMIN_EMAIL=admin@redesblog.test
PGADMIN_PASSWORD=admin
```

Remove `DB_ROOT_PASSWORD` and any `MYSQL_*`/phpMyAdmin vars.

### `config/database.php`

- `'default' => env('DB_CONNECTION', 'pgsql')`.
- Keep the framework's stock `pgsql` connection block (`search_path => public`,
  `sslmode => prefer`). No `strict`/`engine` keys (those are MySQL-only).

### `rblog` wrapper

- `docker-compose.yml` service names: rename the `mysql` exec target to
  `postgres`.
- Replace `cmd_mysql()` with `cmd_psql()`:
  `compose exec postgres psql -U "$(get_env DB_USERNAME)" "$(get_env DB_DATABASE)"`.
  Update the `mysql|db)` dispatch case to `psql|db)`.
- `cmd_urls()`: print the pgAdmin URL instead of phpMyAdmin.
- Wrapper help text: "Opens the psql client on the application database".

### PostgreSQL vs MySQL — schema/query gotchas (apply in every later phase)

- **`unsigned*` column modifiers are advisory only** on Postgres — Laravel's
  schema grammar creates a plain `integer`/`bigint`. Keep using
  `unsignedBigInteger`, `unsignedInteger`, etc. for readability and portability;
  just don't rely on the DB rejecting negatives (guard in the Service).
- **Case sensitivity:** Postgres string comparison is case-sensitive by default
  (MySQL's default collation was not). `communities.name`, `users.username` and
  `users.email` uniqueness + lookups must be made case-insensitive explicitly.
  **Decision: use `citext`.** Mechanics (Laravel has no `$table->citext()`):
    1. A dedicated first migration `enable_citext_extension` —
       `up()`: `DB::statement('CREATE EXTENSION IF NOT EXISTS citext')`,
       `down()`: `DB::statement('DROP EXTENSION IF EXISTS citext')`.
    2. Each CI column: create it normally (`$table->string('name', 21)` /
       `$table->string('username', 30)`), then in the **same** migration, after the
       `Schema::create`/`Schema::table` block:
       `DB::statement('ALTER TABLE communities ALTER COLUMN name TYPE citext')`.
       For `users.email` (created by the stock Laravel migration) do this in
       `add_profile_fields_to_users_table`.
    3. `down()` reverses with `ALTER COLUMN ... TYPE varchar(n)`.
       Once the column is `citext`, the `unique` validation rule and `where('name', …)`
       are case-insensitive automatically — no `lower()` in queries.
- **Materialized-path LIKE prefix** (`comments.path LIKE '/1/45/%'`): a plain
  btree index won't serve `LIKE` on a non-C locale — add a `text_pattern_ops`
  index. In the `create_comments_table` migration, **after** `Schema::create`:
  `DB::statement('CREATE INDEX comments_path_prefix ON comments (post_id, path text_pattern_ops)')`;
  `down()` (or the auto-generated drop-table) must
  `DB::statement('DROP INDEX IF EXISTS comments_path_prefix')` before dropping the
  table — raw DDL is not auto-reversed.
- **One-creator-per-community:** a partial unique index expresses it directly. In
  `create_community_user_table`, after `Schema::create`:
  `DB::statement('CREATE UNIQUE INDEX community_user_one_creator ON community_user (community_id) WHERE is_creator')`;
  `down()`: `DB::statement('DROP INDEX IF EXISTS community_user_one_creator')`.
- **`string(191)` is a MySQL utf8mb4 index-length workaround** — not needed on
  Postgres. Use `string(255)` / `text` freely (see `.plan/06-database-and-seeds.md`).
- **Boolean columns** are real `boolean` — good. `->default(false)` works.
- **`insert()` batch + returning ids:** use `->insertGetId()` per row or
  `Model::insert()` without ids; Postgres supports `RETURNING` if needed.
- **Full-text search** (TopBar search, later): Postgres `to_tsvector`/`tsquery`
  is available natively — no extra service. Out of scope for v1.
- Tests: run on Postgres against the `redesblog_testing` database — see the
  "Test database (`phpunit.xml`)" subsection above. Not SQLite (the `citext`,
  partial index, and `text_pattern_ops` DDL won't run there).

### Verify

`./rblog build` succeeds and `php -m` in the container lists `pdo_pgsql` +
`pgsql`; `./rblog artisan migrate:fresh` runs clean; `./rblog psql -c '\dt'`
lists the tables; `./rblog artisan db:show` reports the `pgsql` driver;
`./rblog test` connects to `redesblog_testing`.

---

## 0.1 S3 / object storage

### Docker (`docker/docker-compose.yml`)

Add a MinIO service and a one-shot bucket-creation service on the `redesblog`
network:

```yaml
minio:
    image: minio/minio:latest
    restart: unless-stopped
    command: server /data --console-address ":8900"
    environment:
        MINIO_ROOT_USER: ${AWS_ACCESS_KEY_ID:-redesblog}
        MINIO_ROOT_PASSWORD: ${AWS_SECRET_ACCESS_KEY:-password}
    ports:
        - '${FORWARD_MINIO_PORT:-9000}:9000'
        - '${FORWARD_MINIO_CONSOLE_PORT:-8900}:8900'
    volumes:
        - minio-data:/data
    healthcheck:
        test: ['CMD', 'mc', 'ready', 'local']
        interval: 5s
        timeout: 5s
        retries: 20
    networks:
        - redesblog

createbuckets:
    image: minio/mc:latest
    depends_on:
        minio:
            condition: service_healthy
    entrypoint: >
        /bin/sh -c "
        mc alias set local http://minio:9000 $${AWS_ACCESS_KEY_ID} $${AWS_SECRET_ACCESS_KEY};
        mc mb --ignore-existing local/$${AWS_BUCKET};
        mc anonymous set download local/$${AWS_BUCKET};
        exit 0;
        "
    networks:
        - redesblog
```

- Add `minio-data:` under `volumes:`.
- Add `minio` to the `app` service `depends_on` (condition: service_healthy).

### `.env.example` (and document in the wrapper's `.env` copy)

```dotenv
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=redesblog
AWS_SECRET_ACCESS_KEY=password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=redesblog
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/redesblog

FORWARD_MINIO_PORT=9000
FORWARD_MINIO_CONSOLE_PORT=8900
```

> Note the split: `AWS_ENDPOINT` (container-to-container, used by the SDK) vs
> `AWS_URL` (browser-facing, used to build public URLs). If the project later
> serves images through the app, revisit this.

### `config/filesystems.php`

The `s3` disk already exists. Add `'endpoint' => env('AWS_ENDPOINT')` and
`'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false)` if not
present, and keep `'throw' => true` so upload failures surface.

### Composer

`./rblog composer require league/flysystem-aws-s3-v3` — **ask the user first**
(dependency change, `.ai/rules` / CLAUDE.md).

### Wrapper

Add a `cmd_minio()` helper? Not required. But add MinIO URLs to `cmd_urls()`
output. Small edit to `rblog`.

### Verify

`./rblog artisan tinker --execute 'Storage::disk("s3")->put("smoke.txt","ok"); echo Storage::disk("s3")->get("smoke.txt");'`
should print `ok`. Add a feature test `StorageSmokeTest` that puts+gets on a
faked disk (`Storage::fake('s3')`).

---

## 0.2 Attachments (polymorphic file storage)

Used by posts (0..N) and comments (0..1). One implementation, reused.

### Migration `create_attachments_table`

| column                             | type                             | notes                  |
| ---------------------------------- | -------------------------------- | ---------------------- |
| `id`                               | `id`                             |                        |
| `attachable_type`, `attachable_id` | `morphs('attachable')`           | indexed together       |
| `disk`                             | `string`, default `s3`           |                        |
| `path`                             | `string`                         | key within the bucket  |
| `original_name`                    | `string`                         |                        |
| `mime_type`                        | `string`                         |                        |
| `size`                             | `unsignedBigInteger`             | bytes                  |
| `width`, `height`                  | `unsignedInteger` nullable       | for layout hints       |
| `position`                         | `unsignedSmallInteger` default 0 | ordering within a post |
| timestamps                         |                                  |                        |

### `app/Models/Attachment.php`

- `morphTo attachable`.
- `$fillable` via `#[Fillable([...])]` attribute (match `User` model style).
- Accessor `url(): Attribute` → `Storage::disk($this->disk)->url($this->path)`.
- Cast nothing special.

### `app/Repositories/AttachmentRepository.php`

- `create(array $data): Attachment`
- `deleteForModel(Model $model): void` — delete rows + files.

### `app/Services/AttachmentService.php`

```php
public function __construct(
    private AttachmentRepository $attachments,
    private Filesystem $disk, // resolved as Storage::disk(config('filesystems.default'))
) {}

/** @param array<UploadedFile> $files */
public function attachMany(Model $attachable, array $files, string $folder): void;
public function attachOne(Model $attachable, UploadedFile $file, string $folder): Attachment;
public function sync(Model $attachable, array $keepIds, array $newFiles, string $folder): void; // for edits
public function detachAll(Model $attachable): void;
```

- Store under `"{folder}/{ulid}.{ext}"` (e.g. `posts/01J.../abc.webp`).
- Wrap multi-file ops in a DB transaction; delete uploaded blobs on rollback.
- Read `width`/`height` with `getimagesize()` (no extra dep) or `intervention/image`
  if approved.

### Shared upload validation

`app/Http/Requests/Concerns/ValidatesAttachments.php` (trait) exposing:

```php
protected function imageRules(int $max = 1): array
{
    return [
        'images'   => ['sometimes', 'array', "max:$max"],
        'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=4000,max_height=4000'],
    ];
}
```

Tests: `AttachmentServiceTest` (unit) with `Storage::fake('s3')` — attach, sync
(add + remove), detachAll removes files.

---

## 0.3 Layered scaffolding conventions

Create the base classes so feature agents copy a real sibling, not a guess.

- `app/Repositories/` — no base class needed; each repo is a plain final class
  with constructor-injected model or using the model statically. Pick ONE style
  and document it here once the first repo (`AttachmentRepository`) exists.
  **Decision:** repositories are `final` classes, methods accept/return models or
  collections, queries built with the Eloquent query builder inside the repo.
- `app/Services/` — `final` classes, constructor-promoted deps, `@throws`
  documented, transactions via `DB::transaction()`.
- Controllers — invokable or resource controllers, one action calls exactly one
  service method (plus maybe a repo read for the response), returns
  `Inertia::render()` or `back()/to_route()`.
- Add `app/Repositories` and `app/Services` to `phpstan.neon` paths if not
  already covered (they are under `app/`, so level 7 applies).

Record this as a rule with `record-rule` (glob `app/Repositories/**`,
`app/Services/**`) once the pattern is concrete.

---

## 0.4 App shell (Inertia + React)

### Shared props — `app/Http/Middleware/HandleInertiaRequests.php`

`share()` must expose:

```php
'auth' => [
    'user' => $request->user()?->only('id', 'name', 'email', 'avatar_path') // + avatar_url appended
],
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error'   => fn () => $request->session()->get('error'),
],
'sidebar' => [
    // communities the user belongs to, for the left nav; Inertia::optional so it
    // is only computed when the page needs it
    'communities' => Inertia::optional(fn () => /* CommunityRepository::forSidebar($user) */),
],
```

Update `resources/js/types/index.ts` (`User` gains `avatar_url`, add `Community`,
`Flash` types). Keep the existing `[key: string]: unknown` escape hatch.

### Layouts — `resources/js/layouts/`

- `AppLayout.tsx` — the Reddit-like frame: fixed top bar (logo, search box,
  "Create" button, theme toggle, auth menu / login+register buttons), left
  sidebar (`CommunityNav`), main content slot, optional right rail slot.
  Below `lg` the sidebar becomes a slide-over drawer toggled from the top bar.
- `AuthLayout.tsx` — centered card for login/register.
- `GuestNotice` — inline "log in to do this" prompts on write affordances.

Wire layouts as **persistent layouts** (`Page.layout = ...`) so the shell does
not remount between visits.

### Theme

- `resources/js/hooks/useAppearance.ts` — `'light' | 'dark' | 'system'`, writes
  `localStorage.appearance` and a `appearance` cookie (so the blade `@class`
  avoids FOUC), toggles `document.documentElement.classList`.
- `resources/js/components/ThemeToggle.tsx` — 3-state control in the top bar.
- Blade already reads `$appearance`; make `HandleInertiaRequests` or a small
  middleware read the cookie into the shared view data. Confirm no flash.
- Tailwind v4: dark variant is driven by the `.dark` class (already on `<html>`).
  Define semantic color tokens in `resources/css/app.css` `@theme` (e.g.
  `--color-surface`, `--color-muted`) with light values, and override inside a
  `@variant dark { :root { ... } }` block or `.dark { ... }` — follow the
  `tailwindcss-development` skill.

### Base UI components — `resources/js/components/ui/`

Minimum set (lint ignores this folder, keep it small and generic): `Button`,
`Input`, `Textarea`, `Card`, `Avatar`, `Dropdown`, `Modal`, `Tabs`, `Badge`,
`Skeleton`. Build with Tailwind + `cn()`. No component library dependency.

---

## 0.5 Inputmask wrapper

- `./rblog npm install inputmask` (ask user; it is in the spec so approval is
  expected).
- `resources/js/components/MaskedInput.tsx`:

```tsx
import Inputmask from 'inputmask';
// forwards ref to <input>, runs Inputmask(mask, { showMaskOnHover: true,
// showMaskOnFocus: true, ...opts }).mask(el) in a useEffect, cleans up on unmount.
```

- Defaults object `MASK_DEFAULTS = { showMaskOnHover: true, showMaskOnFocus: true }`
  exported and spread into every usage — the spec pins these two options.
- Document which fields actually need masks (none are strictly required by the
  initial domain — there is no phone/CPF field yet). Provide the component now so
  later fields (profile, community contact info) use it. If a field is added,
  it MUST route through `MaskedInput`.

---

## 0.6 Routing skeleton

`routes/web.php` — replace the single welcome route:

```php
Route::get('/', HomeController::class)->name('home'); // aggregated feed

require __DIR__.'/auth.php'; // phase 1 adds this file

// feature route groups are added by their phases, e.g.:
// Route::middleware('auth')->group(function () { ... });
```

Keep `Route::inertia` only for truly static pages. Run
`./rblog artisan wayfinder:generate` after adding routes.

---

## 0.7 Tasks checklist

- [ ] `docker/Dockerfile`: drop `pdo_mysql`/`mysql-client`, add `postgresql-dev`
      (build), `libpq` + `postgresql-client` (runtime), `pdo_pgsql pgsql` exts.
      Then `./rblog build`.
- [ ] Swap Docker `mysql`/`phpmyadmin` → `postgres`/`pgadmin` (+ `postgres-data`
      volume, `app` depends_on, healthcheck).
- [ ] `docker/postgres/initdb/10-create-test-db.sh` creates `redesblog_testing`;
      mount into `/docker-entrypoint-initdb.d`; `rblog` `cmd_dev` also
      `createdb --if-not-exists redesblog_testing` idempotently.
- [ ] `.env.example`: `DB_CONNECTION=pgsql`, port 5432, drop MySQL/root vars,
      add `FORWARD_PGADMIN_PORT` + `PGADMIN_*`.
- [ ] `config/database.php` default `pgsql`; drop MySQL-only keys.
- [ ] `rblog`: `cmd_mysql` → `cmd_psql`, dispatch `psql|db)`, exec target
      `postgres`, `cmd_urls` prints pgAdmin URL.
- [ ] `enable_citext_extension` migration (create/drop extension) ordered first.
- [ ] `phpunit.xml`: replace the `sqlite`/`:memory:` env lines with
      `pgsql` + `redesblog_testing` (no `.env.testing` needed).
- [ ] `./rblog build` → `php -m` shows `pdo_pgsql`/`pgsql`;
      `./rblog artisan migrate:fresh` + `./rblog psql -c '\dt'` + `./rblog test`
      verified.
- [ ] Add MinIO + createbuckets services, volume, `app` depends_on.
- [ ] Update `.env.example` with S3/MinIO vars, `FILESYSTEM_DISK=s3`.
- [ ] `config/filesystems.php`: endpoint + path-style on `s3`, `throw => true`.
- [ ] (approval) `composer require league/flysystem-aws-s3-v3`.
- [ ] `rblog` `cmd_urls` prints MinIO console URL.
- [ ] `attachments` migration + `Attachment` model + repository + service + trait.
- [ ] `StorageSmokeTest`, `AttachmentServiceTest`.
- [ ] `HandleInertiaRequests::share()` — auth, flash, sidebar (optional prop).
- [ ] `AppLayout`, `AuthLayout`, `ThemeToggle`, `useAppearance`, appearance cookie.
- [ ] `resources/css/app.css` semantic tokens + dark overrides.
- [ ] `components/ui/*` base set.
- [ ] `npm install inputmask` + `MaskedInput.tsx` with pinned options.
- [ ] `HomeController` stub + route cleanup + `wayfinder:generate`.
- [ ] `./rblog pint` / `./rblog stan` / `./rblog test` green.

## 0.8 Acceptance

- `./rblog fresh` boots; visiting `/` renders `AppLayout` with an empty feed
  placeholder, working theme toggle (persists across reload, no flash), and a
  drawer sidebar on mobile width.
- Uploading a file via tinker to the `s3` disk works and the returned URL opens
  in a browser.
