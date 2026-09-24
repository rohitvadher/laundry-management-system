<?php
declare(strict_types=1);
$pageTitle = 'Customer profile';
$pageScript = '../assets/js/pages/customer_view.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Directory / Customers / Profile</div><h2 id="cName">Customer</h2></div><span class="spacer"></span><a class="btn" href="customers.php">Back</a></div>
<div id="alertBox"></div>
<div class="profile-grid" id="statRow"></div>
<div class="card" style="margin-top:1rem"><div class="chead"><h3>Details</h3></div><div class="cbody" id="detailBox"><span class="spin"></span> Loading...</div></div>
<div class="card" style="margin-top:1rem"><div class="chead"><h3>Order history</h3></div><div class="cbody"><div class="tablewrap"><table class="data"><thead><tr><th scope="col">Invoice</th><th scope="col">Date</th><th scope="col" class="num">Total</th><th scope="col" class="num">Paid</th><th scope="col">Payment</th><th scope="col">Status</th><th scope="col"></th></tr></thead><tbody id="histBody"><tr><td colspan="7"><span class="spin"></span> Loading...</td></tr></tbody></table></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

