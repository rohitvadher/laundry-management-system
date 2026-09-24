<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? lms_input()['action'] ?? 'list';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    if ($action === 'list') {
        lms_require_login();
        $in = array_merge($_GET, lms_input());
        $q = lms_str($in['q'] ?? '', 80);
        $p = [];
        $w = '`is_active` = 1';
        if ($q !== '') {
            $w .= ' AND `item_name` LIKE :q';
            $p[':q'] = '%' . $q . '%';
        }
        $st = $pdo->prepare("SELECT *, CASE WHEN `current_stock` <= `low_threshold` THEN 1 ELSE 0 END AS is_low FROM `inventory_items` WHERE $w ORDER BY is_low DESC, `item_name` ASC LIMIT 500");
        $st->execute($p);
        lms_ok('', ['rows' => $st->fetchAll()]);
    }
    if ($action === 'save') {
        lms_require_perm('inventory');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        $name = lms_str($in['item_name'] ?? '', 120);
        $unit = lms_str($in['unit'] ?? 'pcs', 30);
        $low = max(0, (float)($in['low_threshold'] ?? 0));
        if ($name === '' || $unit === '') {
            lms_fail('Invalid item data.', 422);
        }
        if ($id > 0) {
            $pdo->prepare('UPDATE `inventory_items` SET `item_name` = :n, `unit` = :u, `low_threshold` = :t WHERE `id` = :id')->execute([':n' => $name, ':u' => $unit, ':t' => $low, ':id' => $id]);
            lms_ok('Item updated.', ['id' => $id]);
        }
        $st = $pdo->prepare('INSERT INTO `inventory_items` (`item_name`,`unit`,`low_threshold`) VALUES (:n,:u,:t)');
        $st->execute([':n' => $name, ':u' => $unit, ':t' => $low]);
        lms_ok('Item created.', ['id' => (int)$pdo->lastInsertId()], 201);
    }
    if ($action === 'move') {
        $u = lms_require_perm('inventory');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['item_id'] ?? 0, 0);
        $type = lms_str($in['move_type'] ?? '', 10);
        $qty = (float)($in['quantity'] ?? 0);
        $note = lms_str($in['note'] ?? '', 255);
        if ($id <= 0 || !in_array($type, ['purchase', 'usage', 'adjust'], true) || $qty <= 0 || $qty > 1000000) {
            lms_fail('Invalid movement data.', 422);
        }
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('SELECT * FROM `inventory_items` WHERE `id` = :id FOR UPDATE');
            $st->execute([':id' => $id]);
            $item = $st->fetch();
            if (!$item) {
                $pdo->rollBack();
                lms_fail('Item not found.', 404);
            }
            if ($type === 'usage' && $qty > (float)$item['current_stock']) {
                $pdo->rollBack();
                lms_fail('Insufficient stock.', 422);
            }
            $delta = $type === 'usage' ? -$qty : $qty;
            if ($type === 'adjust') {
                $delta = $qty - (float)$item['current_stock'];
            }
            $pdo->prepare('INSERT INTO `inventory_movements` (`item_id`,`move_type`,`quantity`,`note`,`moved_by`) VALUES (:i,:t,:q,:n,:u)')->execute([':i' => $id, ':t' => $type, ':q' => $qty, ':n' => ($note !== '' ? $note : null), ':u' => $u['id']]);
            $pdo->prepare('UPDATE `inventory_items` SET `current_stock` = `current_stock` + :d WHERE `id` = :id')->execute([':d' => $delta, ':id' => $id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $cur = $pdo->prepare('SELECT `current_stock`,`low_threshold`,`item_name` FROM `inventory_items` WHERE `id` = :id');
        $cur->execute([':id' => $id]);
        $cur = $cur->fetch();
        if ($cur && (float)$cur['current_stock'] <= (float)$cur['low_threshold']) {
            lms_notify($pdo, 'stock_low', 'Low stock: ' . $cur['item_name'], 'Current: ' . $cur['current_stock'], 'pages/inventory.php');
        }
        lms_ok('Stock updated.', ['stock' => (float)$cur['current_stock']]);
    }
    if ($action === 'moves') {
        lms_require_login();
        $id = lms_int($_GET['item_id'] ?? 0, 0);
        $st = $pdo->prepare('SELECT m.*, u.`full_name` AS by_name FROM `inventory_movements` m LEFT JOIN `users` u ON u.`id` = m.`moved_by` WHERE m.`item_id` = :id ORDER BY m.`id` DESC LIMIT 50');
        $st->execute([':id' => $id]);
        lms_ok('', ['rows' => $st->fetchAll()]);
    }
    lms_fail('Unknown action.', 404);
});

