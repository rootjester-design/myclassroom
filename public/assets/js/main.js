// ── CSRF Auto-Injection ────────────────────────────────
const originalFetch = window.fetch;
window.fetch = async function() {
  let [resource, config] = arguments;
  if (config && config.method && config.method.toUpperCase() === 'POST') {
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    if (tokenMeta) {
      config.headers = config.headers || {};
      if (config.headers instanceof Headers) {
        if (!config.headers.has('X-CSRF-Token') && !config.headers.has('X-CSRF-TOKEN') && !config.headers.has('x-csrf-token')) {
          config.headers.append('X-CSRF-Token', tokenMeta.content);
        }
      } else {
        const hasToken = Object.keys(config.headers).some(k => k.toLowerCase() === 'x-csrf-token');
        if (!hasToken) {
          config.headers['X-CSRF-Token'] = tokenMeta.content;
        }
      }
    }
  }
  return originalFetch.apply(this, arguments);
};

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
  if (typeof window.onImageCropped !== 'undefined') {
    window.onImageCropped = null;
  }
  if (typeof currentCropper !== 'undefined') {
    currentCropper = null;
  }
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

function getEmbedUrl(url) {
  if (!url) return '';
  let embedUrl = url.trim();
  if (!/^https?:\/\//i.test(embedUrl)) {
    embedUrl = 'https://' + embedUrl;
  }
  
  // Robust YouTube ID extraction
  const ytMatch = embedUrl.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i);
  if (ytMatch && ytMatch[1]) {
    return 'https://www.youtube.com/embed/' + ytMatch[1];
  }

  if (embedUrl.includes('drive.google.com/file/d/')) {
    embedUrl = embedUrl.replace('/view', '/preview');
  }
  return embedUrl;
}

function playRecording(id, url) {
  const embedUrl = getEmbedUrl(url);
  const watermarkText = window.STUDENT_PHONE || '';
  let watermarkHtml = '';
  
  if (watermarkText) {
    watermarkHtml = `
      <div style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        <div style="transform:rotate(-25deg);font-size:clamp(2rem, 5vw, 4rem);color:rgba(255,255,255,0.25);font-weight:900;user-select:none;white-space:nowrap;text-shadow: 2px 2px 4px rgba(0,0,0,0.5); letter-spacing: 4px;">
          ${watermarkText}
        </div>
      </div>
    `;
  }

  // Create a dedicated fullscreen container
  const fsContainer = document.createElement('div');
  fsContainer.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:#000;z-index:999999;';
  fsContainer.innerHTML = `
    <iframe src="${embedUrl}" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
    ${watermarkHtml}
  `;
  document.body.appendChild(fsContainer);

  const handleFsChange = () => {
    if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
      // Exited fullscreen -> remove player
      fsContainer.remove();
      document.removeEventListener('fullscreenchange', handleFsChange);
      document.removeEventListener('webkitfullscreenchange', handleFsChange);
      document.removeEventListener('msfullscreenchange', handleFsChange);
    }
  };
  
  document.addEventListener('fullscreenchange', handleFsChange);
  document.addEventListener('webkitfullscreenchange', handleFsChange);
  document.addEventListener('msfullscreenchange', handleFsChange);

  try {
    let fsPromise;
    if (fsContainer.requestFullscreen) {
      fsPromise = fsContainer.requestFullscreen();
    } else if (fsContainer.webkitRequestFullscreen) { /* Safari */
      fsPromise = fsContainer.webkitRequestFullscreen();
    } else if (fsContainer.msRequestFullscreen) { /* IE11 */
      fsPromise = fsContainer.msRequestFullscreen();
    }

    if (fsPromise && fsPromise.catch) {
      fsPromise.catch(err => {
        console.warn("Fullscreen request failed:", err);
        // Fallback close button if fullscreen fails but fixed div is shown
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '× Close';
        closeBtn.style.cssText = 'position:absolute;top:20px;right:20px;z-index:100000;padding:10px 20px;background:rgba(255,0,0,0.8);color:white;border:none;border-radius:4px;cursor:pointer;font-size:16px;font-weight:bold;';
        closeBtn.onclick = () => {
          fsContainer.remove();
          document.removeEventListener('fullscreenchange', handleFsChange);
        };
        fsContainer.appendChild(closeBtn);
      });
    }
  } catch(err) {
    console.warn("Fullscreen error:", err);
  }
}
