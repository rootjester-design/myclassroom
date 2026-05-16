// ── Tab Switching ──────────────────────────────────────
const tabTitles = {home:'Home',courses:'My Courses',store:'Store',settings:'Settings'};
let currentTab = 'home';
let storeData = {tutors:[], courses:[]};
let storeCurrentView = 'tutors';

function switchTab(name, el) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  const tab = document.getElementById('tab-'+name);
  if (tab) tab.classList.add('active');

  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.querySelectorAll('.bottom-nav-item').forEach(n => n.classList.remove('active'));
  if (el) el.classList.add('active');

  const titleEl = document.getElementById('topbarTitle');
  if (titleEl) titleEl.textContent = tabTitles[name] || name;
  currentTab = name;
  closeSidebar();
  closeProfile();

  if (name==='home') loadHome();
  if (name==='courses') loadMyCourses();
  if (name==='store') loadStore();
}

// ── Home ───────────────────────────────────────────────
async function loadHome() {
  try {
    const [annRes, zoomRes, progRes] = await Promise.all([
      fetch('../api/student/announcements.php'),
      fetch('../api/student/upcoming-zoom.php'),
      fetch('../api/student/progress.php')
    ]);
    const [ann, zoom, prog] = await Promise.all([annRes.json(), zoomRes.json(), progRes.json()]);
    renderAnnouncements(ann.data || []);
    renderZoom(zoom.data || []);
    renderProgress(prog.data || []);
  } catch(e) { console.error(e); }
}

function renderAnnouncements(list) {
  const el = document.getElementById('home-announcements');
  if (!list.length) { el.innerHTML='<div class="empty-state"><div class="empty-state-icon">📢</div><p>No announcements yet</p></div>'; return; }
  el.innerHTML = list.slice(0,3).map(a=>`
    <div style="padding:12px;border-radius:10px;background:var(--gray-100);margin-bottom:8px">
      <div class="flex-between"><strong class="fs-sm">${a.title}</strong><span class="fs-xs text-muted">${timeAgo(a.created_at)}</span></div>
      <p class="fs-sm text-muted mt-8">${a.content.substring(0,100)}${a.content.length>100?'...':''}</p>
    </div>`).join('');
}

function renderZoom(list) {
  const el = document.getElementById('home-zoom');
  if (!list.length) { el.innerHTML='<div class="empty-state"><div class="empty-state-icon">🎬</div><p>No upcoming classes</p></div>'; return; }
  el.innerHTML = list.slice(0,3).map(z=>`
    <div style="padding:12px;border-radius:10px;background:var(--gray-100);margin-bottom:8px">
      <div class="fw-600 fs-sm">${z.title}</div>
      <div class="fs-xs text-muted mt-4">📅 ${new Date(z.scheduled_at).toLocaleString()}</div>
      <a href="${z.zoom_url}" target="_blank" class="btn btn-primary btn-sm mt-8">Join Class</a>
    </div>`).join('');
}

function renderProgress(list) {
  const el = document.getElementById('home-progress');
  if (!list.length) { el.innerHTML='<div class="empty-state"><div class="empty-state-icon">📊</div><p>No courses enrolled yet. <span class="auth-link" onclick="switchTab(\'store\',null)">Browse Store</span></p></div>'; return; }
  el.innerHTML = list.slice(0,6).map(c=>`
    <div class="card p-16">
      <div class="fw-600 fs-sm mb-8">${c.title}</div>
      <div class="fs-xs text-muted mb-8">${c.tutor_name}</div>
      <div class="progress-bar"><div class="progress-fill" style="width:${c.progress||0}%"></div></div>
      <div class="flex-between mt-8"><span class="fs-xs text-muted">${c.progress||0}% complete</span><button class="btn btn-primary btn-sm" onclick="openCourseDetail(${c.id})">Enter</button></div>
    </div>`).join('');
}

