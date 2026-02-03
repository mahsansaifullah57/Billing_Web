<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "billing_pro_9d");

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: stock.php?msg=Deleted");
    exit();
}

// --- ADD / UPDATE LOGIC ---
if (isset($_POST['save_product'])) {
    $name = $conn->real_escape_string($_POST['item_name']);
    $barcode = $conn->real_escape_string($_POST['barcode']);
    $cost = $_POST['cost_price'];
    $sale = $_POST['sale_price'];
    $qty = $_POST['stock_qty'];

    // Agar barcode pehle se hai to update karega, warna naya add karega
    $sql = "INSERT INTO products (barcode, item_name, cost_price, sale_price, stock_qty) 
            VALUES ('$barcode', '$name', '$cost', '$sale', '$qty')
            ON DUPLICATE KEY UPDATE 
            item_name='$name', cost_price='$cost', sale_price='$sale', stock_qty = stock_qty + $qty";
    
    if($conn->query($sql)){
        header("Location: stock.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stock | 9D POS</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .action-btns { display: flex; gap: 8px; }
        .btn-edit { background: #3b82f6; color: white; border: none; padding: 5px; border-radius: 5px; cursor: pointer; }
        .btn-del { background: #ef4444; color: white; border: none; padding: 5px; border-radius: 5px; cursor: pointer; }
        .nav-links { display: flex; gap: 10px; margin-bottom: 10px; }
        .nav-links a { color: var(--neon); text-decoration: none; font-size: 14px; border: 1px solid var(--neon); padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="app-container">
    <header class="mobile-nav">
        <a href="index.php" style="color:white;"><i data-lucide="arrow-left"></i></a>
        <div class="logo">📦 STOCK<span>MANAGER</span></div>
        <a href="customers.php" title="Add Customer" style="color:white;"><i data-lucide="user-plus"></i></a>
    </header>

    <main class="content">
        <div class="nav-links">
            <a href="customers.php">+ Register New Customer</a>
        </div>

        <div class="glass-card">
            <h3>Add / Restock Product</h3>
            <form method="POST">
                <input type="text" name="item_name" class="input-9d" placeholder="Product Name" required>
                <div class="grid-2">
                    <input type="text" name="barcode" class="input-9d" placeholder="Barcode / Code">
                    <input type="number" name="stock_qty" class="input-9d" placeholder="Qty to Add" required>
                </div>
                <div class="grid-2">
                    <input type="number" step="0.01" name="cost_price" class="input-9d" placeholder="Cost Price" required>
                    <input type="number" step="0.01" name="sale_price" class="input-9d" placeholder="Sale Price" required>
                </div>
                <button type="submit" name="save_product" class="main-btn">SAVE TO INVENTORY</button>
            </form>
        </div>

        <div class="glass-card">
            <div class="section-header">
                <h3>Available Stock</h3>
            </div>
            <div class="mobile-list">
                <?php
                $res = $conn->query("SELECT * FROM products ORDER BY id DESC");
                while($p = $res->fetch_assoc()):
                ?>
                <div class="item-row">
                    <div class="item-info">
                        <h4><?php echo $p['item_name']; ?></h4>
                        <small>Stock: <b><?php echo $p['stock_qty']; ?></b> | Code: <?php echo $p['barcode']; ?></small>
                    </div>
                    
                    <div class="action-btns">
                        <div style="text-align:right; margin-right:10px;">
                            <span style="color:var(--neon)">Rs. <?php echo $p['sale_price']; ?></span><br>
                            <small style="color:gray">Cost: <?php echo $p['cost_price']; ?></small>
                        </div>
                        
                        <a href="stock.php?delete_id=<?php echo $p['id']; ?>" onclick="return confirm('Delete this product?')">
                            <button class="btn-del"><i data-lucide="trash-2" style="width:16px;"></i></button>
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>

<script>lucide.createIcons();</script>
</body>
</html>