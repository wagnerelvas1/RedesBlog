# Phase 1 — Authentication & Profile

Goal: user registration, login, logout, "remember me", password confirmation
(reused by community deletion in phase 2), and a profile the user can edit
(display name, bio, avatar).

No starter kit is installed — build auth explicitly with framework primitives
(`Auth`, `Hash`, session regeneration). Do **not** pull in Breeze/Jetstream/Fortify
without approval.

Depends on: phase 0. Blocks: phase 2+.

---

## 1.1 Database

### Migration `add_profile_fields_to_users_table`

| column        | type                   | notes                                                                                                                                                                                                                                                                                                                                    |
| ------------- | ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `avatar_path` | `string` nullable      | key on the `s3` disk                                                                                                                                                                                                                                                                                                                     |
| `bio`         | `string(500)` nullable |                                                                                                                                                                                                                                                                                                                                          |
| `username`    | `citext` unique        | handle for `/u/{username}`; generate from email/name on register, editable. Case-insensitive unique (Postgres `citext`) so `Ada` and `ada` collide and `/u/ADA` resolves. Needs the `enable_citext_extension` migration to run first (see `.plan/00-foundations.md` §0.0 / `.plan/06`). **Decision: add it** — Reddit-like URLs need it. |

This migration also runs `ALTER TABLE users ALTER COLUMN email TYPE citext` so
email uniqueness/login are case-insensitive. (The `enable_citext_extension`
migration from phase 0 ordering must precede it.)

Update `User` model:

- `#[Fillable([...])]` → add `username`, `bio`. **Not** `avatar_path` (set only
  via `AttachmentService`/service layer).
- Appended accessor `avatarUrl(): Attribute` → S3 URL or a generated fallback
  (e.g. `https://api.dicebear.com/...` is external — instead render initials in
  the `Avatar` component; keep `avatar_url` null when no avatar).
- Relationships (added incrementally by later phases, but declare here what phase
  1 needs): none yet beyond framework.

### Factory `UserFactory`

Add states: `->withAvatar()` (fakes an attachment path), and ensure `username`
is unique (`fake()->unique()->userName()`).

---

## 1.2 Backend — layers

### Routes — `routes/auth.php` (new, required by phase 0 skeleton)

| Method | URI                 | Name               | Controller                             | Middleware               |
| ------ | ------------------- | ------------------ | -------------------------------------- | ------------------------ |
| GET    | `/register`         | `register`         | `Auth\RegisterController@create`       | `guest`                  |
| POST   | `/register`         | `register.store`   | `Auth\RegisterController@store`        | `guest`                  |
| GET    | `/login`            | `login`            | `Auth\LoginController@create`          | `guest`                  |
| POST   | `/login`            | `login.store`      | `Auth\LoginController@store`           | `guest`, throttle:6,1    |
| POST   | `/logout`           | `logout`           | `Auth\LoginController@destroy`         | `auth`                   |
| GET    | `/confirm-password` | `password.confirm` | `Auth\ConfirmPasswordController@show`  | `auth`                   |
| POST   | `/confirm-password` | —                  | `Auth\ConfirmPasswordController@store` | `auth`, throttle         |
| GET    | `/settings/profile` | `profile.edit`     | `ProfileController@edit`               | `auth`                   |
| PATCH  | `/settings/profile` | `profile.update`   | `ProfileController@update`             | `auth`                   |
| DELETE | `/settings/profile` | `profile.destroy`  | `ProfileController@destroy`            | `auth`, password.confirm |

Run `./rblog artisan wayfinder:generate`.

### FormRequests (`app/Http/Requests/Auth/`, `app/Http/Requests/Profile/`)

- `RegisterRequest`: `name` req|string|max:255; `username` req|string|min:3|max:30|
  regex alnum+underscore|unique:users; `email` req|email|max:255|unique:users;
  `password` req|confirmed|`Password::defaults()`. `username` uniqueness is
  case-insensitive via `citext`. For `email`, either also convert the column to
  `citext` in the `add_profile_fields_to_users_table` migration
  (`ALTER TABLE users ALTER COLUMN email TYPE citext`) — **decision: do this** —
  or lowercase it in `AuthService::register`.
- `LoginRequest`: `email` req|email; `password` req|string; `remember` boolean.
  Add a `authenticate()` helper method that calls `Auth::attempt` with rate
  limiting (`RateLimiter`), throwing `ValidationException` with a throttle
  message — mirror the canonical Breeze `LoginRequest` shape.
- `ConfirmPasswordRequest`: `password` req|current_password.
- `UpdateProfileRequest`: `name`, `username` (unique ignoring self), `bio`
  nullable|max:500, `avatar` nullable|image rules (reuse `ValidatesAttachments`),
  `remove_avatar` boolean.

### Repository — `app/Repositories/UserRepository.php`

- `create(array $attributes): User` (hashes password? no — cast `hashed` handles
  it; pass plain).
- `update(User $user, array $attributes): User`
- `findByUsername(string $username): ?User`
- `deleteAccount(User $user): void`

### Service — `app/Services/AuthService.php`

