document.addEventListener("DOMContentLoaded", () => {
  let preset = "month";
  const go = () => load();
  document.querySelectorAll("#presetTabs button").forEach((b) => {
    b.onclick = () => {
      document.querySelectorAll("#presetTabs button").forEach((x) => x.classList.remove("active"));
      b.classList.add("active");
      preset = b.dataset.p;
      document.getElementById("fFrom").value = "";
      document.getElementById("fTo").value = "";
      load();
    };
  });
  document.getElementById("goBtn").onclick = go;
  async function load() {
    const params = { preset };
    if (document.getElementById("fFrom").value && document.getElementById("fTo").value) {
      params.from = document.getElementById("fFrom").value;
      params.to = document.getElementById("fTo").value;
      delete params.preset;
    }
    try {
      const d = await LMS.api.get("../api/reports.php", params);
      const k = d.kpi || {};
      const cards = [
        ["Revenue", LMS.ui.amt(k.revenue || 0), (k.orders || 0) + " orders"],
        ["Collected", LMS.ui.amt(k.collected || 0), "paid on orders in range"],
        ["Outstanding", LMS.ui.amt(k.outstanding || 0), "yet to collect"],
        ["Discounts", LMS.ui.amt(k.discounts || 0), "GST " + LMS.ui.amt(k.gst || 0)]
      ];
      document.getElementById("kpiRow").innerHTML = cards.map((x) => "<div class='kpi-card'><div class='label'>" + x[0] + "</div><div class='value'>" + x[1] + "</div><div class='sub'>" + x[2] + "</div></div>").join("");
      const chart = document.getElementById("chartBox");
      chart.innerHTML = "";
      const max = Math.max(1, ...(d.daily || []).map((x) => Number(x.amt)));
      (d.daily || []).forEach((x) => {
        const i = document.createElement("i");
        i.style.height = Math.max(4, Math.round((Number(x.amt) / max) * 130)) + "px";
        i.title = x.d + ": " + Number(x.amt).toFixed(2);
        chart.appendChild(i);
      });
      document.getElementById("statusBox").innerHTML = (d.by_status || []).map((x) => "<p>" + LMS.ui.badge(x.status) + " " + x.n + " orders â€” " + LMS.ui.amt(x.amt) + "</p>").join("") + "<p class='foot-note'>Cancelled: " + (d.cancelled ? d.cancelled.n : 0) + " orders excluded from revenue.</p>";
      document.getElementById("svcBox").innerHTML = (d.services || []).length ? (d.services || []).map((x) => "<p><strong>" + LMS.ui.esc(x.service_name) + "</strong> â€” " + x.qty + " units â€” " + LMS.ui.amt(x.amt) + "</p>").join("") : "No service data.";
      document.getElementById("payBox").innerHTML = ((d.payments || []).length ? (d.payments || []).map((x) => "<p><strong>" + LMS.ui.esc(x.method) + "</strong> â€” " + x.n + " â€” " + LMS.ui.amt(x.amt) + "</p>").join("") : "No payments received in range.") + "<p class='foot-note'>By payment date; revenue KPIs above are by order date.</p>";
    } catch (e) {
      document.getElementById("alertBox").innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load reports.") + "</div>";
    }
  }
  load();
});

