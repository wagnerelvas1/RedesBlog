---
paths:
  - 'app/**'
---

# App

## Layered architecture: Model / Repository / Service / Controller
The project follows a layered structure:
- Model (app/Models) — data definition: table attributes, casts, Eloquent relationships.
- Repository (app/Repositories) — data manipulation: queries, persistence.
- Service (app/Services) — business logic/rules.
- Controller (app/Http/Controllers) — validation (via FormRequest), orchestrates the call to the Service/Repository and returns the response.

Controllers must not contain business logic or direct Eloquent queries — delegate to Service/Repository.

Every single route that receives parameters via query string or request body (GET with filters, POST, PUT, PATCH, DELETE) must validate the data with a dedicated FormRequest class (app/Http/Requests) — never validate inline in the Controller. See [[requests]] for details.