```php
public function register(array $data): User;   // create user, fire Registered event, Auth::login
public function updateProfile(User $user, array $data, ?UploadedFile $avatar, bool $removeAvatar): User;
public function deleteAccount(User $user): void; // detach attachments, transfer/handle owned communities (see note), delete
```

- `AuthService::updateProfile` uses `AttachmentService::attachOne` for the avatar
  — but avatar is a single file on the user, not polymorphic list. **Decision:**
  store the avatar as a plain `avatar_path` string on `users` (managed by a small
  private helper in `AuthService` using `Storage::disk('s3')`), not through the
  `attachments` table. Attachments table is for post/comment images only.
- **Owned communities on account deletion:** block deletion if the user is the
  creator of any community, returning a validation error listing them ("transfer
  or delete these communities first"). Cross-reference phase 2.

### Controllers (`app/Http/Controllers/Auth/`, `ProfileController`)

Thin. Example `RegisterController@store`:

```php
public function store(RegisterRequest $request, AuthService $auth): RedirectResponse
{
    $auth->register($request->validated());
    return to_route('home');
}
```

`LoginController@store` calls `$request->authenticate()`, then
`$request->session()->regenerate()`, redirects intended.
`destroy` logs out, invalidates + regenerates token.

### Password confirmation middleware

Use Laravel's built-in `password.confirm` middleware alias
(`Illuminate\Auth\Middleware\RequirePassword`). Confirm it is registered (Laravel
13 registers it by default). The confirm screen posts to `password.confirm`,
which stores `auth.password_confirmed_at` in the session. Community deletion
(phase 2) and account deletion reuse this.

---

## 1.3 Frontend

Pages under `resources/js/pages/auth/` and `resources/js/pages/settings/`:

| Page                                  | Notes                                                                                                                                                                                       |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `auth/register.tsx`                   | `AuthLayout`. Fields: name, username, email, password + confirm. Uses `useForm` from `@inertiajs/react`, submits via Wayfinder `register.store.form()`. Inline errors.                      |
| `auth/login.tsx`                      | email, password, remember checkbox, "forgot?" (out of scope — omit or disable). Link to register.                                                                                           |
| `auth/confirm-password.tsx`           | single password field; used as an interstitial.                                                                                                                                             |
| `settings/profile.tsx`                | `AppLayout`. Avatar upload (drag/click, preview, remove), name, username, bio (char counter). Danger zone: delete account (opens `Modal`, routes through confirm-password).                 |
| `u/[username].tsx` _(public profile)_ | shows avatar, bio, join date, tabs: Posts / Comments (populated in phases 3–4; empty states now). Route `GET /u/{user:username}` → `ProfileController@show` (or a `UserProfileController`). |

Components: `AvatarUploader`, reuse `ui/Input`, `ui/Textarea`, `ui/Button`,
`ui/Modal`. Top-bar auth menu (in `AppLayout` from phase 0) now renders real
user data / login+register links based on `auth.user`.

Wayfinder: import form helpers from `@/routes` (named routes) — e.g.
`import { store as registerStore } from '@/actions/App/Http/Controllers/Auth/RegisterController'`
or the named-route variant; follow `wayfinder-development` skill for the exact
import path this project generates.

---

## 1.4 Tests (`tests/Feature/Auth/`, `tests/Feature/Profile/`)

- `RegisterTest`: screen renders for guests; valid data creates user + logs in +
  redirects; duplicate email/username rejected; weak password rejected;
  authenticated user redirected away from `/register`.
- `LoginTest`: valid credentials authenticate; wrong password fails; throttle
  after 6 attempts (assert `ValidationException` / 429-ish message); remember
  cookie set when requested; logout works.
- `PasswordConfirmationTest`: screen shown, correct password confirms, wrong
  rejected, confirmation expires.
- `ProfileUpdateTest`: name/username/bio update; username uniqueness ignores self;
  avatar upload stores on faked `s3` disk and sets `avatar_path`; `remove_avatar`
  deletes the blob and nulls the column.
- `DeleteAccountTest`: requires confirmed password; blocked when user owns a
  community; success deletes user and avatar blob.

Use `Storage::fake('s3')`, `RefreshDatabase`, model factories, `actingAs`.

---

## 1.5 Tasks checklist

- [ ] `add_profile_fields_to_users_table` migration; update `User` model + factory.
- [ ] `routes/auth.php` + include from `web.php`; `wayfinder:generate`.
- [ ] Auth FormRequests (Register/Login/ConfirmPassword) + Profile requests.
- [ ] `UserRepository`, `AuthService`.
- [ ] Auth controllers + `ProfileController` (edit/update/destroy/show).
- [ ] Verify `password.confirm` middleware alias resolves.
- [ ] Pages: register, login, confirm-password, settings/profile, u/[username].
- [ ] `AvatarUploader` component; top-bar auth menu real data.
- [ ] Feature tests above; `pint` / `stan` / `test` green.

## 1.6 Acceptance

Guest can register and is logged in; can log out and back in; can edit profile
and upload/remove an avatar; account deletion demands password re-entry and is
blocked while owning communities.
