<?php
declare(strict_types=1);
function lms_str($v, int $max = 255): string {
    return mb_substr(trim((string)($v ?? '')), 0, $max);
}
function lms_int($v, int $def = 0): int {
    if (is_int($v)) {
        return $v;
    }
    if (is_string($v) && preg_match('/^-?\d+$/', trim($v))) {
        return (int)trim($v);
    }
    if (is_numeric($v) && (int)$v == $v) {
        return (int)$v;
    }
    return $def;
}
function lms_money($v): float {
    return round(max(0, (float)($v ?? 0)), 2);
}
function lms_valid_date(?string $d): bool {
    if ($d === null || $d === '') {
        return false;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
        return false;
    }
    [$y, $m, $day] = array_map('intval', explode('-', $d));
    return checkdate($m, $day, $y);
}
function lms_valid_mobile(string $m): bool {
    $t = preg_replace('/[\s\-+]/', '', $m);
    return $t !== '' && ctype_digit($t) && strlen($t) >= 7 && strlen($t) <= 15;
}
function lms_page(array $in, int $defLimit = 15, int $maxLimit = 100): array {
    $page = max(1, lms_int($in['page'] ?? 1, 1));
    $limit = lms_int($in['limit'] ?? $defLimit, $defLimit);
    $limit = min(max(1, $limit), $maxLimit);
    return [$page, $limit, ($page - 1) * $limit];
}
function lms_esc($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

