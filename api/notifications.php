<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? lms_input()['action'] ?? 'list';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    lms_require_login();
    if ($action === 'list') {
        [$page, $limit, $offset] = lms_page(array_merge($_GET, lms_input()), 10, 50);
        $st = $pdo->prepare('SELECT * FROM `notifications` ORDER BY `id` DESC LIMIT :l OFFSET :o');
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->bindValue(':o', $offset, PDO::PARAM_INT);
        $st->execute();
        $un = $pdo->query('SELECT COUNT(*) FROM `notifications` WHERE `is_read` = 0')->fetchColumn();
        $total = (int)$pdo->query('SELECT COUNT(*) FROM `notifications`')->fetchColumn();
        lms_ok('', ['rows' => $st->fetchAll(), 'unread' => (int)$un, 'total' => $total]);
    }
    if ($action === 'read') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        if (!empty($in['all'])) {
            $pdo->exec('UPDATE `notifications` SET `is_read` = 1');
            lms_ok('All marked read.');
        }
        $pdo->prepare('UPDATE `notifications` SET `is_read` = 1 WHERE `id` = :id')->execute([':id' => lms_int($in['id'] ?? 0, 0)]);
        lms_ok('Marked read.');
    }
    lms_fail('Unknown action.', 404);
});

