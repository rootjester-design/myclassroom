<?php
require_once '../includes/helpers.php';
if (isLoggedIn()) redirect('/student/dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — MyClassroom</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/auth.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div id="toast-container"></div>
<div class="auth-page">
  <div class="auth-left">
    <div class="auth-left-content">
      <div class="auth-logo">My<span>Classroom</span></div>
      <p class="auth-tagline">Start your learning journey today</p>
      <div class="auth-feature-list">
        <div class="auth-feature"><div class="auth-feature-icon">🔐</div><div><div class="auth-feature-title">Secure Registration</div><div class="auth-feature-text">OTP verified phone number</div></div></div>
        <div class="auth-feature"><div class="auth-feature-icon">🎓</div><div><div class="auth-feature-title">Instant Access</div><div class="auth-feature-text">Browse courses right away</div></div></div>
        <div class="auth-feature"><div class="auth-feature-icon">💳</div><div><div class="auth-feature-title">Simple Payments</div><div class="auth-feature-text">Easy enrollment process</div></div></div>
      </div>
    </div>
  </div>
  <div class="auth-right">
    <div class="auth-box">
      <div class="step-indicator">
        <div class="step"><div class="step-dot active" id="step1-dot">1</div><span class="fs-xs text-muted">Phone</span></div>
        <div class="step-line" id="line1"></div>
        <div class="step"><div class="step-dot" id="step2-dot">2</div><span class="fs-xs text-muted">Verify</span></div>
        <div class="step-line" id="line2"></div>
        <div class="step"><div class="step-dot" id="step3-dot">3</div><span class="fs-xs text-muted">Details</span></div>
      </div>

      <div id="step1">
        <h2 class="auth-box-title">Create Account</h2>
        <p class="auth-box-subtitle">Enter your phone number to get started</p>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <div class="phone-group">
            <span class="phone-prefix">🇱🇰 +94</span>
            <input type="tel" id="reg-phone" class="form-control" placeholder="7X XXX XXXX" maxlength="10">
          </div>
          <div class="form-error" id="phone-err"></div>
        </div>
        <button class="btn btn-primary btn-full btn-lg" id="sendOtpBtn" onclick="sendOtp()">
          <span id="sendOtpText">Send OTP</span>
          <span id="sendOtpSpinner" class="spinner spinner-sm hidden"></span>
        </button>
        <p class="text-center text-muted fs-sm mt-16">Already have an account? <a href="login.php" class="auth-link">Sign in</a></p>
      </div>

      <div id="step2" class="hidden">
        <h2 class="auth-box-title">Verify Phone 📱</h2>
        <p class="auth-box-subtitle" id="otp-subtitle">Enter the 6-digit OTP</p>
        <div class="otp-inputs">
          <input type="text" class="otp-input" maxlength="1" data-index="0">
          <input type="text" class="otp-input" maxlength="1" data-index="1">
          <input type="text" class="otp-input" maxlength="1" data-index="2">
          <input type="text" class="otp-input" maxlength="1" data-index="3">
          <input type="text" class="otp-input" maxlength="1" data-index="4">
          <input type="text" class="otp-input" maxlength="1" data-index="5">
        </div>
        <div class="form-error text-center" id="otp-err"></div>
        <button class="btn btn-primary btn-full btn-lg mt-16" id="verifyOtpBtn" onclick="verifyOtp()">
          <span id="verifyText">Verify OTP</span>
          <span id="verifySpinner" class="spinner spinner-sm hidden"></span>
        </button>
        <p class="text-center fs-sm mt-12 text-muted">Didn't receive? <span class="auth-link" id="resendBtn" onclick="sendOtp()">Resend</span> <span id="resendTimer"></span></p>
        <p class="text-center mt-8"><span class="auth-link fs-sm" onclick="goStep(1)">← Change number</span></p>
      </div>

      <div id="step3" class="hidden">
        <h2 class="auth-box-title">Your Details</h2>
        <p class="auth-box-subtitle">Complete your profile</p>
        <form id="registerForm" novalidate>
          <input type="hidden" id="reg-phone-hidden" name="phone">
          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" placeholder="Kasun" required>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" placeholder="Perera" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Birthday</label>
            <input type="date" name="birthday" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" placeholder="No. 1, Colombo Road" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input type="password" name="password" id="reg-pass" class="form-control" placeholder="Min 8 characters" required>
              <span class="input-toggle" onclick="togglePwd('reg-pass',this)"><i class="fa fa-eye"></i></span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-group">
              <input type="password" name="confirm_password" id="reg-pass2" class="form-control" placeholder="Repeat password" required>
              <span class="input-toggle" onclick="togglePwd('reg-pass2',this)"><i class="fa fa-eye"></i></span>
            </div>
            <div class="form-error" id="pass-err"></div>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" id="registerBtn">
            <span id="regText">Create Account 🎉</span>
            <span id="regSpinner" class="spinner spinner-sm hidden"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<input type="hidden" id="csrfToken" value="<?= generateCsrfToken() ?>">
<script>
let verifiedPhone=''; let resendInterval;
function showToast(msg,type='info'){const icons={success:'✅',error:'❌',warning:'⚠️',info:'ℹ️'};const t=document.createElement('div');t.className=`toast ${type}`;t.innerHTML=`<span class="toast-icon">${icons[type]}</span><span>${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">×</span>`;document.getElementById('toast-container').appendChild(t);setTimeout(()=>{t.style.animation='slideOut 0.3s ease forwards';setTimeout(()=>t.remove(),300);},4000);}
function goStep(n){[1,2,3].forEach(i=>{const el=document.getElementById('step'+i);if(el)el.classList.toggle('hidden',i!==n);});document.getElementById('step1-dot').className='step-dot'+(n>=1?' active':'');document.getElementById('step2-dot').className='step-dot'+(n===2?' active':n>2?' done':'');document.getElementById('step3-dot').className='step-dot'+(n===3?' active':'');document.getElementById('line1').className='step-line'+(n>1?' done':'');document.getElementById('line2').className='step-line'+(n>2?' done':'');}
function setLoading(b,t,s,txt,on){document.getElementById(b).disabled=on;document.getElementById(t).textContent=txt;document.getElementById(s).classList.toggle('hidden',!on);}
async function sendOtp(){const phone=document.getElementById('reg-phone').value.trim();document.getElementById('phone-err').textContent='';if(!phone||phone.length<9){document.getElementById('phone-err').textContent='Enter a valid phone number';return;}setLoading('sendOtpBtn','sendOtpText','sendOtpSpinner','Sending...',true);try{const fd=new FormData();fd.append('phone','0'+phone.replace(/^0/,''));fd.append('purpose','register');fd.append('csrf_token',document.getElementById('csrfToken').value);const res=await fetch('../api/auth/send-otp.php',{method:'POST',body:fd});const data=await res.json();if(data.success){verifiedPhone='0'+phone.replace(/^0/,'');document.getElementById('otp-subtitle').textContent=`OTP sent to +94${phone}`;if(data.otp)showToast(`Dev OTP: ${data.otp}`,'info');else showToast('OTP sent!','success');goStep(2);startTimer();}else{showToast(data.message||'Failed','error');}}catch(e){showToast('Network error','error');}setLoading('sendOtpBtn','sendOtpText','sendOtpSpinner','Send OTP',false);}
function startTimer(){let sec=60;document.getElementById('resendBtn').style.opacity='0.4';document.getElementById('resendTimer').textContent=`(${sec}s)`;clearInterval(resendInterval);resendInterval=setInterval(()=>{sec--;document.getElementById('resendTimer').textContent=`(${sec}s)`;if(sec<=0){clearInterval(resendInterval);document.getElementById('resendBtn').style.opacity='1';document.getElementById('resendTimer').textContent='';}},1000);}
document.querySelectorAll('.otp-input').forEach((inp,idx,all)=>{inp.addEventListener('input',()=>{inp.value=inp.value.replace(/\D/,'');if(inp.value&&idx<5)all[idx+1].focus();inp.classList.toggle('filled',!!inp.value);});inp.addEventListener('keydown',(e)=>{if(e.key==='Backspace'&&!inp.value&&idx>0)all[idx-1].focus();});inp.addEventListener('paste',(e)=>{e.preventDefault();const p=e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);[...p].forEach((c,i)=>{if(all[i]){all[i].value=c;all[i].classList.add('filled');}});if(all[p.length-1])all[p.length-1].focus();});});
async function verifyOtp(){const otp=[...document.querySelectorAll('.otp-input')].map(i=>i.value).join('');document.getElementById('otp-err').textContent='';if(otp.length<6){document.getElementById('otp-err').textContent='Enter all 6 digits';return;}setLoading('verifyOtpBtn','verifyText','verifySpinner','Verifying...',true);try{const fd=new FormData();fd.append('phone',verifiedPhone);fd.append('otp',otp);fd.append('purpose','register');fd.append('csrf_token',document.getElementById('csrfToken').value);const res=await fetch('../api/auth/verify-otp.php',{method:'POST',body:fd});const data=await res.json();if(data.success){showToast('Phone verified!','success');document.getElementById('reg-phone-hidden').value=verifiedPhone;goStep(3);}else{document.getElementById('otp-err').textContent=data.message||'Invalid OTP';}}catch(e){showToast('Network error','error');}setLoading('verifyOtpBtn','verifyText','verifySpinner','Verify OTP',false);}
document.getElementById('registerForm').addEventListener('submit',async(e)=>{e.preventDefault();const pass=document.getElementById('reg-pass').value;const pass2=document.getElementById('reg-pass2').value;document.getElementById('pass-err').textContent='';if(pass.length<8){document.getElementById('pass-err').textContent='Password must be 8+ characters';return;}if(pass!==pass2){document.getElementById('pass-err').textContent='Passwords do not match';return;}setLoading('registerBtn','regText','regSpinner','Creating...',true);try{const fd=new FormData(e.target);const res=await fetch('../api/auth/register.php',{method:'POST',body:fd});const data=await res.json();if(data.success){showToast('Account created! Redirecting...','success');setTimeout(()=>window.location.href='/myclassroom/student/dashboard.php',1200);}else{showToast(data.message||'Registration failed','error');}}catch(err){showToast('Network error','error');}setLoading('registerBtn','regText','regSpinner','Create Account 🎉',false);});
function togglePwd(id,el){const inp=document.getElementById(id);const show=inp.type==='password';inp.type=show?'text':'password';el.innerHTML=show?'<i class="fa fa-eye-slash"></i>':'<i class="fa fa-eye"></i>';}
</script>
</body>
</html>
