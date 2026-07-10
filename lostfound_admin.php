<?php
require_once 'lostfound_config.php';
if (!isset($_SESSION['aid']) || $_SESSION['role'] !== 'admin') { header("Location: lostfound_index.php"); exit(); }
$db = getDB();
$msg = '';

// ── DELETE ITEM ──────────────────────────────────────────────────────────────
if (isset($_GET['del_item'])) {
    $iid = intval($_GET['del_item']);

    // Delete related claims first (avoids foreign key errors)
    $stmt = $db->prepare("DELETE FROM CLAIM WHERE ItemID=?");
    $stmt->bind_param("i", $iid);
    $stmt->execute();
    $stmt->close();

    // Try to delete uploaded image file if it exists
    $stmt = $db->prepare("SELECT Image FROM ITEM WHERE ItemID=?");
    $stmt->bind_param("i", $iid);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item && !empty($item['Image'])) {
        $imgPath = UPLOAD_DIR . $item['Image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    // Delete the item
    $stmt = $db->prepare("DELETE FROM ITEM WHERE ItemID=?");
    $stmt->bind_param("i", $iid);
    if ($stmt->execute()) {
        $msg = "Item deleted successfully!";
    } else {
        $msg = "Delete failed: " . $db->error;
    }
    $stmt->close();
}

// ── DELETE STUDENT ───────────────────────────────────────────────────────────
if (isset($_GET['del_user'])) {
    $uid = intval($_GET['del_user']);
    $stmt = $db->prepare("DELETE FROM STUDENT WHERE StudentID=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    $msg = "Student removed!";
}

// ── CLOSE ITEM ───────────────────────────────────────────────────────────────
if (isset($_GET['close_item'])) {
    $cid = intval($_GET['close_item']);
    $stmt = $db->prepare("UPDATE ITEM SET Status='Closed' WHERE ItemID=?");
    $stmt->bind_param("i", $cid);
    $stmt->execute();
    $stmt->close();
    $msg = "Item closed!";
}

// ── UPDATE CLAIM STATUS ──────────────────────────────────────────────────────
if (isset($_POST['update_claim'])) {
    $claimId = intval($_POST['claim_id']);
    $status  = $_POST['status'];

    $stmt = $db->prepare("UPDATE CLAIM SET Status=? WHERE ClaimID=?");
    $stmt->bind_param("si", $status, $claimId);
    $stmt->execute();
    $stmt->close();

    if ($status === 'Approved') {
        $stmt = $db->prepare("SELECT ItemID FROM CLAIM WHERE ClaimID=?");
        $stmt->bind_param("i", $claimId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $stmt = $db->prepare("UPDATE ITEM SET Status='Claimed' WHERE ItemID=?");
            $stmt->bind_param("i", $row['ItemID']);
            $stmt->execute();
            $stmt->close();
        }
    }
    $msg = "Claim updated!";
}

$tab            = $_GET['tab'] ?? 'overview';
$total_students = $db->query("SELECT COUNT(*) as c FROM STUDENT")->fetch_assoc()['c'];
$total_items    = $db->query("SELECT COUNT(*) as c FROM ITEM")->fetch_assoc()['c'];
$total_lost     = $db->query("SELECT COUNT(*) as c FROM ITEM WHERE Type='Lost'")->fetch_assoc()['c'];
$total_found    = $db->query("SELECT COUNT(*) as c FROM ITEM WHERE Type='Found'")->fetch_assoc()['c'];
$total_claimed  = $db->query("SELECT COUNT(*) as c FROM ITEM WHERE Status='Claimed'")->fetch_assoc()['c'];
$items          = $db->query("SELECT I.*,S.Name as SName,S.Department FROM ITEM I JOIN STUDENT S ON I.StudentID=S.StudentID ORDER BY I.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$students       = $db->query("SELECT * FROM STUDENT ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$claims         = $db->query("SELECT C.*,I.Title,I.Type,S.Name as SName,S.Email FROM CLAIM C JOIN ITEM I ON C.ItemID=I.ItemID JOIN STUDENT S ON C.StudentID=S.StudentID ORDER BY C.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin – CampusFinder</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;background:#f8f9fa;}
nav{background:#2c3e50;color:white;padding:14px 32px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;}
nav h2{color:#f39c12;font-size:1.2rem;}
.nav-links{display:flex;gap:6px;flex-wrap:wrap;}
.nav-links a{color:#aaa;text-decoration:none;border:1px solid transparent;padding:7px 12px;border-radius:6px;font-size:0.8rem;}
.nav-links a:hover,.nav-links a.active{border-color:#aaa;color:white;}
.nav-links a.logout:hover{border-color:#e74c3c;color:#e74c3c;}
.container{max-width:1100px;margin:32px auto;padding:0 20px;}
.success{background:#d5f5e3;border:1px solid #27ae60;color:#1e8449;padding:12px;border-radius:8px;margin-bottom:20px;}
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:28px;}
.stat{background:white;border-radius:12px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-top:4px solid #3498db;text-align:center;}
.stat .lbl{font-size:0.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#888;}
.stat .val{font-size:1.8rem;font-weight:800;margin-top:6px;color:#2c3e50;}
h3{font-size:1.1rem;font-weight:800;color:#2c3e50;margin-bottom:14px;}
.tbl-wrap{background:white;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:24px;}
table{width:100%;border-collapse:collapse;}
th{background:#2c3e50;color:white;padding:11px 13px;text-align:left;font-size:0.74rem;letter-spacing:1px;text-transform:uppercase;}
td{padding:10px 13px;border-bottom:1px solid #f0f0f0;font-size:0.85rem;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f8fafc;}
.badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:0.72rem;font-weight:700;}
.badge-open{background:#d6eaf8;color:#1a5276;}
.badge-claimed{background:#d5f5e3;color:#1e8449;}
.badge-closed{background:#f0f0f0;color:#888;}
.badge-lost{background:#fdecea;color:#c0392b;}
.badge-found{background:#d5f5e3;color:#1e8449;}
.badge-pending{background:#fef9e7;color:#d68910;}
.badge-approved{background:#d5f5e3;color:#1e8449;}
.badge-rejected{background:#fdecea;color:#c0392b;}
.btn-sm{padding:5px 11px;border-radius:6px;font-size:0.74rem;font-weight:700;cursor:pointer;border:none;text-decoration:none;display:inline-block;}
.btn-red{background:#e74c3c;color:white;}.btn-red:hover{background:#c0392b;}
.btn-grey{background:#95a5a6;color:white;}.btn-grey:hover{background:#7f8c8d;}
.btn-blue{background:#3498db;color:white;}.btn-blue:hover{background:#2980b9;}
.page{display:none;}.page.active{display:block;}
select{padding:6px 10px;border:1px solid #ddd;border-radius:6px;font-size:0.82rem;}
.thumb{width:48px;height:48px;object-fit:cover;border-radius:8px;border:2px solid #eee;}
.no-img{width:48px;height:48px;background:#f0f0f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;}
</style>
</head>
<body>
<nav>
  <h2>🔍 CampusFinder Admin</h2>
  <div class="nav-links">
    <a href="?tab=overview" class="<?=$tab=='overview'?'active':''?>">Overview</a>
    <a href="?tab=items"    class="<?=$tab=='items'   ?'active':''?>">All Items</a>
    <a href="?tab=claims"   class="<?=$tab=='claims'  ?'active':''?>">Claims</a>
    <a href="?tab=students" class="<?=$tab=='students'?'active':''?>">Students</a>
    <a href="lostfound_logout.php" class="logout">Logout</a>
  </div>
</nav>
<div class="container">
  <?php if($msg): ?><div class="success">✓ <?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="stats">
    <div class="stat"><div class="lbl">Students</div><div class="val"><?=$total_students?></div></div>
    <div class="stat"><div class="lbl">Total Items</div><div class="val"><?=$total_items?></div></div>
    <div class="stat"><div class="lbl">Lost</div><div class="val" style="color:#e74c3c"><?=$total_lost?></div></div>
    <div class="stat"><div class="lbl">Found</div><div class="val" style="color:#27ae60"><?=$total_found?></div></div>
    <div class="stat"><div class="lbl">Returned</div><div class="val" style="color:#f39c12"><?=$total_claimed?></div></div>
  </div>

  <!-- OVERVIEW -->
  <div class="page <?=$tab=='overview'?'active':''?>">
    <h3>Recent Items</h3>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Photo</th><th>Type</th><th>Title</th><th>By</th><th>Location</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach(array_slice($items,0,10) as $item): ?>
        <tr>
          <td>
            <?php
              $imgFile = !empty($item['Image']) ? UPLOAD_DIR . $item['Image'] : '';
              if ($imgFile && file_exists($imgFile)):
            ?>
              <img src="<?=UPLOAD_URL.htmlspecialchars($item['Image'])?>" class="thumb">
            <?php else: ?>
              <div class="no-img"><?=$item['Type']=='Lost'?'🔴':'🟢'?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-<?=strtolower($item['Type'])?>"><?=$item['Type']?></span></td>
          <td><strong><?=htmlspecialchars($item['Title'])?></strong></td>
          <td><?=htmlspecialchars($item['SName'])?></td>
          <td><?=htmlspecialchars($item['Location'])?></td>
          <td><?=$item['Date']?></td>
          <td><span class="badge badge-<?=strtolower($item['Status'])?>"><?=$item['Status']?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <!-- ALL ITEMS -->
  <div class="page <?=$tab=='items'?'active':''?>">
    <h3>All Items (<?=$total_items?>)</h3>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Photo</th><th>Type</th><th>Title</th><th>By</th><th>Location</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($items as $item): ?>
        <tr>
          <td>
            <?php
              $imgFile = !empty($item['Image']) ? UPLOAD_DIR . $item['Image'] : '';
              if ($imgFile && file_exists($imgFile)):
            ?>
              <img src="<?=UPLOAD_URL.htmlspecialchars($item['Image'])?>" class="thumb">
            <?php else: ?>
              <div class="no-img"><?=$item['Type']=='Lost'?'🔴':'🟢'?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-<?=strtolower($item['Type'])?>"><?=$item['Type']?></span></td>
          <td><strong><?=htmlspecialchars($item['Title'])?></strong></td>
          <td><?=htmlspecialchars($item['SName'])?></td>
          <td><?=htmlspecialchars($item['Location'])?></td>
          <td><span class="badge badge-<?=strtolower($item['Status'])?>"><?=$item['Status']?></span></td>
          <td style="display:flex;gap:6px;">
            <a href="?tab=items&close_item=<?=$item['ItemID']?>" class="btn-sm btn-grey"
               onclick="return confirm('Close this item?')">Close</a>
            <a href="?tab=items&del_item=<?=$item['ItemID']?>" class="btn-sm btn-red"
               onclick="return confirm('Delete this item? This cannot be undone.')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($items)): ?>
        <tr><td colspan="7" style="text-align:center;color:#888;padding:24px;">No items yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </div>

  <!-- CLAIMS -->
  <div class="page <?=$tab=='claims'?'active':''?>">
    <h3>All Claims</h3>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Item</th><th>Type</th><th>Claimed By</th><th>Email</th><th>Message</th><th>Status</th><th>Update</th></tr></thead>
      <tbody>
        <?php foreach($claims as $c): ?>
        <tr>
          <td><strong><?=htmlspecialchars($c['Title'])?></strong></td>
          <td><span class="badge badge-<?=strtolower($c['Type'])?>"><?=$c['Type']?></span></td>
          <td><?=htmlspecialchars($c['SName'])?></td>
          <td style="font-size:0.78rem"><?=htmlspecialchars($c['Email'])?></td>
          <td style="max-width:150px;font-size:0.8rem"><?=htmlspecialchars(substr($c['Message'],0,60))?></td>
          <td><span class="badge badge-<?=strtolower($c['Status'])?>"><?=$c['Status']?></span></td>
          <td>
            <form method="POST" action="?tab=claims" style="display:flex;gap:5px;">
              <input type="hidden" name="claim_id" value="<?=$c['ClaimID']?>">
              <select name="status" style="font-size:0.78rem;">
                <option <?=$c['Status']=='Pending' ?'selected':''?>>Pending</option>
                <option <?=$c['Status']=='Approved'?'selected':''?>>Approved</option>
                <option <?=$c['Status']=='Rejected'?'selected':''?>>Rejected</option>
              </select>
              <button type="submit" name="update_claim" class="btn-sm btn-blue">✓</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($claims)): ?>
        <tr><td colspan="7" style="text-align:center;color:#888;padding:24px;">No claims yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </div>

  <!-- STUDENTS -->
  <div class="page <?=$tab=='students'?'active':''?>">
    <h3>All Students (<?=$total_students?>)</h3>
    <div class="tbl-wrap"><table>
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Dept</th><th>Joined</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach($students as $s): ?>
        <tr>
          <td>#<?=$s['StudentID']?></td>
          <td><strong><?=htmlspecialchars($s['Name'])?></strong></td>
          <td><?=htmlspecialchars($s['Email'])?></td>
          <td><?=$s['Phone']?></td>
          <td><?=htmlspecialchars($s['Department'])?></td>
          <td><?=date('d M Y',strtotime($s['created_at']))?></td>
          <td><a href="?tab=students&del_user=<?=$s['StudentID']?>" class="btn-sm btn-red"
                 onclick="return confirm('Remove this student?')">Remove</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($students)): ?>
        <tr><td colspan="7" style="text-align:center;color:#888;padding:24px;">No students.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </div>

</div>
</body>
</html>
