document.addEventListener('DOMContentLoaded', () => {
  const ensureSlash = s => (s.endsWith('/') ? s : s + '/');
  function detectBaseFromScript() {
    try {
      const scripts = document.getElementsByTagName('script');
      for (let i = scripts.length - 1; i >= 0; i--) {
        const src = scripts[i].src || '';
        const m = src.match(/^(https?:\/\/[^/]+\/[^/]+\/)assets\/js\/admin-staffs\.js/i);
        if (m) return ensureSlash(m[1]);
      }
    } catch (_) {}
    return '/';
  }
  const BASE = ensureSlash(
    (window.App?.BASE_URL && window.App.BASE_URL.length > 1)
      ? window.App.BASE_URL
      : detectBaseFromScript()
  );

  const API = {
    list     : `${BASE}api/staffs/list.php`,
    create   : `${BASE}api/staffs/create.php`,
    update   : `${BASE}api/staffs/update.php`,
    toggle   : `${BASE}api/staffs/toggle_active.php`,
    resetPw  : `${BASE}api/staffs/reset_password.php`,
    // docs:
    docsList : `${BASE}api/staffs/docs_list.php`,
    docsDel  : `${BASE}api/staffs/docs_delete.php`,
  };

  const rows   = document.getElementById('rows');
  const cards  = document.getElementById('cards');
  const q      = document.getElementById('q');

  const modal  = document.getElementById('staffModal');
  const closeM = document.getElementById('closeStaffModal');
  const btnAdd = document.getElementById('btnAdd');
  const form   = document.getElementById('staffForm');
  const title  = document.getElementById('modalTitle');
  const cancel = document.getElementById('cancelStaff');
  const avatar = document.getElementById('avatar');
  const avatarPreview = document.getElementById('avatarPreview');
  const docsWrap = document.getElementById('docsWrap');

  // Docs modal
  const docsModal = document.getElementById('docsModal');
  const closeDocs = document.getElementById('closeDocsModal');
  const docsVault = document.getElementById('docsVault');
  const docsTitle = document.getElementById('docsTitle');

  // modal helpers (retain)
  const openModal  = m => { m.removeAttribute('hidden'); document.body.style.overflow='hidden'; };
  const closeModal = m => { m.setAttribute('hidden',''); document.body.style.overflow=''; };

  let cache = [];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
  const emailOk = e => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
  const fmtPhone = val => (val||'').replace(/\D/g,'').slice(0,11).replace(/^(\d{0,4})(\d{0,3})(\d{0,4}).*$/, (m,a,b,c)=>[a,b,c].filter(Boolean).join(' '));
  const fallbackAvatar = `${BASE}assets/images/person1.jpg`;

  // map file type -> icon class key
  function iconFor(name, type='') {
    name = (name||'').toLowerCase();
    type = (type||'').toLowerCase();
    if (type.startsWith('image/')) return 'img';
    if (name.endsWith('.pdf') || type === 'application/pdf') return 'pdf';
    if (name.endsWith('.doc') || name.endsWith('.docx')) return 'word';
    if (name.endsWith('.xls') || name.endsWith('.xlsx')) return 'excel';
    if (name.endsWith('.ppt') || name.endsWith('.pptx')) return 'ppt';
    return 'file';
  }
  
  function setupTooltips() {
    const icons = document.querySelectorAll('.table .iconbtn');
    icons.forEach(icon => {
      let tooltip;

      const showTooltip = () => {
        const titleText = icon.getAttribute('title');
        if (!titleText) return;

        // Create tooltip element
        tooltip = document.createElement('div');
        tooltip.className = 'js-tooltip show';
        tooltip.textContent = titleText;
        document.body.appendChild(tooltip);

        // Position tooltip
        const rect = icon.getBoundingClientRect();
        tooltip.style.top = `${rect.top + window.scrollY - 10}px`;
        tooltip.style.left = `${rect.left + window.scrollX + (rect.width / 2)}px`;
        tooltip.style.transform = 'translate(-50%, -100%)';
      };

      const hideTooltip = () => {
        if (tooltip) {
          tooltip.remove();
          tooltip = null;
        }
      };

      icon.addEventListener('mouseenter', showTooltip);
      icon.addEventListener('mouseleave', hideTooltip);
      icon.addEventListener('focus', showTooltip);
      icon.addEventListener('blur', hideTooltip);
    });
  }

  // table row & card
  const rowTpl = s => `
    <tr data-id="${s.id}">
      <td class="avatar-td"><img class="avatar" src="${esc(s.avatar_url || fallbackAvatar)}" alt="" onerror="this.src='${fallbackAvatar}'"></td>
      <td>${esc(s.first_name)} ${esc(s.last_name)}</td>
      <td>${esc(s.email)}<div class="muted" style="font-size:12px">${esc(s.username)}</div></td>
      <td>${esc(s.designation || '—')}</td>
      <td class="mincol">
        <label class="switch" title="Toggle active"><input type="checkbox" class="js-active" ${s.is_active?'checked':''}><span class="slider"></span></label>
      </td>
      <td class="mincol">
        <div class="actions">
          <button class="iconbtn js-docs"  title="Documents"><i class="fa-regular fa-folder-open"></i></button>
          <button class="iconbtn js-reset" title="Reset password"><i class="fa-solid fa-key"></i></button>
          <button class="iconbtn js-edit"  title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
        </div>
      </td>
    </tr>`;

  const cardTpl = s => `
    <div class="card" data-id="${s.id}">
      <div class="card-head">
        <div style="display:flex;align-items:center;gap:10px">
          <img class="avatar" src="${esc(s.avatar_url || fallbackAvatar)}" alt="" onerror="this.src='${fallbackAvatar}'" title="Click to zoom">
          <div><b>${esc(s.first_name)} ${esc(s.last_name)}</b><div class="muted" style="font-size:12px">${esc(s.email)} • ${esc(s.username)}</div></div>
        </div>
        <span class="badge">${esc(s.designation || '—')}</span>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px">
        <label class="switch" title="Toggle active">
          <input type="checkbox" class="js-active" ${s.is_active?'checked':''}><span class="slider"></span>
        </label>
        <div class="actions">
          <button class="iconbtn js-docs"  title="Documents"><i class="fa-regular fa-folder-open"></i></button>
          <button class="iconbtn js-reset" title="Reset password"><i class="fa-solid fa-key"></i></button>
          <button class="iconbtn js-edit"  title="Edit"><i class="fa-regular fa-pen-to-square"></i></button>
        </div>
      </div>
    </div>`;

  function render(list){
    rows.innerHTML  = list.length ? list.map(rowTpl).join('')  : `<tr><td colspan="6">No staff found.</td></tr>`;
    cards.innerHTML = list.length ? list.map(cardTpl).join('') : `<div class="muted">No staff found.</div>`;
    setupTooltips();
  }

  async function load(){
    try{
      const res = await fetch(API.list, {cache:'no-store'});
      const text = await res.text();
      let j; try { j = JSON.parse(text); } catch { throw new Error(text.slice(0,200)); }
      if (!res.ok || !j.ok) throw new Error(j.error || 'Server error');
      cache = j.items || j.rows || [];
      applyFilter();
    }catch(e){
      console.error(e);
      rows.innerHTML = `<tr><td colspan="6">Failed to load staff.</td></tr>`;
    }
  }

  function applyFilter(){
    const k = (q.value||'').toLowerCase();
    const list = !k ? cache : cache.filter(s =>
      `${s.first_name} ${s.last_name} ${s.email} ${s.username}`.toLowerCase().includes(k)
    );
    render(list);
  }
  q?.addEventListener('input', applyFilter);

  function resetForm(){
    form.reset();
    form.user_id.value = '';
    avatarPreview.src = fallbackAvatar;
    docsWrap.querySelectorAll('.doc-row').forEach((row,idx)=>{ if(idx>0) row.remove(); });
  }

  btnAdd?.addEventListener('click', () => { title.textContent='Add Staff'; resetForm(); openModal(modal); });
  closeM?.addEventListener('click', () => closeModal(modal));
  cancel?.addEventListener('click', (e)=>{ e.preventDefault(); closeModal(modal); });

  avatar?.addEventListener('change', () => {
    const f = avatar.files?.[0];
    avatarPreview.src = f ? URL.createObjectURL(f) : fallbackAvatar;
  });

  form.phone?.addEventListener('input', e => e.target.value = fmtPhone(e.target.value));

  docsWrap.addEventListener('change', (e) => {
    if (e.target.classList.contains('doc-file')) {
      const filenameSpan = e.target.closest('.file-upload-wrapper').querySelector('.file-upload-filename');
      if (e.target.files.length > 0) {
        filenameSpan.textContent = e.target.files[0].name;
      } else {
        filenameSpan.textContent = 'No file chosen';
      }
    }
  });

  docsWrap.addEventListener('click', (e) => {
    if (e.target.closest('.doc-btn.add')) {
      const row = e.target.closest('.doc-row');
      const clone = row.cloneNode(true);
      clone.querySelector('.doc-file').value = '';
      clone.querySelector('.file-upload-filename').textContent = 'No file chosen';
      docsWrap.appendChild(clone);
    }
    if (e.target.closest('.doc-btn.remove')) {
      const rows = docsWrap.querySelectorAll('.doc-row');
      if (rows.length > 1) e.target.closest('.doc-row').remove();
    }
    if (e.target.closest('.file-upload-button')) {
      const fileInput = e.target.closest('.file-upload-wrapper').querySelector('.doc-file');
      fileInput.click();
    }
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const first = form.first_name.value.trim();
    const last  = form.last_name.value.trim();
    const email = form.email.value.trim();
    const usern = form.username.value.trim();
    if (!first || !last || !email || !usern) { Swal.fire({icon:'error',title:'Complete required fields'}); return; }
    if (!emailOk(email)) { Swal.fire({icon:'error',title:'Invalid email'}); return; }
    if (!/(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9]{4,20}$/.test(usern)) {
      Swal.fire({icon:'error',title:'Cannot save',text:'Username must be 4–20 characters, letters and numbers only, and include at least one letter and one number.'});
      return;
    }

    const fd = new FormData(form);
    [...form.querySelectorAll('.perm:checked')].forEach(i => fd.append('permissions[]', i.value));
    const isEdit = !!form.user_id.value;
    const url = isEdit ? API.update : API.create;

    Swal.fire({title:'Saving…', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
    try{
      const res = await fetch(url, {method:'POST', body:fd});
      const j = await res.json();
      if (!res.ok || !j.ok) throw new Error(j.error || 'Create/Update failed');
      closeModal(modal);
      let html = `<p>Staff saved.</p>`;
      if (j.temp_password) {
        html += `
          <div style="margin-top:10px;padding:10px;border:1px dashed #94a3b8;border-radius:8px;background:#f8fafc">
            <div style="font-weight:600;margin-bottom:6px">Temporary Password</div>
            <div id="tmpPass" style="font-family:monospace">${j.temp_password}</div>
            <button id="copyTmp" class="btn btn-primary" style="margin-top:8px">Copy</button>
          </div>
          <small class="muted">We also emailed the credentials to the staff.</small>`;
      }
      await Swal.fire({icon:'success', title:'Success', html,
        didOpen: () => {
          const btn = document.getElementById('copyTmp');
          const val = document.getElementById('tmpPass')?.textContent || '';
          btn?.addEventListener('click', async()=>{ try{ await navigator.clipboard.writeText(val); btn.textContent='Copied!'; }catch(_){}} );
        }
      });
      await load();
    }catch(err){
      Swal.fire({icon:'error',title:'Cannot save',text:String(err?.message||err)});
    }finally{ Swal.close(); }
  });

  document.body.addEventListener('change', async (e) => {
    const wrap = e.target.closest('tr, .card'); if (!wrap) return;
    const id = +wrap.dataset.id;
    if (e.target.classList.contains('js-active')) {
      const isActive = e.target.checked;
      const on = isActive ? '1' : '0';
      const statusText = isActive ? 'activated' : 'deactivated';
      
      try{
        const res = await fetch(API.toggle, {method:'POST', body: new URLSearchParams({user_id:id, is_active:on})});
        const j = await res.json();
        if (!res.ok || !j.ok) throw new Error(j.error || 'Server error');
        
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: `Staff account has been ${statusText}.`
        });
      }catch(err){
        e.target.checked = !isActive; // Revert the toggle state
        Swal.fire({icon:'error',title:'Toggle failed',text:String(err?.message||err)});
      }
    }
  });

  // clicks (image zoom, reset, docs, edit)
  document.body.addEventListener('click', async (e) => {
    const img = e.target.closest('img.avatar');
    if (img) { Swal.fire({imageUrl: img.src, showConfirmButton:false, showCloseButton:true, width:'auto'}); return; }

    const wrap = e.target.closest('tr, .card'); if (!wrap) return;
    const id = +wrap.dataset.id;

    if (e.target.closest('.js-reset')) {
      const ok = await Swal.fire({icon:'warning',title:'Reset password?',text:'A new temporary password will be emailed.',showCancelButton:true});
      if (!ok.isConfirmed) return;
      Swal.fire({title:'Sending…', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});
      try{
        const res = await fetch(API.resetPw, {method:'POST', body:new URLSearchParams({user_id:id})});
        const j = await res.json();
        Swal.close();
        Swal.fire({icon: j.ok?'success':'error', title: j.ok?'Reset sent':'Failed', text: j.ok?'The user will receive an email.':'Server error'});
      }catch(_){
        Swal.close();
        Swal.fire({icon:'error',title:'Failed',text:'Server error'});
      }
    }

    if (e.target.closest('.js-docs')) {
      const s = cache.find(x => x.id == id) || {};
      docsTitle.textContent = `Documents • ${esc(s.first_name||'')} ${esc(s.last_name||'')}`;
      openDocs(id);
    }

    if (e.target.closest('.js-edit')) {
      const s = cache.find(x => x.id == id) || {};
      title.textContent='Edit Staff';
      resetForm();
      form.user_id.value     = s.id || '';
      form.first_name.value  = s.first_name || '';
      form.last_name.value   = s.last_name || '';
      form.email.value       = s.email || '';
      form.username.value    = s.username || '';
      form.phone.value       = fmtPhone(s.phone || '');
      form.designation.value = s.designation || '';
      avatarPreview.src      = s.avatar_url || fallbackAvatar;

      const allowed = (s.permissions || []);
      form.querySelectorAll('.perm').forEach(cb => cb.checked = allowed.includes(cb.value));
      openModal(modal);
    }
  });

  // ===== Documents Vault =====
  closeDocs?.addEventListener('click', () => closeModal(docsModal));

  async function openDocs(userId){
    docsVault.innerHTML = `<div class="vault-empty">Loading…</div>`;
    openModal(docsModal);
    try{
      const res = await fetch(API.docsList, {method:'POST', body:new URLSearchParams({user_id:userId})});
      const j = await res.json();
      if (!res.ok || !j.ok) throw 0;
      renderVault(j.groups || {}, userId);
    }catch(_){
      docsVault.innerHTML = `<div class="vault-empty">Failed to load documents.</div>`;
    }
  }

  function renderVault(groups, userId){
    const order = ['resume','id','license','photo','other'];
    let html = '';
    let total = 0;

    for (const k of order){
      const items = groups[k] || [];
      if (!items.length) continue;
      total += items.length;

      html += `<div class="vault-group" data-kind="${k}">
        <div class="vault-title">${k[0].toUpperCase()+k.slice(1)} <span class="muted" style="font-weight:400">(${items.length})</span></div>
        <div class="vault-grid">`;

      for (const it of items){
        const ic = iconFor(it.name, it.type);
        const thumb = ic === 'img'
          ? `<img src="${esc(it.url)}" alt="">`
          : `<i class="fa-solid ${ic==='pdf'?'fa-file-pdf':ic==='word'?'fa-file-word':ic==='excel'?'fa-file-excel':ic==='ppt'?'fa-file-powerpoint':'fa-file'}"></i>`;
        html += `
          <div class="vault-item" data-id="${it.id}" data-url="${esc(it.url)}" data-type="${esc(it.type||'')}" title="Open">
            <div class="vault-actions">
              <button class="trash" title="Delete"><i class="fa-regular fa-trash-can"></i></button>
            </div>
            <div class="vault-thumb">${thumb}</div>
            <div class="vault-name">${esc(it.name)}</div>
          </div>`;
      }
      html += `</div></div>`;
    }

    if (!total) {
      html = `<div class="vault-empty">No documents uploaded yet.</div>`;
    }
    docsVault.innerHTML = html;
    docsVault.dataset.userId = String(userId);
  }

  // open / delete tile
  docsModal?.addEventListener('click', async (e) => {
    const tile = e.target.closest('.vault-item');
    if (!tile) return;

    // delete
    if (e.target.closest('.trash')) {
      const ok = await Swal.fire({icon:'warning', title:'Delete this file?', showCancelButton:true});
      if (!ok.isConfirmed) return;
      try{
        const res = await fetch(API.docsDel, {method:'POST', body:new URLSearchParams({doc_id: tile.dataset.id})});
        const j = await res.json();
        if (!res.ok || !j.ok) throw 0;
        const grid = tile.parentElement;
        tile.remove();
        if (grid && !grid.querySelector('.vault-item')) {
          const group = grid.closest('.vault-group');
          group?.remove();
          if (!docsVault.querySelector('.vault-item')) {
            docsVault.innerHTML = `<div class="vault-empty">No documents uploaded yet.</div>`;
          }
        }
      }catch(_){
        Swal.fire({icon:'error',title:'Failed',text:'Could not delete file.'});
      }
      return;
    }

    // open
    const type = (tile.dataset.type||'').toLowerCase();
    const url  = tile.dataset.url;
    if (type.startsWith('image/')) {
      Swal.fire({imageUrl:url, showConfirmButton:false, showCloseButton:true, width:'auto'});
    } else {
      window.open(url, '_blank');
    }
  });

  // Init
  load();
});