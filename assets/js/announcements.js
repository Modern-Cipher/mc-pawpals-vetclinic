document.addEventListener('DOMContentLoaded', () => {
  const API = `${App.BASE_URL}api/announcements.php`;

  /* ---------- Modal & form ---------- */
  const modal   = document.getElementById('annModal');
  const backdrop= document.getElementById('modalBackdrop');
  const titleEl = document.getElementById('annModalTitle');
  const form    = document.getElementById('annForm');
  const submit  = form.querySelector('button[type="submit"]');

  const idEl  = document.getElementById('annId');
  const tIn   = document.getElementById('annTitle');
  const bIn   = document.getElementById('annBody');
  const urlIn = document.getElementById('annUrl');
  const aSel  = document.getElementById('annAudience');
  const lSel  = document.getElementById('annLocation');
  const pubAt = document.getElementById('annPublishedAt');
  const expAt = document.getElementById('annExpiresAt');
  const stat  = document.getElementById('annStatus');
  const imgIn = document.getElementById('annImage');
  const imgNm = document.getElementById('annImageName');
  const imgPv = document.getElementById('annImgPrev');

  // Flatpickr config – same UI on mobile, auto-close
  const fpCfg = {
    enableTime: true,
    dateFormat: "Y-m-d H:i",
    altInput: true,
    altFormat: "F j, Y h:i K",
    disableMobile: true,
    onChange: (_, __, fp) => { if (fp.selectedDates.length) fp.close(); }
  };
  const pubPicker = flatpickr(pubAt, fpCfg);
  const expPicker = flatpickr(expAt, fpCfg);

  function resetForm(){
    form.reset(); idEl.value = '';
    imgPv.innerHTML = '<i class="fa-regular fa-image"></i>';
    imgNm.textContent = 'No file chosen';
    submit.disabled = false;
    submit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Save</span>';
    pubPicker.clear(); expPicker.clear();
  }
  function showModal(){ modal.hidden=false; backdrop.hidden=false; document.body.style.overflow='hidden'; }
  function hideModal(){ modal.hidden=true;  backdrop.hidden=true;  document.body.style.overflow=''; resetForm(); }

  function openAddModal(){
    resetForm();
    titleEl.textContent = 'Add Announcement';
    showModal();
  }

  // Close events
  document.getElementById('annModalClose').addEventListener('click', hideModal);
  document.getElementById('annCancel').addEventListener('click', hideModal);
  backdrop.addEventListener('click', e => { if (e.target === backdrop) hideModal(); });

  // Image preview + validations
  imgIn.addEventListener('change', () => {
    const f = imgIn.files?.[0];
    imgNm.textContent = f ? f.name : 'No file chosen';
    if (!f){ imgPv.innerHTML = '<i class="fa-regular fa-image"></i>'; return; }
    if (!['image/png','image/jpeg','image/webp'].includes(f.type)){
      Swal.fire('Invalid file','Only JPG/PNG/WEBP','error'); imgIn.value=''; imgNm.textContent='No file chosen'; return;
    }
    if (f.size > 4*1024*1024){
      Swal.fire('Too large','Max 4MB','error'); imgIn.value=''; imgNm.textContent='No file chosen'; return;
    }
    imgPv.innerHTML = `<img src="${URL.createObjectURL(f)}" alt="Preview">`;
  });

  // Edit/Delete handlers (table rows & mobile cards)
  function openEditFrom(el){
    resetForm();
    const d = $(el).data();
    titleEl.textContent = 'Edit Announcement';
    idEl.value = d.id;
    tIn.value  = d.title || '';
    bIn.value  = d.body  || '';
    urlIn.value= d.external_url || '';
    aSel.value = d.audience;
    lSel.value = d.location;
    stat.value = d.published ? '1' : '0';
    if (d.published_at) pubPicker.setDate(d.published_at, true);
    if (d.expires_at)   expPicker.setDate(d.expires_at, true);
    imgPv.innerHTML = d.image ? `<img src="${App.BASE_URL}${d.image}" alt="">` : '<i class="fa-regular fa-image"></i>';
    showModal();
  }
  function deleteFrom(el){
    const id = $(el).data('id');
    Swal.fire({title:'Delete this announcement?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'})
      .then(async r=>{
        if(!r.isConfirmed) return;
        try{
          const res = await fetch(`${API}?action=delete`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
          const json = await res.json();
          if(!json.success) throw new Error(json.message || 'Failed');
          sessionStorage.setItem('flashMessage', JSON.stringify({type:'success', message:'Announcement deleted.'}));
          location.reload();
        }catch(e){ Swal.fire('Error', e.message || 'Failed','error'); }
      });
  }
  $('.panel-body').on('click','.edit-ann',   function(){ openEditFrom($(this).closest('tr,.ann-card')); });
  $('.panel-body').on('click','.delete-ann', function(){ deleteFrom($(this).closest('tr,.ann-card')); });

  // Image zoom
  $('.panel-body').on('click','.zoomable-image', function(){
    Swal.fire({ imageUrl: this.src, imageAlt:'Announcement Image', imageWidth:'90%', showConfirmButton:false, background:'transparent', backdrop:'rgba(0,0,0,.8)' });
  });

  /* ---------- DataTable (desktop) ---------- */
  const dt = $('#annTable').DataTable({
    pagingType: 'simple_numbers',
    responsive: false,
    // IMPORTANT: `f` renders the search on the right column
    dom:
      "<'row dt-top'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 dt-actions'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    lengthMenu: [[5,10,25,-1],[5,10,25,'All']],
    pageLength: 5,
    order: [[1,'asc']],
    language: { search: "", searchPlaceholder: "Search announcements..." }
  });

  // Add button beside search (and ensure it never duplicates)
  function ensureAddBtn(){
    const $wrap = $('#annTable_wrapper .dt-actions');
    if (!$wrap.length) return;
    // ensure a wrapper exists to keep search + button in a single row
    if (!$wrap.find('.dt-actions-wrapper').length) {
      $wrap.append('<div class="dt-actions-wrapper"></div>');
      $wrap.find('.dt-actions-wrapper').append($wrap.find('#annTable_filter'));
    }
    if (!$wrap.find('#desktopAddBtn').length) {
      const $btn = $('<button id="desktopAddBtn" class="btn btn-primary" title="Add"><i class="fa-solid fa-plus"></i><span class="btn-text">&nbsp;Add</span></button>');
      $wrap.find('.dt-actions-wrapper').append($btn);
      $btn.on('click', openAddModal);
    }
  }
  ensureAddBtn();
  $('#annTable').on('draw.dt', ensureAddBtn);

  /* ---------- Mobile: search & add ---------- */
  document.getElementById('mobileAddBtn')?.addEventListener('click', openAddModal);
  const mobileSearchInput = document.getElementById('mobileSearchInput');
  const mobileCardContainer = document.getElementById('mobileCardContainer');
  if (mobileSearchInput && mobileCardContainer) {
    mobileSearchInput.addEventListener('input', () => {
      const q = mobileSearchInput.value.toLowerCase();
      mobileCardContainer.querySelectorAll('.ann-card').forEach(card => {
        const allData = JSON.stringify(card.dataset).toLowerCase();
        card.style.display = allData.includes(q) ? 'flex' : 'none';
      });
    });
  }

  /* ---------- Submit ---------- */
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    const action = fd.get('id') ? 'update' : 'create';
    submit.disabled = true;
    submit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Saving…</span>';
    try{
      const res = await fetch(`${API}?action=${action}`, {method:'POST', body: fd});
      const json = await res.json();
      if(!json.success) throw new Error(json.message || 'Failed');
      sessionStorage.setItem('flashMessage', JSON.stringify({type:'success', message: json.message}));
      location.reload();
    }catch(err){
      Swal.fire('Error', err.message || 'Failed','error');
      submit.disabled = false;
      submit.innerHTML = '<i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Save</span>';
    }
  });

  // Toast after actions
  const flash = sessionStorage.getItem('flashMessage');
  if (flash){
    const msg = JSON.parse(flash);
    const Toast = Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true});
    Toast.fire({icon: msg.type, title: msg.message});
    sessionStorage.removeItem('flashMessage');
  }
});
