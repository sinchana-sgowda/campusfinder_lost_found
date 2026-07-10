<?php
error_reporting(0);
require_once 'lostfound_config.php';
if (!isset($_SESSION['sid']) || $_SESSION['role'] !== 'student') { header("Location: lostfound_index.php"); exit(); }
$db = getDB();
$sid = $_SESSION['sid'];
$msg = '';

if (isset($_POST['report_item'])) {
    $type=$_POST['type']; $title=trim($_POST['title']);
    $desc=trim($_POST['description']); $cat=trim($_POST['category']);
    $loc=trim($_POST['location']); $date=$_POST['date'];
    $stmt=$db->prepare("INSERT INTO ITEM (StudentID,Type,Title,Description,Category,Location,Date) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("issssss",$sid,$type,$title,$desc,$cat,$loc,$date);
    $stmt->execute();
    $msg="Item reported successfully!";
}

if (isset($_POST['submit_claim'])) {
    $iid=intval($_POST['item_id']); $message=trim($_POST['message']);
    $check=$db->query("SELECT ClaimID FROM CLAIM WHERE ItemID=$iid AND StudentID=$sid");
    if ($check->num_rows === 0) {
        $stmt=$db->prepare("INSERT INTO CLAIM (ItemID,StudentID,Message) VALUES (?,?,?)");
        $stmt->bind_param("iis",$iid,$sid,$message);
        $stmt->execute();
        $msg="Claim submitted! The reporter will contact you.";
    } else { $msg="You already claimed this item!"; }
}

$tab=$_GET['tab'] ?? 'browse';
$search=$_GET['search'] ?? '';
$filter_type=$_GET['type'] ?? '';
$filter_cat=$_GET['cat'] ?? '';

$where="WHERE I.Status='Open'";
if ($search) $where.=" AND (I.Title LIKE '%$search%' OR I.Description LIKE '%$search%' OR I.Location LIKE '%$search%')";
if ($filter_type) $where.=" AND I.Type='$filter_type'";
if ($filter_cat) $where.=" AND I.Category='$filter_cat'";

$all_items=$db->query("SELECT I.*,S.Name as SName,S.Department FROM ITEM I JOIN STUDENT S ON I.StudentID=S.StudentID $where ORDER BY I.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$my_items=$db->query("SELECT * FROM ITEM WHERE StudentID=$sid ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$my_claims=$db->query("SELECT C.*,I.Title,I.Type,I.Location FROM CLAIM C JOIN ITEM I ON C.ItemID=I.ItemID WHERE C.StudentID=$sid ORDER BY C.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$student=$db->query("SELECT * FROM STUDENT WHERE StudentID=$sid")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard – CampusFinder</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;background:#f8f9fa;}
nav{background:#2c3e50;color:white;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;}
nav h2{color:#f39c12;font-size:1.2rem;}
.nav-links{display:flex;gap:6px;align-items:center;}
.nav-links a{color:#aaa;text-decoration:none;border:1px solid transparent;padding:7px 13px;border-radius:6px;font-size:0.8rem;}
.nav-links a:hover,.nav-links a.active{border-color:#aaa;color:white;}
.nav-links a.logout:hover{border-color:#e74c3c;color:#e74c3c;}
.container{max-width:1100px;margin:32px auto;padding:0 20px;}
.success{background:#d5f5e3;border:1px solid #27ae60;color:#1e8449;padding:12px 16px;border-radius:8px;margin-bottom:20px;}
.welcome{background:linear-gradient(135deg,#2c3e50,#3498db);color:white;border-radius:16px;padding:24px 32px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;}
.welcome h2{font-size:1.6rem;} .welcome h2 span{color:#f39c12;}
.welcome p{color:#bde3ff;margin-top:6px;}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;}
.stat{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-top:4px solid #3498db;}
.stat .lbl{font-size:0.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#888;}
.stat .val{font-size:2rem;font-weight:800;margin-top:6px;color:#2c3e50;}
.search-bar{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap;}
.search-bar input,.search-bar select{padding:10px 14px;border:2px solid #ddd;border-radius:8px;font-size:0.9rem;outline:none;flex:1;min-width:150px;}
.search-bar input:focus,.search-bar select:focus{border-color:#2c3e50;}
.search-bar button{padding:10px 24px;background:#2c3e50;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;}
.items-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
.item-card{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-top:4px solid #3498db;}
.item-card.lost{border-top-color:#e74c3c;}
.item-card.found{border-top-color:#27ae60;}
.item-type{font-size:0.72px;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;font-size:0.72rem;}
.item-type.lost{color:#e74c3c;} .item-type.found{color:#27ae60;}
.item-title{font-size:1rem;font-weight:700;color:#2c3e50;margin-bottom:6px;}
.item-meta{font-size:0.8rem;color:#888;margin-bottom:3px;}
.item-desc{font-size:0.85rem;color:#555;margin:8px 0;line-height:1.4;}
.item-cat{display:inline-block;background:#eef2ff;color:#3498db;padding:3px 10px;border-radius:20px;font-size:0.74rem;font-weight:700;}
.claim-btn{width:100%;margin-top:12px;padding:9px;background:#f39c12;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:0.85rem;}
.claim-btn:hover{background:#e67e22;}
.form-card{background:white;border-radius:14px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:24px;}
h3{font-size:1.2rem;font-weight:800;color:#2c3e50;margin-bottom:16px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
label{font-size:0.8rem;font-weight:600;color:#444;}
input,select,textarea{padding:10px 12px;border:2px solid #ddd;border-radius:8px;font-size:0.88rem;outline:none;font-family:Arial;}
input:focus,select:focus,textarea:focus{border-color:#2c3e50;}
.btn{padding:12px 24px;background:#2c3e50;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;}
.btn:hover{background:#1a252f;}
.tbl-wrap{background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px;}
table{width:100%;border-collapse:collapse;}
th{background:#2c3e50;color:white;padding:11px 14px;text-align:left;font-size:0.76rem;letter-spacing:1px;text-transform:uppercase;}
td{padding:11px 14px;border-bottom:1px solid #f0f0f0;font-size:0.88rem;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8fafc;}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:0.74rem;font-weight:700;}
.badge-open{background:#d6eaf8;color:#1a5276;}
.badge-claimed{background:#d5f5e3;color:#1e8449;}
.badge-closed{background:#f0f0f0;color:#888;}
.badge-pending{background:#fef9e7;color:#d68910;}
.badge-approved{background:#d5f5e3;color:#1e8449;}
.badge-rejected{background:#fdecea;color:#c0392b;}
.badge-lost{background:#fdecea;color:#c0392b;}
.badge-found{background:#d5f5e3;color:#1e8449;}
.page{display:none;} .page.active{display:block;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;justify-content:center;align-items:center;}
.modal.open{display:flex;}
.modal-box{background:white;border-radius:16px;padding:32px;width:400px;max-width:90%;}
.modal-box h3{margin-bottom:16px;}
.modal-close{float:right;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#888;}
@media(max-width:800px){.items-grid{grid-template-columns:1fr;}.form-grid{grid-template-columns:1fr;}.stats{grid-template-columns:1fr 1fr;}}
</style>
</head>
<body>
<nav>
  <h2>🔍 CampusFinder</h2>
  <div class="nav-links">
    <a href="?tab=browse" class="<?=$tab=='browse'?'active':''?>">Browse Items</a>
    <a href="?tab=report" class="<?=$tab=='report'?'active':''?>">Report Item</a>
    <a href="?tab=myitems" class="<?=$tab=='myitems'?'active':''?>">My Items</a>
    <a href="?tab=claims" class="<?=$tab=='claims'?'active':''?>">My Claims</a>
    <a href="lostfound_logout.php" class="logout">Logout</a>
  </div>
</nav>

<div class="container">
  <?php if($msg): ?><div class="success">✓ <?=$msg?></div><?php endif; ?>

  <div class="welcome">
    <div>
      <h2>Hello, <span><?=htmlspecialchars(explode(' ',$_SESSION['sname'])[0])?></span>! 👋</h2>
      <p>Dept: <?=htmlspecialchars($student['Department'])?> | Help your campus community find lost items.</p>
    </div>
  </div>

  <div class="stats">
    <div class="stat"><div class="lbl">My Reported Items</div><div class="val"><?=count($my_items)?></div></div>
    <div class="stat"><div class="lbl">My Claims</div><div class="val"><?=count($my_claims)?></div></div>
    <div class="stat"><div class="lbl">Open Items on Campus</div><div class="val"><?=count($all_items)?></div></div>
  </div>

  <!-- BROWSE -->
  <div class="page <?=$tab=='browse'?'active':''?>">
    <h3>Browse Lost & Found Items</h3>
    <form method="GET" action="?tab=browse">
      <div class="search-bar">
        <input type="text" name="search" placeholder="🔍 Search by title, location..." value="<?=htmlspecialchars($search)?>">
        <select name="type">
          <option value="">All Types</option>
          <option <?=$filter_type=='Lost'?'selected':''?>>Lost</option>
          <option <?=$filter_type=='Found'?'selected':''?>>Found</option>
        </select>
        <select name="cat">
          <option value="">All Categories</option>
          <?php foreach(['Electronics','Wallet','ID/Cards','Books','Accessories','Clothing','Keys','Other'] as $c): ?>
          <option <?=$filter_cat==$c?'selected':''?>><?=$c?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
      </div>
    </form>
    <div class="items-grid">
      <?php foreach($all_items as $item): ?>
      <div class="item-card <?=strtolower($item['Type'])?>">
        <div class="item-type <?=strtolower($item['Type'])?>"><?=$item['Type']=='Lost'?'🔴 LOST':'🟢 FOUND'?></div>
        <div class="item-title"><?=htmlspecialchars($item['Title'])?></div>
        <div class="item-desc"><?=htmlspecialchars(substr($item['Description'],0,80))?>...</div>
        <div class="item-meta">📍 <?=htmlspecialchars($item['Location'])?></div>
        <div class="item-meta">📅 <?=$item['Date']?></div>
        <div class="item-meta">👤 <?=htmlspecialchars($item['SName'])?> (<?=htmlspecialchars($item['Department'])?>)</div>
        <span class="item-cat"><?=htmlspecialchars($item['Category'])?></span>
        <?php if($item['StudentID'] != $sid): ?>
        <button class="claim-btn" onclick="openClaim(<?=$item['ItemID']?>, '<?=htmlspecialchars($item['Title'])?>')">
          <?=$item['Type']=='Lost'?'I Found This!':'I Lost This!'?>
        </button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if(empty($all_items)): ?>
      <div style="grid-column:1/-1;text-align:center;color:#888;padding:48px;">No items found matching your search.</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- REPORT -->
  <div class="page <?=$tab=='report'?'active':''?>">
    <h3>Report a Lost or Found Item</h3>
    <div class="form-card">
      <form method="POST" action="?tab=report">
        <div class="form-grid">
          <div class="form-group">
            <label>Item Type</label>
            <select name="type" required>
              <option value="">Select Type</option>
              <option value="Lost">🔴 I Lost an Item</option>
              <option value="Found">🟢 I Found an Item</option>
            </select>
          </div>
          <div class="form-group">
            <label>Category</label>
            <select name="category" required>
              <option value="">Select Category</option>
              <?php foreach(['Electronics','Wallet','ID/Cards','Books','Accessories','Clothing','Keys','Other'] as $c): ?>
              <option><?=$c?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group full"><label>Item Title</label><input type="text" name="title" placeholder="e.g. Blue Water Bottle, Black Wallet..." required></div>
          <div class="form-group full"><label>Description</label><textarea name="description" rows="3" placeholder="Describe the item in detail – color, brand, any marks..." required></textarea></div>
          <div class="form-group"><label>Location</label><input type="text" name="location" placeholder="e.g. Library 2nd Floor, Canteen..." required></div>
          <div class="form-group"><label>Date Lost/Found</label><input type="date" name="date" required></div>
        </div>
        <button type="submit" name="report_item" class="btn" style="margin-top:16px;width:100%;">Submit Report →</button>
      </form>
    </div>
  </div>

  <!-- MY ITEMS -->
  <div class="page <?=$tab=='myitems'?'active':''?>">
    <h3>My Reported Items</h3>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Type</th><th>Title</th><th>Category</th><th>Location</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($my_items as $item): ?>
          <tr>
            <td><span class="badge badge-<?=strtolower($item['Type'])?>"><?=$item['Type']?></span></td>
            <td><strong><?=htmlspecialchars($item['Title'])?></strong></td>
            <td><?=htmlspecialchars($item['Category'])?></td>
            <td><?=htmlspecialchars($item['Location'])?></td>
            <td><?=$item['Date']?></td>
            <td><span class="badge badge-<?=strtolower($item['Status'])?>"><?=$item['Status']?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($my_items)): ?><tr><td colspan="6" style="text-align:center;color:#888;padding:24px;">No items reported yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- CLAIMS -->
  <div class="page <?=$tab=='claims'?'active':''?>">
    <h3>My Claims</h3>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Item</th><th>Type</th><th>Location</th><th>Message</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($my_claims as $c): ?>
          <tr>
            <td><strong><?=htmlspecialchars($c['Title'])?></strong></td>
            <td><span class="badge badge-<?=strtolower($c['Type'])?>"><?=$c['Type']?></span></td>
            <td><?=htmlspecialchars($c['Location'])?></td>
            <td><?=htmlspecialchars(substr($c['Message'],0,50))?></td>
            <td><span class="badge badge-<?=strtolower($c['Status'])?>"><?=$c['Status']?></span></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($my_claims)): ?><tr><td colspan="5" style="text-align:center;color:#888;padding:24px;">No claims submitted yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- CLAIM MODAL -->
<div class="modal" id="claimModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeClaim()">✕</button>
    <h3>Submit a Claim</h3>
    <p id="claimItemTitle" style="color:#3498db;font-weight:700;margin-bottom:16px;"></p>
    <form method="POST" action="?tab=browse">
      <input type="hidden" name="item_id" id="claimItemId">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Your Message (proof of ownership)</label>
        <textarea name="message" rows="4" placeholder="Describe how you know this is yours or where you found it..." required></textarea>
      </div>
      <button type="submit" name="submit_claim" class="btn" style="width:100%;">Submit Claim →</button>
    </form>
  </div>
</div>

<script>
function openClaim(id, title) {
  document.getElementById('claimItemId').value = id;
  document.getElementById('claimItemTitle').textContent = '📦 ' + title;
  document.getElementById('claimModal').classList.add('open');
}
function closeClaim() { document.getElementById('claimModal').classList.remove('open'); }
window.onclick = function(e) { if(e.target.id==='claimModal') closeClaim(); }
</script>
</body>
</html>
