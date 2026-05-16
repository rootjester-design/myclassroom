<?php
require_once '../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$studentId = $user['id'];
$stats = [
  'enrolled' => $db->fetch("SELECT COUNT(*) c FROM enrollments WHERE student_id=?",[$studentId])['c'],
  'active'   => $db->fetch("SELECT COUNT(*) c FROM enrollments WHERE student_id=? AND status='active'",[$studentId])['c'],
  'pending'  => $db->fetch("SELECT COUNT(*) c FROM payments WHERE student_id=? AND status='pending'",[$studentId])['c'],
];
$unread = $db->fetch("SELECT COUNT(*) c FROM notifications WHERE user_id=? AND user_type='student' AND is_read=0",[$studentId])['c'];
$fullName = htmlspecialchars($user['first_name'].' '.$user['last_name']);
$initial = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Student Dashboard — MyClassroom</title>
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
    <button class="nav-item active" onclick="switchTab('home',this)"><span class="nav-icon"><i class="fa fa-home"></i></span> Home</button>
    <button class="nav-item" onclick="switchTab('courses',this)"><span class="nav-icon"><i class="fa fa-book"></i></span> My Courses</button>
    <button class="nav-item" onclick="switchTab('store',this)"><span class="nav-icon"><i class="fa fa-store"></i></span> Store</button>
    <button class="nav-item" onclick="switchTab('settings',this)"><span class="nav-icon"><i class="fa fa-cog"></i></span> Settings</button>
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
      <span class="topbar-title" id="topbarTitle">Home</span>
    </div>
    <div class="topbar-right">
      <button class="icon-btn" id="notifBtn" onclick="toggleNotif()"><i class="fa fa-bell"></i><?php if($unread>0): ?><span class="notif-dot" id="notifDot"></span><?php endif; ?></button>
      <button class="icon-btn" id="profileBtn" onclick="toggleProfile()">
        <div class="avatar avatar-sm"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
      </button>
    </div>
  </header>
  <div class="page-content">
    <!-- HOME -->
    <div id="tab-home" class="tab-content active">
      <div class="welcome-banner">
        <div><h2>Welcome back, <?=htmlspecialchars($user['first_name'])?>! 👋</h2><p>Continue your learning. You have <?=$stats['active']?> active course(s).</p></div>
        <span class="welcome-emoji">🎓</span>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-value"><?=$stats['enrolled']?></div><div class="stat-label">Enrolled</div></div>
        <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value"><?=$stats['active']?></div><div class="stat-label">Active</div></div>
        <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?=$stats['pending']?></div><div class="stat-label">Pending Payments</div></div>
        <div class="stat-card"><div class="stat-icon">🔔</div><div class="stat-value"><?=$unread?></div><div class="stat-label">Notifications</div></div>
      </div>
      <div class="grid-2">
        <div class="card p-20"><h3 class="section-title" style="font-size:1rem">📢 Announcements</h3><div id="home-announcements"><div class="skeleton" style="height:60px;margin-bottom:10px"></div><div class="skeleton" style="height:60px"></div></div></div>
        <div class="card p-20"><h3 class="section-title" style="font-size:1rem">🎬 Upcoming Live Classes</h3><div id="home-zoom"><div class="skeleton" style="height:60px;margin-bottom:10px"></div><div class="skeleton" style="height:60px"></div></div></div>
      </div>
      <div class="card p-20 mt-20"><h3 class="section-title" style="font-size:1rem">📊 Course Progress</h3><div id="home-progress" class="grid-3"></div></div>
    </div>
    <!-- COURSES -->
    <div id="tab-courses" class="tab-content">
      <div class="page-header"><h1>My Courses</h1><p>All your enrolled courses</p></div>
      <div id="courses-list" class="grid-3"></div>
    </div>
    <!-- STORE -->
    <div id="tab-store" class="tab-content">
      <div class="page-header">
        <div class="flex-between flex-wrap" style="gap:12px">
          <div><h1>Store</h1><p>Browse tutors and courses</p></div>
          <div class="search-bar" style="min-width:260px"><i class="fa fa-search search-icon"></i><input type="text" id="storeSearch" placeholder="Search..." oninput="filterStore()"></div>
        </div>
      </div>
      <div class="nav-tabs mb-20">
        <button class="nav-tab active" onclick="storeView('tutors',this)">👨‍🏫 Tutors</button>
        <button class="nav-tab" onclick="storeView('courses',this)">📚 All Courses</button>
      </div>
      <div id="store-tutors" class="grid-4"></div>
      <div id="store-courses" class="grid-3 hidden"></div>
    </div>
    <!-- SETTINGS -->
    <div id="tab-settings" class="tab-content">
      <div class="page-header"><h1>Settings</h1><p>Manage your profile</p></div>
      <div class="card p-24" style="max-width:600px">
        <div class="text-center mb-20">
          <div class="avatar avatar-xl flex-center" id="settingsAvatar" style="margin:0 auto 12px"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
          <label class="btn btn-outline btn-sm" style="cursor:pointer"><i class="fa fa-camera"></i> Change Photo<input type="file" id="profilePicInput" accept="image/*" style="display:none" onchange="uploadProfilePic(this)"></label>
        </div>
        <form id="settingsForm">
          <div class="form-row">
            <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'])?>"></div>
            <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'])?>"></div>
          </div>
          <div class="form-group"><label class="form-label">Birthday</label><input type="date" name="birthday" class="form-control" value="<?=htmlspecialchars($user['birthday']??'')?>" disabled><p class="fs-xs text-muted mt-2">Birthday cannot be changed after registration.</p></div>
          <div class="form-group"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?=htmlspecialchars($user['address']??'')?>"></div>
          <div class="form-group"><label class="form-label">Phone</label><input type="text" class="form-control" value="<?=htmlspecialchars($user['phone'])?>" disabled></div>
          <div class="divider"></div>
          <p class="fw-700 mb-8">Change Password</p>
          <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" placeholder="Leave blank to keep"></div>
          <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control"></div>
          <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control"></div>
          <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-save"></i> Save Changes</button>
        </form>
      </div>
    </div>
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
    <div class="profile-info-row"><span class="profile-info-label">Enrolled Courses</span><span class="profile-info-value"><?=$stats['enrolled']?></span></div>
    <div class="mt-16">
      <button class="btn btn-outline btn-full btn-sm mb-8" onclick="switchTab('settings',null);closeProfile()"><i class="fa fa-cog"></i> Edit Profile</button>
      <a href="../api/auth/logout.php" class="btn btn-ghost btn-full btn-sm"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</div>

