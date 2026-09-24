<?php
declare(strict_types=1);
$pageTitle = 'Services';
$pageScript = '../assets/js/pages/services.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Directory / Services</div><h2>Services</h2></div><span class="spacer"></span><button class="btn primary" id="addBtn">Add service</button></div>
<div class="card"><div class="cbody">
<div class="filterbar">
<div class="field search"><label for="q">Search</label><input class="input" id="q" placeholder="Service name..."></div>
<div class="field"><label for="active">Status</label><select class="select" id="active"><option value="1">Active</option><option value="0">Inactive</option><option value="">All</option></select></div>
<div class="field"><label>&nbsp;</label><button class="btn primary" id="searchBtn">Search</button></div>
</div>
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Service</th><th scope="col">Description</th><th scope="col" class="num">Price</th><th scope="col" class="num">GST %</th><th scope="col"></th></tr></thead><tbody id="rows"><tr><td colspan="5"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
<div class="pager" id="pager"></div>
</div></div>
<div class="modal-back" id="svcModal"><div class="modal" role="dialog" aria-modal="true"><div class="mhead"><h3 id="mTitle">Add service</h3></div><form id="svcForm"><div class="mbody">
<input type="hidden" name="id" id="fId">
<div class="field"><label for="fName">Service name</label><input class="input" name="service_name" id="fName" maxlength="100" required></div>
<div class="field"><label for="fDesc">Description</label><textarea class="textarea" name="description" id="fDesc" rows="3" maxlength="500"></textarea></div>
<div class="frow"><div class="field"><label for="fPrice">Price</label><input class="input" name="price" id="fPrice" type="number" step="0.01" min="0.01" required></div>
<div class="field"><label for="fGst">GST % (optional)</label><input class="input" name="gst_rate" id="fGst" type="number" step="0.01" min="0" max="100"></div></div>
</div><div class="mfoot"><button type="button" class="btn" id="mClose">Close</button><button type="submit" class="btn primary" id="mSave">Save</button></div></form></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

