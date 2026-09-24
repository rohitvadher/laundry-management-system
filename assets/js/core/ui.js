const LMS2 = window.LMS || {};
LMS2.ui = (() => {
  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  }
  function toast(msg, type = "info", ms = 4200) {
    const box = document.getElementById("toasts");
    if (!box) return;
    const t = document.createElement("div");
    t.className = "toast " + type;
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(() => { t.remove(); }, ms);
  }
  function fieldErrors(form, errors) {
    form.querySelectorAll(".ferr").forEach((e) => e.remove());
    form.querySelectorAll(".invalid").forEach((e) => e.classList.remove("invalid"));
    Object.keys(errors || {}).forEach((k) => {
      const el = form.querySelector('[name="' + k + '"]');
      if (!el) return;
      el.classList.add("invalid");
      const d = document.createElement("div");
      d.className = "ferr";
      d.textContent = errors[k];
      el.after(d);
    });
  }
  function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add("open");
    const f = m.querySelector("input:not([type=hidden]), select, textarea");
    if (f) setTimeout(() => f.focus(), 60);
  }
  function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove("open");
  }
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") document.querySelectorAll(".modal-back.open").forEach((m) => m.classList.remove("open"));
  });
  function confirmDlg(title, body, okLabel = "Confirm") {
    return new Promise((resolve) => {
      const back = document.getElementById("confirmBack");
      document.getElementById("confirmTitle").textContent = title;
      document.getElementById("confirmBody").textContent = body;
      const ok = document.getElementById("confirmOk");
      ok.textContent = okLabel;
      back.classList.add("open");
      setTimeout(() => ok.focus(), 60);
      const done = (v) => {
        back.classList.remove("open");
        ok.onclick = null;
        resolve(v);
      };
      ok.onclick = () => done(true);
      document.getElementById("confirmCancel").onclick = () => done(false);
      back.onclick = (e) => { if (e.target === back) done(false); };
    });
  }
  function setLoading(btn, on, label = "Saving...") {
    if (!btn) return;
    if (on) {
      btn.dataset.label = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spin"></span> ' + esc(label);
    } else {
      btn.disabled = false;
      if (btn.dataset.label) btn.innerHTML = btn.dataset.label;
    }
  }
  function emptyRow(cols, text) {
    return '<tr><td colspan="' + cols + '"><div class="empty"><div class="big">â€”</div><div>' + esc(text) + "</div></div></td></tr>";
  }
  function badge(status) {
    const s = String(status || "");
    const cls = s.toLowerCase().replace(/[^a-z]/g, "");
    return '<span class="badge ' + esc(cls) + '">' + esc(s) + "</span>";
  }
  function pager(el, total, page, limit, onGo) {
    const pages = Math.max(1, Math.ceil(total / limit));
    el.innerHTML = "";
    const info = document.createElement("span");
    info.textContent = total + " records Â· page " + page + " of " + pages;
    el.appendChild(info);
    const mk = (label, p, dis) => {
      const b = document.createElement("button");
      b.className = "btn small";
      b.textContent = label;
      b.disabled = !!dis;
      b.onclick = () => onGo(p);
      el.appendChild(b);
    };
    mk("Prev", Math.max(1, page - 1), page <= 1);
    mk("Next", Math.min(pages, page + 1), page >= pages);
  }
  function money(n) {
    const v = Number(n || 0);
    try {
      return v.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch (e) {
      return v.toFixed(2);
    }
  }
  function amt(n) {
    const s = String((window.LMS && LMS.sym) || "");
    return s + money(n);
  }
  async function fail(e, form) {
    const msg = (e && e.message) || "Unexpected server error.";
    toast(msg, "error");
    if (form && e && e.errors) fieldErrors(form, e.errors);
  }
  return { esc, toast, fieldErrors, openModal, closeModal, confirmDlg, setLoading, emptyRow, badge, pager, money, amt, fail };
})();
window.LMS = Object.assign(window.LMS || {}, LMS2);

