<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Products Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body style="background-color: aquamarine; font-family: Arial, sans-serif;">
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="m-0" style="text-shadow: 1px 1px #fff;">Products</h3>
      <div class="d-flex gap-2">
        <a href="orders.php" class="btn btn-outline-secondary btn-sm">Orders</a>
        <a href="report.php" class="btn btn-outline-secondary btn-sm">Reports</a>
        <a id="link-users" href="usermanagement.php" class="btn btn-outline-secondary btn-sm" style="display:none;">Users</a>
        <a href="menu.php" class="btn btn-outline-secondary btn-sm">menu</a>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12 col-lg-4">
        <div class="card" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
          <div class="card-header">Add Product</div>
          <div class="card-body">
            <form id="product-form" enctype="multipart/form-data">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="product_name" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Price (₱)</label>
                <input type="number" class="form-control" name="price" step="0.01" min="0" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" class="form-control" name="image" accept="image/*">
              </div>
              <button class="btn btn-primary w-100" type="submit">Add</button>
            </form>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-8">
        <div class="card" style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Product List</span>
            <button id="btn-refresh" class="btn btn-sm btn-outline-primary">Refresh</button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-striped table-hover m-0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Added By</th>
                    <th>Date Added</th>
                  </tr>
                </thead>
                <tbody id="tbody-products"></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    async function loadProducts() {
      const fd = new FormData();
      fd.append('action', 'list_products');
      const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
      if (!res.success) {
        await Swal.fire('Error', res.message || 'Failed to load products', 'error');
        return;
      }
      const tbody = document.getElementById('tbody-products');
      tbody.innerHTML = '';
      (res.products || []).forEach((p, idx) => {
        const imgSrc = p.image ? ('../upload/' + p.image) : 'https://via.placeholder.com/60x40?text=No+Image';
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${idx + 1}</td>
          <td><img src="${imgSrc}" alt="${p.product_name}" style="width:60px;height:40px;object-fit:cover;border:1px solid #ddd"/></td>
          <td>${p.product_name}</td>
          <td>₱${Number(p.price).toFixed(2)}</td>
          <td>${p.added_by_name || '-'}</td>
          <td>${p.date_added}</td>
        `;
        tbody.appendChild(tr);
      });
    }

    document.getElementById('btn-refresh').addEventListener('click', loadProducts);

    document.getElementById('product-form').addEventListener('submit', async function(e){
      e.preventDefault();
      const fd = new FormData(this);
      fd.append('action', 'add_product');
      const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r => r.json());
      if (res.success) {
        await Swal.fire('Success', res.message || 'Product added', 'success');
        this.reset();
        loadProducts();
      } else {
        await Swal.fire('Error', res.message || 'Failed to add product', 'error');
      }
    });

    loadProducts();
    // Show Users link only for superadmin
    (async function(){
      try {
        const fd = new FormData();
        fd.append('action','me');
        const res = await fetch('api/handler.php', { method: 'POST', body: fd }).then(r=>r.json());
        if (res && res.success && res.user && res.user.role === 'superadmin') {
          const link = document.getElementById('link-users');
          if (link) link.style.display = '';
        }
      } catch(e) {}
    })();
  </script>
</body>
</html>
