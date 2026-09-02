---
paths:
    - 'app/Repositories/**'
---

# Repositories

## Repositories are final classes and own all Eloquent

Repositories are `final` classes with no base class. All Eloquent lives here — services and controllers never build queries.

Methods accept and return models, collections or paginators. When a model's `#[Fillable]` list excludes owner columns (e.g. `Vote` only allows `value`), set them explicitly instead of `updateOrCreate`/`fill` — mass assignment silently drops them and the insert fails or writes a wrong row.

`CommunityRepository` is registered with `$this->app->scoped()` in `AppServiceProvider` so its per-request membership cache is shared with the policies. Without that, every policy check re-queries `community_user` once per rendered post/comment.
