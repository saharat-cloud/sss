<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM transactions ORDER BY created_at DESC");
$sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Sales History - POS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 class="page-title" style="margin: 0;">ประวัติการขาย (Sales History)</h1>
        </div>

        <div class="card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>เลขที่ใบเสร็จ</th>
                        <th>วันที่และเวลา</th>
                        <th>วิธีชำระเงิน</th>
                        <th>ยอดรวม</th>
                        <th>รายการสินค้า (Items)</th>
                        <th>พิมพ์</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="6" style="text-align: center;">ไม่มีข้อมูลประวัติการขาย</td></tr>
                    <?php
else: ?>
                        <?php foreach ($sales as $sale):
        $items = json_decode($sale['items_json'], true);
        $itemCount = is_array($items) ? array_sum(array_column($items, 'qty')) : 0;
?>
                            <tr>
                                <td>#<?php echo str_pad($sale['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($sale['created_at'])); ?></td>
                                <td>
                                    <?php
        if ($sale['payment_method'] === 'cash')
            echo '<span class="badge" style="background:#d1fae5; color:#065f46;"><i class="fas fa-money-bill"></i> เงินสด</span>';
        elseif ($sale['payment_method'] === 'promptpay')
            echo '<span class="badge" style="background:#e0e7ff; color:#3730a3;"><i class="fas fa-qrcode"></i> พร้อมเพย์</span>';
        elseif ($sale['payment_method'] === 'truemoney')
            echo '<span class="badge" style="background:#ffedd5; color:#9a3412;"><i class="fas fa-wallet"></i> ทรูมันนี่</span>';
        else
            echo $sale['payment_method'];
?>
                                </td>
                                <td style="font-weight: bold; color: var(--admin-primary);">฿<?php echo number_format($sale['total_amount'], 2); ?></td>
                                <td><?php echo $itemCount; ?> ชิ้น</td>
                                <td>
                                    <button class="btn btn-secondary" onclick="window.open('../receipt.php?id=<?php echo $sale['id']; ?>', 'receipt', 'width=400,height=600')">
                                        <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                                    </button>
                                </td>
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
