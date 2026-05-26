<?php
require_once __DIR__ . '/../app/includes/helpers.php';

if (isLoggedIn()) {
    $user = getAuthUser();
    switch ($user['role']) {
        case 'student':    redirect('/student/dashboard.php');
        case 'tutor':      redirect('/tutor/dashboard.php');
        case 'super_admin':redirect('/admin/dashboard.php');
    }
}

$db = Database::getInstance();
$sc = $db->fetch("SELECT COUNT(*) c FROM students")['c'];
$tc = $db->fetch("SELECT COUNT(*) c FROM tutors WHERE is_approved=1")['c'];
$cc = $db->fetch("SELECT COUNT(*) c FROM courses WHERE is_active=1")['c'];
$tutors = $db->fetchAll("SELECT id, first_name, last_name, display_name, subjects, profile_image FROM tutors WHERE is_approved=1 ORDER BY created_at DESC LIMIT 10");
$reviews = $db->fetchAll("SELECT r.rating, r.review, r.created_at, s.first_name, s.last_name, s.profile_image FROM site_reviews r JOIN students s ON r.student_id = s.id ORDER BY r.id DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>MyClassroom — Premium Learning Management System</title>
<meta name="description" content="MyClassroom is a premium online learning platform connecting students with expert tutors in Sri Lanka.">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?=APP_URL?>/">
<meta property="og:title" content="MyClassroom — Premium Learning Management System">
<meta property="og:description" content="Connect with top educators and discover premium courses on MyClassroom. Sri Lanka's leading online education platform.">
<meta property="og:image" content="<?=APP_URL?>/assets/img/logo-1.png">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?=APP_URL?>/">
<meta name="twitter:title" content="MyClassroom — Premium Learning Management System">
<meta name="twitter:description" content="Connect with top educators and discover premium courses on MyClassroom.">
<meta name="twitter:image" content="<?=APP_URL?>/assets/img/logo-1.png">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="assets/img/logo-2.png">
<style>
:root {
  --primary: #e53935;
  --primary-glow: rgba(229, 57, 53, 0.4);
  --bg-color: #fbfbfb;
  --text-main: #111;
  --text-muted: #64748b;
  --glass-bg: rgba(255, 255, 255, 0.65);
  --glass-border: rgba(255, 255, 255, 0.8);
  --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
}

* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
body { background: var(--bg-color); color: var(--text-main); overflow-x: hidden; position: relative; }

/* Animated Blobs */
.bg-blobs { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; overflow: hidden; pointer-events: none; }
.blob { position: absolute; border-radius: 50%; filter: blur(90px); opacity: 0.6; animation: float 20s infinite alternate ease-in-out; }
.blob-1 { width: 600px; height: 600px; background: rgba(229, 57, 53, 0.15); top: -100px; left: -100px; }
.blob-2 { width: 500px; height: 500px; background: rgba(0, 0, 0, 0.04); bottom: -100px; right: -50px; animation-delay: -5s; }
.blob-3 { width: 400px; height: 400px; background: rgba(229, 57, 53, 0.08); top: 40%; left: 60%; animation-duration: 25s; }
@keyframes float {
  0% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(50px, 30px) scale(1.05); }
  100% { transform: translate(-30px, 50px) scale(0.95); }
}

/* Glass UI Elements */
.glass {
  background: var(--glass-bg);
  backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
  border: 1px solid var(--glass-border);
  box-shadow: var(--glass-shadow);
}

/* Nav */
nav {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 40px; position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  background: rgba(255, 255, 255, 0.75); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.6);
}
.nav-links { display: flex; gap: 24px; align-items: center; }
.nav-links a.nav-link { color: var(--text-main); font-weight: 500; text-decoration: none; transition: color 0.3s; }
.nav-links a.nav-link:hover { color: var(--primary); }

