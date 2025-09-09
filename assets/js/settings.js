document.addEventListener('DOMContentLoaded', () => {
  const API = `${App.BASE_URL}api/social_links.php`;

  // Map platform -> Font Awesome icon class
  const PLATFORM_ICONS = {
    'Facebook': 'fa-brands fa-facebook',
    'Instagram': 'fa-brands fa-instagram',
    'Twitter/X': 'fa-brands fa-x-twitter',
    'YouTube': 'fa-brands fa-youtube',
    'LinkedIn': 'fa-brands fa-linkedin',
    'TikTok': 'fa-brands fa-tiktok',
    'Pinterest': 'fa-brands fa-pinterest',
    'WhatsApp': 'fa-brands fa-whatsapp',
    'Viber': 'fa-brands fa-viber',
    'Website': 'fa-solid fa-globe',
  };

  /* =======================
     DESKTOP DATATABLE
  ======================= */
  try {
    $('#socialLinksTable').DataTable({
      dom:
        "<'dt-controls d-flex align-items-center justify-content-between flex-wrap gap-2'<'length-wrap'l><'search-wrap ms-auto'f>>" +
        "t" +
        "<'row mt-2'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
      lengthMenu: [[5,10,25,-1],[5,10,25,'All']],
      pageLength: 5,
      columnDefs: [{ orderable:false, targets:'no-sort' }],
      order: [[0,'asc']],
      language: { search: "", searchPlaceholder: "Search..." }
    });
  } catch(e) { /* ignore when hidden on mobile */ }

  /* =======================
     FORM UTILITIES
  ======================= */
  const wireChangeDetect = (formId, saveBtnId, cancelBtnId) => {
    const f = document.getElementById(formId);
    const s = document.getElementById(saveBtnId);
    const c = document.getElementById(cancelBtnId);
    if(!f||!s||!c) return;
    const getState = () => JSON.stringify(
      Array.from(new FormData(f).entries())
        .filter(([k])=>!k.startsWith('save_'))
        .map(([k,v])=>[k,(v instanceof File)?v.name:v])
    );
    let initial = getState();
    const check = ()=>{ const changed = getState()!==initial; s.disabled=!changed; c.hidden=!changed; };
    f.addEventListener('input', check); f.addEventListener('change', check);
    c.addEventListener('click', ()=>{ f.reset(); initial=getState(); check(); document.querySelectorAll('.file-upload-filename').forEach(el=>el.textContent='No file chosen'); });
  };
  wireChangeDetect('brandingForm','saveBrandingBtn','cancelBrandingBtn');
  wireChangeDetect('contactForm','saveContactBtn','cancelContactBtn');

  // Phone & ZIP formatting
  const phoneInput = document.getElementById('contact_phone');
  if (phoneInput) {
    const formatPhone = (val) => {
      if (!val) return '';
      let d = val.replace(/\D/g, '').slice(0, 11);
      if (d.length > 7) return `${d.slice(0,4)} ${d.slice(4,7)} ${d.slice(7)}`;
      if (d.length > 4) return `${d.slice(0,4)} ${d.slice(4)}`;
      return d;
    };
    phoneInput.value = formatPhone(phoneInput.value);
    phoneInput.addEventListener('input', () => { phoneInput.value = formatPhone(phoneInput.value); });
  }
  document.getElementById('contact_zipcode')?.addEventListener('input', e => { e.target.value = e.target.value.replace(/\D/g,''); });

  // Hero upload client validation
  const heroFileInput = document.getElementById('hero_image');
  if (heroFileInput) {
    heroFileInput.addEventListener('change', () => {
      const fileNameSpan = document.getElementById('heroFileName');
      const f = heroFileInput.files?.[0];
      fileNameSpan.textContent = f ? f.name : 'No file chosen';
      if (!f) return;
      const okTypes = ['image/png','image/jpeg','image/webp'];
      if (!okTypes.includes(f.type)) { Swal.fire({icon:'error', title:'Invalid file', text:'Only JPG/PNG/WEBP allowed.'}); heroFileInput.value=''; fileNameSpan.textContent='No file chosen'; return; }
      if (f.size > 4*1024*1024) { Swal.fire({icon:'error', title:'Too large', text:'Max file size is 4MB.'}); heroFileInput.value=''; fileNameSpan.textContent='No file chosen'; }
    });
  }

  /* =======================
     SOCIAL LINKS MODAL
  ======================= */
  const modal = document.getElementById('socialLinkModal');
  const modalBackdrop = document.getElementById('modalBackdrop');
  const modalTitle = document.getElementById('socialLinkModalTitle');
  const form = document.getElementById('socialLinkForm');
  const addBtn = document.getElementById('addSocialLinkBtn');
  const addBtnMobile = document.getElementById('addSocialLinkBtnMobile');
  const closeBtn = document.getElementById('closeSocialLinkModal');
  const cancelBtn = document.getElementById('cancelSocialLinkModal');

  const platformSelect = document.getElementById('socialPlatform');
  const iconInput = document.getElementById('socialIconClass');

  platformSelect?.addEventListener('change', () => {
    const val = platformSelect.value;
    if (PLATFORM_ICONS[val]) iconInput.value = PLATFORM_ICONS[val];
    if (val === 'Other' && !iconInput.value) iconInput.placeholder = 'e.g., fa-brands fa-facebook';
  });

  function openModal(edit=false, data={}){
    form.reset();
    form.querySelector('#socialLinkId').value = edit ? (data.id||'') : '';
    platformSelect.value = edit ? (data.platform||'') : '';
    iconInput.value = edit ? (data.icon || PLATFORM_ICONS[data.platform] || '') : (PLATFORM_ICONS[platformSelect.value||''] || '');
    form.querySelector('#socialUrl').value    = edit ? (data.url||'') : '';
    form.querySelector('#displayOrder').value = edit ? (data.order||0) : 0;

    modalTitle.textContent = edit ? 'Edit Social Link' : 'Add Social Link';
    modal.removeAttribute('hidden'); modalBackdrop.removeAttribute('hidden');
    modal.classList.add('show'); modalBackdrop.classList.add('show');
  }
  function closeModal(){
    modal.setAttribute('hidden',''); modalBackdrop.setAttribute('hidden','');
    modal.classList.remove('show'); modalBackdrop.classList.remove('show');
  }
  addBtn?.addEventListener('click', ()=>openModal(false));
  addBtnMobile?.addEventListener('click', ()=>openModal(false));
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  modalBackdrop?.addEventListener('click', (e)=>{ if(e.target===modalBackdrop) closeModal(); });

  // Create/Update submit
  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const id = form.querySelector('#socialLinkId').value.trim();
    const payload = {
      id: id || undefined,
      platform: platformSelect.value.trim(),
      icon_class: iconInput.value.trim(),
      url: form.querySelector('#socialUrl').value.trim(),
      display_order: parseInt(form.querySelector('#displayOrder').value || '0', 10)
    };
    if (!payload.platform || !payload.icon_class || !payload.url) { Swal.fire({icon:'error', title:'Missing fields', text:'Complete all fields.'}); return; }
    if (!/^https?:\/\//i.test(payload.url)) { Swal.fire({icon:'error', title:'Invalid URL', text:'URL must start with http:// or https://'}); return; }

    const action = id ? 'update' : 'create';
    try{
      const res = await fetch(`${API}?action=${action}`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
      const json = await res.json();
      if (!json.success) throw new Error(json.message || 'Unknown error');
      sessionStorage.setItem('flashMessage', JSON.stringify({type:'success', message: json.message}));
      location.reload();
    }catch(err){
      Swal.fire({icon:'error', title:'Error', text: err.message || 'Failed'});
    }
  });

  /* =======================
     DESKTOP row actions
  ======================= */
  $('#socialLinksTable tbody').on('click', '.edit-link', function(){
    const row = $(this).closest('tr');
    const data = {
      id: row.data('id'),
      platform: row.data('platform'),
      icon: row.data('icon'),
      url: row.data('url'),
      order: row.data('order') || 0
    };
    openModal(true, data);
  });

  $('#socialLinksTable tbody').on('click', '.delete-link', async function(){
    const row = $(this).closest('tr');
    const id = row.data('id');
    const ok = await Swal.fire({ title:'Delete this link?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Yes, delete' });
    if(!ok.isConfirmed) return;
    try{
      const res = await fetch(`${API}?action=delete`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
      const json = await res.json();
      if(!json.success) throw new Error(json.message || 'Unknown error');
      $('#socialLinksTable').DataTable().row(row).remove().draw(false);
      Swal.fire('Deleted!', 'The social link has been deleted.', 'success');
    }catch(err){
      Swal.fire('Error', err.message || 'Failed to delete', 'error');
    }
  });

  /* =======================
     MOBILE slider behaviour
  ======================= */
  const mobileSearch = document.getElementById('mobileSocialSearch');
  const mobileWrap   = document.getElementById('socialMobileContainer');

  // Filter cards by any text on the card
  if (mobileSearch && mobileWrap) {
    mobileSearch.addEventListener('input', ()=>{
      const q = mobileSearch.value.toLowerCase();
      mobileWrap.querySelectorAll('.social-card').forEach(card=>{
        const allText = (card.dataset.platform + ' ' + card.dataset.url + ' ' + card.dataset.icon).toLowerCase();
        card.style.display = allText.includes(q) ? 'flex' : 'none';
      });
    });
  }

  // Edit / delete from mobile cards
  $('.social-mobile').on('click', '.edit-social', function(){
    const card = $(this).closest('.social-card');
    const data = {
      id: card.data('id'),
      platform: card.data('platform'),
      icon: card.data('icon'),
      url: card.data('url'),
      order: card.data('order') || 0
    };
    openModal(true, data);
  });

  $('.social-mobile').on('click', '.delete-social', async function(){
    const card = $(this).closest('.social-card');
    const id = card.data('id');
    const ok = await Swal.fire({ title:'Delete this link?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Yes, delete' });
    if(!ok.isConfirmed) return;
    try{
      const res = await fetch(`${API}?action=delete`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
      const json = await res.json();
      if(!json.success) throw new Error(json.message || 'Unknown error');
      card.remove();
      Swal.fire('Deleted!', 'The social link has been deleted.', 'success');
    }catch(err){
      Swal.fire('Error', err.message || 'Failed to delete', 'error');
    }
  });

  // Flash toast for AJAX actions
  const flash = sessionStorage.getItem('flashMessage');
  if (flash) {
    const msg = JSON.parse(flash);
    const Toast = Swal.mixin({toast:true, position:'top-end', showConfirmButton:false, timer:3000, timerProgressBar:true});
    Toast.fire({icon: msg.type, title: msg.message});
    sessionStorage.removeItem('flashMessage');
  }
});
