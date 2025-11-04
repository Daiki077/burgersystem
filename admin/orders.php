<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Orders - Cashier</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    body { background-color: aquamarine; font-family: Arial, sans-serif; }
    h3 { text-shadow: 1px 1px #fff; }
    .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="m-0">Orders</h3>
      <div class="d-flex gap-2">
        <a href="menu.php" class="btn btn-outline-secondary btn-sm">POS</a>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Products</a>
        <a href="report.php" class="btn btn-outline-secondary btn-sm">Reports</a>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <form id="filter-form" class="row g-2 align-items-end">
          <div class="col-auto">
            <label class="form-label">Date start</label>
            <input type="date" class="form-control" id="date_start">
          </div>
          <div class="col-auto">
            <label class="form-label">Date end</label>
            <input type="date" class="form-control" id="date_end">
          </div>
          <div class="col-auto">
            <button class="btn btn-primary" type="submit">Apply</button>
          </div>
          <div class="col-auto ms-auto">
            <button id="btn-refresh" class="btn btn-outline-primary" type="button">Refresh</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-striped table-hover m-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Date</th>
              <th>Source</th>
              <th>Status</th>
              <th>Payment OK?</th>
              <th>Total (₱)</th>
              <th>Paid (₱)</th>
              <th>Change (₱)</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tbody-orders"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="modal fade" id="itemsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Order Items</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
              <tbody id="tbody-items"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let refreshTimer = null;

    async function loadOrders() {
      const fd = new FormData();
      fd.append('action', 'report');
      const ds = document.getElementById('date_start').value;
      const de = document.getElementById('date_end').value;
      if (ds) fd.append('date_start', ds);
      if (de) fd.append('date_end', de);
      const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
      if (!res.success) {
        await Swal.fire('Error', res.message || 'Failed to load orders', 'error');
        return;
      }
      const tbody = document.getElementById('tbody-orders');
      tbody.innerHTML = '';
      (res.orders || []).forEach((o, idx) => {
        const source = o.cashier ? 'In-store' : 'Public';
        const paid = o.payment_amount !== null ? Number(o.payment_amount) : 0;
        const total = Number(o.total);
        const ok = paid >= total;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td>${o.date_added}</td>
          <td>${source}</td>
          <td>${o.status}</td>
          <td>${ok ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">Insufficient</span>'}</td>
          <td>₱${Number(o.total).toFixed(2)}</td>
          <td>₱${o.payment_amount !== null ? Number(o.payment_amount).toFixed(2) : '-'}</td>
          <td>₱${o.change_amount !== null ? Number(o.change_amount).toFixed(2) : '-'}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-action="view" data-id="${o.id}">View</button>
            ${o.status === 'pending' ? `<button class="btn btn-sm btn-success ms-1" data-action="received" data-id="${o.id}">Mark Received</button>` : ''}
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    async function viewItems(orderId) {
      const fd = new FormData();
      fd.append('action', 'order_details');
      fd.append('order_id', String(orderId));
      const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
      if (!res.success) {
        await Swal.fire('Error', res.message || 'Failed to load items', 'error');
        return;
      }
      const tbody = document.getElementById('tbody-items');
      tbody.innerHTML = '';
      (res.items || []).forEach(it => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${it.product_name}</td>
          <td class="text-end">${it.qty}</td>
          <td class="text-end">₱${Number(it.price).toFixed(2)}</td>
          <td class="text-end">₱${Number(it.subtotal).toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
      });
      const modal = new bootstrap.Modal(document.getElementById('itemsModal'));
      modal.show();
    }

    document.getElementById('filter-form').addEventListener('submit', function(e) {
      e.preventDefault();
      loadOrders();
    });
    document.getElementById('btn-refresh').addEventListener('click', loadOrders);
    document.getElementById('tbody-orders').addEventListener('click', async function(e){
      const viewBtn = e.target.closest('button[data-action="view"]');
      if (viewBtn) {
        const id = viewBtn.getAttribute('data-id');
        viewItems(id);
        return;
      }
      const recvBtn = e.target.closest('button[data-action="received"]');
      if (recvBtn) {
        const id = recvBtn.getAttribute('data-id');
        const fd = new FormData();
        fd.append('action', 'update_order_status');
        fd.append('order_id', id);
        fd.append('status', 'received');
        const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
        if (res.success) {
          await Swal.fire('Success', res.message || 'Order marked as received', 'success');
          loadOrders();
        } else {
          await Swal.fire('Error', res.message || 'Failed to update order', 'error');
        }
      }
    });

    // Auto-refresh every 10s
    function startAutoRefresh() {
      if (refreshTimer) clearInterval(refreshTimer);
      refreshTimer = setInterval(loadOrders, 10000);
    }

    loadOrders();
    startAutoRefresh();
  </script>
</body>
</html>

