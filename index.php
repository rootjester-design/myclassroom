<?php
require_once 'includes/helpers.php';

if (isLoggedIn()) {
    $user = getAuthUser();
    switch ($user['role']) {
        case 'student':    redirect('/student/dashboard.php');
        case 'tutor':      redirect('/tutor/dashboard.php');
        case 'super_admin':redirect('/admin/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>MyClassroom — Premium Learning Management System</title>
<meta name="description" content="MyClassroom is a premium online learning platform connecting students with expert tutors in Sri Lanka.">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:#fff;color:#111;overflow-x:hidden;}
.hero{min-height:100vh;background:linear-gradient(135deg,#fff5f5 0%,#fff 60%,#fff0f0 100%);display:flex;flex-direction:column;}
nav{display:flex;align-items:center;justify-content:space-between;padding:20px 60px;position:sticky;top:0;background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);border-bottom:1px solid rgba(229,57,53,0.1);z-index:100;}
.nav-logo{font-size:1.5rem;font-weight:800;color:#e53935;letter-spacing:-0.5px;}
.nav-logo span{color:#111;}
.nav-links{display:flex;gap:28px;align-items:center;}
.nav-links a{font-weight:500;font-size:0.92rem;color:#424242;transition:color 0.2s;}
.nav-links a:hover{color:#e53935;}
.hero-body{flex:1;display:flex;align-items:center;justify-content:center;padding:80px 60px;gap:60px;max-width:1200px;margin:0 auto;width:100%;}
.hero-text{flex:1;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(229,57,53,0.08);color:#e53935;border-radius:100px;font-size:0.82rem;font-weight:700;margin-bottom:24px;}
.hero-title{font-size:3.2rem;font-weight:900;line-height:1.1;letter-spacing:-1.5px;margin-bottom:20px;}
.hero-title span{color:#e53935;}
.hero-sub{font-size:1.1rem;color:#757575;max-width:480px;line-height:1.7;margin-bottom:36px;}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap;}
.hero-visual{flex:1;display:flex;justify-content:center;align-items:center;}
.hero-card{background:rgba(255,255,255,0.9);backdrop-filter:blur(20px);border:1px solid rgba(229,57,53,0.15);border-radius:24px;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,0.12);max-width:380px;width:100%;}
.feature-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px;}
.feature-item{background:rgba(229,57,53,0.05);border-radius:12px;padding:16px;text-align:center;}
.feature-icon{font-size:2rem;margin-bottom:8px;}
.feature-title{font-weight:700;font-size:0.85rem;}
.feature-desc{font-size:0.75rem;color:#757575;margin-top:4px;}
.stats-bar{background:#111;color:#fff;padding:28px 60px;display:flex;justify-content:center;gap:60px;flex-wrap:wrap;}
.stat-item{text-align:center;}
.stat-num{font-size:2rem;font-weight:800;color:#e53935;}
.stat-lbl{font-size:0.85rem;opacity:0.6;margin-top:4px;}
.features-section{padding:80px 60px;max-width:1200px;margin:0 auto;}
.section-header{text-align:center;margin-bottom:48px;}
.section-header h2{font-size:2.2rem;font-weight:800;letter-spacing:-0.5px;}
.section-header p{color:#757575;margin-top:12px;font-size:1rem;}
.features-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
.feat-card{border:1px solid rgba(229,57,53,0.1);border-radius:20px;padding:28px;transition:all 0.3s;}
.feat-card:hover{box-shadow:0 8px 40px rgba(0,0,0,0.1);transform:translateY(-4px);border-color:rgba(229,57,53,0.3);}
.feat-card-icon{width:52px;height:52px;background:rgba(229,57,53,0.1);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:16px;}
.feat-card h3{font-weight:700;margin-bottom:8px;}
.feat-card p{color:#757575;font-size:0.9rem;line-height:1.6;}
.cta-section{background:linear-gradient(135deg,#e53935,#b71c1c);color:#fff;padding:80px 60px;text-align:center;}
.cta-section h2{font-size:2.2rem;font-weight:800;margin-bottom:16px;}
.cta-section p{opacity:0.85;max-width:500px;margin:0 auto 32px;}
footer{background:#111;color:rgba(255,255,255,0.6);padding:32px 60px;text-align:center;font-size:0.88rem;}
footer a{color:rgba(255,255,255,0.4);margin:0 12px;}
@media(max-width:900px){.hero-body{flex-direction:column;padding:40px 24px;gap:32px;}.hero-visual{display:none;}.features-cards{grid-template-columns:1fr;}.stats-bar{gap:32px;}.nav{padding:16px 24px;}.features-section{padding:48px 24px;}.cta-section{padding:48px 24px;}}
</style>
</head>
<body>
<div class="hero">
  <nav>
    <div class="nav-logo">My<span>Classroom</span></div>
    <div class="nav-links">
      <a href="#features">Features</a>
      <a href="auth/login.php" class="btn btn-outline btn-sm">Login</a>
      <a href="auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
    </div>
  </nav>

  <div class="hero-body">
    <div class="hero-text">
      <div class="hero-badge">🇱🇰 Sri Lanka's #1 LMS Platform</div>
      <h1 class="hero-title">Learn From The<br><span>Best Tutors</span><br>Anytime</h1>
      <p class="hero-sub">MyClassroom connects ambitious students with certified expert tutors. Access live classes, recordings, and course materials all in one place.</p>
      <div class="hero-btns">
        <a href="auth/register.php" class="btn btn-primary btn-lg"><i class="fa fa-rocket"></i> Start Learning Free</a>
        <a href="admin/login.php" class="btn btn-ghost btn-lg"><i class="fa fa-chalkboard-teacher"></i> Tutor Login</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-card">
        <div style="font-weight:800;font-size:1.1rem;margin-bottom:4px">📊 Platform Stats</div>
        <div style="font-size:0.85rem;color:#757575;margin-bottom:20px">Live data from MyClassroom</div>
        <?php
        $db = Database::getInstance();
        $sc = $db->fetch("SELECT COUNT(*) c FROM students")['c'];
        $tc = $db->fetch("SELECT COUNT(*) c FROM tutors WHERE is_approved=1")['c'];
        $cc = $db->fetch("SELECT COUNT(*) c FROM courses WHERE is_active=1")['c'];
        ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
          <div style="background:rgba(229,57,53,0.08);border-radius:12px;padding:14px;text-align:center"><div style="font-size:1.6rem;font-weight:800;color:#e53935"><?=$sc?></div><div style="font-size:0.78rem;color:#757575">Students</div></div>
          <div style="background:rgba(229,57,53,0.08);border-radius:12px;padding:14px;text-align:center"><div style="font-size:1.6rem;font-weight:800;color:#e53935"><?=$tc?></div><div style="font-size:0.78rem;color:#757575">Tutors</div></div>
          <div style="background:rgba(229,57,53,0.08);border-radius:12px;padding:14px;text-align:center;grid-column:1/-1"><div style="font-size:1.6rem;font-weight:800;color:#e53935"><?=$cc?></div><div style="font-size:0.78rem;color:#757575">Active Courses</div></div>
        </div>
        <a href="auth/register.php" class="btn btn-primary btn-full"><i class="fa fa-arrow-right"></i> Join Now — It's Free</a>
      </div>
    </div>
  </div>
</div>

<div class="stats-bar">
  <div class="stat-item"><div class="stat-num">100%</div><div class="stat-lbl">Verified Tutors</div></div>
  <div class="stat-item"><div class="stat-num">24/7</div><div class="stat-lbl">Access Anytime</div></div>
  <div class="stat-item"><div class="stat-num">🔒</div><div class="stat-lbl">Secure Platform</div></div>
  <div class="stat-item"><div class="stat-num">📱</div><div class="stat-lbl">Mobile Friendly</div></div>
</div>

<div class="features-section" id="features">
  <div class="section-header">
    <h2>Everything You Need to Learn</h2>
    <p>A complete learning ecosystem built for Sri Lankan students and tutors</p>
  </div>
  <div class="features-cards">
    <div class="feat-card"><div class="feat-card-icon">🎬</div><h3>Live & Recorded Classes</h3><p>Join Zoom live classes and access all previous recordings anytime you want.</p></div>
    <div class="feat-card"><div class="feat-card-icon">📁</div><h3>Course Materials</h3><p>Download PDFs, notes, and assignments shared by your tutor directly.</p></div>
    <div class="feat-card"><div class="feat-card-icon">💳</div><h3>Simple Payments</h3><p>Upload your bank slip and get enrolled after manual approval by the tutor.</p></div>
    <div class="feat-card"><div class="feat-card-icon">📢</div><h3>Announcements</h3><p>Stay updated with class announcements and important notices in real time.</p></div>
    <div class="feat-card"><div class="feat-card-icon">📓</div><h3>Personal Notes</h3><p>Take and save personal notes for each course directly inside the platform.</p></div>
    <div class="feat-card"><div class="feat-card-icon">📊</div><h3>Progress Tracking</h3><p>Track your learning progress and stay motivated with visual progress bars.</p></div>
  </div>
</div>

<div class="cta-section">
  <h2>Ready to Start Learning?</h2>
  <p>Join thousands of students already learning on MyClassroom. Register with your phone number in seconds.</p>
  <a href="auth/register.php" class="btn btn-full" style="background:#fff;color:#e53935;font-weight:700;padding:16px 40px;border-radius:12px;display:inline-flex;align-items:center;gap:10px;font-size:1rem;max-width:300px"><i class="fa fa-user-plus"></i> Create Free Account</a>
</div>

<footer>
  <div>© <?=date('Y')?> MyClassroom. All rights reserved.</div>
  <div style="margin-top:12px"><a href="auth/login.php">Student Login</a><a href="admin/login.php">Admin/Tutor Login</a></div>
</footer>
</body>
</html>
