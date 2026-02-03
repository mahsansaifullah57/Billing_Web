<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");

// Naya Customer Save Karne ka Logic
if (isset($_POST['save_cust'])) {
    $name = $_POST['name']; $cnic = $_POST['cnic']; $phone = $_POST['phone']; $addr = $_POST['addr'];
    $conn->query("INSERT INTO customers (full_name, cnic, phone, address) VALUES ('$name', '$cnic', '$phone', '$addr')");
    header("Location: customers.php?msg=Customer Added");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registry</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
<div class="app-container">
    <header class="mobile-nav">
        <a href="index.php" style="color:white;"><i data-lucide="arrow-left"></i></a>
        <div class="logo">👤 CUSTOMER<span>REGISTRY</span></div>
        <div></div>
    </header>

    <main class="content">
        <div class="glass-card">
            <h3>Add New Customer</h3>
            <form method="POST">
                <input type="text" name="name" class="input-9d" placeholder="Full Name" required>
                <input type="text" name="cnic" class="input-9d" placeholder="CNIC (Unique)" required>
                <input type="text" name="phone" class="input-9d" placeholder="Phone Number" required>
                <textarea name="addr" class="input-9d" placeholder="Address"></textarea>
                <button type="submit" name="save_cust" class="main-btn">REGISTER CUSTOMER</button>
            </form>
        </div>
    </main>
</div>
<script>lucide.createIcons();</script>
</body>
</html>