# Laundry Management System

A professional desktop-focused web application for managing laundry business operations — from customers and services to orders, billing, payments, deliveries, inventory, and reports.

## Overview

**Laundry Management System (LMS)** is a standalone business management application built with PHP 8.2 and MariaDB/MySQL.

It provides a centralized workflow for:

- Customer management
- Laundry service management
- Order processing
- GST-aware billing
- Payment tracking
- Delivery management
- Inventory control
- Staff and role management
- Notifications
- Business reports
- Invoice and business settings

Designed for local deployment, LMS works with Apache and can be easily run using XAMPP or a similar PHP/MySQL environment.

## Features

### Dashboard
- Revenue and collection overview
- Outstanding balance tracking
- Delivery statistics
- Low-stock alerts
- Recent orders
- 14-day revenue chart

### Customers
- Create, edit, view, archive, and restore customers
- Customer order history
- Spending and outstanding summaries
- Mobile number and address management
- Safe deletion based on order history

### Services
- Service catalog management
- Pricing and descriptions
- GST rate reference
- Activate/deactivate services
- Restore archived services
- Future-order pricing control

### Orders
- Multi-service order creation
- Customer and delivery date selection
- Optional pickup date
- Staff assignment
- Automatic duplicate-service merging
- Fixed or percentage discounts
- GST inclusive/exclusive billing
- Automatic invoice numbering
- Order status workflow

### Billing & Invoices
- Centralized billing calculation
- Discount and taxable amount calculation
- GST, CGST, and SGST calculation
- Inclusive and exclusive GST modes
- Print-ready invoices
- Historical price and tax snapshots

### Payments
- Partial and full payments
- Cash, UPI, Card, Bank Transfer, and Other
- Automatic balance calculation
- Unpaid / Partial / Paid status
- Overpayment validation
- Duplicate-submission protection

### Delivery
- Upcoming deliveries
- Today's deliveries
- Overdue deliveries
- Delivered orders
- Delivery count indicators

### Inventory
- Stock item management
- Purchase, usage, and adjustment movements
- Low-stock thresholds
- Stock movement history
- Overdraw prevention
- Inventory alerts

### Reports
- Revenue and order summaries
- Collection and outstanding amounts
- Discount and GST totals
- Order status breakdown
- Service-wise revenue
- Payment method summaries
- Today / Week / Month / Year / Custom date filters

### Users & Roles
- Admin
- Manager
- Staff
- Accountant
- Role-based permissions
- User activation/deactivation
- Last-admin protection

### Notifications
- Low-stock alerts
- Order ready notifications
- Order delivered notifications
- Payment notifications
- Unread notification counter

## Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 |
| Database | MariaDB 10.5+ / MySQL 8.0+ |
| Frontend | HTML5, CSS3, Vanilla ES6 JavaScript |
| Server | Apache 2.4+ |
| Database Access | PDO |
| API | JSON |
| Currency | INR (₹) |

## Architecture

The application follows a structured frontend/backend architecture:

```text
Laundry MS/
├── api/                 # JSON API endpoints
├── assets/              # CSS, JavaScript, icons
├── backend/             # Configuration, database, core libraries
├── includes/            # Shared layout components
├── pages/               # Application views
├── tests/               # Billing checks
├── database.sql         # Database schema
└── index.php            # Application entry point
```

Core backend libraries handle:

- Authentication and permissions
- CSRF protection
- Input validation
- Billing calculations
- Idempotency
- JSON responses
- Database access

## Billing

All billing operations use a centralized billing engine through `lms_bill()`.

```text
Subtotal
   ↓
Discount
   ↓
Taxable Amount
   ↓
GST
   ↓
Grand Total
```

The system supports both GST-inclusive and GST-exclusive calculations while preserving historical order pricing and tax information.

## Order Workflow

```text
Customer
   ↓
Create Order
   ↓
Select Services
   ↓
Apply Discount / GST
   ↓
Generate Invoice
   ↓
Pending
   ↓
Processing
   ↓
Ready
   ↓
Delivered
```

Payments can be recorded at any stage where payment is permitted by the order state.

## Database

The application uses a structured relational database with tables for:

- `users`
- `login_attempts`
- `customers`
- `services`
- `orders`
- `order_items`
- `payments`
- `inventory_items`
- `inventory_movements`
- `settings`
- `notifications`
- `request_keys`

The included installer supports schema creation and version-aware updates without truncating existing application data.

## Security

LMS includes several application-level security controls:

- Session-based authentication
- Role-based authorization
- CSRF protection
- Parameterized PDO queries
- Dynamic output escaping
- Login rate limiting
- Temporary account lockout
- Idempotency protection for supported actions
- Protected configuration and backend paths
- Session timeout handling

## Installation

### XAMPP

1. Install XAMPP 8.2+.
2. Start **Apache** and **MySQL**.
3. Copy the project into:

```text
C:\xampp\htdocs\Laundry MS\
```

4. Open:

```text
http://localhost/Laundry%20MS/
```

5. The installer will create or update the database schema on first access.

### Default Login

```text
Username: admin
Password: admin123
```

Change the default administrator password before using the application in a real business environment.

### Database

For manual setup:

```sql
CREATE DATABASE laundry_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Then import `database.sql` or allow the built-in installer to configure the database.

## Configuration

Optional database environment variables:

```text
LMS_DB_HOST=localhost
LMS_DB_NAME=laundry_db
LMS_DB_USER=root
LMS_DB_PASS=
```

Application-level configuration is located in:

```text
backend/config/app.php
```

Business and invoice settings can also be managed from the application settings page.

## UI & Design

The interface uses a clean desktop-oriented design system with:

- Fixed sidebar navigation
- Role-based menus
- Top navigation bar
- Dashboard cards
- Data tables
- Status badges
- Toast notifications
- Confirmation dialogs
- Modal forms
- Loading states
- Print-optimized invoice layouts

### Brand Colors

```text
Primary:       #3C2182
Primary Dark:  #2B175F
Primary Light: #EDE8F7
```

Design tokens are centralized in:

```text
assets/css/variables.css
```

## Smart Workflow

The application automatically adapts its interface and business logic to the current context:

- Discount rows appear only when applicable.
- GST rows appear only when applicable.
- Payment controls react to order payment state.
- Status controls expose valid transitions.
- Customers and services can be archived when historical records exist.
- Duplicate service lines are merged during order creation.
- Validation errors preserve entered form values.
- Notifications display unread counts in the navigation.
- Loading states are shown during asynchronous actions.

## Author

**Rohit Vadher**

GitHub: [@rohitvadher](https://github.com/rohitvadher)

