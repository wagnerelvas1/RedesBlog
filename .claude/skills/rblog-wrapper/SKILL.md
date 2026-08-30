---
name: rblog-wrapper
description: Use this skill whenever running ANY project command for RedesBlog — composer, php artisan, npm/vite, pest/phpunit, pint, phpstan, tinker, migrations, docker compose, or starting/stopping the environment. The `./rblog` wrapper is the only sanctioned way to run commands that create artifacts (dependencies, migrations, generated files, builds), because the project runs inside Docker. Trigger before typing `composer`, `php artisan`, `npm`, `vendor/bin/*`, or `docker compose` in a Bash call.
---

# rblog — the project's mandatory wrapper

RedesBlog runs inside containers (`docker/docker-compose.yml`). The host does
**not** have Node, and the host's PHP/MySQL do not match the project environment.
Running commands directly on the host produces artifacts with the wrong user,
version and extensions.

**Rule:** every command that creates, changes or generates artifacts goes through
`./rblog`.

## Translation map

| Never run on the host           | Use                                  |
| ------------------------------- | ------------------------------------ |
| `composer install`, `composer require ...` | `./rblog composer install`, `./rblog composer require ...` |
| `php artisan make:model Post`   | `./rblog artisan make:model Post`    |
| `php artisan migrate`           | `./rblog migrate` (or `./rblog artisan migrate`) |
| `npm install`, `npm run build`  | `./rblog npm install`, `./rblog npm run build` |
| `php artisan test`              | `./rblog test`                       |
| `vendor/bin/pint`               | `./rblog pint`                       |
| `vendor/bin/phpstan analyse`    | `./rblog stan`                       |
| `php artisan tinker`            | `./rblog tinker`                     |
| `docker compose up -d`          | `./rblog up`                         |
| Starting the environment from scratch | `./rblog dev`                  |

`./rblog` with no arguments lists every available command. If a command is
missing, prefer adding it to the wrapper instead of working around the wrapper.

## What may run on the host

Only read-only and local inspection that generates no artifacts:

- `ls`, `cat`, `grep`, `find`, `sed -n`, `git ...`
- the agent's own file editing tools

Anything else on the host requires the **user to explicitly ask or authorize
it**. Do not decide on your own that "just this once" is acceptable — if the
wrapper does not cover the case, ask.

## Useful details

- The wrapper starts the containers on its own when they are down; there is no
  need to run `./rblog up` first.
- It is run from the project root: `./rblog <command>`.
- The containers run as `www-data`, already mapped to the host UID/GID, so
  generated files end up with the correct ownership on the host.
- `./rblog dev` is idempotent: it creates `.env` from `.env.example` when
  missing, installs dependencies only when needed, generates the `APP_KEY`, runs
  the migrations, creates the storage link and builds the assets.
