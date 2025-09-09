document.addEventListener('DOMContentLoaded', () => {
  // --- Change Password Modal Logic ---
  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const passwordModal     = document.getElementById('passwordModal');
  const closeModalBtn     = document.getElementById('closeModalBtn');
  const cancelModalBtn    = document.getElementById('cancelModalBtn');
  const modalBackdrop     = document.getElementById('modalBackdrop');
  const passwordChangeForm= document.getElementById('passwordChangeForm');

  const openModal  = () => { passwordModal?.classList.add('show'); modalBackdrop?.removeAttribute('hidden'); modalBackdrop?.classList.add('show'); };
  const closeModal = () => { passwordModal?.classList.remove('show'); modalBackdrop?.setAttribute('hidden', ''); modalBackdrop?.classList.remove('show'); passwordChangeForm?.reset(); };

  changePasswordBtn?.addEventListener('click', openModal);
  closeModalBtn?.addEventListener('click', closeModal);
  cancelModalBtn?.addEventListener('click', closeModal);
  modalBackdrop?.addEventListener('click', closeModal);

  if (passwordChangeForm) {
    passwordChangeForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(passwordChangeForm).entries());
      fetch(`${App.BASE_URL}api/change-password`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      })
      .then(r => r.json())
      .then(result => {
        if (result.status === 'success') {
          closeModal();
          Swal.fire({ icon: 'success', title: 'Success!', text: result.message });
        } else {
          Swal.fire({ icon: 'error', title: 'Oops...', text: result.message });
        }
      })
      .catch(err => {
        console.error('Error:', err);
        Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Could not connect to the server.' });
      });
    });
  }

  // --- Profile Form Enhancements ---
  const profileForm     = document.getElementById('profileForm');
  const saveChangesBtn  = document.getElementById('saveChangesBtn');
  const cancelChangesBtn= document.getElementById('cancelChangesBtn');

  // Username availability check + "required" rule
  const usernameInput = document.getElementById('username');
  const usernameHint  = document.getElementById('usernameHint');
  const initialUsername = usernameInput?.value?.trim() || '';
  const reUsername = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9_]{4,20}$/; // letters+numbers required, underscore ok
  let invalidUsername = false;
  let userDebounce = null;
  let lastChecked = '';

  function hintErr(msg){ if (usernameHint){ usernameHint.textContent = msg; usernameHint.className = 'hint err-hint'; } usernameInput?.classList.add('field-error'); }
  function hintOk(msg=''){ if (usernameHint){ usernameHint.textContent = msg; usernameHint.className = msg ? 'hint ok-hint' : 'hint'; } usernameInput?.classList.remove('field-error'); }

  async function checkUsernameAvailability(u){
    try{
      if (u === initialUsername) return true; // unchanged
      const url = new URL(`${App.BASE_URL}api/check-username`, window.location.origin);
      url.searchParams.set('u', u);
      const res = await fetch(url.toString(), { cache: 'no-store' });
      const j   = await res.json();
      return !!(j && j.ok && j.available === true);
    }catch(_){ return true; }
  }

  function validateUsernameLive(){
    if (!usernameInput || usernameInput.disabled) return;
    const u = usernameInput.value.trim();

    // unchanged -> OK
    if (u === initialUsername) {
      invalidUsername = false; hintOk('');
      updateButtons();
      return;
    }

    // REQUIRED
    if (!u) {
      invalidUsername = true;
      hintErr('Username is required');
      updateButtons();
      return;
    }

    // REGEX
    if (!reUsername.test(u)){
      invalidUsername = true;
      hintErr('4–20 chars, must include letters and numbers (underscore allowed)');
      updateButtons();
      return;
    }

    // availability (debounced)
    clearTimeout(userDebounce);
    userDebounce = setTimeout(async ()=>{
      if (u === lastChecked) return;
      lastChecked = u;
      const available = await checkUsernameAvailability(u);
      invalidUsername = !available;
      if (available) hintOk('Username is available ✓');
      else hintErr('Username is already taken.');
      updateButtons();
    }, 350);
  }

  usernameInput?.addEventListener('input', validateUsernameLive);
  usernameInput?.addEventListener('blur',  validateUsernameLive);

  // Form dirty tracking
  function getFormState(form){
    const state = {};
    const fd = new FormData(form);
    for (const [k, v] of fd.entries()) state[k] = (v instanceof File) ? v.name : v;
    return JSON.stringify(state);
  }
  let initialFormState = profileForm ? getFormState(profileForm) : '';

  function updateButtons(){
    if (!profileForm || !saveChangesBtn || !cancelChangesBtn) return;
    const changed = (getFormState(profileForm) !== initialFormState);
    saveChangesBtn.disabled = !changed || invalidUsername;
    cancelChangesBtn.hidden = !changed;
  }

  if (profileForm && saveChangesBtn && cancelChangesBtn) {
    profileForm.addEventListener('input',  updateButtons);
    profileForm.addEventListener('change', updateButtons);

    cancelChangesBtn.addEventListener('click', () => {
      profileForm.reset();
      if (usernameInput){
        usernameInput.value = initialUsername;
        invalidUsername = false;
        hintOk('');
      }
      updateButtons();
      const fileNameSpan = document.getElementById('fileName');
      if (fileNameSpan) fileNameSpan.textContent = 'No file chosen';
    });

    // Final submit guard (required + availability)
    profileForm.addEventListener('submit', (e)=>{
      if (usernameInput && !usernameInput.disabled) {
        const u = usernameInput.value.trim();
        if (!u) {
          e.preventDefault();
          hintErr('Username is required');
          Swal.fire({icon:'error', title:'Missing username', text:'Please enter a username.'});
          return;
        }
      }
      if (invalidUsername){
        e.preventDefault();
        Swal.fire({icon:'error', title:'Username not available', text:'Please choose a different username.'});
      }
    });
  }

  // --- Phone Number & ZIP Formatting ---
  const phoneInput = document.getElementById('phone');
  if (phoneInput) {
    const formatPhoneNumber = (value) => {
      if (!value) return '';
      let digits = value.replace(/\D/g, '');
      if (digits.length > 11) digits = digits.slice(0, 11);
      let formatted = '';
      if (digits.length > 7) {
        formatted = `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7)}`;
      } else if (digits.length > 4) {
        formatted = `${digits.slice(0, 4)} ${digits.slice(4)}`;
      } else {
        formatted = digits;
      }
      return formatted;
    };
    phoneInput.value = formatPhoneNumber(phoneInput.value);
    phoneInput.addEventListener('input', (e) => {
      e.target.value = formatPhoneNumber(e.target.value);
    });
  }

  const zipInput = document.getElementById('address_zip');
  if (zipInput) {
    zipInput.addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D/g, ''); });
  }

  // --- File Upload & Image Zoom Logic ---
  const fileInput = document.getElementById('avatar');
  const fileNameSpan = document.getElementById('fileName');
  if (fileInput && fileNameSpan) {
    fileInput.addEventListener('change', () => {
      if (fileInput.files.length > 0) { fileNameSpan.textContent = fileInput.files[0].name; }
      else { fileNameSpan.textContent = 'No file chosen'; }
    });
  }

  const profileAvatar = document.querySelector('.profile-avatar-large');
  if (profileAvatar) {
    profileAvatar.addEventListener('click', () => {
      Swal.fire({
        imageUrl: profileAvatar.src,
        imageAlt: 'Profile Picture',
        width: 'auto',
        showCloseButton: true,
        showConfirmButton: false,
        background: 'transparent',
        backdrop: `rgba(0,0,0,0.8)`
      });
    });
    profileAvatar.style.cursor = 'pointer';
  }
});
