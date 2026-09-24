<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? lms_input()['action'] ?? 'list';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    if ($action === 'roles') {
        lms_require_login();
        lms_ok('', ['roles' => LMS_ROLES]);
    }
    if ($action === 'list') {
        lms_require_perm('users');
        $rows = $pdo->query('SELECT `id`,`username`,`full_name`,`role`,`is_active`,`created_at` FROM `users` ORDER BY `id` ASC')->fetchAll();
        lms_ok('', ['rows' => $rows]);
    }
    if ($action === 'staff_options') {
        lms_require_login();
        $rows = $pdo->query("SELECT `id`,`full_name` FROM `users` WHERE `is_active` = 1 ORDER BY `full_name` ASC")->fetchAll();
        lms_ok('', ['rows' => $rows]);
    }
    if ($action === 'save') {
        $me = lms_require_perm('users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        $username = lms_str($in['username'] ?? '', 50);
        $name = lms_str($in['full_name'] ?? '', 100);
        $role = lms_str($in['role'] ?? 'staff', 20);
        $password = (string)($in['password'] ?? '');
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $errors['username'] = 'Username: 3-50 letters, numbers or underscore.';
        }
        if ($name === '') {
            $errors['full_name'] = 'Full name is required.';
        }
        if (!in_array($role, LMS_ROLES, true)) {
            $errors['role'] = 'Invalid role.';
        }
        if ($id === 0 && strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($password !== '' && strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters.';
        }
        if ($errors) {
            lms_fail('Invalid user data.', 422, $errors);
        }
        if ($id > 0) {
            $st = $pdo->prepare('SELECT `role`,`is_active` FROM `users` WHERE `id` = :id');
            $st->execute([':id' => $id]);
            $cur = $st->fetch();
            if (!$cur) {
                lms_fail('User not found.', 404);
            }
            if ((int)$id === (int)$me['id'] && (string)$cur['role'] === 'admin' && $role !== 'admin') {
                $admins = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin' AND `is_active` = 1")->fetchColumn();
                if ($admins <= 1) {
                    lms_fail('You cannot demote the last active admin.', 409);
                }
            }
            if ($password !== '') {
                $pdo->prepare('UPDATE `users` SET `username` = :u, `full_name` = :n, `role` = :r, `password` = :p WHERE `id` = :id')->execute([':u' => $username, ':n' => $name, ':r' => $role, ':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
            } else {
                $pdo->prepare('UPDATE `users` SET `username` = :u, `full_name` = :n, `role` = :r WHERE `id` = :id')->execute([':u' => $username, ':n' => $name, ':r' => $role, ':id' => $id]);
            }
            lms_ok('User updated.', ['id' => $id]);
        }
        try {
            $pdo->prepare('INSERT INTO `users` (`username`,`password`,`full_name`,`role`) VALUES (:u,:p,:n,:r)')->execute([':u' => $username, ':p' => password_hash($password, PASSWORD_DEFAULT), ':n' => $name, ':r' => $role]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'uplicate') !== false) {
                lms_fail('Username already exists.', 409, ['username' => 'Taken.']);
            }
            throw $e;
        }
        lms_ok('User created.', ['id' => (int)$pdo->lastInsertId()], 201);
    }
    if ($action === 'toggle') {
        $me = lms_require_perm('users');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        lms_csrf_check(lms_csrf_from_input($in));
        $id = lms_int($in['id'] ?? 0, 0);
        if ($id === (int)$me['id']) {
            lms_fail('You cannot deactivate your own account.', 409);
        }
        $st = $pdo->prepare('SELECT `role`,`is_active` FROM `users` WHERE `id` = :id');
        $st->execute([':id' => $id]);
        $target = $st->fetch();
        if (!$target) {
            lms_fail('User not found.', 404);
        }
        if ($target['role'] === 'admin' && (int)$target['is_active'] === 1) {
            $admins = (int)$pdo->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin' AND `is_active` = 1")->fetchColumn();
            if ($admins <= 1) {
                lms_fail('Cannot deactivate the last active admin.', 409);
            }
        }
        $pdo->prepare('UPDATE `users` SET `is_active` = 1 - `is_active` WHERE `id` = :id')->execute([':id' => $id]);
        lms_ok('User status changed.');
    }
    lms_fail('Unknown action.', 404);
});

