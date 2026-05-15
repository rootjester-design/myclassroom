<?php
require_once '../includes/helpers.php';
if (isLoggedIn() && getAuthUser()['role']==='super_admin') redirect('/admin/dashboard.php');
if (isLoggedIn() && getAuthUser()['role']==='tutor') redirect('/tutor/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Admin Login — MyClassroom</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.auth-left{background:linear-gradient(150deg,#111 0%,#1a1a1a 60%,#2d2d2d 100%);}
.auth-page{background:linear-gradient(135deg,#f5f5f5 0%,#fff 100%);}
</style>
</head>
<body>
<div id="toast-container"></div>
<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo" style="color:#e53935">My<span style="color:#fff">Classroom</span></div>
      <p class="auth-tagline">Administrator & Tutor Portal</p>
      <div class="auth-feature-list">
        <div class="auth-feature"><div class="auth-feature-icon" style="background:rgba(229,57,53,0.2)">👑</div><div><div class="auth-feature-title">Super Admin</div><div class="auth-feature-text">Full platform control</div></div></div>
        <div class="auth-feature"><div class="auth-feature-icon" style="background:rgba(229,57,53,0.2)">👨‍🏫</div><div><div class="auth-feature-title">Tutor Panel</div><div class="auth-feature-text">Manage your courses</div></div></div>
        <div class="auth-feature"><div class="auth-feature-icon" style="background:rgba(229,57,53,0.2)">🔐</div><div><div class="auth-feature-title">Secure Access</div><div class="auth-feature-text">Role-based authentication</div></div></div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-box">
      <h1 class="auth-box-title">Admin Login 👑</h1>
      <p class="auth-box-subtitle">Sign in to the administration panel</p>
      <form id="adminLoginForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="form-group">
          <label class="form-label">Email or Phone</label>
          <div class="input-group">
            <input type="text" id="identifier" name="phone" class="form-control" placeholder="admin@myclassroom.lk" required>
          </div>
          <div class="form-error" id="id-error"></div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-group">
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
            <span class="input-toggle" onclick="togglePwd('password',this)"><i class="fa fa-eye"></i></span>
          </div>
          <div class="form-error" id="pass-error"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" id="loginBtn">
          <span id="loginText">Sign In</span>
          <span id="loginSpinner" class="spinner spinner-sm hidden"></span>
        </button>
      </form>
      <p class="text-center text-muted fs-sm mt-20">Student? <a href="../auth/login.php" class="auth-link">Student Login</a></p>
      <div class="alert alert-info mt-20 fs-xs">
        <i class="fa fa-info-circle"></i>
      </div>
    </div>
  </div>
</div>
<script>
function togglePwd(id,el){const inp=document.getElementById(id);const s=inp.type==='password';inp.type=s?'text':'password';el.innerHTML=s?'<i class="fa fa-eye-slash"></i>':'<i class="fa fa-eye"></i>';}
function showToast(msg,type='info'){const t=document.createElement('div');t.className=`toast ${type}`;t.innerHTML=`<span>${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">×</span>`;document.getElementById('toast-container').appendChild(t);setTimeout(()=>t.remove(),4000);}
document.getElementById('adminLoginForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const btn=document.getElementById('loginBtn');const text=document.getElementById('loginText');const spin=document.getElementById('loginSpinner');
  btn.disabled=true;text.textContent='Signing in...';spin.classList.remove('hidden');
  try{
    const res=await fetch('../api/auth/login.php',{method:'POST',body:new FormData(e.target)});
    const text=await res.text();
    let d;
    try { d = JSON.parse(text); } catch (parseErr) { throw new Error(text || 'Invalid server response'); }
    if(d.success){
      showToast('Login successful!','success');
      setTimeout(()=>{ window.location.href = d.redirect; },800);
    }
    else{showToast(d.message||'Login failed','error');}
  }catch(err){
    showToast(err.message||'Network error','error');
  }
  btn.disabled=false;text.textContent='Sign In';spin.classList.add('hidden');
});
</script>
</body>
</html>
