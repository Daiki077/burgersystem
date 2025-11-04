<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS System Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  </head>
  <body class="bg-light d-flex justify-content-center align-items-center vh-100">

    <div class="card shadow-sm p-4" style="width: 22rem;">
      <h3 class="text-center mb-4">POS System Login</h3>
      <form  id="login-form">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>
    </div>

  </body>
  <script>
      const loginForm = document.getElementById('login-form');

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    await loginUser();
});

async function loginUser() {
    const formData = new FormData(loginForm);
    formData.append('action', 'login');
    
    
    const response = await fetch('api/handler.php', {
        method: 'POST',
        body: formData
    });
    const result = await response.json();

    if (result.success) {
        await Swal.fire('Success', result.message, 'success');
        if (result.role === 'admin') {
            location.href = 'cashier_dashboard.php';
        } else {
            location.href = 'dashboard.php';
        }
    } else {
        await Swal.fire('Error', result.message, 'error');
    }
}
</script>
  </script>
</html>
