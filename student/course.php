<?php
require_once '../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$studentId = $user['id'];
$unread = $db->fetch("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND user_type='student' AND is_read=0", [$studentId])['c'];
$fullName = htmlspecialchars($user['first_name'].' '.$user['last_name']);
$initial = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Course — MyClassroom</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="toast-container"></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-layout">
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">My<span>Classroom</span></div>
  <nav class="sidebar-nav">
    <button class="nav-item" onclick="window.location.href='dashboard.php'"><span class="nav-icon"><i class="fa fa-home"></i></span> Home</button>
    <button class="nav-item" onclick="window.location.href='dashboard.php#courses'"><span class="nav-icon"><i class="fa fa-book"></i></span> My Courses</button>
    <button class="nav-item" onclick="window.location.href='dashboard.php#store'"><span class="nav-icon"><i class="fa fa-store"></i></span> Store</button>
    <button class="nav-item" onclick="window.location.href='dashboard.php#settings'"><span class="nav-icon"><i class="fa fa-cog"></i></span> Settings</button>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user" onclick="toggleProfile()">
      <div class="avatar avatar-sm"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
      <div class="sidebar-user-info"><div class="sidebar-user-name"><?=$fullName?></div><div class="sidebar-user-role">Student</div></div>
    </div>
  </div>
</aside>
<div class="main-content">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="icon-btn" onclick="toggleSidebar()" id="menuBtn"><i class="fa fa-bars"></i></button>
      <span class="topbar-title" id="topbarTitle">Course</span>
    </div>
    <div class="topbar-right">
      <button class="icon-btn" id="notifBtn" onclick="toggleNotif()"><i class="fa fa-bell"></i><?php if($unread>0): ?><span class="notif-dot" id="notifDot"></span><?php endif; ?></button>
      <button class="icon-btn" id="profileBtn" onclick="toggleProfile()">
        <div class="avatar avatar-sm"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
      </button>
    </div>
  </header>
  <div class="page-content" id="coursePageContent">
    <div class="page-header"><h1 id="coursePageTitle">Loading course...</h1><p>Access course content directly.</p></div>
    <div id="coursePageBody"><div class="flex-center" style="padding:40px"><div class="spinner"></div></div></div>
  </div>
</div>
</div>

<!-- PROFILE PANEL -->
<div class="profile-panel" id="profilePanel">
  <div class="profile-panel-header">
    <div class="avatar avatar-lg flex-center" style="margin:0 auto 12px;border:3px solid rgba(255,255,255,0.3)"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
    <div style="font-weight:700;font-size:1.1rem"><?=$fullName?></div>
    <div style="opacity:0.8;font-size:0.85rem">Student — <?=htmlspecialchars($user['student_id']??'')?></div>
  </div>
  <div class="profile-panel-body">
    <div class="profile-info-row"><span class="profile-info-label">Phone</span><span class="profile-info-value"><?=htmlspecialchars($user['phone'])?></span></div>
    <div class="profile-info-row"><span class="profile-info-label">Joined</span><span class="profile-info-value"><?=date('M d, Y',strtotime($user['created_at']))?></span></div>
    <div class="profile-info-row"><span class="profile-info-label">Birthday</span><span class="profile-info-value"><?=htmlspecialchars($user['birthday']??'-')?></span></div>
    <div class="mt-16">
      <button class="btn btn-outline btn-full btn-sm mb-8" onclick="window.location.href='dashboard.php#settings'">Edit Profile</button>
      <a href="../api/auth/logout.php" class="btn btn-ghost btn-full btn-sm"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</div>

<!-- NOTIFICATION PANEL -->
<div class="notif-panel" id="notifPanel">
  <div class="notif-header"><h4>🔔 Notifications</h4><span class="auth-link fs-sm" onclick="markAllRead()">Mark all read</span></div>
  <div class="notif-list" id="notifList"></div>
</div>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/student.js"></script>
</body>
</html>
