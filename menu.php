<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Menu – Royal Nawab</title>
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
      <li><a href="delivery.html">Delivery &amp; Collection</a></li>
      <li><a href="deals.php">Deals</a></li>
      <li><a href="menu.php" class="btn-menu active-page">Menu</a></li>
    </ul>
  </nav>

  <section class="page-hero">
    <div><h1>Our Menu</h1><p>Explore our authentic Indian &amp; Pakistani cuisine.</p></div>
  </section>

<?php
require 'db_connect.php';
require_once 'category_image.php';

// If a category is selected, show its items
$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

if ($cat_id > 0):
  $cRow = $pdo->query("SELECT * FROM menu_categories WHERE id=$cat_id")->fetch();
  if (!$cRow) { header('Location: menu.php'); exit; }
?>

  <section class="section" style="max-width:820px; margin:0 auto;">
    <a href="menu.php" class="btn btn-light btn-sm" style="margin-bottom:18px; display:inline-block;">&#8592; Back to Menu</a>
    <h2 class="menu-category-title">
      <?php
      $catImg = rn_category_image_src($cRow['name']);
      if ($catImg):
      ?><img src="<?php echo htmlspecialchars($catImg); ?>" alt="" class="menu-category-title-img" width="56" height="56" loading="lazy" /><?php endif; ?>
      <span><?php echo htmlspecialchars($cRow['name']); ?></span>
    </h2>
    <hr />
    <?php
    $items = $pdo->query("SELECT * FROM menu_items WHERE category_id=$cat_id AND is_available=1 ORDER BY sort_order, name")->fetchAll();
    $review_stats = [];
    $recent_reviews = [];
    if (count($items) > 0) {
      $item_ids = array_map(function($it){ return (int)$it['id']; }, $items);
      $item_ids = array_values(array_filter($item_ids, function($id){ return $id > 0; }));
      if (count($item_ids) > 0) {
        $id_list = implode(',', $item_ids);
        try {
          $statRows = $pdo->query("SELECT menu_item_id, COUNT(*) AS review_count, ROUND(AVG(rating), 1) AS avg_rating FROM order_feedback WHERE menu_item_id IN ($id_list) GROUP BY menu_item_id")->fetchAll(PDO::FETCH_ASSOC);
          foreach ($statRows as $sr) {
            $iid = (int)($sr['menu_item_id'] ?? 0);
            if ($iid > 0) {
              $review_stats[$iid] = [
                'count' => (int)$sr['review_count'],
                'avg' => (float)$sr['avg_rating'],
              ];
            }
          }

          $revRows = $pdo->query("SELECT menu_item_id, customer_name, rating, feedback_text, created_at FROM order_feedback WHERE menu_item_id IN ($id_list) AND feedback_text IS NOT NULL AND TRIM(feedback_text) <> '' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
          foreach ($revRows as $rr) {
            $iid = (int)($rr['menu_item_id'] ?? 0);
            if ($iid <= 0) continue;
            if (!isset($recent_reviews[$iid])) $recent_reviews[$iid] = [];
            if (count($recent_reviews[$iid]) >= 2) continue;
            $recent_reviews[$iid][] = $rr;
          }
        } catch (Throwable $e) {
          $review_stats = [];
          $recent_reviews = [];
        }
      }
    }
    if (count($items) > 0):
      foreach ($items as $item):
      $iid = (int)$item['id'];
      $istat = $review_stats[$iid] ?? null;
    ?>
    <div class="menu-item-row">
      <div>
        <div class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
        <div class="menu-item-desc"><?php echo htmlspecialchars($item['description']); ?></div>
        <?php if ($istat): ?>
        <div class="menu-item-review-meta">
          <?php
            $avg = (float)$istat['avg'];
            $filled = max(1, min(5, (int)round($avg)));
            echo str_repeat('&#9733;', $filled) . str_repeat('&#9734;', 5 - $filled);
          ?>
          &nbsp;<?php echo number_format($avg, 1); ?>/5
          <span class="menu-item-review-count">(<?php echo (int)$istat['count']; ?> review<?php echo ((int)$istat['count'] !== 1) ? 's' : ''; ?>)</span>
        </div>
        <?php endif; ?>
        <?php if (!empty($recent_reviews[$iid])): ?>
          <?php foreach ($recent_reviews[$iid] as $rev): ?>
          <div class="menu-item-review-snippet">
            "<?php echo htmlspecialchars((string)$rev['feedback_text']); ?>"
            <span class="menu-item-review-by">- <?php echo htmlspecialchars(trim((string)($rev['customer_name'] ?? 'Customer')) ?: 'Customer'); ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div class="menu-item-price">&pound;<?php echo number_format($item['price'], 2); ?></div>
    </div>
    <?php endforeach; else: ?>
    <p style="color:#888; padding:20px 0;">No items available in this category yet.</p>
    <?php endif; ?>

    <div style="margin-top:28px; display:flex; gap:12px; flex-wrap:wrap;">
      <a href="order-collection.php" class="btn">Order Collection</a>
      <a href="order-delivery.php" class="btn btn-light">Order Delivery</a>
    </div>
  </section>

<?php else: // show categories ?>

  <section class="section" style="max-width:850px; margin:0 auto;">
    <h2 class="center">Our Menu</h2>
    <hr />
    <div class="grid-3">
    <?php
    $cats = $pdo->query("SELECT * FROM menu_categories ORDER BY sort_order, name");
    while ($cat = $cats->fetch()):
    ?>
      <div class="card">
        <?php $src = rn_category_image_src($cat['name']); ?>
        <?php if ($src): ?>
        <div class="category-photo"><img src="<?php echo htmlspecialchars($src); ?>" alt="" loading="lazy" width="400" height="300" /></div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($cat['name']); ?></h3>
        <br/>
        <a href="menu.php?cat=<?php echo $cat['id']; ?>" class="btn">View Items</a>
      </div>
    <?php endwhile; ?>
    </div>

    <div class="info-box" style="margin-top:28px;">
      <h4>Collection Hours</h4>
      <p>Mon–Sat: 10:00 AM – 12:00 AM &nbsp;|&nbsp; Sunday: 2:00 PM – 10:00 PM</p>
    </div>
  </section>

<?php endif; ?>

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
  </script>

</body>
</html>