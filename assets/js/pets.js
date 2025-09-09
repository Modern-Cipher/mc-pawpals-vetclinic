// assets/js/pets.js
document.addEventListener('DOMContentLoaded', () => {
  // ---------- Base URL detection ----------
  const ensureSlash = s => (s.endsWith('/') ? s : s + '/');
  const detectBaseFromScript = () => {
    try {
      const scripts = document.getElementsByTagName('script');
      for (let i = scripts.length - 1; i >= 0; i--) {
        const src = scripts[i].src || '';
        const m = src.match(/^(https?:\/\/[^/]+\/[^/]+\/)assets\/js\/pets\.js/i);
        if (m) return ensureSlash(m[1]);
      }
    } catch {}
    return '/';
  };
  const BASE =
    (window.App?.BASE_URL && window.App.BASE_URL.length > 2)
      ? ensureSlash(window.App.BASE_URL)
      : detectBaseFromScript();

  const API = {
    list:   `${BASE}api/pets/list.php`,
    create: `${BASE}api/pets/create.php`,
    update: `${BASE}api/pets/update.php`,
    delete: `${BASE}api/pets/delete.php`,
    // --- START OF CHANGES ---
    history: `${BASE}api/pets/history.php`, // Added API endpoint for history
    // --- END OF CHANGES ---
  };

  // ---------- Elements ----------
  const petsGrid     = document.getElementById('petsGrid');
  const petModal     = document.getElementById('petModal');
  const detailsModal = document.getElementById('petDetails');
  const detailsBody  = document.getElementById('detailsBody');
  const btnOpenPet   = document.getElementById('btnOpenPetModal');
  const petForm      = document.getElementById('petForm');
  const petSearch    = document.getElementById('petSearch');
  const speciesChips = document.getElementById('speciesChips');
  const pageRoot     = document.querySelector('.page-wrapper') || document.body;

  // Backdrop (ensure exists)
  const ensureBackdrop = () => {
    let b = document.getElementById('drawerBackdrop');
    if (!b) {
      b = document.createElement('div');
      b.id = 'drawerBackdrop';
      b.className = 'backdrop';
      b.setAttribute('hidden', '');
      document.body.appendChild(b);
    }
    return b;
  };
  const backdrop = ensureBackdrop();

  const getPetModalTitleEl = () =>
    document.getElementById('petModalTitle') ||
    document.getElementById('petFormTitle');

  // ---------- Modal helpers (blur before ARIA toggles) ----------
  let lastFocus = null;

  function showModal(modalEl) {
    document.querySelectorAll('.modal.show').forEach(m => {
      if (m !== modalEl) hideModal(m, { silent: true });
    });
    if (document.activeElement) {
      try { document.activeElement.blur(); } catch {}
    }
    lastFocus = document.activeElement;
    pageRoot.setAttribute('aria-hidden', 'true');
    pageRoot.setAttribute('inert', '');
    backdrop.removeAttribute('hidden');
    backdrop.classList.add('show');
    modalEl.classList.add('show');
    modalEl.setAttribute('aria-hidden', 'false');
    const box = modalEl.querySelector('.modal-content');
    if (box) {
      box.setAttribute('tabindex', '-1');
      box.focus({ preventScroll: true });
    }
  }

  function hideModal(modalEl, opts = {}) {
    if (document.activeElement && modalEl.contains(document.activeElement)) {
      try { document.activeElement.blur(); } catch {}
    }
    modalEl.classList.remove('show');
    modalEl.setAttribute('aria-hidden', 'true');
    const anyOpen = document.querySelector('.modal.show');
    if (!anyOpen) {
      backdrop.classList.remove('show');
      setTimeout(() => backdrop.setAttribute('hidden', ''), 300);
      pageRoot.removeAttribute('inert');
      pageRoot.setAttribute('aria-hidden', 'false');
      if (!opts.silent && lastFocus) {
        try { lastFocus.focus({ preventScroll: true }); } catch {}
      }
    }
  }

  backdrop.addEventListener('click', () => {
    if (detailsModal?.classList.contains('show')) hideModal(detailsModal);
    if (petModal?.classList.contains('show')) hideModal(petModal);
  });
  document.getElementById('closePetModal')
    ?.addEventListener('click', () => hideModal(petModal));
  document.getElementById('closePetDetails')
    ?.addEventListener('click', () => hideModal(detailsModal));
  document.getElementById('btnCancelPet')
    ?.addEventListener('click', () => hideModal(petModal));

  // ---------- Utils ----------
  const escapeHtml = s =>
    String(s ?? '').replace(/[&<>"']/g, c => (
      {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]
    ));

  const ageFrom = (dateStr) => {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    if (isNaN(+d)) return '—';
    const t = new Date();
    let y = t.getFullYear() - d.getFullYear();
    const m = t.getMonth() - d.getMonth();
    if (m < 0 || (m === 0 && t.getDate() < d.getDate())) y--;
    return y < 0 ? '—' : (y === 0 ? '<1 year' : `${y} year${y > 1 ? 's' : ''}`);
  };

  const fmtDateTime = (s, withTime = true) => {
    if (!s) return '—';
    const d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(+d)) return '—';
    const opts = withTime
      ? { month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit' }
      : { month:'long', day:'numeric', year:'numeric' };
    return new Intl.DateTimeFormat('en-US', opts).format(d);
  };
  
  // --- START OF CHANGES ---
  const fmtDateOnly = (s) => s ? fmtDateTime(s, false) : '—';
  // --- END OF CHANGES ---

  // Datepicker
  let fpInstance;
  function initDatePickerForForm() {
    if (typeof flatpickr !== 'function') return;
    if (fpInstance) fpInstance.destroy();
    fpInstance = flatpickr('#birthdate', {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'F j, Y',
      allowInput: true,
      maxDate: 'today',
      disableMobile: true,
      appendTo: petModal.querySelector('.modal-content')
    });
  }

  // Zoom image via SweetAlert
  const zoomImage = (url, name) => {
    if (!url) return;
    Swal.fire({
      imageUrl: url,
      imageAlt: `Photo of ${escapeHtml(name)}`,
      showCloseButton: true,
      showConfirmButton: false,
      backdrop: `rgba(0,0,0,0.8)`,
      width: 'auto',
      customClass: { popup: 'zoom-card-popup', image: 'zoomed-image-in-card' }
    });
  };

  // ---------- Render ----------
  const petCardTemplate = (p) => {
    const photoHtml = p.photo_url
      ? `<img class="pet-photo" src="${p.photo_url}" alt="Photo of ${escapeHtml(p.name)}">`
      : `<div class="pet-photo-placeholder"><i class="fa-solid fa-paw"></i></div>`;
    const deleteDisabled = p.has_medical_history ? 'disabled' : '';
    const deleteTooltip  = p.has_medical_history ? 'Cannot delete: Has medical history' : 'Delete Pet';
    return `
      <article class="pet-card" data-pet-id="${p.id}">
        ${photoHtml}
        <div class="pet-body">
          <h4 class="pet-name">${escapeHtml(p.name)}</h4>
          <div class="pet-meta">${escapeHtml(p.species)} • ${escapeHtml(p.breed || 'N/A')}</div>
          <div class="pet-actions">
            <button class="iconbtn js-view"   data-tooltip="View Details & History"><i class="fa-regular fa-eye"></i></button>
            <button class="iconbtn js-edit"   data-tooltip="Edit Pet"><i class="fa-regular fa-pen-to-square"></i></button>
            <button class="iconbtn js-delete" data-tooltip="${deleteTooltip}" ${deleteDisabled}><i class="fa-regular fa-trash-can"></i></button>
          </div>
        </div>
      </article>`;
  };

  function renderPets(list) {
    petsGrid.innerHTML = list.length
      ? list.map(petCardTemplate).join('')
      : `<p class="muted p-4 text-center">No pets yet. Click <b>Add Pet</b> to get started.</p>`;

    list.forEach(pet => {
      const card = petsGrid.querySelector(`.pet-card[data-pet-id="${pet.id}"]`);
      const img  = card?.querySelector('.pet-photo');
      if (img) {
        img.style.cursor = 'pointer';
        img.addEventListener('click', (e) => {
          e.stopPropagation();
          zoomImage(pet.photo_url, pet.name);
        });
      }
    });
  }

  // ---------- Data / filters ----------
  let pets = [];

  function applyFilters() {
    const q = (petSearch?.value || '').toLowerCase();
    const activeChip = document.querySelector('.species-chips .chip.active');
    const filterSpecies = activeChip ? activeChip.dataset.sp : 'all';
    const list = pets.filter(p => {
      const bySpec = (filterSpecies === 'all') || (p.species === filterSpecies);
      const hay = `${p.name} ${p.species} ${p.breed ?? ''}`.toLowerCase();
      const byQ = !q || hay.includes(q);
      return bySpec && byQ;
    });
    renderPets(list);
  }

  async function loadPets() {
    try {
      const res = await fetch(API.list, { cache: 'no-store' });
      if (!res.ok) throw new Error(`Server responded with ${res.status}.`);
      const j = await res.json();
      if (!j.ok) throw new Error(j.error || 'Failed to load pets');
      pets = j.pets || [];
      applyFilters();
    } catch (err) {
      console.error(err);
      Swal.fire({ icon: 'error', title: 'Cannot load pets', text: String(err.message || err) });
    }
  }

  // --- START OF CHANGES ---
  // New functions to render the medical history
  function renderHistory(history) {
    let html = '';
    const sections = [
        { title: 'Consultations (S.O.A.P.)', key: 'consultations', renderer: r => `
            <div><strong>Date:</strong> ${fmtDateTime(r.record_date)}</div>
            <div><strong>Recorded by:</strong> ${escapeHtml(r.staff_name)}</div>
            <div class="soap-details">
                <p><strong>Subjective:</strong> ${escapeHtml(r.subjective)}</p>
                <p><strong>Assessment:</strong> ${escapeHtml(r.assessment)}</p>
            </div>` 
        },
        { title: 'Vaccinations', key: 'vaccinations', renderer: r => `
            <div><strong>Vaccine:</strong> ${escapeHtml(r.vaccine_name)}</div>
            <div><strong>Date Administered:</strong> ${fmtDateOnly(r.date_administered)}</div>
            <div><strong>Next Due:</strong> ${fmtDateOnly(r.next_due_date)}</div>`
        },
        { title: 'Deworming', key: 'deworming', renderer: r => `
            <div><strong>Product:</strong> ${escapeHtml(r.product_name)}</div>
            <div><strong>Date Administered:</strong> ${fmtDateOnly(r.date_administered)}</div>
            <div><strong>Next Due:</strong> ${fmtDateOnly(r.next_due_date)}</div>`
        },
        { title: 'Parasite Preventions', key: 'preventions', renderer: r => `
            <div><strong>Product:</strong> ${escapeHtml(r.product_name)} (${escapeHtml(r.type)})</div>
            <div><strong>Date Administered:</strong> ${fmtDateOnly(r.date_administered)}</div>
            <div><strong>Next Due:</strong> ${fmtDateOnly(r.next_due_date)}</div>`
        },
        { title: 'Medications', key: 'medications', renderer: r => `
            <div><strong>Drug:</strong> ${escapeHtml(r.drug_name)} (${escapeHtml(r.dosage)})</div>
            <div><strong>Frequency:</strong> ${escapeHtml(r.frequency)}</div>
            <div><strong>Duration:</strong> ${fmtDateOnly(r.start_date)} to ${fmtDateOnly(r.end_date)}</div>`
        },
        { title: 'Allergies', key: 'allergies', renderer: r => `
            <div><strong>Allergen:</strong> ${escapeHtml(r.allergen)}</div>
            <div><strong>Severity:</strong> ${escapeHtml(r.severity)}</div>
            <div><strong>Noted On:</strong> ${fmtDateOnly(r.noted_at)}</div>`
        }
    ];

    let hasAnyHistory = false;
    for (const section of sections) {
        if (history[section.key] && history[section.key].length > 0) {
            hasAnyHistory = true;
            html += `<h3>${section.title}</h3>`;
            html += history[section.key].map(record => `<div class="history-card">${section.renderer(record)}</div>`).join('');
        }
    }

    if (!hasAnyHistory) {
      return '<p class="muted p-4 text-center">No medical history found for this pet.</p>';
    }
    return html;
  }

  async function loadPetHistory(petId) {
    const historyPanel = document.getElementById('tab_history');
    if (!historyPanel) return;
    historyPanel.innerHTML = '<p class="muted p-4 text-center">Loading history...</p>';

    try {
        const res = await fetch(`${API.history}?pet_id=${petId}`);
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Failed to fetch history');
        historyPanel.innerHTML = renderHistory(data.history);
    } catch(err) {
        console.error(err);
        historyPanel.innerHTML = '<p class="error p-4 text-center">Could not load medical history.</p>';
    }
  }
  // --- END OF CHANGES ---

  // ---------- Details modal ----------
  function openDetails(p) {
    if (petModal?.classList.contains('show')) hideModal(petModal, { silent: true });

    const photoHtml = p.photo_url
      ? `<img class="pet-photo" src="${p.photo_url}" alt="Photo of ${escapeHtml(p.name)}">`
      : `<div class="pet-photo-placeholder"><i class="fa-solid fa-camera"></i></div>`;

    const row = (label, value) => `
      <div class="details-row">
        <div class="label">${label}</div>
        <div class="value">${escapeHtml(value ?? '—')}</div>
      </div>`;

    detailsBody.innerHTML = `
      <div class="pet-header">
        ${photoHtml}
        <div class="pet-header-info">
          <h3>${escapeHtml(p.name)}</h3>
          <div class="pet-sub">${escapeHtml(p.species)} • ${escapeHtml(p.breed || 'N/A')}</div>
        </div>
      </div>
      <div class="tabs">
        <button class="tabbtn active" data-tab="profile" role="tab" aria-selected="true">Profile</button>
        <button class="tabbtn" data-tab="history" role="tab" aria-selected="false">Medical History</button>
      </div>
      <div id="tab_profile" role="tabpanel">
        ${row('Name', p.name)}
        ${row('Species', p.species)}
        ${row('Breed', p.breed)}
        ${row('Sex', p.sex)}
        ${row('Color', p.color)}
        ${row('Birthdate', fmtDateOnly(p.birthdate))}
        ${row('Age', ageFrom(p.birthdate))}
        ${row('Neutered/Spayed', Number(p.sterilized) ? 'Yes' : 'No')}
        ${row('Created', fmtDateTime(p.created_at))}
        ${row('Updated', fmtDateTime(p.updated_at))}
      </div>
      <div id="tab_history" role="tabpanel" style="display:none;" aria-hidden="true">
        <!-- Content will be loaded dynamically by loadPetHistory() -->
      </div>`;

    const img = detailsBody.querySelector('.pet-photo');
    if (img) {
      img.style.cursor = 'pointer';
      img.addEventListener('click', () => zoomImage(p.photo_url, p.name));
    }
    
    // --- START OF CHANGES ---
    // Load history when modal is opened, but Profile tab is shown by default
    loadPetHistory(p.id);
    // --- END OF CHANGES ---

    showModal(detailsModal);
  }

  detailsModal.addEventListener('click', (e) => {
    const tabBtn = e.target.closest('[role="tab"]');
    if (!tabBtn) return;
    detailsModal.querySelectorAll('[role="tabpanel"]').forEach(p => {
      p.style.display = 'none';
      p.setAttribute('aria-hidden','true');
    });
    detailsModal.querySelectorAll('[role="tab"]').forEach(b => {
      b.classList.remove('active');
      b.setAttribute('aria-selected','false');
    });
    tabBtn.classList.add('active');
    tabBtn.setAttribute('aria-selected','true');
    const panel = detailsModal.querySelector(`#tab_${tabBtn.dataset.tab}`);
    panel.style.display = 'block';
    panel.setAttribute('aria-hidden','false');
  });

  // ---------- Form modal ----------
  const ALLOWED_SPECIES = ['dog','cat','bird','rabbit','hamster','fish','reptile','other'];
  const MAX_PHOTO_BYTES = 40 * 1024 * 1024; // 40 MB
  const ALLOWED_MIME = ['image/jpeg','image/png','image/webp','image/gif'];

  function clearFieldError(el) {
    try {
      el.removeAttribute('aria-invalid');
      el.classList.remove('is-invalid');
      const fg = el.closest('.form-group');
      fg?.querySelector('.error-text')?.remove();
    } catch {}
  }

  function setFieldError(el, msg) {
    try {
      el.setAttribute('aria-invalid','true');
      el.classList.add('is-invalid');
      const fg = el.closest('.form-group');
      if (fg && !fg.querySelector('.error-text')) {
        const small = document.createElement('small');
        small.className = 'error-text';
        small.style.color = '#d33';
        small.style.display = 'block';
        small.style.marginTop = '6px';
        small.textContent = msg;
        fg.appendChild(small);
      }
    } catch {}
  }

  function validateForm() {
    const errors = [];
    let firstEl = null;

    const nameEl   = petForm.elements['name'];
    const specEl   = petForm.elements['species'];
    const otherEl  = petForm.elements['species_other'];
    const bdateEl  = petForm.elements['birthdate'];
    const photoEl  = petForm.elements['photo'];

    [nameEl, specEl, otherEl, bdateEl, photoEl].forEach(el => el && clearFieldError(el));

    const name = (nameEl?.value || '').trim();
    if (!name || name.length < 2 || name.length > 120) {
      errors.push('Pet name is required (2–120 characters).');
      if (nameEl && !firstEl) firstEl = nameEl;
      if (nameEl) setFieldError(nameEl, 'Please enter a valid pet name.');
    }

    const species = (specEl?.value || '').trim().toLowerCase();
    if (!ALLOWED_SPECIES.includes(species)) {
      errors.push('Please select a valid species.');
      if (specEl && !firstEl) firstEl = specEl;
      if (specEl) setFieldError(specEl, 'Choose a species.');
    }

    if (species === 'other') {
      const other = (otherEl?.value || '').trim();
      if (!other) {
        errors.push('Please specify the species when "Other" is selected.');
        if (otherEl && !firstEl) firstEl = otherEl;
        if (otherEl) setFieldError(otherEl, 'Please specify the species.');
      }
    }

    const bd = (bdateEl?.value || '').trim();
    if (bd) {
      const d = new Date(bd);
      const today = new Date();
      if (isNaN(+d) || d > today) {
        errors.push('Birthdate cannot be in the future.');
        if (bdateEl && !firstEl) firstEl = bdateEl;
        if (bdateEl) setFieldError(bdateEl, 'Choose a valid past date.');
      }
    }

    const file = photoEl?.files?.[0];
    if (file) {
      if (!ALLOWED_MIME.includes(file.type)) {
        errors.push('Photo must be an image (JPG, PNG, WEBP, GIF).');
        if (photoEl && !firstEl) firstEl = photoEl;
        if (photoEl) setFieldError(photoEl, 'Invalid file type.');
      }
      if (file.size > MAX_PHOTO_BYTES) {
        errors.push('Photo must be 40 MB or smaller.');
        if (photoEl && !firstEl) firstEl = photoEl;
        if (photoEl) setFieldError(photoEl, 'File too large (max 40 MB).');
      }
    }
    return { ok: errors.length === 0, errors, firstEl };
  }

  petForm?.addEventListener('input', e => {
    const el = e.target;
    if (el && (el.matches('input,select,textarea'))) {
      clearFieldError(el);
    }
  });

  function openForm(mode = 'add', pet = null) {
    if (detailsModal?.classList.contains('show')) hideModal(detailsModal, { silent: true });
    petForm?.reset();
    const preview = document.getElementById('imagePreview');
    if (preview) {
      preview.style.backgroundImage = 'none';
      preview.textContent = 'Image Preview';
      preview.classList.remove('has-image');
    }
    const fileNameSpan = petForm?.querySelector('.file-input-name');
    if (fileNameSpan) fileNameSpan.textContent = 'No file chosen.';
    const titleEl = document.getElementById('petModalTitle') || document.getElementById('petFormTitle');
    if (titleEl) titleEl.textContent = (mode === 'edit') ? 'Edit Pet' : 'Add Pet';
    const idEl = document.getElementById('pet_id');
    if (idEl) idEl.value = pet?.id || '';
    petForm.querySelectorAll('.error-text').forEach(n => n.remove());
    petForm.querySelectorAll('[aria-invalid="true"]').forEach(n => n.removeAttribute('aria-invalid'));
    petForm.querySelectorAll('.is-invalid').forEach(n => n.classList.remove('is-invalid'));
    if (pet && petForm) {
      Object.keys(pet).forEach(k => {
        const el = petForm.elements[k];
        if (!el) return;
        if (el.type === 'checkbox') el.checked = !!Number(pet[k]);
        else el.value = pet[k] ?? '';
      });
      if (pet.photo_url && preview) {
        preview.style.backgroundImage = `url(${pet.photo_url})`;
        preview.textContent = '';
        preview.classList.add('has-image');
      }
    }
    [...petForm.elements].forEach(el => (el.disabled = false));
    if (mode === 'edit' && pet?.has_medical_history) {
      const protectedFields = ['name','species','breed','sex','birthdate'];
      protectedFields.forEach(fname => {
        const el = petForm.elements[fname];
        if (el) el.disabled = true;
      });
    }
    initDatePickerForForm();
    showModal(petModal);
  }

  async function confirmDelete(p) {
    if (p.has_medical_history) {
      Swal.fire({ icon:'error', title:'Action Not Allowed', text:'Pets with a medical history cannot be deleted.' });
      return;
    }
    const r = await Swal.fire({
      icon:'warning',
      title:`Delete ${p.name}?`,
      text:'This action cannot be undone.',
      showCancelButton:true,
      confirmButtonText:'Yes, delete it',
      confirmButtonColor:'#d33'
    });
    if (!r.isConfirmed) return;
    try {
      const fd = new FormData();
      fd.set('id', p.id);
      const res = await fetch(API.delete, { method:'POST', body: fd });
      const j = await res.json();
      if (!res.ok || !j.ok) throw new Error(j.error || 'Failed to delete');
      Swal.fire({ icon:'success', title:'Deleted!', timer:900, showConfirmButton:false });
      loadPets();
    } catch (err) {
      Swal.fire({ icon:'error', title:'Cannot Delete', text:String(err.message||err) });
    }
  }

  // ---------- Events ----------
  btnOpenPet?.addEventListener('click', () => openForm('add'));
  petSearch?.addEventListener('input', applyFilters);
  speciesChips?.addEventListener('click', e => {
    if (!e.target.classList.contains('chip')) return;
    const current = speciesChips.querySelector('.chip.active');
    if (current) {
      current.classList.remove('active');
      current.setAttribute('aria-selected','false');
    }
    e.target.classList.add('active');
    e.target.setAttribute('aria-selected','true');
    applyFilters();
  });
  petsGrid.addEventListener('click', e => {
    const card = e.target.closest('.pet-card');
    if (!card) return;
    const pet = pets.find(p => p.id == card.dataset.petId);
    if (!pet) return;
    if (e.target.closest('.js-view'))   openDetails(pet);
    if (e.target.closest('.js-edit'))   openForm('edit', pet);
    if (e.target.closest('.js-delete:not([disabled])')) confirmDelete(pet);
  });
  petForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const { ok, errors, firstEl } = validateForm();
    if (!ok) {
      const htmlList = `<ul style="text-align:left;margin:0 0 0 1rem;">${errors.map(x=>`<li>${escapeHtml(x)}</li>`).join('')}</ul>`;
      Swal.fire({ icon:'error', title:'Please fix the following', html: htmlList });
      if (firstEl) {
        try {
          firstEl.focus({ preventScroll: false });
          firstEl.scrollIntoView({ behavior:'smooth', block:'center' });
        } catch {}
      }
      return;
    }
    const id = document.getElementById('pet_id')?.value?.trim();
    const endpoint = id ? API.update : API.create;
    const loading = Swal.fire({
      title:'Saving…',
      didOpen:()=>Swal.showLoading(),
      allowOutsideClick:false,
      showConfirmButton:false
    });
    try {
      const fd = new FormData(petForm);
      if (!fd.has('sterilized')) fd.set('sterilized','0');
      const res = await fetch(endpoint, { method:'POST', body: fd });
      let j = null;
      const ct = res.headers.get('Content-Type') || '';
      if (ct.includes('application/json')) {
        j = await res.json();
      } else {
        const text = await res.text();
        throw new Error(text.slice(0, 140) || 'Unexpected response.');
      }
      if (!res.ok || !j.ok) {
        throw new Error(j?.error || `Server error (${res.status}).`);
      }
      await Swal.fire({ icon:'success', title:'Saved!', timer:1100, showConfirmButton:false });
      hideModal(petModal);
      loadPets();
    } catch (err) {
      Swal.fire({ icon:'error', title:'Cannot Save', text:String(err.message||err) });
    } finally {
      loading.close();
    }
  });
  petModal.addEventListener('change', e => {
    if (!e.target.matches('input[type="file"]#photo')) return;
    const fileNameSpan = e.target.closest('.form-group')?.querySelector('.file-input-name');
    const preview = document.getElementById('imagePreview');
    const file = e.target.files?.[0];
    if (file) {
      if (fileNameSpan) fileNameSpan.textContent = file.name;
      if (preview) {
        const reader = new FileReader();
        reader.onload = ev => {
          preview.style.backgroundImage = `url(${ev.target.result})`;
          preview.textContent = '';
          preview.classList.add('has-image');
        };
        reader.readAsDataURL(file);
      }
    } else {
      if (fileNameSpan) fileNameSpan.textContent = 'No file chosen.';
      if (preview) {
        preview.style.backgroundImage = 'none';
        preview.textContent = 'Image Preview';
        preview.classList.remove('has-image');
      }
    }
  });

  // ---------- Go! ----------
  loadPets();
});