// ── My Courses ─────────────────────────────────────────
async function loadMyCourses() {
  const el = document.getElementById('courses-list');
  el.innerHTML = '<div class="skeleton" style="height:220px"></div><div class="skeleton" style="height:220px"></div>';
  try {
    const res = await fetch('../api/student/courses.php');
    const data = await res.json();
    if (!data.data?.length) { el.innerHTML='<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">📚</div><h3>No courses yet</h3><p>Visit the Store to enroll</p><button class="btn btn-primary mt-16" onclick="switchTab(\'store\',null)">Browse Store</button></div>'; return; }
    el.innerHTML = data.data.map(c=>`
      <div class="course-card">
        <div class="course-card-thumb">${c.thumbnail?`<img src="${c.thumbnail}" style="width:100%;height:100%;object-fit:cover">`:'📚'}</div>
        <div class="course-card-body">
          <div class="course-card-title">${c.title}</div>
          <div class="course-card-tutor"><i class="fa fa-user-tie"></i> ${c.tutor_name}</div>
          <div class="mt-12"><div class="flex-between mb-4"><span class="fs-xs text-muted">Progress</span><span class="fs-xs fw-600">${c.progress||0}%</span></div>
          <div class="progress-bar"><div class="progress-fill" style="width:${c.progress||0}%"></div></div></div>
          <div class="mt-12"><span class="badge ${c.status==='active'?'badge-green':'badge-yellow'}">${c.status}</span></div>
        </div>
        <div class="course-card-footer"><button class="btn btn-primary btn-sm btn-full" onclick="openCourseDetail(${c.id})">Enter Course</button></div>
      </div>`).join('');
  } catch(e) { el.innerHTML='<div class="empty-state">Failed to load courses</div>'; }
}

// ── Course Detail ──────────────────────────────────────
async function openCourseDetail(courseId) {
  window.location.href = `../student/course.php?id=${courseId}`;
}

async function loadCoursePage() {
  const container = document.getElementById('coursePageBody');
  const title = document.getElementById('coursePageTitle');
  if (!container || !title) return;
  const params = new URLSearchParams(window.location.search);
  const courseId = parseInt(params.get('id') || '', 10);
  if (!courseId) {
    title.textContent = 'Course not found';
    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">❌</div><p>Course not found.</p></div>';
    return;
  }
  title.textContent = 'Loading course...';
  container.innerHTML = '<div class="flex-center" style="padding:40px"><div class="spinner"></div></div>';
  try {
    const res = await fetch(`../api/student/course-detail.php?id=${courseId}`);
    const data = await res.json();
    if (!data.success) {
      title.textContent = 'Course unavailable';
      container.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
      return;
    }
    const c = data.course;
    title.textContent = c.title;
    container.innerHTML = `
      <div class="course-detail-tabs" id="cdTabs">
        <button class="course-tab-btn active" onclick="cdTab('recordings',this)">🎬 Recordings</button>
        <button class="course-tab-btn" onclick="cdTab('zoom',this)">📹 Live Classes</button>
        <button class="course-tab-btn" onclick="cdTab('materials',this)">📁 Materials</button>
        <button class="course-tab-btn" onclick="cdTab('announcements',this)">📢 Announcements</button>
        <button class="course-tab-btn" onclick="cdTab('assignments',this)">📝 Assignments</button>
        <button class="course-tab-btn" onclick="cdTab('notes',this)">📓 Notes</button>
      </div>
      <div class="card p-20 mb-20">
        <div class="flex-between flex-wrap" style="gap:12px">
          <div>
            <div class="fw-700 fs-lg">${c.title}</div>
            <div class="fs-sm text-muted">Tutor: ${c.tutor_name}</div>
            <div class="fs-sm text-muted mt-8">${c.description||''}</div>
          </div>
          <div class="text-right">
            <div class="badge badge-green">Enrolled</div>
            <div class="fs-sm text-muted mt-8">Progress: ${c.progress||0}%</div>
          </div>
        </div>
      </div>
      <div id="cd-recordings">${renderRecordings(data.recordings||[])}</div>
      <div id="cd-zoom" class="hidden">${renderZoomList(data.zoom||[])}</div>
      <div id="cd-materials" class="hidden">${renderMaterials(data.materials||[])}</div>
      <div id="cd-announcements" class="hidden">${renderCourseAnn(data.announcements||[])}</div>
      <div id="cd-assignments" class="hidden">${renderAssignments(data.assignments||[])}</div>
      <div id="cd-notes" class="hidden">${renderNotes(data.notes||[], courseId)}</div>`;
  } catch(e) {
    title.textContent = 'Failed to load course';
    container.innerHTML = '<div class="alert alert-error">Failed to load course content.</div>';
  }
}

document.addEventListener('DOMContentLoaded', loadCoursePage);

