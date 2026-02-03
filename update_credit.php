    <?php
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");
if (isset($_POST['pay_amount'])) {
    $id = $_POST['ledger_id']; $amt = $_POST['pay_amount'];
    $row = $conn->query("SELECT total_amount, amount_paid FROM credit_ledger WHERE id=$id")->fetch_assoc();
    $new_paid = $row['amount_paid'] + $amt;
    $status = ($new_paid >= $row['total_amount']) ? 'cleared' : 'pending';
    $conn->query("UPDATE credit_ledger SET amount_paid='$new_paid', status='$status' WHERE id=$id");
    $conn->query("INSERT INTO credit_payments (ledger_id, paid_amount) VALUES ('$id', '$amt')");
    header("Location: ledger.php?success=1");
}
?>