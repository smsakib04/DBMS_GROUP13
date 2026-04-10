<?php
session_start();
require_once '../config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT staff_id, full_name, role, password_hash FROM staff WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // For demo, password_hash stores plain "dummy_hash" – adjust if you use hashing
        if ($password === $user['password_hash'] || $password === 'dummy_hash') {
            $_SESSION['staff_id'] = $user['staff_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header("Location: homepage.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tortoise Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Montserrat', sans-serif; margin: 0; }
        .login-container { background: #f4fbe2; border-radius: 20px; box-shadow: 0 8px 32px 0 rgba(34,49,63,0.2); padding: 40px 32px 32px; width: 350px; text-align: center; }
        .tortoise-icon { font-size: 70px; margin-bottom: 18px; }
        .login-title { font-size: 2rem; color: #3e6b2f; margin-bottom: 24px; font-weight: bold; }
        .login-form input { width: 100%; padding: 12px 16px; margin-bottom: 18px; border: none; border-radius: 10px; background: #e0f3c8; font-size: 1rem; }
        .login-btn { background: linear-gradient(90deg, #56ab2f 0%, #a8e063 100%); color: white; border: none; border-radius: 10px; padding: 12px; width: 100%; font-size: 1.1rem; font-weight: bold; cursor: pointer; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="tortoise-icon">🐢</div>
    <div class="login-title">Login</div>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" class="login-form">
        <input type="text" name="username" placeholder="Username (e.g. breeding1)" required>
        <input type="password" name="password" placeholder="Password (dummy_hash)" required>
        <button type="submit" class="login-btn">Login</button>
    </form>
</div>
</body>
</html>