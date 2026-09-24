<?php
declare(strict_types=1);
$pageTitle = 'Notifications';
$pageScript = '../assets/js/pages/notifications.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">Insights / Notifications</div><h2>Notifications</h2></div><span class="spacer"></span><button class="btn" id="readAll">Mark all read</button></div>
<div class="card"><div class="cbody" id="list"><span class="spin"></span> Loading...</div><div class="pager" id="pager"></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

