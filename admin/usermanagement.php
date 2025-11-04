<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>User Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body style="background-color: aquamarine; font-family: Arial, sans-serif;">
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="m-0" style="text-shadow: 1px 1px #fff;">User Management</h3>
      <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>

    <div class="row g-4">
      <div class="col-12 col-lg-4">
        <div class="card" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
          <div class="card-header">Create Admin</div>
          <div class="card-body">
            <form id="create-admin-form">
              <div class="mb-3">
                <label class="form-label">Full name</label>
                <input type="text" class="form-control" name="fullname" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Create Admin</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-8">
        <div class="card" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Users</span>
            <button id="refresh-users" class="btn btn-sm btn-outline-primary">Refresh</button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped table-hover m-0">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Full name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="users-tbody">
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Guard: only superadmin may access this page
    (async function guard(){
      try {
        const fd = new FormData();
        fd.append('action','me');
        const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r=>r.json());
        if (!res || !res.success || !res.user || res.user.role !== 'superadmin') {
          window.location.href = 'cashier_dashboard.php';
        }
      } catch(e) {
        window.location.href = 'cashier_dashboard.php';
      }
    })();

    async function fetchJson(url, options) {
      const res = await fetch(url, options);
      return res.json();
    }

    async function loadUsers() {
      const form = new FormData();
      form.append('action', 'list_users');
      const result = await fetchJson('api/handler.php', { method: 'POST', body: form });
      if (!result.success) {
        await Swal.fire('Error', result.message || 'Failed to load users', 'error');
        return;
      }
      const tbody = document.getElementById('users-tbody');
      tbody.innerHTML = '';
      (result.users || []).forEach(u => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${u.id}</td>
          <td>${u.fullname}</td>
          <td>${u.username}</td>
          <td><span class="badge bg-${u.role === 'superadmin' ? 'dark' : 'secondary'}">${u.role}</span></td>
          <td><span class="badge bg-${u.status === 'active' ? 'success' : 'danger'}">${u.status}</span></td>
          <td>${u.date_added}</td>
          <td>
            ${u.role === 'admin' ? `
              <button class="btn btn-sm ${u.status === 'active' ? 'btn-outline-danger' : 'btn-outline-success'}" data-action="toggle" data-id="${u.id}" data-status="${u.status}">
                ${u.status === 'active' ? 'Suspend' : 'Activate'}
              </button>` : ''}
          </td>
        `;
        tbody.appendChild(tr);
      });
    }

    async function createAdmin(e) {
      e.preventDefault();
      const formEl = document.getElementById('create-admin-form');
      const fd = new FormData(formEl);
      fd.append('action', 'create_admin');
      const result = await fetchJson('api/handler.php', { method: 'POST', body: fd });
      if (result.success) {
        await Swal.fire('Success', result.message || 'Admin created', 'success');
        formEl.reset();
        loadUsers();
      } else {
        await Swal.fire('Error', result.message || 'Failed to create admin', 'error');
      }
    }

    async function onUsersTableClick(e) {
      const btn = e.target.closest('button[data-action="toggle"]');
      if (!btn) return;
      const id = btn.getAttribute('data-id');
      const currentStatus = btn.getAttribute('data-status');
      const nextStatus = currentStatus === 'active' ? 'suspended' : 'active';

      const fd = new FormData();
      fd.append('action', 'set_user_status');
      fd.append('user_id', id);
      fd.append('status', nextStatus);
      const result = await fetchJson('api/handler.php', { method: 'POST', body: fd });
      if (result.success) {
        await Swal.fire('Success', result.message || 'User status updated', 'success');
        loadUsers();
      } else {
        await Swal.fire('Error', result.message || 'Failed to update status', 'error');
      }
    }

    document.getElementById('create-admin-form').addEventListener('submit', createAdmin);
    document.getElementById('refresh-users').addEventListener('click', loadUsers);
    document.getElementById('users-tbody').addEventListener('click', onUsersTableClick);
    loadUsers();
  </script>
</body>
</html>