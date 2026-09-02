---
paths:
    - '**'
    - '**/*.php'
    - phpunit.xml
---

# General

## Always use the ./rblog wrapper to run commands

The project runs in Docker (docker/docker-compose.yml) and the host has neither Node nor the same PHP/MySQL. Every command that creates or changes artifacts (dependencies, migrations, generated files, builds, tests) MUST go through `./rblog`, never directly on the host: `./rblog composer ...`, `./rblog artisan ...`, `./rblog npm ...`, `./rblog test`, `./rblog pint`, `./rblog stan`, `./rblog migrate`, `./rblog dev`. Run `./rblog` with no arguments to see the full list.

On the host, only inspection is allowed (ls, cat, grep, find, git) plus editing files. Any other command on the host requires the user to explicitly ask or authorize it.

If a command is missing, add it to the wrapper instead of working around it. Details in .claude/skills/rblog-wrapper/SKILL.md.

## Naming cases

The same case standard applies to every language in the project (PHP, JavaScript, …):

- `PascalCase` — classes.
- `camelCase` — functions and methods.
- `camelCase` — variables, properties and parameters (PSR-12 for PHP, the ecosystem standard for JS).
- `UPPER_SNAKE_CASE` — constants.

`snake_case` is only for the places the framework itself dictates it: database table and column names, migration/config/env keys, and route parameter names.

## Run pint and stan on every PHP file you change

After creating or editing any PHP file, and before reporting the work as done, run both checks on the files you touched — not on the whole project:

- `./rblog pint app/Services/PostService.php app/Models/Post.php`
- `./rblog stan app/Services/PostService.php app/Models/Post.php`

Both accept a list of paths. Fix what they report and rerun until clean.

`./rblog pint --dirty` also works and is the shortest way to format exactly what you touched: it formats the files git reports as changed, including untracked ones, so it is correct even on a fresh repository with no commits yet. PHPStan has no `--dirty` equivalent — always give `./rblog stan` the paths yourself.

PHPStan runs at level 7 (phpstan.neon) and normally covers app/, config/, database/, routes/ and bootstrap/app.php; passing a path outside those (e.g. tests/) still analyses it at the same level.

Only run the full sweep (`./rblog check` = pint + stan + tests) when the user asks for it or the change is wide enough that per-file checks miss the blast radius.

## Tests must be run through ./rblog test

The container injects the development values (`APP_ENV=local`, `DB_DATABASE=redesblog`) as real environment variables via docker-compose `env_file`. Those outrank `phpunit.xml`'s `<env>` entries even with `force="true"`, so running `php artisan test` directly inside the container executes `RefreshDatabase` against the **development** database and wipes the seeded data.

`./rblog test` (and `./rblog check`) pass `-e APP_ENV=testing -e DB_DATABASE=redesblog_testing` explicitly, which is what makes the suite safe. Always use the wrapper. `.env.testing` carries the remaining test values.
