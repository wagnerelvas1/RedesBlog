---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## FormRequest required for every route that takes input
Every single route that receives parameters via query string or request body (GET with filters, POST, PUT, PATCH, DELETE) must have a dedicated FormRequest class validating the expected data — never validate inline inside the Controller.
