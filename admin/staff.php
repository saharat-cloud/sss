<?php
require_once '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Staff Management - POS Admin</title>
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
            <h1 class="page-title" style="margin: 0;">จัดการพนักงาน (Staff)</h1>
            <button class="btn btn-primary" onclick="Swal.fire('ระบบตัวอย่าง', 'Demo: ในระบบใช้งานจริงจะมีปุ่มเปิด Modal ให้เพิ่มรหัสพนักงานใหม่', 'info')"><i class="fas fa-user-plus"></i> เพิ่มพนักงาน</button>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px; font-size: 1.1rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">รายชื่อพนักงาน</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อบัญชีผู้ใช้ (Username)</th>
                        <th>ระดับ (Role)</th>
                        <th>วันที่เพิ่ม</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#1</td>
                        <td style="font-weight: 600;">admin</td>
                        <td><span style="background: #e0e7ff; color: #4338ca; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">ผู้ดูแลระบบ (Admin)</span></td>
                        <td>24/03/2026</td>
                        <td>
                            <button class="btn btn-secondary"><i class="fas fa-edit"></i> แก้ไข</button>
                        </td>
                    </tr>
                    <tr>
                        <td>#2</td>
                        <td style="font-weight: 600;">cashier_1</td>
                        <td><span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: bold;">พนักงานขาย (Staff)</span></td>
                        <td>24/03/2026</td>
                        <td>
                            <button class="btn btn-secondary"><i class="fas fa-edit"></i> แก้ไข</button>
                            <button class="btn btn-danger"><i class="fas fa-trash"></i> ลบ</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top: 15px; font-size: 0.85rem; color: var(--text-secondary);">* หมายเหตุ: หน้านี้แสดงข้อมูลตัวอย่าง (Mockup) สำหรับสิทธิ์พนักงาน หากต้องการเพิ่มระบบ Login จะต้องเชื่อม Backend Authentication แบบเต็มรูปแบบเพิ่มเติม</p>
        </div>
    </main>
</body>
</html>
