const tabTitles={overview:'Dashboard Overview',tutors:'Tutor Management',students:'Student Management',courses:'Course Management',payments:'Payment Management',logs:'Activity Logs',settings:'Settings'};
let allTutors=[],allStudents=[],allPayments=[],currentPayStatus='pending';

// Core API helper — sends proper headers so PHP isAjax() returns true
async function api(url, opts={}) {
  const headers = Object.assign({
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }, opts.headers || {});
  // Don't set Content-Type for FormData (browser does it)
  if (!(opts.body instanceof FormData || opts.body instanceof URLSearchParams)) {
    headers['Content-Type'] = 'application/json';
  }
  const res = await fetch(url, Object.assign({}, opts, { headers }));
  if (!res.ok && res.status === 401) {
    window.location.href = '../admin/login.php';
    return { success: false };
  }
  const text = await res.text();
  try { return JSON.parse(text); } catch(e) { return { success: false, message: text || 'Server error' }; }
}

function switchTab(name,el){
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.querySelectorAll('.bottom-nav-item').forEach(n=>n.classList.remove('active'));
  if(el)el.classList.add('active');
  document.getElementById('topbarTitle').textContent=tabTitles[name]||name;
  closeSidebar();
  if(name==='overview')loadOverview();
  if(name==='tutors')loadTutors();
  if(name==='students')loadStudents();
  if(name==='courses')loadCourses();
  if(name==='payments')loadPayments('pending');
  if(name==='logs')loadLogs();
}


async function loadOverview(){
  const[p,t]=await Promise.all([api('../api/admin/payments.php?status=pending&limit=5'),api('../api/admin/tutors.php?limit=5')]);
  document.getElementById('admin-pending').innerHTML=(p.data||[]).map(x=>`<div style="padding:10px;border-radius:8px;background:var(--gray-100);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between"><div><div class="fw-600 fs-sm">${x.student_name}</div><div class="fs-xs text-muted">${x.course_title} — Rs.${parseFloat(x.amount).toLocaleString()}</div></div><button class="btn btn-primary btn-sm" onclick="viewPayment(${x.id})">View</button></div>`).join('')||'<div class="empty-state" style="padding:20px"><p>No pending payments</p></div>';
  document.getElementById('admin-tutors-preview').innerHTML=(t.data||[]).map(x=>{const approved = parseInt(x.is_approved,10)===1;return `<div style="padding:10px;border-radius:8px;background:var(--gray-100);margin-bottom:8px;display:flex;align-items:center;gap:10px"><div class="avatar avatar-sm" style="background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0">${(x.first_name||'?')[0]}${(x.last_name||'')[0]}</div><div><div class="fw-600 fs-sm">${x.first_name} ${x.last_name}</div><div class="fs-xs text-muted">${x.subjects||'No subjects'} · ${x.total_students||0} students</div></div><span class="badge ${approved?'badge-green':'badge-yellow'}" style="margin-left:auto">${approved?'Active':'Pending'}</span></div>`}).join('')||'<div class="empty-state" style="padding:20px"><p>No tutors yet</p></div>';
}

