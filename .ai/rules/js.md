---
paths:
  - 'resources/js/**'
---

# Js

## Always use ES Modules
All frontend JavaScript follows the ES Modules standard (`import`/`export`). Never rely on globals or on loose `<script>` tags that define behaviour.

## Always install libraries from npm
Any third-party library must be installed via npm and imported by package name — never copy the library's actual files into the project and never load it from a CDN. Install with the wrapper: `./rblog npm install <package>`.

## Naming cases
JavaScript follows the same case standard as the rest of the project (see [[general]]): PascalCase for classes, camelCase for functions, variables and parameters, UPPER_SNAKE_CASE for constants.
