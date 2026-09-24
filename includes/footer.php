</div>
</div>
</div>
<div class="modal-back" id="confirmBack">
<div class="modal" role="dialog" aria-modal="true">
<div class="mhead"><h3 id="confirmTitle">Confirm</h3></div>
<div class="mbody"><p id="confirmBody"></p></div>
<div class="mfoot"><button class="btn" id="confirmCancel">Cancel</button><button class="btn danger" id="confirmOk">Confirm</button></div>
</div>
</div>
<div id="toasts" aria-live="polite"></div>
<script>
window.LMS = window.LMS || {};
LMS.csrf = document.querySelector('meta[name="csrf-token"]')?.content || "";
LMS.base = "../api/";
<?php
$__sym = "\xE2\x82\xB9";
try {
    $__s = lms_settings(lms_db());
    $__cur = strtoupper((string)($__s['currency'] ?? 'INR'));
    $__sym = $__cur === 'INR' ? "\xE2\x82\xB9" : $__cur . ' ';
} catch (Throwable $e) {
}
?>
LMS.sym = <?php echo json_encode($__sym, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="../assets/js/core/api.js"></script>
<script src="../assets/js/core/ui.js"></script>
<script>
(function () {
  const btn = document.getElementById("logoutBtn");
  if (btn) btn.onclick = async () => {
    try { await LMS.api.post("../api/auth.php?action=logout", {}); } catch (e) {}
    window.location.href = "../index.php";
  };
  async function bell() {
    try {
      const d = await LMS.api.get("../api/notifications.php", { action: "list", limit: 1, page: 1 });
      const dot = document.getElementById("bellDot");
      if (dot && d && d.unread > 0) { dot.style.display = "flex"; dot.textContent = d.unread > 99 ? "99+" : d.unread; }
    } catch (e) {}
  }
  bell();
  setInterval(bell, 60000);
})();
</script>
<?php if (!empty($pageScript)): ?>
<script src="<?php echo lms_esc($pageScript); ?>"></script>
<?php endif; ?>
</body>
</html>

