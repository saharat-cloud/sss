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
    <title>System Settings - POS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <h1 class="page-title">ตั้งค่าระบบร้าน (Store Settings)</h1>

        <div class="card" style="max-width: 800px;">
            <h2 style="margin-bottom: 20px; font-size: 1.2rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">ข้อมูลร้าน (Store Info)</h2>
            <div class="form-group">
                <label>ชื่อร้านค้า (Store Name)</label>
                <input type="text" id="storeName" class="form-control" placeholder="ชื่อร้านที่จะแสดงในหน้าร้าน">
            </div>

            <h2 style="margin-top: 40px; margin-bottom: 20px; font-size: 1.2rem; color: var(--admin-primary); border-bottom: 1px solid var(--admin-border); padding-bottom: 10px;">การรับชำระเงิน (Payment Gateway)</h2>
            <div class="form-group">
                <label>เบอร์โทรศัพท์ / รหัสบัตรประชาชน (PromptPay ID)</label>
                <input type="text" id="promptpayId" class="form-control" placeholder="เช่น 0812345678" maxlength="13">
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 5px;">* จะใช้แสดงเป็น QR Code รับเงินโอนสำหรับบัญชีพร้อมเพย์ (API: promptpay.io)</p>
            </div>
            
            <div class="form-group">
                <label>เบอร์โทรศัพท์ TrueMoney Wallet</label>
                <input type="text" id="truemoneyPhone" class="form-control" placeholder="เช่น 0812345678" maxlength="10">
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 5px;">* ปัจจุบันให้โอนเข้าบัญชี TrueMoney เบอร์นี้ หากจะใช้ API องค์กรต้องติดต่อ TrueMoney โดยตรง</p>
            </div>

            <div style="margin-top: 30px;">
                <button class="btn btn-primary" onclick="saveSettings()"><i class="fas fa-save"></i> บันทึกการตั้งค่า</button>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const res = await fetch('../api.php?action=get_settings');
            const data = await res.json();
            
            if(data['store_name']) document.getElementById('storeName').value = data['store_name'];
            if(data['promptpay_id']) document.getElementById('promptpayId').value = data['promptpay_id'];
            if(data['truemoney_phone']) document.getElementById('truemoneyPhone').value = data['truemoney_phone'];
        });

        async function saveSettings() {
            const data = {
                'store_name': document.getElementById('storeName').value,
                'promptpay_id': document.getElementById('promptpayId').value,
                'truemoney_phone': document.getElementById('truemoneyPhone').value
            };

            const btn = document.querySelector('.btn-primary');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            btn.disabled = true;

            const res = await fetch('../api.php?action=save_settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            if (await res.json()) {
                Swal.fire('สำเร็จ', 'บันทึกการตั้งค่าเรียบร้อยแล้ว!', 'success');
            }
            btn.innerHTML = '<i class="fas fa-save"></i> บันทึกการตั้งค่า';
            btn.disabled = false;
        }
    </script>
</body>
</html>
