<?php
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");
$q = $_GET['q'];
$res = $conn->query("SELECT * FROM products WHERE item_name LIKE '%$q%' OR barcode='$q' LIMIT 1");
echo json_encode($res->fetch_assoc());
?>