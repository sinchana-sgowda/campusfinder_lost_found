<?php
error_reporting(0);
require_once 'lostfound_config.php';
$error = '';

if (isset($_POST['student_login'])) {
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM STUDENT WHERE Email=? AND Password=?");
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows === 1) {
        $s = $r->fetch_assoc();
        $_SESSION['sid'] = $s['StudentID'];
        $_SESSION['sname'] = $s['Name'];
        $_SESSION['role'] = 'student';
        header("Location: lostfound_dashboard.php"); exit();
    } else { $error = "Invalid email or password!"; }
}

if (isset($_POST['student_register'])) {
    $name=trim($_POST['name']); $email=trim($_POST['email']);
    $phone=trim($_POST['phone']); $dept=trim($_POST['dept']);
    $pass=trim($_POST['password']);
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO STUDENT (Name,Email,Phone,Department,Password) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss",$name,$email,$phone,$dept,$pass);
    if ($stmt->execute()) {
        $_SESSION['sid'] = $db->insert_id;
        $_SESSION['sname'] = $name;
        $_SESSION['role'] = 'student';
        header("Location: lostfound_dashboard.php"); exit();
    } else { $error = "Email already registered!"; }
}

if (isset($_POST['admin_login'])) {
    $name=trim($_POST['admin_name']); $pass=trim($_POST['admin_pass']);
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM ADMIN WHERE Name=? AND Password=?");
    $stmt->bind_param("ss",$name,$pass);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r->num_rows === 1) {
        $a = $r->fetch_assoc();
        $_SESSION['aid'] = $a['AdminID'];
        $_SESSION['aname'] = $a['Name'];
        $_SESSION['role'] = 'admin';
        header("Location: lostfound_admin.php"); exit();
    } else { $error = "Invalid admin credentials!"; }
}

