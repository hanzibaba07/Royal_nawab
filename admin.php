<?php
session_start();
require 'db_connect.php';
require_once 'category_image.php';
require_once 'deal_image.php';

// LOGOUT
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

// LOGIN
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='login') {
    $u = trim($_POST['username']??'');
    $p = $_POST['password']??'';
    $st = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
    $st->execute([$u]);
    $row = $st->fetch();
    if ($row && password_verify($p,$row['password_hash'])) {
        $_SESSION['rn_admin']=$row['id'];
        header('Location: admin.php?tab=dashboard'); exit;
    }
    $login_err='Wrong username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel – Royal Nawab</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <style>
    .admin-header { background:#2c2c2c;color:#fff;padding:18px 30px;display:flex;justify-content:space-between;align-items:center; }
    .admin-header h1 { font-size:1.3rem;color:#fff; }
    .admin-header a { color:#bbb;font-size:0.82rem; }
    .admin-header a:hover { color:#fff; }
    .tab-bar { background:#2c2c2c;display:flex;overflow-x:auto;border-top:1px solid #444; }
    .tab-bar a { display:block;padding:15px 22px;color:#aaa;font-size:0.78rem;text-transform:uppercase;letter-spacing:1px;white-space:nowrap;border-bottom:3px solid transparent;transition:all 0.2s;text-decoration:none; }
    .tab-bar a:hover { color:#fff; }
    .tab-bar a.active { color:#fff;border-bottom-color:#8b1a1a; }
    .admin-wrapper { max-width:1150px;margin:0 auto;padding:26px 20px; }
    .stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px; }
    .stat-card { background:#fff;border:1px solid #ddd;padding:20px;text-align:center; }
    .stat-card .number { font-size:2rem;font-weight:700;color:#2c2c2c;font-family:'Rubik',sans-serif; }
    .stat-card p { font-size:0.75rem;color:#888;text-transform:uppercase;letter-spacing:1px;margin-top:5px; }
    .admin-table-wrap { background:#fff;border:1px solid #ddd;overflow-x:auto; }
    .admin-table { width:100%;border-collapse:collapse;font-size:0.82rem; }
    .admin-table th { background:#2c2c2c;color:#fff;padding:11px 13px;text-align:left;font-weight:700;letter-spacing:0.5px;white-space:nowrap; }
    .admin-table td { padding:10px 13px;border-bottom:1px solid #eee;color:#444;vertical-align:middle; }
    .admin-table tr:hover td { background:#fafafa; }
    .no-data { text-align:center;padding:40px;color:#aaa;font-size:0.9rem; }
    .badge { display:inline-block;padding:3px 9px;font-size:0.72rem;font-weight:700;letter-spacing:0.5px;text-transform:uppercase; }
    .badge-pending   { background:#fff3cd;color:#856404; }
    .badge-confirmed { background:#cce5ff;color:#004085; }
    .badge-preparing { background:#d1ecf1;color:#0c5460; }
    .badge-ready-for-collection,.badge-ready { background:#d4edda;color:#155724; }
    .badge-out-for-delivery { background:#d6d8f7;color:#383d8b; }
    .badge-delivered,.badge-collected,.badge-done,.badge-completed { background:#d4edda;color:#155724; }
    .badge-cancelled { background:#f8d7da;color:#721c24; }
    .badge-yes { background:#d4edda;color:#155724; }
    .badge-no  { background:#f8d7da;color:#721c24; }
    .status-sel { padding:5px 8px;border:1px solid #ccc;font-size:0.8rem;font-family:'Rubik',sans-serif;cursor:pointer; }
    .form-box { background:#fff;border:1px solid #ddd;padding:22px;margin-bottom:20px; }
    .form-box h3 { font-size:0.95rem;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid #eee; }
    .form-row { display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end; }
    .form-row .form-group { flex:1;min-width:120px;margin-bottom:0; }
    .form-row .form-group label { font-size:0.78rem;font-weight:700;display:block;margin-bottom:5px; }
    .form-row .form-group input,
    .form-row .form-group select,
    .form-row .form-group textarea { width:100%;padding:8px 11px;border:1px solid #ccc;font-family:'Rubik',sans-serif;font-size:0.83rem;outline:none; }
    .form-row .form-group input:focus,
    .form-row .form-group select:focus { border-color:#8b1a1a; }
    .form-row .form-group textarea { height:90px;resize:vertical; }
    .table-actions { display:flex;gap:6px;flex-wrap:wrap; }
    .table-actions form { display:inline; }
    .edit-row { display:none;background:#f9f9f9;border-top:1px solid #eee;padding:14px;margin-top:8px; }
    .edit-row.open { display:block; }
    .login-box { max-width:380px;margin:80px auto;background:#fff;border:1px solid #ddd;padding:36px; }
    .login-box h2 { font-size:1.3rem;margin-bottom:22px; }
    .login-box .fg { display:flex;flex-direction:column;gap:5px;margin-bottom:14px; }
    .login-box label { font-size:0.83rem;font-weight:700; }
    .login-box input { padding:9px 13px;border:1px solid #ccc;font-size:0.85rem;font-family:'Rubik',sans-serif;outline:none;width:100%; }
    .login-box input:focus { border-color:#8b1a1a; }
    @media(max-width:900px){ .stats-row{grid-template-columns:1fr 1fr;} }
    @media(max-width:600px){ .stats-row{grid-template-columns:1fr 1fr;}.form-row{flex-direction:column;} }

    /* Gala background + glass chrome (matches main site; overrides rules above) */
    body.admin-page {
      background-color: #050510;
      background-image:
        linear-gradient(165deg, rgba(5, 4, 14, 0.76) 0%, rgba(14, 8, 32, 0.72) 42%, rgba(3, 2, 10, 0.84) 100%),
        url('asset/bgimg.jpeg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      background-repeat: no-repeat;
      min-height: 100vh;
      color: #f8f6f0;
    }
    body.admin-page .admin-header {
      background: rgba(8, 6, 18, 0.88);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(212, 175, 55, 0.35);
    }
    body.admin-page .tab-bar {
      background: rgba(8, 6, 18, 0.82);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top: 1px solid rgba(212, 175, 55, 0.28);
    }
    body.admin-page .tab-bar a.active { border-bottom-color: #d4af37; }
    body.admin-page .login-box {
      background: rgba(255, 252, 248, 0.94);
      border: 1px solid rgba(212, 175, 55, 0.35);
      box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
    }
    body.admin-page .login-box h2 { color: #1a1428; }
    body.admin-page .stat-card,
    body.admin-page .admin-table-wrap,
    body.admin-page .form-box {
      background: rgba(255, 252, 248, 0.94);
      border-color: rgba(212, 175, 55, 0.35);
    }
    body.admin-page .edit-row { background: rgba(248, 244, 238, 0.92); }
    body.admin-page .admin-wrapper .alert-success { color: #155724; }

    /* Readable copy on cream/glass panels (body.admin-page defaults to pale text elsewhere) */
    body.admin-page .admin-wrapper {
      color: #2a2520;
    }
    body.admin-page .admin-wrapper .form-box h3,
    body.admin-page .admin-wrapper h3.admin-menu-cat-head {
      color: #1a1428;
      border-bottom-color: rgba(26, 20, 40, 0.12);
    }
    body.admin-page .admin-wrapper label,
    body.admin-page .admin-wrapper .form-group label {
      color: #352b28;
      font-weight: 700;
    }
    body.admin-page .admin-wrapper .form-box input,
    body.admin-page .admin-wrapper .form-box select,
    body.admin-page .admin-wrapper .form-box textarea,
    body.admin-page .admin-wrapper .edit-row input,
    body.admin-page .admin-wrapper .edit-row select,
    body.admin-page .admin-wrapper .edit-row textarea,
    body.admin-page .admin-wrapper .status-sel {
      background: #fffcf8;
      border: 1px solid rgba(26, 20, 40, 0.2);
      color: #2a2520;
    }
    body.admin-page .admin-wrapper .form-box input::placeholder,
    body.admin-page .admin-wrapper .form-box textarea::placeholder,
    body.admin-page .admin-wrapper .edit-row input::placeholder,
    body.admin-page .admin-wrapper .edit-row textarea::placeholder {
      color: #6e675e;
      opacity: 1;
    }
    body.admin-page .admin-wrapper .form-box p,
    body.admin-page .admin-wrapper .form-box small,
    body.admin-page .admin-wrapper .admin-hint {
      color: #5c554c;
    }
    body.admin-page .admin-wrapper .form-box code {
      background: rgba(212, 175, 55, 0.15);
      color: #3d331f;
      padding: 2px 6px;
      border-radius: 3px;
      font-size: 0.88em;
    }
    body.admin-page .admin-wrapper .stat-card .number {
      color: #1a1428;
    }
    body.admin-page .admin-wrapper .stat-card p {
      color: #5c554c;
    }
    body.admin-page .admin-wrapper .admin-table td {
      color: #3a3530;
    }
    body.admin-page .admin-wrapper .admin-table td strong {
      color: #1a1428;
    }
    body.admin-page .admin-wrapper .no-data {
      color: #6b6570;
    }
    body.admin-page .admin-wrapper .admin-muted-cell {
      font-size: 0.8rem;
      color: #5c554c;
    }
  </style>
</head>
<body class="admin-page">
<?php
// SHOW LOGIN
if (empty($_SESSION['rn_admin'])): ?>
<div class="login-box">
  <h2>Admin Login</h2>
  <?php if(!empty($login_err)) echo '<div class="alert alert-error" style="margin-bottom:14px;">'.htmlspecialchars($login_err).'</div>'; ?>
  <form method="POST">
    <input type="hidden" name="action" value="login"/>
    <div class="fg"><label>Username</label><input name="username" type="text" placeholder="admin" autofocus required/></div>
    <div class="fg"><label>Password</label><input name="password" type="password" placeholder="••••••••" required/></div>
    <button type="submit" class="btn btn-full" style="margin-top:6px;">Login</button>
  </form>
  <p style="margin-top:14px;font-size:0.78rem;color:#aaa;">Default: admin / admin123</p>
</div>
<?php exit; endif;

// ── HELPERS ───────────────────────────────────────────
function s($v){ global $pdo; return $pdo->quote(trim((string)$v)); }

// ── POST ACTIONS ──────────────────────────────────────
$fb='';
$act=$_POST['action']??'';

// Bookings
if($act==='upd_booking'){  $id=(int)$_POST['id'];$st=s($_POST['status']); $pdo->exec("UPDATE bookings SET status=$st WHERE id=$id"); $fb='Booking updated.'; }
if($act==='del_booking'){  $id=(int)$_POST['id']; $pdo->exec("DELETE FROM bookings WHERE id=$id"); $fb='Booking deleted.'; }
// Delivery
if($act==='upd_delivery'){ $id=(int)$_POST['id'];$st=s($_POST['status']); $pdo->exec("UPDATE delivery_orders SET status=$st WHERE id=$id"); $fb='Order updated.'; }
// Collection
if($act==='upd_collection'){ $id=(int)$_POST['id'];$st=s($_POST['status']); $pdo->exec("UPDATE collection_orders SET status=$st WHERE id=$id"); $fb='Order updated.'; }
// Menu add
if($act==='add_item'){
    $cat=(int)$_POST['category_id']; $nameRaw=trim($_POST['item_name']??''); $name=s($_POST['item_name']); $desc=s($_POST['item_desc']); $price=(float)$_POST['item_price'];
    if($nameRaw!==''&&$price>0){ $pdo->exec("INSERT INTO menu_items (category_id,name,description,price) VALUES($cat,$name,$desc,$price)"); $fb="Item added."; }
    else $fb='Enter name and price.';
}
// Menu edit
if($act==='edit_item'){
    $id=(int)$_POST['id']; $cat=(int)$_POST['category_id']; $name=s($_POST['item_name']); $desc=s($_POST['item_desc']); $price=(float)$_POST['item_price']; $av=isset($_POST['is_available'])?1:0;
    $pdo->exec("UPDATE menu_items SET category_id=$cat,name=$name,description=$desc,price=$price,is_available=$av WHERE id=$id"); $fb='Item updated.';
}
// Menu delete / toggle
if($act==='del_item'){   $id=(int)$_POST['id']; $pdo->exec("DELETE FROM menu_items WHERE id=$id"); $fb='Item deleted.'; }
if($act==='toggle_item'){ $id=(int)$_POST['id']; $pdo->exec("UPDATE menu_items SET is_available=NOT is_available WHERE id=$id"); $fb='Availability toggled.'; }
// Deal add
if($act==='add_deal'){
    $nameRaw=trim($_POST['deal_name']??''); $name=s($_POST['deal_name']); $desc=s($_POST['deal_desc']); $price=(float)$_POST['deal_price'];
    if($nameRaw!==''&&$price>0){
        $pdo->exec("INSERT INTO deals (name,description,price) VALUES($name,$desc,$price)");
        $did=(int)$pdo->lastInsertId();
        foreach(array_filter(array_map('trim',explode("\n",$_POST['deal_items']??''))) as $it){
            $it=s($it); $pdo->exec("INSERT INTO deal_items (deal_id,item_text) VALUES($did,$it)");
        }
        $fb="Deal added.";
    } else $fb='Enter name and price.';
}
// Deal edit
if($act==='edit_deal'){
    $id=(int)$_POST['id']; $name=s($_POST['deal_name']); $desc=s($_POST['deal_desc']); $price=(float)$_POST['deal_price']; $act2=isset($_POST['is_active'])?1:0;
    $pdo->exec("UPDATE deals SET name=$name,description=$desc,price=$price,is_active=$act2 WHERE id=$id");
    $pdo->exec("DELETE FROM deal_items WHERE deal_id=$id");
    foreach(array_filter(array_map('trim',explode("\n",$_POST['deal_items']??''))) as $it){
        $it=s($it); $pdo->exec("INSERT INTO deal_items (deal_id,item_text) VALUES($id,$it)");
    }
    $fb='Deal updated.';
}
// Deal delete
if($act==='del_deal'){ $id=(int)$_POST['id']; $pdo->exec("DELETE FROM deals WHERE id=$id"); $fb='Deal deleted.'; }
// Order feedback / reviews
if($act==='del_feedback'){
    $id=(int)$_POST['id'];
    if($id>0){
        $st=$pdo->prepare('DELETE FROM order_feedback WHERE id=?');
        $st->execute([$id]);
        $fb='Review removed.';
    }
}

// ── STATS ─────────────────────────────────────────────
$nb  =(int)$pdo->query("SELECT COUNT(*) c FROM bookings")->fetch()['c'];
$nd  =(int)$pdo->query("SELECT COUNT(*) c FROM delivery_orders")->fetch()['c'];
$nc  =(int)$pdo->query("SELECT COUNT(*) c FROM collection_orders")->fetch()['c'];
$revd=(float)$pdo->query("SELECT COALESCE(SUM(total),0) r FROM delivery_orders WHERE status!='Cancelled'")->fetch()['r'];
$revc=(float)$pdo->query("SELECT COALESCE(SUM(total),0) r FROM collection_orders WHERE status!='Cancelled'")->fetch()['r'];
$rev =$revd+$revc;

$tab=$_GET['tab']??'dashboard';
?>

<div class="admin-header">
  <h1>Royal Nawab &mdash; Admin Panel</h1>
  <div style="display:flex;gap:20px;">
    <a href="index.html">&#8592; View Website</a>
    <a href="admin.php?logout=1">Logout</a>
  </div>
</div>

<div class="tab-bar">
  <a href="admin.php?tab=dashboard"   class="<?php echo $tab==='dashboard'  ?'active':'';?>">Dashboard</a>
  <a href="admin.php?tab=bookings"    class="<?php echo $tab==='bookings'   ?'active':'';?>">Bookings</a>
  <a href="admin.php?tab=delivery"    class="<?php echo $tab==='delivery'   ?'active':'';?>">Delivery Orders</a>
  <a href="admin.php?tab=collection"  class="<?php echo $tab==='collection' ?'active':'';?>">Collection Orders</a>
  <a href="admin.php?tab=menu"        class="<?php echo $tab==='menu'       ?'active':'';?>">Menu</a>
  <a href="admin.php?tab=deals"       class="<?php echo $tab==='deals'      ?'active':'';?>">Deals</a>
  <a href="admin.php?tab=subscribers" class="<?php echo $tab==='subscribers'?'active':'';?>">Subscribers</a>
  <a href="admin.php?tab=reviews"      class="<?php echo $tab==='reviews'     ?'active':'';?>">Reviews</a>
</div>

<div class="admin-wrapper">

<?php if($fb): ?><div class="alert alert-success" style="margin-bottom:16px;"><?php echo htmlspecialchars($fb);?></div><?php endif; ?>

<?php // ════ DASHBOARD ════
if($tab==='dashboard'): ?>
<div class="stats-row">
  <div class="stat-card"><div class="number"><?php echo $nb;?></div><p>Total Bookings</p></div>
  <div class="stat-card"><div class="number"><?php echo $nd;?></div><p>Delivery Orders</p></div>
  <div class="stat-card"><div class="number"><?php echo $nc;?></div><p>Collection Orders</p></div>
  <div class="stat-card"><div class="number">&pound;<?php echo number_format($rev,2);?></div><p>Total Revenue</p></div>
</div>

<h3 style="font-size:1rem;margin-bottom:12px;">Recent Bookings</h3>
<div class="admin-table-wrap" style="margin-bottom:24px;">
  <table class="admin-table">
    <thead><tr><th>#</th><th>Name</th><th>Date</th><th>Time</th><th>Guests</th><th>Status</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5")->fetchAll();
    if(count($r)): foreach($r as $row):
      $b=strtolower(str_replace(' ','-',$row['status'])); ?>
      <tr><td><?php echo $row['id'];?></td><td><?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']);?></td><td><?php echo $row['booking_date'];?></td><td><?php echo htmlspecialchars($row['booking_time']);?></td><td><?php echo $row['num_guests'];?></td><td><span class="badge badge-<?php echo $b;?>"><?php echo $row['status'];?></span></td></tr>
    <?php endforeach; else: echo '<tr><td colspan="6" class="no-data">No bookings yet.</td></tr>'; endif; ?>
    </tbody>
  </table>
</div>

<h3 style="font-size:1rem;margin-bottom:12px;">Recent Delivery Orders</h3>
<div class="admin-table-wrap" style="margin-bottom:24px;">
  <table class="admin-table">
    <thead><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM delivery_orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
    if(count($r)): foreach($r as $row):
      $b=strtolower(str_replace(' ','-',$row['status'])); ?>
      <tr><td><?php echo $row['order_ref'];?></td><td><?php echo htmlspecialchars($row['full_name']);?></td><td><?php echo htmlspecialchars($row['phone']);?></td><td>&pound;<?php echo number_format($row['total'],2);?></td><td><span class="badge badge-<?php echo $b;?>"><?php echo $row['status'];?></span></td><td><?php echo date('d M, H:i',strtotime($row['created_at']));?></td></tr>
    <?php endforeach; else: echo '<tr><td colspan="6" class="no-data">No delivery orders yet.</td></tr>'; endif; ?>
    </tbody>
  </table>
</div>

<h3 style="font-size:1rem;margin-bottom:12px;">Recent Collection Orders</h3>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM collection_orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
    if(count($r)): foreach($r as $row):
      $b=strtolower(str_replace(' ','-',$row['status'])); ?>
      <tr><td><?php echo $row['order_ref'];?></td><td><?php echo htmlspecialchars($row['full_name']);?></td><td><?php echo htmlspecialchars($row['phone']);?></td><td>&pound;<?php echo number_format($row['total'],2);?></td><td><span class="badge badge-<?php echo $b;?>"><?php echo $row['status'];?></span></td><td><?php echo date('d M, H:i',strtotime($row['created_at']));?></td></tr>
    <?php endforeach; else: echo '<tr><td colspan="6" class="no-data">No collection orders yet.</td></tr>'; endif; ?>
    </tbody>
  </table>
</div>

<?php // ════ BOOKINGS ════
elseif($tab==='bookings'): ?>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Guests</th><th>Date</th><th>Time</th><th>Special Req.</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM bookings ORDER BY booking_date DESC,booking_time DESC")->fetchAll();
    if(count($r)): foreach($r as $row):
      $b=strtolower(str_replace(' ','-',$row['status'])); ?>
      <tr>
        <td><?php echo $row['id'];?></td>
        <td><?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']);?></td>
        <td><?php echo htmlspecialchars($row['email']);?></td>
        <td><?php echo htmlspecialchars($row['phone']);?></td>
        <td><?php echo $row['num_guests'];?></td>
        <td><?php echo $row['booking_date'];?></td>
        <td><?php echo htmlspecialchars($row['booking_time']);?></td>
        <td style="font-size:0.8rem;"><?php echo htmlspecialchars($row['special_requests']?:'–');?></td>
        <td><span class="badge badge-<?php echo $b;?>"><?php echo $row['status'];?></span></td>
        <td>
          <div class="table-actions">
            <form method="POST"><input type="hidden" name="action" value="upd_booking"/><input type="hidden" name="id" value="<?php echo $row['id'];?>"/>
              <select name="status" class="status-sel" onchange="this.form.submit()">
                <?php foreach(['Pending','Confirmed','Cancelled','Completed'] as $s): ?><option <?php echo $row['status']===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach;?>
              </select>
            </form>
            <form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="del_booking"/><input type="hidden" name="id" value="<?php echo $row['id'];?>"/><button class="btn btn-sm btn-red" type="submit">Delete</button></form>
          </div>
        </td>
      </tr>
    <?php endforeach; else: echo '<tr><td colspan="10" class="no-data">No bookings yet.</td></tr>'; endif; ?>
    </tbody>
  </table>
</div>

<?php // ════ DELIVERY ORDERS ════
elseif($tab==='delivery'): ?>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Address</th><th>Items</th><th>Special Req.</th><th>Time</th><th>Sub</th><th>Fee</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM delivery_orders ORDER BY created_at DESC")->fetchAll();
    if(count($r)): foreach($r as $row):
      $b=strtolower(str_replace(' ','-',$row['status']));
      $iq=$pdo->query("SELECT item_name,quantity FROM delivery_order_items WHERE order_id=".$row['id'])->fetchAll();
      $il=[];foreach($iq as $it) $il[]=$it['item_name'].' x'.$it['quantity'];
    ?>
      <tr>
        <td><?php echo $row['order_ref'];?></td>
        <td><?php echo htmlspecialchars($row['full_name']);?></td>
        <td><?php echo htmlspecialchars($row['phone']);?></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars($row['delivery_address']);?></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars(implode(', ',$il)?:'–');?></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars($row['special_requests']?:'–');?></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars($row['delivery_time']);?></td>
        <td>&pound;<?php echo number_format($row['subtotal'],2);?></td>
        <td>&pound;<?php echo number_format($row['delivery_fee'],2);?></td>
        <td><strong>&pound;<?php echo number_format($row['total'],2);?></strong></td>
        <td><form method="POST"><input type="hidden" name="action" value="upd_delivery"/><input type="hidden" name="id" value="<?php echo $row['id'];?>"/>
          <select name="status" class="status-sel" onchange="this.form.submit()">
            <?php foreach(['Pending','Confirmed','Preparing','Out for Delivery','Delivered','Cancelled'] as $s):?><option <?php echo $row['status']===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach;?>
          </select></form>
        </td>
        <td style="font-size:0.79rem;"><?php echo date('d M, H:i',strtotime($row['created_at']));?></td>
      </tr>
    <?php endforeach; else: echo '<tr><td colspan="12" class="no-data">No delivery orders yet.</td></tr>'; endif; ?>
    </tbody>
  </table>
</div>

<?php // ════ COLLECTION ORDERS ════
elseif($tab==='collection'): ?>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Ref</th><th>Name</th><th>Phone</th><th>Items</th><th>Special Req.</th><th>Collect Time</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM collection_orders ORDER BY created_at DESC")->fetchAll();
    if(count($r)): foreach($r as $row):
      $b=strtolower(str_replace(' ','-',$row['status']));
      $iq=$pdo->query("SELECT item_name,quantity FROM collection_order_items WHERE order_id=".$row['id'])->fetchAll();
      $il=[];foreach($iq as $it) $il[]=$it['item_name'].' x'.$it['quantity'];
    ?>
      <tr>
        <td><?php echo $row['order_ref'];?></td>
        <td><?php echo htmlspecialchars($row['full_name']);?></td>
        <td><?php echo htmlspecialchars($row['phone']);?></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars(implode(', ',$il)?:'–');?></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars($row['special_requests']?:'–');?></td>
        <td><?php echo htmlspecialchars($row['collection_time']);?></td>
        <td><strong>&pound;<?php echo number_format($row['total'],2);?></strong></td>
        <td><form method="POST"><input type="hidden" name="action" value="upd_collection"/><input type="hidden" name="id" value="<?php echo $row['id'];?>"/>
          <select name="status" class="status-sel" onchange="this.form.submit()">
            <?php foreach(['Pending','Confirmed','Preparing','Ready for Collection','Collected','Cancelled'] as $s):?><option <?php echo $row['status']===$s?'selected':'';?>><?php echo $s;?></option><?php endforeach;?>
          </select></form>
        </td>
        <td style="font-size:0.79rem;"><?php echo date('d M, H:i',strtotime($row['created_at']));?></td>
      </tr>
    <?php endforeach; else: echo '<tr><td colspan="9" class="no-data">No collection orders yet.</td></tr>'; endif; ?>
    </tbody>
  </table>
</div>

<?php // ════ MENU ════
elseif($tab==='menu'):
  $cats=$pdo->query("SELECT * FROM menu_categories ORDER BY sort_order")->fetchAll();
  $cat_list=$cats;
?>
<div class="form-box">
  <h3>Add New Menu Item</h3>
  <form method="POST"><input type="hidden" name="action" value="add_item"/>
    <div class="form-row">
      <div class="form-group"><label>Category</label><select name="category_id"><?php foreach($cat_list as $c):?><option value="<?php echo $c['id'];?>"><?php echo htmlspecialchars($c['name']);?></option><?php endforeach;?></select></div>
      <div class="form-group"><label>Item Name *</label><input type="text" name="item_name" placeholder="e.g. Chicken Tikka" required/></div>
      <div class="form-group" style="max-width:120px;"><label>Price (£) *</label><input type="number" step="0.01" min="0.01" name="item_price" placeholder="9.95" required/></div>
      <div class="form-group"><label>Description</label><input type="text" name="item_desc" placeholder="Short description"/></div>
      <div class="form-group"><label>&nbsp;</label><button type="submit" class="btn">Add Item</button></div>
    </div>
  </form>
</div>

<?php foreach($cat_list as $cat):
  $items=$pdo->query("SELECT * FROM menu_items WHERE category_id={$cat['id']} ORDER BY sort_order,name")->fetchAll();
  if(count($items)===0) continue;
?>
<h3 class="admin-menu-cat-head" style="font-size:0.9rem;margin:18px 0 8px;">
<?php
  $__imgsrc = rn_category_image_src($cat['name']);
  if ($__imgsrc): ?><img src="<?php echo htmlspecialchars($__imgsrc); ?>" alt="" width="28" height="28" style="object-fit:cover;border-radius:4px;vertical-align:middle;margin-right:8px;" /><?php endif;
  echo htmlspecialchars($cat['name']);
?></h3>
<div class="admin-table-wrap" style="margin-bottom:16px;">
  <table class="admin-table">
    <thead><tr><th>#</th><th>Name</th><th>Description</th><th>Price</th><th>Available</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($items as $item): ?>
      <tr>
        <td><?php echo $item['id'];?></td>
        <td><strong><?php echo htmlspecialchars($item['name']);?></strong></td>
        <td class="admin-muted-cell"><?php echo htmlspecialchars($item['description']);?></td>
        <td>&pound;<?php echo number_format($item['price'],2);?></td>
        <td><span class="badge <?php echo $item['is_available']?'badge-yes':'badge-no';?>"><?php echo $item['is_available']?'Yes':'No';?></span></td>
        <td>
          <div class="table-actions">
            <button class="btn btn-sm btn-light" onclick="toggleEdit('i<?php echo $item['id'];?>')">Edit</button>
            <form method="POST"><input type="hidden" name="action" value="toggle_item"/><input type="hidden" name="id" value="<?php echo $item['id'];?>"/><button class="btn btn-sm btn-light" type="submit"><?php echo $item['is_available']?'Disable':'Enable';?></button></form>
            <form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="del_item"/><input type="hidden" name="id" value="<?php echo $item['id'];?>"/><button class="btn btn-sm btn-red" type="submit">Delete</button></form>
          </div>
          <div class="edit-row" id="i<?php echo $item['id'];?>">
            <form method="POST"><input type="hidden" name="action" value="edit_item"/><input type="hidden" name="id" value="<?php echo $item['id'];?>"/>
              <div class="form-row">
                <div class="form-group"><label>Category</label><select name="category_id"><?php foreach($cat_list as $cc):?><option value="<?php echo $cc['id'];?>" <?php echo $cc['id']==$item['category_id']?'selected':'';?>><?php echo htmlspecialchars($cc['name']);?></option><?php endforeach;?></select></div>
                <div class="form-group"><label>Name</label><input type="text" name="item_name" value="<?php echo htmlspecialchars($item['name'],ENT_QUOTES);?>" required/></div>
                <div class="form-group" style="max-width:120px;"><label>Price (£)</label><input type="number" step="0.01" name="item_price" value="<?php echo $item['price'];?>"/></div>
                <div class="form-group"><label>Description</label><input type="text" name="item_desc" value="<?php echo htmlspecialchars($item['description'],ENT_QUOTES);?>"/></div>
                <div class="form-group"><label>Available</label><br/><input type="checkbox" name="is_available" <?php echo $item['is_available']?'checked':'';?> style="width:auto;margin-top:8px;"/></div>
                <div class="form-group"><label>&nbsp;</label><button type="submit" class="btn">Save</button></div>
              </div>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach;?>
    </tbody>
  </table>
</div>
<?php endforeach;?>

<?php // ════ DEALS ════
elseif($tab==='deals'): ?>
<div class="form-box">
  <h3>Add New Deal</h3>
  <form method="POST"><input type="hidden" name="action" value="add_deal"/>
    <div class="form-row">
      <div class="form-group"><label>Deal Name *</label><input type="text" name="deal_name" placeholder="e.g. Family Feast" required/></div>
      <div class="form-group" style="max-width:120px;"><label>Price (£) *</label><input type="number" step="0.01" min="0.01" name="deal_price" placeholder="29.95" required/></div>
      <div class="form-group"><label>Description</label><input type="text" name="deal_desc" placeholder="Short description"/></div>
    </div>
    <p class="admin-hint" style="font-size:0.78rem;margin:-6px 0 10px;">Deal image: place a JPEG in <code>asset/</code> matching the deal name (see <code>deal_image.php</code>).</p>
    <div class="form-row" style="margin-top:10px;">
      <div class="form-group" style="flex:1;"><label>Items Included <small>(one per line)</small></label><textarea name="deal_items" placeholder="2x Chicken Karahi&#10;Pilau Rice x4&#10;Naan x4"></textarea></div>
      <div class="form-group"><label>&nbsp;</label><button type="submit" class="btn">Add Deal</button></div>
    </div>
  </form>
</div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>#</th><th>Image</th><th>Name</th><th>Description</th><th>Price</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $deals=$pdo->query("SELECT * FROM deals ORDER BY sort_order,id")->fetchAll();
    if(count($deals)): foreach($deals as $d):
      $diq=$pdo->query("SELECT item_text FROM deal_items WHERE deal_id={$d['id']} ORDER BY id")->fetchAll();
      $di=[];foreach($diq as $dit) $di[]=$dit['item_text'];
    ?>
      <tr>
        <td><?php echo $d['id'];?></td>
        <td><?php
          $__ds = rn_deal_image_src($d['name']);
          if ($__ds): ?><img src="<?php echo htmlspecialchars($__ds); ?>" alt="" width="44" height="44" style="object-fit:cover;border-radius:4px;border:1px solid #ddd;" /><?php else: ?>—<?php endif; ?></td>
        <td><strong><?php echo htmlspecialchars($d['name']);?></strong></td>
        <td class="admin-muted-cell"><?php echo htmlspecialchars($d['description']);?></td>
        <td>&pound;<?php echo number_format($d['price'],2);?></td>
        <td><span class="badge <?php echo $d['is_active']?'badge-yes':'badge-no';?>"><?php echo $d['is_active']?'Yes':'No';?></span></td>
        <td>
          <div class="table-actions">
            <button class="btn btn-sm btn-light" onclick="toggleEdit('d<?php echo $d['id'];?>')">Edit</button>
            <form method="POST" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="del_deal"/><input type="hidden" name="id" value="<?php echo $d['id'];?>"/><button class="btn btn-sm btn-red" type="submit">Delete</button></form>
          </div>
          <div class="edit-row" id="d<?php echo $d['id'];?>">
            <form method="POST"><input type="hidden" name="action" value="edit_deal"/><input type="hidden" name="id" value="<?php echo $d['id'];?>"/>
              <div class="form-row">
                <div class="form-group"><label>Name</label><input type="text" name="deal_name" value="<?php echo htmlspecialchars($d['name'],ENT_QUOTES);?>" required/></div>
                <div class="form-group" style="max-width:120px;"><label>Price (£)</label><input type="number" step="0.01" name="deal_price" value="<?php echo $d['price'];?>"/></div>
                <div class="form-group"><label>Description</label><input type="text" name="deal_desc" value="<?php echo htmlspecialchars($d['description'],ENT_QUOTES);?>"/></div>
                <div class="form-group"><label>Active</label><br/><input type="checkbox" name="is_active" <?php echo $d['is_active']?'checked':'';?> style="width:auto;margin-top:8px;"/></div>
              </div>
              <div class="form-row" style="margin-top:10px;">
                <div class="form-group" style="flex:1;"><label>Items (one per line)</label><textarea name="deal_items"><?php echo htmlspecialchars(implode("\n",$di));?></textarea></div>
                <div class="form-group"><label>&nbsp;</label><button type="submit" class="btn">Save</button></div>
              </div>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; else: echo '<tr><td colspan="7" class="no-data">No deals yet.</td></tr>'; endif;?>
    </tbody>
  </table>
</div>

<?php // ════ REVIEWS / FEEDBACK ════
elseif($tab==='reviews'):
  $__fb=$pdo->query("SELECT COUNT(*) n, ROUND(AVG(rating), 2) a FROM order_feedback")->fetch(PDO::FETCH_ASSOC);
  $nfc=(int)$__fb['n'];
  $avgf=$__fb['a'];
?>
<p style="margin-bottom:14px;color:#555;font-size:0.85rem;"><strong><?php echo $nfc;?></strong> review(s)<?php if($nfc>0&&$avgf!==null): ?> &mdash; average rating <strong><?php echo htmlspecialchars((string)$avgf);?></strong> / 5<?php endif; ?></p>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>Date</th><th>Order ref</th><th>Type</th><th>Item</th><th>Customer</th><th>Rating</th><th>Feedback</th><th></th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM order_feedback ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    if(count($r)): foreach($r as $row):
      $__rt=(int)$row['rating'];
      $__stars='';
      for($__i=1;$__i<=5;$__i++) $__stars .= ($__i<=$__rt ? "\u{2605}" : "\u{2606}");
      $__txt=trim((string)($row['feedback_text']??''));
      $__type=$row['order_type']==='collection'?'Collection':'Delivery';
      ?>
      <tr>
        <td style="white-space:nowrap;font-size:0.79rem;"><?php echo date('d M Y, H:i',strtotime($row['created_at']));?></td>
        <td><strong><?php echo htmlspecialchars($row['order_ref']);?></strong></td>
        <td><span class="badge badge-<?php echo $row['order_type']==='collection'?'ready':'confirmed';?>"><?php echo htmlspecialchars($__type);?></span></td>
        <td style="font-size:0.79rem;"><?php echo htmlspecialchars(trim((string)($row['menu_item_name'] ?? '')) ?: '—'); ?></td>
        <td><?php
          $__cn = trim((string)($row['customer_name']??''));
          echo $__cn !== '' ? htmlspecialchars($__cn) : '–';
        ?></td>
        <td title="<?php echo htmlspecialchars($__rt.' out of 5'); ?>" style="white-space:nowrap;letter-spacing:1px;color:#b8860b;"><?php echo $__stars; ?> <small style="color:#777;">(<?php echo $__rt; ?>/5)</small></td>
        <td style="font-size:0.79rem;max-width:280px;"><span<?php if ($__txt !== '') { echo ' title="'.htmlspecialchars($__txt, ENT_QUOTES).'"'; } ?>><?php
          if ($__txt === '') {
            echo '–';
          } else {
            $shown = strlen($__txt) > 200 ? (substr($__txt, 0, 200) . '…') : $__txt;
            echo nl2br(htmlspecialchars($shown));
          }
        ?></span></td>
        <td><form method="POST" action="admin.php?tab=reviews" onsubmit="return confirm('Remove this review?')" style="display:inline;"><input type="hidden" name="action" value="del_feedback"/><input type="hidden" name="id" value="<?php echo (int)$row['id'];?>"/><button class="btn btn-sm btn-red" type="submit">Delete</button></form></td>
      </tr>
    <?php endforeach; else: echo '<tr><td colspan="8" class="no-data">No reviews yet. They appear here after customers pay and submit feedback from checkout.</td></tr>'; endif;?>
    </tbody>
  </table>
</div>

<?php // ════ SUBSCRIBERS ════
elseif($tab==='subscribers'):
  $ts=(int)$pdo->query("SELECT COUNT(*) c FROM subscribers")->fetch()['c'];
?>
<p style="margin-bottom:14px;color:#555;font-size:0.85rem;"><strong><?php echo $ts;?></strong> subscriber(s)</p>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead><tr><th>#</th><th>Email Address</th><th>Signed Up</th></tr></thead>
    <tbody>
    <?php $r=$pdo->query("SELECT * FROM subscribers ORDER BY created_at DESC")->fetchAll();
    if(count($r)): foreach($r as $row):?>
      <tr><td><?php echo $row['id'];?></td><td><?php echo htmlspecialchars($row['email']);?></td><td><?php echo date('d M Y, H:i',strtotime($row['created_at']));?></td></tr>
    <?php endforeach; else: echo '<tr><td colspan="3" class="no-data">No subscribers yet.</td></tr>'; endif;?>
    </tbody>
  </table>
</div>

<?php endif;?>

</div><!-- /admin-wrapper -->

<script>
function toggleEdit(id){
  var el=document.getElementById(id);
  el.classList.toggle('open');
}
</script>
</body>
</html>