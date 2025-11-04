<?php
require_once '../../core/dbonfig.php';

// Helpers
function json_ok($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function json_error($message, $code = 400, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => false, 'message' => $message], $extra));
    exit;
}

function current_user() {
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_auth() {
    if (!current_user()) {
        json_error('Unauthorized', 401);
    }
}

function require_role($roles) {
    $user = current_user();
    if (!$user) json_error('Unauthorized', 401);
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $roles)) {
        json_error('Forbidden', 403);
    }
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        json_error('Username and password are required');
    }

    $stmt = $pdo->prepare('SELECT id, fullname, username, password, role, status FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_error('Invalid username or password', 401);
    }

    if ($user['status'] === 'suspended') {
        json_error('Your account is suspended. You cannot log in.', 403);
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'fullname' => $user['fullname'],
        'username' => $user['username'],
        'role' => $user['role'],
        'status' => $user['status'],
    ];

    json_ok(['message' => 'Logged in successfully', 'role' => $user['role']]);
}


// Session info for frontend
if ($action === 'me') {
    $user = current_user();
    if ($user) {
        json_ok(['user' => $user]);
    } else {
        json_ok(['user' => null]);
    }
}



// Superadmin: create admin user
if ($action === 'create_admin') {
    require_role('superadmin');

    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullname === '' || $username === '' || $password === '') {
        json_error('Fullname, username and password are required');
    }

    // Ensure username unique
    $exists = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $exists->execute([$username]);
    if ($exists->fetch()) {
        json_error('Username already exists');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users(fullname, username, password, role, status, date_added) VALUES(?,?,?,?,?,NOW())');
    $stmt->execute([$fullname, $username, $hash, 'admin', 'active']);

    json_ok(['message' => 'Admin account created']);
}

// Superadmin: suspend/activate user
if ($action === 'set_user_status') {
    require_role('superadmin');
    $userId = (int)($_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$userId || !in_array($status, ['active', 'suspended'])) {
        json_error('Invalid input');
    }
    $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ? AND role = "admin"');
    $stmt->execute([$status, $userId]);
    json_ok(['message' => 'User status updated']);
}

// List users (superadmin only)
if ($action === 'list_users') {
    require_role('superadmin');
    $stmt = $pdo->query('SELECT id, fullname, username, role, status, date_added FROM users ORDER BY id DESC');
    $users = $stmt->fetchAll();
    json_ok(['users' => $users]);
}

// Products
if ($action === 'add_product') {
    require_role(['admin', 'superadmin']);
    $name = trim($_POST['product_name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($name === '' || $price === '' || !is_numeric($price)) {
        json_error('Product name and numeric price are required');
    }

    $imageFileName = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = realpath(__DIR__ . '/../../upload');
        if ($uploadDir === false) {
            json_error('Upload directory not found on server');
        }
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $safeExt = strtolower(preg_replace('/[^a-z0-9]/i', '', $ext));
        $newName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . ($safeExt ? ('.' . $safeExt) : '');
        $target = $uploadDir . DIRECTORY_SEPARATOR . $newName;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            json_error('Failed to upload image');
        }
        $imageFileName = $newName;
    }

    $user = current_user();
    $stmt = $pdo->prepare('INSERT INTO products(product_name, description, price, image, added_by, date_added) VALUES(?,?,?,?,?,NOW())');
    $stmt->execute([$name, $description, (float)$price, $imageFileName, $user['id']]);
    json_ok(['message' => 'Product added']);
}

if ($action === 'list_products') {
    require_auth();
    $stmt = $pdo->query('SELECT p.*, u.fullname AS added_by_name FROM products p LEFT JOIN users u ON u.id = p.added_by ORDER BY p.id DESC');
    $products = $stmt->fetchAll();
    json_ok(['products' => $products]);
}

// Public list of products (no auth required)
if ($action === 'list_products_public') {
    $stmt = $pdo->query('SELECT id, product_name, price, image, description FROM products ORDER BY id DESC');
    $products = $stmt->fetchAll();
    json_ok(['products' => $products]);
}

