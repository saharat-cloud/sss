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
    <title>Manage Categories - POS Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .modal {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center; z-index: 100;
        }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 400px; max-width: 90%; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 class="page-title" style="margin: 0;">จัดการหมวดหมู่สินค้า (Categories)</h1>
            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่</button>
        </div>

        <div class="card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อหมวดหมู่</th>
                        <th>ระบบนับสต็อก</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="categoryList">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2 style="margin-bottom: 20px; color: var(--admin-primary);">เพิ่มหมวดหมู่ใหม่</h2>
            <div class="form-group">
                <label>ชื่อหมวดหมู่</label>
                <input type="text" id="cName" class="form-control" placeholder="เช่น อาหารจานเดียว">
            </div>
            
            <div class="form-group" style="margin-top: 15px;">
                <label>ต้องนับสต็อก (Requires Stock)</label>
                <select id="cRequiresStock" class="form-control">
                    <option value="1">ใช่ (ลดจำนวนเมื่อขาย)</option>
                    <option value="0">ไม่ (สำหรับสินค้าปรุงสุก / อาหาร)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                <button class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="saveCategory()">บันทึกข้อมูล</button>
            </div>
        </div>
    </div>

    <script>
        async function loadData() {
            const res = await fetch('../api.php?action=get_categories');
            const data = await res.json();
            const list = document.getElementById('categoryList');
            list.innerHTML = data.map(c => `
                <tr>
                    <td>#${c.id}</td>
                    <td style="font-weight: 600;">${c.name}</td>
                    <td>${c.requires_stock == 1 ? '<span style="color:var(--success)"><i class="fas fa-check-circle"></i> เปิดใช้งาน</span>' : '<span style="color:var(--text-secondary)"><i class="fas fa-times-circle"></i> ไม่ได้ใช้งาน</span>'}</td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteCategory(${c.id})"><i class="fas fa-trash"></i> ลบ</button>
                    </td>
                </tr>
            `).join('');
        }

        function openModal() { document.getElementById('addModal').classList.add('active'); }
        function closeModal() { document.getElementById('addModal').classList.remove('active'); }

        async function saveCategory() {
            const name = document.getElementById('cName').value;
            const requires_stock = document.getElementById('cRequiresStock').value;

            if(!name) return Swal.fire('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อหมวดหมู่!', 'warning');

            const res = await fetch('../api.php?action=add_category', {
                method: 'POST', body: JSON.stringify({name, requires_stock})
            });
            
            if(await res.json()) {
                closeModal();
                loadData();
                document.getElementById('cName').value = '';
                document.getElementById('cRequiresStock').value = '1';
            }
        }

        async function deleteCategory(id) {
            const result = await Swal.fire({
                title: 'ยืนยันลบหมวดหมู่นี้?',
                text: "หากมีสินค้าในหมวดนี้ สินค้านั้นอาจไม่แสดงในหน้าร้าน",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ลบทันที',
                cancelButtonText: 'ยกเลิก'
            });
            if(result.isConfirmed) {
                const res = await fetch(`../api.php?action=delete_category&id=${id}`);
                if(await res.json()) loadData();
            }
        }

        document.addEventListener('DOMContentLoaded', loadData);
    </script>
</body>
</html>
