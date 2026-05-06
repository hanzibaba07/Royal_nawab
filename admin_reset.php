<?php
// =============================================
// admin_reset.php - Admin Login Fix Tool
// =============================================
require 'db_connect.php';
session_start();

echo "<h2> Royal Nawab Admin Reset Tool</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $pdo->prepare("DELETE FROM admin_users WHERE username = 'admin'")->execute();

    $sql = 'INSERT INTO admin_users (username, password_hash) VALUES (?, ?)';
    $st = $pdo->prepare($sql);
    if ($st->execute(['admin', $hash])) {
        echo "<p style='color:green; font-size:18px;'>✅ Admin account reset successfully!<br>";
        echo "<strong>Username:</strong> admin<br>";
        echo "<strong>Password:</strong> admin123</p>";
        echo "<hr><a href='admin.php'>→ Go to Admin Login</a>";
    } else {
        echo "<p style='color:red;'>Error: " . htmlspecialchars(implode(' ', $st->errorInfo())) . "</p>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Reset Tool</title>
  <style>
    body { font-family: Arial, sans-serif; text-align:center; margin-top:50px; background:#f4f4f4; }
    .box { max-width:500px; margin:0 auto; background:white; padding:40px; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
    button { padding:15px 30px; font-size:18px; background:#8b1a1a; color:white; border:none; border-radius:5px; cursor:pointer; }
  </style>
</head>
<body>
  <div class="box">
    <h1>Admin Login Fix</h1>
    <p>This will reset the admin account to default credentials.</p>
    <form method="POST">
      <input type="hidden" name="reset" value="1">
      <button type="submit">Reset Admin Account Now</button>
    </form>
    <br><br>
    <small>Default Login: <strong>admin / admin123</strong></small>
  </div>
</body>
</html>
