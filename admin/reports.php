<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Daily Report
$dateFilter = $_GET['date'] ?? '';
$dailyQuery = "
    SELECT 
        DATE(created_at) as date, 
        COUNT(id) as total_orders, 
        SUM(total_amount) as revenue, 
        SUM(total_cost) as cost, 
        SUM(total_profit) as profit 
    FROM transactions 
";
if ($dateFilter) {
    $dailyQuery .= " WHERE DATE(created_at) = :date ";
}
$dailyQuery .= " GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 30";
$stmtDaily = $pdo->prepare($dailyQuery);
if ($dateFilter)
    $stmtDaily->execute(['date' => $dateFilter]);
else
    $stmtDaily->execute();
$dailyReports = $stmtDaily->fetchAll();

// Monthly Report
$monthFilter = $_GET['month'] ?? '';
$monthlyQuery = "
    SELECT 
        strftime('%Y-%m', created_at) as month, 
        COUNT(id) as total_orders, 
        SUM(total_amount) as revenue, 
        SUM(total_cost) as cost, 
        SUM(total_profit) as profit 
    FROM transactions 
";
if ($monthFilter) {
    $monthlyQuery .= " WHERE strftime('%Y-%m', created_at) = :month ";
}
$monthlyQuery .= " GROUP BY strftime('%Y-%m', created_at) ORDER BY month DESC";
$stmtMonthly = $pdo->prepare($monthlyQuery);
if ($monthFilter)
    $stmtMonthly->execute(['month' => $monthFilter]);
else
    $stmtMonthly->execute();
