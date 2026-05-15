const tabTitles={overview:'Dashboard Overview',courses:'My Courses',students:'My Students',payments:'Payments',live:'Live Classes',recordings:'Recordings',materials:'Materials',announcements:'Announcements',profile:'My Profile',settings:'Settings'};
let myCourses=[];

function switchTab(name,el){
  document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.querySelectorAll('.bottom-nav-item').forEach(n=>n.classList.remove('active'));
  if(el)el.classList.add('active');
  document.getElementById('topbarTitle').textContent=tabTitles[name]||name;
  closeSidebar();
  if(name==='overview')loadOverview();
  if(name==='courses')loadCourses();
  if(name==='students')loadStudents();
  if(name==='payments')loadPayments('pending');
  if(name==='live')loadZoom();
  if(name==='recordings')loadRecordings();
  if(name==='materials')loadMaterials();
  if(name==='announcements')loadAnnouncements();
}

async function api(url, opts={}) {
  const headers = Object.assign({
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }, opts.headers || {});
  if (opts.body instanceof FormData || opts.body instanceof URLSearchParams) {
    delete headers['Content-Type'];
  }
  const res = await fetch(url, Object.assign({}, opts, { headers }));
  if (!res.ok && res.status === 401) {
    window.location.href = '/myclassroom/admin/login.php';
    return { success: false };
  }
  try { return await res.json(); } catch(e) { return { success: false, message: 'Server error' }; }
}

async function loadOverview(){
  const [p,s]=await Promise.all([api('../api/tutor/payments.php?status=pending'),api('../api/tutor/students.php?limit=5')]);
  const pp=document.getElementById('pending-payments-list');
  pp.innerHTML=(p.data||[]).slice(0,5).map(x=>`<div style="padding:10px;border-radius:8px;background:var(--gray-100);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between"><div><div class="fw-600 fs-sm">${x.student_name}</div><div class="fs-xs text-muted">${x.course_title} — Rs.${parseFloat(x.amount).toLocaleString()}</div></div><button class="btn btn-primary btn-sm" onclick="reviewPayment(${x.id})">Review</button></div>`).join('')||'<div class="empty-state" style="padding:20px"><p>No pending payments</p></div>';
  const ss=document.getElementById('recent-students-list');
  ss.innerHTML=(s.data||[]).map(x=>`<div style="padding:10px;border-radius:8px;background:var(--gray-100);margin-bottom:8px;display:flex;align-items:center;gap:10px"><div class="avatar avatar-sm" style="background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.8rem;flex-shrink:0">${(x.first_name||'?')[0]}${(x.last_name||'')[0]}</div><div><div class="fw-600 fs-sm">${x.first_name} ${x.last_name}</div><div class="fs-xs text-muted">${x.course_title}</div></div></div>`).join('')||'<div class="empty-state" style="padding:20px"><p>No students yet</p></div>';
}

async function loadCourses(){
  const d=await api('../api/tutor/courses.php');
  myCourses=d.data||[];
  populateCourseSelects();
  const el=document.getElementById('courses-grid');
  if(!myCourses.length){el.innerHTML='<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">📚</div><h3>No courses yet</h3><button class="btn btn-primary mt-16" onclick="openModal(\'addCourseModal\')">Add Your First Course</button></div>';return;}
  el.innerHTML=myCourses.map(c=>`<div class="course-card"><div class="course-card-thumb">${c.thumbnail?`<img src="${c.thumbnail}" style="width:100%;height:100%;object-fit:cover">`:'📚'}</div><div class="course-card-body"><div class="course-card-title">${c.title}</div><div class="fs-xs text-muted mt-4">${c.duration||''}  ${c.level}</div><div class="course-card-price">Rs. ${parseFloat(c.price||0).toLocaleString()}</div><div class="mt-8"><span class="badge ${c.is_active?'badge-green':'badge-gray'}">${c.is_active?'Active':'Inactive'}</span></div></div><div class="course-card-footer" style="gap:6px"><button class="btn btn-outline btn-sm" onclick="editCourse(${c.id})"><i class="fa fa-edit"></i></button><button class="btn btn-danger btn-sm" onclick="deleteCourse(${c.id})"><i class="fa fa-trash"></i></button></div></div>`).join('');
}

