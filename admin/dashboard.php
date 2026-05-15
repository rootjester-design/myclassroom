<?php
require_once '../includes/helpers.php';
startSession();
$user = getAuthUser();
if (!$user || $user['role'] !== 'super_admin') {
    header('Location: /admin/login.php');
    exit;
}
$db = Database::getInstance();

$stats = [
  'students'    => $db->fetch("SELECT COUNT(*) c FROM students")['c'],
  'tutors'      => $db->fetch("SELECT COUNT(*) c FROM tutors")['c'],
  'courses'     => $db->fetch("SELECT COUNT(*) c FROM courses")['c'],
  'revenue'     => $db->fetch("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE status='approved'")['c'],
  'pending'     => $db->fetch("SELECT COUNT(*) c FROM payments WHERE status='pending'")['c'],
  'enrollments' => $db->fetch("SELECT COUNT(*) c FROM enrollments WHERE status='active'")['c'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Super Admin — MyClassroom</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="toast-container"></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-layout">

<aside class="sidebar admin-sidebar" id="sidebar">
  <div class="sidebar-logo" style="color:#e53935">My<span style="color:#fff">Classroom</span><br><span style="font-size:0.65rem;color:rgba(255,255,255,0.4);font-weight:400">Super Admin</span></div>
  <nav class="sidebar-nav">
    <button class="nav-item active" onclick="switchTab('overview',this)"><span class="nav-icon"><i class="fa fa-tachometer-alt"></i></span> Overview</button>
    <button class="nav-item" onclick="switchTab('tutors',this)"><span class="nav-icon"><i class="fa fa-chalkboard-teacher"></i></span> Tutors</button>
    <button class="nav-item" onclick="switchTab('students',this)"><span class="nav-icon"><i class="fa fa-users"></i></span> Students</button>
    <button class="nav-item" onclick="switchTab('courses',this)"><span class="nav-icon"><i class="fa fa-book"></i></span> Courses</button>
    <button class="nav-item" onclick="switchTab('payments',this)"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Payments <span id="pendingBadge" class="nav-badge" style="<?=$stats['pending']>0?'':'display:none'?>"><?=$stats['pending']?></span></button>
    <button class="nav-item" onclick="switchTab('logs',this)"><span class="nav-icon"><i class="fa fa-shield-alt"></i></span> Activity Logs</button>
    <button class="nav-item" onclick="switchTab('settings',this)"><span class="nav-icon"><i class="fa fa-cog"></i></span> Settings</button>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar avatar-sm" style="background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center">👑</div>
      <div class="sidebar-user-info"><div class="sidebar-user-name">Super Admin</div><div class="sidebar-user-role">Administrator</div></div>
      <a href="../api/auth/logout.php"><i class="fa fa-sign-out-alt" style="color:rgba(255,255,255,0.4)"></i></a>
    </div>
  </div>
</aside>

<div class="main-content">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="icon-btn" onclick="toggleSidebar()" id="menuBtn"><i class="fa fa-bars"></i></button>
      <span class="topbar-title" id="topbarTitle">Dashboard Overview</span>
    </div>
    <div class="topbar-right">
      <span class="badge badge-red">Super Admin</span>
      <a href="../api/auth/logout.php" class="btn btn-ghost btn-sm"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>

  <div class="page-content">

    <!-- OVERVIEW -->
    <div id="tab-overview" class="tab-content active">
      <div class="welcome-banner" style="background:linear-gradient(135deg,#1a1a1a,#2d2d2d)">
        <div><h2>Platform Overview 👑</h2><p>Full control of your MyClassroom platform</p></div>
        <span class="welcome-emoji">🏫</span>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon">👨‍🎓</div><div class="stat-value"><?=$stats['students']?></div><div class="stat-label">Total Students</div></div>
        <div class="stat-card"><div class="stat-icon">👨‍🏫</div><div class="stat-value"><?=$stats['tutors']?></div><div class="stat-label">Total Tutors</div></div>
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-value"><?=$stats['courses']?></div><div class="stat-label">Total Courses</div></div>
        <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value">Rs. <?=number_format($stats['revenue'],0)?></div><div class="stat-label">Total Revenue</div></div>
        <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?=$stats['pending']?></div><div class="stat-label">Pending Payments</div></div>
        <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-value"><?=$stats['enrollments']?></div><div class="stat-label">Active Enrollments</div></div>
      </div>
      <div class="grid-2">
        <div class="card p-20"><h3 class="section-title" style="font-size:1rem">⏳ Recent Pending Payments</h3><div id="admin-pending"></div></div>
        <div class="card p-20"><h3 class="section-title" style="font-size:1rem">👨‍🏫 Recent Tutors</h3><div id="admin-tutors-preview"></div></div>
      </div>
    </div>

    <!-- TUTORS -->
    <div id="tab-tutors" class="tab-content">
      <div class="flex-between mb-20 flex-wrap" style="gap:12px">
        <h2 class="section-title">Tutor Management</h2>
        <button class="btn btn-primary" onclick="openModal('createTutorModal')"><i class="fa fa-plus"></i> Create Tutor</button>
      </div>
      <div class="search-bar mb-16"><i class="fa fa-search search-icon"></i><input type="text" id="tutorSearch" placeholder="Search tutors..." oninput="filterTutors()"></div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>Tutor</th><th>Email</th><th>Phone</th><th>Subjects</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead><tbody id="tutors-tbody"></tbody></table></div></div>
    </div>

    <!-- STUDENTS -->
    <div id="tab-students" class="tab-content">
      <div class="page-header"><h1>Student Management</h1></div>
      <div class="search-bar mb-16"><i class="fa fa-search search-icon"></i><input type="text" id="studentSearch" placeholder="Search students..." oninput="filterStudents()"></div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>Student</th><th>Phone</th><th>Student ID</th><th>Joined</th><th>Courses</th><th>Status</th><th>Actions</th></tr></thead><tbody id="students-tbody"></tbody></table></div></div>
    </div>

    <!-- COURSES -->
    <div id="tab-courses" class="tab-content">
      <div class="page-header"><h1>Course Management</h1></div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>Course</th><th>Tutor</th><th>Price</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead><tbody id="courses-tbody"></tbody></table></div></div>
    </div>

    <!-- PAYMENTS -->
    <div id="tab-payments" class="tab-content">
      <div class="page-header"><h1>Payment Management</h1></div>
      <div class="nav-tabs mb-16">
        <button class="nav-tab active" onclick="adminPayFilter('pending',this)">⏳ Pending</button>
        <button class="nav-tab" onclick="adminPayFilter('approved',this)">✅ Approved</button>
        <button class="nav-tab" onclick="adminPayFilter('rejected',this)">❌ Rejected</button>
        <button class="nav-tab" onclick="adminPayFilter('all',this)">All</button>
      </div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>Student</th><th>Course</th><th>Tutor</th><th>Amount</th><th>Reference</th><th>Slip</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead><tbody id="admin-payments-tbody"></tbody></table></div></div>
    </div>

    <!-- LOGS -->
    <div id="tab-logs" class="tab-content">
      <div class="page-header"><h1>Activity Logs</h1></div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>User</th><th>Type</th><th>Action</th><th>Description</th><th>IP</th><th>Time</th></tr></thead><tbody id="logs-tbody"></tbody></table></div></div>
    </div>

    <!-- SETTINGS -->
    <div id="tab-settings" class="tab-content">
      <div class="page-header"><h1>Admin Settings</h1></div>
      <div class="card p-24" style="max-width:500px">
        <form id="adminSettingsForm">
          <p class="fw-700 mb-16">Change Admin Password</p>
          <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control"></div>
          <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control"></div>
          <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control"></div>
          <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-save"></i> Update Password</button>
        </form>
      </div>
    </div>

  </div>
</div>
</div>

<!-- CREATE TUTOR MODAL -->
<div class="modal-overlay" id="createTutorModal">
  <div class="modal" style="max-width:580px">
    <div class="modal-header"><span class="modal-title" id="tutorModalLabel">Create Tutor Account</span><span class="modal-close" onclick="closeModal('createTutorModal')">×</span></div>
    <div class="modal-body">
      <form id="createTutorForm">
        <input type="hidden" name="tutor_id" id="editTutorId">
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Phone *</label><input type="tel" name="phone" class="form-control" placeholder="07XXXXXXXX" required></div>
        </div>
        <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" id="tutorPass" class="form-control" placeholder="Min 8 characters" required></div>
        <div class="form-group"><label class="form-label">Subjects</label><input type="text" name="subjects" class="form-control" placeholder="e.g. Maths, Science"></div>
        <div class="form-group"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-control"></div>
        <div class="form-group" id="autoApproveWrap" style="display:flex;align-items:center;gap:10px">
          <input type="checkbox" name="is_approved" id="autoApprove" value="1" checked>
          <label for="autoApprove" class="form-label" style="margin:0">Auto-approve tutor</label>
        </div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-user-plus"></i> Create Account</button>
      </form>
    </div>
  </div>
</div>

<!-- VIEW PAYMENT MODAL -->
<div class="modal-overlay" id="adminPaymentModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Payment Details</span><span class="modal-close" onclick="closeModal('adminPaymentModal')">×</span></div>
    <div class="modal-body" id="adminPaymentBody"></div>
  </div>
</div>

<nav class="bottom-nav">
  <div class="bottom-nav-inner">
    <button class="bottom-nav-item active" onclick="switchTab('overview',this)"><span class="bn-icon">📊</span><span>Overview</span></button>
    <button class="bottom-nav-item" onclick="switchTab('tutors',this)"><span class="bn-icon">👨‍🏫</span><span>Tutors</span></button>
    <button class="bottom-nav-item" onclick="switchTab('students',this)"><span class="bn-icon">👨‍🎓</span><span>Students</span></button>
    <button class="bottom-nav-item" onclick="switchTab('payments',this)"><span class="bn-icon">💳</span><span>Payments</span></button>
  </div>
</nav>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
