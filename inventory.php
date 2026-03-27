<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - ระบบ POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .inventory-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 30px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            width: 95%;
            overflow-y: auto;
            max-height: 90vh;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-secondary); }
        .inventory-input {
            width: 100%;
            padding: 12px;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            outline: none;
        }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .product-table th, .product-table td {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid var(--glass-border);
        }
        .product-table th { color: var(--text-secondary); font-weight: 600; }
        .btn-add {
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-delete {
            color: var(--danger);
            cursor: pointer;
            background: none;
            border: none;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-item" onclick="location.href='index.php'" title="ขายสินค้า">
            <i class="fas fa-shopping-cart fa-lg"></i>
        </div>
        <div class="sidebar-item active" title="จัดการสินค้า">
            <i class="fas fa-box fa-lg"></i>
        </div>
        <div class="sidebar-item" title="ประวัติการขาย">
            <i class="fas fa-history fa-lg"></i>
        </div>
    </aside>

    <main style="flex: 1; overflow-y: auto;">
        <div class="inventory-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1>จัดการรายการอาหาร</h1>
                <button class="btn-add" onclick="toggleForm()">+ เพิ่มรายการใหม่</button>
            </div>

            <div id="addProductForm" style="display: none; margin-bottom: 40px; padding: 20px; border: 1px solid var(--glass-border); border-radius: 20px; background: rgba(51, 65, 85, 0.3);">
                <h3>เพิ่มข้อมูลสินค้า</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                    <div class="form-group">
                        <label>ชื่ออาหาร</label>
                        <input type="text" id="pName" class="inventory-input" placeholder="เช่น ข้าวกะเพราหมูสับ">
                    </div>
                    <div class="form-group">
                        <label>ราคา (บาท)</label>
                        <input type="number" id="pPrice" class="inventory-input" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <input type="text" id="pCategory" class="inventory-input" placeholder="เช่น อาหารจานเดียว, เครื่องดื่ม">
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button class="btn-add" onclick="saveProduct()">บันทึกข้อมูล</button>
                    <button class="btn-add" style="background: var(--card-bg);" onclick="toggleForm()">ยกเลิก</button>
                </div>
            </div>

            <table class="product-table">
                <thead>
                    <tr>
                        <th>ชื่อสินค้า</th>
                        <th>หมวดหมู่</th>
                        <th>ราคา</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody id="inventoryList">
                    <!-- Products will be loaded here -->
                </tbody>
            </table>
        </div>
    </main>

    <script>
        async function loadInventory() {
            const response = await fetch('api.php?action=get_products');
            const products = await response.json();
            const list = document.getElementById('inventoryList');
            list.innerHTML = products.map(p => `
                <tr>
                    <td>${p.name}</td>
                    <td>${p.category}</td>
                    <td>฿${parseFloat(p.price).toFixed(2)}</td>
                    <td>
                        <button class="btn-delete" onclick="deleteProduct(${p.id})">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function toggleForm() {
            const form = document.getElementById('addProductForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        async function saveProduct() {
            const name = document.getElementById('pName').value;
            const price = document.getElementById('pPrice').value;
            const category = document.getElementById('pCategory').value;

            if(!name || !price || !category) return alert('กรุณากรอกข้อมูลให้ครบถ้วน');

            const response = await fetch('api.php?action=add_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, price, category })
            });
            
            if(await response.json()) {
                alert('บันทึกสำเร็จ');
                toggleForm();
                loadInventory();
                // Clear fields
                document.getElementById('pName').value = '';
                document.getElementById('pPrice').value = '';
                document.getElementById('pCategory').value = '';
            }
        }

        async function deleteProduct(id) {
            if(!confirm('ยืนยันการลบรายการนี้?')) return;
            const response = await fetch(`api.php?action=delete_product&id=${id}`);
            if(await response.json()) {
                loadInventory();
            }
        }

        document.addEventListener('DOMContentLoaded', loadInventory);
    </script>
</body>
</html>
