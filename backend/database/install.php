<?php
declare(strict_types=1);
function lms_col_exists(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c');
    $st->execute([':t' => $table, ':c' => $col]);
    return (bool)$st->fetchColumn();
}
function lms_add_col(PDO $pdo, string $table, string $col, string $ddl): void {
    if (!lms_col_exists($pdo, $table, $col)) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN $ddl");
    }
}
function lms_index_exists(PDO $pdo, string $table, string $index): bool {
    $st = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND INDEX_NAME = :i');
    $st->execute([':t' => $table, ':i' => $index]);
    return (bool)$st->fetchColumn();
}
function lms_add_index(PDO $pdo, string $table, string $index, string $cols, bool $unique = false): void {
    if (!lms_index_exists($pdo, $table, $index)) {
        $kind = $unique ? 'UNIQUE INDEX' : 'INDEX';
        $pdo->exec("CREATE $kind `$index` ON `$table` ($cols)");
    }
}
function lms_schema_version(PDO $pdo): int {
    try {
        $st = $pdo->prepare('SELECT `svalue` FROM `settings` WHERE `skey` = :k');
        $st->execute([':k' => 'schema_version']);
        $v = (int)$st->fetchColumn();
        return max(0, $v);
    } catch (Throwable $e) {
        return 0;
    }
}
function lms_set_schema_version(PDO $pdo, int $v): void {
    $pdo->prepare("INSERT INTO `settings` (`skey`,`svalue`) VALUES ('schema_version',:v) ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`)")->execute([':v' => (string)$v]);
}
function lms_install(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(100) NOT NULL,
        `role` ENUM('admin','manager','staff','accountant') NOT NULL DEFAULT 'staff',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_users_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `customer_name` VARCHAR(100) NOT NULL,
        `mobile` VARCHAR(20) NOT NULL,
        `address` TEXT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `services` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `service_name` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `customer_id` INT NOT NULL,
        `order_date` DATE NOT NULL,
        `pickup_date` DATE NULL,
        `delivery_date` DATE NULL,
        `actual_delivery_date` DATE NULL,
        `assigned_staff` INT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `discount_type` ENUM('none','fixed','percent') NOT NULL DEFAULT 'none',
        `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `taxable_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `payment_status` ENUM('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid',
        `invoice_no` VARCHAR(40) NULL,
        `notes` VARCHAR(500) NULL,
        `status` ENUM('Pending','Processing','Ready','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
        `created_by` INT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_orders_customer` (`customer_id`),
        CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
        CONSTRAINT `fk_orders_staff` FOREIGN KEY (`assigned_staff`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `order_id` INT NOT NULL,
        `service_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `line_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (`id`),
        KEY `idx_items_order` (`order_id`),
        KEY `idx_items_service` (`service_id`),
        CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_items_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `order_id` INT NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `method` ENUM('Cash','UPI','Card','Bank transfer','Other') NOT NULL DEFAULT 'Cash',
        `reference_no` VARCHAR(100) NULL,
        `paid_at` DATE NOT NULL,
        `received_by` INT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_payments_order` (`order_id`),
        CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `skey` VARCHAR(80) NOT NULL,
        `svalue` TEXT NULL,
        PRIMARY KEY (`skey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `inventory_items` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `item_name` VARCHAR(120) NOT NULL,
        `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
        `current_stock` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `low_threshold` DECIMAL(10,2) NOT NULL DEFAULT 5.00,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `inventory_movements` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `item_id` INT NOT NULL,
        `move_type` ENUM('purchase','usage','adjust') NOT NULL,
        `quantity` DECIMAL(10,2) NOT NULL,
        `note` VARCHAR(255) NULL,
        `moved_by` INT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_mov_item` (`item_id`),
        CONSTRAINT `fk_mov_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `ntype` VARCHAR(60) NOT NULL,
        `title` VARCHAR(180) NOT NULL,
        `body` VARCHAR(500) NULL,
        `link` VARCHAR(255) NULL,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_notif_read` (`is_read`),
        KEY `idx_notif_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `ip` VARCHAR(45) NOT NULL,
        `username` VARCHAR(50) NOT NULL,
        `fails` INT NOT NULL DEFAULT 0,
        `locked_until` DATETIME NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_login_ip_user` (`ip`,`username`),
        KEY `idx_login_updated` (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `request_keys` (
        `idem_key` VARCHAR(64) NOT NULL,
        `payload` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`idem_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $roleType = (string)$pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'")->fetchColumn();
    if (strpos($roleType, 'manager') === false) {
        $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','manager','staff','accountant') NOT NULL DEFAULT 'staff'");
    }
    lms_add_col($pdo, 'users', 'is_active', '`is_active` TINYINT(1) NOT NULL DEFAULT 1');
    lms_add_col($pdo, 'users', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    lms_add_col($pdo, 'customers', 'is_active', '`is_active` TINYINT(1) NOT NULL DEFAULT 1');
    lms_add_col($pdo, 'customers', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    lms_add_col($pdo, 'services', 'gst_rate', '`gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00');
    lms_add_col($pdo, 'services', 'is_active', '`is_active` TINYINT(1) NOT NULL DEFAULT 1');
    lms_add_col($pdo, 'services', 'updated_at', '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    foreach (['subtotal','discount_type','discount_value','discount_amount','taxable_amount','gst_rate','gst_amount','cgst_amount','sgst_amount','igst_amount','grand_total','paid_amount','payment_status','invoice_no','pickup_date','actual_delivery_date','assigned_staff','notes','created_by','updated_at'] as $c) {
        $map = [
            'subtotal' => '`subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'discount_type' => "`discount_type` ENUM('none','fixed','percent') NOT NULL DEFAULT 'none'",
            'discount_value' => '`discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'discount_amount' => '`discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'taxable_amount' => '`taxable_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'gst_rate' => '`gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00',
            'gst_amount' => '`gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'cgst_amount' => '`cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'sgst_amount' => '`sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'igst_amount' => '`igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'grand_total' => '`grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'paid_amount' => '`paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00',
            'payment_status' => "`payment_status` ENUM('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid'",
            'invoice_no' => '`invoice_no` VARCHAR(40) NULL',
            'pickup_date' => '`pickup_date` DATE NULL',
            'actual_delivery_date' => '`actual_delivery_date` DATE NULL',
            'assigned_staff' => '`assigned_staff` INT NULL',
            'notes' => '`notes` VARCHAR(500) NULL',
            'created_by' => '`created_by` INT NULL',
            'updated_at' => '`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
        ];
        lms_add_col($pdo, 'orders', $c, $map[$c]);
    }
    lms_add_col($pdo, 'order_items', 'line_total', '`line_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00');
    lms_add_index($pdo, 'orders', 'idx_orders_status', '`status`');
    lms_add_index($pdo, 'orders', 'idx_orders_created', '`created_at`');
    lms_add_index($pdo, 'orders', 'idx_orders_delivery', '`delivery_date`');
    lms_add_index($pdo, 'orders', 'idx_orders_invoice', '`invoice_no`');
    lms_add_index($pdo, 'orders', 'idx_orders_paystatus', '`payment_status`');
    lms_add_index($pdo, 'customers', 'idx_customers_mobile', '`mobile`');
    lms_add_index($pdo, 'customers', 'idx_customers_name', '`customer_name`');
    lms_add_index($pdo, 'services', 'idx_services_name', '`service_name`');
    lms_add_index($pdo, 'order_items', 'idx_items_order_service', '`order_id`,`service_id`');
    try {
        $fk = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND CONSTRAINT_NAME = 'orders_ibfk_1'")->fetch();
        if ($fk) {
            $pdo->exec('ALTER TABLE `orders` DROP FOREIGN KEY `orders_ibfk_1`');
        }
        $hasRestrict = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND CONSTRAINT_NAME = 'fk_orders_customer'")->fetch();
        if (!$hasRestrict) {
            $pdo->exec('ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT');
        }
    } catch (Throwable $e) {
    }

    $defaults = [
        'business_name' => 'Laundry Management System',
        'business_address' => '123 Laundry Street, City',
        'business_phone' => '+91 1234567890',
        'business_email' => '',
        'gstin' => '',
        'gst_enabled' => '1',
        'gst_rate' => '5',
        'gst_inclusive' => '0',
        'invoice_prefix' => 'INV',
        'currency' => 'INR',
        'invoice_footer' => 'Thank you for your business!',
        'invoice_terms' => 'Goods once delivered will not be taken back. Please check items at delivery.'
    ];
    $ins = $pdo->prepare('INSERT IGNORE INTO `settings` (`skey`,`svalue`) VALUES (:k,:v)');
    foreach ($defaults as $k => $v) {
        $ins->execute([':k' => $k, ':v' => $v]);
    }

    $admin = $pdo->prepare('SELECT `id`,`password` FROM `users` WHERE `username` = :u');
    $admin->execute([':u' => 'admin']);
    $row = $admin->fetch();
    if (!$row) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $st = $pdo->prepare("INSERT INTO `users` (`username`,`password`,`full_name`,`role`) VALUES ('admin',:p,'System Administrator','admin')");
        $st->execute([':p' => $hash]);
    } elseif (password_get_info((string)$row['password'])['algo'] === null) {
        $hash = password_hash((string)$row['password'], PASSWORD_DEFAULT);
        $up = $pdo->prepare('UPDATE `users` SET `password` = :p WHERE `id` = :id');
        $up->execute([':p' => $hash, ':id' => $row['id']]);
    }
    if ($pdo->query('SELECT COUNT(*) FROM `services`')->fetchColumn() == 0) {
        $seed = [
            ['Wash & Fold (Kg)', 'Regular washing and folding per kg', 50.00],
            ['Dry Clean - Shirt', 'Dry cleaning for shirts', 150.00],
            ['Dry Clean - Suit', 'Dry cleaning for 2-piece suit', 450.00],
            ['Ironing - Shirt', 'Steam ironing', 30.00],
            ['Ironing - Trousers', 'Steam ironing', 40.00]
        ];
        $st = $pdo->prepare('INSERT INTO `services` (`service_name`,`description`,`price`) VALUES (:n,:d,:p)');
        foreach ($seed as $s) {
            $st->execute([':n' => $s[0], ':d' => $s[1], ':p' => $s[2]]);
        }
    }
    if ($pdo->query('SELECT COUNT(*) FROM `inventory_items`')->fetchColumn() == 0) {
        $inv = [
            ['Detergent', 'kg', 20, 5],
            ['Bleach', 'ltr', 10, 3],
            ['Hangers', 'pcs', 200, 50],
            ['Packaging bags', 'pcs', 300, 100]
        ];
        $st = $pdo->prepare('INSERT INTO `inventory_items` (`item_name`,`unit`,`current_stock`,`low_threshold`) VALUES (:n,:u,:s,:t)');
        foreach ($inv as $r) {
            $st->execute([':n' => $r[0], ':u' => $r[1], ':s' => $r[2], ':t' => $r[3]]);
        }
    }

    $version = lms_schema_version($pdo);
    $migrations = [
        1 => static function (PDO $pdo): void {
            $backs = $pdo->query("SELECT `id`,`total_amount`,`grand_total` FROM `orders` WHERE `grand_total` = 0 AND `total_amount` > 0 LIMIT 200")->fetchAll();
            if ($backs) {
                $u = $pdo->prepare('UPDATE `orders` SET `grand_total` = `total_amount`, `subtotal` = `total_amount`, `taxable_amount` = `total_amount` WHERE `id` = :id');
                foreach ($backs as $b) {
                    $u->execute([':id' => $b['id']]);
                }
            }
            $pdo->exec("UPDATE `orders` SET `payment_status` = CASE WHEN `paid_amount` >= `grand_total` AND `grand_total` > 0 THEN 'Paid' WHEN `paid_amount` > 0 THEN 'Partial' ELSE 'Unpaid' END WHERE `grand_total` > 0 AND `payment_status` = 'Unpaid'");
        }
    ];
    foreach ($migrations as $mv => $fn) {
        if ($mv > $version) {
            try {
                $fn($pdo);
                lms_set_schema_version($pdo, $mv);
            } catch (Throwable $e) {
                error_log('LMS migration ' . $mv . ' failed: ' . $e->getMessage());
                throw $e;
            }
        }
    }
}
