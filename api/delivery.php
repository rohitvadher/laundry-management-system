<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? 'list';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    lms_require_login();
    $in = array_merge($_GET, lms_input());
    $filter = lms_str($in['filter'] ?? 'upcoming', 12);
    $today = date('Y-m-d');
    [$page, $limit, $offset] = lms_page($in);
    $where = "o.`status` <> 'Cancelled' AND o.`status` <> 'Delivered'";
    if ($filter === 'today') {
        $where .= " AND o.`delivery_date` = :t";
        $p = [':t' => $today];
    } elseif ($filter === 'overdue') {
        $where .= " AND o.`delivery_date` < :t";
        $p = [':t' => $today];
    } elseif ($filter === 'upcoming') {
        $where .= " AND o.`delivery_date` >= :t";
        $p = [':t' => $today];
    } elseif ($filter === 'delivered') {
        $where = "o.`status` = 'Delivered'";
        $p = [];
    } else {
        $p = [];
    }
    $c = $pdo->prepare("SELECT COUNT(*) FROM `orders` o WHERE $where");
    $c->execute($p);
    $total = (int)$c->fetchColumn();
    $st = $pdo->prepare("SELECT o.`id`,o.`invoice_no`,o.`delivery_date`,o.`status`,o.`grand_total`,o.`payment_status`,c.`customer_name`,c.`mobile`,u.`full_name` AS staff_name FROM `orders` o JOIN `customers` c ON c.`id` = o.`customer_id` LEFT JOIN `users` u ON u.`id` = o.`assigned_staff` WHERE $where ORDER BY o.`delivery_date` ASC LIMIT :l OFFSET :o");
    foreach ($p as $k => $v) {
        $st->bindValue($k, $v);
    }
    $st->bindValue(':l', $limit, PDO::PARAM_INT);
    $st->bindValue(':o', $offset, PDO::PARAM_INT);
    $st->execute();
    $counts = [];
    foreach (['today' => "o.`delivery_date` = '$today'", 'overdue' => "o.`delivery_date` < '$today'", 'upcoming' => "o.`delivery_date` >= '$today'"] as $k => $cond) {
        $q = $pdo->query("SELECT COUNT(*) FROM `orders` o WHERE o.`status` <> 'Cancelled' AND o.`status` <> 'Delivered' AND $cond");
        $counts[$k] = (int)$q->fetchColumn();
    }
    lms_ok('', ['rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'limit' => $limit, 'counts' => $counts, 'today' => $today]);
});

