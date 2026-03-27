// js/app.js - Main POS Logic (Touch Optimized)

let products = [];
let cartItems = [];
let currentCategory = 'all';
let currentPaymentMethod = 'cash';
let systemSettings = {};

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    await loadSettings();
    await loadProducts();

    // Set UI elements based on settings
    document.getElementById('storeNameLabel').innerText = systemSettings['store_name'] || 'POS System';

    renderProducts();
    renderCategories();
});

// Load Settings from API
async function loadSettings() {
    try {
        const response = await fetch('api.php?action=get_settings');
        systemSettings = await response.json();
    } catch (error) {
        console.error('Error loading settings:', error);
    }
}

// Load products from API
async function loadProducts() {
    try {
        const response = await fetch('api.php?action=get_products');
        products = await response.json();
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Render product grid
function renderProducts() {
    const grid = document.getElementById('productGrid');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();

    const filtered = products.filter(p => {
        const matchesCategory = currentCategory === 'all' || p.category === currentCategory;
        const matchesSearch = p.name.toLowerCase().includes(searchTerm);
        return matchesCategory && matchesSearch;
    });

    grid.innerHTML = filtered.map(p => {
        const requiresStock = parseInt(p.requires_stock) === 1;
        const stockStatus = requiresStock ? (p.stock > 0 ? `<div class="product-stock">${p.stock} คงเหลือ</div>` : `<div class="product-stock" style="background:var(--danger)">หมด!</div>`) : '';

        // Stock -1 logic (Disabled)
        const isDisabled = parseInt(p.stock) === -1;
        const onclick = isDisabled ? `onclick="Swal.fire('ขออภัย', 'สินค้านี้ยังไม่พร้อมขาย', 'info')" style="opacity:0.5; filter:grayscale(1); cursor:not-allowed;"` :
            (requiresStock && p.stock <= 0 ? `onclick="Swal.fire('หมดสต็อก', 'สินค้านี้หมดแล้ว', 'error')" style="opacity:0.6;"` : `onclick="cart.add(${p.id})"`);

        // Price display (Discount logic)
        const originalPrice = parseFloat(p.price);
        const discountPrice = parseFloat(p.discount_price || 0);
        const hasDiscount = discountPrice > 0;
        const displayPrice = hasDiscount ?
            `<div class="product-price"><span style="text-decoration:line-through; font-size:0.85rem; color:#94a3b8; margin-right:5px;">฿${originalPrice.toFixed(2)}</span> <span style="color:var(--danger); font-weight:800;">฿${discountPrice.toFixed(2)}</span></div>` :
            `<div class="product-price">฿${originalPrice.toFixed(2)}</div>`;

        return `
        <div class="product-card" ${onclick}>
            ${stockStatus}
            <img src="${p.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=60'}" alt="${p.name}">
            <div class="product-name">${p.name}</div>
            ${displayPrice}
            <div style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 5px;">${p.category} ${isDisabled ? '<span style="color:red">(ปิดขาย)</span>' : ''}</div>
        </div>
    `}).join('');
}

// Render dynamic categories
function renderCategories() {
    const container = document.getElementById('categories');
    const cats = ['all', ...new Set(products.map(p => p.category))];

    container.innerHTML = cats.map(c => `
        <div class="category-pill ${currentCategory === c ? 'active' : ''}" 
             onclick="setCategory('${c}')">
            ${c === 'all' ? 'ทั้งหมด' : c}
        </div>
    `).join('');
}

function setCategory(cat) {
    currentCategory = cat;
    renderCategories();
    renderProducts();
}

document.getElementById('searchInput').oninput = renderProducts;

// Cart Management
const cart = {
    add(productId) {
        const product = products.find(p => p.id === productId);
        const existing = cartItems.find(item => item.id === productId);

        const currentQty = existing ? existing.qty : 0;
        if (currentQty + 1 > product.stock) {
            return Swal.fire('แจ้งเตือน', 'สินค้าไม่พอขาย! (เหลือ ' + product.stock + ')', 'warning');
        }

        if (existing) {
            existing.qty++;
        } else {
            // Apply discount price if set
            const sellingPrice = parseFloat(product.discount_price) > 0 ? parseFloat(product.discount_price) : parseFloat(product.price);
            cartItems.push({ ...product, price: sellingPrice, qty: 1 });
        }
        this.render();
    },

    remove(productId) {
        cartItems = cartItems.filter(item => item.id !== productId);
        this.render();
    },

    updateQty(productId, delta) {
        const item = cartItems.find(item => item.id === productId);
        const product = products.find(p => p.id === productId);

        if (item) {
            if (delta > 0 && item.qty + delta > product.stock) {
                return Swal.fire('แจ้งเตือน', 'สินค้าไม่พอขาย! (เหลือ ' + product.stock + ')', 'warning');
            }
            item.qty += delta;
            if (item.qty <= 0) this.remove(productId);
            else this.render();
        }
    },

    async clear() {
        if (cartItems.length > 0) {
            const result = await Swal.fire({
                title: 'ยืนยัน?',
                text: "ต้องการล้างตะกร้าสินค้าทั้งหมดหรือไม่",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'ล้างตะกร้า',
                cancelButtonText: 'ยกเลิก'
            });
            if (result.isConfirmed) {
                cartItems = [];
                this.render();
            }
        }
    },

    getTotal() {
        return cartItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
    },

    render() {
        const container = document.getElementById('cartItems');
        const count = document.getElementById('totalItems');
        const amount = document.getElementById('totalAmount');

        if (cartItems.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; margin-top: 50px; color: var(--text-secondary);">
                    <i class="fas fa-shopping-basket fa-3x" style="margin-bottom:15px; opacity:0.5;"></i>
                    <p style="font-size:1.1rem;">ยังไม่มีสินค้าในตะกร้า</p>
                </div>`;
        } else {
            container.innerHTML = cartItems.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p>฿${parseFloat(item.price).toFixed(2)} x ${item.qty}</p>
                    </div>
                    <div class="quantity-controls">
                        <button class="btn-qty" onclick="cart.updateQty(${item.id}, -1)">-</button>
                        <span style="font-size:1.2rem; font-weight:800; min-width: 20px; text-align:center;">${item.qty}</span>
                        <button class="btn-qty" onclick="cart.updateQty(${item.id}, 1)">+</button>
                    </div>
                </div>
            `).join('');
        }

        count.innerText = cartItems.reduce((sum, item) => sum + item.qty, 0);
        amount.innerText = `฿${this.getTotal().toFixed(2)}`;
    }
};

// On-screen Numpad Logic (Modal)
const numpad = {
    input(val) {
        const el = document.getElementById('cashInput');
        if (el.value === '0') el.value = val;
        else el.value += val;
        checkout.calcChange();
    },
    backspace() {
        const el = document.getElementById('cashInput');
        el.value = el.value.slice(0, -1);
        if (el.value === '') el.value = '0';
        checkout.calcChange();
    },
    reset() {
        document.getElementById('cashInput').value = '0';
    }
};

// Main Screen Numpad Logic
const numpadMain = {
    input(val) {
        const el = document.getElementById('mainNumpadDisplay');
        if (el.value === '' || el.value === '0') el.value = val;
        else el.value += val;
    },
    backspace() {
        const el = document.getElementById('mainNumpadDisplay');
        el.value = el.value.slice(0, -1);
    },
    reset() {
        document.getElementById('mainNumpadDisplay').value = '';
    },
    getValue() {
        return parseFloat(document.getElementById('mainNumpadDisplay').value) || 0;
    }
};

function addCustomPrice() {
    const val = numpadMain.getValue();
    if (val <= 0) return Swal.fire('ผิดพลาด', 'กรุณาระบุราคาที่มากกว่า 0', 'error');

    cartItems.push({
        id: 'special',
        name: 'ราคาพิเศษ',
        price: val,
        cost_price: 0,
        category: 'อื่นๆ',
        qty: 1
    });
    cart.render();
    numpadMain.reset();
}

function toggleFullScreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            alert(`ไม่สามารถเข้าโหมดเต็มจอได้: ${err.message}`);
        });
    } else {
        document.exitFullscreen();
    }
}

