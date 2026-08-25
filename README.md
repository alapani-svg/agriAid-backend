# agriAid Backend

The backend API for **agriAid**, a modern smart agriculture platform built using Laravel 12 and Domain-Driven Design (DDD).

## Technology Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Laravel Sanctum
- Spatie Permission
- Spatie Laravel Data
- Pest
- Domain-Driven Design (DDD)

## Project Status

🚧 Under active development.

## Implemented Features

### Buyer Request for Quotation (RFQ)
- `buyer_requests` migration, model, factory, resource, and `BuyerRequestController`.
- Full state flow: `PENDING` → `FARMER_QUOTED` → `BUYER_ACCEPTED` → `FARMER_APPROVED`.
- Buyer can reject and farmer can reject with reason.
- Buyer dashboard: post product demands and manage quotes.
- Farmer dashboard: view buyer demands, submit prices, approve/reject deals.

### Harvest Records
- `harvest_records` migration, model, factory, resource, and `HarvestRecordController`.
- Frontend `harvestRecordsApi` and `FarmerDashboard` integration.

### Purchase Orders & Payments
- Payment methods: `MoMo`, `Orange Money`, `Bank Transfer`, `Cash on Delivery`.
- Validated and stored in `PurchaseOrderController`.

### Marketplace Dashboards
- Liquid Glass dashboards: Farmer, Warehouse, Institution, Admin, Buyer.
- Top navigation with Buyer / Warehouse / Institution tabs.
- In-dashboard role switcher to access Farmer and Admin.
- Buyer and Farmer RFQ managers mounted in their dashboards.

### Real-time Notifications
- SSE stream endpoint for live notifications.
- API clients load real backend data for market listings, loans, purchase orders, warehouse receipts, audit logs, notifications, institutions, buyers, and harvest records.

## Repository Structure

This project follows a Domain-Driven Design architecture where business logic is organized into domains instead of the traditional Laravel MVC structure.

```
src/
    Domain/
    Application/
    Infrastructure/
    Shared/
```

## Contributing

See the root `CONTRIBUTING.md` for a step-by-step guide on adding new backend and frontend features.

## License

MIT