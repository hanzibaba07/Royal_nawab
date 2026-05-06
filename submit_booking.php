<?php
// =============================================
// submit_booking.php
// =============================================
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php'); exit;
}

$first_name  = trim($_POST['first_name']  ?? '');
$last_name   = trim($_POST['last_name']   ?? '');
$email       = trim($_POST['email']       ?? '');
$phone       = trim($_POST['phone']       ?? '');
$num_guests  = (int)($_POST['num_guests'] ?? 2);
$booking_date= trim($_POST['booking_date']?? '');
$booking_time= trim($_POST['booking_time']?? '');
$special_req = trim($_POST['special_requests'] ?? '');

// Validate
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($booking_date)) {
    header('Location: booking.php?status=error&msg=Please+fill+in+all+required+fields');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: booking.php?status=error&msg=Please+enter+a+valid+email+address');
    exit;
}
if ($booking_date < date('Y-m-d')) {
    header('Location: booking.php?status=error&msg=Please+choose+a+future+date');
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO bookings (first_name, last_name, email, phone, num_guests, booking_date, booking_time, special_requests)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
if ($stmt->execute([
    $first_name, $last_name, $email, $phone,
    $num_guests, $booking_date, $booking_time, $special_req
])) {
    header('Location: booking.php?status=success');
} else {
    header('Location: booking.php?status=error&msg=Database+error.+Please+try+again.');
}
exit;
