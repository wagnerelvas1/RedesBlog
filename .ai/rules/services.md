---
paths:
    - 'app/Services/**'
---

# Services

## Services are final, own transactions and domain guards

Services are `final` classes with constructor-promoted readonly dependencies. They hold the business rules, wrap multi-step writes in `DB::transaction()`, and are the only place denormalised counters (`members_count`, `posts_count`, `comments_count`, `replies_count`, vote counters) are mutated.

Domain rule violations throw `App\Exceptions\CommunityException` (or `ValidationException`); controllers catch them and turn them into `back()->with('error', ...)`.

Creator invariants live here as well as in the DB: the `community_user.is_creator` row can never be demoted, banned or removed. `VoteService` recomputes counters with a `COUNT` under `lockForUpdate()` rather than incrementing, so they cannot drift.
