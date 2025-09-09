<?php $BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>PawPals - Sign Up</title>

  <link rel="stylesheet" href="<?= $BASE ?>assets/css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    .field-error{border-color:#e74c3c!important;box-shadow:0 0 0 3px rgba(231,76,60,.15)!important}
    .hint{font-size:.85rem;color:var(--text-medium)}
    .ok-hint{color:#16a34a}.err-hint{color:#e74c3c}
    .btn[disabled]{opacity:.7;pointer-events:none}
  </style>
</head>
<body>

  <a class="back-home" href="<?= $BASE ?>">
    <i class="fa-solid fa-arrow-left"></i><span>Back to Home</span>
  </a>

  <div class="auth-layout">
    <div class="auth-branding-panel">
      <div class="branding-content">
        <div class="logo">🐾 PawPals</div>
        <h1>Join Our Community!</h1>
        <p>Create an account to easily manage your pet's health records and book appointments online.</p>
        <div class="copyright">&copy; <?= date('Y') ?> PawPals. All rights reserved.</div>
      </div>
    </div>

    <div class="auth-form-panel">
      <div class="auth-card">
        <div class="form-header"><h3>Create Your Account</h3></div>

        <form id="signup-form" novalidate>
          <div class="row-2">
            <div class="form-group">
              <label for="firstname">First Name</label>
              <input type="text" id="firstname" name="first_name" placeholder="Juan" required
                     autocomplete="given-name" maxlength="80">
            </div>
            <div class="form-group">
              <label for="lastname">Last Name</label>
              <input type="text" id="lastname" name="last_name" placeholder="Dela Cruz" required
                     autocomplete="family-name" maxlength="80">
            </div>
          </div>

          <div class="row-2">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" placeholder="you@example.com" required
                     autocomplete="email"
                     pattern="^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$"
                     title="Must be a valid email (e.g., name@example.com)">
              <small id="emailHint" class="hint"></small>
            </div>
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" placeholder="e.g., juan123" required
                     autocomplete="username" minlength="4" maxlength="20"
                     pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9_]{4,20}$"
                     title="4–20 chars, letters+numbers required, underscore allowed">
              <small id="userHint" class="hint">4–20 chars, must include letters and numbers (underscore allowed)</small>
            </div>
          </div>

          <div class="row-2">
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" placeholder="0912 345 6789" required
                     inputmode="numeric" maxlength="14"
                     pattern="^09\d{2}\s\d{3}\s\d{4}$"
                     title="PH mobile format: 0912 345 6789">
              <small id="phoneHint" class="hint">Format: <b>0912 345 6789</b> (11 digits)</small>
            </div>
            <div class="form-group">
              <label for="sex">Sex</label>
              <select id="sex" name="sex" required>
                <option value="" disabled selected>Select...</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>

          <div class="row-2">
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" placeholder="Create a strong password" required
                     autocomplete="new-password"
                     pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
                     title="Min 8 chars, include uppercase, lowercase, number, and @ $ ! % * ? &">
              <small class="hint">Min 8 chars, with <b>uppercase</b>, <b>lowercase</b>, <b>number</b>, and <b>@$!%*?&</b></small>
            </div>
            <div class="form-group">
              <label for="confirm-password">Confirm Password</label>
              <input type="password" id="confirm-password" name="confirm" placeholder="Confirm your password" required
                     autocomplete="new-password">
              <small id="pwHint" class="hint"></small>
            </div>
          </div>

          <button id="submitBtn" type="submit" class="btn btn-primary">
            <i class="fa-solid fa-user-plus"></i> Create Account
          </button>
        </form>

        <div class="auth-switch-link">
          <p>Already have an account? <a href="<?= $BASE ?>auth/login">Login now</a></p>
        </div>
      </div>
    </div>
  </div>

