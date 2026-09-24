# Laundry Management System

A professional desktop application for managing laundry business operations including customers, services, orders, billing, payments, inventory, and reports.

## Overview

Laundry Management System (LMS) is a PC-only web application built with PHP 8.2 and MariaDB/MySQL. It provides a complete workflow for laundry businesses: customer management, service catalog, order processing with GST-compliant billing, payment tracking, delivery scheduling, inventory control, and financial reporting.

## Key Features

### Core Modules
- **Dashboard** — Revenue KPIs, outstanding balances, delivery counts, low-stock alerts, recent orders, 14-day revenue chart
- **Customers** — CRUD with archive/restore, order history, spending and outstanding summaries
- **Services** — Price list with per-service GST rate reference, active/archive toggle
- **Orders** — Multi-line creation, duplicate service merging, discount (percent/fixed), GST (inclusive/exclusive), automatic invoice numbering, status workflow
- **Payments** — Partial/full payments, overpayment rejection, future-date rejection, method tracking (Cash/UPI/Card/Bank/Other), automatic balance update
- **Delivery** — Upcoming/today/overdue/delivered tabs with counts and routing view
- **Inventory** — Stock tracking with low-threshold alerts, movement log (purchase/usage/adjust), overdraw prevention with row locking
- **Reports** — Revenue/orders/collections by order date, payments by payment date, service revenue, status breakdown, daily chart
- **Users & Roles** — Admin/Manager/Staff/Accountant with permission matrix, last-admin protection
- **Settings** — Business identity, GST configuration, invoice prefix/footer/terms, currency
- **Notifications** — Low stock, order ready/delivered, payment received

### Technical Highlights
- Single authoritative billing engine (`lms_bill`) used by orders, invoices, reports, and tests
- CSRF protection on all state-changing requests
- Idempotency keys for duplicate-submission safety
- Parameterized PDO queries throughout
- Role-based authorization enforced at API layer
- Database schema versioning with safe migrations
- UTF-8 throughout, INR currency with symbol

## Technology Stack

- **Backend**: PHP 8.2 (strict types), MariaDB/MySQL 10.5+
- **Frontend**: Vanilla ES6 JavaScript, CSS custom properties
- **Server**: Apache (XAMPP recommended)
- **Dependencies**: Zero external PHP/JS dependencies

## System Requirements

- PHP 8.1+ with PDO, PDO_MySQL, mbstring, json, session
- MariaDB 10.5+ or MySQL 8.0+
- Apache 2.4+ with mod_rewrite
- XAMPP 8.2+ (Windows) or equivalent LAMP/WAMP stack

## Installation

### Quick Start (XAMPP)

1. Install XAMPP 8.2+ and start Apache + MySQL
2. Copy the project to `C:\xampp\htdocs\Laundry MS\`
3. Open `http://localhost/Laundry%20MS/` — the installer runs automatically on first request
4. Default login: `admin` / `admin123`

### Manual Database Setup

