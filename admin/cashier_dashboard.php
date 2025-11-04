<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cashier Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: aquamarine; font-family: Arial, sans-serif; }
    h2 { text-align:center; text-shadow: 1px 1px #fff; margin: 20px 0; }
    .card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); cursor: pointer; }
    .grid { display:flex; gap:20px; justify-content:center; flex-wrap:wrap; }
    .card-body { text-align:center; padding:30px; }
  </style>
</head>
<body>
  <div class="container py-4">
    <h2>Cashier</h2>
    <div class="grid">
      <a class="card text-decoration-none" href="menu.php" style="width:220px;">
        <div class="card-body">
          <h5>POS</h5>
          <div>Take Orders</div>
        </div>
      </a>
      <a class="card text-decoration-none" href="orders.php" style="width:220px;">
        <div class="card-body">
          <h5>Orders</h5>
          <div>View/Receive Orders</div>
        </div>
      </a>
      <a class="card text-decoration-none" href="dashboard.php" style="width:220px;">
        <div class="card-body">
          <h5>Products</h5>
          <div>Add or View Products</div>
        </div>
      </a>
      <a class="card text-decoration-none" href="report.php" style="width:220px;">
        <div class="card-body">
          <h5>Reports</h5>
          <div>Transactions & PDF</div>
        </div>
      </a>
    </div>
    <div class="text-center mt-4">
      <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>
  </div>
</body>
</html>