<script>
(function(){
  const $ = (s)=>document.querySelector(s);

  const form   = $('#signup-form');
  const email  = $('#email');
  const uname  = $('#username');
  const phone  = $('#phone');
  const pwd    = $('#password');
  const cpw    = $('#confirm-password');
  const btn    = $('#submitBtn');

  const emailHint = $('#emailHint');
  const userHint  = $('#userHint');
  const phoneHint = $('#phoneHint');
  const pwHint    = $('#pwHint');

  const reEmail       = /^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/;
  const reUsername    = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9_]{4,20}$/;
  const rePhoneRaw    = /^09\d{9}$/;
  const rePass        = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

  const setErr = (el, hintEl, msg)=>{ el.classList.add('field-error'); if(hintEl){hintEl.textContent=msg;hintEl.className='hint err-hint';} }
  const clrErr = (el, hintEl, msg='')=>{ el.classList.remove('field-error'); if(hintEl){hintEl.textContent=msg;hintEl.className= msg?'hint ok-hint':'hint';} }

  function formatPhonePretty(v){
    v = (v||'').replace(/\D/g,'').slice(0,11);
    if (v.length <= 4) return v;
    if (v.length <= 7) return v.slice(0,4)+' '+v.slice(4);
    return v.slice(0,4)+' '+v.slice(4,7)+' '+v.slice(7);
  }

  phone.value = formatPhonePretty(phone.value);
  phone.addEventListener('input', (e)=>{
    e.target.value = formatPhonePretty(e.target.value);
    const raw = e.target.value.replace(/\D/g,'');
    if (!rePhoneRaw.test(raw)) setErr(phone, phoneHint, 'Must be 11 digits starting with 09 (e.g., 0912 345 6789)');
    else clrErr(phone, phoneHint, 'Looks good!');
  });
  phone.addEventListener('keypress', (e)=>{ if(!/[0-9]/.test(e.key)) e.preventDefault(); });

  email.addEventListener('input', ()=>{
    reEmail.test(email.value.trim()) ? clrErr(email,emailHint) : setErr(email,emailHint,'Enter a valid email (e.g., name@example.com)');
  });

  function checkPw(){
    let ok = true;
    if (!rePass.test(pwd.value)) { setErr(pwd,pwHint,'Weak password. Follow the rules above.'); ok=false; }
    else clrErr(pwd,pwHint);
    if (cpw.value){
      if (pwd.value !== cpw.value) { setErr(cpw,pwHint,'Passwords do not match'); ok=false; }
      else clrErr(cpw,pwHint,'Looks good!');
    }
    return ok;
  }
  pwd.addEventListener('input', checkPw);
  cpw.addEventListener('input', checkPw);

  let uTimer=null,lastChecked='';
  async function usernameAvailable(u){
    try{
      const url = new URL('<?= $BASE ?>api/check-username', location.origin);
      url.searchParams.set('u', u);
      const res = await fetch(url, {cache:'no-store'});
      const j = await res.json();
      return !!(j && j.ok && j.available === true);
    }catch{ return true; }
  }
  uname.addEventListener('input', ()=>{
    const u = uname.value.trim();
    clearTimeout(uTimer);
    if (!reUsername.test(u)) { setErr(uname,userHint,'4–20 chars, must include letters and numbers (underscore allowed)'); return; }
    uTimer = setTimeout(async ()=>{
      if (u===lastChecked) return; lastChecked=u;
      const available = await usernameAvailable(u);
      if (!available) setErr(uname,userHint,'Username is already taken.');
      else clrErr(uname,userHint,'Username is available ✓');
    }, 350);
  });

  form?.addEventListener('submit', async (e)=>{
    e.preventDefault();

    ['firstname','lastname','email','username'].forEach(id=>{
      const el = document.getElementById(id);
      if (el) el.value = el.value.trim();
    });

    let bad=false;
    if (!reEmail.test(email.value.trim())) { setErr(email,emailHint,'Enter a valid email'); bad=true; } else clrErr(email,emailHint);
    if (!reUsername.test(uname.value.trim())) { setErr(uname,userHint,'4–20 chars, must include letters and numbers (underscore allowed)'); bad=true; }
    const rawPhone = phone.value.replace(/\D/g,'');
    if (!rePhoneRaw.test(rawPhone)) { setErr(phone,phoneHint,'Must be 11 digits starting with 09 (e.g., 0912 345 6789)'); bad=true; } else clrErr(phone,phoneHint);
    if (!rePass.test(pwd.value)) { setErr(pwd,pwHint,'Weak password.'); bad=true; }
    if (pwd.value !== cpw.value) { setErr(cpw,pwHint,'Passwords do not match'); bad=true; }
    const sex = document.getElementById('sex'); if (!sex.value){ sex.classList.add('field-error'); bad=true; } else sex.classList.remove('field-error');
    if (bad){ Swal.fire({icon:'error', title:'Check the form', text:'Please fix the highlighted fields.'}); return; }

    const avail = await usernameAvailable(uname.value.trim());
    if (!avail){ setErr(uname,userHint,'Username is already taken.'); Swal.fire({icon:'error', title:'Username taken', text:'Please choose a different username.'}); return; }

    // show loader immediately + disable button (avoid double click)
    btn.disabled = true;
    Swal.fire({title:'Creating your account', html:'Please wait…', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});

    try{
      const fd = new FormData(form);
      fd.set('phone', rawPhone); // digits only
      const res = await fetch('<?= $BASE ?>api/pet-owners-register', { method:'POST', body: fd });
      const j   = await res.json();
      if (!res.ok || !j.ok) throw new Error(j.error || 'Sign up failed');

      await Swal.fire({
        icon:'success',
        title:'Verify your email',
        html:`We emailed <b>${j.email}</b> a verification link. Please open it to activate your account.`,
        confirmButtonText:'Open verification page'
      });
      location.href = '<?= $BASE ?>auth/verify-email?email=' + encodeURIComponent(j.email);
    }catch(err){
      btn.disabled = false;
      Swal.fire({icon:'error', title:'Cannot sign up', text:String(err.message || err)});
    }
  });
})();
</script>
</body>
</html>
