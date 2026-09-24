<?php
declare(strict_types=1);
$pageTitle = 'Staff';
$pageScript = '../assets/js/pages/users.js';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="pagehead"><div><div class="crumb">System / Staff</div><h2>Staff &amp; roles</h2></div><span class="spacer"></span><button class="btn primary" id="addBtn">Add user</button></div>
<div class="card"><div class="cbody">
<div class="tablewrap"><table class="data"><thead><tr><th scope="col">Username</th><th scope="col">Full name</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col"></th></tr></thead><tbody id="rows"><tr><td colspan="5"><span class="spin"></span> Loading...</td></tr></tbody></table></div>
</div></div>
<div class="modal-back" id="userModal"><div class="modal" role="dialog" aria-modal="true"><div class="mhead"><h3 id="mTitle">Add user</h3></div><form id="userForm"><div class="mbody">
<input type="hidden" name="id" id="fId">
<div class="frow"><div class="field"><label for="fUser">Username</label><input class="input" name="username" id="fUser" maxlength="50" required></div>
<div class="field"><label for="fRole">Role</label><select class="select" name="role" id="fRole"><option>admin</option><option>manager</option><option>staff</option><option>accountant</option></select></div></div>
<div class="field"><label for="fName">Full name</label><input class="input" name="full_name" id="fName" maxlength="100" required></div>
<div class="field"><label for="fPass">Password (6+ chars; blank keeps current on edit)</label><input class="input" type="password" name="password" id="fPass"></div>
</div><div class="mfoot"><button type="button" class="btn" id="mClose">Close</button><button type="submit" class="btn primary" id="mSave">Save</button></div></form></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

