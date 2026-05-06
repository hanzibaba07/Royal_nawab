<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=royal_nawab;charset=utf8mb4', 'root', '');
} catch (PDOException $e) {
    exit('MySQL is not running or refused the connection. In XAMPP Control Panel click <strong>Start</strong> next to MySQL. If you use another port, edit <code>db_connect.php</code> (the <code>port=</code> value).');
}
