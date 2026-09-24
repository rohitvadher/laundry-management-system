<?php
declare(strict_types=1);
$pageTitle = 'Customers';
$pageScript = '../assets/js/pages/customers.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Directory / Customers</div><h2>Customers</h2></div><span class="spacer"></span><button class="btn primary" id="addBtn">Add customer</button></div>
<div class="card"><div class="cbody">
<div class="filterbar">
<div class="field search"><label for="q">Search</label><input class="input" id="q" placeholder="Name or mobile..."></div>
<div class="field"><label for="active">Status</label><select class="select" id="active"><option value="1">Active</option><option value="0">Archived</option><option value="">All</option></select></div>
<div class="field"><label>&nbsp;</label><button class="btn primary" id="searchBtn">Search</button></div>
</div>
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Name</th><th scope="col">Mobile</th><th scope="col">Orders</th><th scope="col" class="num">Spent</th><th scope="col" class="num">Due</th><th scope="col"></th></tr></thead><tbody id="rows"><tr><td colspan="6"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
<div class="pager" id="pager"></div>
</div></div>
<div class="modal-back" id="custModal"><div class="modal" role="dialog" aria-modal="true"><div class="mhead"><h3 id="mTitle">Add customer</h3></div><form id="custForm"><div class="mbody">
<input type="hidden" name="id" id="fId">
<div class="field"><label for="fName">Customer name</label><input class="input" name="customer_name" id="fName" maxlength="100" required></div>
<div class="field"><label for="fMobile">Mobile number</label><input class="input" name="mobile" id="fMobile" maxlength="20" required></div>
<div class="field"><label for="fAddr">Address</label><textarea class="textarea" name="address" id="fAddr" rows="3" maxlength="500"></textarea></div>
</div><div class="mfoot"><button type="button" class="btn" id="mClose">Close</button><button type="submit" class="btn primary" id="mSave">Save</button></div></form></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