async function loadTutors(){
  const d=await api('../api/admin/tutors.php');
  allTutors=d.data||[];renderTutors(allTutors);
}
function filterTutors(){const q=document.getElementById('tutorSearch').value.toLowerCase();renderTutors(allTutors.filter(t=>(t.first_name+' '+t.last_name).toLowerCase().includes(q)||(t.email||'').toLowerCase().includes(q)||(t.phone||'').toLowerCase().includes(q)));}
function renderTutors(list){
  const tb=document.getElementById('tutors-tbody');
  if(!list.length){tb.innerHTML='<tr><td colspan="7" class="text-center text-muted" style="padding:40px">No tutors found</td></tr>';return;}
  tb.innerHTML=list.map(t=>{const suspended = parseInt(t.is_suspended,10)===1;const approved = parseInt(t.is_approved,10)===1;return `<tr><td><div class="fw-600">${t.first_name} ${t.last_name}</div><div class="fs-xs text-muted">${t.display_name||''}</div></td><td class="fs-sm">${t.email||'-'}</td><td class="fs-sm">${t.phone}</td><td class="fs-sm">${t.subjects||'-'}</td><td>${t.total_students||0}</td><td><span class="badge ${suspended?'badge-red':approved?'badge-green':'badge-yellow'}">${suspended?'Suspended':approved?'Active':'Pending'}</span></td><td style="display:flex;gap:4px;flex-wrap:wrap">${!approved?`<button class="btn btn-success btn-sm" onclick="approveTutor(${t.id})">✅ Approve</button>`:''}${!suspended?`<button class="btn btn-sm" style="background:#fb8c00;color:#fff" onclick="suspendTutor(${t.id},1)">⛔ Suspend</button>`:`<button class="btn btn-success btn-sm" onclick="suspendTutor(${t.id},0)">✅ Unsuspend</button>`}<button class="btn btn-outline btn-sm" onclick="editTutor(${t.id})">✏️</button><button class="btn btn-danger btn-sm" onclick="deleteTutor(${t.id})">🗑️</button></td></tr>`}).join('');
}

async function loadStudents(){
  const d=await api('../api/admin/students.php');
  allStudents=d.data||[];renderStudents(allStudents);
}
function filterStudents(){const q=document.getElementById('studentSearch').value.toLowerCase();renderStudents(allStudents.filter(s=>(s.first_name+' '+s.last_name).toLowerCase().includes(q)||(s.phone||'').includes(q)||(s.student_id||'').includes(q)));}
function renderStudents(list){
  const tb=document.getElementById('students-tbody');
  if(!list.length){tb.innerHTML='<tr><td colspan="7" class="text-center text-muted" style="padding:40px">No students found</td></tr>';return;}
  tb.innerHTML=list.map(s=>{const suspended = parseInt(s.is_suspended,10)===1;return `<tr><td><div class="fw-600">${s.first_name} ${s.last_name}</div></td><td class="fs-sm">${s.phone}</td><td class="fs-sm">${s.student_id||'-'}</td><td class="fs-xs">${new Date(s.created_at).toLocaleDateString()}</td><td>${s.course_count||0}</td><td><span class="badge ${suspended?'badge-red':'badge-green'}">${suspended?'Suspended':'Active'}</span></td><td>${suspended?`<button class="btn btn-success btn-sm" onclick="suspendStudent(${s.id},0)">✅ Restore</button>`:`<button class="btn btn-sm" style="background:#fb8c00;color:#fff" onclick="suspendStudent(${s.id},1)">⛔ Suspend</button>`}</td></tr>`}).join('');
}

async function loadCourses(){
  const d=await api('../api/admin/courses.php');
  const tb=document.getElementById('courses-tbody');
  if(!(d.data||[]).length){tb.innerHTML='<tr><td colspan="6" class="text-center text-muted" style="padding:40px">No courses</td></tr>';return;}
  tb.innerHTML=d.data.map(c=>{const active = parseInt(c.is_active,10)===1;return `<tr><td class="fw-600">${c.title}</td><td class="fs-sm">${c.tutor_name}</td><td class="fw-600 text-primary">Rs.${parseFloat(c.price||0).toLocaleString()}</td><td>${c.total_students||0}</td><td><span class="badge ${active?'badge-green':'badge-gray'}">${active?'Active':'Inactive'}</span></td><td><button class="btn btn-danger btn-sm" onclick="adminDeleteCourse(${c.id})">🗑️ Delete</button></td></tr>`}).join('');
}

