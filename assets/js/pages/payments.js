document.addEventListener("DOMContentLoaded", () => {
  let page = 1;
  const limit = 15;
  document.getElementById("searchBtn").onclick = () => { page = 1; load(); };
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="6"><span class="spin"></span> Loading...</td></tr>';
    try {
      const oid = Number(document.getElementById("q").value || 0);
      const d = await LMS.api.get("../api/payments.php", { action: "list", page, limit, order_id: oid > 0 ? oid : "", method: document.getElementById("fMethod").value, from: document.getElementById("fFrom").value, to: document.getElementById("fTo").value });
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(6, "No payments found.");
      d.rows.forEach((p) => {
        const tr = document.createElement("tr");
        tr.innerHTML = "<td>" + LMS.ui.esc(String(p.paid_at).slice(0, 10)) + "</td><td>" + LMS.ui.esc(p.invoice_no || ("#" + p.order_id)) + "</td><td>" + LMS.ui.esc(p.customer_name) + "</td><td>" + LMS.ui.esc(p.method) + "</td><td>" + LMS.ui.esc(p.reference_no || "â€”") + "</td><td class='num'>" + LMS.ui.amt(p.amount) + "</td>";
        tb.appendChild(tr);
      });
      LMS.ui.pager(document.getElementById("pager"), d.total, d.page, d.limit, (p) => { page = p; load(); });
    } catch (e) { tb.innerHTML = LMS.ui.emptyRow(6, e.message || "Failed to load."); }
  }
  load();
});

