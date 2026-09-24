<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$__u = lms_user();
if (!$__u) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: ../index.php' . ($next ? ('?next=' . $next) : ''));
    exit;
}
$__csrf = lms_csrf();
$__page = basename($_SERVER['PHP_SELF'] ?? '');
$__title = isset($pageTitle) ? (string)$pageTitle : 'Laundry Management System';
$__role = $__u['role'];
function lms_nav_active(string $f, string $cur): string { return $f === $cur ? 'active' : ''; }
$__initials = strtoupper(mb_substr((string)$__u['name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo lms_esc($__title); ?></title>
<meta name="csrf-token" content="<?php echo lms_esc($__csrf); ?>">
<link rel="icon" href="../icons/web/favicon.ico" sizes="any">
<link rel="stylesheet" href="../assets/css/variables.css">
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/forms.css">
<link rel="stylesheet" href="../assets/css/tables.css">
<link rel="stylesheet" href="../assets/css/pages.css">
<link rel="stylesheet" href="../assets/css/print.css">
</head>
<body>
<div class="app">
<aside class="sidebar">
<div class="brand"><img src="../icons/web/icon-192.png" alt="LMS"><strong>Laundry MS</strong></div>
<nav class="nav" aria-label="Main">
<div class="sep">Work</div>
<a href="dashboard.php" class="<?php echo lms_nav_active('dashboard.php', $__page); ?>">Dashboard</a>
<a href="orders.php" class="<?php echo lms_nav_active('orders.php', $__page); ?> <?php echo in_array($__page, ['order_create.php', 'order_view.php'], true) ? 'active' : ''; ?>">Orders</a>
<a href="delivery.php" class="<?php echo lms_nav_active('delivery.php', $__page); ?>">Delivery</a>
<a href="payments.php" class="<?php echo lms_nav_active('payments.php', $__page); ?>">Payments</a>
<div class="sep">Directory</div>
<a href="customers.php" class="<?php echo in_array($__page, ['customers.php', 'customer_view.php'], true) ? 'active' : ''; ?>">Customers</a>
<a href="services.php" class="<?php echo lms_nav_active('services.php', $__page); ?>">Services</a>
<a href="inventory.php" class="<?php echo lms_nav_active('inventory.php', $__page); ?>">Inventory</a>
<?php if (in_array($__role, ['admin', 'manager', 'accountant'], true)): ?>
<div class="sep">Insights</div>
<a href="reports.php" class="<?php echo lms_nav_active('reports.php', $__page); ?>">Reports</a>
<?php endif; ?>
<a href="notifications.php" class="<?php echo lms_nav_active('notifications.php', $__page); ?>">Notifications</a>
<div class="sep">System</div>
<?php if (in_array($__role, ['admin', 'manager'], true)): ?>
<a href="settings.php" class="<?php echo lms_nav_active('settings.php', $__page); ?>">Settings</a>
<?php endif; ?>
<?php if ($__role === 'admin'): ?>
<a href="users.php" class="<?php echo lms_nav_active('users.php', $__page); ?>">Staff</a>
<?php endif; ?>
<a href="help.php" class="<?php echo lms_nav_active('help.php', $__page); ?>">Help</a>
</nav>
<div class="userbox"><span class="avatar"><?php echo lms_esc($__initials); ?></span><span><strong><?php echo lms_esc($__u['name']); ?></strong><small><?php echo lms_esc(ucfirst($__role)); ?></small></span></div>
</aside>
<div class="main">
<div class="topbar">
<span>Welcome back, <?php echo lms_esc($__u['name']); ?></span>
<span class="spacer"></span>
<a class="btn small ghost bell" href="notifications.php" id="bellLink" aria-label="Notifications">Alerts<span class="dot" id="bellDot" style="display:none">0</span></a>
<button class="btn small" id="logoutBtn">Sign out</button>
</div>
<div class="content">