<!-- NOTIFICATION PANEL -->
<div class="notif-panel" id="notifPanel">
  <div class="notif-header"><h4>🔔 Notifications</h4><span class="auth-link fs-sm" onclick="markAllRead()">Mark all read</span></div>
  <div class="notif-list" id="notifList"></div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="paymentModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">💳 Submit Payment</span><span class="modal-close" onclick="closeModal('paymentModal')">×</span></div>
    <div class="modal-body">
      <div id="paymentCourseInfo" class="alert alert-info mb-16"></div>
      <form id="paymentForm">
        <input type="hidden" name="course_id" id="pay-course-id">
        <div class="form-group"><label class="form-label">Payment Reference *</label><input type="text" name="payment_reference" class="form-control" placeholder="e.g. TXN123456" required></div>
        <div class="form-group"><label class="form-label">Payment Slip *</label>
          <div class="file-upload-area" onclick="document.getElementById('slipFile').click()">
            <div class="upload-icon">📄</div><p>Click to upload slip</p><p class="fs-xs text-muted">JPG, PNG, PDF — Max 5MB</p>
            <input type="file" id="slipFile" name="payment_slip" accept="image/*,.pdf" style="display:none" onchange="previewSlip(this)">
          </div>
          <img id="slipPreview" style="display:none;max-height:150px;margin-top:10px;border-radius:8px">
        </div>
        <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea></div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-paper-plane"></i> Submit Payment</button>
      </form>
    </div>
  </div>
</div>

<!-- COURSE DETAIL MODAL -->
<div class="modal-overlay" id="courseModal" style="align-items:flex-start;padding-top:20px">
  <div class="modal" style="max-width:800px">
    <div class="modal-header"><span class="modal-title" id="courseModalTitle">Course</span><span class="modal-close" onclick="closeModal('courseModal')">×</span></div>
    <div class="modal-body" id="courseModalBody"></div>
  </div>
</div>

<!-- TUTOR MODAL -->
<div class="modal-overlay" id="tutorModal">
  <div class="modal" style="max-width:700px">
    <div class="modal-header"><span class="modal-title" id="tutorModalTitle">Tutor Profile</span><span class="modal-close" onclick="closeModal('tutorModal')">×</span></div>
    <div class="modal-body" id="tutorModalBody"></div>
  </div>
</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
  <div class="bottom-nav-inner">
    <button class="bottom-nav-item active" onclick="switchTab('home',this)"><span class="bn-icon">🏠</span><span>Home</span></button>
    <button class="bottom-nav-item" onclick="switchTab('courses',this)"><span class="bn-icon">📚</span><span>Courses</span></button>
    <button class="bottom-nav-item" onclick="switchTab('store',this)"><span class="bn-icon">🛒</span><span>Store</span></button>
    <button class="bottom-nav-item" onclick="switchTab('settings',this)"><span class="bn-icon">⚙️</span><span>Settings</span></button>
  </div>
</nav>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/student.js"></script>
</body>
</html>