```sql
CREATE DATABASE laundry_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
Then import `database.sql` or visit the application URL to trigger the installer.

### Configuration

Environment variables (optional, override defaults in `backend/config/app.php`):
```
LMS_DB_HOST=localhost
LMS_DB_NAME=laundry_db
LMS_DB_USER=root
LMS_DB_PASS=
```

### File Permissions (Linux/macOS)
```bash
chmod -R 755 /path/to/LaundryMS
chmod -R 777 /path/to/LaundryMS  # for session/upload dirs if needed
```

## Database

### Schema Overview

The installer creates/updates these tables:
- `users` — authentication and roles
- `login_attempts` — rate-limit state
- `customers` — customer directory
- `services` — service catalog with GST rate reference
- `orders` — order headers with billing snapshot
- `order_items` — order line items
- `payments` — payment records
- `inventory_items` — stock items
- `inventory_movements` — stock change log
- `settings` — key-value configuration (includes `schema_version`)
- `notifications` — system alerts
- `request_keys` — idempotency storage

### Upgrades

Run the installer again (visit any page) — it applies only missing columns/indexes/constraints and bumps `schema_version`. Safe on live data. Never truncates tables.

### Authoritative Schema

`database.sql` contains the final schema matching `backend/database/install.php`. Use for fresh installs or diff-based upgrades.

## Default Configuration

The installer seeds only essential defaults:
- Admin user: `admin` / `admin123`
- 5 standard services (Wash & Fold, Dry Clean Shirt/Suit, Ironing Shirt/Trousers)
- Business identity, GST (5% exclusive), INR currency, invoice prefix `INV`
- 4 inventory items (Detergent, Bleach, Hangers, Packaging bags)

No demo orders, customers, or payments are created.

## Application Workflow

### Customer Management
1. **Create** — Name, mobile (validated), address
2. **Edit** — Inline modal, preserves history
3. **Archive** — Soft delete (`is_active=0`) if orders exist; hard delete otherwise
4. **Restore** — Reactivate archived customers
5. **View** — Stats: order count, total spent, outstanding, last order

### Service Management
1. **Create** — Name, description, price, GST rate (catalog reference)
2. **Edit** — Price/GST changes affect future orders only
3. **Deactivate** — Soft delete if used in orders; hard delete otherwise
4. **Restore** — Reactivate

### Order Workflow
1. **Create** — Select customer, delivery date, optional pickup date, staff assignment
2. **Add lines** — Pick services, quantities; duplicate services auto-merge
3. **Discount** — None / Fixed amount / Percentage (capped at subtotal)
4. **GST** — Uses global settings rate (snapshotted); inclusive or exclusive
5. **Save** — Generates invoice number, status `Pending`, `Unpaid`
6. **Status transitions** — `Pending` → `Processing` → `Ready` → `Delivered` (or `Cancelled` from any non-terminal)
7. **Payments** — Partial or full; overpay rejected; future dates rejected; blocked on `Cancelled`
8. **Delivery** — Appears in delivery tabs by `delivery_date`
9. **Invoice** — Print-ready view with smart conditional rows (discount/GST only when applicable)

### Billing Rules (Single Source: `lms_bill()`)

```
Subtotal = Σ(price × qty)
Discount = fixed amount OR percentage of subtotal (capped at subtotal)
Taxable = Subtotal − Discount
GST = Taxable × rate / 100           (exclusive)
     or Taxable − Taxable/(1+rate/100) (inclusive)
Grand = Taxable + GST                (exclusive)
     or Taxable                      (inclusive)
CGST = SGST = GST / 2 (rounded)
```

- All money `DECIMAL(10,2)`, rounded to 2 decimals
- `grand_total` is canonical; `total_amount` mirrors it
- Historical orders never recalculate (rates/prices snapshotted)

### Payment Behavior
- Methods: Cash, UPI, Card, Bank transfer, Other
- `paid_at` = payment date (used for reports), not future-dated
- Balance = `grand_total − paid_amount`
- Status: `Unpaid` → `Partial` → `Paid`
- `Cancelled` orders: no further payments allowed
- Idempotency key per submission prevents double-charge

### Inventory
- Stock updated via movements: `purchase` (+), `usage` (−), `adjust` (set)
- `usage` beyond available stock → 422 error
- Row-level lock (`SELECT FOR UPDATE`) prevents concurrent overdraw
- Low-stock threshold triggers notification + dashboard badge

### Reports
- **By order date**: revenue, orders, discounts, GST, collected, outstanding, status breakdown, daily chart
- **By payment date**: payments grouped by method
- **Services**: quantity and revenue per service
- **Date presets**: Today, Week, Month, Year, Custom range

## API Architecture

All endpoints under `/api/` return JSON:
```json
{"success": true, "message": "...", "data": {...}}
{"success": false, "message": "...", "errors": {...}}
```

| Module | Actions |
|--------|---------|
| `auth.php` | `login`, `logout`, `me` |
| `customers.php` | `list`, `get`, `history`, `save`, `delete`, `restore` |
| `services.php` | `list`, `active`, `save`, `delete`, `restore` |
| `orders.php` | `list`, `get`, `create`, `status` |
| `payments.php` | `list`, `add` |
| `inventory.php` | `list`, `save`, `move`, `moves` |
| `reports.php` | `dashboard`, `summary` (with filters) |
| `users.php` | `roles`, `list`, `staff_options`, `save`, `toggle` |
| `settings.php` | `get`, `save` |
| `notifications.php` | `list`, `read` |
| `delivery.php` | `list` (with filters) |

### Request Requirements
- `Content-Type: application/json` or `application/x-www-form-urlencoded`
- `X-CSRF-Token` header or `csrf` form field for POST
- `idempotency_key` for create/order/payment actions (max 64 chars)
- Session cookie for authentication

### Error Codes
- `401` — Unauthenticated
- `403` — CSRF invalid / permission denied
- `404` — Not found
- `409` — Conflict (idempotent duplicate, illegal status transition)
- `422` — Validation error / overpay / overdraw
- `500` — Server error

## Security Model

- **Authentication**: Session + HttpOnly cookie, 30-min idle timeout
- **Authorization**: Role matrix enforced in `Auth::lms_can()`
- **CSRF**: Per-session token, validated on all POST
- **Idempotency**: DB-backed keys with 30-day TTL
- **SQL**: Parameterized PDO everywhere
- **XSS**: `lms_esc()` on all dynamic output
- **Rate limiting**: 5 failed logins per IP/user → 5-min lockout
- **Config protection**: `.htaccess` denies access to `config/`, `backend/`, `database.sql`

## UI / Design System

### Brand Color
- Primary: `#3C2182` (CSS variable `--primary`)
- Dark: `#2B175F` (`--primary-dark`)
- Light: `#EDE8F7` (`--primary-light`)

