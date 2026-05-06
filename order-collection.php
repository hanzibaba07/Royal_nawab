<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Collection – Royal Nawab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
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
    <div><h1>Order for Collection</h1><p>Order ahead and collect hot from us.</p></div>
  </section>

  <?php
  require 'db_connect.php';
  require_once 'category_image.php';
  if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
      $ref = htmlspecialchars($_GET['ref'] ?? '');
      echo '<div class="alert alert-success">&#10003; Order placed! Reference: <strong>' . $ref . '</strong>. Please collect in 15–20 minutes.</div>';
    } else {
      echo '<div class="alert alert-error">&#10007; ' . htmlspecialchars($_GET['msg'] ?? 'Something went wrong.') . '</div>';
    }
  }

  $cats = $pdo->query("SELECT * FROM menu_categories ORDER BY sort_order");
  $menu = [];
  while ($c = $cats->fetch()) {
    $items = $pdo->query("SELECT * FROM menu_items WHERE category_id={$c['id']} AND is_available=1 ORDER BY sort_order, name")->fetchAll();
    $c['items'] = $items;
    if (!empty($c['items'])) $menu[] = $c;
  }
  ?>

  <section class="section order-page-section">
    <h2 class="center">Choose Your Items</h2>
    <br/>
    <form action="checkout.php" method="POST" id="collectionForm">
      <input type="hidden" name="order_type" value="collection" />

      <div class="order-layout">
        <div class="order-box order-picker">
          <h3>Menu Items</h3>
          <p class="order-picker-hint">Open a category below, set a quantity, then tap Add to cart.</p>
          <?php
          $__cat_index = 0;
          foreach ($menu as $cat):
            $__cat_index++;
            $__oci = rn_category_image_src($cat['name']);
            $__count = count($cat['items']);
          ?>
          <details class="order-category-block"<?php echo $__cat_index === 1 ? ' open' : ''; ?>>
            <summary class="order-category-summary">
              <?php if ($__oci): ?>
              <img src="<?php echo htmlspecialchars($__oci); ?>" alt="" class="order-category-summary-thumb" width="40" height="40" loading="lazy" />
              <?php endif; ?>
              <span class="order-category-summary-title"><?php echo htmlspecialchars($cat['name']); ?></span>
              <span class="order-category-summary-count"><?php echo (int)$__count; ?> item<?php echo $__count !== 1 ? 's' : ''; ?></span>
              <span class="order-category-summary-chevron" aria-hidden="true"></span>
            </summary>
            <div class="menu-item-cards">
            <?php foreach ($cat['items'] as $item): ?>
              <div class="menu-item-card">
                <div class="menu-item-card-body">
                  <div class="menu-item-card-head">
                    <span class="menu-item-card-name"><?php echo htmlspecialchars($item['name']); ?></span>
                    <span class="menu-item-card-price">&pound;<?php echo number_format($item['price'], 2); ?></span>
                  </div>
                  <?php if (trim((string)($item['description'] ?? '')) !== ''): ?>
                  <p class="menu-item-card-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                  <?php endif; ?>
                </div>
                <div class="menu-item-card-actions">
                  <div class="qty-stepper" role="group" aria-label="Quantity for <?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>">
                    <button type="button" class="qty-btn qty-btn-minus" aria-label="Decrease quantity">&minus;</button>
                    <span class="qty-value">1</span>
                    <button type="button" class="qty-btn qty-btn-plus" aria-label="Increase quantity">+</button>
                  </div>
                  <button type="button"
                          class="btn btn-sm menu-item-add-btn"
                          data-id="<?php echo $item['id']; ?>"
                          data-price="<?php echo $item['price']; ?>"
                          data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>"
                          onclick="addToCart(this)">Add</button>
                </div>
              </div>
            <?php endforeach; ?>
            </div>
          </details>
          <?php endforeach; ?>

          <div class="form-group" style="margin-top:16px;">
            <label>Special Requests</label>
            <textarea name="special_requests" placeholder="Allergies, spice level..."></textarea>
          </div>
          <div class="form-group">
            <label>Collection Time</label>
            <select name="collection_time">
              <option>ASAP (15–20 mins)</option>
              <option>In 30 minutes</option>
              <option>In 45 minutes</option>
              <option>In 1 hour</option>
            </select>
          </div>
        </div>

        <div class="order-box order-cart-panel">
          <h3>Your Cart</h3>
          <div id="cartItems" style="margin-bottom:12px; font-size:0.82rem; color:#888; min-height:40px;">
            No items selected yet.
          </div>
          <div id="cartInputs"></div>
          <div class="cart-row"><span>Subtotal:</span><span id="subtotal">£0.00</span></div>
          <div class="cart-row"><span>Collection Fee:</span><span style="color:#2a7a2a; font-weight:700;">FREE</span></div>
          <div class="cart-total"><span>Total:</span><span id="total">£0.00</span></div>
          <div class="cart-actions">
            <button type="button" class="btn btn-light btn-sm" onclick="clearCart()">Clear</button>
          </div>

          <h3 style="margin-top:16px;">Your Details</h3>
          <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" placeholder="Full Name" required /></div>
          <div class="form-group"><label>Phone *</label><input type="tel" name="phone" placeholder="Phone Number" required /></div>
        </div>
      </div>

      <br/>
      <button type="submit" class="btn btn-full">Continue to Payment</button>
    </form>

    <div class="info-box" style="margin-top:20px;">
      <h4>Collection Information</h4>
      <p>Mon–Sat: 10:00 AM – 12:00 AM &nbsp;|&nbsp; Sunday: 2:00 PM – 10:00 PM<br/>Collection is completely free.</p>
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
    var CART_STORAGE_KEY = 'rnCartCollection';

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
      var row = btn.closest('.menu-item-card');
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
      document.getElementById('subtotal').textContent = '£' + sub.toFixed(2);
      document.getElementById('total').textContent = '£' + sub.toFixed(2);
      document.getElementById('cartItems').innerHTML = lines.length
        ? lines.join('')
        : '<span style="color:#aaa; font-size:0.82rem;">No items selected yet.</span>';
      document.getElementById('cartInputs').innerHTML = inputs.join('');
      saveCartToStorage();
    }

    function clearCart() {
      cart = {};
      renderCart();
    }

    renderCart();

    document.querySelector('.order-picker').addEventListener('click', function(e) {
      var dec = e.target.closest('.qty-btn-minus');
      var inc = e.target.closest('.qty-btn-plus');
      if (!dec && !inc) return;
      var row = (dec || inc).closest('.menu-item-card');
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

    document.getElementById('collectionForm').addEventListener('submit', function(e) {
      if (!Object.keys(cart).length) {
        e.preventDefault();
        alert('Please add at least one item to your cart.');
      }
    });
  </script>

</body>
</html>