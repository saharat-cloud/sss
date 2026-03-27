<?php
if (session_status() !== PHP_SESSION_ACTIVE)
    session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>POS System - Touch Optimized</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Sarabun:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/style.css?v=2">
</head>
<body>
    <aside class="sidebar">
        <a href="index.php" class="sidebar-item active" title="ขายสินค้า">
            <i class="fas fa-cash-register fa-2x"></i>
        </a>
        <a href="admin/index.php" class="sidebar-item" title="ระบบหลังบ้าน (Admin)">
            <i class="fas fa-tasks fa-2x"></i>
        </a>
        <a href="javascript:void(0)" class="sidebar-item" style="margin-top: auto;" title="เต็มหน้าจอ" onclick="toggleFullScreen()">
            <i class="fas fa-expand fa-2x"></i>
        </a>
        <a href="javascript:void(0)" class="sidebar-item" id="soundToggle" style="color: white;" title="เปิด/ปิดเสียง" onclick="toggleSound()">
            <i class="fas fa-volume-up fa-2x"></i>
        </a>
        <a href="logout.php" class="sidebar-item" style="color: var(--danger);" title="ออกจากระบบ">
            <i class="fas fa-sign-out-alt fa-2x"></i>
        </a>
    </aside>

    <main class="main-content">
        <section class="products-container">
            <header class="header">
                <div>
                    <h1><i class="fas fa-store"></i> <span id="storeNameLabel">ร้านอาหารของฉัน</span></h1>
                </div>
                <input type="text" class="search-bar" placeholder="ค้นหาชื่ออาหาร..." id="searchInput">
            </header>

            <div class="categories" id="categories">
                <!-- Categories loaded dynamically -->
            </div>

            <div class="grid" id="productGrid">
                <!-- Products loaded dynamically -->
            </div>
        </section>

        <section class="cart-panel" style="width: 33.33vw; min-width: 500px; flex-shrink: 0;">
            <div class="cart-title" style="display: flex; justify-content: space-between; align-items: center;">
                <div>รายการสั่งซื้อ (<span id="totalItems">0</span>)</div>
                <button class="btn-qty" style="color: var(--danger); border-color: var(--danger); width: 45px; height: 45px;" onclick="cart.clear()" title="ล้างตะกร้า">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            
            <div class="cart-items" id="cartItems" style="flex: 1; margin-bottom: 10px;">
                <!-- Cart items -->
            </div>

            <div class="cart-summary" style="padding-top: 10px; margin-bottom: 10px; border-top: 2px dashed var(--border);">
                <div class="summary-total" style="font-size: 2.4rem; margin-bottom: 5px;">
                    <span>ยอดรวม:</span>
                    <span id="totalAmount">฿0.00</span>
                </div>
            </div>

            <!-- Numpad & Payment Area -->
            <div style="background: var(--bg-light); border-radius: 16px; padding: 12px;">
                <div style="display: flex; gap: 10px; margin-bottom: 8px;">
                    <input type="text" class="cash-display-large" id="mainNumpadDisplay" readonly value="" placeholder="ระบุราคา..." style="flex: 1; padding: 12px 15px; font-size: 2.3rem; text-align: right; margin: 0; background: white; border-radius: 12px; font-weight: 800; color: var(--primary);">
                    <button class="btn-primary" style="border-radius: 12px; font-weight: bold; width: 70px; border:none; cursor:pointer; font-size: 1.8rem; background: var(--danger);" onclick="numpadMain.reset()" title="ล้างตัวเลข">C</button>
                    <button class="btn-primary" style="border-radius: 12px; font-weight: bold; width: 70px; border:none; cursor:pointer; font-size: 1.5rem;" onclick="addCustomPrice()" title="เพิ่มราคาพิเศษ"><i class="fas fa-plus"></i></button>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <div class="numpad" style="flex: 1; grid-template-columns: repeat(3, 1fr); gap: 6px;">
                        <button class="numpad-main-btn" onclick="numpadMain.input('7')">7</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('8')">8</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('9')">9</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('4')">4</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('5')">5</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('6')">6</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('1')">1</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('2')">2</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('3')">3</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('0')">0</button>
                        <button class="numpad-main-btn" onclick="numpadMain.input('00')">00</button>
                        <button class="numpad-main-btn" style="color: var(--danger);" onclick="numpadMain.backspace()"><i class="fas fa-backspace"></i></button>
                    </div>
                    
                    <div style="width: 150px; display: flex; flex-direction: column; gap: 6px;">
                        <button class="btn-checkout" style="flex:1; font-size: 1.1rem; font-weight:800; padding: 8px; background: #10b981; border-radius: 10px;" onclick="fastCheckout('cash')">
                            <i class="fas fa-money-bill-wave" style="display:block; margin-bottom:2px; font-size: 1.8rem;"></i> เงินสด
                        </button>
                        <button class="btn-checkout" style="flex:1; font-size: 1.1rem; font-weight:800; padding: 8px; background: #003399; border-radius: 10px;" onclick="fastCheckout('promptpay')">
                            <i class="fas fa-qrcode" style="display:block; margin-bottom:2px; font-size: 1.8rem;"></i> พร้อมเพย์
                        </button>
                        <button class="btn-checkout" style="flex:1; font-size: 1.1rem; font-weight:800; padding: 8px; background: #ff8c00; border-radius: 10px;" onclick="fastCheckout('truemoney')">
                            <i class="fas fa-wallet" style="display:block; margin-bottom:2px; font-size: 1.8rem;"></i> ทรูมันนี่
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Checkout Modal -->
    <div class="modal" id="checkoutModal">
        <div class="modal-content" style="flex-direction: column; gap: 15px;">
            <!-- Header -->
            <div style="text-align: center; border-bottom: 2px dashed var(--border); padding-bottom: 15px;">
                <h2 style="color: var(--danger); font-size: 2.5rem; margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.05);">ยอดที่ต้องชำระ: <span id="checkoutTotalDisplay">฿0.00</span></h2>
            </div>
            
            <div style="display: flex; flex: 1; gap: 30px;">
                <div class="modal-left">
                    <h2 style="margin-bottom: 25px; color: var(--primary); font-size: 1.5rem;">วิธีชำระเงิน</h2>
                <div class="method-grid">
                    <div class="method-card active" onclick="checkout.setMethod('cash')">
                        <i class="fas fa-money-bill-wave fa-lg" style="color: var(--success); width: 30px;"></i>
                        <div>เงินสด</div>
                    </div>
                    <div class="method-card" onclick="checkout.setMethod('promptpay')">
                        <i class="fas fa-qrcode fa-lg" style="color: #003399; width: 30px;"></i>
                        <div>พร้อมเพย์</div>
                    </div>
                    <div class="method-card" onclick="checkout.setMethod('truemoney')">
                        <i class="fas fa-wallet fa-lg" style="color: #ff8c00; width: 30px;"></i>
                        <div>ทรูมันนี่</div>
                    </div>
                </div>
            </div>

            <div class="modal-center">
                <!-- Cash Interface -->
                <div id="cashInterface">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="color: var(--text-primary); margin: 0; font-size: 1.8rem;">รับเงินสด</h3>
                        <button class="btn-primary" style="padding: 12px 20px; border-radius: 12px; font-weight: bold; font-size: 1.2rem; background: #10b981; border: none; cursor: pointer;" onclick="checkout.exactAmount()"><i class="fas fa-check"></i> รับเงินพอดี</button>
                    </div>
                    <input type="text" class="cash-display-large" id="cashInput" readonly value="0">
                    
                    <div class="numpad">
                        <button class="numpad-btn" onclick="numpad.input('7')">7</button>
                        <button class="numpad-btn" onclick="numpad.input('8')">8</button>
                        <button class="numpad-btn" onclick="numpad.input('9')">9</button>
                        <button class="numpad-btn" onclick="numpad.input('4')">4</button>
                        <button class="numpad-btn" onclick="numpad.input('5')">5</button>
                        <button class="numpad-btn" onclick="numpad.input('6')">6</button>
                        <button class="numpad-btn" onclick="numpad.input('1')">1</button>
                        <button class="numpad-btn" onclick="numpad.input('2')">2</button>
                        <button class="numpad-btn" onclick="numpad.input('3')">3</button>
                        <button class="numpad-btn" onclick="numpad.input('0')">0</button>
                        <button class="numpad-btn" onclick="numpad.input('00')">00</button>
                        <button class="numpad-btn" style="color: var(--danger);" onclick="numpad.backspace()"><i class="fas fa-backspace"></i></button>
                    </div>

                    <div class="change-display">เงินทอน: <span id="changeAmount">฿0.00</span></div>
                </div>

                <!-- QR Interface -->
                <div id="qrInterface" class="qr-container">
                    <h3 style="margin-bottom: 20px; color: var(--text-primary); font-size: 1.8rem;">สแกนเพื่อจ่ายชำระ</h3>
                    <img src="" id="promptpayQR">
                    <div id="qrAmount">฿0.00</div>
                    <p id="qrAccountInfo"></p>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-lg btn-primary" id="btnConfirmPrint" onclick="checkout.confirm(true)" style="background: #0ea5e9;"><i class="fas fa-print"></i> พิมพ์ใบเสร็จ</button>
                <button class="btn-lg btn-primary" id="btnConfirm" onclick="checkout.confirm(false)" style="background: #10b981;"><i class="fas fa-check-circle"></i> ยืนยัน (ไม่พิมพ์)</button>
                <button class="btn-lg btn-secondary" onclick="checkout.close()"><i class="fas fa-times"></i> ยกเลิก</button>
            </div>
            <!-- End Body Flex -->
            </div>

        </div>
    </div>
    <!-- Hidden Print Frame -->
    <iframe id="printFrame" name="printFrame" style="display:none;"></iframe>

    <script src="js/app_v2.js?v=2"></script>
</body>
</html>
