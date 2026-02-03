<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");

// Aaj ki date
$today = date('Y-m-d');

// 1. Total Sales (Today)
$sales_res = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE DATE(created_at) = '$today'");
$today_sales = $sales_res->fetch_assoc()['total'] ?? 0;

// 2. Cash vs Borrow (Today)
$cash_res = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE DATE(created_at) = '$today' AND payment_mode='cash'");
$today_cash = $cash_res->fetch_assoc()['total'] ?? 0;

$borrow_res = $conn->query("SELECT SUM(total_amount) as total FROM invoices WHERE DATE(created_at) = '$today' AND payment_mode='borrow'");
$today_borrow = $borrow_res->fetch_assoc()['total'] ?? 0;

// 3. Profit Calculation (Today)
// Logic: (Bechnay ki qeemat - Khareed ki qeemat) x Quantity
$profit_res = $conn->query("
    SELECT SUM((ii.price_at_sale - p.cost_price) * ii.qty) as profit 
    FROM invoice_items ii 
    JOIN products p ON ii.product_id = p.id 
    JOIN invoices i ON ii.invoice_id = i.id 
    WHERE DATE(i.created_at) = '$today'
");
$today_profit = $profit_res->fetch_assoc()['profit'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | 9D POS</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .stat-card { padding: 20px; border-radius: 20px; text-align: center; }
        .bg-green { background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e; }
        .bg-blue { background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; }
        .bg-purple { background: rgba(168, 85, 247, 0.1); border: 1px solid #a855f7; }
        .bg-orange { background: rgba(249, 115, 22, 0.1); border: 1px solid #f97316; }
        .stat-card h1 { margin: 10px 0 0 0; font-size: 1.8rem; }
        .stat-card small { color: #94a3b8; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="app-container">
    <header class="mobile-nav">
        <a href="index.php" style="color:white;"><i data-lucide="arrow-left"></i></a>
        <div class="logo">📊 SALES<span>INSIGHTS</span></div>
        <div></div>
    </header>

    <main class="content">
        <div class="stat-grid">
            <div class="stat-card bg-blue">
                <small>Today's Sale</small>
                <h1>Rs. <?php echo number_format($today_sales); ?></h1>
            </div>
            <div class="stat-card bg-green">
                <small>Net Profit</small>
                <h1 style="color: #4ade80;">Rs. <?php echo number_format($today_profit); ?></h1>
            </div>
            <div class="stat-card bg-purple">
                <small>Cash In Hand</small>
                <h1>Rs. <?php echo number_format($today_cash); ?></h1>
            </div>
            <div class="stat-card bg-orange">
                <small>Today's Udhaar</small>
                <h1>Rs. <?php echo number_format($today_borrow); ?></h1>
            </div>
        </div>

        <div class="glass-card">
            <h3>Recent Transactions</h3>
            <div class="mobile-list">
                <?php
                $recent = $conn->query("SELECT i.*, c.full_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id ORDER BY i.id DESC LIMIT 10");
                while($row = $recent->fetch_assoc()):
                ?>
                <div class="item-row">
                    <div class="item-info">
                        <h4>Inv #<?php echo $row['id']; ?> - <?php echo $row['full_name'] ?? 'Walking Customer'; ?></h4>
                        <small><?php echo date('h:i A', strtotime($row['created_at'])); ?> | Mode: <?php echo ucfirst($row['payment_mode']); ?></small>
                    </div>
                    <div class="item-price">Rs. <?php echo $row['total_amount']; ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>

<script>lucide.createIcons();</script>
</body>
</html>