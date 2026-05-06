<?php
require 'db_connect.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '&pound;' . number_format((float)$value, 2);
}

$type = ($_GET['type'] ?? '') === 'collection' ? 'collection' : 'delivery';
$ref = trim($_GET['ref'] ?? '');

if ($ref === '') {
    http_response_code(400);
    echo 'Missing order reference.';
    exit;
}

if ($type === 'delivery') {
    $st = $pdo->prepare("SELECT * FROM delivery_orders WHERE order_ref = ? AND payment_status = 'Paid'");
    $st->execute([$ref]);
    $order = $st->fetch();
    $items_sql = $order ? 'SELECT item_name, quantity, unit_price FROM delivery_order_items WHERE order_id = ' . (int)$order['id'] : null;
} else {
    $st = $pdo->prepare("SELECT * FROM collection_orders WHERE order_ref = ? AND payment_status = 'Paid'");
    $st->execute([$ref]);
    $order = $st->fetch();
    $items_sql = $order ? 'SELECT item_name, quantity, unit_price FROM collection_order_items WHERE order_id = ' . (int)$order['id'] : null;
}

if (!$order) {
    http_response_code(404);
    echo 'Paid order not found.';
    exit;
}

$paySt = $pdo->prepare('SELECT * FROM payments WHERE order_ref = ? ORDER BY paid_at DESC LIMIT 1');
$paySt->execute([$ref]);
$payment = $paySt->fetch();

$items = [];
$items_res = $pdo->query($items_sql);
while ($item = $items_res->fetch()) {
    $items[] = $item;
}

$orderFeedback = null;
try {
    $fbSt = $pdo->prepare(
        'SELECT rating, feedback_text, created_at FROM order_feedback WHERE order_ref = ? AND order_type = ? LIMIT 1'
    );
    $fbSt->execute([$ref, $type]);
    $orderFeedback = $fbSt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $orderFeedback = null;
}

$filename = 'invoice-' . preg_replace('/[^A-Za-z0-9_-]/', '', $ref) . '.html';
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Invoice <?php echo h($ref); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Rubik', ui-sans-serif, system-ui, sans-serif; color:#222; margin:40px; font-variant-numeric: tabular-nums; }
    .invoice { max-width:800px; margin:0 auto; }
    .top { display:flex; justify-content:space-between; gap:20px; border-bottom:2px solid #222; padding-bottom:16px; margin-bottom:24px; }
    h1 { margin:0; font-size:28px; }
    h2 { font-size:18px; margin-top:28px; }
    p { margin:5px 0; }
    table { width:100%; border-collapse:collapse; margin-top:12px; }
    th, td { border-bottom:1px solid #ddd; padding:10px; text-align:left; font-family: 'Rubik', ui-sans-serif, system-ui, sans-serif; }
    th { background:#f4f4f4; }
    .right { text-align:right; font-variant-numeric: tabular-nums; }
    .totals { max-width:320px; margin-left:auto; margin-top:18px; }
    .total-row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid #ddd; font-family: 'Rubik', ui-sans-serif, system-ui, sans-serif; font-variant-numeric: tabular-nums; }
    .grand { font-weight:bold; font-size:18px; }
  </style>
</head>
<body>
  <div class="invoice">
    <div class="top">
      <div>
        <h1>Royal Nawab</h1>
        <p>Order Invoice</p>
      </div>
      <div>
        <p><strong>Invoice Ref:</strong> <?php echo h($order['order_ref']); ?></p>
        <p><strong>Order Type:</strong> <?php echo ucfirst(h($type)); ?></p>
        <p><strong>Paid:</strong> <?php echo h($order['paid_at'] ?? ''); ?></p>
      </div>
    </div>

    <h2>Customer</h2>
    <p><strong>Name:</strong> <?php echo h($order['full_name']); ?></p>
    <p><strong>Phone:</strong> <?php echo h($order['phone']); ?></p>
    <?php if ($type === 'delivery'): ?>
      <p><strong>Delivery Address:</strong> <?php echo h($order['delivery_address']); ?></p>
      <p><strong>Estimated Delivery:</strong> <?php echo h($order['delivery_time'] ?: 'To be confirmed by restaurant'); ?></p>
    <?php else: ?>
      <p><strong>Collection Time:</strong> <?php echo h($order['collection_time']); ?></p>
    <?php endif; ?>

    <h2>Items</h2>
    <table>
      <thead>
        <tr><th>Item</th><th>Qty</th><th class="right">Unit Price</th><th class="right">Line Total</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr>
            <td><?php echo h($item['item_name']); ?></td>
            <td><?php echo (int)$item['quantity']; ?></td>
            <td class="right"><?php echo money($item['unit_price']); ?></td>
            <td class="right"><?php echo money((float)$item['unit_price'] * (int)$item['quantity']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($orderFeedback): ?>
      <h2>Your review</h2>
      <div style="border:1px solid #ddd; padding:16px 18px; border-radius:8px; background:#fafafa; margin:12px 0 22px;">
        <p style="margin:0 0 10px;font-size:16px;line-height:1.4;color:#856404;">
          <?php
          $stars = (int)$orderFeedback['rating'];
          for ($si = 1; $si <= 5; $si++) {
              echo $si <= $stars ? '<span style="color:#d4af37;">&#9733;</span>' : '<span style="color:#ddd;">&#9734;</span>';
          }
          ?>
          &nbsp; <strong><?php echo (int)$orderFeedback['rating']; ?></strong> / 5
        </p>
        <?php $ft = trim((string)($orderFeedback['feedback_text'] ?? '')); ?>
        <?php if ($ft !== ''): ?>
          <p style="margin:0; white-space:pre-wrap;"><?php echo h($ft); ?></p>
        <?php else: ?>
          <p style="margin:0; color:#666; font-style:italic;">No written feedback was left with this rating.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="totals">
      <div class="total-row"><span>Subtotal</span><span><?php echo money($order['subtotal']); ?></span></div>
      <?php if ($type === 'delivery'): ?>
        <div class="total-row"><span>Delivery Fee</span><span><?php echo money($order['delivery_fee']); ?></span></div>
      <?php endif; ?>
      <div class="total-row grand"><span>Total Paid</span><span><?php echo money($order['total']); ?></span></div>
    </div>

    <h2>Payment</h2>
    <p><strong>Status:</strong> <?php echo h($order['payment_status']); ?></p>
    <p><strong>Payment Ref:</strong> <?php echo h($order['payment_ref']); ?></p>
    <p><strong>Method:</strong> Card<?php echo !empty($payment['card_last4']) ? ' ending ' . h($payment['card_last4']) : ''; ?></p>
    <p>Thank you for your order.</p>
  </div>
</body>
</html>