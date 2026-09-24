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
        $active = $in['active'] ?? '1';
        $where = [];
        $p = [];
        if ($q !== '') {
            $where[] = '(`customer_name` LIKE :q1 OR `mobile` LIKE :q2)';
            $p[':q1'] = '%' . $q . '%';
            $p[':q2'] = '%' . $q . '%';
        }
        if ($active === '1' || $active === '0') {
            $where[] = '`is_active` = :a';
            $p[':a'] = $active;
        }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $c = $pdo->prepare("SELECT COUNT(*) FROM `customers` $w");
        $c->execute($p);
        $total = (int)$c->fetchColumn();
        $st = $pdo->prepare("SELECT c.*, COUNT(o.`id`) AS order_count, COALESCE(SUM(CASE WHEN o.`status` <> 'Cancelled' THEN o.`grand_total` ELSE 0 END),0) AS total_spent, COALESCE(SUM(CASE WHEN o.`status` <> 'Cancelled' AND o.`payment_status` <> 'Paid' THEN o.`grand_total` - o.`paid_amount` ELSE 0 END),0) AS outstanding FROM `customers` c LEFT JOIN `orders` o ON o.`customer_id` = c.`id` $w GROUP BY c.`id` ORDER BY c.`id` DESC LIMIT :l OFFSET :o");
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
            lms_fail('Customer not found.', 404);
        }
        $st = $pdo->prepare('SELECT * FROM `customers` WHERE `id` = :id');
        $st->execute([':id' => $id]);
        $row = $st->fetch();
        if (!$row) {
            lms_fail('Customer not found.', 404);
        }
        lms_ok('', ['customer' => $row]);
    }
    if ($action === 'history') {
        lms_require_login();
        $id = lms_int($_GET['id'] ?? 0, 0);
        if ($id <= 0) {
            lms_fail('Customer not found.', 404);
        }
        $st = $pdo->prepare('SELECT `id`,`invoice_no`,`order_date`,`delivery_date`,`subtotal`,`discount_amount`,`gst_amount`,`grand_total`,`paid_amount`,`payment_status`,`status`,`created_at` FROM `orders` WHERE `customer_id` = :id ORDER BY `created_at` DESC LIMIT 100');
        $st->execute([':id' => $id]);
        $rows = $st->fetchAll();
        $agg = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(CASE WHEN `status` <> 'Cancelled' THEN `grand_total` ELSE 0 END),0) AS spent, COALESCE(SUM(CASE WHEN `status` <> 'Cancelled' AND `payment_status` <> 'Paid' THEN `grand_total` - `paid_amount` ELSE 0 END),0) AS due, MAX(`created_at`) AS last_order FROM `orders` WHERE `customer_id` = :id");
        $agg->execute([':id' => $id]);
        lms_ok('', ['orders' => $rows, 'summary' => $agg->fetch()]);
    }
    if ($action === 'save') {
        lms_require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        $name = lms_str($in['customer_name'] ?? '', 100);
        $mobile = lms_str($in['mobile'] ?? '', 20);
        $address = lms_str($in['address'] ?? '', 500);
        $errors = [];
        if ($name === '') {
            $errors['customer_name'] = 'Customer name is required.';
        }
        if (!lms_valid_mobile($mobile)) {
            $errors['mobile'] = 'Enter a valid mobile number.';
        }
        if ($errors) {
            lms_fail('Invalid form data.', 422, $errors);
        }
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE `customers` SET `customer_name` = :n, `mobile` = :m, `address` = :a WHERE `id` = :id');
            $st->execute([':n' => $name, ':m' => $mobile, ':a' => $address ?: null, ':id' => $id]);
            lms_ok('Customer updated.', ['id' => $id]);
        }
        $st = $pdo->prepare('INSERT INTO `customers` (`customer_name`,`mobile`,`address`) VALUES (:n,:m,:a)');
        $st->execute([':n' => $name, ':m' => $mobile, ':a' => $address ?: null]);
        lms_ok('Customer created.', ['id' => (int)$pdo->lastInsertId()], 201);
    }
    if ($action === 'delete') {
        $u = lms_require_perm('delete');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        if ($id <= 0) {
            lms_fail('Customer not found.', 404);
        }
        $c = $pdo->prepare('SELECT COUNT(*) FROM `orders` WHERE `customer_id` = :id');
        $c->execute([':id' => $id]);
        if ((int)$c->fetchColumn() > 0) {
            $st = $pdo->prepare('UPDATE `customers` SET `is_active` = 0 WHERE `id` = :id');
            $st->execute([':id' => $id]);
            lms_ok('Customer has order history, so it was archived instead of deleted.');
        }
        $st = $pdo->prepare('DELETE FROM `customers` WHERE `id` = :id');
        $st->execute([':id' => $id]);
        if ($st->rowCount() === 0) {
            lms_fail('Record already deleted.', 409);
        }
        lms_ok('Customer deleted.');
    }
    if ($action === 'restore') {
        lms_require_perm('delete');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $st = $pdo->prepare('UPDATE `customers` SET `is_active` = 1 WHERE `id` = :id');
        $st->execute([':id' => lms_int($in['id'] ?? 0, 0)]);
        lms_ok('Customer restored.');
    }
    lms_fail('Unknown action.', 404);
});

