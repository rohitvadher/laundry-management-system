document.addEventListener("DOMContentLoaded", async () => {
  const id = new URLSearchParams(window.location.search).get("id");
  if (!id) { window.location.href = "orders.php"; return; }
  const NEXT = { Pending: ["Processing", "Cancelled"], Processing: ["Ready", "Cancelled"], Ready: ["Delivered", "Cancelled"], Delivered: [], Cancelled: [] };
  try {
    const d = await LMS.api.get("../api/orders.php", { action: "get", id });
    const o = d.order;
    const terminal = o.status === "Delivered" || o.status === "Cancelled";
    const settled = o.payment_status === "Paid" || o.status === "Cancelled";
    document.getElementById("invTitle").textContent = "Invoice " + (o.invoice_no || ("#" + o.id));
    renderInvoice(o);
    const hero = document.getElementById("statusHero");
    hero.innerHTML = LMS.ui.badge(o.status) + " " + LMS.ui.badge(o.payment_status);
    const sel = document.getElementById("stSel");
    const allowed = NEXT[o.status] || [];
    sel.innerHTML = ["<option selected>" + LMS.ui.esc(o.status) + " (current)</option>"]
      .concat(allowed.map((s) => "<option>" + LMS.ui.esc(s) + "</option>")).join("");
    document.getElementById("transNote").textContent = allowed.length ? "Allowed next: " + allowed.join(", ") : "No further transitions allowed.";
    if (terminal) {
      sel.disabled = true;
      document.getElementById("stBtn").disabled = true;
      document.getElementById("transNote").textContent = "This order is closed. Status cannot be changed.";
    }
    if (settled) {
      document.getElementById("payCard").style.display = "none";
      document.getElementById("payNote").textContent = o.status === "Cancelled" ? "Cancelled orders accept no payments." : "This order is fully paid.";
      document.getElementById("payNote").style.display = "block";
    }
    document.getElementById("pDate").value = new Date().toISOString().slice(0, 10);
    document.getElementById("stBtn").onclick = async () => {
      const to = sel.value;
      const ok = await LMS.ui.confirmDlg("Update status", "Move order from " + o.status + " to " + to + "?");
      if (!ok) return;
      const btn = document.getElementById("stBtn");
      LMS.ui.setLoading(btn, true, "Updating...");
      try {
        await LMS.api.post("../api/orders.php?action=status", { id: o.id, status: to });
        LMS.ui.toast("Status updated.", "success");
        window.location.reload();
      } catch (e) { LMS.ui.fail(e); }
      LMS.ui.setLoading(btn, false);
    };
    document.getElementById("payForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const btn = document.getElementById("payBtn");
      LMS.ui.setLoading(btn, true);
      try {
        const fd = Object.fromEntries(new FormData(e.target).entries());
        fd.order_id = o.id;
        fd.idempotency_key = "pay_" + o.id + "_" + Date.now().toString(36);
        await LMS.api.post("../api/payments.php?action=add", fd);
        LMS.ui.toast("Payment recorded.", "success");
        window.location.reload();
      } catch (err) { LMS.ui.fail(err, e.target); }
      LMS.ui.setLoading(btn, false);
    });
    document.getElementById("lookupBtn").onclick = () => {
      const v = document.getElementById("lookup").value.trim();
      if (!v) return;
      window.location.href = "orders.php?q=" + encodeURIComponent(v);
    };
  } catch (e) {
    document.getElementById("alertBox").innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Order not found.") + '</div><a class="btn" href="orders.php">Back to orders</a>';
    document.getElementById("invBox").innerHTML = '<div class="empty"><div class="big">â€”</div><div>Order could not be loaded.</div></div>';
  }
  async function renderInvoice(o) {
    let s = {};
    try { s = (await LMS.api.get("../api/settings.php", { action: "get" })).settings || {}; } catch (e) {}
    const m = (v) => LMS.ui.amt(v);
    const rows = (o.items || []).map((i) => "<tr><td>" + LMS.ui.esc(i.service_name) + "</td><td class='num'>" + i.quantity + "</td><td class='num'>" + m(i.price) + "</td><td class='num'>" + m(i.line_total) + "</td></tr>").join("");
    const pays = (o.payments || []).map((p) => "<tr><td>" + LMS.ui.esc(String(p.paid_at).slice(0, 10)) + "</td><td>" + LMS.ui.esc(p.method) + "</td><td class='num'>" + m(p.amount) + "</td></tr>").join("") || "<tr><td colspan='3'>No payments yet.</td></tr>";
    const bal = (Number(o.grand_total) - Number(o.paid_amount)).toFixed(2);
    document.getElementById("invBox").innerHTML =
      "<div class='inv-head'><div class='inv-brand'><img src='../icons/web/icon-192.png' alt='logo'><div><h3>" + LMS.ui.esc(s.business_name || "Laundry MS") + "</h3><div class='foot-note'>" + LMS.ui.esc(s.business_address || "") + "<br>" + LMS.ui.esc(s.business_phone || "") + (s.gstin ? "<br>GSTIN: " + LMS.ui.esc(s.gstin) : "") + "</div></div></div>" +
      "<div class='inv-meta'><h3>INVOICE</h3><div>" + LMS.ui.esc(o.invoice_no || ("#" + o.id)) + "</div><div class='foot-note'>Order: " + LMS.ui.esc(String(o.order_date || "").slice(0, 10)) + "<br>Delivery: " + LMS.ui.esc(String(o.delivery_date || "â€”").slice(0, 10)) + "<br>Status: " + LMS.ui.esc(o.status) + "</div></div></div>" +
      "<div class='inv-box'><div><strong>Bill to</strong><br>" + LMS.ui.esc(o.customer_name) + "<br>" + LMS.ui.esc(o.address || "") + "<br>" + LMS.ui.esc(o.mobile || "") + "</div>" +
      "<div><strong>Payment</strong><br>" + LMS.ui.badge(o.payment_status) + "<br>Paid: " + m(o.paid_amount) + "<br>Balance: " + m(bal) + "</div></div>" +
      "<div class='tablewrap'><table class='data'><thead><tr><th scope='col'>Service</th><th scope='col' class='num'>Qty</th><th scope='col' class='num'>Rate</th><th scope='col' class='num'>Amount</th></tr></thead><tbody>" + rows + "</tbody></table></div>" +
      "<div class='tablewrap inv-totals' style='margin-top:1rem'><table class='data'><tbody>" +
      "<tr><td>Subtotal</td><td class='num'>" + m(o.subtotal) + "</td></tr>" +
      (o.discount_type !== "none" ? "<tr><td>Discount (" + LMS.ui.esc(o.discount_type) + " " + LMS.ui.esc(String(o.discount_value)) + ")</td><td class='num'>âˆ’" + m(o.discount_amount) + "</td></tr>" : "") +
      (Number(o.gst_amount) > 0 ? "<tr><td>Taxable</td><td class='num'>" + m(o.taxable_amount) + "</td></tr>" +
       "<tr><td>GST " + LMS.ui.money(o.gst_rate) + "% (CGST " + m(o.cgst_amount) + " + SGST " + m(o.sgst_amount) + ")</td><td class='num'>" + m(o.gst_amount) + "</td></tr>" : "") +
      "<tr><td><strong>Grand total</strong></td><td class='num'><strong>" + m(o.grand_total) + "</strong></td></tr>" +
      "<tr><td>Paid</td><td class='num'>" + m(o.paid_amount) + "</td></tr>" +
      "<tr><td><strong>Balance</strong></td><td class='num'><strong>" + m(bal) + "</strong></td></tr>" +
      "</tbody></table></div>" +
      "<h3 style='margin-top:1rem'>Payments</h3><div class='tablewrap'><table class='data'><thead><tr><th scope='col'>Date</th><th scope='col'>Method</th><th scope='col' class='num'>Amount</th></tr></thead><tbody>" + pays + "</tbody></table></div>" +
      "<div class='foot-note'>" + LMS.ui.esc(s.invoice_terms || "") + "<br>" + LMS.ui.esc(s.invoice_footer || "Thank you for your business!") + "</div>";
  }
});

