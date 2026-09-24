document.addEventListener("DOMContentLoaded", async () => {
  const box = document.getElementById("alertBox");
  try {
    const d = await LMS.api.get("../api/reports.php", { action: "dashboard" });
    const m = (v) => LMS.ui.amt(v);
    const k = d.kpi || {};
    const del = d.delivery || {};
    set("kRevenue", m(k.revenue || 0));
    document.getElementById("kOrdersSub").textContent = (k.orders || 0) + " orders this month";
    set("kDue", m(k.outstanding || 0));
    set("kToday", String(Number(del.today || 0)));
    document.getElementById("kOverdueSub").textContent = Number(del.overdue || 0) + " overdue";
    set("kStock", String(Number(d.low_stock || 0)));
    const chart = document.getElementById("chartBox");
    const daily = (d.daily || []).slice(-14);
    chart.innerHTML = "";
    const max = Math.max(1, ...daily.map((x) => Number(x.amt)));
    daily.forEach((x) => {
      const i = document.createElement("i");
      i.style.height = Math.max(4, Math.round((Number(x.amt) / max) * 130)) + "px";
      i.title = x.d + ": " + m(x.amt);
      chart.appendChild(i);
    });
    document.getElementById("chartNote").textContent = daily.length ? "Cancelled orders excluded." : "No revenue in range.";
    const tb = document.getElementById("recentBody");
    tb.innerHTML = "";
    if (!(d.recent || []).length) {
      tb.innerHTML = LMS.ui.emptyRow(5, "No orders yet. Create your first order to get started.");
      return;
    }
    d.recent.forEach((o) => {
      const tr = document.createElement("tr");
      tr.innerHTML = "<td>" + LMS.ui.esc(o.invoice_no || ("#" + o.id)) + "</td><td>" + LMS.ui.esc(o.customer_name) + "</td><td class='num'>" + LMS.ui.amt(o.grand_total) + "</td><td>" + LMS.ui.badge(o.status) + "</td><td><a class='btn small' href='order_view.php?id=" + o.id + "'>View</a></td>";
      tb.appendChild(tr);
    });
  } catch (e) {
    box.innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load dashboard.") + "</div>";
  }
  function set(id, v) {
    const el = document.getElementById(id);
    el.classList.remove("skeleton");
    el.textContent = v;
  }
});

