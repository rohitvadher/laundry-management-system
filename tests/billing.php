<?php
declare(strict_types=1);
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}
require_once __DIR__ . '/../backend/bootstrap.php';

$pass = 0;
$fail = 0;
function eq($a, $b, string $label): void {
    global $pass, $fail;
    if (abs((float)$a - (float)$b) < 0.005) {
        $pass++;
    } else {
        $fail++;
        echo "FAIL  $label â€” expected " . var_export($b, true) . " got " . var_export($a, true) . "\n";
    }
}
function is_(bool $b, string $label): void {
    global $pass, $fail;
    if ($b) {
        $pass++;
    } else {
        $fail++;
        echo "FAIL  $label\n";
    }
}

$b = lms_bill([['price' => 100, 'qty' => 1]], 'none', 0, 5, false);
eq($b['subtotal'], 100, '1x100 no disc subtotal');
eq($b['discount_amount'], 0, 'no disc amount');
eq($b['taxable'], 100, 'taxable 100');
eq($b['gst'], 5, 'gst 5');
eq($b['grand'], 105, 'grand 105');
eq($b['cgst'], 2.5, 'cgst 2.5');
eq($b['sgst'], 2.5, 'sgst 2.5');

$b = lms_bill([['price' => 50, 'qty' => 2]], 'none', 0, 5, false);
eq($b['subtotal'], 100, '2x50 subtotal');

$b = lms_bill([['price' => 99.99, 'qty' => 1]], 'none', 0, 5, false);
eq($b['grand'], 104.99, '99.99 + 5%% = 104.99');

$b = lms_bill([['price' => 0.01, 'qty' => 1]], 'none', 0, 5, false);
eq($b['subtotal'], 0.01, '0.01 subtotal');
eq($b['grand'], 0.01, '0.01 grand (gst rounds to 0)');

$b = lms_bill([['price' => 100, 'qty' => 1], ['price' => 50, 'qty' => 2]], 'fixed', 10, 5, false);
eq($b['subtotal'], 200, 'fixed disc subtotal');
eq($b['discount_amount'], 10, 'fixed disc 10');
eq($b['taxable'], 190, 'taxable after fixed disc');
eq($b['grand'], 199.50, 'grand after fixed disc 5%%');
eq($b['cgst'], 4.75, 'cgst after fixed disc');
eq($b['sgst'], 4.75, 'sgst after fixed disc');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'fixed', 200, 5, false);
eq($b['discount_amount'], 100, 'fixed disc capped at subtotal');
eq($b['discount_value'], 200, 'discount_value keeps raw dval');
eq($b['grand'], 0, 'fully discounted grand');
eq($b['taxable'], 0, 'fully discounted taxable');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'percent', 50, 5, false);
eq($b['discount_amount'], 50, '50%% disc amount');
eq($b['taxable'], 50, 'taxable after 50%%');
eq($b['grand'], 52.50, 'grand after 50%%');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'percent', 100, 5, false);
eq($b['discount_amount'], 100, '100%% disc amount');
eq($b['grand'], 0, '100%% grand 0');
eq($b['discount_value'], 100, 'percent capped at 100');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'percent', 150, 5, false);
eq($b['discount_amount'], 100, 'percent disc capped');
is_($b['discount_value'] <= 100, 'percent dval capped at 100');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'banana', 10, 5, false);
is_($b['discount_type'] === 'none', 'invalid type coerced to none');
eq($b['discount_amount'], 0, 'invalid type disc amount 0');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'none', 0, 0, false);
eq($b['gst'], 0, '0%% gst');
eq($b['grand'], 100, '0%% grand');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'none', 0, 18, false);
eq($b['grand'], 118, '18%% grand');
eq($b['cgst'], 9, '18%% cgst');
eq($b['sgst'], 9, '18%% sgst');

$b = lms_bill([['price' => 50, 'qty' => 1]], 'none', 0, 5, false);
eq($b['gst'], 2.5, 'half-cent gst');
eq($b['cgst'], 1.25, 'cgst half 1.25');
eq($b['sgst'], 1.25, 'sgst half 1.25');

$b = lms_bill([['price' => 100, 'qty' => 1]], 'none', 0, 5, true);
eq($b['taxable'], 95.24, 'inclusive taxable 95.24');
eq($b['gst'], 4.76, 'inclusive gst 4.76');
eq($b['grand'], 100, 'inclusive grand = afterDisc');
eq($b['cgst'], 2.38, 'inclusive cgst');
eq($b['sgst'], 2.38, 'inclusive sgst');

$b = lms_bill([['price' => 99999.99, 'qty' => 999]], 'none', 0, 0, false);
eq($b['subtotal'], round(99999.99 * 999, 2), 'large amount subtotal');
eq($b['grand'], round(99999.99 * 999, 2), 'large amount grand');

is_(lms_pay_status(0, 0) === 'Paid', 'zero-total unpaid -> Paid');
is_(lms_pay_status(0, 5) === 'Paid', 'zero-total with money -> Paid');
is_(lms_pay_status(100, 0) === 'Unpaid', 'Unpaid');
is_(lms_pay_status(100, 50) === 'Partial', 'Partial');
is_(lms_pay_status(100, 100) === 'Paid', 'Paid');
is_(lms_pay_status(100, 150) === 'Paid', 'overpaid -> Paid');

is_(lms_currency_symbol(['currency' => 'INR']) === "\xE2\x82\xB9", 'INR symbol rupee');
is_(lms_currency_symbol(['currency' => 'usd']) === 'USD ', 'USD symbol has space');
is_(lms_currency_symbol(['currency' => '']) === '', 'empty currency no symbol');

$long = str_repeat('a', 100);
is_(strlen(lms_idem_normalize_key($long)) === 64, 'idem key truncated to 64');

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
