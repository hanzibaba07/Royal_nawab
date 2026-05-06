<?php
require 'db_connect.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '&pound;' . number_format((float)$value, 2);
}

function build_items($pdo, $items) {
    $validated = [];
    $subtotal = 0;
    $stmt = $pdo->prepare('SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1');

    foreach ($items as $item_id => $qty) {
        $item_id = (int)$item_id;
        $qty = (int)$qty;
        if ($item_id <= 0 || $qty <= 0) continue;

        $stmt->execute([$item_id]);
        $row = $stmt->fetch();
        if (!$row) continue;

        $line_total = (float)$row['price'] * $qty;
        $subtotal += $line_total;
        $validated[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'qty' => $qty,
            'line_total' => $line_total
        ];
    }

    return [$validated, $subtotal];
}

function redirect_error($order_type, $message) {
    $page = $order_type === 'collection' ? 'order-collection.php' : 'order-delivery.php';
    header('Location: ' . $page . '?status=error&msg=' . urlencode($message));
    exit;
}

function first_available_driver_id($pdo) {
    $busy_statuses = "'Pending','Confirmed','Preparing','Out for Delivery'";
    $sql = "
        SELECT d.id
        FROM delivery_drivers d
        WHERE d.is_active = 1
          AND NOT EXISTS (
            SELECT 1
            FROM delivery_orders o
            WHERE o.driver_id = d.id
              AND o.status IN ($busy_statuses)
          )
        ORDER BY d.id ASC
        LIMIT 1
    ";
    $driver = $pdo->query($sql)->fetch();
    return $driver ? (int)$driver['id'] : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: order-delivery.php');
    exit;
}

$action = $_POST['action'] ?? 'review';
$order_type = $_POST['order_type'] === 'collection' ? 'collection' : 'delivery';
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$special_req = trim($_POST['special_requests'] ?? '');
$delivery_address = trim($_POST['delivery_address'] ?? '');
$collection_time = trim($_POST['collection_time'] ?? 'ASAP');
$items = $_POST['items'] ?? [];

if ($full_name === '' || $phone === '') {
    redirect_error($order_type, 'Please fill in all required fields.');
}
if ($order_type === 'delivery' && $delivery_address === '') {
    redirect_error($order_type, 'Please enter your delivery address.');
}

[$validated, $subtotal] = build_items($pdo, $items);
if (empty($validated)) {
    redirect_error($order_type, 'Please add at least one item to your cart.');
}

$delivery_fee = $order_type === 'delivery' ? 2.99 : 0;
$total = $subtotal + $delivery_fee;
$error = '';
$success = false;
$order_ref = '';
$payment_ref = '';
$cardholder_name = trim($_POST['cardholder_name'] ?? '');
$card_number = trim($_POST['card_number'] ?? '');

