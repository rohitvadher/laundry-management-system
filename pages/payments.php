<?php
declare(strict_types=1);
$pageTitle = 'Payments';
$pageScript = '../assets/js/pages/payments.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Work / Payments</div><h2>Payments</h2></div></div>
<div class="card"><div class="cbody">
<div class="filterbar">
<div class="field search"><label for="q">Order id</label><input class="input" id="q" type="number" placeholder="Order id..."></div>
<div class="field"><label for="fMethod">Method</label><select class="select" id="fMethod"><option value="">All</option><option>Cash</option><option>UPI</option><option>Card</option><option>Bank transfer</option><option>Other</option></select></div>
<div class="field"><label for="fFrom">From</label><input class="input" type="date" id="fFrom"></div>
<div class="field"><label for="fTo">To</label><input class="input" type="date" id="fTo"></div>
<div class="field"><label>&nbsp;</label><button class="btn primary" id="searchBtn">Filter</button></div>
</div>
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Date</th><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col">Method</th><th scope="col">Reference</th><th scope="col" class="num">Amount</th></tr></thead><tbody id="rows"><tr><td colspan="6"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
<div class="pager" id="pager"></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

