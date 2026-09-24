document.addEventListener("DOMContentLoaded", () => {
  let page = 1;
  let filter = "upcoming";
  const limit = 15;
  document.querySelectorAll("#tabs button").forEach((b) => {
    b.onclick = () => {
      document.querySelectorAll("#tabs button").forEach((x) => x.classList.remove("active"));
      b.classList.add("active");
      filter = b.dataset.f;
      page = 1;
      load();
    };
  });
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="8"><span class="spin"></span> Loading...</td></tr>';
    try {
      const d = await LMS.api.get("../api/delivery.php", { action: "list", filter, page, limit });
      document.getElementById("cntRow").innerHTML = ["upcoming", "today", "overdue"].map((k) => "<div class='kpi-card'><div class='label'>" + k + "</div><div class='value'>" + (d.counts ? d.counts[k] : 0) + "</div></div>").join("");
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(8, "Nothing in this view.");
      const today = d.today;
      d.rows.forEach((o) => {
        const late = o.delivery_date < today ? " " + LMS.ui.badge("overdue") : "";
        const tr = document.createElement("tr");
        tr.innerHTML = "<td>" + LMS.ui.esc(o.invoice_no || ("#" + o.id)) + "</td><td>" + LMS.ui.esc(o.customer_name) + "</td><td>" + LMS.ui.esc(String(o.delivery_date).slice(0, 10)) + late + "</td><td>" + LMS.ui.badge(o.status) + "</td><td>" + LMS.ui.esc(o.staff_name || "â€”") + "</td><td class='num'>" + LMS.ui.amt(o.grand_total) + "</td><td>" + LMS.ui.badge(o.payment_status) + "</td><td><a class='btn small' href='order_view.php?id=" + o.id + "'>View</a></td>";
        tb.appendChild(tr);
      });
      LMS.ui.pager(document.getElementById("pager"), d.total, d.page, d.limit, (p) => { page = p; load(); });
    } catch (e) { tb.innerHTML = LMS.ui.emptyRow(8, e.message || "Failed to load."); }
  }
  load();
});