$db = getDB();
$total_lost = $db->query("SELECT COUNT(*) as c FROM ITEM WHERE Type='Lost' AND Status='Open'")->fetch_assoc()['c'];
$total_found = $db->query("SELECT COUNT(*) as c FROM ITEM WHERE Type='Found' AND Status='Open'")->fetch_assoc()['c'];
$total_claimed = $db->query("SELECT COUNT(*) as c FROM ITEM WHERE Status='Claimed'")->fetch_assoc()['c'];
$recent = $db->query("SELECT I.*,S.Name as SName FROM ITEM I JOIN STUDENT S ON I.StudentID=S.StudentID WHERE I.Status='Open' ORDER BY I.created_at DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>CampusFinder – Lost & Found</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:Arial,sans-serif;background:#f8f9fa;}
nav{background:#2c3e50;color:white;padding:16px 40px;display:flex;justify-content:space-between;align-items:center;}
nav h1{font-size:1.4rem;color:#f39c12;}
nav span{font-size:0.85rem;color:#aaa;}
.hero{background:linear-gradient(135deg,#2c3e50,#3498db);color:white;padding:60px 40px;text-align:center;}
.hero h2{font-size:2.8rem;margin-bottom:12px;}
.hero h2 span{color:#f39c12;}
.hero p{font-size:1.1rem;color:#bde3ff;margin-bottom:32px;}
.stats-row{display:flex;justify-content:center;gap:40px;margin-top:20px;}
.stat{text-align:center;}
.stat .num{font-size:2.5rem;font-weight:800;color:#f39c12;}
.stat .lbl{font-size:0.8rem;color:#bde3ff;text-transform:uppercase;letter-spacing:1px;}
.main{max-width:1100px;margin:0 auto;padding:48px 24px;display:grid;grid-template-columns:1fr 360px;gap:40px;}
.section-title{font-size:1.3rem;font-weight:800;color:#2c3e50;margin-bottom:20px;border-bottom:3px solid #f39c12;padding-bottom:10px;display:inline-block;}
.items-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:32px;}
.item-card{background:white;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.08);border-left:4px solid #3498db;transition:transform 0.2s;}
.item-card:hover{transform:translateY(-2px);}
.item-card.lost{border-left-color:#e74c3c;}
.item-card.found{border-left-color:#27ae60;}
.item-type{font-size:0.72rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;}
.item-type.lost{color:#e74c3c;}
.item-type.found{color:#27ae60;}
.item-title{font-size:1rem;font-weight:700;color:#2c3e50;margin-bottom:6px;}
.item-meta{font-size:0.82rem;color:#888;margin-bottom:4px;}
.item-cat{display:inline-block;background:#eef2ff;color:#3498db;padding:3px 10px;border-radius:20px;font-size:0.74rem;font-weight:700;margin-top:8px;}
.card{background:white;border-radius:14px;padding:28px;box-shadow:0 2px 12px rgba(0,0,0,0.1);}
.card h3{font-size:1.1rem;color:#2c3e50;margin-bottom:18px;border-bottom:2px solid #eee;padding-bottom:10px;}
.tabs{display:flex;border:2px solid #2c3e50;border-radius:8px;overflow:hidden;margin-bottom:18px;}
.tab{flex:1;padding:9px;text-align:center;font-weight:700;font-size:0.82rem;cursor:pointer;background:transparent;border:none;}
.tab.active{background:#2c3e50;color:white;}
.tab-content{display:none;}
.tab-content.active{display:block;}
.form-group{margin-bottom:12px;}
label{display:block;font-size:0.8rem;font-weight:600;color:#444;margin-bottom:5px;}
input,select{width:100%;padding:10px 12px;border:2px solid #ddd;border-radius:8px;font-size:0.88rem;outline:none;}
input:focus,select:focus{border-color:#2c3e50;}
.btn{width:100%;padding:12px;background:#2c3e50;color:white;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:0.95rem;margin-top:6px;}
.btn:hover{background:#1a252f;}
.btn-orange{background:#f39c12;} .btn-orange:hover{background:#e67e22;}
.error{background:#fdecea;border:1px solid #e74c3c;color:#c0392b;padding:10px;border-radius:8px;margin-bottom:12px;font-size:0.85rem;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.admin-card{background:#2c3e50;color:white;border-radius:14px;padding:24px;margin-top:20px;}
.admin-card h3{color:#f39c12;margin-bottom:16px;border-bottom:1px solid #444;padding-bottom:10px;}
.admin-card input{background:#3d5166;border-color:#5d7fa0;color:white;}
.admin-card input::placeholder{color:#aaa;}
.admin-card label{color:#bde3ff;}
.admin-card .btn{background:#e74c3c;} .admin-card .btn:hover{background:#c0392b;}
@media(max-width:900px){.main{grid-template-columns:1fr;}.items-grid{grid-template-columns:1fr;}.form-row{grid-template-columns:1fr;}}
</style>
</head>
<body>
<nav>
  <h1>🔍 CampusFinder</h1>
  <span>College Lost & Found Platform</span>
</nav>
<div class="hero">
  <h2>Lost Something? <span>Found Something?</span></h2>
  <p>Help your fellow students by reporting lost or found items on campus.</p>
  <div class="stats-row">
    <div class="stat"><div class="num"><?=$total_lost?></div><div class="lbl">Lost Items</div></div>
    <div class="stat"><div class="num"><?=$total_found?></div><div class="lbl">Found Items</div></div>
    <div class="stat"><div class="num"><?=$total_claimed?></div><div class="lbl">Items Returned</div></div>
  </div>
</div>

<div class="main">
  <div>
    <div class="section-title">Recent Items</div>
    <div class="items-grid">
      <?php foreach($recent as $item): ?>
      <div class="item-card <?=strtolower($item['Type'])?>">
        <div class="item-type <?=strtolower($item['Type'])?>"><?=$item['Type']=='Lost'?'🔴 LOST':'🟢 FOUND'?></div>
        <div class="item-title"><?=htmlspecialchars($item['Title'])?></div>
        <div class="item-meta">📍 <?=htmlspecialchars($item['Location'])?></div>
        <div class="item-meta">📅 <?=$item['Date']?></div>
        <div class="item-meta">👤 <?=htmlspecialchars($item['SName'])?></div>
        <span class="item-cat"><?=htmlspecialchars($item['Category'])?></span>
      </div>
      <?php endforeach; ?>
      <?php if(empty($recent)): ?>
      <div style="grid-column:1/-1;text-align:center;color:#888;padding:40px;">No items posted yet.</div>
      <?php endif; ?>
    </div>
    <p style="color:#888;font-size:0.9rem;">Login to view all items, report lost/found items, and contact finders.</p>
  </div>

  <div>
    <?php if($error): ?><div class="error">⚠ <?=$error?></div><?php endif; ?>
    <div class="card">
      <h3>🎓 Student Portal</h3>
      <div class="tabs">
        <button class="tab active" onclick="switchTab('login',this)">Login</button>
        <button class="tab" onclick="switchTab('register',this)">Register</button>
      </div>
      <div class="tab-content active" id="tab-login">
        <form method="POST">
          <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="your@college.com" required></div>
          <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="••••••••" required></div>
          <button type="submit" name="student_login" class="btn">Login →</button>
        </form>
      </div>
      <div class="tab-content" id="tab-register">
        <form method="POST">
          <div class="form-group"><label>Full Name</label><input type="text" name="name" placeholder="Your name" required></div>
          <div class="form-row">
            <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="Email" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="Phone" required></div>
          </div>
          <div class="form-group"><label>Department</label>
            <select name="dept"><option value="">Select Dept</option>
            <option>CSE</option><option>ECE</option><option>ME</option><option>Civil</option><option>EEE</option><option>ISE</option></select>
          </div>
          <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Create password" required></div>
          <button type="submit" name="student_register" class="btn btn-orange">Register →</button>
        </form>
      </div>
    </div>
    <div class="admin-card">
      <h3>🔐 Admin Login</h3>
      <form method="POST">
        <div class="form-group"><label>Username</label><input type="text" name="admin_name" placeholder="Administrator" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="admin_pass" placeholder="••••••••" required></div>
        <button type="submit" name="admin_login" class="btn">Admin Access →</button>
      </form>
    </div>
  </div>
</div>
<script>
function switchTab(tab,btn){
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-'+tab).classList.add('active');
  btn.classList.add('active');
}
</script>
</body>
</html>