/* Buttons */
.btn {
  padding: 12px 24px; border-radius: 12px; font-weight: 600; text-decoration: none;
  display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.3s; border: none; font-size: 0.95rem;
}
.btn-primary { background: var(--primary); color: #fff; box-shadow: 0 6px 20px var(--primary-glow); }
.btn-primary:hover { background: #d32f2f; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(229, 57, 53, 0.5); }
.btn-glass { background: rgba(255, 255, 255, 0.8); color: var(--text-main); border: 1px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.btn-glass:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }

/* Hero */
.hero { min-height: 100vh; padding: 140px 40px 60px; display: flex; align-items: center; justify-content: center; max-width: 1400px; margin: 0 auto; }
.hero-content { display: flex; align-items: center; gap: 60px; width: 100%; flex-wrap: wrap; }
.hero-text { flex: 1; min-width: 320px; position: relative; z-index: 2; }
.hero-badge {
  display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 100px; font-size: 0.85rem; font-weight: 700;
  color: var(--primary); background: rgba(229, 57, 53, 0.08); margin-bottom: 24px; border: 1px solid rgba(229, 57, 53, 0.15);
}
.hero-title { font-size: 4.2rem; font-weight: 900; line-height: 1.1; letter-spacing: -2px; margin-bottom: 24px; }
.hero-title span { background: linear-gradient(135deg, var(--primary), #ff8a80); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.hero-sub { font-size: 1.15rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 40px; max-width: 540px; }
.hero-btns { display: flex; gap: 16px; flex-wrap: wrap; }

.hero-visual { flex: 1; min-width: 320px; position: relative; display: flex; justify-content: center; align-items: center; height: 500px; }
.hero-dashboard {
  width: 100%; max-width: 500px; height: 400px; border-radius: 24px; position: relative;
  background: rgba(255,255,255,0.4); border: 1px solid rgba(255,255,255,0.7); box-shadow: 0 20px 60px rgba(0,0,0,0.08);
  display: flex; flex-direction: column; padding: 20px; overflow: hidden; backdrop-filter: blur(10px);
}
.dash-header { display: flex; gap: 10px; margin-bottom: 20px; }
.dash-circle { width: 12px; height: 12px; border-radius: 50%; background: #e0e0e0; }
.dash-circle:nth-child(1) { background: #ff5f56; } .dash-circle:nth-child(2) { background: #ffbd2e; } .dash-circle:nth-child(3) { background: #27c93f; }
.dash-body { flex: 1; border-radius: 12px; background: rgba(255,255,255,0.6); display: flex; flex-direction: column; gap: 12px; padding: 16px; }
.dash-line { height: 12px; background: rgba(0,0,0,0.05); border-radius: 6px; width: 100%; }
.dash-line.short { width: 60%; }
.dash-line.primary { background: rgba(229,57,53,0.1); }

/* Floating widgets */
.widget { position: absolute; border-radius: 16px; padding: 20px; animation: floatWidget 6s infinite alternate ease-in-out; }
.w-1 { top: 20px; left: -40px; width: 220px; animation-delay: 0s; }
.w-2 { bottom: 40px; right: -40px; width: 240px; animation-delay: -3s; }
.w-3 { top: 50%; right: -60px; width: 180px; animation-delay: -1.5s; background: rgba(17,17,17,0.85); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
@keyframes floatWidget { 0% { transform: translateY(0); } 100% { transform: translateY(-20px); } }
.w-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: rgba(229,57,53,0.1); color: var(--primary); font-size: 1.3rem; margin-bottom: 12px; }

/* Sections */
.section { padding: 100px 20px; max-width: 1300px; margin: 0 auto; }
.section-header { text-align: center; margin-bottom: 60px; }
.section-header h2 { font-size: 2.6rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 16px; }
.section-header p { color: var(--text-muted); font-size: 1.15rem; max-width: 600px; margin: 0 auto; line-height: 1.6; }

/* Marquee */
.marquee-wrapper { width: 100%; overflow: hidden; -webkit-mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent); mask-image: linear-gradient(to right, transparent, black 5%, black 95%, transparent); padding: 20px 0; }
.marquee-container { display: flex; gap: 24px; width: fit-content; animation: marquee 35s linear infinite; }
.marquee-wrapper:hover .marquee-container { animation-play-state: paused; }
@keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(calc(-50% - 12px)); } }
.tutor-card { width: 260px; flex-shrink: 0; padding: 28px 24px; text-align: center; border-radius: 24px; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); cursor: pointer; text-decoration: none; color: inherit; display: block; }
.tutor-card:hover { transform: translateY(-12px); box-shadow: 0 24px 48px rgba(229,57,53,0.12); border-color: rgba(229,57,53,0.3); }
.tutor-avatar { width: 110px; height: 110px; border-radius: 50%; margin: 0 auto 16px; background: #fff; border: 4px solid #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.08); object-fit: cover; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; color: var(--primary); }
.tutor-card .name { font-weight: 800; font-size: 1.15rem; margin-bottom: 6px; color: var(--text-main); }
.tutor-card .subject { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 16px; }
.tutor-card .btn-glass { width: 100%; padding: 8px; border-radius: 8px; font-size: 0.85rem; }

/* Stats Grid */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; }
.stat-card { padding: 40px 24px; text-align: center; border-radius: 24px; transition: transform 0.3s; }
.stat-card:hover { transform: translateY(-5px); }
.stat-icon { font-size: 2rem; margin-bottom: 16px; }
.stat-num { font-size: 3.5rem; font-weight: 900; color: var(--primary); margin-bottom: 8px; letter-spacing: -1px; }
.stat-lbl { font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-size: 0.85rem; }

/* Features Grid */
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; }
.feature-card { padding: 40px 32px; border-radius: 24px; transition: all 0.4s; position: relative; overflow: hidden; }
.feature-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, transparent, rgba(229,57,53,0.03)); opacity: 0; transition: opacity 0.4s; pointer-events: none; }
.feature-card:hover { transform: translateY(-8px); border-color: rgba(229,57,53,0.3); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
.feature-card:hover::before { opacity: 1; }
.feature-icon { width: 64px; height: 64px; border-radius: 20px; background: linear-gradient(135deg, rgba(229,57,53,0.1), rgba(229,57,53,0.02)); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: var(--primary); margin-bottom: 24px; box-shadow: inset 0 2px 4px rgba(255,255,255,0.5); }
.feature-title { font-size: 1.3rem; font-weight: 800; margin-bottom: 12px; }
.feature-desc { color: var(--text-muted); line-height: 1.7; font-size: 0.95rem; }

/* Reviews */
.reviews-container { display: flex; gap: 24px; width: fit-content; animation: marquee 40s linear infinite reverse; }
.marquee-wrapper:hover .reviews-container { animation-play-state: paused; }
.review-card { padding: 32px; border-radius: 24px; width: 380px; flex-shrink: 0; text-align: left; transition: all 0.4s; }
.review-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.06); }
.review-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.review-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