function populateCourseSelects(){
  ['zoomCourseSelect','recCourseSelect','matCourseSelect','annCourseSelect'].forEach(id=>{
    const sel=document.getElementById(id);
    if(sel)sel.innerHTML=myCourses.map(c=>`<option value="${c.id}">${c.title}</option>`).join('');
  });
}

async function loadStudents(){
  const d=await api('../api/tutor/students.php');
  const tb=document.getElementById('students-tbody');
  if(!(d.data||[]).length){tb.innerHTML='<tr><td colspan="5" class="text-center text-muted" style="padding:40px">No students yet</td></tr>';return;}
  tb.innerHTML=d.data.map(s=>`<tr><td><div class="fw-600">${s.first_name} ${s.last_name}</div><div class="fs-xs text-muted">${s.student_id||''}</div></td><td>${s.phone}</td><td>${s.course_title}</td><td class="fs-xs">${new Date(s.enrolled_at).toLocaleDateString()}</td><td><div class="progress-bar" style="min-width:80px"><div class="progress-fill" style="width:${s.progress||0}%"></div></div><span class="fs-xs">${s.progress||0}%</span></td></tr>`).join('');
}

let currentPayFilter='pending';
function payFilter(status,el){
  currentPayFilter=status;
  document.querySelectorAll('#tab-payments .nav-tab').forEach(t=>t.classList.remove('active'));
  if(el)el.classList.add('active');
  loadPayments(status);
}

async function loadPayments(status='pending'){
  const d=await api(`../api/tutor/payments.php?status=${status}`);
  const tb=document.getElementById('payments-tbody');
  if(!(d.data||[]).length){tb.innerHTML=`<tr><td colspan="8" class="text-center text-muted" style="padding:40px">No ${status} payments</td></tr>`;return;}
  tb.innerHTML=d.data.map(p=>`<tr><td><div class="fw-600 fs-sm">${p.student_name}</div></td><td class="fs-sm">${p.course_title}</td><td class="fw-600 text-primary">Rs.${parseFloat(p.amount).toLocaleString()}</td><td class="fs-sm">${p.payment_reference||'-'}</td><td>${p.payment_slip?`<a href="${p.payment_slip}" target="_blank" class="btn btn-outline btn-sm"><i class="fa fa-image"></i> View</a>`:'-'}</td><td class="fs-xs">${new Date(p.created_at).toLocaleDateString()}</td><td><span class="badge ${p.status==='approved'?'badge-green':p.status==='rejected'?'badge-red':'badge-yellow'}">${p.status}</span></td><td>${p.status==='pending'?`<button class="btn btn-success btn-sm" onclick="approvePayment(${p.id})">✅</button> <button class="btn btn-danger btn-sm" onclick="rejectPayment(${p.id})">❌</button>`:'-'}</td></tr>`).join('');
}

async function reviewPayment(id){
  const d=await api(`../api/tutor/payments.php?id=${id}`);
  const p=d.data;
  document.getElementById('reviewPaymentBody').innerHTML=`<div class="mb-16"><div class="profile-info-row"><span class="profile-info-label">Student</span><span class="profile-info-value">${p.student_name}</span></div><div class="profile-info-row"><span class="profile-info-label">Course</span><span class="profile-info-value">${p.course_title}</span></div><div class="profile-info-row"><span class="profile-info-label">Amount</span><span class="profile-info-value text-primary fw-700">Rs. ${parseFloat(p.amount).toLocaleString()}</span></div><div class="profile-info-row"><span class="profile-info-label">Reference</span><span class="profile-info-value">${p.payment_reference||'-'}</span></div><div class="profile-info-row"><span class="profile-info-label">Notes</span><span class="profile-info-value">${p.notes||'-'}</span></div></div>${p.payment_slip?`<img src="${p.payment_slip}" style="width:100%;max-height:300px;object-fit:contain;border-radius:8px;margin-bottom:16px">`:'<div class="alert alert-info mb-16">No slip uploaded</div>'}<div style="display:flex;gap:10px"><button class="btn btn-success btn-full" onclick="approvePayment(${p.id})">✅ Approve</button><button class="btn btn-danger btn-full" onclick="rejectPayment(${p.id})">❌ Reject</button></div>`;
  openModal('reviewPaymentModal');
}

