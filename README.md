# agriAid Backend

Laravel API for **agriAid**, a digital agricultural-finance platform. The
backend helps farmers build a verifiable agricultural record and gives lenders
and administrators the evidence required to provide financial aid responsibly.

The platform connects:

```text
Farmer identity and estate
        ↓
Harvest and crop records
        ↓
Warehouse storage and receipts
        ↓
Credibility score and financing review
        ↓
Marketplace, repayment, reporting, and administration
```

## Core objective

agriAid's primary objective is **financial inclusion for farmers**. A farmer's
estate, harvests, verified stock, warehouse receipts, movements, and platform
history form a traceable record that can be used by lenders to assess financing
eligibility. The marketplace and warehouse features support this objective by
protecting and commercialising the farmer's production.

## Technology

- PHP 8.2+
- Laravel 12
- MySQL (SQLite is supported by the test suite)
- Laravel Sanctum API authentication
- Spatie Laravel Permission for role-based access
- Spatie Laravel Data for typed request/response data
- Pest/PHPUnit for automated tests
- Dompdf for PDF exports
- Endroid QR Code for digital warehouse receipts
- Laravel queues, notifications, storage, and mail

## Roles

The API supports the following principal roles:

| Role | Main responsibility |
| --- | --- |
| `farmer` | Maintain identity and estates, record harvests, store produce, manage listings, and respond to lender access requests |
| `warehouse` | Receive and validate stock, manage warehouses, log sensor readings and gate movements, and issue receipts |
| `lender` | Review credibility, request farmer access, and assess financing applications |
| `buyer` | Browse validated stock and place marketplace orders |
| `government` | Access role-specific reporting and regional oversight |
| `admin` | Manage users, farmers, warehouses, loans, institutions, products, notifications, and audit records |

## Repository structure

The project uses a Laravel application shell with domain-oriented modules. The
autoload configuration maps both `app/` and `src/` to the `App\` namespace.

```text
app/
├── Http/Controllers/          # Cross-domain and administrative controllers
├── Models/                    # Eloquent persistence models
└── Services/                  # Shared application services

src/
├── Auth/                      # Identity and authentication boundaries
├── Credibility/               # Farmer scoring and financing evidence
├── Farm/                      # Harvest domain
├── Farmer/                    # Farmer profile and estate domain
├── Infrastructure/           # Shared infrastructure adapters
├── Notifications/             # In-app notification domain
├── Receipt/                   # Warehouse receipt domain
├── Shared/                    # Shared contracts and value objects
├── Stock/                     # Stock tracking and automatic updates
└── Warehouse/                 # Warehouse, telemetry, and logistics domains

routes/
├── api.php                   # Authenticated and public API routes
└── web.php                   # Laravel web routes

database/
├── migrations/               # Database schema changes
├── factories/                # Test data factories
└── seeders/                 # Development seeders

tests/
├── Feature/                  # HTTP and integration tests
└── Unit/                     # Domain and service tests
```

## Requirements

Install the following before starting:

- PHP 8.2 or newer
- Composer 2
- MySQL 8 or MariaDB
- Node.js 20+ and npm (only needed for Laravel asset tooling)
- Git

On Windows, PHP and Composer may be installed through Laragon, XAMPP, or
standalone distributions. Make sure `php`, `composer`, and `node` are
available in PowerShell.

## Local installation

From the backend directory:

```powershell
cd C:\Projects\agriAid\agriAid-backend
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Create a MySQL database named `agriaid`, then update the database values in
`.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agriaid
DB_USERNAME=root
DB_PASSWORD=
```

Run the schema and create the public storage link:

```powershell
php artisan migrate
php artisan storage:link
```

The default seeder creates only a basic test user. It does not create a full
farmer, warehouse, lender, buyer, or administrator demo dataset. Create role
accounts through the application or an approved local seeder before a
multi-role demonstration.

## Environment configuration

Copy `.env.example` to `.env` for local development. Important values include:

| Variable | Purpose |
| --- | --- |
| `APP_URL` | Backend base URL, normally `http://localhost:8000` |
| `FRONTEND_URL` | Frontend origin used by CORS and links |
| `DB_*` | MySQL connection |
| `SANCTUM_STATEFUL_DOMAINS` | Allowed browser origins for Sanctum |
| `FILESYSTEM_DISK` | Upload storage disk |
| `QUEUE_CONNECTION` | Queue driver; `sync` is convenient locally |
| `MAIL_*` | Password-reset and notification mail |
| `LOG_LEVEL` | Application logging detail |

Never commit `.env`, production credentials, API keys, or database passwords.
For production, use `.env.production.example` as the starting point.

## Run the API locally

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

The API is then available at:

```text
http://localhost:8000
```

Laravel's Composer scripts also provide:

```powershell
composer run dev
```

That command starts the Laravel server, queue listener, log viewer, and
frontend asset server configured by the project.

## API surface

All API endpoints are prefixed by `/api`. Most business endpoints require a
Sanctum bearer token and a permitted role.

