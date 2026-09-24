<?php
declare(strict_types=1);
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/../database/install.php';
function lms_pdo(bool $withDb = true): PDO {
    $dsn = $withDb
        ? 'mysql:host=' . LMS_DB_HOST . ';dbname=' . LMS_DB_NAME . ';charset=utf8mb4'
        : 'mysql:host=' . LMS_DB_HOST . ';charset=utf8mb4';
    $pdo = new PDO($dsn, LMS_DB_USER, LMS_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    return $pdo;
}
function lms_db(): PDO {
    try {
        $pdo = lms_pdo(true);
        $pdo->query('SELECT 1');
    } catch (PDOException $e) {
        $server = lms_pdo(false);
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', LMS_DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo = lms_pdo(true);
    }
    lms_install($pdo);
    return $pdo;
}

