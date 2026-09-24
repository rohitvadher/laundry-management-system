document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("addBtn").onclick = () => {
    document.getElementById("mTitle").textContent = "Add user";
    document.getElementById("userForm").reset();
    document.getElementById("fId").value = "";
    LMS.ui.openModal("userModal");
  };
  document.getElementById("mClose").onclick = () => LMS.ui.closeModal("userModal");
  document.getElementById("userForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    const btn = document.getElementById("mSave");
    LMS.ui.setLoading(btn, true);
    try {
      await LMS.api.post("../api/users.php?action=save", Object.fromEntries(new FormData(e.target).entries()));
      LMS.ui.toast("User saved.", "success");
      LMS.ui.closeModal("userModal");
      load();
    } catch (err) { LMS.ui.fail(err, e.target); }
    LMS.ui.setLoading(btn, false);
  });
  async function load() {
    const tb = document.getElementById("rows");
    tb.innerHTML = '<tr><td colspan="5"><span class="spin"></span> Loading...</td></tr>';
    try {
      const d = await LMS.api.get("../api/users.php", { action: "list" });
      tb.innerHTML = "";
      if (!d.rows.length) tb.innerHTML = LMS.ui.emptyRow(5, "No users.");
      d.rows.forEach((u) => {
        const tr = document.createElement("tr");
        tr.innerHTML = "<td>" + LMS.ui.esc(u.username) + "</td><td>" + LMS.ui.esc(u.full_name) + "</td><td>" + LMS.ui.badge(u.role) + "</td><td>" + (Number(u.is_active) ? LMS.ui.badge("active") : LMS.ui.badge("inactive")) + "</td>";
        const td = document.createElement("td");
        const eb = document.createElement("button");
        eb.className = "btn small"; eb.textContent = "Edit";
        eb.onclick = () => {
          document.getElementById("mTitle").textContent = "Edit user";
          document.getElementById("fId").value = u.id;
          document.getElementById("fUser").value = u.username;
          document.getElementById("fName").value = u.full_name;
          document.getElementById("fRole").value = u.role;
          document.getElementById("fPass").value = "";
          LMS.ui.openModal("userModal");
        };
        td.appendChild(eb);
        const tb2 = document.createElement("button");
        tb2.className = "btn small"; tb2.style.marginLeft = "4px"; tb2.textContent = Number(u.is_active) ? "Deactivate" : "Activate";
        tb2.onclick = async () => {
          const ok = await LMS.ui.confirmDlg("Change user status", "Toggle active state for " + u.username + "?");
          if (!ok) return;
          try {
            await LMS.api.post("../api/users.php?action=toggle", { id: u.id });
            LMS.ui.toast("Updated.", "success");
            load();
          } catch (err) { LMS.ui.fail(err); }
        };
        td.appendChild(tb2);
        tr.appendChild(td);
        tb.appendChild(tr);
      });
    } catch (e) { tb.innerHTML = LMS.ui.emptyRow(5, e.message || "Failed to load."); }
  }
  load();
});