// Orders
if ($action === 'create_order') {
    require_role(['admin', 'superadmin']);
    $itemsJson = $_POST['items'] ?? '';
    $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : null;
    $items = json_decode($itemsJson, true);
    if (!is_array($items) || count($items) === 0) {
        json_error('Order items required');
    }

    // Calculate totals
    $orderTotal = 0.0;
    foreach ($items as $it) {
        $qty = (int)($it['qty'] ?? 0);
        $price = (float)($it['price'] ?? 0);
        if ($qty <= 0 || $price < 0) json_error('Invalid item entry');
        $orderTotal += $qty * $price;
    }

    $changeAmount = $paymentAmount !== null ? ($paymentAmount - $orderTotal) : null;

    try {
        $pdo->beginTransaction();
        $user = current_user();
        $stmt = $pdo->prepare('INSERT INTO orders(user_id, total, payment_amount, change_amount, status, date_added) VALUES(?,?,?,?,?,NOW())');
        $stmt->execute([$user['id'], $orderTotal, $paymentAmount, $changeAmount, 'pending']);
        $orderId = (int)$pdo->lastInsertId();

        $oi = $pdo->prepare('INSERT INTO order_items(order_id, product_name, product_id, qty, price, subtotal) VALUES(?,?,?,?,?,?)');
        foreach ($items as $it) {
            $qty = (int)$it['qty'];
            $price = (float)$it['price'];
            $name = (string)$it['product_name'];
            $pid = isset($it['product_id']) ? (int)$it['product_id'] : 0;
            $subtotal = $qty * $price;
            $oi->execute([$orderId, $name, $pid, $qty, $price, $subtotal]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        json_error('Failed to create order');
    }

    json_ok(['message' => 'Order created', 'total' => $orderTotal, 'change' => $changeAmount ?? 0, 'order_id' => $orderId]);
}

// Public orders (no login) for customer checkout
if ($action === 'create_order_public') {
    $itemsJson = $_POST['items'] ?? '';
    $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : null;
    $items = json_decode($itemsJson, true);
    if (!is_array($items) || count($items) === 0) {
        json_error('Order items required');
    }

    $orderTotal = 0.0;
    foreach ($items as $it) {
        $qty = (int)($it['qty'] ?? 0);
        $price = (float)($it['price'] ?? 0);
        if ($qty <= 0 || $price < 0) json_error('Invalid item entry');
        $orderTotal += $qty * $price;
    }
    $changeAmount = $paymentAmount !== null ? ($paymentAmount - $orderTotal) : null;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO orders(user_id, total, payment_amount, change_amount, status, date_added) VALUES(?,?,?,?,?,NOW())');
        $stmt->execute([null, $orderTotal, $paymentAmount, $changeAmount, 'pending']);
        $orderId = (int)$pdo->lastInsertId();

        $oi = $pdo->prepare('INSERT INTO order_items(order_id, product_name, product_id, qty, price, subtotal) VALUES(?,?,?,?,?,?)');
        foreach ($items as $it) {
            $qty = (int)$it['qty'];
            $price = (float)$it['price'];
            $name = (string)$it['product_name'];
            $pid = isset($it['product_id']) ? (int)$it['product_id'] : 0;
            $subtotal = $qty * $price;
            $oi->execute([$orderId, $name, $pid, $qty, $price, $subtotal]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        json_error('Failed to create order');
    }

    json_ok(['message' => 'Order placed', 'total' => $orderTotal, 'change' => $changeAmount ?? 0, 'order_id' => $orderId]);
}
// Reports JSON with optional date filters
if ($action === 'report') {
    require_role(['admin', 'superadmin']);
    $dateStart = $_GET['date_start'] ?? $_POST['date_start'] ?? '';
    $dateEnd = $_GET['date_end'] ?? $_POST['date_end'] ?? '';

    $conditions = [];
    $params = [];
    if ($dateStart !== '') {
        $conditions[] = 'DATE(o.date_added) >= DATE(?)';
        $params[] = $dateStart;
    }
    if ($dateEnd !== '') {
        $conditions[] = 'DATE(o.date_added) <= DATE(?)';
        $params[] = $dateEnd;
    }
    $where = count($conditions) ? ('WHERE ' . implode(' AND ', $conditions)) : '';

    $sql = "SELECT o.id, o.total, o.payment_amount, o.change_amount, o.status, o.date_added,
                   u.fullname AS cashier,
                   ru.fullname AS received_by_name, o.received_at
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            LEFT JOIN users ru ON ru.id = o.received_by
            $where ORDER BY o.date_added DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $sum = 0.0;
    foreach ($orders as $o) { $sum += (float)$o['total']; }

    json_ok(['orders' => $orders, 'total_sum' => $sum]);
}

// Order details (items by order)
if ($action === 'order_details') {
    require_role(['admin', 'superadmin']);
    $orderId = (int)($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
    if (!$orderId) {
        json_error('order_id is required');
    }
    $stmt = $pdo->prepare('SELECT product_name, product_id, qty, price, subtotal FROM order_items WHERE order_id = ? ORDER BY id ASC');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    json_ok(['items' => $items]);
}

// Update order status (admin/superadmin)
if ($action === 'update_order_status') {
    require_role(['admin', 'superadmin']);
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!$orderId || !in_array($status, ['pending','received'])) {
        json_error('Invalid input');
    }
    if ($status === 'received') {
        $user = current_user();
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, received_by = ?, received_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $user['id'], $orderId]);
    } else {
        $stmt = $pdo->prepare('UPDATE orders SET status = ?, received_by = NULL, received_at = NULL WHERE id = ?');
        $stmt->execute([$status, $orderId]);
    }
    json_ok(['message' => 'Order status updated']);
}

// PDF report placeholder
if ($action === 'report_pdf') {
    json_error('PDF generation not yet implemented', 501);
}

// Fallback
json_error('Unknown action', 404);
?>