<?php
require_once '../includes/helpers.php';
if (isLoggedIn()) redirect('/student/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Forgot Password — MyClassroom</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="toast-container"></div>
<input type="hidden" id="csrfToken" value="<?= generateCsrfToken() ?>">
<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo">My<span>Classroom</span></div>
      <p class="auth-tagline">Reset your password securely</p>
      <div class="auth-feature-list">
        <div class="auth-feature"><div class="auth-feature-icon">📱</div><div><div class="auth-feature-title">OTP Verification</div><div class="auth-feature-text">Verify your phone number</div></div></div>
        <div class="auth-feature"><div class="auth-feature-icon">🔒</div><div><div class="auth-feature-title">Secure Reset</div><div class="auth-feature-text">Set a strong new password</div></div></div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-box">
      <div class="step-indicator">
        <div class="step"><div class="step-dot active" id="s1">1</div><span class="fs-xs text-muted">Phone</span></div>
        <div class="step-line" id="l1"></div>
        <div class="step"><div class="step-dot" id="s2">2</div><span class="fs-xs text-muted">Verify</span></div>
        <div class="step-line" id="l2"></div>
        <div class="step"><div class="step-dot" id="s3">3</div><span class="fs-xs text-muted">Reset</span></div>
      </div>

      <!-- Step 1 -->
      <div id="step1">
        <h2 class="auth-box-title">Forgot Password?</h2>
        <p class="auth-box-subtitle">Enter your registered phone number</p>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="phone-group">
            <span class="phone-prefix">🇱🇰 +94</span>
            <input type="tel" id="fp-phone" class="form-control" placeholder="7X XXX XXXX" maxlength="10">
          </div>
          <div class="form-error" id="fp-phone-err"></div>
        </div>
        <button class="btn btn-primary btn-full btn-lg" id="fpSendBtn" onclick="sendResetOtp()">
          <span id="fpSendText">Send OTP</span>
          <span id="fpSendSpinner" class="spinner spinner-sm hidden"></span>
        </button>
        <p class="text-center mt-16 fs-sm"><a href="login.php" class="auth-link">← Back to Login</a></p>
      </div>

      <!-- Step 2 -->
      <div id="step2" class="hidden">
        <h2 class="auth-box-title">Verify OTP 📱</h2>
        <p class="auth-box-subtitle" id="fp-otp-sub">Enter the code sent to your number</p>
        <div class="otp-inputs">
          <input type="text" class="otp-input" maxlength="1">
          <input type="text" class="otp-input" maxlength="1">
          <input type="text" class="otp-input" maxlength="1">
          <input type="text" class="otp-input" maxlength="1">
          <input type="text" class="otp-input" maxlength="1">
          <input type="text" class="otp-input" maxlength="1">
        </div>
        <div class="form-error text-center" id="fp-otp-err"></div>
        <button class="btn btn-primary btn-full btn-lg mt-16" id="fpVerifyBtn" onclick="verifyResetOtp()">
          <span id="fpVerifyText">Verify OTP</span>
          <span id="fpVerifySpinner" class="spinner spinner-sm hidden"></span>
        </button>
        <p class="text-center mt-8"><span class="auth-link fs-sm" onclick="goFpStep(1)">← Change number</span></p>
      </div>

      <!-- Step 3 -->
      <div id="step3" class="hidden">
        <h2 class="auth-box-title">New Password 🔑</h2>
        <p class="auth-box-subtitle">Choose a strong new password</p>
        <div class="form-group">
          <label class="form-label">New Password</label>
          <div class="input-group">
            <input type="password" id="new-pass" class="form-control" placeholder="Min 8 characters">
            <span class="input-toggle" onclick="togglePwd('new-pass',this)"><i class="fa fa-eye"></i></span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-group">
            <input type="password" id="new-pass2" class="form-control" placeholder="Repeat password">
            <span class="input-toggle" onclick="togglePwd('new-pass2',this)"><i class="fa fa-eye"></i></span>
          </div>
          <div class="form-error" id="fp-pass-err"></div>
        </div>
        <button class="btn btn-primary btn-full btn-lg" id="fpResetBtn" onclick="resetPassword()">
          <span id="fpResetText">Reset Password</span>
          <span id="fpResetSpinner" class="spinner spinner-sm hidden"></span>
        </button>
      </div>
    </div>
  </div>
</div>
<script>
let fpPhone='';
function showToast(msg,type='info'){const t=document.createElement('div');t.className=`toast ${type}`;t.innerHTML=`<span>${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">×</span>`;document.getElementById('toast-container').appendChild(t);setTimeout(()=>t.remove(),4000);}
function goFpStep(n){[1,2,3].forEach(i=>document.getElementById('step'+i).classList.toggle('hidden',i!==n));document.getElementById('s1').className='step-dot'+(n>=1?' active':'');document.getElementById('s2').className='step-dot'+(n===2?' active':n>2?' done':'');document.getElementById('s3').className='step-dot'+(n===3?' active':'');document.getElementById('l1').className='step-line'+(n>1?' done':'');document.getElementById('l2').className='step-line'+(n>2?' done':'');}
function setL(b,t,s,txt,on){document.getElementById(b).disabled=on;document.getElementById(t).textContent=txt;document.getElementById(s).classList.toggle('hidden',!on);}
async function sendResetOtp(){const phone=document.getElementById('fp-phone').value.trim();document.getElementById('fp-phone-err').textContent='';if(!phone||phone.length<9){document.getElementById('fp-phone-err').textContent='Enter a valid phone number';return;}setL('fpSendBtn','fpSendText','fpSendSpinner','Sending...',true);try{const fd=new FormData();fd.append('phone','0'+phone.replace(/^0/,''));fd.append('purpose','reset');fd.append('csrf_token',document.getElementById('csrfToken').value);const res=await fetch('../api/auth/send-otp.php',{method:'POST',body:fd});const d=await res.json();if(d.success){fpPhone='0'+phone.replace(/^0/,'');if(d.otp)showToast(`Dev OTP: ${d.otp}`,'info');else showToast('OTP sent!','success');goFpStep(2);}else showToast(d.message||'Failed','error');}catch(e){showToast('Network error','error');}setL('fpSendBtn','fpSendText','fpSendSpinner','Send OTP',false);}
document.querySelectorAll('.otp-input').forEach((inp,idx,all)=>{inp.addEventListener('input',()=>{inp.value=inp.value.replace(/\D/,'');if(inp.value&&idx<5)all[idx+1].focus();inp.classList.toggle('filled',!!inp.value);});inp.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!inp.value&&idx>0)all[idx-1].focus();});});
async function verifyResetOtp(){const otp=[...document.querySelectorAll('.otp-input')].map(i=>i.value).join('');document.getElementById('fp-otp-err').textContent='';if(otp.length<6){document.getElementById('fp-otp-err').textContent='Enter all 6 digits';return;}setL('fpVerifyBtn','fpVerifyText','fpVerifySpinner','Verifying...',true);try{const fd=new FormData();fd.append('phone',fpPhone);fd.append('otp',otp);fd.append('purpose','reset');fd.append('csrf_token',document.getElementById('csrfToken').value);const res=await fetch('../api/auth/verify-otp.php',{method:'POST',body:fd});const d=await res.json();if(d.success){showToast('Verified!','success');goFpStep(3);}else document.getElementById('fp-otp-err').textContent=d.message||'Invalid OTP';}catch(e){showToast('Network error','error');}setL('fpVerifyBtn','fpVerifyText','fpVerifySpinner','Verify OTP',false);}
async function resetPassword(){const p=document.getElementById('new-pass').value;const p2=document.getElementById('new-pass2').value;document.getElementById('fp-pass-err').textContent='';if(p.length<8){document.getElementById('fp-pass-err').textContent='Password must be 8+ characters';return;}if(p!==p2){document.getElementById('fp-pass-err').textContent='Passwords do not match';return;}setL('fpResetBtn','fpResetText','fpResetSpinner','Resetting...',true);try{const fd=new FormData();fd.append('phone',fpPhone);fd.append('password',p);fd.append('csrf_token',document.getElementById('csrfToken').value);const res=await fetch('../api/auth/reset-password.php',{method:'POST',body:fd});const d=await res.json();if(d.success){showToast('Password reset! Redirecting...','success');setTimeout(()=>window.location.href='/myclassroom/auth/login.php',1500);}else showToast(d.message||'Failed','error');}catch(e){showToast('Network error','error');}setL('fpResetBtn','fpResetText','fpResetSpinner','Reset Password',false);}
function togglePwd(id,el){const inp=document.getElementById(id);const show=inp.type==='password';inp.type=show?'text':'password';el.innerHTML=show?'<i class="fa fa-eye-slash"></i>':'<i class="fa fa-eye"></i>';}
</script>
</body>
</html>