async function loadPayments(status='pending'){
  currentPayStatus=status;
  const d=await api(`../api/admin/payments.php?status=${status}`);
  const tb=document.getElementById('admin-payments-tbody');
  if(!(d.data||[]).length){tb.innerHTML=`<tr><td colspan="9" class="text-center text-muted" style="padding:40px">No ${status} payments</td></tr>`;return;}
  tb.innerHTML=d.data.map(p=>`<tr><td class="fw-600 fs-sm">${p.student_name}</td><td class="fs-sm">${p.course_title}</td><td class="fs-sm">${p.tutor_name}</td><td class="fw-600 text-primary">Rs.${parseFloat(p.amount).toLocaleString()}</td><td class="fs-sm">${p.payment_reference||'-'}</td><td>${p.payment_slip?`<a href="${p.payment_slip}" target="_blank" class="btn btn-outline btn-sm"><i class="fa fa-image"></i></a>`:'-'}</td><td class="fs-xs">${new Date(p.created_at).toLocaleDateString()}</td><td><span class="badge ${p.status==='approved'?'badge-green':p.status==='rejected'?'badge-red':'badge-yellow'}">${p.status}</span></td><td>${p.status==='pending'?`<button class="btn btn-success btn-sm" onclick="adminApprovePayment(${p.id})">✅</button> <button class="btn btn-danger btn-sm" onclick="adminRejectPayment(${p.id})">❌</button>`:'-'}</td></tr>`).join('');
}
function adminPayFilter(status,el){document.querySelectorAll('#tab-payments .nav-tab').forEach(t=>t.classList.remove('active'));if(el)el.classList.add('active');loadPayments(status);}

async function viewPayment(id){
  const d=await api(`../api/admin/payments.php?id=${id}`);
  const p=d.data;
  document.getElementById('adminPaymentBody').innerHTML=`<div class="mb-16"><div class="profile-info-row"><span class="profile-info-label">Student</span><span class="profile-info-value">${p.student_name}</span></div><div class="profile-info-row"><span class="profile-info-label">Course</span><span class="profile-info-value">${p.course_title}</span></div><div class="profile-info-row"><span class="profile-info-label">Tutor</span><span class="profile-info-value">${p.tutor_name}</span></div><div class="profile-info-row"><span class="profile-info-label">Amount</span><span class="profile-info-value text-primary fw-700">Rs. ${parseFloat(p.amount).toLocaleString()}</span></div><div class="profile-info-row"><span class="profile-info-label">Reference</span><span class="profile-info-value">${p.payment_reference||'-'}</span></div></div>${p.payment_slip?`<img src="${p.payment_slip}" style="width:100%;max-height:280px;object-fit:contain;border-radius:8px;margin-bottom:16px">`:'<div class="alert alert-info mb-16">No slip</div>'}<div style="display:flex;gap:10px">${p.status==='pending'?`<button class="btn btn-success btn-full" onclick="adminApprovePayment(${p.id})">✅ Approve</button><button class="btn btn-danger btn-full" onclick="adminRejectPayment(${p.id})">❌ Reject</button>`:'<p class="text-muted text-center w-full">Already reviewed</p>'}</div>`;
  openModal('adminPaymentModal');
}

async function adminApprovePayment(id){
  if(!confirm('Approve this payment?'))return;
  const d=await api('../api/admin/review-payment.php',{method:'POST',body:new URLSearchParams({id,action:'approve'})});
  if(d.success){showToast('Payment approved!','success');closeModal('adminPaymentModal');loadPayments(currentPayStatus);document.getElementById('pendingBadge').textContent=Math.max(0,parseInt(document.getElementById('pendingBadge').textContent||0)-1);}
  else showToast(d.message||'Failed','error');
}
async function adminRejectPayment(id){
  const reason=prompt('Rejection reason (optional):');
  const fd=new FormData();fd.append('id',id);fd.append('action','reject');if(reason)fd.append('reason',reason);
  const d=await api('../api/admin/review-payment.php',{method:'POST',body:fd});
  if(d.success){showToast('Payment rejected','success');closeModal('adminPaymentModal');loadPayments(currentPayStatus);}
  else showToast(d.message||'Failed','error');
}

