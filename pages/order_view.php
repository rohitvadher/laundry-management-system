<?php
declare(strict_types=1);
$pageTitle = 'Order & invoice';
$pageScript = '../assets/js/pages/order_view.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead no-print"><div><div class="crumb">Work / Orders / Invoice</div><h2 id="invTitle">Invoice</h2></div><span class="spacer"></span><a class="btn" href="orders.php">Back</a><button class="btn primary" onclick="window.print()">Print</button></div>
<div id="alertBox" class="no-print"></div>
<div class="status-hero no-print" id="statusHero"><span class="spin"></span></div>
<div class="grid two">
<div class="card inv"><div class="cbody" id="invBox"><span class="spin"></span> Loading...</div></div>
<div>
<div class="card no-print"><div class="chead"><h3>Manage</h3></div><div class="cbody">
<div class="field"><label for="stSel">Order status</label><select class="select" id="stSel"></select></div>
<button class="btn primary" id="stBtn" style="width:100%">Update status</button>
<div class="foot-note" id="transNote"></div>
</div></div>
<div class="alert info no-print" id="payNote" style="display:none;margin-top:1rem"></div>
<div class="card no-print" id="payCard" style="margin-top:1rem"><div class="chead"><h3>Record payment</h3></div><div class="cbody"><form id="payForm">
<div class="frow"><div class="field"><label for="pAmt">Amount</label><input class="input" type="number" step="0.01" min="0.01" id="pAmt" name="amount" required></div>
<div class="field"><label for="pMethod">Method</label><select class="select" id="pMethod" name="method"><option>Cash</option><option>UPI</option><option>Card</option><option>Bank transfer</option><option>Other</option></select></div></div>
<div class="frow"><div class="field"><label for="pDate">Date</label><input class="input" type="date" id="pDate" name="paid_at"></div>
<div class="field"><label for="pRef">Reference</label><input class="input" id="pRef" name="reference_no" maxlength="100"></div></div>
<button class="btn primary" id="payBtn" style="width:100%">Add payment</button>
</form></div></div>
<div class="card no-print" style="margin-top:1rem"><div class="chead"><h3>Quick lookup</h3></div><div class="cbody">
<div class="field"><label for="lookup">Order reference</label><input class="input" id="lookup" placeholder="Paste invoice no..."><div style="margin-top:.4rem"><button class="btn small" id="lookupBtn">Open order</button></div></div>
</div></div>
</div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

