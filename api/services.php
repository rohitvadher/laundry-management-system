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
            $where[] = '(`service_name` LIKE :q1 OR `description` LIKE :q2)';
            $p[':q1'] = '%' . $q . '%';
            $p[':q2'] = '%' . $q . '%';
        }
        if ($active === '1' || $active === '0') {
            $where[] = '`is_active` = :a';
            $p[':a'] = $active;
        }
        $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $c = $pdo->prepare("SELECT COUNT(*) FROM `services` $w");
        $c->execute($p);
        $total = (int)$c->fetchColumn();
        $st = $pdo->prepare("SELECT * FROM `services` $w ORDER BY `service_name` ASC LIMIT :l OFFSET :o");
        foreach ($p as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->bindValue(':o', $offset, PDO::PARAM_INT);
        $st->execute();
        lms_ok('', ['rows' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }
    if ($action === 'active') {
        lms_require_login();
        $rows = $pdo->query("SELECT `id`,`service_name`,`price`,`gst_rate` FROM `services` WHERE `is_active` = 1 ORDER BY `service_name` ASC")->fetchAll();
        lms_ok('', ['rows' => $rows]);
    }
    if ($action === 'save') {
        lms_require_perm('service_write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        $name = lms_str($in['service_name'] ?? '', 100);
        $desc = lms_str($in['description'] ?? '', 500);
        $price = round(max(0, (float)($in['price'] ?? 0)), 2);
        $gst = round(min(100, max(0, (float)($in['gst_rate'] ?? 0))), 2);
        $errors = [];
        if ($name === '') {
            $errors['service_name'] = 'Service name is required.';
        }
        if ($price <= 0 || $price > 100000) {
            $errors['price'] = 'Price must be between 0.01 and 100000.';
        }
        if ($errors) {
            lms_fail('Invalid form data.', 422, $errors);
        }
        if ($id > 0) {
            $st = $pdo->prepare('UPDATE `services` SET `service_name` = :n, `description` = :d, `price` = :p, `gst_rate` = :g WHERE `id` = :id');
            $st->execute([':n' => $name, ':d' => $desc ?: null, ':p' => $price, ':g' => $gst, ':id' => $id]);
            lms_ok('Service updated.', ['id' => $id]);
        }
        $st = $pdo->prepare('INSERT INTO `services` (`service_name`,`description`,`price`,`gst_rate`) VALUES (:n,:d,:p,:g)');
        $st->execute([':n' => $name, ':d' => $desc ?: null, ':p' => $price, ':g' => $gst]);
        lms_ok('Service created.', ['id' => (int)$pdo->lastInsertId()], 201);
    }
    if ($action === 'delete') {
        lms_require_perm('delete');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        if ($id <= 0) {
            lms_fail('Service not found.', 404);
        }
        $c = $pdo->prepare('SELECT COUNT(*) FROM `order_items` WHERE `service_id` = :id');
        $c->execute([':id' => $id]);
        if ((int)$c->fetchColumn() > 0) {
            $st = $pdo->prepare('UPDATE `services` SET `is_active` = 0 WHERE `id` = :id');
            $st->execute([':id' => $id]);
            lms_ok('Service is used in past orders, so it was deactivated instead of deleted.');
        }
        $st = $pdo->prepare('DELETE FROM `services` WHERE `id` = :id');
        $st->execute([':id' => $id]);
        if ($st->rowCount() === 0) {
            lms_fail('Record already deleted.', 409);
        }
        lms_ok('Service deleted.');
    }
    if ($action === 'restore') {
        lms_require_perm('service_write');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $st = $pdo->prepare('UPDATE `services` SET `is_active` = 1 WHERE `id` = :id');
        $st->execute([':id' => lms_int($in['id'] ?? 0, 0)]);
        lms_ok('Service restored.');
    }
    lms_fail('Unknown action.', 404);
});

