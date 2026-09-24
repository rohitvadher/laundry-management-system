<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? 'summary';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    if ($action === 'dashboard') {
        lms_require_login();
        $today = date('Y-m-d');
        $from = date('Y-m-01');
        $kpi = $pdo->prepare("SELECT COUNT(*) AS orders, COALESCE(SUM(`grand_total`),0) AS revenue FROM `orders` WHERE `order_date` >= :from AND `order_date` <= :to AND `status` <> 'Cancelled'");
        $kpi->execute([':from' => $from, ':to' => $today]);
        $kpiRow = $kpi->fetch();
        $kpiRow['outstanding'] = (float)$pdo->query("SELECT COALESCE(SUM(`grand_total` - `paid_amount`),0) FROM `orders` WHERE `status` <> 'Cancelled'")->fetchColumn();
        $cnt = $pdo->prepare("SELECT SUM(`delivery_date` = :t1) AS today, SUM(`delivery_date` < :t2) AS overdue, SUM(`delivery_date` >= :t3) AS upcoming FROM `orders` WHERE `status` <> 'Cancelled' AND `status` <> 'Delivered'");
        $cnt->execute([':t1' => $today, ':t2' => $today, ':t3' => $today]);
        $low = (int)$pdo->query('SELECT COUNT(*) FROM `inventory_items` WHERE `is_active` = 1 AND `current_stock` <= `low_threshold`')->fetchColumn();
        $recent = $pdo->query("SELECT o.`id`,o.`invoice_no`,o.`grand_total`,o.`status`,c.`customer_name` FROM `orders` o JOIN `customers` c ON c.`id` = o.`customer_id` ORDER BY o.`id` DESC LIMIT 5")->fetchAll();
        $daily = $pdo->prepare("SELECT `order_date` AS d, COALESCE(SUM(`grand_total`),0) AS amt FROM `orders` WHERE `order_date` >= :from AND `order_date` <= :to AND `status` <> 'Cancelled' GROUP BY `order_date` ORDER BY `order_date` ASC");
        $daily->execute([':from' => date('Y-m-d', strtotime('-13 days')), ':to' => $today]);
        lms_ok('', ['kpi' => $kpiRow, 'delivery' => $cnt->fetch(), 'low_stock' => $low, 'recent' => $recent, 'daily' => $daily->fetchAll(), 'currency' => (lms_settings($pdo)['currency'] ?? 'INR')]);
    }
    lms_require_perm('reports_finance');
    $in = $_GET;
    $from = lms_str($in['from'] ?? date('Y-m-01'), 10);
    $to = lms_str($in['to'] ?? date('Y-m-d'), 10);
    $preset = lms_str($in['preset'] ?? '', 10);
    $today = date('Y-m-d');
    if ($preset === 'today') {
        $from = $today;
        $to = $today;
    } elseif ($preset === 'week') {
        $from = date('Y-m-d', strtotime('monday this week'));
        $to = $today;
    } elseif ($preset === 'month') {
        $from = date('Y-m-01');
        $to = $today;
    } elseif ($preset === 'year') {
        $from = date('Y-01-01');
        $to = $today;
    }
    if (!lms_valid_date($from) || !lms_valid_date($to) || $from > $to) {
        lms_fail('Invalid date range.', 422);
    }
    $base = "FROM `orders` o WHERE o.`order_date` >= :from AND o.`order_date` <= :to AND o.`status` <> 'Cancelled'";
    $p = [':from' => $from, ':to' => $to];
    $kpi = $pdo->prepare("SELECT COUNT(*) AS orders, COALESCE(SUM(o.`grand_total`),0) AS revenue, COALESCE(SUM(o.`discount_amount`),0) AS discounts, COALESCE(SUM(o.`gst_amount`),0) AS gst, COALESCE(SUM(o.`paid_amount`),0) AS collected, COALESCE(SUM(o.`grand_total` - o.`paid_amount`),0) AS outstanding $base");
    $kpi->execute($p);
    $byStatus = $pdo->prepare("SELECT o.`status`, COUNT(*) AS n, COALESCE(SUM(o.`grand_total`),0) AS amt FROM `orders` o WHERE o.`order_date` >= :from AND o.`order_date` <= :to GROUP BY o.`status`");
    $byStatus->execute($p);
    $daily = $pdo->prepare("SELECT o.`order_date` AS d, COUNT(*) AS n, COALESCE(SUM(o.`grand_total`),0) AS amt $base GROUP BY o.`order_date` ORDER BY o.`order_date` ASC");
    $daily->execute($p);
    $svc = $pdo->prepare("SELECT s.`service_name`, SUM(oi.`quantity`) AS qty, COALESCE(SUM(oi.`line_total`),0) AS amt FROM `order_items` oi JOIN `services` s ON s.`id` = oi.`service_id` JOIN `orders` o ON o.`id` = oi.`order_id` WHERE o.`order_date` >= :from AND o.`order_date` <= :to AND o.`status` <> 'Cancelled' GROUP BY s.`id`, s.`service_name` ORDER BY amt DESC LIMIT 10");
    $svc->execute($p);
    $pay = $pdo->prepare("SELECT p.`method`, COUNT(*) AS n, COALESCE(SUM(p.`amount`),0) AS amt FROM `payments` p WHERE p.`paid_at` >= :from AND p.`paid_at` <= :to GROUP BY p.`method`");
    $pay->execute($p);
    $cancel = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(`grand_total`),0) AS amt FROM `orders` WHERE `order_date` >= :from AND `order_date` <= :to AND `status` = 'Cancelled'");
    $cancel->execute($p);
    lms_ok('', [
        'range' => ['from' => $from, 'to' => $to],
        'kpi' => $kpi->fetch(),
        'by_status' => $byStatus->fetchAll(),
        'daily' => $daily->fetchAll(),
        'services' => $svc->fetchAll(),
        'payments' => $pay->fetchAll(),
        'cancelled' => $cancel->fetch()
    ]);
});