async function approvePayment(id){
  if(!confirm('Approve this payment?'))return;
  const d=await api('../api/tutor/review-payment.php',{method:'POST',body:new URLSearchParams({id,action:'approve'})});
  if(d.success){showToast('Payment approved! Course unlocked.','success');closeModal('reviewPaymentModal');loadPayments(currentPayFilter);loadOverview();}
  else showToast(d.message||'Failed','error');
}

async function rejectPayment(id){
  const reason=prompt('Reason for rejection (optional):');
  const fd=new FormData();fd.append('id',id);fd.append('action','reject');if(reason)fd.append('reason',reason);
  const d=await api('../api/tutor/review-payment.php',{method:'POST',body:fd});
  if(d.success){showToast('Payment rejected.','success');closeModal('reviewPaymentModal');loadPayments(currentPayFilter);}
  else showToast(d.message||'Failed','error');
}

async function loadZoom(){
  const d=await api('../api/tutor/zoom.php');
  const el=document.getElementById('zoom-list');
  if(!(d.data||[]).length){el.innerHTML='<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">📹</div><h3>No live classes yet</h3></div>';return;}
  el.innerHTML=d.data.map(z=>`<div class="card p-16"><div class="flex-between mb-8"><div class="fw-600">${z.title}</div><button class="btn btn-danger btn-sm" onclick="deleteItem('zoom',${z.id})"><i class="fa fa-trash"></i></button></div><div class="fs-sm text-muted">${z.course_title}</div>${z.scheduled_at?`<div class="fs-xs mt-4">📅 ${new Date(z.scheduled_at).toLocaleString()}</div>`:''}<a href="${z.zoom_url}" target="_blank" class="btn btn-primary btn-sm mt-8"><i class="fa fa-video"></i> Open</a></div>`).join('');
}

async function loadRecordings(){
  const d=await api('../api/tutor/recordings.php');
  const el=document.getElementById('recordings-list');
  if(!(d.data||[]).length){el.innerHTML='<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">🎬</div><h3>No recordings yet</h3></div>';return;}
  el.innerHTML=d.data.map(r=>`<div class="card p-16"><div class="flex-between mb-8"><div class="fw-600">${r.title}</div><button class="btn btn-danger btn-sm" onclick="deleteItem('recording',${r.id})"><i class="fa fa-trash"></i></button></div><div class="fs-sm text-muted">${r.course_title}</div><a href="${r.video_url}" target="_blank" class="btn btn-outline btn-sm mt-8"><i class="fa fa-play"></i> Preview</a></div>`).join('');
}

async function loadMaterials(){
  const d=await api('../api/tutor/materials.php');
  const el=document.getElementById('materials-list');
  if(!(d.data||[]).length){el.innerHTML='<div class="empty-state"><div class="empty-state-icon">📁</div><h3>No materials yet</h3></div>';return;}
  el.innerHTML='<div class="card"><div class="table-wrap"><table><thead><tr><th>Title</th><th>Course</th><th>Type</th><th>Date</th><th>Action</th></tr></thead><tbody>'+d.data.map(m=>`<tr><td class="fw-600">${m.title}</td><td class="fs-sm">${m.course_title}</td><td class="fs-xs">${m.file_type||'-'}</td><td class="fs-xs">${new Date(m.created_at).toLocaleDateString()}</td><td><a href="${m.file_path}" target="_blank" class="btn btn-outline btn-sm"><i class="fa fa-download"></i></a> <button class="btn btn-danger btn-sm" onclick="deleteItem(\'material\',${m.id})"><i class="fa fa-trash"></i></button></td></tr>`).join('')+'</tbody></table></div></div>';
}

async function loadAnnouncements(){
  const d=await api('../api/tutor/announcements.php');
  const el=document.getElementById('announcements-list');
  if(!(d.data||[]).length){el.innerHTML='<div class="empty-state"><div class="empty-state-icon">📢</div><h3>No announcements yet</h3></div>';return;}
  el.innerHTML=d.data.map(a=>`<div class="card p-16 mb-12"><div class="flex-between mb-8"><div class="fw-600">${a.title}</div><div style="display:flex;gap:8px"><span class="fs-xs text-muted">${new Date(a.created_at).toLocaleDateString()}</span><button class="btn btn-danger btn-sm" onclick="deleteItem('announcement',${a.id})"><i class="fa fa-trash"></i></button></div></div><div class="fs-sm text-muted">${a.course_title}</div><p class="fs-sm mt-8">${a.content}</p></div>`).join('');
}

