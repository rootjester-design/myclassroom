// ── Shared Utilities ──────────────────────────────────
function showToast(msg, type='info') {
  const icons = {success:'✅',error:'❌',warning:'⚠️',info:'ℹ️'};
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon">${icons[type]||'ℹ️'}</span><span style="flex:1">${msg}</span><span class="toast-close" onclick="this.parentElement.remove()">×</span>`;
  document.getElementById('toast-container').appendChild(t);
  setTimeout(() => { t.style.animation='slideOut 0.3s ease forwards'; setTimeout(()=>t.remove(),300); }, 4000);
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('active');
}
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('active');
}

function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('sidebarOverlay');
  if (sb) sb.classList.toggle('open');
  if (ov) ov.classList.toggle('active');
}
function closeSidebar() {
  const sb = document.getElementById('sidebar');
  const ov = document.getElementById('sidebarOverlay');
  if (sb) sb.classList.remove('open');
  if (ov) ov.classList.remove('active');
}

// ── Profile & Notification panels ──────────────────────
let profileOpen = false, notifOpen = false;

function toggleProfile() {
  profileOpen = !profileOpen;
  const panel = document.getElementById('profilePanel');
  if (panel) panel.classList.toggle('open', profileOpen);
  if (notifOpen) {
    notifOpen = false;
    const np = document.getElementById('notifPanel');
    if (np) np.classList.remove('open');
  }
}
function closeProfile() {
  profileOpen = false;
  const panel = document.getElementById('profilePanel');
  if (panel) panel.classList.remove('open');
}

function toggleNotif() {
  notifOpen = !notifOpen;
  const panel = document.getElementById('notifPanel');
  if (panel) panel.classList.toggle('open', notifOpen);
  if (profileOpen) {
    profileOpen = false;
    const pp = document.getElementById('profilePanel');
    if (pp) pp.classList.remove('open');
  }
  if (notifOpen && typeof loadNotifications === 'function') loadNotifications();
}

// Click outside to close panels
document.addEventListener('click', e => {
  const pPanel = document.getElementById('profilePanel');
  const nPanel = document.getElementById('notifPanel');
  const pBtn   = document.getElementById('profileBtn');
  const nBtn   = document.getElementById('notifBtn');
  const sUser  = document.querySelector('.sidebar-user');

  if (profileOpen && pPanel &&
      !pPanel.contains(e.target) &&
      (!pBtn || !pBtn.contains(e.target)) &&
      (!sUser || !sUser.contains(e.target))) {
    profileOpen = false;
    pPanel.classList.remove('open');
  }

  if (notifOpen && nPanel &&
      !nPanel.contains(e.target) &&
      (!nBtn || !nBtn.contains(e.target))) {
    notifOpen = false;
    nPanel.classList.remove('open');
  }
});

// Close modals on backdrop click
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); });
});

// ── Helpers ────────────────────────────────────────────
function avatar(name, img, size='md') {
  const initials = (name||'?').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
  if (img) return `<img src="${img}" class="avatar avatar-${size}" style="border-radius:50%;object-fit:cover">`;
  return `<div class="avatar avatar-${size}" style="display:flex;align-items:center;justify-content:center">${initials}</div>`;
}

function timeAgo(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr); const now = new Date();
  const s = Math.floor((now - d) / 1000);
  if (s < 60)    return 'just now';
  if (s < 3600)  return Math.floor(s/60) + 'm ago';
  if (s < 86400) return Math.floor(s/3600) + 'h ago';
  if (s < 604800) return Math.floor(s/86400) + 'd ago';
  return d.toLocaleDateString();
}

function stars(rating) {
  let s = '';
  for (let i=1; i<=5; i++) s += `<span style="color:${i<=Math.round(rating)?'#ffc107':'#e0e0e0'}">★</span>`;
  return s;
}
