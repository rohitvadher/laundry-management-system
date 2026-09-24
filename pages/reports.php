<?php
declare(strict_types=1);
$pageTitle = 'Reports';
$pageScript = '../assets/js/pages/reports.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Insights / Reports</div><h2>Reports</h2></div><span class="spacer"></span><button class="btn" onclick="window.print()">Print</button></div>
<div class="card no-print"><div class="cbody"><div class="filterbar">
<div class="field"><label>Preset</label><div class="tabs" id="presetTabs" style="margin:0"><button data-p="today">Today</button><button data-p="week">Week</button><button data-p="month" class="active">Month</button><button data-p="year">Year</button></div></div>
<div class="field"><label for="fFrom">From</label><input class="input" type="date" id="fFrom"></div>
<div class="field"><label for="fTo">To</label><input class="input" type="date" id="fTo"></div>
<div class="field"><label>&nbsp;</label><button class="btn primary" id="goBtn">Apply</button></div>
</div></div></div>
<div id="alertBox"></div>
<div class="grid kpi" id="kpiRow" style="margin-top:1rem"></div>
<div class="grid two" style="margin-top:1rem">
<div class="card"><div class="chead"><h3>Daily revenue</h3></div><div class="cbody"><div class="chart-wrap"><div class="chart" id="chartBox"></div></div></div></div>
<div class="card"><div class="chead"><h3>Orders by status</h3></div><div class="cbody" id="statusBox"></div></div>
</div>
<div class="grid two" style="margin-top:1rem">
<div class="card"><div class="chead"><h3>Top services</h3></div><div class="cbody" id="svcBox"></div></div>
<div class="card"><div class="chead"><h3>Payments by method</h3></div><div class="cbody" id="payBox"></div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

