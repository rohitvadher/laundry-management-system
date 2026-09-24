document.addEventListener("DOMContentLoaded", () => {
  const T = [
    ["Getting started", "<p>Laundry MS tracks customers, services, orders, payments, deliveries, stock, and reports from one desktop workspace. Sign in, check the Dashboard for today's position, then work through Orders, Delivery, and Payments.</p><ol><li>Open Dashboard to see revenue, outstanding, deliveries, and stock.</li><li>Add customers and services once; reuse them on every order.</li><li>Create orders, move them along the status pipeline, record payments, and print invoices.</li></ol><p><strong>Note:</strong> cancelled orders never count toward revenue. Amounts shown before submitting an order are estimates; the server computes the final bill.</p>"],
    ["Dashboard", "<p>Shows this month's revenue and order count, total outstanding balances, today's and overdue deliveries, low-stock items, the last five orders, and a 14-day revenue chart.</p><p><strong>When to use it:</strong> at the start of the day to decide what needs attention. <strong>Common mistake:</strong> treating revenue as cash collected â€” use Outstanding and the Payments page for collection.</p>"],
    ["Customers", "<p><strong>What:</strong> master list of customers with order count, lifetime spending, and outstanding balance.</p><ol><li>Use search and the Active filter, then Add customer with name and a valid mobile number.</li><li>Open a profile for contact details, financial summary, and full order history.</li><li>Deleting a customer with order history archives instead of wiping it; restore from the Archived filter.</li></ol><p><strong>Common mistake:</strong> creating the same customer twice â€” search by mobile first.</p>"],
    ["Services", "<p><strong>What:</strong> priced laundry services with an optional GST rate each.</p><ol><li>Add a service with name and a price above zero.</li><li>Changing a price affects new orders only; old invoices keep their frozen rates.</li><li>Services used in past orders deactivate instead of deleting; restore from the Inactive filter.</li></ol>"],
    ["Creating orders", "<p><strong>What:</strong> a billable job for one customer with one or more service lines.</p><ol><li>Pick the customer, pickup and delivery dates, and assignee.</li><li>Add service rows with quantities from 1 to 1000; duplicate services merge automatically.</li><li>Set a fixed or percent discount if needed, review the estimate, then place the order once â€” double submits are ignored safely.</li></ol><p><strong>Notes:</strong> delivery cannot be in the past; empty orders and unknown services are rejected; the invoice number is assigned automatically.</p><p><strong>Common mistakes:</strong> leaving every quantity at zero, or trusting the on-screen estimate as the bill â€” the server recalculates everything.</p>"],
    ["Order status", "<p>Pipeline: Pending to Processing to Ready to Delivered, with Cancel allowed from Pending, Processing, or Ready. Closed orders (Delivered, Cancelled) cannot move again.</p><ol><li>Open the order and pick the next allowed status shown under the form.</li><li>Marking Delivered stamps the actual delivery date.</li></ol><p><strong>Common mistake:</strong> trying to reopen a delivered order â€” create a new order instead.</p>"],
    ["Payments", "<p><strong>What:</strong> receipts against an order by Cash, UPI, Card, Bank transfer, or Other.</p><ol><li>Open the order, enter amount, method, date, and reference for non-cash.</li><li>The form hides once the order is fully paid or cancelled.</li><li>Status becomes Partial or Paid automatically; overpayments and payments on cancelled orders are rejected with the exact balance shown.</li></ol><p><strong>Common mistake:</strong> recording the full total twice â€” check Paid and Balance on the invoice first.</p>"],
    ["Invoices", "<p>Each order carries one invoice with business identity, GSTIN, customer block, item lines, subtotal, discount, taxable value, CGST plus SGST, grand total, paid and balance, and payment history.</p><p><strong>When to use it:</strong> billing the customer and settling disputes â€” the stored snapshot never changes when prices change later.</p>"],
    ["Delivery", "<p>Board of non-cancelled, non-delivered orders grouped as Upcoming, Today, Overdue, and Delivered, with counts, staff assignment, totals, and payment state.</p><ol><li>Start the day on Today and Overdue.</li><li>Open an order to reassign staff or mark it Delivered.</li></ol>"],
    ["Inventory", "<p>Tracks consumables such as detergent and packaging with current stock and low-stock thresholds.</p><ol><li>Add items once, then record Purchase to add, Usage to reduce, or Adjust to reconcile to a counted value.</li><li>Usage beyond available stock is rejected; low items badge automatically and raise a notification.</li><li>Use History on any row for the full movement trail.</li></ol>"],
    ["Reports", "<p>Presets for today, week, month, and year plus any custom range. Shows orders, revenue, collected, outstanding, discounts, GST, a daily chart, status breakdown, top services, payments by method, and cancelled orders separately.</p><p><strong>Note:</strong> revenue always excludes cancelled orders. Staff accounts cannot open financial reports.</p>"],
    ["Notifications", "<p>Internal feed for new orders, ready and delivered orders, payments received, and low stock. The header bell shows the unread count; open a notification to jump to the record, and mark items read individually or all at once.</p>"],
    ["Staff and roles", "<p>Roles: admin (everything), manager (everything except staff management), staff (daily operations), accountant (payments and reports).</p><ol><li>Admins create users with a username of 3 or more characters and a 6 or more character password.</li><li>Deactivate rather than delete leavers; you cannot deactivate your own account.</li><li>Hidden menu items are backed by server permission checks, not just hidden buttons.</li></ol>"],
    ["Business settings", "<p>Central profile used on every invoice and in billing: name, address, phone, email, GSTIN, GST on or off, default rate, inclusive or exclusive tax mode, invoice prefix, currency, footer, and terms. Restricted to admin and manager. Changes apply to new orders; old invoices keep their snapshots.</p>"],
    ["GST", "<p>Enable GST in Settings with a default rate and tax mode. Exclusive adds tax on top of the discounted amount; inclusive treats the amount as already containing tax and splits it out for display. Invoices always show taxable value, rate, CGST, SGST, and total GST; reports total GST per range.</p>"],
    ["Search and filters", "<p>Lists offer text search plus status, payment, date, method, and sort controls depending on the page. Results are paged server-side so large histories stay fast. Clear the search box to reset.</p>"],
    ["Printing invoices", "<p>Open the order and choose Print. The print layout keeps the invoice and totals while hiding navigation and action buttons. Check the preview for one page before bulk printing.</p>"],
    ["Common errors", "<ul><li><strong>Session expired:</strong> sign in again; unsaved form input may be lost.</li><li><strong>Invalid request token:</strong> refresh the page and retry.</li><li><strong>Too many failed attempts:</strong> wait a few minutes before retrying login.</li><li><strong>Payment exceeds balance:</strong> the message states the exact outstanding amount â€” enter that or less.</li><li><strong>Cannot move order:</strong> only the listed next statuses are allowed.</li><li><strong>Unauthorized action:</strong> your role lacks permission â€” ask an admin.</li></ul>"],
    ["Troubleshooting", "<ul><li>List not loading: check your connection and reload; the app shows the exact reason in a banner.</li><li>Modal lost your input after an error: field errors appear under each field â€” fix and resubmit.</li><li>Dashboard empty for a staff account: financial KPIs are restricted; use Orders, Delivery, and Customers.</li><li>Database unavailable message: confirm MySQL is running and reload once.</li></ul>"],
    ["Security and login", "<p>Passwords are stored hashed, sessions expire after inactivity, and repeated failed logins trigger a short cooldown with a generic message that never reveals whether a username exists. Always sign out on shared computers. Admins should replace the default admin password immediately and create individual accounts.</p>"],
    ["Logout", "<p>Use Sign out in the top bar to end your session securely. Closing the browser tab alone relies on session expiry instead.</p>"]
  ];
  const list = document.getElementById("helpList");
  const empty = document.getElementById("helpEmpty");
  T.forEach(([title, body], i) => {
    const div = document.createElement("div");
    div.className = "help-item";
    div.dataset.title = title.toLowerCase();
    div.innerHTML = "<button type='button' aria-expanded='false'><span>" + LMS.ui.esc(title) + "</span><span>+</span></button><div class='help-body'>" + body + "</div>";
    const btn = div.querySelector("button");
    btn.onclick = () => {
      const open = div.classList.toggle("open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
      btn.querySelectorAll("span")[1].textContent = open ? "â€“" : "+";
    };
    if (i === 0) btn.click();
    list.appendChild(div);
  });
  document.getElementById("helpQ").addEventListener("input", (e) => {
    const q = e.target.value.trim().toLowerCase();
    let visible = 0;
    list.querySelectorAll(".help-item").forEach((div) => {
      const hit = !q || div.dataset.title.includes(q) || div.textContent.toLowerCase().includes(q);
      div.style.display = hit ? "" : "none";
      if (hit) visible++;
    });
    empty.style.display = visible ? "none" : "";
  });
});

