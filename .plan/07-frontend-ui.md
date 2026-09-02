# Phase 7 — Frontend UI, theme & responsiveness

Cross-cutting reference. Consult while building any page. Owns the component
library contract, the responsive shell, dark mode, and the empty/loading-state
policy. Look-and-feel target: **Reddit** (dense feed, left vote rail, community
sidebar, rounded cards, muted surfaces).

Activate the `tailwindcss-development` skill for every component task. Use the
`wayfinder-development` skill for every backend call.

---

## 7.1 Stack recap

- Inertia v3 + React 19 + TS. Pages in `resources/js/pages`, one per screen.
- Tailwind v4 (`@tailwindcss/vite`), config-less; tokens in
  `resources/css/app.css` `@theme`. Dark mode = `.dark` class on `<html>`
  (blade already sets it from `$appearance`).
- Prettier: 4-space, single quotes, semis, `printWidth: 80`, Tailwind class
  sorting on (`clsx`/`cn`/`cva` aware). `cn()` in `resources/js/lib/utils.ts`.
- Wayfinder-generated code in `resources/js/actions|routes|wayfinder` is
  lint-ignored — never edit by hand, regenerate.
- React Compiler is on (babel preset) — do not hand-memoize unless profiling says
  so.

## 7.2 Design tokens (`resources/css/app.css`)

Add semantic CSS variables (light values on `:root`, dark overrides). Suggested
set — map to Tailwind via `@theme inline`:

```
--color-bg            page background        light #f6f7f8  / dark #030303
--color-surface       card background        light #ffffff  / dark #1a1a1b
--color-surface-2     nested/hover surface   light #f0f2f5  / dark #272729
--color-border        hairlines              light #ccced1  / dark #343536
--color-text          primary text          light #1c1c1c  / dark #d7dadc
--color-text-muted    meta text             light #7c7c7c  / dark #818384
--color-primary       brand / links / CTA   #ff4500 (Reddit orange), same both
--color-upvote        #ff4500
--color-downvote      #7193ff
--color-focus         focus ring            primary @ 60%
```

Everything else uses Tailwind's default palette. Never hardcode hex in
components — use `bg-surface`, `text-muted`, etc.

## 7.3 Responsive shell (`AppLayout`)

Breakpoints: base (mobile ≥360), `md` (tablet ≥768), `lg` (desktop ≥1024),
`xl` (≥1280, enables right rail).

```
┌───────────────────────────────────────────────┐
│ TopBar (sticky, h-12): ☰(mobile) logo  ⌕search   ＋Create  ☾theme  ▾user │
├───────────┬───────────────────────────┬───────┤
│ Sidebar   │  Main (max-w-[640px])      │ Right │
│ (lg+, w-64│  feed / post / form        │ rail  │
│ sticky)   │                           │ (xl+) │
└───────────┴───────────────────────────┴───────┘
```

- **< lg:** sidebar hidden, opened as a left slide-over `Drawer` from `☰`.
  Right rail content (AboutCard etc.) moves inline above/below main content.
- **Main column** capped at ~640px like Reddit; centered.
- TopBar search is a `Combobox` (community + post search) — can ship as a simple
  input that navigates to `/communities?q=` in v1, upgrade later.
- Persistent layout: `Page.layout = (page) => <AppLayout>{page}</AppLayout>` so
  scroll/state survive Inertia visits.
- Bottom-nav on mobile (optional): Home / Communities / Create / Saved / Profile.

## 7.4 Component library (`resources/js/components/`)

### Primitives — `components/ui/`

`Button` (variants: primary/secondary/ghost/danger, sizes sm/md), `IconButton`,
`Input`, `Textarea`, `Select`, `Checkbox`, `Toggle`, `Card`, `Avatar`
(image or initials fallback), `Badge`, `Dropdown`/`Menu`, `Modal`/`Dialog`,
`Drawer`, `Tabs`, `Tooltip`, `Skeleton`, `Spinner`, `Toast`/`Flash`,
`Pagination`, `EmptyState`, `ConfirmDialog` (wraps password-confirm flow when
`requirePassword`), `Lightbox`.

### Domain components

| Component                                                         | Used in                                     | Notes                                                                      |
| ----------------------------------------------------------------- | ------------------------------------------- | -------------------------------------------------------------------------- |
| `MaskedInput`                                                     | any masked field                            | phase 0; `showMaskOnHover/Focus: true` pinned                              |
| `MarkdownContent`                                                 | post body, comment, community rules         | render + sanitize (npm lib, e.g. `marked` + `dompurify`); no raw HTML      |
| `RelativeTime`                                                    | everywhere                                  | "5h", "2d"; `<time>` with title=full date                                  |
| `ThemeToggle`                                                     | TopBar                                      | 3-state light/dark/system                                                  |
| `VoteControl`                                                     | PostCard, CommentNode                       | phase 5; vertical + horizontal variants                                    |
| `PostCard`                                                        | feeds, post page                            | `compact` and `full` variants                                              |
| `PostList`                                                        | all feeds                                   | infinite scroll + skeletons                                                |
| `ImageUploader`                                                   | post form (multi), comment/profile (single) | drag-drop, preview, reorder, remove, client-side size/type/dimension guard |
| `ImageGallery`                                                    | post page                                   | carousel / grid + `Lightbox`                                               |
| `CommunityHeader`                                                 | community pages                             | banner, avatar, join/leave, admin gear                                     |
| `CommunityCard`                                                   | communities index, right rail               |                                                                            |
| `AboutCard`                                                       | community right rail                        | description, rules link, creator, created date                             |
| `JoinButton`                                                      | wherever a community shows                  | optimistic                                                                 |
| `CommentTree` / `CommentNode` / `CommentForm` / `CommentComposer` | post page                                   | phase 4                                                                    |
| `MemberRow` / `RoleBadge`                                         | community members settings                  |                                                                            |
| `SortTabs` / `CommentSortTabs`                                    | feeds / comments                            | choice persisted in localStorage                                           |
| `UserMenu`                                                        | TopBar                                      | avatar → profile, saved, settings, logout                                  |
| `CommunityNav`                                                    | sidebar                                     | user's communities + "create" + explore link                               |
| `GuestGate`                                                       | write affordances                           | renders children only if `auth.user`, else login prompt / redirect helper  |

