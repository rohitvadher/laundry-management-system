<?php
declare(strict_types=1);
$pageTitle = 'Delivery';
$pageScript = '../assets/js/pages/delivery.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Work / Delivery</div><h2>Delivery</h2></div></div>
<div class="grid kpi" id="cntRow" style="margin-bottom:1rem"></div>
<div class="tabs" id="tabs">
<button data-f="upcoming" class="active">Upcoming</button>
<button data-f="today">Today</button>
<button data-f="overdue">Overdue</button>
<button data-f="delivered">Delivered</button>
</div>
<div class="card"><div class="cbody">
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col">Delivery</th><th scope="col">Status</th><th scope="col">Staff</th><th scope="col" class="num">Total</th><th scope="col">Payment</th><th scope="col"></th></tr></thead><tbody id="rows"><tr><td colspan="8"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
<div class="pager" id="pager"></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