$monthlyReports = $stmtMonthly->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Reports - POS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 12px 24px; border: none; background: var(--admin-border); border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 1.1rem; color: var(--text-secondary); transition: 0.2s; }
        .tab-btn:hover { background: #cbd5e1; }
        .tab-btn.active { background: var(--admin-primary); color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .text-right { text-align: right; }
        .positive { color: #16a34a; font-weight: bold; }
        .negative { color: #dc2626; font-weight: bold; }
        @media print {
            .sidebar, .tabs, button { display: none !important; }
            .main-content { padding: 0; width: 100%; }
            .card { box-shadow: none; padding: 0; }
            .admin-table th, .admin-table td { border-bottom: 1px solid #000; color: #000 !important; }
            .tab-content { display: block !important; margin-bottom: 40px; }
            #monthly { page-break-before: always; }
            h1 { text-align: center; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 class="page-title" style="margin: 0;">รายงานสรุปยอดขาย (Sales Reports)</h1>
            <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> พิมพ์รายงานนี้</button>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab(this, 'daily')">ยอดขายรายวัน</button>
            <button class="tab-btn" onclick="switchTab(this, 'monthly')">ยอดขายรายเดือน</button>
        </div>

        <div id="daily" class="tab-content active">
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: white; padding: 15px; border-radius: 10px;">
                <label style="font-weight: 600;">เลือกวันที่:</label>
                <input type="date" id="dateFilter" class="form-control" style="width: auto; margin: 0;" value="<?php echo htmlspecialchars($dateFilter); ?>">
                <button class="btn btn-primary" onclick="applyFilter('date')"><i class="fas fa-search"></i> ค้นหา</button>
                <?php if ($dateFilter): ?>
                    <button class="btn btn-secondary" onclick="window.location.href='reports.php'"><i class="fas fa-times"></i> ล้างตัวกรอง</button>
                <?php
endif; ?>
            </div>
            <div class="card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>วันที่ (Date)</th>
                            <th class="text-right">จำนวนบิล</th>
                            <th class="text-right">ยอดขายรวม (Revenue)</th>
                            <th class="text-right">ต้นทุนรวม (Cost)</th>
                            <th class="text-right">กำไรสุทธิ (Profit)</th>
                            <th style="width: 100px; text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dailyReports)): ?>
                            <tr><td colspan="6" style="text-align: center;">ไม่มีข้อมูล</td></tr>
                        <?php
else: ?>
                            <?php foreach ($dailyReports as $row):
        $profitClass = $row['profit'] >= 0 ? 'positive' : 'negative';
?>
                            <tr>
                                <td><i class="fas fa-calendar-day" style="color:#94a3b8; margin-right:8px;"></i> <?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                                <td class="text-right"><?php echo $row['total_orders']; ?></td>
                                <td class="text-right" style="color: var(--primary); font-weight: bold;">฿<?php echo number_format($row['revenue'], 2); ?></td>
                                <td class="text-right">฿<?php echo number_format($row['cost'], 2); ?></td>
                                <td class="text-right <?php echo $profitClass; ?>">฿<?php echo number_format($row['profit'], 2); ?></td>
                                <td style="text-align: center;">
                                    <button class="btn btn-danger btn-sm" onclick="resetSales('date', '<?php echo $row['date']; ?>', 'ยอดขายวันที่ <?php echo date('d/m/Y', strtotime($row['date'])); ?>')">
                                        <i class="fas fa-trash-alt"></i> รีเซ็ต
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
        </div>

        <div id="monthly" class="tab-content">
            <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: white; padding: 15px; border-radius: 10px;">
                <label style="font-weight: 600;">เลือกเดือน:</label>
                <input type="month" id="monthFilter" class="form-control" style="width: auto; margin: 0;" value="<?php echo htmlspecialchars($monthFilter); ?>">
                <button class="btn btn-primary" onclick="applyFilter('month')"><i class="fas fa-search"></i> ค้นหา</button>
                <?php if ($monthFilter): ?>
                    <button class="btn btn-secondary" onclick="window.location.href='reports.php'"><i class="fas fa-times"></i> ล้างตัวกรอง</button>
                <?php
endif; ?>
            </div>
            <div class="card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>เดือน/ปี (Month/Year)</th>
                            <th class="text-right">จำนวนบิล</th>
                            <th class="text-right">ยอดขายรวม (Revenue)</th>
                            <th class="text-right">ต้นทุนรวม (Cost)</th>
                            <th class="text-right">กำไรสุทธิ (Profit)</th>
                            <th style="width: 100px; text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlyReports)): ?>
                            <tr><td colspan="6" style="text-align: center;">ไม่มีข้อมูล</td></tr>
                        <?php
else: ?>
                            <?php foreach ($monthlyReports as $row):
        $profitClass = $row['profit'] >= 0 ? 'positive' : 'negative';
        $monthYear = date('F Y', strtotime($row['month'] . '-01'));
?>
                            <tr>
                                <td><i class="fas fa-calendar-alt" style="color:#94a3b8; margin-right:8px;"></i> <?php echo $monthYear; ?></td>
                                <td class="text-right"><?php echo $row['total_orders']; ?></td>
                                <td class="text-right" style="color: var(--primary); font-weight: bold;">฿<?php echo number_format($row['revenue'], 2); ?></td>
                                <td class="text-right">฿<?php echo number_format($row['cost'], 2); ?></td>
                                <td class="text-right <?php echo $profitClass; ?>">฿<?php echo number_format($row['profit'], 2); ?></td>
                                <td style="text-align: center;">
                                    <button class="btn btn-danger btn-sm" onclick="resetSales('month', '<?php echo $row['month']; ?>', 'ยอดขายเดือน <?php echo $monthYear; ?>')">
                                        <i class="fas fa-trash-alt"></i> รีเซ็ต
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
        </div>
    </main>

    <script>
        function switchTab(btn, tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }

        function applyFilter(type) {
            let val;
            if (type === 'date') val = document.getElementById('dateFilter').value;
            if (type === 'month') val = document.getElementById('monthFilter').value;
            if (!val) return;
            window.location.href = `reports.php?${type}=${val}`;
        }

        async function resetSales(type, value, label) {
            const result = await Swal.fire({
                title: 'ยืนยันการล้างข้อมูล?',
                html: `คุณกำลังจะล้างข้อมูล <b>${label}</b><br><span style="color:red;">การกระทำนี้ไม่สามารถย้อนกลับได้!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ยืนยัน ล้างข้อมูล',
                cancelButtonText: 'ยกเลิก'
            });

            if (result.isConfirmed) {
                try {
                    const res = await fetch('../api.php?action=delete_sales', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ type, value })
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        await Swal.fire('สำเร็จ!', `ลบข้อมูลประวัติการขายไปจำนวน ${data.deleted} รายการ`, 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('ผิดพลาด', data.error || 'ไม่สามารถลบข้อมูลได้', 'error');
                    }
                } catch (e) {
                    Swal.fire('ผิดพลาด', 'เชื่อมต่อเซิร์ฟเวอร์ไม่ได้', 'error');
                }
            }
        }
    </script>
</body>
</html>
