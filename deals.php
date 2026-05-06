<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Deals – Royal Nawab</title>
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
      <li><a href="deals.php" class="active-page">Deals</a></li>
      <li><a href="menu.php" class="btn-menu">Menu</a></li>
    </ul>
  </nav>

  <section class="page-hero">
    <div><h1>Deals</h1><p>Enjoy our exclusive deals at Royal Nawab. Available daily.</p></div>
  </section>

<?php
require 'db_connect.php';
require_once 'deal_image.php';

// Show single deal detail
$deal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($deal_id > 0):
  $deal = $pdo->query("SELECT * FROM deals WHERE id=$deal_id AND is_active=1")->fetch();
  if (!$deal) { header('Location: deals.php'); exit; }
  $dItems = $pdo->query("SELECT item_text FROM deal_items WHERE deal_id=$deal_id ORDER BY id");
?>

  <section class="section" style="max-width:700px; margin:0 auto;">
    <a href="deals.php" class="btn btn-light btn-sm" style="margin-bottom:18px; display:inline-block;">&#8592; Back to Deals</a>
    <div class="card" style="text-align:left; padding:30px;">
      <div style="text-align:center; margin-bottom:20px;">
        <?php $dimg = rn_deal_image_src($deal['name']); ?>
        <?php if ($dimg): ?>
        <div class="deal-photo deal-photo-hero"><img src="<?php echo htmlspecialchars($dimg); ?>" alt="" loading="lazy" width="600" height="400" /></div>
        <?php endif; ?>
        <h2><?php echo htmlspecialchars($deal['name']); ?></h2>
        <p class="price">&pound;<?php echo number_format($deal['price'], 2); ?></p>
        <p style="color:#666; font-size:0.88rem;"><?php echo htmlspecialchars($deal['description']); ?></p>
      </div>
      <hr style="margin:18px 0;" />
      <h4 style="font-size:0.82rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:12px;">What's Included:</h4>
      <ul style="padding-left:18px; line-height:2; font-size:0.85rem; color:#555;">
        <?php while ($di = $dItems->fetch()): ?>
          <li><?php echo htmlspecialchars($di['item_text']); ?></li>
        <?php endwhile; ?>
      </ul>
      <div style="margin-top:24px; display:flex; gap:12px; flex-wrap:wrap;">
        <a href="order-collection.php" class="btn">Order Collection</a>
        <a href="order-delivery.php" class="btn btn-light">Order Delivery</a>
      </div>
    </div>
  </section>

<?php else: // show all deals ?>

  <section class="section" style="max-width:850px; margin:0 auto;">
    <h2 class="center">Our Current Deals</h2>
    <hr />
    <div class="grid-3">
    <?php
    $deals = $pdo->query("SELECT * FROM deals WHERE is_active=1 ORDER BY sort_order, id");
    while ($d = $deals->fetch()):
    ?>
      <div class="card">
        <?php $dsrc = rn_deal_image_src($d['name']); ?>
        <?php if ($dsrc): ?>
        <div class="deal-photo"><img src="<?php echo htmlspecialchars($dsrc); ?>" alt="" loading="lazy" width="400" height="280" /></div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($d['name']); ?></h3>
        <p class="price">&pound;<?php echo number_format($d['price'], 2); ?></p>
        <a href="deals.php?id=<?php echo $d['id']; ?>" class="btn">View Deal</a>
      </div>
    <?php endwhile; ?>
    </div>

    <div class="grid-2" style="margin-top:25px;">
      <div class="info-box"><h4>Opening Hours</h4><p>Mon–Sat: 10:00 AM – 12:00 AM<br/>Sunday: 2:00 PM – 10:00 PM</p></div>
      <div class="info-box"><h4>Booking Information</h4><ul><li>Table reservations recommended</li><li>Large groups book in advance</li></ul></div>
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
