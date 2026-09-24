<?php
declare(strict_types=1);
require_once __DIR__ . '/backend/bootstrap.php';
if (lms_user()) {
    header('Location: pages/dashboard.php');
    exit;
}
$expired = isset($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in â€” Laundry MS</title>
<link rel="icon" href="icons/web/favicon.ico" sizes="any">
<link rel="stylesheet" href="assets/css/variables.css">
<link rel="stylesheet" href="assets/css/base.css">
<link rel="stylesheet" href="assets/css/layout.css">
<link rel="stylesheet" href="assets/css/components.css">
<link rel="stylesheet" href="assets/css/forms.css">
</head>
<body>
<div class="login-wrap">
<div class="login-card">
<div class="logo"><img src="icons/web/icon-192.png" alt="Laundry MS"><div><h2 style="margin:0">Laundry MS</h2><small style="color:var(--muted)">Sign in to continue</small></div></div>
<?php if ($expired): ?>
<div class="alert warning">Session expired. Please sign in again.</div>
<?php endif; ?>
<div class="alert error" id="errBox" style="display:none"></div>
<form id="loginForm">
<div class="field"><label for="username">Username</label><input class="input" id="username" name="username" autocomplete="username" maxlength="50" required autofocus></div>
<div class="field" style="position:relative"><label for="password">Password</label><input class="input" type="password" id="password" name="password" autocomplete="current-password" required style="padding-right:3.4rem"><button type="button" id="pwToggle" aria-label="Show password" style="position:absolute;right:6px;bottom:6px;border:none;background:none;color:var(--primary);cursor:pointer;font-size:0.82rem;font-weight:600;padding:0.35rem 0.4rem">Show</button></div>
<button class="btn primary" id="loginBtn" style="width:100%">Sign in</button>
</form>
</div>
</div>
<script>
const pwField = document.getElementById("password");
const pwToggle = document.getElementById("pwToggle");
pwToggle.addEventListener("click", () => {
  const show = pwField.type === "password";
  pwField.type = show ? "text" : "password";
  pwToggle.textContent = show ? "Hide" : "Show";
  pwToggle.setAttribute("aria-label", show ? "Hide password" : "Show password");
});
document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const box = document.getElementById("errBox");
  const btn = document.getElementById("loginBtn");
  box.style.display = "none";
  btn.disabled = true;
  btn.textContent = "Signing in...";
  const fd = new FormData(e.target);
  try {
    const res = await fetch("api/auth.php?action=login", { method: "POST", body: fd, headers: { Accept: "application/json" } });
    const body = await res.json();
    if (!res.ok || !body.success) throw new Error((body && body.message) || "Sign in failed.");
    const next = new URLSearchParams(window.location.search).get("next");
    const safe = next && next.charAt(0) === "/" && next.charAt(1) !== "/" ? next : "pages/dashboard.php";
    window.location.href = safe;
  } catch (err) {
    box.textContent = err.message || "Sign in failed.";
    box.style.display = "block";
  }
  btn.disabled = false;
  btn.textContent = "Sign in";
});
</script>
</body>
</html>