## 7.5 Page inventory (`resources/js/pages/`)

| Route name                                | File                                               | Phase |
| ----------------------------------------- | -------------------------------------------------- | ----- |
| `home`                                    | `home.tsx`                                         | 3     |
| `register` / `login` / `password.confirm` | `auth/*.tsx`                                       | 1     |
| `profile.edit`                            | `settings/profile.tsx`                             | 1     |
| public profile (`/u/{username}`)          | `u/[username].tsx`                                 | 1/3/4 |
| `communities.index`                       | `communities/index.tsx`                            | 2     |
| `communities.create`                      | `communities/create.tsx`                           | 2     |
| `communities.show`                        | `communities/show.tsx`                             | 2/3   |
| `communities.about`                       | `communities/about.tsx`                            | 2     |
| `communities.settings.edit`               | `communities/settings/edit.tsx`                    | 2     |
| `communities.members.index`               | `communities/settings/members.tsx`                 | 2     |
| `posts.create` / `posts.edit`             | `posts/create.tsx` / `posts/edit.tsx`              | 3     |
| `posts.show`                              | `posts/show.tsx`                                   | 3/4/5 |
| `posts.saved`                             | `saved/index.tsx`                                  | 3     |
| error pages (403/404/500)                 | `errors/*.tsx` (Inertia v3 custom exception pages) | 0/7   |

## 7.6 State: empty / loading / error (mandatory)

- **Deferred / infinite-scroll props:** always render a pulsing `Skeleton`
  matching the final layout (Inertia v3 rule: deferred props need an animated
  skeleton). No layout shift.
- **Empty states:** every list has an `EmptyState` (icon + line + CTA):
  no communities, empty feed, no comments ("Be the first to comment"), no saved
  posts, no members.
- **Optimistic actions** (vote, save, join): update immediately, roll back +
  `Toast` on failure (Inertia v3 optimistic updates).
- **Flash messages:** `flash.success` / `flash.error` from shared props →
  `Toast`. Wire once in `AppLayout`.
- **Forms:** disable submit while `processing`; show inline field errors from
  `useForm().errors`; keep file inputs controlled for previews.
- **Errors:** custom `errors/403|404|500` pages themed to the shell; 403 explains
  the missing permission where possible.

## 7.7 Accessibility & polish

- All interactive elements keyboard-reachable; visible focus ring
  (`--color-focus`). `Modal`/`Drawer` trap focus, close on Esc, restore focus.
- Vote arrows: `<button>` with `aria-pressed` + `aria-label`.
- Images: `alt` from `original_name` or a sensible default; lazy-load below fold.
- `prefers-reduced-motion`: gate the welcome-style animations and skeleton pulse.
- Color contrast ≥ 4.5:1 for text in both themes (check the tokens above).
- `RelativeTime` uses `<time datetime>`; screen-reader gets the full timestamp.

## 7.8 Theme toggle details

- `useAppearance()` hook: state `'light' | 'dark' | 'system'`.
    - on change: write `localStorage.appearance` **and** `document.cookie`
      (`appearance=...; path=/; max-age=31536000; SameSite=Lax`).
    - apply: `system` → match `window.matchMedia('(prefers-color-scheme: dark)')`,
      add/remove `.dark` on `document.documentElement`; subscribe to media changes
      while in `system`.
- Server: a lightweight middleware (or extend `HandleInertiaRequests`) reads the
  `appearance` cookie and passes `appearance` to the root view so blade's
  `@class(['dark' => ...])` prevents FOUC. `system` → let blade default (no
  class) and let the inline head script decide before paint (add a tiny
  `<script>` in `app.blade.php` head that reads cookie/localStorage and toggles
  `.dark` synchronously).

## 7.9 Tasks checklist

- [ ] Tokens + dark overrides in `resources/css/app.css`; remove hardcoded hex
      from `welcome.tsx` or delete that page.
- [ ] `AppLayout` + `AuthLayout` + `Drawer` sidebar; persistent layout wiring.
- [ ] `useAppearance` + `ThemeToggle` + head anti-FOUC script + cookie middleware.
- [ ] `components/ui/*` primitives.
- [ ] Domain components table in 7.4 (built alongside their phases, tracked here).
- [ ] Flash → Toast wiring in `AppLayout`.
- [ ] Custom `errors/403|404|500` pages.
- [ ] `npm install` markdown + sanitizer lib (approval), wire `MarkdownContent`.
- [ ] Responsive pass at 360 / 768 / 1024 / 1280.
- [ ] a11y pass (focus, aria, contrast, reduced-motion).
- [ ] `npm run build` / lint (`./rblog npm run check`) clean.

## 7.10 Acceptance

Every page renders correctly at mobile / tablet / desktop widths; the sidebar is
a drawer on mobile; light/dark/system toggle works with no flash and persists;
every list has an empty state; deferred data shows a skeleton; vote/save/join
feel instant and roll back on error; keyboard navigation reaches everything with
a visible focus ring.
