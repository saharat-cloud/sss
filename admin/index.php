<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch stats
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT SUM(total_amount) FROM transactions");
$totalSales = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
$totalOrders = $stmt->fetchColumn() ?: 0;

$lowStockStmt = $pdo->query("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category = c.name WHERE p.stock <= 3 AND c.requires_stock = 1");
$lowStock = $lowStockStmt->fetchColumn() ?: 0;

$recentTransactions = $pdo->query("SELECT id, total_amount, payment_method, created_at FROM transactions ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - POS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: var(--admin-bg);
            color: var(--admin-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-details h3 {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        .stat-details p {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h1 class="page-title">ภาพรวมระบบ (Dashboard)</h1>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #10b981; background: rgba(16, 185, 129, 0.1);"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-details">
                    <h3>ยอดขายรวม</h3>
                    <p>฿<?php echo number_format($totalSales, 2); ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #3b82f6; background: rgba(59, 130, 246, 0.1);"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-details">
                    <h3>จำนวนบิล</h3>
                    <p><?php echo $totalOrders; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #8b5cf6; background: rgba(139, 92, 246, 0.1);"><i class="fas fa-box"></i></div>
                <div class="stat-details">
                    <h3>สินค้าในระบบ</h3>
                    <p><?php echo $totalProducts; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--danger); background: rgba(239, 68, 68, 0.1);"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-details">
                    <h3>สินค้าใกล้หมดสต็อก</h3>
                    <p><?php echo $lowStock; ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">บิลล่าสุด (Recent Transactions)</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>รหัสบิล</th>
                        <th>วันที่ / เวลา</th>
                        <th>วิธีชำระเงิน</th>
                        <th>ยอดรวม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr><td colspan="4" style="text-align: center;">ไม่มีข้อมูลการขาย</td></tr>
                    <?php
else: ?>
                        <?php foreach ($recentTransactions as $txn): ?>
                            <tr>
                                <td>#<?php echo $txn['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($txn['created_at'])); ?></td>
                                <td>
                                    <?php
        if ($txn['payment_method'] === 'cash')
            echo '<span style="color:#10b981"><i class="fas fa-money-bill"></i> เงินสด</span>';
        elseif ($txn['payment_method'] === 'promptpay')
            echo '<span style="color:#003399"><i class="fas fa-qrcode"></i> พร้อมเพย์</span>';
        elseif ($txn['payment_method'] === 'truemoney')
            echo '<span style="color:#ff8c00"><i class="fas fa-wallet"></i> ทรูมันนี่</span>';
        else
            echo $txn['payment_method'];
?>
                                </td>
                                <td style="font-weight: bold;">฿<?php echo number_format($txn['total_amount'], 2); ?></td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
