// assets/js/petcare.js
document.addEventListener('DOMContentLoaded', () => {
  const API = `${App.BASE_URL}api/petcare.php`;

  /* ---------- Modal & form ---------- */
  const modal   = document.getElementById('tipModal');
  const backdrop= document.getElementById('modalBackdrop');
  const titleEl = document.getElementById('tipModalTitle');
  const form    = document.getElementById('tipForm');
  const submit  = form.querySelector('button[type="submit"]');

  const idEl  = document.getElementById('tipId');
  const tIn   = document.getElementById('tipTitle');
  const sIn   = document.getElementById('tipSummary');
  const cat   = document.getElementById('tipCategory');
  const type  = document.getElementById('tipType');
  const body  = document.getElementById('tipBody');
  const urlIn = document.getElementById('tipUrl');
  const pubAt = document.getElementById('tipPublishedAt');
  const expAt = document.getElementById('tipExpiresAt');
  const stat  = document.getElementById('tipStatus');
  const imgIn = document.getElementById('tipImage');
  const imgNm = document.getElementById('tipImageName');
  const imgPv = document.getElementById('tipImgPrev');
  const fileIn= document.getElementById('tipFile');

  // toggle type groups
  const gText = document.getElementById('typeTextGroup');
  const gFile = document.getElementById('typeFileGroup');
  const gUrl  = document.getElementById('typeUrlGroup');
  const updateType = () => {
    const v = type.value;
    gText.hidden = v !== 'text';
    gFile.hidden = v !== 'file';
    gUrl.hidden  = v !== 'url';
  };
  type.addEventListener('change', updateType);

  // Flatpickr (same UI on mobile)
  const fpCfg = { enableTime:true, dateFormat:"Y-m-d H:i", altInput:true, altFormat:"F j, Y h:i K", disableMobile:true,
    onChange: (_, __, fp) => { if (fp.selectedDates.length) fp.close(); }
  };
  const pubPicker = flatpickr(pubAt, fpCfg);
  const expPicker = flatpickr(expAt, fpCfg);

  function resetForm(){
    form.reset(); idEl.value = '';
    imgPv.innerHTML = '<i class="fa-regular fa-image"></i>'; imgNm.textContent='No file chosen';
    submit.disabled=false; submit.innerHTML='<i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Save</span>';
    pubPicker.clear(); expPicker.clear(); updateType();
  }
  function showModal(){ modal.hidden=false; backdrop.hidden=false; document.body.style.overflow='hidden'; }
  function hideModal(){ modal.hidden=true;  backdrop.hidden=true;  document.body.style.overflow=''; resetForm(); }
  function openAdd(){ resetForm(); titleEl.textContent='Add Tip'; showModal(); }

  document.getElementById('tipModalClose').addEventListener('click', hideModal);
  document.getElementById('tipCancel').addEventListener('click', hideModal);
  backdrop.addEventListener('click', e => { if (e.target === backdrop) hideModal(); });

  // Image preview
  imgIn.addEventListener('change', () => {
    const f = imgIn.files?.[0]; imgNm.textContent = f ? f.name : 'No file chosen';
    if (!f){ imgPv.innerHTML = '<i class="fa-regular fa-image"></i>'; return; }
    if (!['image/png','image/jpeg','image/webp'].includes(f.type)){
      Swal.fire('Invalid file','Only JPG/PNG/WEBP','error'); imgIn.value=''; imgNm.textContent='No file chosen'; return;
    }
    if (f.size > 4*1024*1024){ Swal.fire('Too large','Max 4MB','error'); imgIn.value=''; imgNm.textContent='No file chosen'; return; }
    imgPv.innerHTML = `<img src="${URL.createObjectURL(f)}" alt="Preview">`;
  });

  // DataTable
  const dt = $('#tipsTable').DataTable({
    pagingType:'simple_numbers', responsive:false,
    dom: "<'row dt-top'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 dt-actions'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    lengthMenu:[[5,10,25,-1],[5,10,25,'All']], pageLength:5,
    order:[[1,'asc']], language:{search:"",searchPlaceholder:"Search tips..."},
    columnDefs:[{orderable:false, targets:'no-sort'}]
  });
  function placeActions(){
    const $wrap = $('#tipsTable_wrapper .dt-actions');
    if(!$wrap.length) return;
    if(!$wrap.find('.dt-actions-wrapper').length){
      $wrap.append('<div class="dt-actions-wrapper"></div>');
      $wrap.find('.dt-actions-wrapper').append($wrap.find('#tipsTable_filter'));
    }
    if(!$wrap.find('#desktopAddTipBtn').length){
      const $btn = $('<button id="desktopAddTipBtn" class="btn btn-primary" title="Add"><i class="fa-solid fa-plus"></i><span class="btn-text">&nbsp;Add</span></button>');
      $wrap.find('.dt-actions-wrapper').append($btn);
      $btn.on('click', openAdd);
    }
  }
  placeActions(); $('#tipsTable').on('draw.dt', placeActions);

  // Mobile controls
  document.getElementById('mobileAddBtn')?.addEventListener('click', openAdd);
  const mSearch = document.getElementById('mobileSearchInput');
  const mCont   = document.getElementById('mobileCardContainer');
  if (mSearch && mCont) {
    mSearch.addEventListener('input', () => {
      const q = mSearch.value.toLowerCase();
      mCont.querySelectorAll('.tip-card').forEach(card => {
        const all = JSON.stringify(card.dataset).toLowerCase();
        card.style.display = all.includes(q) ? 'flex' : 'none';
      });
    });
  }

  // Edit/Delete
  function openEdit(el){
    resetForm();
    const d = $(el).data();
    titleEl.textContent='Edit Tip';
    idEl.value=d.id; tIn.value=d.title||''; sIn.value=d.summary||''; cat.value=d.category||'health';
    type.value=d.type||'text'; updateType();
    body.value=d.body||''; urlIn.value=d.external_url||'';
    stat.value = d.published ? '1':'0';
    if (d.published_at) pubPicker.setDate(d.published_at,true);
    if (d.expires_at)   expPicker.setDate(d.expires_at,true);
    imgPv.innerHTML = d.image ? `<img src="${App.BASE_URL}${d.image}" alt="">` : '<i class="fa-regular fa-image"></i>';
    showModal();
  }
  function del(el){
    const id = $(el).data('id');
    Swal.fire({title:'Delete this tip?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'})
      .then(async r=>{
        if(!r.isConfirmed) return;
        try{
          const res = await fetch(`${API}?action=delete`, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
          const json = await res.json();
          if(!json.success) throw new Error(json.message || 'Failed');
          sessionStorage.setItem('flashMessage', JSON.stringify({type:'success', message:'Tip deleted.'}));
          location.reload();
        }catch(e){ Swal.fire('Error', e.message || 'Failed','error'); }
      });
  }
  $('.panel-body').on('click','.edit-tip',function(){ openEdit($(this).closest('tr,.tip-card')); });
  $('.panel-body').on('click','.delete-tip',function(){ del($(this).closest('tr,.tip-card')); });

  // Zoom image
  $('.panel-body').on('click','.zoomable-image', function(){
    Swal.fire({ imageUrl: this.src, imageAlt:'Image', imageWidth:'90%', showConfirmButton:false, background:'transparent', backdrop:'rgba(0,0,0,.8)' });
  });

  // Submit
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    const action = fd.get('id') ? 'update' : 'create';
    submit.disabled=true; submit.innerHTML='<i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Saving…</span>';
    try{
      const res = await fetch(`${API}?action=${action}`, {method:'POST', body: fd});
      const json = await res.json();
      if(!json.success) throw new Error(json.message || 'Failed');
      sessionStorage.setItem('flashMessage', JSON.stringify({type:'success', message: json.message}));
      location.reload();
    }catch(err){
      Swal.fire('Error', err.message || 'Failed','error');
      submit.disabled=false; submit.innerHTML='<i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Save</span>';
    }
  });

  // Toast
  const flash = sessionStorage.getItem('flashMessage');
  if (flash){
    const msg = JSON.parse(flash);
    const Toast = Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true});
    Toast.fire({icon: msg.type, title: msg.message});
    sessionStorage.removeItem('flashMessage');
  }
});

function showModal(){
  modal.hidden = false;
  backdrop.hidden = false;
  document.body.style.overflow = 'hidden';
  // optional: if your theme fades with .show
  modal.classList.add('show');
  backdrop.classList.add('show');
}
function hideModal(){
  modal.hidden = true;
  backdrop.hidden = true;
  document.body.style.overflow = '';
  modal.classList.remove('show');
  backdrop.classList.remove('show');
  resetForm();
}
