document.addEventListener("DOMContentLoaded", async () => {
  const custSel = document.getElementById("cust");
  const staffSel = document.getElementById("staff");
  const itemsBox = document.getElementById("items");
  const del = document.getElementById("delivery");
  del.min = new Date().toISOString().slice(0, 10);
  let key = "ord_" + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
  let services = [];
  try {
    const [c, s, u] = await Promise.all([
      LMS.api.get("../api/customers.php", { action: "list", limit: 100, page: 1, active: "1" }),
      LMS.api.get("../api/services.php", { action: "active" }),
      LMS.api.get("../api/users.php", { action: "staff_options" }).catch(() => ({ rows: [] }))
    ]);
    custSel.innerHTML = '<option value="">Select customer</option>' + c.rows.map((x) => "<option value='" + x.id + "'>" + LMS.ui.esc(x.customer_name) + " (" + LMS.ui.esc(x.mobile) + ")</option>").join("");
    services = s.rows || [];
    staffSel.innerHTML = '<option value="">Unassigned</option>' + (u.rows || []).map((x) => "<option value='" + x.id + "'>" + LMS.ui.esc(x.full_name) + "</option>").join("");
  } catch (e) {
    document.getElementById("alertBox").innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load form data.") + "</div>";
  }
  function row() {
    const tr = document.createElement("tr");
    const opts = services.map((s) => "<option value='" + s.id + "' data-price='" + s.price + "'>" + LMS.ui.esc(s.service_name) + " â€” " + LMS.ui.amt(s.price) + "</option>").join("");
    tr.innerHTML = "<td><select class='select svc' required><option value=''>Select</option>" + opts + "</select></td><td class='num rate'>0.00</td><td><input class='input qty' type='number' min='1' max='1000' value='1' required style='width:90px'></td><td class='num line'>0.00</td><td><button type='button' class='iconbtn rm' aria-label='Remove item'>Ã—</button></td>";
    tr.querySelector(".svc").onchange = (e) => {
      const p = e.target.selectedOptions[0]?.dataset.price || 0;
      tr.querySelector(".rate").textContent = LMS.ui.amt(p);
      calc(tr);
    };
    tr.querySelector(".qty").oninput = () => calc(tr);
    tr.querySelector(".rm").onclick = () => { if (itemsBox.rows.length > 1) { tr.remove(); calc(tr); } };
    return tr;
  }
  function calc(tr) {
    const p = Number(tr.querySelector(".svc").selectedOptions[0]?.dataset.price || 0);
    const qn = Math.floor(Number(tr.querySelector(".qty").value || 0));
    tr.querySelector(".line").textContent = LMS.ui.amt(p * (qn > 0 ? qn : 0));
    let total = 0;
    itemsBox.querySelectorAll("tr").forEach((r) => {
      const rp = Number(r.querySelector(".svc").selectedOptions[0]?.dataset.price || 0);
      const rq = Math.floor(Number(r.querySelector(".qty").value || 0));
      if (rq > 0) total += rp * rq;
    });
    document.getElementById("billPrev").innerHTML = "Estimate: <strong>" + LMS.ui.amt(total) + "</strong> â€” final totals are calculated securely on the server.";
  }
  function addRow() { itemsBox.appendChild(row()); }
  document.getElementById("addRow").onclick = addRow;
  addRow();
  document.getElementById("orderForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("placeBtn");
    LMS.ui.setLoading(btn, true, "Placing...");
    try {
      const items = [...itemsBox.rows].map((tr) => ({
        service_id: Number(tr.querySelector(".svc").value || 0),
        quantity: Number(tr.querySelector(".qty").value || 0)
      }));
      const payload = {
        customer_id: Number(custSel.value || 0),
        pickup_date: document.getElementById("pickup").value,
        delivery_date: del.value,
        assigned_staff: Number(staffSel.value || 0),
        discount_type: document.getElementById("dtype").value,
        discount_value: Number(document.getElementById("dval").value || 0),
        notes: document.getElementById("notes").value,
        items,
        idempotency_key: key
      };
      const r = await LMS.api.postJSON("../api/orders.php?action=create", payload);
      LMS.ui.toast("Order placed: " + (r.invoice_no || ("#" + r.id)), "success");
      key = "ord_" + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
      window.location.href = "order_view.php?id=" + r.id;
    } catch (err) { LMS.ui.fail(err); }
    LMS.ui.setLoading(btn, false);
  });
});

