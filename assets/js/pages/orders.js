document.addEventListener("DOMContentLoaded", () => {
  let page = 1;
  const limit = 15;
  const g = (id) => document.getElementById(id).value.trim();
  document.getElementById("searchBtn").onclick = () => { page = 1; load(); };
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="8"><span class="spin"></span> Loading...</td></tr>';
    try {
      const d = await LMS.api.get("../api/orders.php", { action: "list", q: g("q"), status: g("fStatus"), payment_status: g("fPay"), from: g("fFrom"), to: g("fTo"), sort: g("fSort"), page, limit });
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(8, "No orders found.");
      d.rows.forEach((o) => {
        const tr = document.createElement("tr");
        tr.innerHTML = "<td>" + LMS.ui.esc(o.invoice_no || ("#" + o.id)) + "</td><td>" + LMS.ui.esc(o.customer_name) + "<br><small>" + LMS.ui.esc(o.mobile || "") + "</small></td><td>" + LMS.ui.esc(String(o.order_date || "").slice(0, 10)) + "</td><td>" + LMS.ui.esc(String(o.delivery_date || "â€”").slice(0, 10)) + "</td><td class='num'>" + LMS.ui.amt(o.grand_total) + "</td><td>" + LMS.ui.badge(o.payment_status) + "</td><td>" + LMS.ui.badge(o.status) + "</td><td><a class='btn small' href='order_view.php?id=" + o.id + "'>View</a></td>";
        tb.appendChild(tr);
      });
      LMS.ui.pager(document.getElementById("pager"), d.total, d.page, d.limit, (p) => { page = p; load(); });
    } catch (e) { tb.innerHTML = LMS.ui.emptyRow(8, e.message || "Failed to load."); }
  }
  load();
});

