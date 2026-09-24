<?php
declare(strict_types=1);
$pageTitle = 'Help';
$pageScript = '../assets/js/pages/help.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">System / Help</div><h2>Help &amp; user guide</h2></div></div>
<div class="card"><div class="cbody">
<div class="field help-search"><label for="helpQ">Search help</label><input class="input" id="helpQ" placeholder="Type to filter topics, e.g. payment, GST, delivery..."></div>
<div id="helpList"></div>
<div class="empty" id="helpEmpty" style="display:none"><div class="big">â€”</div><div>No topics match your search.</div></div>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

