<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? lms_input()['action'] ?? 'get';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    if ($action === 'get') {
        lms_require_login();
        lms_ok('', ['settings' => lms_settings($pdo)]);
    }
    if ($action === 'save') {
        lms_require_perm('settings');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $allowed = ['business_name', 'business_address', 'business_phone', 'business_email', 'gstin', 'gst_enabled', 'gst_rate', 'gst_inclusive', 'invoice_prefix', 'currency', 'invoice_footer', 'invoice_terms'];
        $errors = [];
        $vals = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $in)) {
                $vals[$k] = lms_str($in[$k], 500);
            }
        }
        if (isset($vals['business_name']) && $vals['business_name'] === '') {
            $errors['business_name'] = 'Business name is required.';
        }
        if (isset($vals['gst_rate']) && ((float)$vals['gst_rate'] < 0 || (float)$vals['gst_rate'] > 100)) {
            $errors['gst_rate'] = 'GST rate must be 0-100.';
        }
        if (isset($vals['gst_enabled']) && !in_array($vals['gst_enabled'], ['0', '1'], true)) {
            $errors['gst_enabled'] = 'Invalid value.';
        }
        if (isset($vals['gst_inclusive']) && !in_array($vals['gst_inclusive'], ['0', '1'], true)) {
            $errors['gst_inclusive'] = 'Invalid value.';
        }
        if (isset($vals['invoice_prefix']) && !preg_match('/^[A-Za-z0-9\-]{1,10}$/', $vals['invoice_prefix'])) {
            $errors['invoice_prefix'] = 'Use 1-10 letters, numbers or hyphen.';
        }
        if (isset($vals['business_email']) && $vals['business_email'] !== '' && !filter_var($vals['business_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['business_email'] = 'Enter a valid email address.';
        }
        if ($errors) {
            lms_fail('Invalid settings.', 422, $errors);
        }
        $st = $pdo->prepare('INSERT INTO `settings` (`skey`,`svalue`) VALUES (:k,:v) ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`)');
        foreach ($vals as $k => $v) {
            $st->execute([':k' => $k, ':v' => $v]);
        }
        lms_ok('Settings saved.');
    }
    lms_fail('Unknown action.', 404);
});