function cdTab(name, el) {
  document.querySelectorAll('#courseModalBody [id^="cd-"]').forEach(t=>t.classList.add('hidden'));
  document.querySelectorAll('.course-tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('cd-'+name).classList.remove('hidden');
  if(el) el.classList.add('active');
}

function renderRecordings(list) {
  if(!list.length) return '<div class="empty-state"><div class="empty-state-icon">🎬</div><p>No recordings yet</p></div>';
  return list.map(r=>`<div class="card p-16 mb-12"><div class="flex-between"><div><div class="fw-600">${r.title}</div><div class="fs-xs text-muted mt-4">${r.description||''}</div></div><a href="${r.video_url}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-play"></i> Watch</a></div></div>`).join('');
}
function renderZoomList(list) {
  if(!list.length) return '<div class="empty-state"><div class="empty-state-icon">📹</div><p>No live classes scheduled</p></div>';
  return list.map(z=>`<div class="card p-16 mb-12"><div class="flex-between flex-wrap" style="gap:10px"><div><div class="fw-600">${z.title}</div><div class="fs-xs text-muted mt-4">📅 ${new Date(z.scheduled_at).toLocaleString()}  🕐 ${z.duration_minutes}min</div>${z.meeting_id?`<div class="fs-xs text-muted">ID: ${z.meeting_id} | Pass: ${z.passcode||'N/A'}</div>`:''}</div><a href="${z.zoom_url}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-video"></i> Join</a></div></div>`).join('');
}
function renderMaterials(list) {
  if(!list.length) return '<div class="empty-state"><div class="empty-state-icon">📁</div><p>No materials yet</p></div>';
  return list.map(m=>`<div class="card p-16 mb-8"><div class="flex-between"><div><div class="fw-600">${m.title}</div><div class="fs-xs text-muted">${m.description||''}</div></div><a href="${m.file_path}" target="_blank" download class="btn btn-outline btn-sm"><i class="fa fa-download"></i> Download</a></div></div>`).join('');
}
function renderCourseAnn(list) {
  if(!list.length) return '<div class="empty-state"><div class="empty-state-icon">📢</div><p>No announcements</p></div>';
  return list.map(a=>`<div class="card p-16 mb-8"><div class="fw-600">${a.title}</div><p class="fs-sm text-muted mt-8">${a.content}</p><div class="fs-xs text-muted mt-8">${timeAgo(a.created_at)}</div></div>`).join('');
}
function renderAssignments(list) {
  if(!list.length) return '<div class="empty-state"><div class="empty-state-icon">📝</div><p>No assignments</p></div>';
  return list.map(a=>`<div class="card p-16 mb-8"><div class="flex-between"><div><div class="fw-600">${a.title}</div><p class="fs-sm text-muted mt-4">${a.description||''}</p>${a.due_date?`<div class="fs-xs text-primary mt-4">Due: ${new Date(a.due_date).toLocaleDateString()}</div>`:''}</div>${a.file_path?`<a href="${a.file_path}" target="_blank" download class="btn btn-outline btn-sm"><i class="fa fa-download"></i></a>`:''}</div></div>`).join('');
}
function renderNotes(list, courseId) {
  return `<div class="mb-16">
    <div class="form-group"><label class="form-label">Add Note</label><input type="text" id="noteTitle" class="form-control" placeholder="Note title"></div>
    <div class="form-group"><textarea id="noteContent" class="form-control" rows="3" placeholder="Write your note..."></textarea></div>
    <button class="btn btn-primary btn-sm" onclick="saveNote(${courseId})"><i class="fa fa-save"></i> Save Note</button>
  </div>
  <div id="notesList">${list.length?list.map(n=>`<div class="card p-16 mb-8"><div class="flex-between"><div class="fw-600">${n.title}</div><span class="fs-xs text-muted">${timeAgo(n.updated_at)}</span></div><p class="fs-sm text-muted mt-8">${n.content||''}</p></div>`).join(''):'<div class="empty-state"><div class="empty-state-icon">📓</div><p>No notes yet</p></div>'}</div>`;
}

async function saveNote(courseId) {
  const title = document.getElementById('noteTitle').value.trim();
  const content = document.getElementById('noteContent').value.trim();
  if (!title) { showToast('Enter a note title','warning'); return; }
  const fd = new FormData(); fd.append('course_id',courseId); fd.append('title',title); fd.append('content',content);
  const res = await fetch('../api/student/save-note.php',{method:'POST',body:fd});
  const d = await res.json();
  if(d.success){showToast('Note saved!','success');document.getElementById('noteTitle').value='';document.getElementById('noteContent').value='';}
  else showToast(d.message||'Failed','error');
}

// ── Store ──────────────────────────────────────────────
async function loadStore() {
  if(storeData.tutors.length) return;
  try {
    const [tRes, cRes] = await Promise.all([fetch('../api/student/tutors.php'), fetch('../api/student/store-courses.php')]);
    const [tData, cData] = await Promise.all([tRes.json(), cRes.json()]);
    storeData.tutors = tData.data||[];
    storeData.courses = cData.data||[];
    renderStoreTutors(storeData.tutors);
    renderStoreCourses(storeData.courses);
  } catch(e) { showToast('Failed to load store','error'); }
}

function storeView(view, el) {
  storeCurrentView = view;
  document.querySelectorAll('.nav-tab').forEach(t=>t.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('store-tutors').classList.toggle('hidden', view!=='tutors');
  document.getElementById('store-courses').classList.toggle('hidden', view!=='courses');
  loadStore();
}

function filterStore() {
  const q = document.getElementById('storeSearch').value.toLowerCase();
  if(storeCurrentView==='tutors') {
    const filtered = storeData.tutors.filter(t=>(t.display_name||t.first_name+' '+t.last_name).toLowerCase().includes(q)||(t.subjects||'').toLowerCase().includes(q));
    renderStoreTutors(filtered);
  } else {
    const filtered = storeData.courses.filter(c=>c.title.toLowerCase().includes(q)||(c.tutor_name||'').toLowerCase().includes(q));
    renderStoreCourses(filtered);
  }
}

function renderStoreTutors(list) {
  const el = document.getElementById('store-tutors');
  if(!list.length){el.innerHTML='<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">👨‍🏫</div><p>No tutors found</p></div>';return;}
  el.innerHTML = list.map(t=>`
    <div class="tutor-card" onclick="openTutorProfile(${t.id})" style="cursor:pointer">
      <div class="tutor-card-banner" style="${t.banner_image?`background-image:url(${t.banner_image});background-size:cover`:''};position:relative">
        <div class="tutor-card-avatar">${t.profile_image?`<img src="${t.profile_image}" class="avatar avatar-lg" style="border:3px solid #fff;border-radius:50%;object-fit:cover">`:`<div class="avatar avatar-lg" style="border:3px solid #fff;display:flex;align-items:center;justify-content:center;background:#fff;color:var(--primary)">${(t.display_name||t.first_name+' '+t.last_name).substring(0,2).toUpperCase()}</div>`}</div>
      </div>
      <div class="tutor-card-body">
        <div class="tutor-card-name">${t.display_name||t.first_name+' '+t.last_name}</div>
        <div class="tutor-card-subject">${t.subjects||'General'}</div>
        <div class="stars mt-8">${stars(t.rating||0)} <span class="fs-xs text-muted">(${t.total_students||0} students)</span></div>
      </div>
    </div>`).join('');
}

function renderStoreCourses(list) {
  const el = document.getElementById('store-courses');
  if(!list.length){el.innerHTML='<div class="empty-state" style="grid-column:1/-1"><div class="empty-state-icon">📚</div><p>No courses found</p></div>';return;}
  el.innerHTML = list.map(c=>`
    <div class="course-card">
      <div class="course-card-thumb">${c.thumbnail?`<img src="${c.thumbnail}" style="width:100%;height:100%;object-fit:cover">`:'📚'}</div>
      <div class="course-card-body">
        <div class="course-card-title">${c.title}</div>
        <div class="course-card-tutor"><i class="fa fa-user-tie"></i> ${c.tutor_name||'Tutor'}</div>
        <p class="fs-sm text-muted mt-8">${(c.description||'').substring(0,80)}...</p>
        ${c.duration?`<div class="fs-xs text-muted mt-4"><i class="fa fa-clock"></i> ${c.duration}</div>`:''}
        <div class="course-card-price">Rs. ${parseFloat(c.price||0).toLocaleString()}</div>
        ${c.monthly_fee>0?`<div class="fs-xs text-muted">Monthly: Rs. ${parseFloat(c.monthly_fee).toLocaleString()}</div>`:''}
      </div>
      <div class="course-card-footer">
        ${c.enrolled?`<span class="badge badge-green btn-full text-center">✅ Enrolled</span>`:`<button class="btn btn-primary btn-sm btn-full" onclick="event.stopPropagation();openPayment(${c.id},'${c.title}',${c.price})">Enroll Now</button>`}
      </div>
    </div>`).join('');
}

async function openTutorProfile(tutorId) {
  openModal('tutorModal');
  document.getElementById('tutorModalBody').innerHTML='<div class="flex-center" style="padding:40px"><div class="spinner"></div></div>';
  try {
    const res = await fetch(`../api/student/tutor-profile.php?id=${tutorId}`);
    const d = await res.json();
    if(!d.success){document.getElementById('tutorModalBody').innerHTML='<div class="alert alert-error">Failed to load profile</div>';return;}
    const t = d.tutor;
    const name = t.display_name||t.first_name+' '+t.last_name;
    document.getElementById('tutorModalTitle').textContent = name;
    document.getElementById('tutorModalBody').innerHTML = `
      <div style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:12px;height:120px;margin-bottom:60px;position:relative;${t.banner_image?`background-image:url(${t.banner_image});background-size:cover`:''};overflow:visible">
        <div style="position:absolute;bottom:-44px;left:24px">${t.profile_image?`<img src="${t.profile_image}" style="width:88px;height:88px;border-radius:50%;border:4px solid #fff;object-fit:cover">`:`<div style="width:88px;height:88px;border-radius:50%;border:4px solid #fff;background:#fff;display:flex;align-items:center;justify-content:center;font-size:1.8rem;color:var(--primary);font-weight:800">${name.substring(0,2).toUpperCase()}</div>`}</div>
      </div>
      <div class="mb-16"><h3 style="font-size:1.3rem;font-weight:800">${name}</h3>
      <div class="stars">${stars(t.rating||0)} <span class="fs-sm text-muted">${t.total_students||0} students</span></div>
      ${t.subjects?`<div class="chip mt-8" style="cursor:default">${t.subjects}</div>`:''}
      </div>
      ${t.description?`<div class="card p-16 mb-16"><h4 class="fw-700 mb-8">About</h4><p class="fs-sm">${t.description}</p></div>`:''}
      ${t.experience?`<div class="card p-16 mb-16"><h4 class="fw-700 mb-8">Experience</h4><p class="fs-sm">${t.experience}</p></div>`:''}
      ${t.qualifications?`<div class="card p-16 mb-16"><h4 class="fw-700 mb-8">Qualifications</h4><p class="fs-sm">${t.qualifications}</p></div>`:''}
      <div class="card p-16 mb-16">
        <h4 class="fw-700 mb-8">Leave a Review</h4>
        ${d.user_rating?`<div class="fs-sm text-muted mb-12">Your last rating: ${stars(d.user_rating.rating)}${d.user_rating.review?` — ${d.user_rating.review}`:''}</div>`:''}
        <div class="form-group"><label class="form-label">Rating</label><select id="tutorRatingSelect" class="form-control"><option value="">Select rating</option><option value="5">5 stars</option><option value="4">4 stars</option><option value="3">3 stars</option><option value="2">2 stars</option><option value="1">1 star</option></select></div>
        <div class="form-group"><label class="form-label">Review</label><textarea id="tutorReviewText" class="form-control" rows="3" placeholder="Share your feedback..."></textarea></div>
        <button class="btn btn-primary btn-full" onclick="submitTutorRating(${t.id})">Submit Review</button>
      </div>
      <h4 class="fw-700 mb-12">Courses by ${name}</h4>
      <div class="grid-2">${(d.courses||[]).map(c=>`<div class="course-card"><div class="course-card-thumb">${c.thumbnail?`<img src="${c.thumbnail}" style="width:100%;height:100%;object-fit:cover">`:'📚'}</div><div class="course-card-body"><div class="course-card-title">${c.title}</div><div class="course-card-price">Rs. ${parseFloat(c.price||0).toLocaleString()}</div></div><div class="course-card-footer">${c.enrolled?`<span class="badge badge-green btn-full text-center">✅ Enrolled</span>`:`<button class="btn btn-primary btn-sm btn-full" onclick="closeModal('tutorModal');openPayment(${c.id},'${c.title}',${c.price})">Enroll</button>`}</div></div>`).join('')||'<p class="text-muted">No courses yet</p>'}</div>`;
  } catch(e) { document.getElementById('tutorModalBody').innerHTML='<div class="alert alert-error">Network error</div>'; }
}

async function submitTutorRating(tutorId) {
  const rating = parseInt(document.getElementById('tutorRatingSelect').value, 10);
  const review = document.getElementById('tutorReviewText').value.trim();
  if (!rating || rating < 1 || rating > 5) { showToast('Please select a rating','warning'); return; }

  try {
    const fd = new FormData();
    fd.append('tutor_id', tutorId);
    fd.append('rating', rating);
    fd.append('review', review);
    const res = await fetch('../api/student/rate-tutor.php',{method:'POST',body:fd});
    const d = await res.json();
    if (d.success) { showToast(d.message || 'Review submitted','success'); closeModal('tutorModal'); openTutorProfile(tutorId); }
    else showToast(d.message || 'Failed to submit review','error');
  } catch(err) { showToast('Network error','error'); }
}

// ── Payment ────────────────────────────────────────────
function openPayment(courseId, title, price) {
  document.getElementById('pay-course-id').value = courseId;
  document.getElementById('paymentCourseInfo').innerHTML = `<strong>${title}</strong><br>Amount: <strong>Rs. ${parseFloat(price).toLocaleString()}</strong>`;
  document.getElementById('slipPreview').style.display='none';
  document.getElementById('slipFile').value='';
  openModal('paymentModal');
}
function previewSlip(input) {
  const file = input.files[0];
  if(file && file.type.startsWith('image/')){const r=new FileReader();r.onload=e=>{const img=document.getElementById('slipPreview');img.src=e.target.result;img.style.display='block';};r.readAsDataURL(file);}
}
document.getElementById('paymentForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = e.target.querySelector('button[type=submit]');
  btn.disabled=true; btn.textContent='Submitting...';
  try {
    const fd = new FormData(e.target);
    const res = await fetch('../api/student/submit-payment.php',{method:'POST',body:fd});
    const d = await res.json();
    if(d.success){showToast('Payment submitted! Awaiting approval.','success');closeModal('paymentModal');e.target.reset();}
    else showToast(d.message||'Failed to submit','error');
  } catch(err){showToast('Network error','error');}
  btn.disabled=false; btn.innerHTML='<i class="fa fa-paper-plane"></i> Submit Payment';
});

// ── Settings ───────────────────────────────────────────
document.getElementById('settingsForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = e.target.querySelector('button[type=submit]');
  btn.disabled=true; btn.textContent='Saving...';
  try {
    const res = await fetch('../api/student/update-profile.php',{method:'POST',body:new FormData(e.target)});
    const d = await res.json();
    if(d.success) showToast('Profile updated!','success');
    else showToast(d.message||'Failed','error');
  } catch(err){showToast('Network error','error');}
  btn.disabled=false; btn.innerHTML='<i class="fa fa-save"></i> Save Changes';
});

