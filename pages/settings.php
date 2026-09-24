<?php
declare(strict_types=1);
$pageTitle = 'Business settings';
$pageScript = '../assets/js/pages/settings.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">System / Settings</div><h2>Business settings</h2></div></div>
<div id="alertBox"></div>
<form id="setForm"><div class="grid two">
<div class="card"><div class="chead"><h3>Business</h3></div><div class="cbody">
<div class="field"><label for="sName">Business name</label><input class="input" id="sName" name="business_name" maxlength="120"></div>
<div class="field"><label for="sAddr">Address</label><textarea class="textarea" id="sAddr" name="business_address" rows="2" maxlength="500"></textarea></div>
<div class="frow"><div class="field"><label for="sPhone">Phone</label><input class="input" id="sPhone" name="business_phone" maxlength="40"></div>
<div class="field"><label for="sEmail">Email</label><input class="input" id="sEmail" name="business_email" maxlength="120"></div></div>
<div class="field"><label for="sGstin">GSTIN</label><input class="input" id="sGstin" name="gstin" maxlength="20"></div>
</div></div>
<div class="card"><div class="chead"><h3>Billing</h3></div><div class="cbody">
<div class="frow"><div class="field"><label for="sGstEn">GST enabled</label><select class="select" id="sGstEn" name="gst_enabled"><option value="1">Yes</option><option value="0">No</option></select></div>
<div class="field"><label for="sGstRate">Default GST %</label><input class="input" type="number" step="0.01" min="0" max="100" id="sGstRate" name="gst_rate"></div></div>
<div class="field"><label for="sGstInc">Tax mode</label><select class="select" id="sGstInc" name="gst_inclusive"><option value="0">Exclusive (tax added on top)</option><option value="1">Inclusive (tax included in price)</option></select></div>
<div class="frow"><div class="field"><label for="sPrefix">Invoice prefix</label><input class="input" id="sPrefix" name="invoice_prefix" maxlength="10"></div>
<div class="field"><label for="sCur">Currency</label><select class="select" id="sCur" name="currency"><option>INR</option><option>USD</option><option>EUR</option></select></div></div>
<div class="field"><label for="sFoot">Invoice footer</label><input class="input" id="sFoot" name="invoice_footer" maxlength="255"></div>
<div class="field"><label for="sTerms">Terms &amp; conditions</label><textarea class="textarea" id="sTerms" name="invoice_terms" rows="3" maxlength="1000"></textarea></div>
<div style="text-align:right"><button class="btn primary" id="saveBtn">Save settings</button></div>
</div></div>
</div></form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

