<?php
require_once '../includes/helpers.php';
startSession();
$user = getAuthUser();
if (!$user || !in_array($user['role'], ['tutor', 'super_admin'])) {
    header('Location: /admin/login.php');
    exit;
}
$db = Database::getInstance();
$tid = $user['id'];

$stats = [
  'students'  => $db->fetch("SELECT COUNT(DISTINCT student_id) c FROM enrollments WHERE tutor_id=?",[$tid])['c'],
  'courses'   => $db->fetch("SELECT COUNT(*) c FROM courses WHERE tutor_id=?",[$tid])['c'],
  'pending'   => $db->fetch("SELECT COUNT(*) c FROM payments WHERE tutor_id=? AND status='pending'",[$tid])['c'],
  'revenue'   => $db->fetch("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE tutor_id=? AND status='approved'",[$tid])['c'],
];
$fullName = htmlspecialchars($user['first_name'].' '.$user['last_name']);
$initial  = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Tutor Dashboard — MyClassroom</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="toast-container"></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="dashboard-layout">

<aside class="sidebar admin-sidebar" id="sidebar">
  <div class="sidebar-logo" style="color:#e53935">My<span style="color:#fff">Classroom</span> <span style="font-size:0.7rem;opacity:0.6;font-weight:400">Tutor</span></div>
  <nav class="sidebar-nav">
    <button class="nav-item active" onclick="switchTab('overview',this)"><span class="nav-icon"><i class="fa fa-tachometer-alt"></i></span> Overview</button>
    <button class="nav-item" onclick="switchTab('courses',this)"><span class="nav-icon"><i class="fa fa-book"></i></span> Courses</button>
    <button class="nav-item" onclick="switchTab('students',this)"><span class="nav-icon"><i class="fa fa-users"></i></span> Students</button>
    <button class="nav-item" onclick="switchTab('payments',this)"><span class="nav-icon"><i class="fa fa-credit-card"></i></span> Payments <span id="pendingBadge" class="nav-badge" style="<?=$stats['pending']>0?'':'display:none'?>"><?=$stats['pending']?></span></button>
    <button class="nav-item" onclick="switchTab('live',this)"><span class="nav-icon"><i class="fa fa-video"></i></span> Live Classes</button>
    <button class="nav-item" onclick="switchTab('recordings',this)"><span class="nav-icon"><i class="fa fa-play-circle"></i></span> Recordings</button>
    <button class="nav-item" onclick="switchTab('materials',this)"><span class="nav-icon"><i class="fa fa-folder"></i></span> Materials</button>
    <button class="nav-item" onclick="switchTab('announcements',this)"><span class="nav-icon"><i class="fa fa-bullhorn"></i></span> Announcements</button>
    <button class="nav-item" onclick="switchTab('profile',this)"><span class="nav-icon"><i class="fa fa-user-edit"></i></span> My Profile</button>
    <button class="nav-item" onclick="switchTab('settings',this)"><span class="nav-icon"><i class="fa fa-cog"></i></span> Settings</button>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar avatar-sm" style="background:var(--primary);color:#fff"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
      <div class="sidebar-user-info"><div class="sidebar-user-name"><?=$fullName?></div><div class="sidebar-user-role">Tutor</div></div>
      <a href="../api/auth/logout.php" title="Logout"><i class="fa fa-sign-out-alt" style="color:rgba(255,255,255,0.4)"></i></a>
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
      <span style="font-size:0.85rem;color:var(--gray-500)">Welcome, <?=htmlspecialchars($user['first_name'])?></span>
      <a href="../api/auth/logout.php" class="btn btn-ghost btn-sm"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>

  <div class="page-content">

    <!-- OVERVIEW -->
    <div id="tab-overview" class="tab-content active">
      <div class="welcome-banner">
        <div><h2>Tutor Dashboard 🎓</h2><p>Manage your courses, students, and content from here.</p></div>
        <span class="welcome-emoji">👨‍🏫</span>
      </div>
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon">👨‍🎓</div><div class="stat-value"><?=$stats['students']?></div><div class="stat-label">Total Students</div></div>
        <div class="stat-card"><div class="stat-icon">📚</div><div class="stat-value"><?=$stats['courses']?></div><div class="stat-label">My Courses</div></div>
        <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-value"><?=$stats['pending']?></div><div class="stat-label">Pending Payments</div></div>
        <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value">Rs. <?=number_format($stats['revenue'],0)?></div><div class="stat-label">Total Revenue</div></div>
      </div>
      <div class="grid-2">
        <div class="card p-20"><h3 class="section-title" style="font-size:1rem">⏳ Pending Payments</h3><div id="pending-payments-list"></div></div>
        <div class="card p-20"><h3 class="section-title" style="font-size:1rem">👨‍🎓 Recent Students</h3><div id="recent-students-list"></div></div>
      </div>
    </div>

    <!-- COURSES -->
    <div id="tab-courses" class="tab-content">
      <div class="flex-between mb-20">
        <div><h2 class="section-title">My Courses</h2></div>
        <button class="btn btn-primary" onclick="openModal('addCourseModal')"><i class="fa fa-plus"></i> Add Course</button>
      </div>
      <div id="courses-grid" class="grid-3"></div>
    </div>

    <!-- STUDENTS -->
    <div id="tab-students" class="tab-content">
      <div class="page-header"><h1>My Students</h1></div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>Student</th><th>Phone</th><th>Course</th><th>Enrolled</th><th>Progress</th></tr></thead><tbody id="students-tbody"></tbody></table></div></div>
    </div>

    <!-- PAYMENTS -->
    <div id="tab-payments" class="tab-content">
      <div class="page-header"><h1>Payments</h1></div>
      <div class="nav-tabs mb-16">
        <button class="nav-tab active" onclick="payFilter('pending',this)">⏳ Pending</button>
        <button class="nav-tab" onclick="payFilter('approved',this)">✅ Approved</button>
        <button class="nav-tab" onclick="payFilter('rejected',this)">❌ Rejected</button>
        <button class="nav-tab" onclick="payFilter('all',this)">All</button>
      </div>
      <div class="card"><div class="table-wrap"><table><thead><tr><th>Student</th><th>Course</th><th>Amount</th><th>Reference</th><th>Slip</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody id="payments-tbody"></tbody></table></div></div>
    </div>

    <!-- LIVE CLASSES -->
    <div id="tab-live" class="tab-content">
      <div class="flex-between mb-20"><h2 class="section-title">Live Classes</h2><button class="btn btn-primary" onclick="openModal('addZoomModal')"><i class="fa fa-plus"></i> Add Zoom Link</button></div>
      <div id="zoom-list" class="grid-2"></div>
    </div>

    <!-- RECORDINGS -->
    <div id="tab-recordings" class="tab-content">
      <div class="flex-between mb-20"><h2 class="section-title">Recordings</h2><button class="btn btn-primary" onclick="openModal('addRecordingModal')"><i class="fa fa-plus"></i> Add Recording</button></div>
      <div id="recordings-list" class="grid-3"></div>
    </div>

    <!-- MATERIALS -->
    <div id="tab-materials" class="tab-content">
      <div class="flex-between mb-20"><h2 class="section-title">Course Materials</h2><button class="btn btn-primary" onclick="openModal('addMaterialModal')"><i class="fa fa-plus"></i> Upload Material</button></div>
      <div id="materials-list"></div>
    </div>

    <!-- ANNOUNCEMENTS -->
    <div id="tab-announcements" class="tab-content">
      <div class="flex-between mb-20"><h2 class="section-title">Announcements</h2><button class="btn btn-primary" onclick="openModal('addAnnouncementModal')"><i class="fa fa-plus"></i> Post Announcement</button></div>
      <div id="announcements-list"></div>
    </div>

    <!-- PUBLIC PROFILE -->
    <div id="tab-profile" class="tab-content">
      <div class="page-header"><h1>My Public Profile</h1><p>This is what students see in the Store</p></div>
      <div class="card p-24" style="max-width:700px">
        <div style="position:relative;height:140px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:12px;margin-bottom:60px">
          <label style="position:absolute;bottom:8px;right:8px;cursor:pointer" class="btn btn-sm" style="background:rgba(255,255,255,0.2);color:#fff">
            <i class="fa fa-camera"></i> Banner<input type="file" id="bannerInput" accept="image/*" style="display:none" onchange="uploadBanner(this)">
          </label>
          <div style="position:absolute;bottom:-44px;left:24px">
            <div style="position:relative;display:inline-block">
              <div class="avatar avatar-xl" id="tutorAvatarPreview" style="border:4px solid #fff;display:flex;align-items:center;justify-content:center;background:#fff;color:var(--primary);font-size:1.8rem"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
              <label style="position:absolute;bottom:0;right:0;cursor:pointer;width:28px;height:28px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem">
                <i class="fa fa-camera"></i><input type="file" id="avatarInput" accept="image/*" style="display:none" onchange="uploadTutorAvatar(this)">
              </label>
            </div>
          </div>
        </div>
        <form id="profileForm">
          <div class="form-group"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-control" value="<?=htmlspecialchars($user['display_name']??$fullName)?>"></div>
          <div class="form-group"><label class="form-label">About / Description</label><textarea name="description" class="form-control" rows="4" placeholder="Tell students about yourself..."><?=htmlspecialchars($user['description']??'')?></textarea></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Subjects</label><input type="text" name="subjects" class="form-control" value="<?=htmlspecialchars($user['subjects']??'')?>" placeholder="e.g. Maths, Science"></div>
            <div class="form-group"><label class="form-label">Experience</label><input type="text" name="experience" class="form-control" value="<?=htmlspecialchars($user['experience']??'')?>" placeholder="e.g. 5 years"></div>
          </div>
          <div class="form-group"><label class="form-label">Qualifications</label><textarea name="qualifications" class="form-control" rows="2" placeholder="BSc, MSc, etc."><?=htmlspecialchars($user['qualifications']??'')?></textarea></div>
          <div class="form-group"><label class="form-label">Contact Info</label><input type="text" name="contact_info" class="form-control" value="<?=htmlspecialchars($user['contact_info']??'')?>" placeholder="Email or phone"></div>
          <div class="form-group"><label class="form-label">Social Links (JSON)</label><input type="text" name="social_links" class="form-control" value="<?=htmlspecialchars($user['social_links']??'')?>" placeholder='{"facebook":"url","youtube":"url"}'></div>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Profile</button>
        </form>
      </div>
    </div>

    <!-- SETTINGS -->
    <div id="tab-settings" class="tab-content">
      <div class="page-header"><h1>Account Settings</h1></div>
      <div class="card p-24" style="max-width:500px">
        <form id="settingsForm">
          <div class="form-row">
            <div class="form-group"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'])?>"></div>
            <div class="form-group"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'])?>"></div>
          </div>
          <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?=htmlspecialchars($user['email']??'')?>"></div>
          <div class="divider"></div>
          <p class="fw-700 mb-12">Change Password</p>
          <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control"></div>
          <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control"></div>
          <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control"></div>
          <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-save"></i> Save Settings</button>
        </form>
      </div>
    </div>
  </div>
