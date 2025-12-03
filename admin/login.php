<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS System Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100">

    <div class="card shadow-sm p-4" style="width: 22rem;">
        <h3 class="text-center mb-4">POS System Login</h3>

        <form id="login-form">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" id="username" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" id="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Login
            </button>
        </form>
    </div>

<script>
document.getElementById("login-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    const formData = new FormData();
    formData.append("action", "login");
    formData.append("username", username);
    formData.append("password", password);

    const response = await fetch("api/handler.php", {
        method: "POST",
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        await Swal.fire("Success", result.message, "success");

        window.location.href =
            result.role === "admin"
            ? "cashier_dashboard.php"
            : "dashboard.php";

    } else {
        await Swal.fire("Error", result.message, "error");
    }
});
</script>

</body>
</html>
