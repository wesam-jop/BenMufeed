# Database Migrations

Migrations are organized by business domain. Laravel loads them recursively — run order is by filename timestamp.

| Folder | Description |
|--------|-------------|
| `01_core` | Users, auth, permissions, cache, queues |
| `02_organization` | Branches, audit logs |
| `03_customers` | Customers, portal, loyalty, approvals |
| `04_catalog` | Products, categories, modifiers, recipes |
| `05_inventory` | Warehouses, stock movements, transfers, counts |
| `06_purchasing` | Suppliers, purchase orders & invoices |
| `07_sales` | POS, orders, invoices, payments, tables |
| `08_manufacturing` | Production orders |
| `09_accounting` | Chart of accounts, journal entries, expenses |
| `10_cms` | Site settings, CMS pages, currencies |

See [`docs/database/README.md`](../../docs/database/README.md) for full schema docs and ERD.