// Sync the fullscreen icon when state changes (e.g., user presses Escape)
document.addEventListener('fullscreenchange', () => {
    const fsBtn = document.querySelector('.sidebar-item[onclick="toggleFullScreen()"] i');
    if (fsBtn) {
        if (document.fullscreenElement) {
            fsBtn.className = 'fas fa-compress fa-2x';
        } else {
            fsBtn.className = 'fas fa-expand fa-2x';
        }
    }
});

async function fastCheckout(method) {
    let val = numpadMain.getValue();

    if (val > 0) {
        addCustomPrice();
    }

    if (cartItems.length === 0) return Swal.fire('แจ้งเตือน', 'กรุณาหยิบสินค้า หรือระบุราคาพิเศษก่อนชำระเงิน', 'warning');

    checkout.openWithMethod(method);
}

async function processCheckout(method, total) {
    const paymentData = {
        total_amount: total,
        payment_method: method,
        items: cartItems
    };

    try {
        const response = await fetch('api.php?action=save_transaction', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(paymentData)
        });
        const res = await response.json();

        if (res.success) {
            const printPrompt = await Swal.fire({
                title: 'ทำรายการสำเร็จ!',
                text: "ต้องการพิมพ์ใบเสร็จหรือไม่?",
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'พิมพ์ใบเสร็จ',
                cancelButtonText: 'ปิด'
            });
            if (printPrompt.isConfirmed) {
                window.open(`receipt.php?id=${res.id}`, 'receipt', 'width=400,height=600');
            }
            cartItems = [];
            cart.render();
            document.getElementById('checkoutModal').classList.remove('active');
            await loadProducts(); // Reload stock
            renderProducts();
        } else {
            Swal.fire('เกิดข้อผิดพลาด', res.error || 'ไม่สามารถบันทึกข้อมูลได้', 'error');
        }
    } catch (error) {
        console.error('Checkout error:', error);
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    }
}

