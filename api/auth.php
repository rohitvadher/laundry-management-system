<?php
declare(strict_types=1);
require_once __DIR__ . '/../backend/bootstrap.php';
$action = $_GET['action'] ?? lms_input()['action'] ?? '';
lms_handle(function () use ($action) {
    $pdo = lms_db();
    if ($action === 'login') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        $in = lms_input();
        $username = lms_str($in['username'] ?? '', 50);
        $password = (string)($in['password'] ?? '');
        if ($username === '' || $password === '') {
            lms_fail('Please fill in all fields.', 422, ['username' => 'required']);
        }
        if (!lms_login_allowed($pdo, $username)) {
            lms_fail('Too many failed attempts. Try again in a few minutes.', 429);
        }
        try {
            $user = lms_login($pdo, $username, $password);
        } catch (RuntimeException $e) {
            lms_login_note_fail($pdo, $username);
            lms_fail('Invalid username or password.', 401);
        }
        lms_login_clear($pdo, $username);
        lms_ok('Login successful.', ['user' => $user, 'csrf' => lms_csrf()]);
    }
    if ($action === 'logout') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            lms_fail('Invalid request method.', 405);
        }
        lms_logout();
        lms_ok('Signed out.');
    }
    if ($action === 'me') {
        $u = lms_require_login();
        lms_ok('', ['user' => $u, 'csrf' => lms_csrf()]);
    }
    lms_fail('Unknown action.', 404);
});