Defined once in `assets/css/variables.css`, referenced everywhere.

### Components
- Buttons: `.btn`, `.btn.primary`, `.btn.ghost`, `.btn.danger`, `.btn.small`
- Inputs: `.input`, `.select`, `.textarea`
- Tables: `.data` with `.tablewrap` (horizontal scroll)
- Badges: `.badge` + status class (pending/processing/ready/delivered/cancelled/paid/partial/unpaid)
- Alerts: `.alert` + type (success/error/warning/info)
- Toasts: Auto-dismiss, stacked
- Modals: Centered, ESC to close, focus trap
- Loading: `.skeleton` placeholders, button spinner
- Empty states: Consistent icon + message

### Layout
- Fixed left sidebar (260px) with role-filtered navigation
- Top bar with welcome, notifications bell, logout
- Content area max-width 1280px, centered
- Responsive grid: 4-col KPI → 2-col ≤1080px → 1-col ≤820px
- Tables horizontal scroll on narrow viewports
- Print stylesheet hides nav/sidebar, optimizes invoice

### Desktop Resolutions Tested
1280, 1366, 1440, 1600, 1920+

No mobile navigation, off-canvas drawers, hamburger menus, or PWA logic.

## Smart Contextual Behavior

- Discount row on invoice only when discount exists
- GST/Taxable/CGST/SGST rows only when GST > 0
- Payment form hidden when order fully paid or cancelled
- Status dropdown shows only valid next transitions
- Delete → archive when history exists; hard delete otherwise
- Duplicate service lines merged on order create
- Form inputs preserved on validation error
- Toast on success, inline field errors on 422
- Loading spinners on all async actions
- Bell badge shows unread notification count

## File Structure

```
Laundry MS/
├── .htaccess                 # Apache config, protects sensitive dirs
├── index.php                 # Login / redirect
├── logout.php                # Session destroy
├── database.sql              # Authoritative schema
├── README.md                 # This file
├── api/                      # JSON endpoints
│   ├── auth.php
│   ├── customers.php
│   ├── delivery.php
│   ├── inventory.php
│   ├── notifications.php
│   ├── orders.php
│   ├── payments.php
│   ├── reports.php
│   ├── services.php
│   ├── settings.php
│   └── users.php
├── assets/
│   ├── css/
│   │   ├── variables.css     # Design tokens (--primary, etc.)
│   │   ├── base.css          # Reset, typography
│   │   ├── layout.css        # App shell, grid, sidebar
│   │   ├── components.css    # Buttons, badges, alerts, toasts, modals
│   │   ├── forms.css         # Input, select, textarea
│   │   ├── tables.css        # Data tables, pager
│   │   ├── pages.css         # Page-specific overrides
│   │   └── print.css         # Invoice print styles
│   ├── js/
│   │   ├── core/
│   │   │   ├── api.js        # Fetch wrapper, CSRF, auth redirect
│   │   │   └── ui.js         # Toast, modal, confirm, pager, currency
│   │   └── pages/            # One file per page
│   └── icons/web/            # favicon.ico, icon-192.png, icon-512.png
├── backend/
│   ├── bootstrap.php         # Autoload, error handlers, lms_fatal_page
│   ├── config/
│   │   ├── app.php           # Constants (roles, statuses, etc.)
│   │   └── database.php      # lms_pdo(), lms_db()
│   ├── database/
│   │   └── install.php       # Schema creation + migrations + seeds
│   └── lib/
│       ├── Auth.php          # Session, login, roles, permissions
│       ├── Csrf.php          # Token generation/validation
│       ├── Idem.php          # Idempotency key processing
│       ├── Money.php         # lms_bill(), settings, currency
│       ├── Response.php      # JSON helpers, input parsing
│       └── Validator.php     # lms_str, lms_int, lms_money, dates
├── includes/
│   ├── header.php            # HTML head, sidebar, topbar
│   └── footer.php            # Scripts, bell poll, logout handler
├── pages/                    # Server-rendered views
│   ├── customer_view.php
│   ├── customers.php
│   ├── dashboard.php
│   ├── delivery.php
│   ├── error.php
│   ├── help.php
│   ├── inventory.php
│   ├── notifications.php
│   ├── order_create.php
│   ├── order_details.php     # Redirects to order_view
│   ├── order_view.php
│   ├── orders.php
│   ├── payments.php
│   ├── reports.php
│   ├── services.php
│   ├── settings.php
│   └── users.php
└── tests/
    └── billing.php           # 56 billing/invoice unit checks
```

