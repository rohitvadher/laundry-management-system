document.addEventListener("DOMContentLoaded", async () => {
  const id = new URLSearchParams(window.location.search).get("id");
  if (!id) { window.location.href = "customers.php"; return; }
  try {
    const c = await LMS.api.get("../api/customers.php", { action: "get", id });
    const h = await LMS.api.get("../api/customers.php", { action: "history", id });
    document.getElementById("cName").textContent = c.customer.customer_name;
    const s = h.summary || {};
    const cards = [
      ["Total orders", s.n || 0, ""],
      ["Total spent", LMS.ui.amt(s.spent || 0), "excl. cancelled"],
      ["Outstanding", LMS.ui.amt(s.due || 0), "unpaid balances"],
      ["Last order", s.last_order ? String(s.last_order).slice(0, 10) : "â€”", c.customer.mobile || ""]
    ];
    document.getElementById("statRow").innerHTML = cards.map((x) => "<div class='kpi-card'><div class='label'>" + LMS.ui.esc(x[0]) + "</div><div class='value'>" + LMS.ui.esc(String(x[1])) + "</div><div class='sub'>" + LMS.ui.esc(x[2]) + "</div></div>").join("");
    document.getElementById("detailBox").innerHTML = "<p><strong>Mobile:</strong> " + LMS.ui.esc(c.customer.mobile) + "</p><p><strong>Address:</strong> " + LMS.ui.esc(c.customer.address || "â€”") + "</p>";
    const tb = document.getElementById("histBody");
    tb.innerHTML = "";
    if (!h.orders.length) { tb.innerHTML = LMS.ui.emptyRow(7, "No orders for this customer."); return; }
    h.orders.forEach((o) => {
      const tr = document.createElement("tr");
      tr.innerHTML = "<td>" + LMS.ui.esc(o.invoice_no || ("#" + o.id)) + "</td><td>" + LMS.ui.esc(String(o.created_at).slice(0, 10)) + "</td><td class='num'>" + LMS.ui.amt(o.grand_total) + "</td><td class='num'>" + LMS.ui.amt(o.paid_amount) + "</td><td>" + LMS.ui.badge(o.payment_status) + "</td><td>" + LMS.ui.badge(o.status) + "</td><td><a class='btn small' href='order_view.php?id=" + o.id + "'>View</a></td>";
      tb.appendChild(tr);
    });
  } catch (e) {
    document.getElementById("alertBox").innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load profile.") + "</div>";
  }
});

