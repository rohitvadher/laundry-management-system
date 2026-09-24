<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? lms_input()['action'] ?? 'list';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    if ($action === 'list') {
        lms_require_login();
        $in = array_merge($_GET, lms_input());
        [$page, $limit, $offset] = lms_page($in);
        $orderId = lms_int($in['order_id'] ?? 0, 0);
        $method = lms_str($in['method'] ?? '', 20);
        $from = lms_str($in['from'] ?? '', 10);
        $to = lms_str($in['to'] ?? '', 10);
        $where = [];
        $p = [];
        if ($orderId > 0) {
            $where[] = 'p.`order_id` = :o';
            $p[':o'] = $orderId;
        }
        if (in_array($method, LMS_PAYMENT_METHODS, true)) {
            $where[] = 'p.`method` = :m';
            $p[':m'] = $method;
        }
        if (lms_valid_date($from ?: null)) {
            $where[] = 'p.`paid_at` >= :from';
            $p[':from'] = $from;
        }
        if (lms_valid_date($to ?: null)) {
            $where[] = 'p.`paid_at` <= :to';
            $p[':to'] = $to;
        }
        $where = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $c = $pdo->prepare("SELECT COUNT(*) FROM `payments` p $where");
        $c->execute($p);
        $total = (int)$c->fetchColumn();
        $st = $pdo->prepare("SELECT p.*, o.`invoice_no`, c.`customer_name` FROM `payments` p JOIN `orders` o ON o.`id` = p.`order_id` JOIN `customers` c ON c.`id` = o.`customer_id` $where ORDER BY p.`id` DESC LIMIT :l OFFSET :o");
        foreach ($p as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->bindValue(':o', $offset, PDO::PARAM_INT);
        $st->execute();
        lms_ok('', ['rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }
    if ($action === 'add') {
        $u = lms_require_perm('payment_write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $key = lms_str($in['idempotency_key'] ?? '', 64);
        $orderId = lms_int($in['order_id'] ?? 0, 0);
        $amount = round((float)($in['amount'] ?? 0), 2);
        $method = lms_str($in['method'] ?? 'Cash', 20);
        $ref = lms_str($in['reference_no'] ?? '', 100);
        $date = lms_str($in['paid_at'] ?? date('Y-m-d'), 10);
        $errors = [];
        if ($orderId <= 0) {
            $errors['order_id'] = 'Order is required.';
        }
        if ($amount <= 0 || $amount > 10000000) {
            $errors['amount'] = 'Amount must be positive.';
        }
        if (!in_array($method, LMS_PAYMENT_METHODS, true)) {
            $errors['method'] = 'Invalid payment method.';
        }
        if (!lms_valid_date($date) || $date > date('Y-m-d')) {
            $errors['paid_at'] = 'Payment date cannot be in the future.';
        }
        if ($errors) {
            lms_fail('Invalid payment data.', 422, $errors);
        }
        $result = lms_idem_process($pdo, $key, function (PDO $pdo) use ($orderId, $amount, $method, $ref, $date, $u) {
            $st = $pdo->prepare("SELECT `id`,`grand_total`,`paid_amount`,`status`,`invoice_no` FROM `orders` WHERE `id` = :id FOR UPDATE");
            $st->execute([':id' => $orderId]);
            $o = $st->fetch();
            if (!$o) {
                throw new LMS_Api_Exception('Order not found.', 404);
            }
            if ($o['status'] === 'Cancelled') {
                throw new LMS_Api_Exception('Cannot accept payment for a cancelled order.', 409);
            }
            $balance = round((float)$o['grand_total'] - (float)$o['paid_amount'], 2);
            if ($amount > $balance) {
                throw new LMS_Api_Exception('Payment exceeds outstanding balance of ' . number_format($balance, 2) . '.', 422, ['amount' => 'Exceeds balance.']);
            }
            $pdo->prepare('INSERT INTO `payments` (`order_id`,`amount`,`method`,`reference_no`,`paid_at`,`received_by`) VALUES (:o,:a,:m,:r,:d,:u)')->execute([':o' => $orderId, ':a' => $amount, ':m' => $method, ':r' => ($ref !== '' ? $ref : null), ':d' => $date, ':u' => $u['id']]);
            $newPaid = round((float)$o['paid_amount'] + $amount, 2);
            $ps = lms_pay_status((float)$o['grand_total'], $newPaid);
            $pdo->prepare('UPDATE `orders` SET `paid_amount` = :p, `payment_status` = :s WHERE `id` = :id')->execute([':p' => $newPaid, ':s' => $ps, ':id' => $orderId]);
            return ['paid_amount' => $newPaid, 'payment_status' => $ps, 'balance' => round((float)$o['grand_total'] - $newPaid, 2), 'order_id' => $orderId];
        });
        if (!empty($result['duplicate'])) {
            lms_ok('Payment already recorded.', $result, 200);
        }
        lms_notify($pdo, 'payment', 'Payment received for order #' . $orderId, number_format($amount, 2) . ' via ' . $method, 'pages/order_view.php?id=' . $orderId);
        lms_ok('Payment recorded.', $result, 201);
    }
    lms_fail('Unknown action.', 404);
});

