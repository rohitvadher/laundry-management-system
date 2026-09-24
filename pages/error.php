<?php
declare(strict_types=1);
$code = (int)($_GET['code'] ?? 404);
$map = [
    400 => ['Bad request', 'The request could not be understood. Check the link or the data you submitted and try again.'],
    401 => ['Session expired', 'Please sign in again to continue. Any unsaved form input may have been lost.'],
    403 => ['Access denied', 'Your account is not allowed to perform this action. If you need it, ask an administrator.'],
    404 => ['Page not found', 'The page or record you asked for does not exist or was removed.'],
    419 => ['Request expired', 'Your form token expired. Refresh the page and submit again.'],
    422 => ['Could not save', 'Some fields need attention. Go back, fix the highlighted fields, and submit again.'],
    429 => ['Too many attempts', 'For security, please wait a few minutes before trying again.'],
    500 => ['Something went wrong', 'An unexpected problem occurred. It has been logged. Please retry shortly.'],
    503 => ['Service unavailable', 'The database is currently unreachable. Please wait a moment and reload.']
];
if (!isset($map[$code])) {
    $code = 404;
}
http_response_code($code);
$title = $map[$code][0];
$msg = $map[$code][1];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $code; ?> â€” <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="icon" href="../icons/web/favicon.ico" sizes="any">
<link rel="stylesheet" href="../assets/css/variables.css">
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
</head>
<body>
<div class="error-wrap">
<div class="error-card">
<img src="../icons/web/icon-192.png" alt="Laundry MS">
<div class="error-code"><?php echo $code; ?></div>
<h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
<p style="color:var(--muted)"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<div class="error-actions">
<button class="btn" onclick="history.back()">Back</button>
<a class="btn primary" href="dashboard.php">Dashboard</a>
<?php if ($code === 401 || $code === 419): ?>
<a class="btn" href="../index.php">Sign in again</a>
<?php else: ?>
<a class="btn" href="orders.php">Orders</a>
<?php endif; ?>
</div>
</div>
</div>
</body>
</html>

