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
<title>Tutor Profile — MyClassroom</title>
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
    <button class="nav-item" onclick="window.location.href='dashboard.php?tab=courses'"><span class="nav-icon"><i class="fa fa-book"></i></span> My Courses</button>
    <button class="nav-item" onclick="window.location.href='dashboard.php?tab=store'"><span class="nav-icon"><i class="fa fa-store"></i></span> Store</button>
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
      <button class="icon-btn" onclick="window.location.href='dashboard.php?tab=store'" title="Back to store"><i class="fa fa-arrow-left"></i></button>
      <button class="icon-btn" onclick="toggleSidebar()" id="menuBtn"><i class="fa fa-bars"></i></button>
      <span class="topbar-title" id="topbarTitle">Tutor Profile</span>
    </div>
    <div class="topbar-right">
      <button class="icon-btn" id="notifBtn" onclick="toggleNotif()"><i class="fa fa-bell"></i><?php if($unread>0): ?><span class="notif-dot" id="notifDot"></span><?php endif; ?></button>
      <button class="icon-btn" id="profileBtn" onclick="toggleProfile()">
        <div class="avatar avatar-sm"><?php if($user['profile_image']): ?><img src="<?=htmlspecialchars($user['profile_image'])?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover"><?php else: echo $initial; endif; ?></div>
      </button>
    </div>
  </header>
  <div class="page-content" id="tutorPageContent">
    <div id="tutorPageBody"><div class="flex-center" style="padding:40px"><div class="spinner"></div></div></div>
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

<script src="../assets/js/main.js"></script>
<script src="../assets/js/student.js"></script>
<script>
async function loadTutorProfilePage() {
  const container = document.getElementById('tutorPageBody');
  if (!container) return;
  const params = new URLSearchParams(window.location.search);
  const tutorId = parseInt(params.get('id') || '', 10);
  if (!tutorId) {
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><p>Tutor not found.</p></div>';
    return;
  }
  container.innerHTML = '<div class="flex-center" style="padding:40px"><div class="spinner"></div></div>';
  try {
    const res = await fetch(`../api/student/tutor-profile.php?id=${tutorId}`);
    const d = await res.json();
    if(!d.success){container.innerHTML='<div class="alert alert-error">Failed to load profile</div>';return;}
    const t = d.tutor;
    const name = t.display_name||t.first_name+' '+t.last_name;
    document.getElementById('topbarTitle').textContent = name;
    container.innerHTML = `
      <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:12px;width:100%;aspect-ratio:5/1;margin-bottom:60px;position:relative;${t.banner_image?`background-image:url(${t.banner_image});background-size:cover`:''};overflow:visible">
        <div style="position:absolute;bottom:-50px;left:24px">${t.profile_image?`<img src="${t.profile_image}" style="width:100px;height:100px;border-radius:50%;border:4px solid #fff;object-fit:cover">`:`<div style="width:100px;height:100px;border-radius:50%;border:4px solid #fff;background:#fff;display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--primary);font-weight:800">${name.substring(0,2).toUpperCase()}</div>`}</div>
      </div>
      <div class="mb-24"><h1 style="font-size:1.8rem;font-weight:800;margin-top:20px">${name}</h1>
      ${t.subjects?`<div class="chip mt-12" style="cursor:default">${t.subjects}</div>`:''}
      </div>
      ${t.social_links?(() => {
        try {
          const links = JSON.parse(t.social_links);
          if (Object.keys(links).length === 0) return '';
          return '<div class="card p-20 mb-20"><h3 class="fw-700 mb-12">Social Links</h3><div style="display:flex;gap:12px">' + 
            Object.entries(links).map(([platform, url]) => `<a href="${url}" target="_blank" class="btn btn-outline btn-sm" style="text-transform:capitalize"><i class="fab fa-${platform.toLowerCase()}"></i> ${platform}</a>`).join('') + 
            '</div></div>';
        } catch(e) { return ''; }
      })():''}
      ${t.description?`<div class="card p-20 mb-20"><h3 class="fw-700 mb-12">About</h3><p>${t.description}</p></div>`:''}
      ${t.experience?`<div class="card p-20 mb-20"><h3 class="fw-700 mb-12">Experience</h3><p>${t.experience}</p></div>`:''}
      ${t.qualifications?`<div class="card p-20 mb-20"><h3 class="fw-700 mb-12">Qualifications</h3><p>${t.qualifications}</p></div>`:''}
      ${t.contact_info?`<div class="card p-20 mb-20"><h3 class="fw-700 mb-12">Contact</h3><p>${t.contact_info}</p></div>`:''}
      <h2 class="section-title">Courses by ${name}</h2>
      <div class="grid-3">${(d.courses||[]).map(c=>`<div class="course-card"><div class="course-card-thumb">${c.thumbnail?`<img src="${c.thumbnail}" style="width:100%;height:100%;object-fit:cover">`:'📚'}</div><div class="course-card-body"><div class="course-card-title">${c.title}</div><div class="course-card-price">Rs. ${parseFloat(c.price||0).toLocaleString()}</div></div><div class="course-card-footer">${c.enrolled?`<span class="badge badge-green btn-full text-center">✅ Enrolled</span>`:`<button class="btn btn-primary btn-sm btn-full" onclick="openPayment(${c.id},'${c.title.replace(/'/g, "\\'")}',${c.price},'${c.thumbnail||''}','${(c.description||'').replace(/'/g, "\\'")}')">Enroll</button>`}</div></div>`).join('')||'<p class="text-muted">No courses yet</p>'}</div>`;
  } catch(e) {
    container.innerHTML = '<div class="alert alert-error">Failed to load tutor profile.</div>';
  }
}
document.addEventListener('DOMContentLoaded', loadTutorProfilePage);
</script>
</body>
</html>