async function uploadProfilePic(input) {
  const file = input.files[0]; if(!file) return;
  const fd = new FormData(); fd.append('profile_image', file);
  const res = await fetch('../api/student/upload-avatar.php',{method:'POST',body:fd});
  const d = await res.json();
  if(d.success){showToast('Photo updated!','success');location.reload();}
  else showToast(d.message||'Failed','error');
}

// ── Notifications ──────────────────────────────────────
async function loadNotifications() {
  const el = document.getElementById('notifList');
  if (!el) return;
  el.innerHTML = '<div style="padding:20px;text-align:center"><div class="spinner spinner-sm" style="margin:0 auto"></div></div>';
  try {
    const res = await fetch('../api/student/notifications.php');
    const d = await res.json();
    const list = d.data || [];
    if (!list.length) {
      el.innerHTML = '<div class="empty-state" style="padding:30px"><div class="empty-state-icon">🔔</div><p>No notifications yet</p></div>';
      return;
    }
    el.innerHTML = list.map(n => `
      <div class="notif-item ${n.is_read==0?'unread':''}" onclick="markRead(${n.id},this)">
        <div class="notif-item-icon">${n.type==='success'?'✅':n.type==='warning'?'⚠️':n.type==='error'?'❌':'ℹ️'}</div>
        <div class="notif-item-content">
          <div class="notif-item-title">${n.title}</div>
          <div class="notif-item-msg">${n.message}</div>
          <div class="notif-item-time">${timeAgo(n.created_at)}</div>
        </div>
      </div>`).join('');
  } catch(e) {
    el.innerHTML = '<div style="padding:20px;text-align:center;color:#bdbdbd">Failed to load</div>';
  }
}

async function markRead(id, el) {
  if (el) el.classList.remove('unread');
  await fetch('../api/student/notifications.php', {
    method: 'POST',
    body: JSON.stringify({action:'mark_read', id}),
    headers: {'Content-Type':'application/json'}
  });
}

async function markAllRead() {
  await fetch('../api/student/notifications.php', {
    method: 'POST',
    body: JSON.stringify({action:'mark_all_read'}),
    headers: {'Content-Type':'application/json'}
  });
  // Remove the notification dot
  const dot = document.getElementById('notifDot');
  if (dot) dot.remove();
  // Mark all items as read visually
  document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
}

// ── Init ───────────────────────────────────────────────
loadHome();
