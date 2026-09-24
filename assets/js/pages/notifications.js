document.addEventListener("DOMContentLoaded", () => {
  let page = 1;
  const limit = 10;
  document.getElementById("readAll").onclick = async () => {
    try {
      await LMS.api.post("../api/notifications.php?action=read", { all: 1 });
      page = 1;
      load();
    } catch (e) { LMS.ui.fail(e); }
  };
  async function load() {
    const box = document.getElementById("list");
    box.innerHTML = '<span class="spin"></span> Loading...';
    try {
      const d = await LMS.api.get("../api/notifications.php", { action: "list", page, limit });
      box.innerHTML = "";
      if (!d.rows.length) box.innerHTML = '<div class="empty"><div class="big">â€”</div><div>No notifications.</div></div>';
      d.rows.forEach((n) => {
        const div = document.createElement("div");
        div.className = "alert " + (Number(n.is_read) ? "info" : "warning");
        div.innerHTML = "<strong>" + LMS.ui.esc(n.title) + "</strong><br>" + LMS.ui.esc(n.body || "") + "<br><small>" + LMS.ui.esc(String(n.created_at).slice(0, 16)) + "</small> " + (n.link ? "<a href='../" + LMS.ui.esc(n.link) + "'>Open</a> " : "") + (Number(n.is_read) ? "" : "<button class='btn small' style='margin-left:8px'>Mark read</button>");
        if (!Number(n.is_read)) {
          div.querySelector("button").onclick = async () => {
            await LMS.api.post("../api/notifications.php?action=read", { id: n.id });
            load();
          };
        }
        box.appendChild(div);
      });
      LMS.ui.pager(document.getElementById("pager"), d.total, page, limit, (p) => { page = p; load(); });
    } catch (e) { box.innerHTML = '<div class="alert error">' + LMS.ui.esc(e.message || "Failed to load.") + "</div>"; }
  }
  load();
});