</div>
</div>

<!-- ADD COURSE MODAL -->
<div class="modal-overlay" id="addCourseModal">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><span class="modal-title" id="courseModalLabel">Add Course</span><span class="modal-close" onclick="closeModal('addCourseModal')">×</span></div>
    <div class="modal-body">
      <form id="courseForm">
        <input type="hidden" name="course_id" id="editCourseId">
        <div class="form-group"><label class="form-label">Course Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Price (Rs.)</label><input type="number" name="price" class="form-control" value="0" min="0"></div>
          <div class="form-group"><label class="form-label">Monthly Fee (Rs.)</label><input type="number" name="monthly_fee" class="form-control" value="0" min="0"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" placeholder="e.g. 3 months"></div>
          <div class="form-group"><label class="form-label">Level</label><select name="level" class="form-control"><option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div>
        </div>
        <div class="form-group"><label class="form-label">Thumbnail</label><input type="file" name="thumbnail" class="form-control" accept="image/*"></div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-save"></i> Save Course</button>
      </form>
    </div>
  </div>
</div>

<!-- ADD ZOOM MODAL -->
<div class="modal-overlay" id="addZoomModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Add Live Class</span><span class="modal-close" onclick="closeModal('addZoomModal')">×</span></div>
    <div class="modal-body">
      <form id="zoomForm">
        <div class="form-group"><label class="form-label">Course *</label><select name="course_id" class="form-control" id="zoomCourseSelect"></select></div>
        <div class="form-group"><label class="form-label">Class Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Zoom URL *</label><input type="url" name="zoom_url" class="form-control" placeholder="https://zoom.us/j/..." required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Meeting ID</label><input type="text" name="meeting_id" class="form-control"></div>
          <div class="form-group"><label class="form-label">Passcode</label><input type="text" name="passcode" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Scheduled At</label><input type="datetime-local" name="scheduled_at" class="form-control"></div>
          <div class="form-group"><label class="form-label">Duration (minutes)</label><input type="number" name="duration_minutes" class="form-control" value="60"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-save"></i> Add Class</button>
      </form>
    </div>
  </div>
