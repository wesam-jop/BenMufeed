# Kasher — Database Schema

## Migration Domains

Migrations are grouped by business domain under `database/migrations/`:

| Folder | Domain | Tables |
|--------|--------|--------|
| `01_core` | Auth & system | users, sessions, passkeys, permissions, cache, jobs |
| `02_organization` | Organization | branches, audit_logs |
| `03_customers` | Customers | customers, addresses, favorites, loyalty, delivery_zones, approval_requests |
| `04_catalog` | Catalog | units, tax_rates, categories, products, variants, modifiers, recipes |
| `05_inventory` | Inventory | warehouses, stock_batches, movements, balances, transfers, counts, wastage |
| `06_purchasing` | Purchasing | suppliers, purchase_orders, purchase_invoices, supplier_payments |
| `07_sales` | Sales / POS | cash_registers, tables, orders, invoices, payments, refunds, otp |
| `08_manufacturing` | Manufacturing | production_orders, production_order_items |
| `09_accounting` | Accounting | chart_of_accounts, periods, journal_entries, expenses |
| `10_cms` | CMS / Site | site_settings, contact_messages, currencies, cms_pages |

**Total:** 67 tables across 10 domains.

## ERD

- Source: [`erd.mmd`](erd.mmd) — editable Mermaid diagram
- Image: [`erd.png`](erd.png) — ready-to-share PNG export

## Key Hubs

- **`branches`** — organizational anchor (users, products, warehouses, orders, accounting)
- **`products`** — catalog/inventory/sales hub (stock, orders, recipes, purchases)
- **`customers`** — portal & sales (orders, invoices, loyalty, favorites)
- **`warehouses`** — inventory operations (movements, balances, production, receiving)

## Fresh Install

```bash
php artisan migrate:fresh --seed
```
