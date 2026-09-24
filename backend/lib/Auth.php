<?php
declare(strict_types=1);
function lms_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}
function lms_user(): ?array {
    lms_session();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity'] > LMS_SESSION_TIMEOUT)) {
        lms_logout();
        return null;
    }
    $_SESSION['last_activity'] = time();
    return [
        'id' => (int)$_SESSION['user_id'],
        'name' => (string)($_SESSION['user_name'] ?? ''),
        'role' => (string)($_SESSION['role'] ?? 'staff')
    ];
}
function lms_require_login(): array {
    $u = lms_user();
    if (!$u) {
        lms_fail('Session expired. Please log in again.', 401);
    }
    return $u;
}
function lms_can(string $role, string $action): bool {
    $matrix = [
        'users' => ['admin'],
        'settings' => ['admin', 'manager'],
        'service_write' => ['admin', 'manager'],
        'reports_finance' => ['admin', 'manager', 'accountant'],
        'payment_write' => ['admin', 'manager', 'accountant'],
        'delete' => ['admin', 'manager'],
        'inventory' => ['admin', 'manager', 'staff']
    ];
    if (!isset($matrix[$action])) {
        return in_array($role, LMS_ROLES, true);
    }
    return in_array($role, $matrix[$action], true);
}
function lms_require_role(array $roles): array {
    $u = lms_require_login();
    if (!in_array($u['role'], $roles, true)) {
        lms_fail('Unauthorized action.', 403);
    }
    return $u;
}
function lms_require_perm(string $action): array {
    $u = lms_require_login();
    if (!lms_can($u['role'], $action)) {
        lms_fail('Unauthorized action.', 403);
    }
    return $u;
}
function lms_login(PDO $pdo, string $username, string $password): array {
    $st = $pdo->prepare("SELECT * FROM `users` WHERE `username` = :u AND `is_active` = 1");
    $st->execute([':u' => $username]);
    $user = $st->fetch();
    $hash = $user ? (string)$user['password'] : '$2y$10$aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    if (!$user || !password_verify($password, $hash)) {
        throw new RuntimeException('bad');
    }
    if (password_needs_rehash((string)$user['password'], PASSWORD_DEFAULT)) {
        $up = $pdo->prepare('UPDATE `users` SET `password` = :p WHERE `id` = :id');
        $up->execute([':p' => password_hash($password, PASSWORD_DEFAULT), ':id' => $user['id']]);
    }
    lms_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = (string)$user['full_name'];
    $_SESSION['role'] = (string)$user['role'];
    $_SESSION['last_activity'] = time();
    return ['id' => (int)$user['id'], 'name' => (string)$user['full_name'], 'role' => (string)$user['role']];
}
function lms_logout(): void {
    lms_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function lms_login_ip(): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'cli');
    return mb_substr($ip, 0, 45);
}
function lms_login_allowed(PDO $pdo, string $username): bool {
    try {
        $st = $pdo->prepare('SELECT `fails`,`locked_until` FROM `login_attempts` WHERE `ip` = :ip AND `username` = :u');
        $st->execute([':ip' => lms_login_ip(), ':u' => mb_strtolower(trim($username))]);
        $row = $st->fetch();
    } catch (PDOException $e) {
        return true;
    }
    if (!$row) {
        return true;
    }
    if ($row['locked_until'] !== null && strtotime((string)$row['locked_until']) > time()) {
        return false;
    }
    return true;
}
function lms_login_note_fail(PDO $pdo, string $username): void {
    try {
        $key = mb_strtolower(trim($username));
        $st = $pdo->prepare('SELECT `id`,`fails` FROM `login_attempts` WHERE `ip` = :ip AND `username` = :u');
        $st->execute([':ip' => lms_login_ip(), ':u' => $key]);
        $row = $st->fetch();
        if (!$row) {
            $pdo->prepare('INSERT INTO `login_attempts` (`ip`,`username`,`fails`) VALUES (:ip,:u,1)')->execute([':ip' => lms_login_ip(), ':u' => $key]);
            return;
        }
        $fails = (int)$row['fails'] + 1;
        if ($fails >= LMS_LOGIN_MAX_ATTEMPTS) {
            $pdo->prepare('UPDATE `login_attempts` SET `fails` = 0, `locked_until` = :u2 WHERE `id` = :id')->execute([':u2' => date('Y-m-d H:i:s', time() + LMS_LOGIN_LOCK_SECONDS), ':id' => $row['id']]);
            error_log('LMS login lockout from ' . lms_login_ip());
        } else {
            $pdo->prepare('UPDATE `login_attempts` SET `fails` = :f WHERE `id` = :id')->execute([':f' => $fails, ':id' => $row['id']]);
        }
        $pdo->exec('DELETE FROM `login_attempts` WHERE `updated_at` < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    } catch (PDOException $e) {
    }
}
function lms_login_clear(PDO $pdo, string $username): void {
    try {
        $pdo->prepare('DELETE FROM `login_attempts` WHERE `ip` = :ip AND `username` = :u')->execute([':ip' => lms_login_ip(), ':u' => mb_strtolower(trim($username))]);
    } catch (PDOException $e) {
    }
}

