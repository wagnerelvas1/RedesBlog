---
paths:
  - '.plan/**'
---

# Feature Planning (`.plan/`)

`.plan/` holds collaborative planning documents for new features, screens, flows, or significant changes. The user and the AI agent write these together, before implementation starts.

## When to use

- Before starting non-trivial work (a new screen, a new flow, a significant change to an existing flow), draft a plan doc here with the user instead of jumping straight to code.
- Use it as the durable counterpart to Plan Mode: when the user wants the agreed plan captured and revisited later — across sessions, or while implementation spans multiple steps — rather than discarded once approved.
- Do not create or write files in `.plan/` on your own initiative — do so only when the developer explicitly asks for it.

## Conventions

- One markdown file per feature/flow. Name it in kebab-case after the feature, e.g. `.plan/cadastro-dispositivo.md`.
- Written in en-US — the plan is an agent-facing working document, not the pt-BR business documentation that lives in `.docs/` (see the Documentation Language rule in CLAUDE.md/AGENTS.md).
- Keep each file living during planning: update it in place as the user and agent iterate, rather than creating dated snapshots or duplicate copies.
- A plan doc captures intent and design decisions (flow steps, screens, edge cases, open questions) — it is not a task tracker or a place to log implementation progress.

## After implementation

Once a plan is implemented, agree with the user on what happens to the file: delete it, keep it as historical reference, or fold any durable business rules it captured into `.docs/` (pt-BR, human-facing).