/* CTA Section */
.cta-wrapper { padding: 0 20px; max-width: 1300px; margin: 0 auto 100px; }
.cta-section { padding: 80px 40px; border-radius: 32px; text-align: center; position: relative; overflow: hidden; background: linear-gradient(135deg, #e53935, #b71c1c); color: #fff; box-shadow: 0 30px 60px rgba(229,57,53,0.2); }
.cta-section::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%); animation: pulse 12s infinite linear; pointer-events: none; }
@keyframes pulse { 0% { transform: scale(1) rotate(0deg); } 50% { transform: scale(1.1) rotate(5deg); } 100% { transform: scale(1) rotate(0deg); } }
.cta-title { font-size: 3rem; font-weight: 900; margin-bottom: 16px; letter-spacing: -1px; position: relative; z-index: 1; }
.cta-desc { font-size: 1.15rem; opacity: 0.9; max-width: 600px; margin: 0 auto 40px; line-height: 1.6; position: relative; z-index: 1; }
.cta-btn { background: #fff; color: var(--primary); font-size: 1.1rem; padding: 16px 40px; border-radius: 100px; font-weight: 800; box-shadow: 0 10px 30px rgba(0,0,0,0.15); position: relative; z-index: 1; }
.cta-btn:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 35px rgba(0,0,0,0.2); background: #f8f8f8; }

/* Footer */
footer { background: rgba(255,255,255,0.7); backdrop-filter: blur(20px); border-top: 1px solid rgba(0,0,0,0.05); padding: 60px 40px; text-align: center; color: var(--text-muted); }
.footer-logo { margin-bottom: 24px; }
.footer-links { display: flex; justify-content: center; gap: 32px; margin-bottom: 32px; flex-wrap: wrap; }
.footer-links a { color: var(--text-muted); text-decoration: none; font-weight: 500; transition: color 0.3s; }
.footer-links a:hover { color: var(--primary); }
.social-icons { display: flex; justify-content: center; gap: 16px; margin-bottom: 32px; }
.social-icons a { width: 44px; height: 44px; border-radius: 50%; background: #fff; border: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: center; color: var(--text-main); transition: all 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.02); font-size: 1.1rem; text-decoration: none; }
.social-icons a:hover { background: var(--primary); color: #fff; transform: translateY(-4px); box-shadow: 0 10px 20px var(--primary-glow); border-color: var(--primary); }

/* Responsiveness */
@media (max-width: 1024px) {
  .hero-title { font-size: 3.5rem; }
  .widget { display: none; } /* Hide floating widgets on smaller screens to reduce clutter */
  .hero-visual { height: auto; }
}
@media (max-width: 768px) {
  nav { padding: 16px 20px; }
  .nav-links a.nav-link { display: none; } /* Hide text links, keep buttons */
  .hero { padding: 120px 20px 60px; }
  .hero-content { flex-direction: column; text-align: center; }
  .hero-visual { display: none; } /* Hide abstract dashboard on mobile */
  .hero-title { font-size: 2.8rem; }
  .hero-sub { margin: 0 auto 32px; }
  .hero-btns { justify-content: center; width: 100%; flex-direction: column; }
  .hero-btns .btn { width: 100%; }
  .section-header h2 { font-size: 2.2rem; }
  .cta-title { font-size: 2.4rem; }
  .cta-section { padding: 60px 20px; }
}
</style>
</head>
<body>

<div class="bg-blobs">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<nav>
  <a href="index.php"><img src="assets/img/logo-1.png" alt="MyClassroom" style="height: 48px; display:block"></a>
  <div class="nav-links">
    <a href="#features" class="nav-link">Features</a>
    <a href="#tutors" class="nav-link">Tutors</a>
    <a href="auth/login.php" class="btn btn-glass">Login</a>
    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
  </div>
</nav>

<div class="hero">
  <div class="hero-content">
    <div class="hero-text">
      <div class="hero-badge">✨ The Future of Learning in Sri Lanka</div>
      <h1 class="hero-title">Elevate Your<br><span>Learning Journey</span></h1>
      <p class="hero-sub">Experience a world-class premium educational platform. Connect with expert tutors, access crystal-clear live classes, and master your subjects anytime.</p>
      <div class="hero-btns">
        <a href="auth/register.php" class="btn btn-primary btn-lg" style="padding:16px 32px; font-size:1.05rem;"><i class="fa fa-rocket"></i> Join for Free</a>
      </div>
    </div>
    
    <div class="hero-visual">
      <!-- Abstract Glass Dashboard Mockup -->
      <div class="hero-dashboard glass">
        <div class="dash-header">
          <div class="dash-circle"></div><div class="dash-circle"></div><div class="dash-circle"></div>
        </div>
        <div class="dash-body">
          <div class="dash-line primary"></div>
          <div class="dash-line short"></div>
        </div>
        <div class="dash-body mt-12" style="flex:2; background:rgba(255,255,255,0.4)">
           <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;height:100%">
             <div style="background:rgba(229,57,53,0.1);border-radius:8px"></div>
             <div style="background:rgba(0,0,0,0.05);border-radius:8px"></div>
           </div>
        </div>
      </div>
      
      <!-- Floating Glass Widgets -->
      <div class="widget w-1 glass">
        <div class="w-icon"><i class="fa fa-users"></i></div>
        <div style="font-weight:800; font-size:1.2rem; color:#111"><?=number_format($sc)?>+</div>
        <div style="font-size:0.85rem; color:var(--text-muted)">Active Students</div>
      </div>
      <div class="widget w-2 glass">
        <div class="w-icon"><i class="fa fa-video"></i></div>
        <div style="font-weight:800; font-size:1.2rem; color:#111"><?=number_format($cc)?></div>
        <div style="font-size:0.85rem; color:var(--text-muted)">Premium Courses</div>
      </div>
      <div class="widget w-3 glass-dark">
        <div style="font-weight:800; font-size:1.1rem; margin-bottom:4px">Verified</div>
        <div style="font-size:0.85rem; color:#aaa">Top Tutors Across SL</div>
      </div>
    </div>
  </div>
</div>

<div class="section" id="tutors" style="padding-top: 40px;">
  <div class="section-header">
    <h2>Learn from the Best</h2>
    <p>Discover our top-rated educators who are ready to guide you to success.</p>
  </div>
  
  <?php if (!empty($tutors)): ?>
  <div class="marquee-wrapper">
    <!-- Duplicate list for infinite scroll -->
    <div class="marquee-container">
      <?php foreach(array_merge($tutors, $tutors) as $t): 
        $name = htmlspecialchars($t['display_name'] ?: $t['first_name'].' '.$t['last_name']);
        $initials = strtoupper(substr($t['first_name'],0,1) . substr($t['last_name'],0,1));
      ?>
      <a href="tutor-profile.php?id=<?=$t['id']?>" class="tutor-card glass">
        <?php if($t['profile_image']): ?>
          <img src="<?=htmlspecialchars($t['profile_image'])?>" class="tutor-avatar" alt="<?=$name?>">
        <?php else: ?>
          <div class="tutor-avatar"><?=$initials?></div>
        <?php endif; ?>
        <div class="name"><?=$name?></div>
        <div class="subject"><?=htmlspecialchars($t['subjects'] ?: 'General')?></div>
        <div class="btn btn-glass">View Profile</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="section" style="padding-top:20px;">
  <div class="stats-grid">
    <div class="stat-card glass">
      <div class="stat-icon">🎓</div>
      <div class="stat-num"><?=number_format($sc)?>+</div>
      <div class="stat-lbl">Learners</div>
    </div>
    <div class="stat-card glass">
      <div class="stat-icon">👨‍🏫</div>
      <div class="stat-num"><?=number_format($tc)?></div>
      <div class="stat-lbl">Expert Tutors</div>
    </div>
    <div class="stat-card glass">
      <div class="stat-icon">📚</div>
      <div class="stat-num"><?=number_format($cc)?></div>
      <div class="stat-lbl">Courses</div>
    </div>
    <div class="stat-card glass">
      <div class="stat-icon">⭐</div>
      <div class="stat-num">4.9</div>
      <div class="stat-lbl">Avg Rating</div>
    </div>
  </div>
</div>

<div class="section" id="features">
  <div class="section-header">
    <h2>A Next-Generation Experience</h2>
    <p>Everything you need to master your subjects, packaged in a beautiful, distraction-free environment.</p>
  </div>
  <div class="features-grid">
    <div class="feature-card glass">
      <div class="feature-icon"><i class="fa fa-video"></i></div>
      <div class="feature-title">Crystal Clear Classes</div>
      <div class="feature-desc">Seamless Zoom integration. Join live classes instantly without hunting for links.</div>
    </div>
    <div class="feature-card glass">
      <div class="feature-icon"><i class="fa fa-play-circle"></i></div>
      <div class="feature-title">In-line Recordings</div>
      <div class="feature-desc">Missed a class? Watch fully embedded recordings directly on your dashboard.</div>
    </div>
    <div class="feature-card glass">
      <div class="feature-icon"><i class="fa fa-folder-open"></i></div>
      <div class="feature-title">Organized Materials</div>
      <div class="feature-desc">Download PDFs, presentations, and assignments neatly categorized by course.</div>
    </div>
    <div class="feature-card glass">
      <div class="feature-icon"><i class="fa fa-shield-alt"></i></div>
      <div class="feature-title">Secure Payments</div>
      <div class="feature-desc">Simple bank slip uploads with fast manual verifications to keep your enrollment safe.</div>
    </div>
    <div class="feature-card glass">
      <div class="feature-icon"><i class="fa fa-bell"></i></div>
      <div class="feature-title">Real-time Alerts</div>
      <div class="feature-desc">Never miss an update. Get instant notifications for new classes and announcements.</div>
    </div>
    <div class="feature-card glass">
      <div class="feature-icon"><i class="fa fa-mobile-alt"></i></div>
      <div class="feature-title">Learn Anywhere</div>
      <div class="feature-desc">A fully responsive platform that looks and works beautifully on any device.</div>
    </div>
  </div>
</div>

<?php if (!empty($reviews)): ?>
<div class="section" style="padding-top:40px;">
  <div class="section-header">
    <h2>Student Success Stories</h2>
    <p>Hear directly from students who upgraded their learning with MyClassroom.</p>
  </div>
  <div class="marquee-wrapper">
    <div class="reviews-container">
      <?php foreach (array_merge($reviews, $reviews) as $rev): 
        $name = htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']);
      ?>
      <div class="review-card glass">
        <div class="review-header">
          <?php if($rev['profile_image']): ?>
            <img src="<?=htmlspecialchars($rev['profile_image'])?>" class="review-avatar" alt="<?=$name?>">
          <?php else: ?>
            <div class="review-avatar" style="background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;">
              <?=strtoupper(substr($rev['first_name'],0,1).substr($rev['last_name'],0,1))?>
            </div>
          <?php endif; ?>
          <div>
            <div style="font-weight:800;color:var(--text-main);"><?=$name?></div>
            <div style="color:#f59e0b;font-size:0.85rem;margin-top:2px;"><?=str_repeat('★', $rev['rating'])?><?=str_repeat('☆', 5 - $rev['rating'])?></div>
          </div>
        </div>
        <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.7;font-style:italic">"<?=htmlspecialchars($rev['review'])?>"</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="cta-wrapper">
  <div class="cta-section">
    <h2 class="cta-title">Ready to Transform Your Learning?</h2>
    <p class="cta-desc">Join our growing community today. Setup your free account in seconds and gain access to Sri Lanka's finest educators.</p>
    <a href="auth/register.php" class="btn cta-btn"><i class="fa fa-user-plus"></i> Create Free Account</a>
  </div>
</div>

<footer>
  <div class="footer-logo">
    <img src="assets/img/logo-1.png" alt="MyClassroom" style="height: 40px; opacity: 0.8;">
  </div>
  <div class="social-icons">
    <a href="https://www.facebook.com/profile.php?id=61590355193106" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
    <a href="#"><i class="fab fa-twitter"></i></a>
    <a href="#"><i class="fab fa-instagram"></i></a>
    <a href="#"><i class="fab fa-youtube"></i></a>
  </div>
  <div style="margin-bottom: 32px; font-size: 0.95rem; color: var(--text-main);">
    <a href="mailto:contact@myclassroom.lk" style="color: inherit; text-decoration: none; margin-right: 20px;"><i class="fa fa-envelope" style="color: var(--primary); margin-right: 6px;"></i> contact@myclassroom.lk</a>
    <a href="tel:+94711555621" style="color: inherit; text-decoration: none;"><i class="fa fa-phone" style="color: var(--primary); margin-right: 6px;"></i> +94 71 1555 621</a>
  </div>
  <div class="footer-links">
    <a href="#features">Features</a>
    <a href="#tutors">Top Tutors</a>
    <a href="auth/login.php">Student Login</a>
  </div>
  <div style="font-size: 0.85rem; opacity: 0.6;">
    © <?=date('Y')?> MyClassroom.lk. All rights reserved. Built for the future of education.
  </div>
</footer>

</body>
</html>
