<?php
declare(strict_types=1);
function lms_json(bool $success, string $message = '', $data = null, array $errors = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) {
        $out['data'] = $data;
    }
    if ($errors) {
        $out['errors'] = $errors;
    }
    echo json_encode($out);
    exit;
}
function lms_ok(string $message = '', $data = null, int $code = 200): void {
    lms_json(true, $message, $data, [], $code);
}
function lms_fail(string $message, int $code = 400, array $errors = []): void {
    lms_json(false, $message, null, $errors, $code);
}
function lms_input(): array {
    $data = $_POST;
    $raw = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $data = array_merge($data, $json);
        }
    }
    return $data;
}
function lms_handle(callable $fn): void {
    try {
        $fn();
    } catch (PDOException $e) {
        error_log('LMS DB error: ' . $e->getMessage());
        $code = (int)$e->getCode();
        if (in_array($code, [2002, 2003, 2006, 1049], true)) {
            lms_fail('Database unavailable. Please try again shortly.', 503);
        }
        lms_fail('Database unavailable. Please try again.', 500);
    } catch (LMS_Api_Exception $e) {
        lms_fail($e->getMessage(), $e->status, $e->errors);
    } catch (Throwable $e) {
        error_log('LMS error: ' . $e->getMessage());
        lms_fail('Unexpected server error.', 500);
    }
}

