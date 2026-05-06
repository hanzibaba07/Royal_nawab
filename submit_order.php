<?php
// =============================================
// submit_order.php  – handles delivery & collection
// =============================================
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: delivery.html'); exit;
}

$order_type   = $_POST['order_type'] ?? 'delivery';
$full_name    = trim($_POST['full_name']    ?? '');
$phone        = trim($_POST['phone']        ?? '');
$special_req  = trim($_POST['special_requests'] ?? '');
$items        = $_POST['items'] ?? [];   // ['menu_item_id' => qty, ...]

// Redirect target
$redirect_page = ($order_type === 'collection') ? 'order-collection.php' : 'order-delivery.php';

// Validate
if (empty($full_name) || empty($phone)) {
    header("Location: {$redirect_page}?status=error&msg=Please+fill+in+all+required+fields");
    exit;
}
if ($order_type === 'delivery' && empty(trim($_POST['delivery_address'] ?? ''))) {
    header("Location: {$redirect_page}?status=error&msg=Please+enter+your+delivery+address");
    exit;
}

// Build validated item list from DB prices
$validated = [];
$subtotal = 0;

$itemStmt = $pdo->prepare('SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1');

foreach ($items as $item_id => $qty) {
    $qty = (int)$qty;
    $item_id = (int)$item_id;
    if ($qty <= 0) continue;

    $itemStmt->execute([$item_id]);
    $row = $itemStmt->fetch();
    if (!$row) continue;

    $line = $row['price'] * $qty;
    $subtotal += $line;
    $validated[] = ['id' => $row['id'], 'name' => $row['name'], 'price' => $row['price'], 'qty' => $qty];
}

if (empty($validated)) {
    header("Location: {$redirect_page}?status=error&msg=Please+select+at+least+one+item");
    exit;
}

$order_ref = 'RN' . strtoupper(substr(uniqid(), -6));

// ---------- DELIVERY ----------
if ($order_type === 'delivery') {
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_fee     = 2.99;
    $total            = $subtotal + $delivery_fee;

    $stmt = $pdo->prepare(
        'INSERT INTO delivery_orders (order_ref, full_name, phone, delivery_address, special_requests, subtotal, delivery_fee, total)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    if (!$stmt->execute([
        $order_ref, $full_name, $phone, $delivery_address,
        $special_req, $subtotal, $delivery_fee, $total
    ])) {
        header("Location: {$redirect_page}?status=error&msg=Database+error.+Please+try+again.");
        exit;
    }
    $order_id = (int)$pdo->lastInsertId();

    $ist = $pdo->prepare(
        'INSERT INTO delivery_order_items (order_id, item_name, quantity, unit_price) VALUES (?,?,?,?)'
    );
    foreach ($validated as $v) {
        $ist->execute([$order_id, $v['name'], $v['qty'], $v['price']]);
    }

    header("Location: order-delivery.php?status=success&ref=" . urlencode($order_ref));
    exit;
}

// ---------- COLLECTION ----------
if ($order_type === 'collection') {
    $collection_time = trim($_POST['collection_time'] ?? 'ASAP');
    $total = $subtotal; // no fee

    $stmt = $pdo->prepare(
        'INSERT INTO collection_orders (order_ref, full_name, phone, special_requests, collection_time, subtotal, total)
         VALUES (?,?,?,?,?,?,?)'
    );
    if (!$stmt->execute([
        $order_ref, $full_name, $phone,
        $special_req, $collection_time, $subtotal, $total
    ])) {
        header("Location: {$redirect_page}?status=error&msg=Database+error.+Please+try+again.");
        exit;
    }
    $order_id = (int)$pdo->lastInsertId();

    $ist = $pdo->prepare(
        'INSERT INTO collection_order_items (order_id, item_name, quantity, unit_price) VALUES (?,?,?,?)'
    );
    foreach ($validated as $v) {
        $ist->execute([$order_id, $v['name'], $v['qty'], $v['price']]);
    }

    header("Location: order-collection.php?status=success&ref=" . urlencode($order_ref));
    exit;
}

header('Location: delivery.html');
exit;
