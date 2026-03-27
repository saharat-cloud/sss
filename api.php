<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
// api.php - API for product and transaction management
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$action = $_GET['action'] ?? '';

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$requiresLogin = ['save_transaction'];
$requiresAdmin = ['save_settings', 'add_product', 'delete_product', 'add_category', 'delete_category', 'edit_product'];

if (in_array($action, $requiresLogin) && !$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (in_array($action, $requiresAdmin) && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

switch ($action) {
    case 'get_products':
        try {
            $stmt = $pdo->query("SELECT p.*, c.requires_stock FROM products p LEFT JOIN categories c ON p.category = c.name ORDER BY p.category, p.name");
            echo json_encode($stmt->fetchAll());
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'save_transaction':
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            break;
        }

        try {
            $pdo->beginTransaction();

            $total_cost = 0;
            // deduct stock and calc total_cost
            $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmtProductInfo = $pdo->prepare("SELECT p.cost_price, c.requires_stock FROM products p LEFT JOIN categories c ON p.category = c.name WHERE p.id = ?");

            foreach ($data['items'] as $item) {
                // check if valid DB id (not a special custom price)
                if (isset($item['id']) && $item['id'] !== 'special' && is_numeric($item['id'])) {
                    $stmtProductInfo->execute([$item['id']]);
                    $pInfo = $stmtProductInfo->fetch();

                    if ($pInfo) {
                        $total_cost += ($pInfo['cost_price'] * $item['qty']);
                        if ($pInfo['requires_stock'] == 1) {
                            $stmtStock->execute([$item['qty'], $item['id']]);
                        }
                    }
                }
                else {
                    // special item, cost is 0
                    $total_cost += 0;
                }
            }

            $total_profit = $data['total_amount'] - $total_cost;

            // Log transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (total_amount, total_cost, total_profit, payment_method, items_json, staff_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['total_amount'],
                $total_cost,
                $total_profit,
                $data['payment_method'],
                json_encode($data['items']),
                $_SESSION['user_id'] ?? 0
            ]);
            $transaction_id = $pdo->lastInsertId();

            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $transaction_id]);
        }
        catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get_settings':
        try {
            $stmt = $pdo->query("SELECT * FROM settings");
            $settings = [];
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            echo json_encode($settings);
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'save_settings':
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value");
            // SQLite UPSERT syntax
            // Actually, SQLite < 3.24 doesn't support ON CONFLICT. For safe fallback:
            $stmtReplace = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");

            foreach ($data as $key => $value) {
                $stmtReplace->execute([$key, $value]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'add_product':
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $cost_price = $_POST['cost_price'] ?? 0;
        $discount_price = $_POST['discount_price'] ?? 0;
        $category = $_POST['category'] ?? '';
        $stock = $_POST['stock'] ?? 0;

        $sku = trim($_POST['sku'] ?? '');
        if (empty($sku)) {
            $sku = 'PRD-' . date('YmdHis') . rand(10, 99);
        }

        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $dest = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image_url = 'uploads/' . $filename;
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, price, cost_price, discount_price, category, stock, sku, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $cost_price, $discount_price, $category, $stock, $sku, $image_url]);
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'รหัส SKU นี้มีอยู่ในระบบแล้ว หรือ ' . $e->getMessage()]);
        }
        break;

    case 'edit_product':
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $price = $_POST['price'] ?? 0;
        $cost_price = $_POST['cost_price'] ?? 0;
        $discount_price = $_POST['discount_price'] ?? 0;
        $category = $_POST['category'] ?? '';
        $stock = $_POST['stock'] ?? 0;

        $sku = trim($_POST['sku'] ?? '');
        if (empty($sku)) {
            $sku = 'PRD-' . date('YmdHis') . rand(10, 99);
        }

        $image_url = '';
        $updateImageSql = "";
        $params = [$name, $price, $cost_price, $discount_price, $category, $stock, $sku];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $dest = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image_url = 'uploads/' . $filename;
                $updateImageSql = ", image_url=?";
                $params[] = $image_url;
            }
        }
        $params[] = $id;

        try {
            $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, cost_price=?, discount_price=?, category=?, stock=?, sku=? $updateImageSql WHERE id=?");
            $stmt->execute($params);
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'รหัส SKU นี้มีอยู่ในระบบแล้ว หรือ ' . $e->getMessage()]);
        }
        break;

    case 'delete_product':
        $id = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get_categories':
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY id");
            echo json_encode($stmt->fetchAll());
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'add_category':
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, requires_stock) VALUES (?, ?)");
            $stmt->execute([$data['name'], $data['requires_stock'] ?? 1]);
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete_category':
        $id = $_GET['id'] ?? 0;
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete_sales':
        $data = json_decode(file_get_contents('php://input'), true);
        $type = $data['type'] ?? '';
        $value = $data['value'] ?? '';

        try {
            if ($type === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $stmt = $pdo->prepare("DELETE FROM transactions WHERE DATE(created_at) = ?");
                $stmt->execute([$value]);
                echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            }
            else if ($type === 'month' && preg_match('/^\d{4}-\d{2}$/', $value)) {
                $stmt = $pdo->prepare("DELETE FROM transactions WHERE strftime('%Y-%m', created_at) = ?");
                $stmt->execute([$value]);
                echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            }
            else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid type or value']);
            }
        }
        catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
}
?>
