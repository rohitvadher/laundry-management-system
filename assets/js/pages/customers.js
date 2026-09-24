document.addEventListener("DOMContentLoaded", () => {
  let page = 1;
  const limit = 15;
  const q = document.getElementById("q");
  const active = document.getElementById("active");
  document.getElementById("searchBtn").onclick = () => { page = 1; load(); };
  q.addEventListener("keydown", (e) => { if (e.key === "Enter") { e.preventDefault(); page = 1; load(); } });
  document.getElementById("addBtn").onclick = () => {
    document.getElementById("mTitle").textContent = "Add customer";
    document.getElementById("custForm").reset();
    document.getElementById("fId").value = "";
    LMS.ui.openModal("custModal");
  };
  document.getElementById("mClose").onclick = () => LMS.ui.closeModal("custModal");
  document.getElementById("custForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById("mSave");
    LMS.ui.setLoading(btn, true);
    try {
      const fd = new FormData(form);
      await LMS.api.post("../api/customers.php?action=save", Object.fromEntries(fd.entries()));
      LMS.ui.toast("Customer saved.", "success");
      LMS.ui.closeModal("custModal");
      load();
    } catch (err) { LMS.ui.fail(err, form); }
    LMS.ui.setLoading(btn, false);
  });
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="6"><span class="spin"></span> Loading...</td></tr>';
    try {
      const d = await LMS.api.get("../api/customers.php", { action: "list", q: q.value.trim(), active: active.value, page, limit });
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(6, "No customers found.");
      d.rows.forEach((c) => {
        const tr = document.createElement("tr");
        const archived = Number(c.is_active) === 0;
        tr.innerHTML = "<td><a href='customer_view.php?id=" + c.id + "'>" + LMS.ui.esc(c.customer_name) + "</a>" + (archived ? " " + LMS.ui.badge("archived") : "") + "</td><td>" + LMS.ui.esc(c.mobile) + "</td><td>" + (c.order_count || 0) + "</td><td class='num'>" + LMS.ui.amt(c.total_spent) + "</td><td class='num'>" + LMS.ui.amt(c.outstanding) + "</td>";
        const td = document.createElement("td");
        const eb = document.createElement("button");
        eb.className = "iconbtn";
        eb.textContent = "Edit";
        eb.style.width = "auto";
        eb.onclick = () => {
          document.getElementById("mTitle").textContent = "Edit customer";
          document.getElementById("fId").value = c.id;
          document.getElementById("fName").value = c.customer_name;
          document.getElementById("fMobile").value = c.mobile;
          document.getElementById("fAddr").value = c.address || "";
          LMS.ui.openModal("custModal");
        };
        td.appendChild(eb);
        if (archived) {
          const rb = document.createElement("button");
          rb.className = "btn small"; rb.textContent = "Restore"; rb.style.marginLeft = "4px";
          rb.onclick = async () => {
            try {
              await LMS.api.post("../api/customers.php?action=restore", { id: c.id });
              LMS.ui.toast("Customer restored.", "success");
              load();
            } catch (err) { LMS.ui.fail(err); }
          };
          td.appendChild(rb);
          tr.appendChild(td);
          tb.appendChild(tr);
          return;
        }
        const db = document.createElement("button");
        db.className = "iconbtn";
        db.textContent = "Del";
        db.style.width = "auto";
        db.style.marginLeft = "4px";
        db.onclick = async () => {
          const ok = await LMS.ui.confirmDlg("Archive / delete customer", archived ? "Delete this customer permanently?" : "Customers with order history are archived, never wiped. Continue?");
          if (!ok) return;
          try {
            const r = await LMS.api.post("../api/customers.php?action=delete", { id: c.id });
            LMS.ui.toast(r.message || "Done.", "success");
            load();
          } catch (err) { LMS.ui.fail(err); }
        };
        td.appendChild(db);
        tr.appendChild(td);
        tb.appendChild(tr);
      });
      LMS.ui.pager(document.getElementById("pager"), d.total, d.page, d.limit, (p) => { page = p; load(); });
    } catch (e) {
      tb.innerHTML = LMS.ui.emptyRow(6, e.message || "Failed to load.");
    }
  }
  load();
});

