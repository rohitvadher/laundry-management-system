document.addEventListener("DOMContentLoaded", () => {
  let page = 1;
  const limit = 15;
  const q = document.getElementById("q");
  const active = document.getElementById("active");
  document.getElementById("searchBtn").onclick = () => { page = 1; load(); };
  document.getElementById("addBtn").onclick = () => {
    document.getElementById("mTitle").textContent = "Add service";
    document.getElementById("svcForm").reset();
    document.getElementById("fId").value = "";
    LMS.ui.openModal("svcModal");
  };
  document.getElementById("mClose").onclick = () => LMS.ui.closeModal("svcModal");
  document.getElementById("svcForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById("mSave");
    LMS.ui.setLoading(btn, true);
    try {
      await LMS.api.post("../api/services.php?action=save", Object.fromEntries(new FormData(form).entries()));
      LMS.ui.toast("Service saved.", "success");
      LMS.ui.closeModal("svcModal");
      load();
    } catch (err) { LMS.ui.fail(err, form); }
    LMS.ui.setLoading(btn, false);
  });
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="5"><span class="spin"></span> Loading...</td></tr>';
    try {
      const d = await LMS.api.get("../api/services.php", { action: "list", q: q.value.trim(), active: active.value, page, limit });
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(5, "No services found.");
      d.rows.forEach((s) => {
        const tr = document.createElement("tr");
        tr.innerHTML = "<td>" + LMS.ui.esc(s.service_name) + (Number(s.is_active) === 0 ? " " + LMS.ui.badge("inactive") : "") + "</td><td>" + LMS.ui.esc(s.description || "â€”") + "</td><td class='num'>" + LMS.ui.amt(s.price) + "</td><td class='num'>" + LMS.ui.money(s.gst_rate) + "</td>";
        const td = document.createElement("td");
        const eb = document.createElement("button");
        eb.className = "iconbtn"; eb.textContent = "Edit"; eb.style.width = "auto";
        eb.onclick = () => {
          document.getElementById("mTitle").textContent = "Edit service";
          document.getElementById("fId").value = s.id;
          document.getElementById("fName").value = s.service_name;
          document.getElementById("fDesc").value = s.description || "";
          document.getElementById("fPrice").value = s.price;
          document.getElementById("fGst").value = s.gst_rate || 0;
          LMS.ui.openModal("svcModal");
        };
        td.appendChild(eb);
        if (Number(s.is_active) === 0) {
          const rb = document.createElement("button");
          rb.className = "btn small"; rb.textContent = "Restore"; rb.style.marginLeft = "4px";
          rb.onclick = async () => {
            try {
              await LMS.api.post("../api/services.php?action=restore", { id: s.id });
              LMS.ui.toast("Service restored.", "success");
              load();
            } catch (err) { LMS.ui.fail(err); }
          };
          td.appendChild(rb);
          tr.appendChild(td);
          tb.appendChild(tr);
          return;
        }
        const db = document.createElement("button");
        db.className = "iconbtn"; db.textContent = "Del"; db.style.width = "auto"; db.style.marginLeft = "4px";
        db.onclick = async () => {
          const ok = await LMS.ui.confirmDlg("Deactivate / delete service", "Services used in past orders are deactivated, never wiped. Continue?");
          if (!ok) return;
          try {
            const r = await LMS.api.post("../api/services.php?action=delete", { id: s.id });
            LMS.ui.toast(r.message || "Done.", "success");
            load();
          } catch (err) { LMS.ui.fail(err); }
        };
        td.appendChild(db);
        tr.appendChild(td);
        tb.appendChild(tr);
      });
      LMS.ui.pager(document.getElementById("pager"), d.total, d.page, d.limit, (p) => { page = p; load(); });
    } catch (e) { tb.innerHTML = LMS.ui.emptyRow(5, e.message || "Failed to load."); }
  }
  load();
});

