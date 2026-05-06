<?php
header('Content-Type: application/json; charset=UTF-8');
require 'db_connect.php';

function ensure_feedback_schema(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS order_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_ref VARCHAR(20) NOT NULL,
            order_type VARCHAR(20) NOT NULL,
            customer_name VARCHAR(150) NULL,
            menu_item_id INT NULL,
            menu_item_name VARCHAR(120) NULL,
            rating TINYINT NOT NULL,
            feedback_text TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_feedback_order (order_ref, order_type),
            KEY idx_feedback_item (menu_item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $colStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_feedback' AND COLUMN_NAME = ?"
    );

    $colStmt->execute(['menu_item_id']);
    if ((int)$colStmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE order_feedback ADD COLUMN menu_item_id INT NULL");
    }

    $colStmt->execute(['menu_item_name']);
    if ((int)$colStmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE order_feedback ADD COLUMN menu_item_name VARCHAR(120) NULL");
    }
}

ensure_feedback_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = [];
if ($raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (!$payload) {
    $payload = $_POST;
}

$order_ref = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($payload['order_ref'] ?? ''));
$order_type = (($payload['order_type'] ?? '') === 'collection') ? 'collection' : 'delivery';
$rating = (int)($payload['rating'] ?? 0);
$feedback_text = trim((string)($payload['feedback'] ?? ''));
$menu_item_id = (int)($payload['menu_item_id'] ?? 0);
$menu_item_name = trim((string)($payload['menu_item_name'] ?? ''));
if (strlen($feedback_text) > 2000) {
    $feedback_text = substr($feedback_text, 0, 2000);
}

if ($order_ref === '' || $rating < 1 || $rating > 5) {
    echo json_encode(['ok' => false, 'error' => 'Please choose a star rating (1–5).']);
    exit;
}

if ($order_type === 'delivery') {
    $stmt = $pdo->prepare(
        'SELECT id, full_name FROM delivery_orders WHERE order_ref = ? AND payment_status = ? LIMIT 1'
    );
    $stmt->execute([$order_ref, 'Paid']);
} else {
    $stmt = $pdo->prepare(
        'SELECT id, full_name FROM collection_orders WHERE order_ref = ? AND payment_status = ? LIMIT 1'
    );
    $stmt->execute([$order_ref, 'Paid']);
}
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Order not found or not paid yet.']);
    exit;
}

$name = trim((string)($row['full_name'] ?? ''));
$order_id = (int)($row['id'] ?? 0);

$ordered_names = [];
if ($order_id > 0) {
    if ($order_type === 'delivery') {
        $itemStmt = $pdo->prepare('SELECT item_name FROM delivery_order_items WHERE order_id = ?');
    } else {
        $itemStmt = $pdo->prepare('SELECT item_name FROM collection_order_items WHERE order_id = ?');
    }
    $itemStmt->execute([$order_id]);
    while ($it = $itemStmt->fetch(PDO::FETCH_ASSOC)) {
        $rawName = trim((string)($it['item_name'] ?? ''));
        if ($rawName !== '') {
            $ordered_names[strtolower($rawName)] = $rawName;
        }
    }
}

$final_item_id = null;
$final_item_name = null;
if ($menu_item_id > 0) {
    $menuStmt = $pdo->prepare('SELECT id, name FROM menu_items WHERE id = ? LIMIT 1');
    $menuStmt->execute([$menu_item_id]);
    $menuRow = $menuStmt->fetch(PDO::FETCH_ASSOC);
    if ($menuRow) {
        $candidateName = trim((string)$menuRow['name']);
        $candidateKey = strtolower($candidateName);
        if (isset($ordered_names[$candidateKey])) {
            $final_item_id = (int)$menuRow['id'];
            $final_item_name = $ordered_names[$candidateKey];
        }
    }
}
if ($final_item_name === null && $menu_item_name !== '') {
    $nameKey = strtolower($menu_item_name);
    if (isset($ordered_names[$nameKey])) {
        $final_item_name = $ordered_names[$nameKey];
    }
}
if ($final_item_name === null) {
    echo json_encode(['ok' => false, 'error' => 'Please choose a valid ordered item to review.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO order_feedback (order_ref, order_type, customer_name, menu_item_id, menu_item_name, rating, feedback_text) VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $order_ref,
        $order_type,
        $name !== '' ? $name : null,
        $final_item_id,
        $final_item_name,
        $rating,
        $feedback_text === '' ? null : $feedback_text,
    ]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    $errno = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
    if ($errno === 1062) {
        echo json_encode(['ok' => false, 'error' => 'You have already left feedback for this order.']);
        exit;
    }
    echo json_encode(['ok' => false, 'error' => 'Could not save feedback. Please try again.']);
}