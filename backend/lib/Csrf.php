<?php
declare(strict_types=1);
function lms_csrf(): string {
    lms_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function lms_csrf_check(?string $token): void {
    lms_session();
    $good = (string)($_SESSION['csrf'] ?? '');
    if ($good === '' || $token === null || !hash_equals($good, $token)) {
        lms_fail('Invalid request token. Please refresh and try again.', 403);
    }
}
function lms_csrf_from_input(array $in): ?string {
    if (!empty($in['csrf'])) {
        return (string)$in['csrf'];
    }
    $h = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return $h !== '' ? (string)$h : null;
}

