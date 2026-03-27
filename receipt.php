<?php
require_once 'db.php';
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized: Please login.");
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$txn = $stmt->fetch();

if (!$txn) {
    die("Receipt not found");
}

$stmtSettings = $pdo->query("SELECT * FROM settings");
$settings = [];
foreach ($stmtSettings->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$storeName = $settings['store_name'] ?? 'ร้านอาหารของฉัน';

$items = json_decode($txn['items_json'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $txn['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* --- Screen Preview Style --- */
        body { 
            font-family: 'Sarabun', sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f1f5f9; 
            display: flex; 
            justify-content: center;
        }
        .receipt { 
            width: 72mm; /* 80mm paper - ~8mm margins */
            background: white; 
            padding: 5mm; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            font-size: 13px;
            color: #000;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        h2 { margin: 0 0 6px 0; font-size: 16px; }
        .divider { border: none; border-bottom: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 3px 0; font-size: 12px; }
        .text-right { text-align: right; }
        .total-row td { font-size: 14px; font-weight: 700; }

        /* --- 80mm Thermal Print Style --- */
        @media print {
            @page {
                size: 80mm auto;   /* 80mm width, auto height (continuous paper) */
                margin: 4mm 3mm;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
                display: block;
            }
            .receipt {
                box-shadow: none;
                width: 100%;
                max-width: 74mm;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="text-center">
            <h2><?php echo htmlspecialchars($storeName); ?></h2>
            <div>ใบเสร็จรับเงิน</div>
            <div>เสร็จสิ้น: <?php echo date('d/m/Y H:i:s', strtotime($txn['created_at'])); ?></div>
            <div>ออเดอร์: #<?php echo str_pad($txn['id'], 6, '0', STR_PAD_LEFT); ?></div>
        </div>
        
        <div class="divider"></div>
        
        <table>
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th class="text-center">จำนวน</th>
                    <th class="text-right">รวม</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="text-center"><?php echo $item['qty']; ?></td>
                    <td class="text-right"><?php echo number_format($item['price'] * $item['qty'], 2); ?></td>
                </tr>
                <?php
endforeach; ?>
            </tbody>
        </table>
        
        <div class="divider"></div>
        
        <table>
            <tr class="total-row">
                <td>ยอดชำระสุทธิ</td>
                <td class="text-right">฿<?php echo number_format($txn['total_amount'], 2); ?></td>
            </tr>
            <tr>
                <td>วิธีชำระ</td>
                <td class="text-right"><?php
if ($txn['payment_method'] == 'cash')
    echo 'เงินสด';
elseif ($txn['payment_method'] == 'promptpay')
    echo 'พร้อมเพย์';
elseif ($txn['payment_method'] == 'truemoney')
    echo 'ทรูมันนี่';
else
    echo $txn['payment_method'];
?></td>
            </tr>
        </table>
        
        <div class="divider"></div>
        <div class="text-center">ขอบคุณที่ใช้บริการ</div>
    </div>

    <script>
        // Wait for fonts then print (works both standalone and in iframe)
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
</body>
</html>
