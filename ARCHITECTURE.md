# agriAid Backend Architecture

Laravel application layer (`app/`) + domain modules under `src/` (PSR-4 `Src\\`).

## Layers

| Layer | Path | Responsibility |
|-------|------|----------------|
| HTTP / adapters | `app/Http` | Controllers, middleware, form requests |
| Eloquent models | `app/Models` | Persistence models (Laravel) |
| Application services | `app/Services` | Use-case orchestration used today |
| Notifications | `app/Notifications` | Mail / OTP templates |
| Support | `app/Support` | Branding helpers, cross-cutting utils |
| **Domain modules** | `src/<BoundedContext>/` | DDD structure for long-term domain logic |

## Domain module map (`src/`)

Each context follows:

```text
src/<Context>/
  Domain/          # Entities, VOs, repository interfaces, domain services
  Application/     # Commands, queries, handlers (use cases)
  Infrastructure/  # Eloquent repos, external adapters
  Presentation/    # Optional HTTP DTOs / presenters
```

| Context | Purpose |
|---------|---------|
| `Identity` | Auth, OTP, roles, access codes |
| `Farmer` | Farmer profile, credibility |
| `Farm` | Estates / land registry |
| `Crop` | Crops & harvest classification |
| `Livestock` | Livestock records |
| `Marketplace` | Listings & offers |
| `Orders` | Order lifecycle |
| `Payments` | Disbursement / repayment |
| `Notifications` | Domain notification policies |
| `Reporting` | Audit & regional reports |
| `Weather` | Weather inputs |
| `AI` | Advisory / scoring AI |
| `Shared` | Shared kernel |
| `Application` / `Infrastructure` | Cross-context app & infra |

## Rules

1. **New domain behaviour** starts in `src/<Context>/Domain` (or Application), not in fat controllers.
2. Controllers in `app/Http` stay thin: validate → call service/domain → respond JSON.
3. Do not delete empty domain folders; they define the **predefined architecture**.
4. `composer.json` already maps `"Src\\": "src/"`.

## Currently live (Laravel app/)

- Auth + OTP (`AuthController`, `OTPController`, `OtpService`)
- Operations: farmers, harvests, stocks
- Sanctum + Spatie roles/permissions scaffolding

Domain folders under `src/` hold contracts and stubs so features land in the correct bounded context without inventing a second architecture.