async function deleteItem(type,id){
  if(!confirm('Delete this item?'))return;
  const d=await api(`../api/tutor/delete-item.php`,{method:'POST',body:new URLSearchParams({type,id})});
  if(d.success){showToast('Deleted','success');if(type==='zoom')loadZoom();else if(type==='recording')loadRecordings();else if(type==='material')loadMaterials();else if(type==='announcement')loadAnnouncements();}
  else showToast(d.message||'Failed','error');
}

async function deleteCourse(id){
  if(!confirm('Delete this course? This will remove all related content.'))return;
  const d=await api('../api/tutor/delete-course.php',{method:'POST',body:new URLSearchParams({id})});
  if(d.success){showToast('Course deleted','success');loadCourses();}
  else showToast(d.message||'Failed','error');
}

async function editCourse(id){
  const course=myCourses.find(c=>c.id==id);if(!course)return;
  document.getElementById('courseModalLabel').textContent='Edit Course';
  document.getElementById('editCourseId').value=id;
  const f=document.getElementById('courseForm');
  f.title.value=course.title;f.description.value=course.description||'';
  f.price.value=course.price||0;f.monthly_fee.value=course.monthly_fee||0;
  f.duration.value=course.duration||'';f.level.value=course.level||'beginner';
  openModal('addCourseModal');
}

// Form handlers
document.getElementById('courseForm').addEventListener('submit',async e=>{
  e.preventDefault();const btn=e.target.querySelector('button[type=submit]');btn.disabled=true;
  const d=await api('../api/tutor/save-course.php',{method:'POST',body:new FormData(e.target)});
  if(d.success){showToast('Course saved!','success');closeModal('addCourseModal');e.target.reset();document.getElementById('editCourseId').value='';loadCourses();}
  else showToast(d.message||'Failed','error');btn.disabled=false;
});

['zoomForm','recordingForm','materialForm','announcementForm'].forEach(fid=>{
  document.getElementById(fid).addEventListener('submit',async e=>{
    e.preventDefault();const btn=e.target.querySelector('button[type=submit]');btn.disabled=true;
    const urls={zoomForm:'../api/tutor/save-zoom.php',recordingForm:'../api/tutor/save-recording.php',materialForm:'../api/tutor/save-material.php',announcementForm:'../api/tutor/save-announcement.php'};
    const loaders={zoomForm:loadZoom,recordingForm:loadRecordings,materialForm:loadMaterials,announcementForm:loadAnnouncements};
    const modals={zoomForm:'addZoomModal',recordingForm:'addRecordingModal',materialForm:'addMaterialModal',announcementForm:'addAnnouncementModal'};
    const d=await api(urls[fid],{method:'POST',body:new FormData(e.target)});
    if(d.success){showToast('Saved!','success');closeModal(modals[fid]);e.target.reset();loaders[fid]();}
    else showToast(d.message||'Failed','error');btn.disabled=false;
  });
});

document.getElementById('profileForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await api('../api/tutor/update-profile.php',{method:'POST',body:new FormData(e.target)});
  if(d.success)showToast('Profile updated!','success');else showToast(d.message||'Failed','error');
});

document.getElementById('settingsForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const d=await api('../api/tutor/update-settings.php',{method:'POST',body:new FormData(e.target)});
  if(d.success)showToast('Settings saved!','success');else showToast(d.message||'Failed','error');
});

async function uploadTutorAvatar(input){
  const fd=new FormData();fd.append('profile_image',input.files[0]);
  const d=await api('../api/tutor/upload-image.php',{method:'POST',body:fd});
  if(d.success){showToast('Photo updated','success');location.reload();}else showToast(d.message||'Failed','error');
}
async function uploadBanner(input){
  const fd=new FormData();fd.append('banner_image',input.files[0]);
  const d=await api('../api/tutor/upload-image.php',{method:'POST',body:fd});
  if(d.success){showToast('Banner updated','success');location.reload();}else showToast(d.message||'Failed','error');
}

loadOverview();loadCourses();
