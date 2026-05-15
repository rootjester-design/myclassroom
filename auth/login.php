<?php
require_once '../includes/helpers.php';
if (isLoggedIn()) {
    $user = getAuthUser();
    $dest = $user['role'] === 'super_admin' ? '/admin/dashboard.php'
          : ($user['role'] === 'tutor'       ? '/tutor/dashboard.php'
          :                                    '/student/dashboard.php');
    redirect($dest);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — MyClassroom</title>
<meta name="description" content="Login to MyClassroom Learning Management System">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="toast-container"></div>
<div class="auth-page">
  <!-- Left Brand Panel -->
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo">My<span>Classroom</span></div>
      <p class="auth-tagline">Your gateway to premium learning</p>
      <div class="auth-feature-list">
        <div class="auth-feature">
          <div class="auth-feature-icon">📚</div>
          <div><div class="auth-feature-title">Expert Tutors</div><div class="auth-feature-text">Learn from certified educators</div></div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon">🎬</div>
          <div><div class="auth-feature-title">Live & Recorded Classes</div><div class="auth-feature-text">Never miss a lesson again</div></div>
        </div>
        <div class="auth-feature">
          <div class="auth-feature-icon">📱</div>
          <div><div class="auth-feature-title">Learn Anywhere</div><div class="auth-feature-text">Mobile-friendly platform</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Form Panel -->
  <div class="auth-right">
    <div class="auth-box">
      <h1 class="auth-box-title">Welcome Back 👋</h1>
      <p class="auth-box-subtitle">Sign in to continue your learning journey</p>

      <form id="loginForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="phone-group">
            <span class="phone-prefix">🇱🇰 +94</span>
            <input type="tel" id="phone" name="phone" class="form-control" placeholder="7X XXX XXXX" maxlength="10" required>
          </div>
          <div class="form-error" id="phone-error"></div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
            <span class="input-toggle" id="togglePass"><i class="fa fa-eye"></i></span>
          </div>
          <div class="form-error" id="pass-error"></div>
        </div>

        <div class="flex-between mb-20">
          <label style="display:flex;align-items:center;gap:8px;font-size:0.88rem;cursor:pointer;">
            <input type="checkbox" id="remember"> Remember me
          </label>
          <a href="forgot-password.php" class="auth-link fs-sm">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-full btn-lg" id="loginBtn">
          <span id="loginBtnText">Sign In</span>
          <span id="loginSpinner" class="spinner spinner-sm hidden"></span>
        </button>
      </form>

      <div class="divider-text mt-20 mb-20"><span>New to MyClassroom?</span></div>
      <a href="register.php" class="btn btn-outline btn-full">🎓 Create Student Account</a>

    </div>
  </div>
</div>

<script>
const togglePass = document.getElementById('togglePass');
const passInput = document.getElementById('password');
togglePass.addEventListener('click', () => {
  const isPass = passInput.type === 'password';
  passInput.type = isPass ? 'text' : 'password';
  togglePass.innerHTML = isPass ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
});

document.getElementById('loginForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  const btnText = document.getElementById('loginBtnText');
  const spinner = document.getElementById('loginSpinner');
  let phone = document.getElementById('phone').value.trim();
  const password = document.getElementById('password').value;
  document.getElementById('phone-error').textContent = '';
  document.getElementById('pass-error').textContent = '';

  if (!phone) { document.getElementById('phone-error').textContent = 'Phone number is required'; return; }
  if (!password) { document.getElementById('pass-error').textContent = 'Password is required'; return; }

  btn.disabled = true;
  btnText.textContent = 'Signing in...';
  spinner.classList.remove('hidden');

  try {
    const fd = new FormData(e.target);
    const res = await fetch('../api/auth/login.php', { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      showToast('Login successful! Redirecting...', 'success');
      setTimeout(() => { window.location.href = '/myclassroom' + data.redirect; }, 800);
    } else {
      showToast(data.message || 'Login failed', 'error');
      if (data.field === 'phone') document.getElementById('phone-error').textContent = data.message;
      if (data.field === 'password') document.getElementById('pass-error').textContent = data.message;
    }
  } catch(err) {
    showToast('Network error. Please try again.', 'error');
  } finally {
    btn.disabled = false;
    btnText.textContent = 'Sign In';
    spinner.classList.add('hidden');
  }
});

function showToast(msg, type='info') {
  const icons = {success:'✅', error:'❌', warning:'⚠️', info:'ℹ️'};
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon">${icons[type]||'ℹ️'}</span><span>${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">×</span>`;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => { t.style.animation='slideOut 0.3s ease forwards'; setTimeout(()=>t.remove(),300); }, 4000);
}
</script>
</body>
</html>
