// assets/js/landing-page.js
document.addEventListener('DOMContentLoaded', () => {
  /* ---------- Helpers ---------- */
  const navbar   = document.querySelector('.navbar');
  const navMenu  = document.querySelector('.navbar-nav');
  const hamburger = document.querySelector('.hamburger');
  const hamburgerIcon = hamburger ? hamburger.querySelector('i') : null;
  const navLinks = document.querySelectorAll('.nav-link');
  const sections = document.querySelectorAll('header, section[id]');

  const setMobileNavHeight = () => {
    const h = navbar ? navbar.offsetHeight : 70;
    document.documentElement.style.setProperty('--mobile-nav-height', `${h}px`);
  };
  setMobileNavHeight();
  window.addEventListener('resize', setMobileNavHeight);

  const lockScroll = (on) => {
    if (on) {
      const sb = window.innerWidth - document.documentElement.clientWidth;
      document.body.style.paddingRight = sb ? `${sb}px` : '';
      document.body.classList.add('no-scroll');
      document.addEventListener('touchmove', preventTouch, { passive: false });
    } else {
      document.body.classList.remove('no-scroll');
      document.body.style.paddingRight = '';
      document.removeEventListener('touchmove', preventTouch);
    }
  };
  const preventTouch = (e) => {
    if (!navMenu?.classList.contains('active')) return;
    if (!navMenu.contains(e.target)) e.preventDefault();
  };

  const setSidebarState = (open) => {
    if (!navMenu || !hamburgerIcon) return;
    if (open) {
      navMenu.classList.add('active');
      navMenu.setAttribute('aria-hidden', 'false');
      hamburger.setAttribute('aria-expanded', 'true');
      hamburgerIcon.classList.replace('fa-bars','fa-times');
      lockScroll(true);
    } else {
      navMenu.classList.remove('active');
      navMenu.setAttribute('aria-hidden', 'true');
      hamburger.setAttribute('aria-expanded', 'false');
      hamburgerIcon.classList.replace('fa-times','fa-bars');
      lockScroll(false);
    }
  };
  hamburger?.addEventListener('click', () => setSidebarState(!navMenu.classList.contains('active')));
  navLinks.forEach(l => l.addEventListener('click', () => { if (window.innerWidth <= 1200) setSidebarState(false); }));

  window.addEventListener('scroll', () => {
    navbar?.classList.toggle('scrolled', window.scrollY > 50);
    let current = '';
    const offset = (navbar?.offsetHeight || 60) + 10;
    sections.forEach(s => {
      const top = s.offsetTop, h = s.clientHeight;
      if (pageYOffset >= top - offset && pageYOffset < top + h - offset) current = s.getAttribute('id');
    });
    navLinks.forEach(a => {
      a.classList.remove('active');
      if (current && a.getAttribute('href').includes(current)) a.classList.add('active');
    });
  });

  /* ---------- AOS ---------- */
  if (window.AOS) {
    AOS.init({ duration: 800, once: true, offset: 120 });
    document.body.classList.add('aos-initialized');
  }

  /* ---------- GSAP: HERO floating + parallax ---------- */
  (function heroFloat(){
    if (!window.gsap) return; // graceful fallback if GSAP not loaded

    const circle  = document.querySelector('.hero-image-circle');
    const img     = document.querySelector('.hero-image-circle img');
    const badges  = window.gsap.utils.toArray('.floating-badges .badge');
    const hero    = document.querySelector('.hero-section');

    if (!circle || !img || !hero) return;

    // initial state
    gsap.set([circle, img, badges], { willChange: 'transform' });
    gsap.set(circle, { y: 0 });
    gsap.set(img, { rotate: 0, transformOrigin: '50% 50%' });

    // gentle bob on image
    gsap.to(circle, {
      y: -10,
      duration: 5,
      repeat: -1,
      yoyo: true,
      ease: 'sine.inOut'
    });

    // float each badge a bit differently
    badges.forEach((b, i) => {
      gsap.to(b, {
        y: `+=${10 + (i % 3) * 3}`,
        x: i % 2 ? '+=6' : '-=6',
        rotation: i % 2 ? 2 : -2,
        duration: 3 + Math.random() * 1.5,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut',
        delay: i * 0.15
      });
    });

    // entrance timeline (starts after page is ready / AOS applied)
    const startHero = () => {
      const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });
      tl.from('.hero-content h1', { y: 20, opacity: 0, duration: 0.6 })
        .from('.hero-content p',  { y: 20, opacity: 0, duration: 0.5 }, '-=0.25')
        .from('.hero-actions',    { y: 20, opacity: 0, duration: 0.5 }, '-=0.25')
        .from(circle,             { scale: 0.95, opacity: 0, duration: 0.6 }, '-=0.35')
        .from(badges,             { y: 12, opacity: 0, duration: 0.45, stagger: 0.12 }, '-=0.25');
    };
    // wait a tick so AOS doesn't clash
    window.addEventListener('load', () => setTimeout(startHero, 100));

    // parallax on mouse move (reduced on mobile)
    const strengthBase = () => (window.innerWidth <= 1200 ? 8 : 16);
    function onMove(e){
      const r = hero.getBoundingClientRect();
      const relX = (e.clientX - r.left) / r.width - 0.5;
      const relY = (e.clientY - r.top)  / r.height - 0.5;
      const s = strengthBase();

      gsap.to(circle, { x: relX * s, y: relY * s, rotation: relX * 3, duration: 0.6, ease: 'power2.out' });
      badges.forEach((b, i) => {
        gsap.to(b, { x: relX * (s * (0.6 + i * 0.25)), y: relY * (s * (0.6 + i * 0.25)), duration: 0.6, ease: 'power2.out' });
      });
    }
    hero.addEventListener('mousemove', onMove);
  })();

  /* ---------- Splide (Announcements) ---------- */
  const ann = document.getElementById('announcements-slider');
  if (ann && window.Splide && ann.querySelectorAll('.splide__slide').length) {
    new Splide(ann, {
      type: 'loop',
      perPage: 1,
      autoplay: true,
      interval: 4800,
      pauseOnHover: true,
      pagination: true,
      arrows: true
    }).mount();
  }

  /* ---------- Glide (Testimonials) ---------- */
  const test = document.getElementById('testimonials-slider');
  if (test && window.Glide) {
    new Glide(test, {
      type:'carousel',
      startAt:0,
      perView:3,
      focusAt:'center',
      gap:30,
      autoplay:3800,
      hoverpause:true,
      breakpoints:{ 1280:{perView:3}, 1200:{perView:2}, 768:{perView:1} }
    }).mount();
  }

  /* ---------- Tips filter (Flip animation if available) ---------- */
  const filterControls = document.querySelector('.filter-controls');
  const tipsGrid = document.getElementById('tipsGrid');
  if (filterControls && tipsGrid) {
    const cards = Array.from(tipsGrid.querySelectorAll('.tip-card'));
    const simpleFilter = (f)=>cards.forEach(c=>c.classList.toggle('hidden', !(f==='all'||c.dataset.category===f)));
    const flipFilter = (f)=>{
      const state = Flip.getState(cards);
      simpleFilter(f);
      Flip.from(state, {
        duration:.6, ease:"power2.inOut", stagger:.03, scale:true,
        onEnter:(els)=>gsap.fromTo(els,{opacity:0,scale:.92},{opacity:1,scale:1,duration:.35,stagger:.04,ease:"power2.out"}),
        onLeave:(els)=>gsap.to(els,{opacity:0,scale:.9,duration:.25,stagger:.03,ease:"power2.in"})
      });
    };
    const apply = (f)=> (window.gsap && window.Flip) ? flipFilter(f) : simpleFilter(f);
    apply('all');
    filterControls.addEventListener('click',(e)=>{
      if(e.target.tagName!=='BUTTON'||e.target.classList.contains('active'))return;
      filterControls.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
      e.target.classList.add('active');
      apply(e.target.dataset.filter||'all');
    });
  }

  /* ---------- Shared modal for Announcements + Tips ---------- */
  const modal = document.getElementById('announcementModal');
  if (modal) {
    const title = document.getElementById('modalTitle');
    const imgC  = document.getElementById('modalImageContainer');
    const bodyC = document.getElementById('modalBody');
    const close = () => modal.classList.remove('active');
    modal.querySelector('.close-button')?.addEventListener('click', close);
    modal.addEventListener('click', (e)=>{ if(e.target === modal) close(); });

    if (ann) {
      ann.addEventListener('click',(e)=>{
        if(!e.target.classList.contains('view-details')) return;
        const slide = e.target.closest('.splide__slide'); if(!slide) return;
        const { type, title:t } = slide.dataset;
        const img = slide.dataset.image, url = slide.dataset.url, desc = slide.dataset.description;
        title.textContent = t || '';
        imgC.innerHTML = img ? `<img src="${img}" alt="${t||'Announcement'}">` : '';
        bodyC.innerHTML = desc ? `<p>${desc}</p>` : '';
        if (type==='external' && url) bodyC.innerHTML += `<p><a href="${url}" target="_blank" class="btn">Go to Document</a></p>`;
        modal.classList.add('active');
      });
    }

    if (tipsGrid) {
      tipsGrid.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-read-more');
        if (!btn) return;
        const card = btn.closest('.tip-card'); if (!card) return;
        const type = card.dataset.type || 'internal';
        const url  = card.dataset.url  || '';
        const t    = card.dataset.title || '';
        const img  = card.dataset.image || '';
        const article = card.querySelector('.tip-article')?.innerHTML || '';

        if ((type === 'external' || type === 'file') && url) {
          window.open(url, '_blank', 'noopener');
          return;
        }
        title.textContent = t || '';
        imgC.innerHTML = img ? `<img src="${img}" alt="${t||'Tip'}">` : '';
        bodyC.innerHTML = article || '';
        modal.classList.add('active');
      });
    }
  }

  /* ---------- Fractional rating input (form) ---------- */
  const ratingEl = document.getElementById('ratingInput');
  const ratingFill = document.getElementById('ratingFill');
  const ratingReading = document.getElementById('ratingReading');
  let ratingVal = 0.0;
  const setRating = (v) => {
    const clamped = Math.max(0.1, Math.min(5, Math.round(v*10)/10));
    ratingVal = clamped;
    ratingFill.style.width = `${(ratingVal/5)*100}%`;
    ratingEl?.setAttribute('aria-valuenow', ratingVal.toFixed(1));
    ratingReading.textContent = `(${ratingVal.toFixed(1)})`;
  };
  const calcFromX = (evt) => {
    const r = ratingEl.getBoundingClientRect();
    const clientX = (evt.touches && evt.touches[0]) ? evt.touches[0].clientX : evt.clientX;
    const x = clientX - r.left;
    const ratio = Math.max(0, Math.min(1, x / r.width));
    return ratio * 5;
  };
  let dragging = false;
  ratingEl?.addEventListener('mousedown', (e)=>{ dragging = true; setRating(calcFromX(e)); });
  window.addEventListener('mouseup', ()=> dragging = false);
  ratingEl?.addEventListener('mousemove', (e)=>{ if(dragging) setRating(calcFromX(e)); });
  ratingEl?.addEventListener('click', (e)=> setRating(calcFromX(e)));
  ratingEl?.addEventListener('touchstart', (e)=> setRating(calcFromX(e)), {passive:true});
  ratingEl?.addEventListener('touchmove',  (e)=> setRating(calcFromX(e)), {passive:true});
  ratingEl?.addEventListener('keydown', (e)=>{
    if (e.key === 'ArrowRight' || e.key === 'ArrowUp') { setRating(ratingVal + 0.1); e.preventDefault(); }
    if (e.key === 'ArrowLeft'  || e.key === 'ArrowDown'){ setRating(ratingVal - 0.1); e.preventDefault(); }
  });

  /* ---------- Feedback form -> API ---------- */
  const form = document.getElementById('feedbackForm');
  if (form) {
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const name  = document.getElementById('feedbackName').value.trim();
      const email = document.getElementById('feedbackEmail').value.trim();
      const msg   = document.getElementById('feedbackMessage').value.trim();
      if(!name||!email||!msg||ratingVal<=0)
        return Swal.fire({icon:'warning',title:'Incomplete',text:'Fill out all fields and give a rating (decimals allowed).'});
      if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))
        return Swal.fire({icon:'error',title:'Invalid Email',text:'Please enter a valid email address.'});

      const payload = {name, email, message: msg, rating: ratingVal.toFixed(1)};

      try{
        const res  = await fetch(`${window.APP_BASE}api/feedbacks.php?action=create`, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Failed to submit');
        Swal.fire({icon:'success',title:'Thank you!',text:'Your feedback was submitted for review.',timer:1800,showConfirmButton:false});
        form.reset(); ratingVal = 0.0; ratingFill.style.width = '0%'; ratingReading.textContent='(0.0)';
      }catch(err){
        Swal.fire({icon:'error',title:'Error',text:err.message || 'Submission failed'});
      }
    });
  }
});
