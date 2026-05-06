<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Delivery – Royal Nawab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <style>
    /* Force high-contrast text in the delivery cart panel */
    .order-cart-panel,
    .order-cart-panel p,
    .order-cart-panel span,
    .order-cart-panel div,
    .order-cart-panel label {
      color: #2a2520 !important;
    }
    .order-cart-panel input,
    .order-cart-panel textarea,
    .order-cart-panel select {
      color: #2a2520 !important;
      background: #fff !important;
    }
    .order-cart-panel input::placeholder,
    .order-cart-panel textarea::placeholder {
      color: #766c5f !important;
    }
    .info-box .form-group label {
      color: #2a2520 !important;
    }
  </style>
</head>
<body>

  <nav class="navbar">
    <div class="logo"><a href="index.html">Royal Nawab</a></div>
    <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
    <ul class="nav-links" id="navLinks">
      <li><a href="booking.php">Booking</a></li>
      <li><a href="delivery.html" class="active-page">Delivery &amp; Collection</a></li>
      <li><a href="deals.php">Deals</a></li>
      <li><a href="menu.php" class="btn-menu">Menu</a></li>
    </ul>
  </nav>

  <section class="page-hero">
    <div><h1>Delivery to Your Doorstep</h1><p>Hot and fresh delivered to your door.</p></div>
  </section>

  <?php
  require 'db_connect.php';
  require_once 'category_image.php';
  $buildDeliveryStatusMessage = function(array $order): string {
    $status_raw = trim((string)($order['status'] ?? ''));
    $status_key = strtolower($status_raw);
    $status = $status_raw !== '' ? $status_raw : 'Pending';
    $time = trim((string)($order['delivery_time'] ?? ''));
    $time_html = htmlspecialchars($time);

    if ($status_key === 'cancelled') {
      return 'Status: <strong>Cancelled</strong>. This order was cancelled. Please contact the restaurant if needed.';
    }
    if ($status_key === 'delivered') {
      return 'Status: <strong>Delivered</strong>. Your order has been delivered.';
    }
    if ($status_key === 'out for delivery') {
      return $time !== ''
        ? 'Status: <strong>Out for Delivery</strong>. Driver is on the way. ETA: <strong>' . $time_html . '</strong>.'
        : 'Status: <strong>Out for Delivery</strong>. Driver is on the way.';
    }
    if ($status_key === 'preparing') {
      return $time !== ''
        ? 'Status: <strong>Preparing</strong>. Your food is being prepared. Estimated delivery: <strong>' . $time_html . '</strong>.'
        : 'Status: <strong>Preparing</strong>. Your food is being prepared.';
    }
    if ($status_key === 'confirmed') {
      return $time !== ''
        ? 'Status: <strong>Confirmed</strong>. Estimated delivery time: <strong>' . $time_html . '</strong>.'
        : 'Status: <strong>Confirmed</strong>. Your order is confirmed and will be prepared shortly.';
    }
    if ($status_key === 'pending') {
      return $time !== ''
        ? 'Status: <strong>Pending</strong>. Estimated delivery time: <strong>' . $time_html . '</strong>.'
        : 'Status: <strong>Pending</strong>. We received your order and the restaurant will confirm it shortly.';
    }

    return $time !== ''
      ? 'Status: <strong>' . htmlspecialchars($status) . '</strong>. Estimated delivery time: <strong>' . $time_html . '</strong>.'
      : 'Status: <strong>' . htmlspecialchars($status) . '</strong>.';
  };

  if (isset($_GET['estimate_ref'])) {
    $raw_ref = trim($_GET['estimate_ref'] ?? '');
    $ref = htmlspecialchars($raw_ref);
    $order = null;
    if ($raw_ref !== '') {
      $est = $pdo->prepare('SELECT status, delivery_time, payment_status FROM delivery_orders WHERE UPPER(TRIM(order_ref)) = UPPER(TRIM(?)) LIMIT 1');
      $est->execute([$raw_ref]);
      $order = $est->fetch();
    }
    if ($order) {
      $estimate_msg = $buildDeliveryStatusMessage($order);
      if (strcasecmp((string)($order['payment_status'] ?? ''), 'Paid') === 0) {
        $estimate_msg .= ' <a href="invoice.php?type=delivery&ref=' . urlencode($raw_ref) . '">Download Invoice</a>';
      }
      echo '<div class="alert alert-success">Order <strong>' . $ref . '</strong>: ' . $estimate_msg . '</div>';
    } else {
      echo '<div class="alert alert-error">&#10007; We could not find an order with that reference.</div>';
    }
  } elseif (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
      $raw_ref = trim($_GET['ref'] ?? '');
      $ref = htmlspecialchars($raw_ref);
      $estimate_msg = 'Status: <strong>Pending</strong>. The restaurant will confirm your order and delivery estimate shortly.';
      if ($raw_ref !== '') {
        $tst = $pdo->prepare('SELECT status, delivery_time, payment_status FROM delivery_orders WHERE UPPER(TRIM(order_ref)) = UPPER(TRIM(?)) LIMIT 1');
        $tst->execute([$raw_ref]);
        $order = $tst->fetch();
        if ($order) {
          $estimate_msg = $buildDeliveryStatusMessage($order);
        }
      }
      echo '<div class="alert alert-success">&#10003; Order placed! Your reference is <strong>' . $ref . '</strong>. ' . $estimate_msg . '</div>';
    } else {
      echo '<div class="alert alert-error">&#10007; ' . htmlspecialchars($_GET['msg'] ?? 'Something went wrong.') . '</div>';
    }
  }

  // Load menu items grouped by category
  $cats = $pdo->query("SELECT * FROM menu_categories ORDER BY sort_order");
  $menu = [];
  while ($c = $cats->fetch()) {
    $items = $pdo->query("SELECT * FROM menu_items WHERE category_id={$c['id']} AND is_available=1 ORDER BY sort_order, name")->fetchAll();
    $c['items'] = $items;
    if (!empty($c['items'])) $menu[] = $c;
  }
  ?>

  <section class="section order-page-section" style="max-width:950px;margin:0 auto;">
    <h2 class="center">Enter Your Delivery Details</h2>
    <br/>
    <form action="checkout.php" method="POST" id="deliveryForm">
      <input type="hidden" name="order_type" value="delivery" />

      <div class="order-layout">

        <!-- LEFT: Item picker (classic list — reliable on all browsers) -->
        <div class="order-box order-picker" id="deliveryMenuPicker">
          <h3>Choose Your Items</h3>
          <?php foreach ($menu as $cat): ?>
            <p class="order-category-label">
              <?php $oci = rn_category_image_src($cat['name']); if ($oci): ?>
              <img src="<?php echo htmlspecialchars($oci); ?>" alt="" class="order-category-thumb" width="32" height="32" loading="lazy" />
              <?php endif; ?>
              <span><?php echo htmlspecialchars($cat['name']); ?></span>
            </p>
            <?php foreach ($cat['items'] as $item): ?>
              <div class="item-row">
                <label><?php echo htmlspecialchars($item['name']); ?></label>
                <span class="item-price">&pound;<?php echo number_format($item['price'], 2); ?></span>
                <div class="qty-stepper" role="group" aria-label="Quantity for <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>">
                  <button type="button" class="qty-btn qty-btn-minus" aria-label="Decrease quantity">&minus;</button>
                  <span class="qty-value">1</span>
                  <button type="button" class="qty-btn qty-btn-plus" aria-label="Increase quantity">+</button>
                </div>
                <button type="button"
                        class="btn btn-sm"
                        data-id="<?php echo $item['id']; ?>"
                        data-price="<?php echo $item['price']; ?>"
                        data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>"
                        onclick="addToCart(this)">Add</button>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>

          <div class="form-group" style="margin-top:16px;">
            <label>Special Requests</label>
            <textarea name="special_requests" placeholder="Allergies, spice level, extra sauces..."></textarea>
          </div>
          <p style="font-size:0.82rem; color:#777; margin-top:12px;">The restaurant will set your estimated delivery time after reviewing the order.</p>
        </div>

        <!-- RIGHT: Cart -->
        <div class="order-box order-cart-panel">
          <h3>Your Cart</h3>
          <div id="cartItems" style="margin-bottom:12px; font-size:0.82rem; color:#2a2520; min-height:40px;">
            No items selected yet.
          </div>
          <div id="cartInputs"></div>
          <div class="cart-row"><span>Subtotal:</span><span id="subtotal">£0.00</span></div>
          <div class="cart-row"><span>Delivery Fee:</span><span id="fee">£0.00</span></div>
          <div class="cart-total"><span>Total:</span><span id="total">£0.00</span></div>
          <div class="cart-actions">
            <button type="button" class="btn btn-light btn-sm" onclick="clearCart()">Clear</button>
          </div>

          <h3 style="margin-top:16px;">Delivery Details</h3>
          <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" placeholder="Full Name" required /></div>
          <div class="form-group"><label>Phone *</label><input type="tel" name="phone" placeholder="Phone Number" required /></div>
          <div class="form-group"><label>Delivery Address *</label><input type="text" name="delivery_address" placeholder="Full delivery address" required /></div>
        </div>
      </div>

      <br/>
      <button type="submit" class="btn btn-full">Continue to Payment</button>
    </form>

    <div class="info-box" style="margin-top:20px;">
      <h4>Ordering Information</h4>
      <p>We deliver Mon–Sat: 10:00 AM – 12:00 AM &nbsp;|&nbsp; Sunday: 2:00 PM – 10:00 PM<br/>Delivery fee: £2.99</p>
    </div>

    <div class="info-box" style="margin-top:20px;">
      <h4>Check Delivery Estimate</h4>
      <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
        <div class="form-group" style="margin-bottom:0; flex:1; min-width:220px;">
          <label>Order Reference</label>
          <input type="text" name="estimate_ref" placeholder="Enter your order reference" required />
        </div>
        <button type="submit" class="btn btn-sm">Check</button>
      </form>
    </div>
  </section>

  <footer class="footer">
    <div class="grid-4">
      <div><h4>Opening Hours</h4><p>Mon–Sat: 10AM–12AM</p><p>Sunday: 2PM–10PM</p></div>
      <div><h4>Contact Us</h4><p>info@royalnawab.com</p><p>📞 +44 1234 567890</p></div>
      <div><h4>Useful Links</h4><ul><li><a href="#">Terms</a></li><li><a href="#">Privacy</a></li><li><a href="menu.php">Menu</a></li></ul></div>
      <div><h4>Social Links</h4><div class="social-icons"><a href="#" class="social">f</a><a href="#" class="social">t</a><a href="#" class="social">in</a></div></div>
    </div>
    <div class="footer-bottom">&copy; 2025 Royal Nawab. All rights reserved.</div>
  </footer>

  <script>
    document.getElementById('hamburger').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('open');
    });

    var CART_STORAGE_KEY = 'rnCartDelivery';

    function saveCartToStorage() {
      try {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
      } catch (err) {}
    }

    function loadCartFromStorage() {
      try {
        var raw = localStorage.getItem(CART_STORAGE_KEY);
        if (!raw) return {};
        var data = JSON.parse(raw);
        if (!data || typeof data !== 'object') return {};
        var out = {};
        Object.keys(data).forEach(function(id) {
          var it = data[id];
          if (!it || typeof it !== 'object') return;
          var qty = parseInt(it.qty, 10);
          if (!(qty > 0)) return;
          var price = parseFloat(it.price);
          if (isNaN(price) || price < 0) return;
          var name = typeof it.name === 'string' ? it.name.trim().slice(0, 220) : '';
          if (!name) return;
          var pid = parseInt(id, 10);
          if (!(pid > 0)) return;
          out[String(pid)] = { name: name, price: price, qty: qty };
        });
        return out;
      } catch (err) {
        return {};
      }
    }

    function escapeHtml(text) {
      var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
      return String(text).replace(/[&<>"']/g, function(ch) { return map[ch] || ch; });
    }

    var cart = loadCartFromStorage();

    function addToCart(btn) {
      var row = btn.closest('.item-row');
      var qtyEl = row.querySelector('.qty-value');
      var qty = qtyEl ? (parseInt(qtyEl.textContent, 10) || 1) : 1;
      var id = btn.dataset.id;
      if (!cart[id]) {
        cart[id] = { name: btn.dataset.name, price: parseFloat(btn.dataset.price), qty: 0 };
      }
      cart[id].qty += qty;
      if (qtyEl) qtyEl.textContent = '1';
      renderCart();
    }

    (function () {
      var picker = document.getElementById('deliveryMenuPicker');
      if (!picker) return;
      picker.addEventListener('click', function(e) {
      var dec = e.target.closest('.qty-btn-minus');
      var inc = e.target.closest('.qty-btn-plus');
      if (!dec && !inc) return;
      var row = (dec || inc).closest('.item-row');
      if (!row) return;
      var valEl = row.querySelector('.qty-value');
      if (!valEl) return;
      var n = parseInt(valEl.textContent, 10) || 1;
      if (dec) {
        valEl.textContent = String(Math.max(1, n - 1));
      } else {
        valEl.textContent = String(n + 1);
      }
    });
    })();

    function removeCartItem(id) {
      delete cart[id];
      renderCart();
    }

    function renderCart() {
      var sub = 0, lines = [], inputs = [];
      Object.keys(cart).forEach(function(id) {
        var item = cart[id];
        sub += item.qty * item.price;
        lines.push('<div style="padding:6px 0; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; gap:8px;"><span>' + escapeHtml(item.name) + ' x' + item.qty + '</span><button type="button" class="btn btn-light btn-sm" onclick="removeCartItem(\'' + String(id).replace(/[^0-9]/g, '') + '\')">Remove</button></div>');
        inputs.push('<input type="hidden" name="items[' + id + ']" value="' + item.qty + '" />');
      });
      var fee = sub > 0 ? 2.99 : 0;
      document.getElementById('subtotal').textContent = '£' + sub.toFixed(2);
      document.getElementById('fee').textContent = '£' + fee.toFixed(2);
      document.getElementById('total').textContent = '£' + (sub + fee).toFixed(2);
      document.getElementById('cartItems').innerHTML = lines.length
        ? lines.join('')
        : '<span style="color:#5c554c; font-size:0.82rem;">No items selected yet.</span>';
      document.getElementById('cartInputs').innerHTML = inputs.join('');
      saveCartToStorage();
    }

    function clearCart() {
      cart = {};
      renderCart();
    }

    renderCart();

    document.getElementById('deliveryForm').addEventListener('submit', function(e) {
      if (!Object.keys(cart).length) {
        e.preventDefault();
        alert('Please add at least one item to your cart.');
      }
    });
  </script>

</body>
</html>