async function approveTutor(id){
  const d=await api('../api/admin/update-tutor.php',{method:'POST',body:new URLSearchParams({id,action:'approve'})});
  if(d.success){showToast('Tutor approved!','success');loadTutors();}else showToast(d.message||'Failed','error');
}
async function suspendTutor(id,suspend){
  const d=await api('../api/admin/update-tutor.php',{method:'POST',body:new URLSearchParams({id,action:suspend?'suspend':'unsuspend'})});
  if(d.success){showToast(suspend?'Tutor suspended':'Tutor restored','success');loadTutors();}else showToast(d.message||'Failed','error');
}
async function deleteTutor(id){
  if(!confirm('Delete this tutor? All their courses will also be deleted.'))return;
  const d=await api('../api/admin/delete-tutor.php',{method:'POST',body:new URLSearchParams({id})});
  if(d.success){showToast('Tutor deleted','success');loadTutors();}else showToast(d.message||'Failed','error');
}
function editTutor(id){
  const t=allTutors.find(x=>x.id==id);if(!t)return;
  document.getElementById('tutorModalLabel').textContent='Edit Tutor';
  document.getElementById('editTutorId').value=id;
  const f=document.getElementById('createTutorForm');
  f.first_name.value=t.first_name;f.last_name.value=t.last_name;
  f.email.value=t.email||'';f.phone.value=t.phone;
  f.subjects.value=t.subjects||'';f.display_name.value=t.display_name||'';
  f.password.required=false;f.password.placeholder='Leave blank to keep';
  openModal('createTutorModal');
}

async function suspendStudent(id,suspend){
  const d=await api('../api/admin/update-student.php',{method:'POST',body:new URLSearchParams({id,action:suspend?'suspend':'unsuspend'})});
  if(d.success){showToast(suspend?'Student suspended':'Student restored','success');loadStudents();}else showToast(d.message||'Failed','error');
}
async function adminDeleteCourse(id){
  if(!confirm('Delete this course?'))return;
  const d=await api('../api/admin/delete-course.php',{method:'POST',body:new URLSearchParams({id})});
  if(d.success){showToast('Course deleted','success');loadCourses();}else showToast(d.message||'Failed','error');
}

async function loadLogs(){
  const d=await api('../api/admin/logs.php');
  const tb=document.getElementById('logs-tbody');
  if(!(d.data||[]).length){tb.innerHTML='<tr><td colspan="6" class="text-center text-muted" style="padding:40px">No logs</td></tr>';return;}
  tb.innerHTML=d.data.map(l=>`<tr><td class="fw-600 fs-sm">${l.user_id||'-'}</td><td><span class="badge badge-gray">${l.user_type||'-'}</span></td><td class="fs-sm">${l.action}</td><td class="fs-sm text-muted">${l.description||'-'}</td><td class="fs-xs">${l.ip_address||'-'}</td><td class="fs-xs">${new Date(l.created_at).toLocaleString()}</td></tr>`).join('');
}

document.getElementById('createTutorForm').addEventListener('submit',async e=>{
  e.preventDefault();const btn=e.target.querySelector('button[type=submit]');btn.disabled=true;
  const d=await api('../api/admin/save-tutor.php',{method:'POST',body:new FormData(e.target)});
  if(d.success){showToast('Tutor saved!','success');closeModal('createTutorModal');e.target.reset();document.getElementById('editTutorId').value='';document.getElementById('tutorModalLabel').textContent='Create Tutor Account';loadTutors();}
  else showToast(d.message||'Failed','error');btn.disabled=false;
});

document.getElementById('adminSettingsForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await api('../api/admin/update-password.php',{method:'POST',body:new FormData(e.target)});
  if(d.success){showToast('Password updated!','success');e.target.reset();}else showToast(d.message||'Failed','error');
});

loadOverview();
