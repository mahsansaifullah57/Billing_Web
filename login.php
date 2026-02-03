<?php
session_start();
$conn = new mysqli("localhost", "root", "", "billing_pro_9d");

$error = "";
if (isset($_POST['login'])) {
    $user = $conn->real_escape_string($_POST['username']);
    $pass = $_POST['password']; // Agar password hash use kar rahe hain toh password_verify use karein

    $res = $conn->query("SELECT * FROM users WHERE username='$user' AND password='$pass'");
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['full_name'];
        $_SESSION['role'] = $row['role'];

        header("Location: index.php");
        exit();
    } else {
        $error = "Ghalat Username ya Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | 9D POS Pro</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: radial-gradient(circle at top right, #1e293b, #0f172a);
            margin: 0;
        }
        .login-card {
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        .logo-area { margin-bottom: 30px; }
        .logo-icon { 
            background: var(--neon); 
            color: #000; 
            width: 60px; height: 60px; 
            border-radius: 15px; 
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 0 20px rgba(0, 242, 254, 0.5);
        }
        .error-msg { color: #ff4444; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>

<div class="login-card glass-card">
    <div class="logo-area">
        <div class="logo-icon"><i data-lucide="shield-check"></i></div>
        <h2 style="margin:0;">Welcome Back</h2>
        <p style="color:gray; font-size:14px;">9D POS System Login</p>
    </div>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group" style="margin-bottom: 15px;">
            <i data-lucide="user" style="width:20px; color:gray;"></i>
            <input type="text" name="username" placeholder="Username" required>
        </div>
        
        <div class="input-group" style="margin-bottom: 25px;">
            <i data-lucide="lock" style="width:20px; color:gray;"></i>
            <input type="password" name="password" placeholder="Password" required>
        </div>

        <button type="submit" name="login" class="main-btn">
            ACCESS DASHBOARD <i data-lucide="arrow-right"></i>
        </button>
    </form>
    
    <p style="margin-top:20px; font-size:12px; color:gray;">&copy; 2026 9D Business Solutions</p>
</div>

<script>lucide.createIcons();</script>
</body>
</html>