<?php
declare(strict_types=1);
$pageTitle = 'Create order';
$pageScript = '../assets/js/pages/order_create.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Work / Orders / New</div><h2>Create order</h2></div><span class="spacer"></span><a class="btn" href="orders.php">Back to list</a></div>
<div id="alertBox"></div>
<form id="orderForm">
<div class="grid two">
<div class="card"><div class="chead"><h3>Customer &amp; schedule</h3></div><div class="cbody">
<div class="field"><label for="cust">Customer</label><select class="select" id="cust" name="customer_id" required><option value="">Loading...</option></select><div style="margin-top:.3rem"><a href="customers.php">Add new customer</a></div></div>
<div class="frow"><div class="field"><label for="pickup">Pickup date</label><input class="input" type="date" id="pickup" name="pickup_date"></div>
<div class="field"><label for="delivery">Delivery date</label><input class="input" type="date" id="delivery" name="delivery_date" required></div></div>
<div class="field"><label for="staff">Assigned staff</label><select class="select" id="staff" name="assigned_staff"><option value="">Unassigned</option></select></div>
<div class="frow"><div class="field"><label for="dtype">Discount type</label><select class="select" id="dtype" name="discount_type"><option value="none">None</option><option value="fixed">Fixed amount</option><option value="percent">Percent %</option></select></div>
<div class="field"><label for="dval">Discount value</label><input class="input" type="number" step="0.01" min="0" id="dval" name="discount_value" value="0"></div></div>
<div class="field"><label for="notes">Notes</label><textarea class="textarea" id="notes" name="notes" rows="2" maxlength="500"></textarea></div>
</div></div>
<div class="card"><div class="chead"><h3>Items</h3><span style="flex:1"></span><button type="button" class="btn small" id="addRow">Add item</button></div><div class="cbody">
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Service</th><th scope="col" class="num">Rate</th><th scope="col" class="num">Qty</th><th scope="col" class="num">Line total</th><th scope="col"></th></tr></thead><tbody id="items"></tbody></table></div>
<div class="estimate" id="billPrev" style="margin-top:.8rem">Estimate: <strong>0.00</strong> â€” final totals are calculated securely on the server.</div>
<div class="foot-note">Duplicate services are merged automatically. Quantity must be a whole number from 1 to 1000.</div>
<div style="margin-top:1rem;text-align:right"><button type="submit" class="btn primary" id="placeBtn">Place order</button></div>
</div></div>
</div>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

