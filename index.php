<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>9D Premium POS</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* --- NEW PREMIUM COLOR SCHEME --- */
        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --neon: #fbbf24; /* Royal Gold */
            --accent: #f8fafc;
            --text: #ffffff;
            --danger: #ef4444;
        }

        /* --- MODERN BILL DESIGN (FOR PRINT) --- */
        @media print {
            body { background: white !important; color: black !important; }
            .no-print { display: none !important; }
            #printArea { 
                display: block !important; 
                width: 80mm; 
                padding: 5px;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .bill-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .bill-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
            .bill-table th { border-bottom: 1px solid #000; text-align: left; }
            .bill-table td { padding: 5px 0; border-bottom: 1px dashed #ddd; }
            .bill-total { border-top: 2px solid #000; margin-top: 10px; padding-top: 5px; text-align: right; }
        }

        #printArea { display: none; color: black; }
        
        /* UI Enhancements */
        .glass-card { background: var(--card); border: 1px solid rgba(251, 191, 36, 0.2); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .main-btn { background: var(--neon); color: #000; font-weight: 800; letter-spacing: 1px; }
        .total-row h1 { color: var(--neon); font-size: 2.5rem; }
    </style>
</head>
<body>

<div class="app-container no-print">
    <header class="mobile-nav" style="border-bottom: 2px solid var(--neon);">
        <div class="logo">👑 9D<span>PREMIUM</span></div>
        <div class="menu-icons" style="display:flex; gap:18px; align-items:center;">
            <a href="index.php" title="Billing"><i data-lucide="shopping-cart" style="color:var(--neon)"></i></a>
            <a href="ledger.php" title="Udhaar Ledger"><i data-lucide="book-user"></i></a>
            <a href="customers.php" title="Customers"><i data-lucide="users"></i></a>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="stock.php" title="Stock"><i data-lucide="package"></i></a>
                <a href="reports.php" title="Reports"><i data-lucide="trending-up"></i></a>
            <?php endif; ?>
            <a href="logout.php" style="color:var(--danger);"><i data-lucide="log-out"></i></a>
        </div>
    </header>

    <main class="content">
        <div class="search-section glass-card">
            <div class="input-group">
                <i data-lucide="scan-barcode" style="color:var(--neon)"></i>
                <input type="text" id="barcodeInput" placeholder="Scan or Type Product..." autofocus onkeypress="if(event.key === 'Enter') addItem()">
                <button onclick="addItem()" class="add-btn" style="background:var(--neon); color:black;"><i data-lucide="plus"></i></button>
            </div>
        </div>

        <div class="cart-section glass-card">
            <div class="section-header">
                <h3><i data-lucide="shopping-bag" style="width:18px;"></i> My Cart</h3>
                <button onclick="clearCart()" class="text-btn" style="color:var(--danger); font-size:12px;">RESET ALL</button>
            </div>
            <div id="cartList" class="mobile-list">
                <div class="empty-state">Cart is empty</div>
            </div>
        </div>

        <div class="checkout-card glass-card">
            <div class="customer-info grid-2">
                <input type="text" id="custCNIC" placeholder="CNIC/Phone (for Ledger)">
                <select id="payMode" style="background: #0f172a; color: white; border: 1px solid var(--neon);">
                    <option value="cash">💵 Cash Sale</option>
                    <option value="borrow">💳 Udhaar (Credit)</option>
                </select>
            </div>
            
            <div class="total-row">
                <span style="text-transform:uppercase; font-size:12px; letter-spacing:1px; color:var(--neon);">Grand Total</span>
                <h1 id="totalAmount">0</h1>
            </div>
            
            <button onclick="saveInvoice()" class="main-btn" id="finalizeBtn">
                FINALIZE & PRINT RECEIPT
            </button>
        </div>
    </main>
</div>

<div id="printArea">
    <div class="bill-header">
        <h2 style="margin:0;">9D RETAIL STORE</h2>
        <p style="margin:2px; font-size:12px;">Premium Quality | Faisalabad</p>
        <p id="pDate" style="font-size: 11px; margin:0;"></p>
    </div>

    <div style="font-size:12px; margin-top:10px;">
        <div id="pCustomerInfo"></div>
        <div id="pModeDisplay"></div>
    </div>

    <table class="bill-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody id="printItems"></tbody>
    </table>

    <div class="bill-total">
        <span style="font-size:12px;">NET AMOUNT</span><br>
        <b style="font-size:18px;" id="pTotal"></b>
    </div>

    <div style="text-align:center; font-size: 10px; margin-top:20px; border-top: 1px solid #000; padding-top:5px;">
        <p>Purchased items can be exchanged within 3 days.<br>Software by 9D Solutions | 0300-1234567</p>
        <b>THANK YOU FOR SHOPPING!</b>
    </div>
</div>

<script>
let cart = [];

async function addItem() {
    let q = document.getElementById('barcodeInput').value;
    if(!q) return;
    try {
        let res = await fetch(`search_product.php?q=${q}`);
        let p = await res.json();
        if(p && p.id) {
            let existing = cart.find(i => i.id === p.id);
            if(existing) {
                existing.qty++;
                existing.total = existing.qty * existing.price;
            } else {
                cart.push({ id: p.id, name: p.item_name, price: parseFloat(p.sale_price), qty: 1, total: parseFloat(p.sale_price) });
            }
            renderCart();
            document.getElementById('barcodeInput').value = '';
        } else {
            alert("Item not found!");
        }
    } catch(e) { console.log(e); }
}

function renderCart() {
    let list = document.getElementById('cartList');
    let total = 0;
    if(cart.length === 0) {
        list.innerHTML = '<div class="empty-state">Cart is empty</div>';
        document.getElementById('totalAmount').innerText = "0";
        return;
    }
    list.innerHTML = cart.map((item, index) => {
        total += item.total;
        return `
        <div class="item-row" style="border-left: 3px solid var(--neon); padding-left:10px;">
            <div class="item-info">
                <h4 style="margin:0;">${item.name}</h4>
                <small style="color:#94a3b8;">${item.price} x ${item.qty}</small>
            </div>
            <div class="item-price" style="color:var(--neon)">Rs. ${item.total}</div>
            <button onclick="removeItem(${index})" style="background:none; border:none; color:var(--danger);"><i data-lucide="x-circle"></i></button>
        </div>`;
    }).join('');
    document.getElementById('totalAmount').innerText = total;
    lucide.createIcons();
}

function removeItem(index) { cart.splice(index, 1); renderCart(); }
function clearCart() { if(confirm("Reset cart?")) { cart = []; renderCart(); } }

async function saveInvoice() {
    if(cart.length === 0) return alert("Empty Cart!");
    let mode = document.getElementById('payMode').value;
    let cnic = document.getElementById('custCNIC').value;
    let total = document.getElementById('totalAmount').innerText;
    if(mode === 'borrow' && !cnic) return alert("CNIC required for Udhaar!");

    document.getElementById('finalizeBtn').disabled = true;
    let data = { mode, cnic, total, items: cart };

    try {
        let res = await fetch('process_sale.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        let result = await res.json();
        if(result.status === 'success') {
            prepareBill(mode, cnic, total);
            window.print();
            location.reload();
        } else {
            alert("Error: " + result.message);
            document.getElementById('finalizeBtn').disabled = false;
        }
    } catch(e) { alert("Server error!"); document.getElementById('finalizeBtn').disabled = false; }
}

function prepareBill(mode, cnic, total) {
    document.getElementById('pDate').innerText = new Date().toLocaleString();
    document.getElementById('pCustomerInfo').innerHTML = "<b>Customer:</b> " + (cnic || "Walking Customer");
    document.getElementById('pModeDisplay').innerHTML = "<b>Payment:</b> " + mode.toUpperCase();
    document.getElementById('printItems').innerHTML = cart.map(i => `<tr><td>${i.name}</td><td>${i.qty}</td><td style="text-align:right;">${i.total}</td></tr>`).join('');
    document.getElementById('pTotal').innerText = "Rs. " + total;
}
lucide.createIcons();
</script>
</body>
</html>