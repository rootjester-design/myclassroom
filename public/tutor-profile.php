<?php
require_once __DIR__ . '/../../app/includes/helpers.php';
// Public page, no auth required

$tutorId = (int)($_GET['id'] ?? 0);
$db = Database::getInstance();
$tutor = $db->fetch("SELECT * FROM tutors WHERE id=? AND is_approved=1", [$tutorId]);

$pageTitle = "Tutor Profile — MyClassroom";
$pageDesc = "Discover courses and learn from expert tutors on MyClassroom.";
$pageImage = APP_URL . "/assets/img/logo-1.png";
$pageUrl = APP_URL . "/tutor-profile.php" . ($tutor ? "?id=" . $tutorId : "");

if ($tutor) {
    $name = $tutor['display_name'] ?: ($tutor['first_name'] . ' ' . $tutor['last_name']);
    $pageTitle = $name . " — MyClassroom";
    if (!empty($tutor['subjects'])) {
        $pageDesc = "Learn " . $tutor['subjects'] . " with " . $name . " on MyClassroom.";
    } else {
        $pageDesc = "View " . $name . "'s profile and premium courses on MyClassroom.";
    }
    if (!empty($tutor['profile_image'])) {
        $pageImage = APP_URL . str_replace('..', '', $tutor['profile_image']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=htmlspecialchars($pageTitle)?></title>
<meta name="description" content="<?=htmlspecialchars($pageDesc)?>">

<!-- Open Graph -->
<meta property="og:type" content="profile">
<meta property="og:url" content="<?=htmlspecialchars($pageUrl)?>">
<meta property="og:title" content="<?=htmlspecialchars($pageTitle)?>">
<meta property="og:description" content="<?=htmlspecialchars($pageDesc)?>">
<meta property="og:image" content="<?=htmlspecialchars($pageImage)?>">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?=htmlspecialchars($pageUrl)?>">
<meta name="twitter:title" content="<?=htmlspecialchars($pageTitle)?>">
<meta name="twitter:description" content="<?=htmlspecialchars($pageDesc)?>">
<meta name="twitter:image" content="<?=htmlspecialchars($pageImage)?>">
<link rel="stylesheet" href="assets/css/main.css?v=<?=time()?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="assets/img/logo-2.png">
<style>
/* Simplified styling for the public profile */
body { background: #fbfbfb; color: #111; font-family: 'Inter', sans-serif; overflow-x: hidden; }
nav {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 40px; position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.page-container { padding: 120px 20px 60px; max-width: 1200px; margin: 0 auto; min-height: 100vh; }
.btn { padding: 12px 24px; border-radius: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; transition: all 0.3s; border: none; font-size: 0.95rem; }
.btn-primary { background: #e53935; color: #fff; box-shadow: 0 6px 20px rgba(229, 57, 53, 0.4); }
.card { background: #fff; border-radius: 20px; box-shadow: 0 8px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
.course-card { background: #fff; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); transition: transform 0.3s; }
.course-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
.course-card-thumb { height: 160px; background: #eee; position: relative; }
.course-card-body { padding: 20px; flex: 1; }
.course-card-title { font-weight: 700; font-size: 1.1rem; margin-bottom: 8px; color: #111; }
.course-card-price { color: #e53935; font-weight: 800; font-size: 1.1rem; }
.course-card-footer { padding: 0 20px 20px; }
.grid-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
footer { background: #fff; border-top: 1px solid rgba(0,0,0,0.05); padding: 40px; text-align: center; color: #666; font-size: 0.9rem; }
@media(max-width: 768px) {
  nav { padding: 16px 20px; }
  .nav-logo-img { height: 36px !important; }
}
</style>
</head>
<body>
<nav>
  <a href="index.php"><img src="assets/img/logo-1.png" alt="MyClassroom" class="nav-logo-img" style="height: 48px; display:block"></a>
  <div class="nav-links" style="display:flex;gap:16px;">
    <a href="auth/login.php" class="btn" style="background:#f1f1f1;color:#111;">Login</a>
    <a href="auth/register.php" class="btn btn-primary">Get Started</a>
  </div>
</nav>

<div class="page-container" id="tutorPageBody">
  <div style="text-align:center;padding:100px 40px;font-size:1.2rem;color:#777;">
    <i class="fa fa-spinner fa-spin" style="margin-right:10px;"></i> Loading profile...
  </div>
</div>

<footer>
  © <?=date('Y')?> MyClassroom.lk. All rights reserved.
</footer>

<script>
async function loadTutorProfilePage() {
  const container = document.getElementById('tutorPageBody');
  const params = new URLSearchParams(window.location.search);
  const tutorId = parseInt(params.get('id') || '', 10);
  if (!tutorId) {
    container.innerHTML = '<div style="text-align:center;padding:100px 40px;color:#e53935;"><i class="fa fa-exclamation-circle fa-2x mb-12"></i><br>Tutor not found.</div>';
    return;
  }
  
  try {
    const res = await fetch(`api/public-tutor-profile.php?id=${tutorId}`);
    const d = await res.json();
    if(!d.success){container.innerHTML='<div style="text-align:center;padding:100px 40px;color:#e53935;"><i class="fa fa-exclamation-triangle fa-2x mb-12"></i><br>Failed to load profile.</div>';return;}
    const t = d.tutor;
    const name = t.display_name||t.first_name+' '+t.last_name;
    document.title = `${name} — MyClassroom`;
    
    container.innerHTML = `
      <a href="index.php" style="display:inline-block;margin-bottom:24px;color:#666;text-decoration:none;font-weight:600;"><i class="fa fa-arrow-left"></i> Back to Home</a>
      <div style="background:linear-gradient(135deg,#e53935,#b71c1c);border-radius:24px;width:100%;height:clamp(150px, 25vw, 300px);margin-bottom:80px;position:relative;${t.banner_image?`background-image:url(${t.banner_image});background-size:cover;background-position:center`:''};overflow:visible;box-shadow:0 10px 30px rgba(229,57,53,0.2);">
        <div style="position:absolute;bottom:-75px;left:40px;z-index:2">${t.profile_image?`<img src="${t.profile_image}" style="width:150px;height:150px;border-radius:50%;border:5px solid #fff;object-fit:cover;box-shadow:0 4px 15px rgba(0,0,0,0.1)">`:`<div style="width:150px;height:150px;border-radius:50%;border:5px solid #fff;background:#fff;display:flex;align-items:center;justify-content:center;font-size:3.5rem;color:#e53935;font-weight:800;box-shadow:0 4px 15px rgba(0,0,0,0.1)">${name.substring(0,2).toUpperCase()}</div>`}</div>
      </div>
      <div style="margin-bottom:32px;">
        <h1 style="font-size:2.2rem;font-weight:900;letter-spacing:-0.5px;">${name}</h1>
        ${t.subjects?`<div style="display:inline-block;padding:6px 16px;background:rgba(229,57,53,0.1);color:#e53935;border-radius:100px;font-weight:700;margin-top:12px;font-size:0.9rem;">${t.subjects}</div>`:''}
      </div>
      ${(t.social_links && t.social_links!=='{}' || t.description || t.experience || t.qualifications) ? `
      <div class="card" style="padding:40px;margin-bottom:48px;">
        ${t.social_links?(() => {
          try {
            const links = JSON.parse(t.social_links);
            if (Object.keys(links).length === 0) return '';
            return '<div style="margin-bottom:32px;"><h3 style="font-size:1.15rem;font-weight:800;margin-bottom:16px;">Connect</h3><div style="display:flex;gap:12px;flex-wrap:wrap">' + 
              Object.entries(links).map(([platform, url]) => `<a href="${url}" target="_blank" class="btn" style="background:#f8f9fa;color:#333;text-transform:capitalize;padding:10px 20px;border:1px solid #eee;"><i class="fab fa-${platform.toLowerCase()}"></i> ${platform}</a>`).join('') + 
              '</div></div>';
          } catch(e) { return ''; }
        })():''}
        ${t.description?`<div style="margin-bottom:32px;"><h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;">About</h3><p style="color:#555;line-height:1.8;font-size:1rem;">${t.description}</p></div>`:''}
        ${t.experience?`<div style="margin-bottom:32px;"><h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;">Experience</h3><p style="color:#555;line-height:1.8;font-size:1rem;">${t.experience}</p></div>`:''}
        ${t.qualifications?`<div><h3 style="font-size:1.15rem;font-weight:800;margin-bottom:12px;">Qualifications</h3><p style="color:#555;line-height:1.8;font-size:1rem;">${t.qualifications}</p></div>`:''}
      </div>
      ` : ''}
      <h2 style="font-size:1.8rem;font-weight:900;margin-bottom:24px;">Courses by ${name}</h2>
      <div class="grid-3">
        ${(d.courses||[]).map(c=>`<div class="course-card">
          <div class="course-card-thumb">${c.thumbnail?`<img src="${c.thumbnail}" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover">`:`<div style="position:absolute;top:0;left:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3.5rem;background:#fafafa;">📚</div>`}</div>
          <div class="course-card-body">
            <div class="course-card-title">${c.title}</div>
            <div class="course-card-price">${parseFloat(c.price||0) > 0 ? 'Rs. ' + parseFloat(c.price).toLocaleString() : '<span style="color:#22c55e;">Free</span>'}</div>
          </div>
          <div class="course-card-footer">
            <a href="auth/login.php?redirect=enroll" class="btn btn-primary" style="width:100%;box-shadow:none;">Login to Enroll</a>
          </div>
        </div>`).join('')||'<p style="color:#777;font-size:1.1rem;">No courses available at the moment.</p>'}
      </div>
    `;
  } catch(e) {
    container.innerHTML = '<div style="text-align:center;padding:100px 40px;color:red;"><i class="fa fa-exclamation-triangle fa-2x mb-12"></i><br>Failed to load tutor profile.</div>';
  }
}
document.addEventListener('DOMContentLoaded', loadTutorProfilePage);
</script>
</body>
</html>
