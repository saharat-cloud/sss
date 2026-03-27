<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();
// db.php - Database connection and initialization
$db_file = __DIR__ . '/database.db';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create products table with stock tracking
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        price REAL NOT NULL,
        category TEXT,
        stock INTEGER DEFAULT 0,
        image_url TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        total_amount REAL NOT NULL,
        payment_method TEXT NOT NULL,
        items_json TEXT NOT NULL,
        staff_id INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key TEXT PRIMARY KEY,
        setting_value TEXT NOT NULL
    )");

    // Create staff/users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS staff (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        role TEXT DEFAULT 'staff',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT UNIQUE NOT NULL,
        requires_stock INTEGER DEFAULT 1
    )");

    // Initialize Default Settings if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM settings");
    if ($stmt->fetchColumn() == 0) {
        $default_settings = [
            ['promptpay_id', '0812345678'],
            ['store_name', 'ร้านอาหารของฉัน']
        ];
        $insert_setting = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($default_settings as $setting) {
            $insert_setting->execute($setting);
        }
    }

    // Initialize Categories if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $sample_categories = [
            ['อาหาร', 0],
            ['ขนม', 1],
            ['เครื่องดื่ม', 1],
            ['ของใช้', 1]
        ];
        $insert_cat = $pdo->prepare("INSERT INTO categories (name, requires_stock) VALUES (?, ?)");
        foreach ($sample_categories as $cat) {
            $insert_cat->execute($cat);
        }
    }

    // Initialize Default Admin Staff if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM staff");
    if ($stmt->fetchColumn() == 0) {
        $insert_staff = $pdo->prepare("INSERT INTO staff (username, password, role) VALUES (?, ?, ?)");
        // Default password is 'admin123' (hash it for basic security)
        $insert_staff->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    }

    // Insert sample data if products table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $sample_products = [
            ['ข้าวผัดกะเพราไข่ดาว', 50, 'อาหารตามสั่ง', 100],
            ['ข้าวผัดหมู', 45, 'อาหารตามสั่ง', 50],
            ['ก๋วยเตี๋ยวเรือ', 40, 'เส้น', 100],
            ['ชานมไข่มุก', 35, 'เครื่องดื่ม', 30],
            ['น้ำเปล่า', 10, 'เครื่องดื่ม', 200]
        ];
        $insert_stmt = $pdo->prepare("INSERT INTO products (name, price, category, stock) VALUES (?, ?, ?, ?)");
        foreach ($sample_products as $product) {
            $insert_stmt->execute($product);
        }
    }

    // Add stock column to existing database if it doesn't exist (Migration)
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN stock INTEGER DEFAULT 0");
    }
    catch (PDOException $e) {
    // Column already exists, ignore
    }

    // Add staff_id column to existing transactions table (Migration)
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN staff_id INTEGER DEFAULT 0");
    }
    catch (PDOException $e) { /* Column already exists, ignore */
    }

    // Add cost_price column to existing products table (Migration)
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN cost_price REAL DEFAULT 0");
    }
    catch (PDOException $e) { /* Column already exists, ignore */
    }

    // Add total_cost column to existing transactions table (Migration)
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN total_cost REAL DEFAULT 0");
    }
    catch (PDOException $e) { /* Column already exists, ignore */
    }

    // Add total_profit column to existing transactions table (Migration)
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN total_profit REAL DEFAULT 0");
    }
    catch (PDOException $e) { /* Column already exists, ignore */
    }


}
catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
