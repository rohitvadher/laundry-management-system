<?php
declare(strict_types=1);
$pageTitle = 'Inventory';
$pageScript = '../assets/js/pages/inventory.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Directory / Inventory</div><h2>Inventory</h2></div><span class="spacer"></span><button class="btn primary" id="addBtn">Add item</button></div>
<div class="card"><div class="cbody">
<div class="filterbar"><div class="field search"><label for="q">Search</label><input class="input" id="q" placeholder="Item name..."></div>
<div class="field"><label>&nbsp;</label><button class="btn primary" id="searchBtn">Search</button></div></div>
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Item</th><th scope="col">Unit</th><th scope="col" class="num">Stock</th><th scope="col" class="num">Low at</th><th scope="col">Status</th><th scope="col"></th></tr></thead><tbody id="rows"><tr><td colspan="6"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
</div></div>
<div class="modal-back" id="itemModal"><div class="modal" role="dialog" aria-modal="true"><div class="mhead"><h3 id="mTitle">Add item</h3></div><form id="itemForm"><div class="mbody">
<input type="hidden" name="id" id="fId">
<div class="field"><label for="fName">Item name</label><input class="input" name="item_name" id="fName" maxlength="120" required></div>
<div class="frow"><div class="field"><label for="fUnit">Unit</label><input class="input" name="unit" id="fUnit" maxlength="30" value="pcs" required></div>
<div class="field"><label for="fLow">Low-stock threshold</label><input class="input" name="low_threshold" id="fLow" type="number" step="0.01" min="0"></div></div>
</div><div class="mfoot"><button type="button" class="btn" id="mClose">Close</button><button type="submit" class="btn primary" id="mSave">Save</button></div></form></div></div>
<div class="modal-back" id="moveModal"><div class="modal" role="dialog" aria-modal="true"><div class="mhead"><h3>Stock movement</h3></div><form id="moveForm"><div class="mbody">
<input type="hidden" name="item_id" id="vId">
<div class="frow"><div class="field"><label for="vType">Type</label><select class="select" name="move_type" id="vType"><option value="purchase">Purchase / add</option><option value="usage">Usage / reduce</option><option value="adjust">Adjust to value</option></select></div>
<div class="field"><label for="vQty">Quantity</label><input class="input" name="quantity" id="vQty" type="number" step="0.01" min="0.01" required></div></div>
<div class="field"><label for="vNote">Note</label><input class="input" name="note" id="vNote" maxlength="255"></div>
</div><div class="mfoot"><button type="button" class="btn" id="vClose">Close</button><button type="submit" class="btn primary" id="vSave">Save</button></div></form></div></div>
<div class="modal-back" id="histModal"><div class="modal" role="dialog" aria-modal="true"><div class="mhead"><h3 id="histTitle">Movements</h3></div><div class="mbody" id="histBody"></div><div class="mfoot"><button type="button" class="btn" onclick="LMS.ui.closeModal('histModal')">Close</button></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

