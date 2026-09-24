<?php
declare(strict_types=1);
function lms_settings(PDO $pdo): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $rows = $pdo->query('SELECT `skey`,`svalue` FROM `settings`')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['skey']] = $r['svalue'];
    }
    $cache = $out;
    return $out;
}
function lms_bill(array $lines, string $dtype, float $dval, float $grate, bool $inclusive): array {
    $subtotal = 0.0;
    foreach ($lines as $ln) {
        $subtotal += round((float)$ln['price'] * (int)$ln['qty'], 2);
    }
    $subtotal = round($subtotal, 2);
    $dtype = in_array($dtype, ['fixed', 'percent'], true) ? $dtype : 'none';
    $dval = max(0, (float)$dval);
    if ($dtype === 'percent') {
        $dval = min(100, $dval);
        $damount = round($subtotal * $dval / 100, 2);
    } elseif ($dtype === 'fixed') {
        $damount = round(min($subtotal, $dval), 2);
    } else {
        $dval = 0;
        $damount = 0.0;
    }
    $afterDisc = round($subtotal - $damount, 2);
    $grate = min(100, max(0, (float)$grate));
    if ($inclusive && $grate > 0 && $afterDisc > 0) {
        $taxable = round($afterDisc * 100 / (100 + $grate), 2);
        $gst = round($afterDisc - $taxable, 2);
    } else {
        $taxable = $afterDisc;
        $gst = round($taxable * $grate / 100, 2);
    }
    $grand = $inclusive ? $afterDisc : round($taxable + $gst, 2);
    $half = round($gst / 2, 2);
    return [
        'subtotal' => $subtotal,
        'discount_type' => $dtype,
        'discount_value' => round($dval, 2),
        'discount_amount' => $damount,
        'taxable' => $taxable,
        'gst_rate' => $grate,
        'gst' => $gst,
        'cgst' => $half,
        'sgst' => round($gst - $half, 2),
        'igst' => 0.0,
        'grand' => $grand
    ];
}
function lms_pay_status(float $grand, float $paid): string {
    if (round($grand, 2) <= 0) {
        return 'Paid';
    }
    if (round($paid, 2) >= round($grand, 2)) {
        return 'Paid';
    }
    if (round($paid, 2) > 0) {
        return 'Partial';
    }
    return 'Unpaid';
}
function lms_invoice_no(PDO $pdo, int $orderId): string {
    $s = lms_settings($pdo);
    $prefix = preg_replace('/[^A-Za-z0-9\-]/', '', (string)($s['invoice_prefix'] ?? 'INV'));
    if ($prefix === '') {
        $prefix = 'INV';
    }
    return $prefix . '-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT);
}
function lms_notify(PDO $pdo, string $type, string $title, string $body = '', string $link = ''): void {
    try {
        $st = $pdo->prepare('INSERT INTO `notifications` (`ntype`,`title`,`body`,`link`) VALUES (:t,:ti,:b,:l)');
        $st->execute([':t' => mb_substr($type, 0, 60), ':ti' => mb_substr($title, 0, 180), ':b' => mb_substr($body, 0, 500), ':l' => mb_substr($link, 0, 255)]);
    } catch (Throwable $e) {
    }
}
function lms_currency_symbol(array $settings): string {
    $cur = strtoupper((string)($settings['currency'] ?? 'INR'));
    return $cur === 'INR' ? "\xE2\x82\xB9" : ($cur !== '' ? $cur . ' ' : '');
}
function lms_currency(PDO $pdo, float $amount): string {
    $s = lms_settings($pdo);
    return lms_currency_symbol($s) . number_format($amount, 2);
}

