document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("searchBtn").onclick = load;
  document.getElementById("addBtn").onclick = () => {
    document.getElementById("mTitle").textContent = "Add item";
    document.getElementById("itemForm").reset();
    document.getElementById("fId").value = "";
    LMS.ui.openModal("itemModal");
  };
  document.getElementById("mClose").onclick = () => LMS.ui.closeModal("itemModal");
  document.getElementById("vClose").onclick = () => LMS.ui.closeModal("moveModal");
  document.getElementById("itemForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("mSave");
    LMS.ui.setLoading(btn, true);
    try {
      await LMS.api.post("../api/inventory.php?action=save", Object.fromEntries(new FormData(e.target).entries()));
      LMS.ui.toast("Item saved.", "success");
      LMS.ui.closeModal("itemModal");
      load();
    } catch (err) { LMS.ui.fail(err, e.target); }
    LMS.ui.setLoading(btn, false);
  });
  document.getElementById("moveForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("vSave");
    LMS.ui.setLoading(btn, true);
    try {
      const r = await LMS.api.post("../api/inventory.php?action=move", Object.fromEntries(new FormData(e.target).entries()));
      LMS.ui.toast("Stock updated. Now: " + r.stock, "success");
      LMS.ui.closeModal("moveModal");
      load();
    } catch (err) { LMS.ui.fail(err, e.target); }
    LMS.ui.setLoading(btn, false);
  });
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="6"><span class="spin"></span> Loading...</td></tr>';
    try {
      const d = await LMS.api.get("../api/inventory.php", { action: "list", q: document.getElementById("q").value.trim() });
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(6, "No items found.");
      d.rows.forEach((r) => {
        const low = Number(r.is_low) === 1;
        const tr = document.createElement("tr");
        tr.innerHTML = "<td>" + LMS.ui.esc(r.item_name) + "</td><td>" + LMS.ui.esc(r.unit) + "</td><td class='num'>" + LMS.ui.money(r.current_stock) + "</td><td class='num'>" + LMS.ui.money(r.low_threshold) + "</td><td>" + (low ? LMS.ui.badge("low stock") : LMS.ui.badge("ok")) + "</td>";
        const td = document.createElement("td");
        const mv = document.createElement("button");
        mv.className = "btn small"; mv.textContent = "Stock";
        mv.onclick = () => { document.getElementById("vId").value = r.id; LMS.ui.openModal("moveModal"); };
        td.appendChild(mv);
        const ed = document.createElement("button");
        ed.className = "btn small"; ed.style.marginLeft = "4px"; ed.textContent = "Edit";
        ed.onclick = () => {
          document.getElementById("mTitle").textContent = "Edit item";
          document.getElementById("fId").value = r.id;
          document.getElementById("fName").value = r.item_name;
          document.getElementById("fUnit").value = r.unit;
          document.getElementById("fLow").value = r.low_threshold;
          LMS.ui.openModal("itemModal");
        };
        td.appendChild(ed);
        const hb = document.createElement("button");
        hb.className = "btn small"; hb.style.marginLeft = "4px"; hb.textContent = "History";
        hb.onclick = async () => {
          const box = document.getElementById("histBody");
          box.innerHTML = '<span class="spin"></span> Loading...';
          document.getElementById("histTitle").textContent = "Movements â€” " + r.item_name;
          LMS.ui.openModal("histModal");
          try {
            const h = await LMS.api.get("../api/inventory.php", { action: "moves", item_id: r.id });
            box.innerHTML = "";
            if (!h.rows.length) { box.innerHTML = '<div class="empty"><div class="big">â€”</div><div>No movements yet.</div></div>'; return; }
            const tw = document.createElement("div");
            tw.className = "tablewrap";
            tw.innerHTML = "<table class='data'><thead><tr><th>Date</th><th>Type</th><th class='num'>Qty</th><th>By</th><th>Note</th></tr></thead><tbody>" + h.rows.map((x) => "<tr><td>" + LMS.ui.esc(String(x.created_at).slice(0, 16).replace("T", " ")) + "</td><td>" + LMS.ui.badge(x.move_type) + "</td><td class='num'>" + LMS.ui.money(x.quantity) + "</td><td>" + LMS.ui.esc(x.by_name || "â€”") + "</td><td>" + LMS.ui.esc(x.note || "â€”") + "</td></tr>").join("") + "</tbody></table>";
            box.appendChild(tw);
          } catch (e) { box.innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load.") + "</div>"; }
        };
        td.appendChild(hb);
        tr.appendChild(td);
        tb.appendChild(tr);
      });
    } catch (e) { tb.innerHTML = LMS.ui.emptyRow(6, e.message || "Failed to load."); }
  }
  load();
});

