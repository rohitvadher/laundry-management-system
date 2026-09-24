document.addEventListener("DOMContentLoaded", async () => {
  const map = { business_name: "sName", business_address: "sAddr", business_phone: "sPhone", business_email: "sEmail", gstin: "sGstin", gst_enabled: "sGstEn", gst_rate: "sGstRate", gst_inclusive: "sGstInc", invoice_prefix: "sPrefix", currency: "sCur", invoice_footer: "sFoot", invoice_terms: "sTerms" };
  try {
    const d = await LMS.api.get("../api/settings.php", { action: "get" });
    Object.keys(map).forEach((k) => {
      if (d.settings && d.settings[k] !== undefined) document.getElementById(map[k]).value = d.settings[k];
    });
  } catch (e) {
    document.getElementById("alertBox").innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load settings.") + "</div>";
  }
  document.getElementById("setForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("saveBtn");
    LMS.ui.setLoading(btn, true);
    try {
      await LMS.api.post("../api/settings.php?action=save", Object.fromEntries(new FormData(e.target).entries()));
      LMS.ui.toast("Settings saved.", "success");
    } catch (err) { LMS.ui.fail(err, e.target); }
    LMS.ui.setLoading(btn, false);
  });
});