## Verification & Testing

### Static Checks
```bash
php -l **/*.php    # All files: 0 syntax errors
```

### Unit Tests
```bash
C:\xampp\php\php.exe tests\billing.php
# Expected: 56 passed, 0 failed
```

### Runtime Verification (requires running Apache + MySQL)
1. Login `admin` / `admin123`
2. Dashboard loads with KPIs
3. Customer CRUD + archive/restore
4. Service CRUD + deactivate/restore
5. Order create → status transitions → invoice → payments
6. Payment overpay/duplicate rejection
7. Inventory move + overdraw rejection
8. Reports by order date + payment date
9. Notifications list + mark read
10. Settings save
11. User create/toggle with guards
12. Logout + re-login
13. All pages return HTTP 200 with app shell
14. Brand color `#3C2182` present in served CSS

## Backup & Maintenance

```bash
# Database dump
mysqldump -u root laundry_db > backup_$(date +%F).sql

# Restore
mysql -u root laundry_db < backup_2026-09-22.sql
```

- Backup `database.sql` and custom settings regularly
- `request_keys` auto-purged after 30 days
- `login_attempts` auto-cleaned daily

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| "Database unavailable" | MySQL down / wrong credentials | Check XAMPP MySQL, `backend/config/app.php` |
| 401 on every request | Session cookie not sent | Check `session.cookie_path`, HTTPS vs HTTP |
| 403 on POST | Missing/invalid CSRF | Ensure `X-CSRF-Token` or `csrf` field sent |
| 422 overpay | Amount > balance | Reduce amount or check `paid_amount` |
| 422 overdraw | Usage > stock | Purchase stock first |
| Invoice shows 0.00 | GST disabled or 0% | Enable GST in Settings |
| Styles missing | `variables.css` not loaded | Check Apache `Alias` / path, clear browser cache |
| "strict_types" error | BOM before `<?php` | Save file as UTF-8 without BOM |

## Known Limitations

- PC-only UI (no mobile/responsive below 820px beyond table scroll)
- Single tax jurisdiction (CGST/SGST only; no IGST/inter-state)
- No email/SMS notifications (in-app only)
- No barcode/QR code generation (removed)
- No multi-branch/tenant support
- No purchase order / vendor management
- Reports use server timezone (Asia/Kolkata)
- No audit log beyond `inventory_movements` and `notifications`

## College / Project Usage Notes

- **Academic integrity**: This is a complete, functional application. Understand the code before submitting.
- **Customization**: Modify `backend/config/app.php` for roles/statuses, `variables.css` for branding.
- **Extension points**: Add API actions following existing pattern; add pages by creating `pages/xyz.php` + `assets/js/pages/xyz.js`.
- **Database changes**: Edit `backend/database/install.php` (add `lms_add_col`/`lms_add_index` calls) and `database.sql`; bump `schema_version` in a new migration closure.
- **Testing**: Run `php -l` and `tests/billing.php` after any change.

## License

Proprietary — college project submission. No warranty.

---

*Laundry Management System — Final Release*