### Authentication and account security

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
PUT    /api/auth/profile
PUT    /api/auth/password
POST   /api/auth/avatar
GET    /api/auth/sessions
POST   /api/otp/generate
POST   /api/otp/verify
```

### Farmer and production

```text
GET/POST        /api/farmers
GET/PUT         /api/farmers/{id}
GET/POST/DELETE /api/farmer-estate-photos
GET/POST        /api/harvests
POST            /api/harvests/{id}/send-to-warehouse
POST            /api/harvests/{id}/store
GET             /api/stocks
POST            /api/verify-image
POST            /api/stocks/{id}/verify-match
```

### Warehouses and receipts

```text
GET/POST        /api/warehouses
GET             /api/warehouses/me
PATCH           /api/warehouses/{id}/aeration
GET/POST        /api/warehouses/{id}/sensor-readings
GET/POST        /api/warehouses/{id}/gate-logs
GET             /api/warehouse-receipts
GET             /api/warehouse-receipts/{id}
```

Sensor readings are optional telemetry. The API deliberately handles a missing
telemetry table without exposing SQL errors or taking down the rest of the
warehouse workspace.

### Financial aid and credibility

```text
GET  /api/farmers/{id}/credibility-score
GET  /api/admin/credibility-scores
GET  /api/admin/financing-stats
GET  /api/farmer-access-requests
POST /api/farmer-access-requests
PUT  /api/farmer-access-requests/{id}/approve
PUT  /api/farmer-access-requests/{id}/deny
GET  /api/admin/loans
PUT  /api/admin/loans/{id}/status
```

The credibility and warehouse evidence are intended to support financing
decisions; they do not automatically guarantee loan approval.

### Marketplace and administration

```text
GET/POST        /api/store/available-stock
GET             /api/store/my-stock
POST            /api/store/my-stock/{id}/publish
POST/PUT        /api/store/my-store
GET/POST        /api/store/orders
PATCH           /api/store/orders/{id}/status
GET/PATCH       /api/admin/store-orders
GET/POST/PUT/DELETE /api/admin/users
GET/PUT/DELETE  /api/admin/farmers
GET/PATCH       /api/admin/notifications
GET             /api/audit-logs
```

For the authoritative route list, see `routes/api.php`.

## Financial-aid workflow

The intended end-to-end financing flow is:

1. A farmer registers and completes a profile.
2. The farmer records an estate and production capacity.
3. The farmer records a harvest with crop, quantity, date, and quality data.
4. The harvest is sent to an assigned warehouse.
5. The warehouse receives and validates the stock.
6. A digital receipt provides evidence that the commodity exists in storage.
7. Stock movements and verified activity contribute to the farmer's credibility.
8. A lender requests access to the farmer's dossier.
9. The farmer approves or denies access.
10. The lender reviews the evidence and manages the financing decision.
11. Admin users monitor the process through loan, credibility, and audit screens.

## Testing and quality checks

Run the full test suite:

```powershell
php artisan test
```

Run unit tests only:

```powershell
php artisan test --testsuite=Unit
```

Useful maintenance commands:

```powershell
php artisan route:list
php artisan migrate:status
php artisan config:clear
php artisan cache:clear
php artisan storage:link
```

## Deployment

The repository includes Docker deployment assets and a detailed
[`DEPLOYMENT.md`](./DEPLOYMENT.md). The production pattern is:

1. Configure `.env` from `.env.production.example`.
2. Build the Docker image or run `docker compose build`.
3. Start the services with `docker compose up -d`.
4. Run migrations and generate the application key on first deployment.
5. Put HTTPS termination in front of the application.
6. Persist `storage/app/public` and the database.
7. Configure queues, mail, backups, and monitoring.

Do not enable production debugging or expose raw exception details. The
application's exception handling returns controlled API errors while detailed
diagnostics remain in server logs.

## Troubleshooting

### Database connection failure

Check that MySQL is running and that `DB_*` values match the database server.
Then run:

```powershell
php artisan migrate:status
```

### Sanctum or CORS authentication failure

Check `APP_URL`, `FRONTEND_URL`, and `SANCTUM_STATEFUL_DOMAINS`. Clear cached
configuration after changing `.env`:

```powershell
php artisan config:clear
```

### Uploaded images are not visible

Run `php artisan storage:link`, verify `FILESYSTEM_DISK`, and confirm that
`public/storage` points to `storage/app/public`.

### Warehouse says unavailable

Confirm the API is running, the user has the `warehouse` role, and a warehouse
is assigned. Optional sensor telemetry can be absent; the warehouse workspace
should still load without the `warehouse_sensor_readings` table.

## Related project

The React client is maintained separately in the
[agriAid frontend repository](https://github.com/alapani-svg/agriAid-frontend).

## Contributors

- AGOUFACK ALAPANI CORANTIN JUNIOR
- TSEHOULE NGALOCK BLONDEL KEVIN
