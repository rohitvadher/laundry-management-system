<?php
declare(strict_types=1);
define('LMS_DB_HOST', getenv('LMS_DB_HOST') ?: 'localhost');
define('LMS_DB_NAME', getenv('LMS_DB_NAME') ?: 'laundry_db');
define('LMS_DB_USER', getenv('LMS_DB_USER') ?: 'root');
define('LMS_DB_PASS', getenv('LMS_DB_PASS') ?: '');
define('LMS_SESSION_TIMEOUT', 1800);
define('LMS_LOGIN_MAX_ATTEMPTS', 5);
define('LMS_LOGIN_LOCK_SECONDS', 300);
define('LMS_ROLES', ['admin', 'manager', 'staff', 'accountant']);
define('LMS_ORDER_STATUSES', ['Pending', 'Processing', 'Ready', 'Delivered', 'Cancelled']);
define('LMS_STATUS_NEXT', [
    'Pending' => ['Processing', 'Cancelled'],
    'Processing' => ['Ready', 'Cancelled'],
    'Ready' => ['Delivered', 'Cancelled'],
    'Delivered' => [],
    'Cancelled' => []
]);
define('LMS_PAYMENT_METHODS', ['Cash', 'UPI', 'Card', 'Bank transfer', 'Other']);
define('LMS_PAYMENT_STATUSES', ['Unpaid', 'Partial', 'Paid']);

