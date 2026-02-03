<?php
session_start();
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");

// JSON data receive karna (Jo index.php se bhejenge)
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Data nahi mila!']);
    exit;
}

$conn->begin_transaction(); // Transaction shuru (taake agar aik cheez fail ho toh kuch bhi save na ho)

try {
    $cust_id = null;
    $mode = $data['mode']; // 'cash' or 'borrow'
    
    // 1. Agar Udhaar hai ya Customer details hain toh customer find/select karein
    if (!empty($data['cnic'])) {
        $cnic = $data['cnic'];
        $res = $conn->query("SELECT id FROM customers WHERE cnic = '$cnic'");
        if ($res->num_rows > 0) {
            $customer = $res->fetch_assoc();
            $cust_id = $customer['id'];
        } else {
            throw new Exception("Customer register nahi hai!");
        }
    }

    // 2. Invoice (Main Bill) Insert karein
    $total = $data['total'];
    $sql_invoice = "INSERT INTO invoices (customer_id, total_amount, payment_mode) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql_invoice);
    $stmt->bind_param("ids", $cust_id, $total, $mode);
    $stmt->execute();
    $invoice_id = $conn->insert_id;

    // 3. Har Item ko process karein (Stock minus + Ledger entry)
    foreach ($data['items'] as $item) {
        $p_name = $item['name'];
        $p_qty = $item['qty'];
        $p_price = $item['price'];
        $p_total = $item['total'];
        $p_id = $item['id']; // Product ID

        // A. Invoice Items mein entry
        $sql_item = "INSERT INTO invoice_items (invoice_id, product_id, product_name, qty, price_at_sale) VALUES (?, ?, ?, ?, ?)";
        $stmt_item = $conn->prepare($sql_item);
        $stmt_item->bind_param("iisid", $invoice_id, $p_id, $p_name, $p_qty, $p_price);
        $stmt_item->execute();
        $inv_item_id = $conn->insert_id;

        // B. Stock Minus karein
        $conn->query("UPDATE products SET stock_qty = stock_qty - $p_qty WHERE id = $p_id");

        // C. Agar Udhaar hai toh Credit Ledger mein aik aik item daalein
        if ($mode === 'borrow') {
            $sql_ledger = "INSERT INTO credit_ledger (customer_id, invoice_item_id, item_name, total_amount) VALUES (?, ?, ?, ?)";
            $stmt_ledger = $conn->prepare($sql_ledger);
            $stmt_ledger->bind_param("iisd", $cust_id, $inv_item_id, $p_name, $p_total);
            $stmt_ledger->execute();
        }
    }

    $conn->commit(); // Sab kuch sahi hai toh save kar do
    echo json_encode(['status' => 'success', 'invoice_id' => $invoice_id]);

} catch (Exception $e) {
    $conn->rollback(); // Agar koi error aye toh wapas purani halat mein le jao
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>