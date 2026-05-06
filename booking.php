<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Booking – Royal Nawab</title>
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
      <li><a href="booking.php" class="active-page">Booking</a></li>
      <li><a href="delivery.html">Delivery &amp; Collection</a></li>
      <li><a href="deals.php">Deals</a></li>
      <li><a href="menu.php" class="btn-menu">Menu</a></li>
    </ul>
  </nav>

  <section class="page-hero">
    <div><h1>Reservations</h1><p>Book your dining experience with Royal Nawab.</p></div>
  </section>

  <?php
  if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success')
      echo '<div class="alert alert-success">&#10003; Table reserved! We will confirm your booking soon.</div>';
    else
      echo '<div class="alert alert-error">&#10007; ' . htmlspecialchars($_GET['msg'] ?? 'Something went wrong.') . '</div>';
  }
  ?>

  <section class="section" style="max-width:720px; margin:0 auto;">
    <h2 class="center">Reserve a Table</h2>
    <hr />
    <form action="submit_booking.php" method="POST">
      <div class="grid-2">
        <div class="form-group"><label>First Name *</label><input type="text" name="first_name" placeholder="First Name" required /></div>
        <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" placeholder="Last Name" required /></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" placeholder="Email Address" required /></div>
        <div class="form-group"><label>Phone *</label><input type="tel" name="phone" placeholder="Phone Number" required /></div>
        <div class="form-group">
          <label>Number of Guests</label>
          <select name="num_guests">
            <option>1</option><option>2</option><option>3</option><option>4</option>
            <option selected>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10+</option>
          </select>
        </div>
        <div class="form-group"><label>Date *</label><input type="date" name="booking_date" id="bookingDate" required /></div>
      </div>
      <div class="form-group">
        <label>Preferred Time</label>
        <select name="booking_time">
          <option>10:00 AM</option><option>11:00 AM</option><option>12:00 PM</option>
          <option>1:00 PM</option><option>2:00 PM</option><option>3:00 PM</option>
          <option>5:00 PM</option><option>6:00 PM</option><option>7:00 PM</option>
          <option>8:00 PM</option><option>9:00 PM</option><option>10:00 PM</option><option>11:00 PM</option>
        </select>
      </div>
      <div class="form-group"><label>Special Requests</label><textarea name="special_requests" placeholder="Dietary requirements, allergies, occasion..."></textarea></div>
      <button type="submit" class="btn btn-full">Reserve Now</button>
    </form>
  </section>

  <section class="section bg-light" style="max-width:720px; margin:0 auto; padding-top:20px;">
    <div class="grid-2">
      <div class="info-box"><h4>Opening Hours</h4><p>Mon–Sat: 10:00 AM – 12:00 AM<br/>Sunday: 2:00 PM – 10:00 PM</p></div>
      <div class="info-box"><h4>Booking Information</h4><ul><li>Table reservations recommended</li><li>Large groups should book in advance</li><li>Please arrive on time for your reservation</li></ul></div>
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
    // Set min date to today
    document.getElementById('bookingDate').min = new Date().toISOString().split('T')[0];
  </script>

</body>
</html>
