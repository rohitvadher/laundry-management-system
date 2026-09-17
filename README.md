# Laundry Management System

A modern web-based laundry management system designed to simplify customer management, service handling, order tracking, and billing.


---

## Overview

The Laundry Management System provides a centralized interface for managing the day-to-day operations of a laundry business.

It allows administrators to manage customers and services, track order progress, and generate invoices from a single system.

## Features

* Dashboard with operational statistics
* Customer management and order history
* Laundry service and pricing management
* Order status tracking
* Automated billing and invoice generation
* Printable invoices
* Simple administrator authentication

## Technology Stack

| Layer    | Technology                       |
| -------- | -------------------------------- |
| Frontend | HTML5, CSS3, Bootstrap 5, jQuery |
| Backend  | PHP                              |
| Database | MySQL                            |
| Icons    | Font Awesome                     |

## Installation

### Database

1. Open phpMyAdmin.
2. Create a database named `laundry_db`.
3. Import `database.sql`.

### Configuration

Update the database credentials in:

```text
config/db.php
```

Example:

```php
<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "laundry_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);
```

### Run Locally

Place the project inside the XAMPP `htdocs` directory or the WAMP `www` directory.

Then open:

```text
http://localhost/laundry-management-system
```

## Default Credentials

| Role          | Username | Password   |
| ------------- | -------- | ---------- |
| Administrator | `admin`  | `admin123` |

> Change the default credentials before using the system in a production environment.

## Project Structure

```text
laundry-management-system/
├── config/
├── css/
├── js/
├── assets/
├── database.sql
├── index.php
└── ...
```

**LinkedIn:** [ʀᴏʜɪᴛ ᴠᴀᴅʜᴇʀ](https://www.linkedin.com/rohitvadher)

