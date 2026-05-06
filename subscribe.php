<?php
require 'db_connect.php';
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['message' => 'Please enter a valid email address.']);
    exit;
}

$st = $pdo->prepare('SELECT id FROM subscribers WHERE email = ?');
$st->execute([$email]);
if ($st->fetch()) {
    echo json_encode(['message' => 'You are already subscribed!']);
    exit;
}
$pdo->prepare('INSERT INTO subscribers (email) VALUES (?)')->execute([$email]);
echo json_encode(['message' => 'Thank you for subscribing!']);
