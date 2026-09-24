<?php
declare(strict_types=1);
$pageTitle = 'Orders';
$pageScript = '../assets/js/pages/orders.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Work / Orders</div><h2>Orders</h2></div><span class="spacer"></span><a class="btn primary" href="order_create.php">Create order</a></div>
<div class="card"><div class="cbody">
<div class="filterbar">
<div class="field search"><label for="q">Search</label><input class="input" id="q" placeholder="Customer, mobile, invoice..."></div>
<div class="field"><label for="fStatus">Status</label><select class="select" id="fStatus"><option value="">All</option><option>Pending</option><option>Processing</option><option>Ready</option><option>Delivered</option><option>Cancelled</option></select></div>
<div class="field"><label for="fPay">Payment</label><select class="select" id="fPay"><option value="">All</option><option>Unpaid</option><option>Partial</option><option>Paid</option></select></div>
<div class="field"><label for="fFrom">From</label><input class="input" type="date" id="fFrom"></div>
<div class="field"><label for="fTo">To</label><input class="input" type="date" id="fTo"></div>
<div class="field"><label for="fSort">Sort</label><select class="select" id="fSort"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="highest">Highest total</option><option value="lowest">Lowest total</option><option value="delivery">Delivery date</option></select></div>
<div class="field"><label>&nbsp;</label><button class="btn primary" id="searchBtn">Filter</button></div>
</div>
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col">Date</th><th scope="col">Delivery</th><th scope="col" class="num">Total</th><th scope="col">Payment</th><th scope="col">Status</th><th scope="col"></th></tr></thead><tbody id="rows"><tr><td colspan="8"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
<div class="pager" id="pager"></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

