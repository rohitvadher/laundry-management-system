<?php
declare(strict_types=1);



class LMS_Api_Exception extends RuntimeException {
    public int $status;
    public array $errors = [];
    public function __construct(string $message, int $status = 409, array $errors = []) {
        parent::__construct($message);
        $this->status = $status;
        $this->errors = $errors;
    }
}

function lms_idem_normalize_key(string $key): string {
    return substr(trim($key), 0, 64);
}

function lms_idem_cleanup(PDO $pdo): void {
    try {
        $pdo->exec('DELETE FROM `request_keys` WHERE `created_at` < NOW() - INTERVAL 30 DAY');
    } catch (Throwable $e) {
    }
}

function lms_idem_get_stored(PDO $pdo, string $key): ?array {
    $st = $pdo->prepare('SELECT `payload` FROM `request_keys` WHERE `idem_key` = :k');
    $st->execute([':k' => $key]);
    $row = $st->fetch();
    if (!$row || $row['payload'] === null || $row['payload'] === '') {
        return null;
    }
    $payload = json_decode((string)$row['payload'], true);
    return is_array($payload) ? $payload : null;
}


function lms_idem_process(PDO $pdo, string $key, callable $work): array {
    $key = lms_idem_normalize_key($key);
    if ($key === '') {
        $pdo->beginTransaction();
        try {
            $payload = $work($pdo);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        return $payload;
    }

    lms_idem_cleanup($pdo);
    $stored = lms_idem_get_stored($pdo, $key);
    if ($stored !== null) {
        return $stored + ['duplicate' => true];
    }

    $pdo->beginTransaction();
    try {
        $payload = $work($pdo);
        $ins = $pdo->prepare('INSERT IGNORE INTO `request_keys` (`idem_key`,`payload`) VALUES (:k,:p)');
        $ins->execute([':k' => $key, ':p' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        if ((int)$ins->rowCount() === 0) {


            $pdo->rollBack();
            $stored = lms_idem_get_stored($pdo, $key);
            if ($stored === null) {
                throw new RuntimeException('Concurrent request could not be resolved.');
            }
            return $stored + ['duplicate' => true];
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return $payload;
}