</div>

<!-- ADD RECORDING MODAL -->
<div class="modal-overlay" id="addRecordingModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Add Recording</span><span class="modal-close" onclick="closeModal('addRecordingModal')">×</span></div>
    <div class="modal-body">
      <form id="recordingForm">
        <div class="form-group"><label class="form-label">Course *</label><select name="course_id" class="form-control" id="recCourseSelect"></select></div>
        <div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Video URL *</label><input type="url" name="video_url" class="form-control" placeholder="YouTube or Google Drive link" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" placeholder="e.g. 1h 30m"></div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-save"></i> Add Recording</button>
      </form>
    </div>
  </div>
</div>

<!-- ADD MATERIAL MODAL -->
<div class="modal-overlay" id="addMaterialModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Upload Material</span><span class="modal-close" onclick="closeModal('addMaterialModal')">×</span></div>
    <div class="modal-body">
      <form id="materialForm" enctype="multipart/form-data">
        <div class="form-group"><label class="form-label">Course *</label><select name="course_id" class="form-control" id="matCourseSelect"></select></div>
        <div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        <div class="form-group"><label class="form-label">File *</label><input type="file" name="file" class="form-control" required></div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-upload"></i> Upload</button>
      </form>
    </div>
  </div>
</div>

<!-- ADD ANNOUNCEMENT MODAL -->
<div class="modal-overlay" id="addAnnouncementModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Post Announcement</span><span class="modal-close" onclick="closeModal('addAnnouncementModal')">×</span></div>
    <div class="modal-body">
      <form id="announcementForm">
        <div class="form-group"><label class="form-label">Course *</label><select name="course_id" class="form-control" id="annCourseSelect"></select></div>
        <div class="form-group"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Content *</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
        <button type="submit" class="btn btn-primary btn-full"><i class="fa fa-bullhorn"></i> Post</button>
      </form>
    </div>
  </div>
</div>

<!-- REVIEW PAYMENT MODAL -->
<div class="modal-overlay" id="reviewPaymentModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Review Payment</span><span class="modal-close" onclick="closeModal('reviewPaymentModal')">×</span></div>
    <div class="modal-body" id="reviewPaymentBody"></div>
  </div>
</div>

<nav class="bottom-nav">
  <div class="bottom-nav-inner">
    <button class="bottom-nav-item active" onclick="switchTab('overview',this)"><span class="bn-icon">📊</span><span>Overview</span></button>
    <button class="bottom-nav-item" onclick="switchTab('courses',this)"><span class="bn-icon">📚</span><span>Courses</span></button>
    <button class="bottom-nav-item" onclick="switchTab('payments',this)"><span class="bn-icon">💳</span><span>Payments</span></button>
    <button class="bottom-nav-item" onclick="switchTab('profile',this)"><span class="bn-icon">👤</span><span>Profile</span></button>
  </div>
</nav>

<script src="../assets/js/main.js"></script>
<script src="../assets/js/tutor.js"></script>
</body>
</html>