if ($action === 'pay') {
    $order_ref = 'RN' . strtoupper(substr(uniqid(), -6));
    $payment_ref = 'PAY' . strtoupper(substr(uniqid(), -8));
    $digits = preg_replace('/\D/', '', $card_number);
    $card_last4 = substr($digits, -4);
    $assigned_driver_id = null;

    $pdo->beginTransaction();

    try {
        if ($order_type === 'delivery') {
            $assigned_driver_id = first_available_driver_id($pdo);
            $stmt = $pdo->prepare(
                "INSERT INTO delivery_orders (order_ref, full_name, phone, delivery_address, special_requests, subtotal, delivery_fee, total, payment_method, payment_status, payment_ref, paid_at, driver_id, status)
                 VALUES (?,?,?,?,?,?,?,?,'card','Paid',?,NOW(),?,'Confirmed')"
            );
            $stmt->execute([
                $order_ref, $full_name, $phone, $delivery_address, $special_req,
                $subtotal, $delivery_fee, $total, $payment_ref, $assigned_driver_id
            ]);
            $order_id = (int)$pdo->lastInsertId();

            $item_stmt = $pdo->prepare(
                'INSERT INTO delivery_order_items (order_id, item_name, quantity, unit_price) VALUES (?,?,?,?)'
            );
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO collection_orders (order_ref, full_name, phone, special_requests, collection_time, subtotal, total, payment_method, payment_status, payment_ref, paid_at, status)
                 VALUES (?,?,?,?,?,?,?,'card','Paid',?,NOW(),'Confirmed')"
            );
            $stmt->execute([
                $order_ref, $full_name, $phone, $special_req, $collection_time,
                $subtotal, $total, $payment_ref
            ]);
            $order_id = (int)$pdo->lastInsertId();

            $item_stmt = $pdo->prepare(
                'INSERT INTO collection_order_items (order_id, item_name, quantity, unit_price) VALUES (?,?,?,?)'
            );
        }

        foreach ($validated as $item) {
            $item_stmt->execute([$order_id, $item['name'], $item['qty'], $item['price']]);
        }

        $pay_stmt = $pdo->prepare(
            "INSERT INTO payments (order_type, order_id, order_ref, amount, payment_method, payment_status, payment_ref, cardholder_name, card_last4)
             VALUES (?,?,?,?,'card','Paid',?,?,?)"
        );
        $pay_stmt->execute([
            $order_type, $order_id, $order_ref, $total, $payment_ref, $cardholder_name, $card_last4
        ]);

        $pdo->commit();
        $success = true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        $error = 'Payment could not be recorded. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Checkout - Royal Nawab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <style>
    .checkout-wrap { max-width: 720px; margin: 0 auto; padding: 40px 20px 60px; }
    .success-actions { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:18px; }
    .success-stack { margin-top: 28px; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo"><a href="index.html">Royal Nawab</a></div>
    <button class="hamburger" id="hamburger"><span></span><span></span><span></span></button>
    <ul class="nav-links" id="navLinks">
      <li><a href="booking.php">Booking</a></li>
      <li><a href="delivery.html">Delivery &amp; Collection</a></li>
      <li><a href="deals.php">Deals</a></li>
      <li><a href="menu.php" class="btn-menu">Menu</a></li>
    </ul>
  </nav>

  <section class="page-hero">
    <div><h1>Checkout</h1><p>Pay by card to confirm your order.</p></div>
  </section>

  <div class="checkout-wrap">
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-stack">
      <div class="success-box">
        <h2>Payment Successful</h2>
        <p>Your <?php echo h($order_type); ?> order has been confirmed.</p>
        <p><strong>Order Ref:</strong> <?php echo h($order_ref); ?></p>
        <p><strong>Payment Ref:</strong> <?php echo h($payment_ref); ?></p>
        <?php if ($order_type === 'delivery'): ?>
          <p>The restaurant will set your estimated delivery time shortly.</p>
        <?php else: ?>
          <p>Please collect at: <?php echo h($collection_time); ?></p>
        <?php endif; ?>
        <div class="success-actions">
          <a class="btn" href="invoice.php?type=<?php echo h($order_type); ?>&ref=<?php echo urlencode($order_ref); ?>">Download Invoice</a>
          <a class="btn btn-light" href="index.html">Back to Home</a>
        </div>
      </div>

      <div id="yourReviewBanner" class="checkout-review-banner" hidden>
        <div class="checkout-review-banner-inner">
          <h4>Your review</h4>
          <div class="checkout-review-banner-stars" id="yourReviewStars" aria-hidden="true"></div>
          <p class="checkout-review-banner-label" id="yourReviewRatingLabel"></p>
          <p class="checkout-review-banner-text" id="yourReviewText"></p>
        </div>
      </div>

      <div class="feedback-modal-overlay" id="feedbackModalOverlay" role="dialog" aria-modal="true" aria-labelledby="feedbackTitle" hidden>
        <div class="feedback-modal">
          <h3 id="feedbackTitle">Rate your experience</h3>
          <p>Your order reference: <strong><?php echo h($order_ref); ?></strong>. Optionally share a note for the kitchen and team.</p>
          <p class="feedback-rating-readout is-placeholder" id="feedbackRatingReadout">Tap a star &mdash; 1 is lowest, 5 is best.</p>
          <div class="feedback-stars" id="feedbackStars" role="radiogroup" aria-label="Rating">
            <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" class="feedback-star-btn" data-rating="<?php echo $s; ?>" aria-pressed="false" aria-label="<?php echo $s; ?> out of 5 stars">&#9733;</button>
            <?php endfor; ?>
          </div>
          <div class="feedback-modal-fields">
            <label for="feedbackItem">Which item are you reviewing? <span style="font-weight:400;color:var(--card-muted);">(required)</span></label>
            <select id="feedbackItem">
              <option value="">Select an item</option>
              <?php foreach ($validated as $item): ?>
              <option value="<?php echo (int)$item['id']; ?>" data-item-name="<?php echo h($item['name']); ?>"><?php echo h($item['name']); ?></option>
              <?php endforeach; ?>
            </select>
            <label for="feedbackText">Comments <span style="font-weight:400;color:var(--card-muted);">(optional)</span></label>
            <textarea id="feedbackText" maxlength="2000" placeholder="Food quality, packaging, delivery speed…"></textarea>
          </div>
          <div class="feedback-modal-actions">
            <button type="button" class="btn btn-light btn-sm" id="feedbackSkip">Not now</button>
            <button type="button" class="btn btn-sm" id="feedbackSubmit">Submit feedback</button>
          </div>
          <p class="feedback-modal-msg" id="feedbackMsg" aria-live="polite"></p>
        </div>
      </div>

      <script>
        try {
          localStorage.removeItem(<?php echo json_encode($order_type === 'collection' ? 'rnCartCollection' : 'rnCartDelivery'); ?>);
        } catch (e) {}
      </script>
      <script>
        (function () {
          var overlay = document.getElementById('feedbackModalOverlay');
          if (!overlay) return;

          var stars = document.querySelectorAll('#feedbackStars .feedback-star-btn');
          var rating = 0;
          var textarea = document.getElementById('feedbackText');
          var reviewItemSelect = document.getElementById('feedbackItem');
          var msg = document.getElementById('feedbackMsg');
          var readout = document.getElementById('feedbackRatingReadout');
          var banner = document.getElementById('yourReviewBanner');
          var yourStarsEl = document.getElementById('yourReviewStars');
          var yourLabelEl = document.getElementById('yourReviewRatingLabel');
          var yourTextEl = document.getElementById('yourReviewText');
          var ref = <?php echo json_encode($order_ref); ?>;
          var ordType = <?php echo json_encode($order_type); ?>;
          var reviewKey = 'rnCheckoutReview:v1:' + ordType + ':' + ref;

          function starString(n) {
            var i, s = '';
            for (i = 0; i < 5; i++) s += (i < n) ? '\u2605 ' : '\u2606 ';
            return s.trim();
          }

          function refreshReadout() {
            if (!readout) return;
            if (rating < 1) {
              readout.textContent = 'Tap a star \u2014 1 is lowest, 5 is best.';
              readout.classList.add('is-placeholder');
            } else {
              readout.textContent = 'You selected ' + rating + ' out of 5 stars.';
              readout.classList.remove('is-placeholder');
            }
          }

          function showReviewBanner(n, feedbackText) {
            if (!banner || !yourStarsEl || !yourLabelEl || !yourTextEl) return;
            yourStarsEl.textContent = starString(n).replace(/\s+/g, '  ');
            yourLabelEl.textContent = 'Rating: ' + n + ' out of 5.';
            var t = feedbackText ? String(feedbackText).trim() : '';
            if (t) {
              yourTextEl.textContent = t;
              yourTextEl.classList.remove('checkout-review-muted');
              yourTextEl.style.color = '';
              yourTextEl.style.fontStyle = '';
            } else {
              yourTextEl.textContent = 'No written comment.';
              yourTextEl.classList.add('checkout-review-muted');
            }
            banner.hidden = false;
          }

          function saveReviewLocally(n, feedbackText) {
            var selectedItemId = reviewItemSelect ? (parseInt(reviewItemSelect.value, 10) || 0) : 0;
            try {
              localStorage.setItem(reviewKey, JSON.stringify({
                rating: n,
                feedback: feedbackText || '',
                menu_item_id: selectedItemId > 0 ? selectedItemId : null,
                ts: Date.now()
              }));
            } catch (e) {}
          }

          function restoreReviewLocally() {
            try {
              var raw = localStorage.getItem(reviewKey);
              if (!raw) return null;
              var o = JSON.parse(raw);
              if (!o || typeof o.rating !== 'number') return null;
              return o;
            } catch (e) {
              return null;
            }
          }

          function setStars(n) {
            rating = Math.max(0, Math.min(5, parseInt(n, 10) || 0));
            stars.forEach(function (btn) {
              var rn = parseInt(btn.getAttribute('data-rating'), 10);
              var on = rating > 0 && rn <= rating;
              btn.classList.toggle('is-on', on);
              btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            refreshReadout();
          }

          stars.forEach(function (btn) {
            btn.addEventListener('click', function () {
              setStars(parseInt(btn.getAttribute('data-rating'), 10));
              msg.textContent = '';
              msg.className = 'feedback-modal-msg';
            });
          });

          var savedLocal = restoreReviewLocally();
          if (savedLocal) {
            setStars(savedLocal.rating);
            if (textarea) textarea.value = savedLocal.feedback || '';
            if (reviewItemSelect && savedLocal.menu_item_id) {
              reviewItemSelect.value = String(savedLocal.menu_item_id);
            }
            showReviewBanner(savedLocal.rating, savedLocal.feedback || '');
          } else {
            refreshReadout();
          }

          function closeModal() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('hidden', '');
            document.body.style.overflow = '';
          }

          document.getElementById('feedbackSkip').addEventListener('click', closeModal);

          document.getElementById('feedbackSubmit').addEventListener('click', function () {
            msg.textContent = '';
            msg.className = 'feedback-modal-msg';
            if (rating < 1) {
              msg.textContent = 'Please choose at least one star.';
              msg.classList.add('is-err');
              return;
            }
            var selectedItemId = reviewItemSelect ? (parseInt(reviewItemSelect.value, 10) || 0) : 0;
            if (selectedItemId < 1) {
              msg.textContent = 'Please select which item you are reviewing.';
              msg.classList.add('is-err');
              return;
            }
            var selectedItemName = '';
            if (reviewItemSelect && reviewItemSelect.selectedIndex >= 0) {
              selectedItemName = reviewItemSelect.options[reviewItemSelect.selectedIndex].getAttribute('data-item-name') || '';
            }
            var fb = textarea ? textarea.value : '';
            var body = JSON.stringify({
              order_ref: ref,
              order_type: ordType,
              rating: rating,
              feedback: fb,
              menu_item_id: selectedItemId,
              menu_item_name: selectedItemName
            });
            fetch('submit_feedback.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body })
              .then(function (res) { return res.json(); })
              .then(function (data) {
                if (data.ok) {
                  saveReviewLocally(rating, fb);
                  showReviewBanner(rating, fb);
                  msg.textContent = 'Saved. Thank you!';
                  msg.classList.add('is-ok');
                  setTimeout(closeModal, 900);
                  return;
                }
                if (data.error && data.error.indexOf('already') !== -1) {
                  saveReviewLocally(rating, fb);
                  showReviewBanner(rating, fb);
                  msg.textContent = 'This order already has feedback on file.';
                  msg.classList.add('is-ok');
                  setTimeout(closeModal, 900);
                  return;
                }
                msg.textContent = data.error || 'Could not save feedback.';
                msg.classList.add('is-err');
              })
              .catch(function () {
                msg.textContent = 'Network error. You can try again later.';
                msg.classList.add('is-err');
              });
          });

          overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
          });
          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeModal();
          });

          function openPrompt() {
            overlay.removeAttribute('hidden');
            overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
          }

          if (!savedLocal) {
            setTimeout(openPrompt, 550);
          }
        })();
      </script>
      </div><!-- .success-stack -->

    <?php else: ?>
      <div class="order-summary-box">
        <h3>Order Summary</h3>
        <?php foreach ($validated as $item): ?>
          <div class="summary-row">
            <span><?php echo h($item['name']); ?> x<?php echo (int)$item['qty']; ?></span>
            <span><?php echo money($item['line_total']); ?></span>
          </div>
        <?php endforeach; ?>
        <div class="summary-row"><span>Order Type:</span><span><?php echo ucfirst(h($order_type)); ?></span></div>
        <div class="summary-row"><span>Subtotal:</span><span><?php echo money($subtotal); ?></span></div>
        <div class="summary-row"><span><?php echo $order_type === 'delivery' ? 'Delivery Fee:' : 'Collection Fee:'; ?></span><span><?php echo $delivery_fee > 0 ? money($delivery_fee) : 'FREE'; ?></span></div>
        <div class="summary-row total"><span>Total:</span><span><?php echo money($total); ?></span></div>
      </div>

      <form method="POST">
        <input type="hidden" name="action" value="pay" />
        <input type="hidden" name="order_type" value="<?php echo h($order_type); ?>" />
        <input type="hidden" name="full_name" value="<?php echo h($full_name); ?>" />
        <input type="hidden" name="phone" value="<?php echo h($phone); ?>" />
        <input type="hidden" name="special_requests" value="<?php echo h($special_req); ?>" />
        <input type="hidden" name="delivery_address" value="<?php echo h($delivery_address); ?>" />
        <input type="hidden" name="collection_time" value="<?php echo h($collection_time); ?>" />
        <?php foreach ($validated as $item): ?>
          <input type="hidden" name="items[<?php echo (int)$item['id']; ?>]" value="<?php echo (int)$item['qty']; ?>" />
        <?php endforeach; ?>

        <h2 style="font-size:1.1rem; margin-bottom:16px;">Card Payment</h2>
        <div class="card-form">
          <div class="form-group">
            <label>Card Number</label>
            <input type="text" name="card_number" placeholder="Any card number for demo payment" required />
          </div>
          <div class="form-group">
            <label>Name on Card</label>
            <input type="text" name="cardholder_name" placeholder="Name on card" required />
          </div>
          <p style="font-size:0.78rem; color:var(--text-on-bg-muted, #888);">Demo payment mode: any card number will be accepted. Full card details and CVV are not stored.</p>
        </div>

        <button type="submit" class="btn btn-full">Pay <?php echo money($total); ?></button>
      </form>
    <?php endif; ?>
  </div>

  <footer class="footer">
    <div class="grid-4">
      <div><h4>Opening Hours</h4><p>Mon-Sat: 10AM-12AM</p><p>Sunday: 2PM-10PM</p></div>
      <div><h4>Contact Us</h4><p>info@royalnawab.com</p><p>Phone: +44 1234 567890</p></div>
      <div><h4>Useful Links</h4><ul><li><a href="#">Terms</a></li><li><a href="#">Privacy</a></li><li><a href="menu.php">Menu</a></li></ul></div>
      <div><h4>Social Links</h4><div class="social-icons"><a href="#" class="social">f</a><a href="#" class="social">t</a><a href="#" class="social">in</a></div></div>
    </div>
    <div class="footer-bottom">&copy; 2025 Royal Nawab. All rights reserved.</div>
  </footer>

  <script>
    document.getElementById('hamburger').addEventListener('click', function() {
      document.getElementById('navLinks').classList.toggle('open');
    });
  </script>
</body>
</html>