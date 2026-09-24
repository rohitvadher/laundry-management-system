<?php
declare(strict_types=1);
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/Response.php';
require_once __DIR__ . '/lib/Validator.php';
require_once __DIR__ . '/lib/Auth.php';
require_once __DIR__ . '/lib/Csrf.php';
require_once __DIR__ . '/lib/Money.php';
require_once __DIR__ . '/lib/Idem.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Kolkata');

if (!function_exists('lms_is_api_request')) {
    function lms_is_api_request(): bool {
        $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
        if (strpos($path, '/api/') !== false) {
            return true;
        }
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        return stripos($accept, 'application/json') !== false;
    }
}

if (!function_exists('lms_fatal_page')) {
    function lms_fatal_page(int $code, string $title, string $msg): void {
        if (headers_sent()) {
            return;
        }
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $m = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        $home = ((substr((string)($_SERVER['REQUEST_URI'] ?? '/'), 0, 5) === '/api/') ? '../' : '') . 'index.php';
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1.0">'
            . '<title>' . $code . ' - Laundry MS</title>'
            . '<style>body{font-family:"Segoe UI",system-ui,Arial,sans-serif;background:#F7F5F9;color:#241F2B;display:flex;align-items:center;justify-content:center;min-height:70vh;margin:0;padding:1rem}.c{background:#fff;border:1px solid #E5E0EA;border-top:5px solid #3C2182;border-radius:12px;padding:2rem;max-width:520px;width:100%;text-align:center}h1{margin:0 0 .5rem}.m{color:#6F6580}a{color:#3C2182}</style>'
            . '</head><body><div class="c"><h1>' . $code . ' â€” ' . $t . '</h1>'
            . '<p class="m">' . $m . '</p>'
            . '<p><a href="' . $home . '">Back to sign in</a></p></div></body></html>';
        exit;
    }
}

if (!function_exists('lms_exception_handler')) {
    function lms_exception_handler(Throwable $e): void {
        error_log('LMS error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        if (php_sapi_name() === 'cli') {
            if (!headers_sent()) {
                fwrite(STDERR, 'FATAL: ' . $e->getMessage() . PHP_EOL);
            }
            exit(1);
        }
        if (lms_is_api_request()) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode(['success' => false, 'message' => 'Unexpected server error.']);
            exit;
        }
        lms_fatal_page(500, 'Something went wrong', 'An unexpected problem occurred. It has been logged. Please retry shortly.');
    }
}

if (!function_exists('lms_error_handler')) {
    function lms_error_handler(int $severity, string $message, string $file, int $line): bool {
        if ($severity & (E_WARNING | E_USER_WARNING | E_ERROR | E_PARSE | E_COMPILE_ERROR | E_CORE_ERROR)) {
            error_log('LMS ' . $severity . ': ' . $message . ' @ ' . $file . ':' . $line);
        }
        return true;
    }
}

if (!function_exists('lms_shutdown_handler')) {
    function lms_shutdown_handler(): void {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            lms_exception_handler(new ErrorException($err['message'], 0, $err['type'], $err['file'], $err['line']));
        }
    }
}

set_error_handler('lms_error_handler');
set_exception_handler('lms_exception_handler');
register_shutdown_function('lms_shutdown_handler');
