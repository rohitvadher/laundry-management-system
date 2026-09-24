const LMS = window.LMS || {};
LMS.api = (() => {
  async function req(path, opts = {}) {
    const o = Object.assign({ headers: {} }, opts);
    o.headers = Object.assign({ Accept: "application/json" }, o.headers || {});
    if (LMS.csrf && (o.method === "POST" || o.method === "PUT" || o.method === "PATCH" || o.method === "DELETE")) {
      o.headers["X-CSRF-Token"] = LMS.csrf;
    }
    let res;
    try {
      res = await fetch(path, o);
    } catch (e) {
      throw { code: 0, message: "Service unavailable. Check your connection." };
    }
    let body = null;
    try {
      body = await res.json();
    } catch (e) {
      body = null;
    }
    if (res.status === 401) {
      const back = encodeURIComponent(window.location.pathname + window.location.search);
      window.location.href = "../index.php?expired=1&next=" + back;
      throw { code: 401, message: "Session expired." };
    }
    if (!res.ok || (body && body.success === false)) {
      throw { code: res.status, message: (body && body.message) || "Unexpected server error.", errors: (body && body.errors) || {} };
    }
    return body ? body.data : null;
  }
  function get(path, params = {}) {
    const q = new URLSearchParams(params).toString();
    return req(path + (q ? "?" + q : ""), { method: "GET" });
  }
  function post(path, data = {}) {
    const fd = new FormData();
    Object.keys(data || {}).forEach((k) => {
      const v = data[k];
      if (v === undefined || v === null) return;
      if (typeof v === "object" && !(v instanceof File)) {
        fd.append(k, JSON.stringify(v));
      } else {
        fd.append(k, v);
      }
    });
    if (LMS.csrf) fd.append("csrf", LMS.csrf);
    return req(path, { method: "POST", body: fd });
  }
  function postJSON(path, data = {}) {
    const payload = Object.assign({}, data);
    if (LMS.csrf && !payload.csrf) payload.csrf = LMS.csrf;
    return req(path, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify(payload) });
  }
  return { req, get, post, postJSON };
})();
window.LMS = LMS;

