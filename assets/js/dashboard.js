// assets/js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
  // ----- Base helpers -----
  // Robust: try App.BASE_URL -> derive from this script's src -> safe fallback
  function ensureSlash(s){ return s.endsWith('/') ? s : s + '/'; }
  function detectBaseFromScript(){
    try {
      const scripts = document.getElementsByTagName('script');
      for (let i = scripts.length - 1; i >= 0; i--) {
        const src = scripts[i].src || '';
        // match ".../<any>/assets/js/dashboard.js"
        const m = src.match(/^(https?:\/\/[^/]+\/.*\/)assets\/js\/dashboard\.js(?:\?.*)?$/i);
        if (m) return ensureSlash(m[1]);
      }
    } catch (_) {}
    return '/';
  }
  const BASE = (window.App && App.BASE_URL ? ensureSlash(App.BASE_URL) : detectBaseFromScript());
  // Use the physical file to avoid rewrite issues:
  const CHECK_URL = `${BASE}api/check-session.php`;

  // ----- Sidebar / collapse / logout -----
  const sidebar   = document.getElementById('sidebar');
  const sbToggle  = document.getElementById('sbToggleTop');
  const logoutLnk = document.getElementById('logoutLink');
  const isMobile  = () => window.matchMedia('(max-width: 1200px)').matches;

  const applyCollapsed = (on) => {
    if (on) {
      sidebar?.classList.add('collapsed');
      document.body.classList.add('sidebar-collapsed');
      localStorage.setItem('sb_collapsed', '1');
    } else {
      sidebar?.classList.remove('collapsed');
      document.body.classList.remove('sidebar-collapsed');
      localStorage.setItem('sb_collapsed', '0');
      document.querySelector('.menu-item-container.has-submenu.active')?.classList.remove('active');
    }
  };

  const saved = localStorage.getItem('sb_collapsed') === '1';
  if (!isMobile()) applyCollapsed(saved);

  const mq = window.matchMedia('(max-width: 1200px)');
  mq.addEventListener('change', () => {
    if (isMobile()) {
      document.body.classList.remove('sidebar-collapsed');
    } else {
      applyCollapsed(localStorage.getItem('sb_collapsed') === '1');
    }
  });

  sbToggle?.addEventListener('click', (e) => {
    e.preventDefault();
    if (isMobile()) return;
    const next = !sidebar.classList.contains('collapsed');
    sidebar.classList.add('animating');
    applyCollapsed(next);
    setTimeout(() => sidebar.classList.remove('animating'), 350);
  });

  function askLogout(e){
    e?.preventDefault();
    const targetLink = e.currentTarget;
    Swal.fire({
      icon:'question',
      title:'Sign out?',
      text:'You will need to sign in again.',
      showCancelButton:true,
      confirmButtonText:'Logout'
    }).then(r=>{ if(r.isConfirmed) location.href = targetLink.href; });
  }
  logoutLnk?.addEventListener('click', askLogout);

  // ----- Sidebar sub-menu -----
  document.querySelectorAll('.submenu-toggle').forEach(toggle => {
    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const parent = e.currentTarget.closest('.menu-item-container');
      if (parent) parent.classList.toggle('active');
    });
  });

  document.addEventListener('click', (e) => {
    if (sidebar && sidebar.classList.contains('collapsed')) {
      const active = document.querySelector('.menu-item-container.has-submenu.active');
      if (active && !active.contains(e.target)) active.classList.remove('active');
    }
  });

  // ----- Mobile Settings drawer -----
  const settingsTrigger     = document.getElementById('mobileSettingsTrigger');
  const settingsDrawer      = document.getElementById('settingsDrawer');
  const closeSettingsDrawer = document.getElementById('closeSettingsDrawer');
  const drawerBackdrop      = document.getElementById('drawerBackdrop');

  const openDrawer = (drawer) => {
    drawerBackdrop?.removeAttribute('hidden');
    drawerBackdrop?.classList.add('show');
    drawer?.classList.add('show');
    document.body.classList.add('no-scroll');
  };
  const closeDrawerFunc = () => {
    drawerBackdrop?.classList.remove('show');
    document.querySelectorAll('.mobile-drawer').forEach(d => d.classList.remove('show'));
    document.body.classList.remove('no-scroll');
    setTimeout(() => drawerBackdrop?.setAttribute('hidden', ''), 300);
  };
  settingsTrigger?.addEventListener('click', () => openDrawer(settingsDrawer));
  closeSettingsDrawer?.addEventListener('click', closeDrawerFunc);
  drawerBackdrop?.addEventListener('click', closeDrawerFunc);

  // ----- Tooltips when sidebar is collapsed -----
  const tooltipElem = document.createElement('div');
  tooltipElem.className = 'js-tooltip';
  document.body.appendChild(tooltipElem);

  const setupTooltips = () => {
    const targets = document.querySelectorAll('.sidebar.collapsed .menu-item, .sidebar.collapsed .sub-menu-item > a');
    targets.forEach(target => {
      target.addEventListener('mouseenter', () => {
        if (sidebar.classList.contains('collapsed')) {
          const title = target.getAttribute('title');
          if (title) {
            const rect = target.getBoundingClientRect();
            tooltipElem.textContent = title;
            tooltipElem.style.top = `${rect.top + rect.height / 2}px`;
            tooltipElem.style.left = `${rect.right + 12}px`;
            tooltipElem.classList.add('show');
          }
        }
      });
      target.addEventListener('mouseleave', () => tooltipElem.classList.remove('show'));
    });
  };
  setupTooltips();
  sbToggle?.addEventListener('click', () => setTimeout(setupTooltips, 350));

  // ----- Security alert / session polling -----
  // ----- Security alert / session polling -----
  let showing = false;
  let lastStatus = 'init';

  async function checkSession(){
    try{
      const res = await fetch(CHECK_URL, { cache:'no-store' });
      if (!res.ok) return;
      const json = await res.json();

      if (json.status === 'attempt_detected' && !showing){
        showing = true;
        Swal.fire({
          icon:'warning',
          title:'Security Alert',
          html:'Someone tried to login to your account from another device. If this wasn’t you, please change your password.',
          confirmButtonText:'OK',
        }).then(()=>{ showing=false; });
      }

      if (json.status === 'conflict' && lastStatus !== 'conflict'){
        Swal.fire({
          icon:'error',
          title:'Session Conflict',
          html:'Your account is active on another device. You may be logged out here.',
          confirmButtonText:'OK'
        });
      }

      lastStatus = json.status || lastStatus;
    }catch(e){ /* silent */ }
  }

  // Poll every 10s (faster), first run after 1s
  setInterval(checkSession, 10000);
  setTimeout(checkSession, 1000);

});

// --- Mobile user dropdown (avatar) ---
const mobileAvatarTrigger = document.getElementById('mobileAvatarTrigger');
const userDropdown = document.getElementById('userDropdown');

function toggleUserDropdown(e){
  e.preventDefault();
  e.stopPropagation();
  userDropdown?.classList.toggle('show');
}
['click','touchstart'].forEach(ev => {
  mobileAvatarTrigger?.addEventListener(ev, toggleUserDropdown, {passive:false});
});

// close when clicking outside
document.addEventListener('click', (e)=>{
  if (!userDropdown) return;
  if (userDropdown.classList.contains('show') &&
      !userDropdown.contains(e.target) &&
      e.target !== mobileAvatarTrigger) {
    userDropdown.classList.remove('show');
  }
});

// apply logout confirm to mobile link too
function askLogout(e){
  e?.preventDefault();
  const href = e.currentTarget.href;
  Swal.fire({
    icon:'question', title:'Sign out?', text:'You will need to sign in again.',
    showCancelButton:true, confirmButtonText:'Logout'
  }).then(r=>{ if(r.isConfirmed) location.href = href; });
}
document.getElementById('logoutLinkMobile')?.addEventListener('click', askLogout);
