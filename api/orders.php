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
        $q = lms_str($in['q'] ?? '', 80);
        $status = lms_str($in['status'] ?? '', 20);
        $pay = lms_str($in['payment_status'] ?? '', 20);
        $from = lms_str($in['from'] ?? '', 10);
        $to = lms_str($in['to'] ?? '', 10);
        $where = [];
        $p = [];
        if ($q !== '') {
            $where[] = '(c.`customer_name` LIKE :q1 OR c.`mobile` LIKE :q2 OR o.`invoice_no` LIKE :q3 OR o.`id` = :qid)';
            $p[':q1'] = '%' . $q . '%';
            $p[':q2'] = '%' . $q . '%';
            $p[':q3'] = '%' . $q . '%';
            $p[':qid'] = ctype_digit($q) ? (int)$q : 0;
        }
        if (in_array($status, LMS_ORDER_STATUSES, true)) {
            $where[] = 'o.`status` = :s';
            $p[':s'] = $status;
        }
        if (in_array($pay, LMS_PAYMENT_STATUSES, true)) {
            $where[] = 'o.`payment_status` = :ps';
            $p[':ps'] = $pay;
        }
        if (lms_valid_date($from ?: null)) {
            $where[] = 'o.`order_date` >= :from';
            $p[':from'] = $from;
        }
        if (lms_valid_date($to ?: null)) {
            $where[] = 'o.`order_date` <= :to';
            $p[':to'] = $to;
        }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sorts = ['newest' => 'o.`id` DESC', 'oldest' => 'o.`id` ASC', 'highest' => 'o.`grand_total` DESC', 'lowest' => 'o.`grand_total` ASC', 'delivery' => 'o.`delivery_date` ASC'];
        $sortKey = lms_str($in['sort'] ?? 'newest', 10);
        $sort = isset($sorts[$sortKey]) ? $sorts[$sortKey] : $sorts['newest'];
        $c = $pdo->prepare("SELECT COUNT(*) FROM `orders` o JOIN `customers` c ON c.`id` = o.`customer_id` $w");
        $c->execute($p);
        $total = (int)$c->fetchColumn();
        $st = $pdo->prepare("SELECT o.`id`,o.`invoice_no`,o.`order_date`,o.`delivery_date`,o.`grand_total`,o.`paid_amount`,o.`payment_status`,o.`status`,o.`created_at`,c.`customer_name`,c.`mobile` FROM `orders` o JOIN `customers` c ON c.`id` = o.`customer_id` $w ORDER BY $sort LIMIT :l OFFSET :o");
        foreach ($p as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->bindValue(':o', $offset, PDO::PARAM_INT);
        $st->execute();
        lms_ok('', ['rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }
    if ($action === 'get') {
        lms_require_login();
        $id = lms_int($_GET['id'] ?? 0, 0);
        if ($id <= 0) {
            lms_fail('Order not found.', 404);
        }
        $st = $pdo->prepare('SELECT o.*, c.`customer_name`, c.`mobile`, c.`address`, u.`full_name` AS staff_name FROM `orders` o JOIN `customers` c ON c.`id` = o.`customer_id` LEFT JOIN `users` u ON u.`id` = o.`assigned_staff` WHERE o.`id` = :id');
        $st->execute([':id' => $id]);
        $order = $st->fetch();
        if (!$order) {
            lms_fail('Order not found.', 404);
        }
        $it = $pdo->prepare('SELECT oi.`service_id`,oi.`quantity`,oi.`price`,oi.`line_total`,s.`service_name` FROM `order_items` oi JOIN `services` s ON s.`id` = oi.`service_id` WHERE oi.`order_id` = :id ORDER BY oi.`id` ASC');
        $it->execute([':id' => $id]);
        $order['items'] = $it->fetchAll();
        $py = $pdo->prepare('SELECT * FROM `payments` WHERE `order_id` = :id ORDER BY `id` ASC');
        $py->execute([':id' => $id]);
        $order['payments'] = $py->fetchAll();
        $order['balance'] = round((float)$order['grand_total'] - (float)$order['paid_amount'], 2);
        lms_ok('', ['order' => $order]);
    }
    if ($action === 'create') {
        $u = lms_require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $key = lms_str($in['idempotency_key'] ?? '', 64);
        $customerId = lms_int($in['customer_id'] ?? 0, 0);
        $delivery = lms_str($in['delivery_date'] ?? '', 10);
        $pickup = lms_str($in['pickup_date'] ?? '', 10);
        $dtype = lms_str($in['discount_type'] ?? 'none', 10);
        $dval = (float)($in['discount_value'] ?? 0);
        $staff = lms_int($in['assigned_staff'] ?? 0, 0);
        $notes = lms_str($in['notes'] ?? '', 500);
        $rawItems = $in['items'] ?? [];
        $errors = [];
        if ($customerId <= 0) {
            $errors['customer_id'] = 'Select a valid customer.';
        }
        if (!lms_valid_date($delivery)) {
            $errors['delivery_date'] = 'Enter a valid delivery date.';
        } elseif ($delivery < date('Y-m-d')) {
            $errors['delivery_date'] = 'Delivery date cannot be in the past.';
        }
        if ($pickup !== '' && !lms_valid_date($pickup)) {
            $errors['pickup_date'] = 'Enter a valid pickup date.';
        }
        $items = [];
        if (!is_array($rawItems) || !$rawItems) {
            $errors['items'] = 'Add at least one service.';
        } else {
            $seen = [];
            foreach ($rawItems as $r) {
                $sid = lms_int($r['service_id'] ?? 0, 0);
                $qty = $r['quantity'] ?? 0;
                if ($sid <= 0) {
                    $errors['items'] = 'Select a valid service for every row.';
                    break;
                }
                if (!is_numeric($qty) || (int)$qty != $qty || (int)$qty <= 0 || (int)$qty > 1000) {
                    $errors['items'] = 'Quantity must be a positive whole number (1-1000).';
                    break;
                }
                $seen[$sid] = ($seen[$sid] ?? 0) + (int)$qty;
            }
            foreach ($seen as $sid => $qty) {
                $items[] = ['service_id' => $sid, 'qty' => $qty];
            }
        }
        if (!in_array($dtype, ['none', 'fixed', 'percent'], true)) {
            $errors['discount_type'] = 'Invalid discount type.';
        }
        if ($dval < 0 || ($dtype === 'percent' && $dval > 100) || ($dtype === 'fixed' && $dval > 1000000)) {
            $errors['discount_value'] = 'Invalid discount value.';
        }
        if ($errors) {
            lms_fail('Invalid order data.', 422, $errors);
        }
        $cs = $pdo->prepare('SELECT `id` FROM `customers` WHERE `id` = :id AND `is_active` = 1');
        $cs->execute([':id' => $customerId]);
        if (!$cs->fetch()) {
            lms_fail('Invalid customer.', 422, ['customer_id' => 'Customer does not exist.']);
        }
        if ($staff > 0) {
            $ss = $pdo->prepare('SELECT `id` FROM `users` WHERE `id` = :id AND `is_active` = 1');
            $ss->execute([':id' => $staff]);
            if (!$ss->fetch()) {
                lms_fail('Invalid staff assignment.', 422, ['assigned_staff' => 'Staff does not exist.']);
            }
        }
        $ids = array_column($items, 'service_id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sp = $pdo->prepare("SELECT `id`,`price` FROM `services` WHERE `id` IN ($ph) AND `is_active` = 1");
        $sp->execute($ids);
        $prices = [];
        foreach ($sp->fetchAll() as $r) {
            $prices[(int)$r['id']] = (float)$r['price'];
        }
        foreach ($items as $it2) {
            if (!isset($prices[$it2['service_id']])) {
                lms_fail('Invalid service selected.', 422, ['items' => 'One or more services are invalid.']);
            }
        }
        $set = lms_settings($pdo);
        $grate = (float)($set['gst_rate'] ?? 5);
        $grate = min(100, max(0, $grate));
        if (($set['gst_enabled'] ?? '1') === '0') {
            $grate = 0;
        }
        $inclusive = ($set['gst_inclusive'] ?? '0') === '1';
        $lines = [];
        foreach ($items as $it2) {
            $lines[] = ['price' => $prices[$it2['service_id']], 'qty' => $it2['qty']];
        }
        $bill = lms_bill($lines, $dtype, $dval, $grate, $inclusive);
        $today = date('Y-m-d');
        $created = lms_idem_process($pdo, $key, function (PDO $pdo) use ($customerId, $today, $pickup, $delivery, $staff, $bill, $items, $lines, $notes, $u) {
            $io = $pdo->prepare('INSERT INTO `orders` (`customer_id`,`order_date`,`pickup_date`,`delivery_date`,`assigned_staff`,`subtotal`,`discount_type`,`discount_value`,`discount_amount`,`taxable_amount`,`gst_rate`,`gst_amount`,`cgst_amount`,`sgst_amount`,`igst_amount`,`total_amount`,`grand_total`,`paid_amount`,`payment_status`,`notes`,`status`,`created_by`) VALUES (:c,:od,:pk,:dd,:st,:sub,:dt,:dv,:da,:tax,:gr,:ga,:cg,:sg,:ig,:tot,:grand,0,:ps,:n,:s,:cb)');
            $io->execute([
                ':c' => $customerId, ':od' => $today, ':pk' => ($pickup !== '' ? $pickup : null), ':dd' => $delivery,
                ':st' => ($staff > 0 ? $staff : null), ':sub' => $bill['subtotal'], ':dt' => $bill['discount_type'],
                ':dv' => $bill['discount_value'], ':da' => $bill['discount_amount'], ':tax' => $bill['taxable'],
                ':gr' => $bill['gst_rate'], ':ga' => $bill['gst'], ':cg' => $bill['cgst'], ':sg' => $bill['sgst'],
                ':ig' => $bill['igst'], ':tot' => $bill['grand'], ':grand' => $bill['grand'], ':ps' => 'Unpaid',
                ':n' => ($notes !== '' ? $notes : null), ':s' => 'Pending', ':cb' => $u['id']
            ]);
            $oid = (int)$pdo->lastInsertId();
            $ii = $pdo->prepare('INSERT INTO `order_items` (`order_id`,`service_id`,`quantity`,`price`,`line_total`) VALUES (:o,:s,:q,:p,:l)');
            foreach ($items as $k => $it2) {
                $lt = round($lines[$k]['price'] * $it2['qty'], 2);
                $ii->execute([':o' => $oid, ':s' => $it2['service_id'], ':q' => $it2['qty'], ':p' => $lines[$k]['price'], ':l' => $lt]);
            }
            $inv = lms_invoice_no($pdo, $oid);
            $pdo->prepare('UPDATE `orders` SET `invoice_no` = :inv WHERE `id` = :id')->execute([':inv' => $inv, ':id' => $oid]);
            return ['id' => $oid, 'invoice_no' => $inv, 'grand_total' => $bill['grand']];
        });
        if (!empty($created['duplicate'])) {
            lms_ok('Order already placed.', $created, 200);
        }
        lms_notify($pdo, 'order_new', 'New order ' . ($created['invoice_no'] ?: ('#' . $created['id'])), 'Order placed for customer #' . $customerId, 'pages/order_view.php?id=' . $created['id']);
        lms_ok('Order placed.', $created, 201);
    }
    if ($action === 'status') {
        lms_require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        $to = lms_str($in['status'] ?? '', 20);
        if ($id <= 0) {
            lms_fail('Order not found.', 404);
        }
        if (!in_array($to, LMS_ORDER_STATUSES, true)) {
            lms_fail('Invalid status.', 422, ['status' => 'Unknown status value.']);
        }
        $st = $pdo->prepare('SELECT `status`,`invoice_no`,`delivery_date` FROM `orders` WHERE `id` = :id');
        $st->execute([':id' => $id]);
        $cur = $st->fetch();
        if (!$cur) {
            lms_fail('Order not found.', 404);
        }
        $from = $cur['status'];
        if ($from === $to) {
            lms_ok('Status unchanged.', ['status' => $from]);
        }
        if (!in_array($to, LMS_STATUS_NEXT[$from] ?? [], true)) {
            lms_fail("Cannot move order from $from to $to.", 409);
        }
        $extra = '';
        $params = [':s' => $to, ':id' => $id];
        if ($to === 'Delivered') {
            $extra = ', `actual_delivery_date` = :ad';
            $params[':ad'] = date('Y-m-d');
        }
        $pdo->prepare("UPDATE `orders` SET `status` = :s $extra WHERE `id` = :id")->execute($params);
        if ($to === 'Ready') {
            lms_notify($pdo, 'order_ready', 'Order ' . ($cur['invoice_no'] ?: ('#' . $id)) . ' is ready', 'Customer can be notified for pickup.', 'pages/order_view.php?id=' . $id);
        } elseif ($to === 'Delivered') {
            lms_notify($pdo, 'order_delivered', 'Order ' . ($cur['invoice_no'] ?: ('#' . $id)) . ' delivered', '', 'pages/order_view.php?id=' . $id);
        }
        lms_ok('Order status updated.', ['status' => $to]);
    }
    lms_fail('Unknown action.', 404);
});

