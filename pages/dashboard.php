<?php
declare(strict_types=1);
$pageTitle = 'Dashboard';
$pageScript = '../assets/js/pages/dashboard.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Work / Dashboard</div><h2>Dashboard</h2></div><span class="spacer"></span><a class="btn primary" href="order_create.php">New order</a></div>
<div id="alertBox"></div>
<div class="grid kpi" id="kpiRow">
<div class="kpi-card"><div class="label">Revenue (this month)</div><div class="value skeleton" id="kRevenue">&nbsp;</div><div class="sub" id="kOrdersSub"></div></div>
<div class="kpi-card"><div class="label">Outstanding</div><div class="value skeleton" id="kDue">&nbsp;</div><div class="sub">All-time unpaid balances</div></div>
<div class="kpi-card"><div class="label">Deliveries due today</div><div class="value skeleton" id="kToday">&nbsp;</div><div class="sub" id="kOverdueSub"></div></div>
<div class="kpi-card"><div class="label">Low stock items</div><div class="value skeleton" id="kStock">&nbsp;</div><div class="sub">Inventory alerts</div></div>
</div>
<div class="grid two" style="margin-top:1rem">
<div class="card"><div class="chead"><h3>Recent orders</h3><span class="spacer" style="flex:1"></span><a class="btn small" href="orders.php">View all</a></div><div class="cbody"><div class="tablewrap"><table class="data"><thead><tr><th scope="col">Invoice</th><th scope="col">Customer</th><th scope="col" class="num">Total</th><th scope="col">Status</th><th scope="col"></th></tr></thead><tbody id="recentBody"><tr><td colspan="5"><span class="spin"></span> Loading...</td></tr></tbody></table></div></div></div>
<div class="card"><div class="chead"><h3>Revenue â€” last 14 days</h3></div><div class="cbody"><div class="chart-wrap"><div class="chart" id="chartBox"></div></div><div class="foot-note" id="chartNote"></div></div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

