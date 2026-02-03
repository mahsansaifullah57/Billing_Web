<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<?php
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");

// --- 1. PAYMENT PROCESSING LOGIC (Back-end) ---
if (isset($_POST['pay_amount']) && isset($_POST['ledger_id'])) {
    $ledger_id = (int)$_POST['ledger_id'];
    $amount_to_add = (float)$_POST['pay_amount'];

    if ($amount_to_add <= 0) {
        header("Location: ledger.php?error=InvalidAmount");
        exit;
    }

    $conn->begin_transaction();
    try {
        $res = $conn->query("SELECT total_amount, amount_paid FROM credit_ledger WHERE id = $ledger_id FOR UPDATE");
        $row = $res->fetch_assoc();

        $new_total_paid = $row['amount_paid'] + $amount_to_add;
        $status = ($new_total_paid >= $row['total_amount']) ? 'cleared' : 'pending';

        $sql_update = "UPDATE credit_ledger SET amount_paid = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("dsi", $new_total_paid, $status, $ledger_id);
        $stmt->execute();

        $sql_history = "INSERT INTO credit_payments (ledger_id, paid_amount) VALUES (?, ?)";
        $stmt_h = $conn->prepare($sql_history);
        $stmt_h->bind_param("id", $ledger_id, $amount_to_add);
        $stmt_h->execute();

        $conn->commit();
        header("Location: ledger.php?success=PaymentUpdated");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: ledger.php?error=Failed");
        exit;
    }
}

// --- 2. SEARCH LOGIC ---
$customer = null;
$items = [];
if (isset($_GET['search_query'])) {
    $q = $conn->real_escape_string($_GET['search_query']);
    $res = $conn->query("SELECT * FROM customers WHERE cnic='$q' OR phone='$q' OR full_name LIKE '%$q%' LIMIT 1");
    $customer = $res->fetch_assoc();
    if ($customer) {
        $cid = $customer['id'];
        $items = $conn->query("SELECT * FROM credit_ledger WHERE customer_id=$cid AND status='pending'");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Ledger | 9D POS</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<div class="app-container">
    <header class="mobile-nav">
        <a href="index.php" style="color:white;"><i data-lucide="arrow-left"></i></a>
        <div class="logo">💳 UDHAAR<span>LEDGER</span></div>
        <div></div>
    </header>

    <main class="content no-print">
        <div class="glass-card">
            <h3>Verify Customer</h3>
            <form method="GET" class="input-group">
                <i data-lucide="search"></i>
                <input type="text" name="search_query" placeholder="Enter CNIC or Phone..." value="<?php echo $_GET['search_query'] ?? ''; ?>">
                <button type="submit" class="add-btn" style="padding:10px; border-radius:8px; border:none; background:var(--neon); cursor:pointer;">Search</button>
            </form>
        </div>

        <?php if ($customer): ?>
            <div class="glass-card" style="border-left: 5px solid var(--neon);">
                <div style="display:flex; align-items:center; gap:15px;">
                    <img src="<?php echo $customer['profile_pic']; ?>" style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                    <div>
                        <h2 style="margin:0;"><?php echo $customer['full_name']; ?></h2>
                        <small>CNIC: <?php echo $customer['cnic']; ?> | Phone: <?php echo $customer['phone']; ?></small>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <h3>Pending Items</h3>
                <div class="mobile-list">
                    <?php if($items->num_rows > 0): ?>
                        <?php while($row = $items->fetch_assoc()): 
                            $due = $row['total_amount'] - $row['amount_paid'];
                        ?>
                        <div class="item-row" style="flex-direction:column; align-items:flex-start; gap:10px;">
                            <div style="width:100%; display:flex; justify-content:space-between;">
                                <div>
                                    <strong><?php echo $row['item_name']; ?></strong><br>
                                    <small>Total: Rs. <?php echo $row['total_amount']; ?> | Paid: <?php echo $row['amount_paid']; ?></small>
                                </div>
                                <div style="color:var(--accent); font-weight:bold;">Due: Rs. <?php echo $due; ?></div>
                            </div>
                            
                            <form method="POST" action="ledger.php" style="width:100%; display:flex; gap:10px;">
                                <input type="hidden" name="ledger_id" value="<?php echo $row['id']; ?>">
                                <input type="number" name="pay_amount" step="0.01" max="<?php echo $due; ?>" class="input-9d" style="margin:0;" placeholder="Amount" required>
                                <button type="submit" class="add-btn" style="padding:0 20px;">Pay</button>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No pending udhaar for this customer!</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif(isset($_GET['search_query'])): ?>
            <p style="color:red; text-align:center;">Customer not found!</p>
        <?php endif; ?>
    </main>
</div>

<script>lucide.createIcons();</script>
</body>
</html>