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
    <title>Manage Menu & Stock - POS Admin</title>
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
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 600px; max-width: 90%; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1 class="page-title" style="margin: 0;">จัดการสินค้าและสต็อก (Menu & Stock)</h1>
            <button class="btn btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> เพิ่มเมนูใหม่</button>
        </div>

        <div class="card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อเมนู</th>
                        <th>หมวดหมู่</th>
                        <th>ราคาต้นทุน</th>
                        <th>ราคาขาย/ลดพิเศษ</th>
                        <th>คงเหลือ (Stock)</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="productList">
                    <!-- Loaded via JS -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add/Edit Product Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 20px; color: var(--admin-primary);">เพิ่มเมนู/สินค้าใหม่</h2>
            <input type="hidden" id="pId">
            <div class="form-group">
                <label>ชื่อเมนู</label>
                <input type="text" id="pName" class="form-control" placeholder="ชื่อเมนู...">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>ราคาต้นทุน (Cost Price)</label>
                    <input type="number" id="pCostPrice" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>ราคาขายปลีก (Selling Price)</label>
                    <input type="number" id="pPrice" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>ราคาลดพิเศษ (Discount Price) <span style="font-size:0.8rem;color:#64748b;">(0 = ไม่ลด)</span></label>
                    <input type="number" id="pDiscountPrice" class="form-control" placeholder="0" value="0">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>รหัสสินค้า (SKU) <span style="font-size:0.8rem;color:#64748b;">(เว้นว่างเพื่อสร้างอัตโนมัติ)</span></label>
                    <input type="text" id="pSku" class="form-control" placeholder="PRD-12345">
                </div>
                <div class="form-group">
                    <label>รูปภาพ (Image)</label>
                    <input type="file" id="pImage" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>จำนวนสต็อก</label>
                    <input type="number" id="pStock" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>หมวดหมู่</label>
                    <select id="pCategory" class="form-control">
                        <!-- Loaded dynamically -->
                    </select>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button class="btn btn-secondary" onclick="closeModal()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="saveProduct()">บันทึกข้อมูล</button>
            </div>
        </div>
    </div>

    <script>
        let categories = [];
        let products = [];

        async function loadCategories() {
            const res = await fetch('../api.php?action=get_categories');
            categories = await res.json();
            const select = document.getElementById('pCategory');
            select.innerHTML = categories.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
        }

        async function loadData() {
            const res = await fetch('../api.php?action=get_products');
            products = await res.json();
            const list = document.getElementById('productList');
            list.innerHTML = products.map(p => {
                const isFood = categories.find(c => c.name === p.category && c.requires_stock == 0);
                let stockDisplay = '';
                if(isFood) {
                    stockDisplay = `<span style="color:#64748b;">(ไม่ต้องนับ)</span>`;
                } else {
                    const stockColor = p.stock <= 3 ? 'color: red; font-weight: bold;' : 'color: green; font-weight: bold;';
                    stockDisplay = `<span style="${stockColor}">${p.stock}</span>`;
                }
                
                return `
                <tr>
                    <td>#${p.id}</td>
                    <td style="font-weight: 600;">${p.name}</td>
                    <td>${p.category}</td>
                    <td>฿${parseFloat(p.cost_price || 0).toFixed(2)}</td>
                    <td>${p.discount_price > 0 ? `<span style="text-decoration:line-through; color:#94a3b8; font-size:0.85rem;">฿${parseFloat(p.price).toFixed(2)}</span> <span style="color:var(--danger); font-weight:700;">฿${parseFloat(p.discount_price).toFixed(2)}</span>` : `฿${parseFloat(p.price).toFixed(2)}`}</td>
                    <td>${stockDisplay}</td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick='editProduct(${JSON.stringify(p).replace(/'/g, "&#39;")})'><i class="fas fa-edit"></i> แก้ไข</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})"><i class="fas fa-trash"></i> ลบ</button>
                    </td>
                </tr>
            `}).join('');
        }

        function openModal(isEdit = false) { 
            if(!isEdit) {
                document.getElementById('modalTitle').innerText = 'เพิ่มเมนู/สินค้าใหม่';
                document.getElementById('pId').value = '';
                document.getElementById('pName').value = '';
                document.getElementById('pCostPrice').value = '';
                document.getElementById('pPrice').value = '';
                document.getElementById('pDiscountPrice').value = '0';
                document.getElementById('pStock').value = '';
                document.getElementById('pSku').value = '';
                document.getElementById('pImage').value = '';
            }
            document.getElementById('addModal').classList.add('active'); 
        }
        function closeModal() { document.getElementById('addModal').classList.remove('active'); }

        function editProduct(p) {
            document.getElementById('modalTitle').innerText = 'แก้ไขเมนู: ' + p.name;
            document.getElementById('pId').value = p.id;
            document.getElementById('pName').value = p.name;
            document.getElementById('pCostPrice').value = p.cost_price || 0;
            document.getElementById('pPrice').value = p.price;
            document.getElementById('pDiscountPrice').value = p.discount_price || 0;
            document.getElementById('pStock').value = p.stock;
            document.getElementById('pSku').value = p.sku || '';
            document.getElementById('pImage').value = '';
            document.getElementById('pCategory').value = p.category;
            openModal(true);
        }

        async function saveProduct() {
            const id = document.getElementById('pId').value;
            const name = document.getElementById('pName').value;
            const cost_price = document.getElementById('pCostPrice').value;
            const price = document.getElementById('pPrice').value;
            const discount_price = document.getElementById('pDiscountPrice').value;
            const stock = document.getElementById('pStock').value;
            const category = document.getElementById('pCategory').value;
            const sku = document.getElementById('pSku').value;
            const imageFile = document.getElementById('pImage').files[0];

            if(!name || !price || !category) return Swal.fire('ข้อมูลไม่ครบ', 'กรอกข้อมูลให้ครบถ้วน!', 'warning');

            const action = id ? 'edit_product' : 'add_product';
            let formData = new FormData();
            formData.append('name', name);
            formData.append('cost_price', cost_price);
            formData.append('price', price);
            formData.append('discount_price', discount_price);
            formData.append('stock', stock);
            formData.append('category', category);
            formData.append('sku', sku);
            if (id) formData.append('id', id);
            if (imageFile) formData.append('image', imageFile);

            const res = await fetch(`../api.php?action=${action}`, {
                method: 'POST', body: formData
            });
            const data = await res.json();
            if(data.success) {
                Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อย', 'success');
                closeModal();
                loadData();
            } else {
                Swal.fire('ผิดพลาด', data.error || 'เกิดปัญหาในการบันทึก', 'error');
            }
        }

        async function deleteProduct(id) {
            const result = await Swal.fire({
                title: 'ยืนยันลบสินค้านี้?',
                text: "ลบแล้วไม่สามารถกู้คืนได้",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ลบทันที',
                cancelButtonText: 'ยกเลิก'
            });
            if(result.isConfirmed) {
                const res = await fetch(`../api.php?action=delete_product&id=${id}`);
                const data = await res.json();
                if(data.success) {
                    Swal.fire('ลบสำเร็จ', 'ข้อมูลถูกลบแล้ว', 'success');
                    loadData();
                } else {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถลบได้', 'error');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            await loadCategories();
            await loadData();
        });
    </script>
</body>
</html>