// Checkout Modal Logic
const checkout = {
    openWithMethod(method) {
        if (cartItems.length === 0) return Swal.fire('แจ้งเตือน', 'กรุณาหยิบสินค้าลงตะกร้าก่อนทำการชำระเงิน', 'warning');
        document.getElementById('checkoutModal').classList.add('active');
        document.getElementById('checkoutTotalDisplay').innerText = `฿${cart.getTotal().toFixed(2)}`;
        this.setMethod(method);
        numpad.reset();
        this.calcChange();
    },

    open() {
        this.openWithMethod('cash');
    },

    close() {
        document.getElementById('checkoutModal').classList.remove('active');
        if (cartItems.length > 0) {
            cartItems = [];
            cart.render();
        }
    },

    setMethod(method) {
        currentPaymentMethod = method;
        document.querySelectorAll('.method-card').forEach(card => card.classList.remove('active'));

        let activeIndex = method === 'cash' ? 0 : method === 'promptpay' ? 1 : 2;
        document.querySelectorAll('.method-card')[activeIndex].classList.add('active');

        document.getElementById('cashInterface').style.display = (method === 'cash') ? 'flex' : 'none';
        document.getElementById('qrInterface').style.display = (method !== 'cash') ? 'flex' : 'none';

        if (method !== 'cash') {
            const total = cart.getTotal();
            const qrImg = document.getElementById('promptpayQR');
            const promptpayId = systemSettings['promptpay_id'] || '0812345678';

            qrImg.src = `https://promptpay.io/${promptpayId}/${total}.png`;
            document.getElementById('qrAmount').innerText = `฿${total.toFixed(2)}`;
            document.getElementById('qrAccountInfo').innerText = `บัญชีพร้อมเพย์: ${promptpayId}`;

            if (method === 'truemoney') {
                document.getElementById('qrAccountInfo').innerText = `ทรูมันนี่วอลเล็ท: ${systemSettings['truemoney_phone'] || promptpayId}`;
            }

            const btnConf = document.getElementById('btnConfirm');
            const btnPrint = document.getElementById('btnConfirmPrint');
            if (btnConf) { btnConf.disabled = false; btnConf.style.opacity = '1'; }
            if (btnPrint) { btnPrint.disabled = false; btnPrint.style.opacity = '1'; }
        } else {
            this.calcChange();
        }
    },

    calcChange() {
        if (currentPaymentMethod !== 'cash') return;

        const total = cart.getTotal();
        let cashText = document.getElementById('cashInput').value;
        const cash = parseFloat(cashText) || 0;
        const change = Math.max(0, cash - total);
        document.getElementById('changeAmount').innerText = `฿${change.toFixed(2)}`;

        const btnConf = document.getElementById('btnConfirm');
        const btnPrint = document.getElementById('btnConfirmPrint');

        // If they typed a number and it's not enough
        if (cashText !== '0' && cashText !== '' && cash < total) {
            btnConf.disabled = true;
            btnConf.style.opacity = '0.5';
            if (btnPrint) { btnPrint.disabled = true; btnPrint.style.opacity = '0.5'; }
        } else if (cashText === '0' || cashText === '') {
            // Assume exact change
            btnConf.disabled = false;
            btnConf.style.opacity = '1';
            if (btnPrint) { btnPrint.disabled = false; btnPrint.style.opacity = '1'; }
            document.getElementById('changeAmount').innerText = `ไม่ต้องทอน (รับพอดี)`;
        } else {
            btnConf.disabled = false;
            btnConf.style.opacity = '1';
            if (btnPrint) { btnPrint.disabled = false; btnPrint.style.opacity = '1'; }
        }
    },

    exactAmount() {
        if (currentPaymentMethod !== 'cash') return;
        const total = cart.getTotal();
        document.getElementById('cashInput').value = total.toString();
        this.calcChange();
        this.confirm(false); // Confirm without print and move to new sale
    },

    async confirm(printReceipt = false) {
        const btn = document.getElementById('btnConfirm');
        if (btn && btn.disabled) return;

        let total = cart.getTotal();

        const paymentData = {
            total_amount: total,
            payment_method: currentPaymentMethod,
            items: cartItems
        };

        try {
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...'; }
            const btnP = document.getElementById('btnConfirmPrint');
            if (btnP) { btnP.disabled = true; }

            const response = await fetch('api.php?action=save_transaction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(paymentData)
            });
            const res = await response.json();

            if (res.success) {
                // Play success sound (local asset)
                if (isSoundEnabled) {
                    const audio = new Audio('assets/sounds/success.mp3');
                    audio.volume = 1.0;
                    audio.play().catch(e => console.log("Audio play failed:", e));
                }

                if (printReceipt) {
                    const printFrame = document.getElementById('printFrame');
                    if (printFrame) {
                        printFrame.src = `receipt.php?id=${res.id}`;
                    } else {
                        window.open(`receipt.php?id=${res.id}`, 'receipt', 'width=400,height=600');
                    }
                }

                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'บันทึกรายการขายเรียบร้อยแล้ว' + (printReceipt ? ' (กำลังส่งพิมพ์...)' : ''),
                    icon: 'success',
                    timer: printReceipt ? 2000 : 1500,
                    showConfirmButton: false
                });

                cartItems = [];
                cart.render();
                this.close();
                await loadProducts(); // Reload stock
                renderProducts();
            } else {
                alert('เกิดข้อผิดพลาด: ' + (res.error || 'ไม่สามารถบันทึกข้อมูลได้'));
            }
        } catch (error) {
            console.error('Checkout error:', error);
            alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check-circle"></i> ยืนยัน (ไม่พิมพ์)'; }
            const btnP = document.getElementById('btnConfirmPrint');
            if (btnP) { btnP.disabled = false; }
        }
    }
};

// Global sound control
let isSoundEnabled = true;
let audioCtx;

function toggleSound() {
    isSoundEnabled = !isSoundEnabled;
    const btn = document.getElementById('soundToggle');
    const icon = btn.querySelector('i');

    if (isSoundEnabled) {
        icon.className = 'fas fa-volume-up fa-2x';
        btn.style.color = 'white';
        // Test sound
        playClickSound();
    } else {
        icon.className = 'fas fa-volume-mute fa-2x';
        btn.style.color = 'rgba(255,255,255,0.4)';
    }
}

function playClickSound() {
    if (!isSoundEnabled) return;
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();

    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(1200, audioCtx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(100, audioCtx.currentTime + 0.1);
    gain.gain.setValueAtTime(0.5, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.1);
}

document.addEventListener('click', (e) => {
    const target = e.target.closest('button, .numpad-btn, .product-card, .sidebar-item, .method-card, .btn-qty, .category-btn');
    if (target && target.id !== 'soundToggle') {
        playClickSound();
    }
});


