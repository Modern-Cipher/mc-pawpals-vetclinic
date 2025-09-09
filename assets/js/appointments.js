// assets/js/appointments.js
document.addEventListener('DOMContentLoaded', () => {
    // ---------- BASE detection ----------
    const BASE = (() => {
        if (window.App && typeof window.App.BASE_URL === 'string') {
            return window.App.BASE_URL;
        }
        console.warn('PHP App.BASE_URL not detected. Guessing from URL path.');
        const pathParts = location.pathname.split('/').filter(Boolean);
        return pathParts.length > 1 ? `/${pathParts[0]}/` : '/';
    })();
    const CB = () => 'cb=' + Date.now();

    // ===== START OF FIX: Tinawag na natin ang .php files nang direkta =====
    const API = {
        pets:              `${BASE}api/pets/list.php?${CB()}`,
        createAppointment: `${BASE}api/appointments/create.php`,
        updateAppointment: `${BASE}api/appointments/update.php`,
        deleteAppointment: `${BASE}api/appointments/delete.php`,
        listAppointments:  `${BASE}api/appointments/list_mine.php`,
        slots:             `${BASE}api/appointments/slots.php`,
    };
    // ===== END OF FIX =====

    // ---------- Elements ----------
    const modal       = document.getElementById('bookingModal');
    const modalTitle  = document.getElementById('bookingModalTitle');
    const form        = document.getElementById('bookingForm');
    const dateInput   = document.getElementById('appointment_date');
    const timeSelect  = document.getElementById('appointment_time');
    const serviceSel  = document.getElementById('service');
    const notesInput  = document.getElementById('notes');
    const upcomingEl  = document.getElementById('upcomingList');
    const historyEl   = document.getElementById('historyList');
    const historyToggle = document.getElementById('historyToggle');

    const hiddenPetId = document.getElementById('pet_id');
    const petPicker   = document.getElementById('petPicker');
    const petTrigger  = document.getElementById('petTrigger');
    const petMenu     = document.getElementById('petMenu');
    const petLabel    = petTrigger?.querySelector('.pet-label');
    const petThumb    = petTrigger?.querySelector('.pet-thumb');

    // ---------- Modal helpers ----------
    const showModal = () => { if (modal) { modal.classList.add('show'); modal.removeAttribute('hidden'); } };
    const hideModal = () => { if (modal) { modal.classList.remove('show'); modal.setAttribute('hidden',''); } };
    const btnClose  = document.getElementById('closeBookingModal');
    const btnCancel = document.getElementById('cancelBooking');
    btnClose && btnClose.addEventListener('click', resetAndHide);
    btnCancel && btnCancel.addEventListener('click', resetAndHide);

    let editingId = null;
    function resetAndHide(){ editingId=null; form?.reset?.(); toggleOtherField(); hideModal(); }

    const OTHER_ID = 'service_other';
    function ensureOtherInput() {
        let txt = document.getElementById(OTHER_ID);
        if (!txt) {
            txt = document.createElement('input');
            txt.type = 'text';
            txt.id = OTHER_ID;
            txt.name = 'service_other';
            txt.className = 'service-other-input';
            txt.placeholder = 'Specify the service…';
            serviceSel.parentElement.appendChild(txt);
        }
        return txt;
    }
    function toggleOtherField(prefillVal='') {
        const txt = ensureOtherInput();
        const isOther = String(serviceSel?.value || '') === 'Other';
        txt.style.display = isOther ? '' : 'none';
        if (isOther && prefillVal) txt.value = prefillVal;
        if (!isOther && !editingId) txt.value = '';
    }

    // ---------- Slots ----------
    const isDisabledFlag = (s) => !!(s?.disabled || s?.is_disabled || s?.booked || s?.unavailable || s?.full);

    async function fetchSlotsFor(dateStr, excludeId = null) {
        try {
            if (!dateStr) {
                timeSelect.innerHTML = '<option value="">-- Select a date first --</option>';
                return;
            }
            // Note: API.slots is already a full URL with cache buster
            const u = new URL(API.slots, location.origin);
            u.searchParams.set('date', dateStr);
            if (hiddenPetId?.value) u.searchParams.set('pet_id', hiddenPetId.value);
            if (serviceSel?.value)  u.searchParams.set('service', serviceSel.value);
            if (excludeId)          u.searchParams.set('exclude_id', excludeId);

            const prev = timeSelect.value;
            const res  = await fetch(u.toString(), { credentials:'same-origin' });
            const data = await res.json().catch(() => ({}));

            timeSelect.innerHTML = '<option value="">-- Select a time --</option>';

            if (res.ok && data.ok && Array.isArray(data.slots) && data.slots.length) {
                data.slots.forEach(s => {
                    const v = typeof s === 'string' ? s : (s.value || s.time);
                    if (!v) return;
                    const opt = new Option(v, v, false, false);
                    if (isDisabledFlag(s)) opt.disabled = true;
                    timeSelect.appendChild(opt);
                });
                if (prev && [...timeSelect.options].some(o => o.value === prev && !o.disabled)) {
                    timeSelect.value = prev;
                }
            } else {
                timeSelect.innerHTML = '<option value="">No slots available</option>';
            }
        } catch {
            timeSelect.innerHTML = '<option value="">Error loading slots</option>';
        }
    }
    function ensureTimeOption(t) {
        if (![...timeSelect.options].some(o => o.value === t)) {
            timeSelect.appendChild(new Option(t, t, true, true));
        }
    }
    function toTime12h(dateObj){
        return dateObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    }

    // ---------- Open modal (new/edit) ----------
    function openBookingModal(dateInfo = null, prefill = null) {
        if (!prefill) form?.reset?.();

        if (dateInfo) {
            modalTitle.textContent = `Book for ${dateInfo.date.toLocaleString('en-US',{month:'long', day:'numeric'})}`;
            dateInput.value = dateInfo.dateStr;
            fetchSlotsFor(dateInfo.dateStr, editingId);
        } else if (!prefill) {
            modalTitle.textContent = 'Book New Appointment';
            dateInput.value = '';
            timeSelect.innerHTML = '<option value="">-- Select a date first --</option>';
        } else {
            modalTitle.textContent = 'Edit Appointment';
        }

        if (prefill) {
            if (prefill.pet_id) setPetSelection(String(prefill.pet_id));
            const selectValues = [...serviceSel.options].map(o => o.value);
            if (prefill.service && !selectValues.includes(prefill.service)) {
                serviceSel.value = 'Other';
                toggleOtherField(prefill.service);
            } else {
                serviceSel.value = prefill.service || '';
                toggleOtherField();
            }
            if (prefill.start) {
                const d = new Date(prefill.start);
                const ds = d.toISOString().slice(0,10);
                dateInput.value = ds;
                fetchSlotsFor(ds, editingId).then(() => {
                    const t12 = toTime12h(d);
                    ensureTimeOption(t12);
                    timeSelect.value = t12;
                });
            }
            if (typeof prefill.notes !== 'undefined' && notesInput) notesInput.value = prefill.notes || '';
        } else {
            toggleOtherField();
        }

        showModal();
    }

    // ---------- Pet picker ----------
    let petList = [];
    function setPetSelection(id) {
        const pet = petList.find(p => String(p.id) === String(id));
        if (!pet) return;
        hiddenPetId.value = pet.id;
        if (petLabel) petLabel.textContent = pet.name;
        if (petThumb) {
            petThumb.classList.remove('placeholder');
            petThumb.style.backgroundImage = pet.photo_url ? `url('${pet.photo_url}')` : 'none';
        }
        petPicker?.classList.remove('open');
        petPicker?.setAttribute('aria-expanded','false');
    }
    function togglePetMenu() {
        if (!petPicker) return;
        const willOpen = !petPicker.classList.contains('open');
        petPicker.classList.toggle('open', willOpen);
        petPicker.setAttribute('aria-expanded', String(willOpen));
    }
    petTrigger && petTrigger.addEventListener('click', togglePetMenu);
    document.addEventListener('click', (e) => {
        if (!petPicker?.contains(e.target)) {
            petPicker?.classList.remove('open');
            petPicker?.setAttribute('aria-expanded','false');
        }
    });

    async function fetchPets() {
        try {
            const res = await fetch(API.pets, { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Could not fetch pets.');
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to load pets.');

            petList = data.pets || [];
            if (!petMenu) return;

            if (!petList.length) {
                petMenu.innerHTML = `<li class="pet-empty">No pets found. <a href="${BASE}dashboard/users/pets">Add one?</a></li>`;
                petLabel && (petLabel.textContent = 'No pets');
                hiddenPetId.value = '';
                return;
            }

            petMenu.innerHTML = petList.map(p => `
                <li class="pet-option" role="option" data-id="${p.id}" title="${p.name}">
                    <span class="pet-thumb" style="${p.photo_url ? `background-image:url('${p.photo_url}')` : ''}"></span>
                    <span class="name">${p.name}</span>
                    <span class="meta">${(p.breed || p.species || '').toString()}</span>
                </li>
            `).join('');

            petMenu.addEventListener('click', (ev) => {
                const li = ev.target.closest('.pet-option');
                if (!li) return;
                setPetSelection(li.dataset.id);
                if (dateInput.value) fetchSlotsFor(dateInput.value, editingId);
            });

            if (petList[0]) setPetSelection(petList[0].id);
        } catch (err) {
            console.error(err);
            if (petLabel) petLabel.textContent = 'Could not load pets';
            hiddenPetId.value = '';
            if (petMenu) petMenu.innerHTML = `<li class="pet-empty error">Could not load pets</li>`;
        }
    }
    
    function renderSideList(element, events, title) {
        if (!element) return;
        if (!events.length) {
            element.innerHTML = `<p class="muted">No ${title} appointments.</p>`;
            return;
        }
        element.innerHTML = events.map(e => {
            const ep = e.extendedProps || {};
            const status = ep.status || '';
            const statusClass = status.toLowerCase().replace(' ','-');
            const photo = ep.pet_photo_url ? `style="background-image:url('${ep.pet_photo_url}')"` : '';
            return `
                <div class="appointment-item status-${statusClass}">
                    <div class="thumb" ${photo}></div>
                    <div class="appt-right">
                        <div class="service-status-line">
                            <div class="service">${String(e.title).split(' for ')[0]}</div>
                            <span class="status-badge ${statusClass}">${status}</span>
                        </div>
                        <div class="pet-name">For: ${ep.pet_name || ''}</div>
                        <div class="datetime">${new Date(e.start).toLocaleString('en-US',{dateStyle:'medium', timeStyle:'short'})}</div>
                    </div>
                </div>`;
        }).join('');
    }

    function renderUpcomingAndHistory(events) {
        const now = new Date();
        const upcoming = events
            .filter(e => new Date(e.start) >= now && ['Pending','Confirmed'].includes(e.extendedProps?.status))
            .sort((a,b) => new Date(a.start) - new Date(b.start));
        
        const history = events
            .filter(e => new Date(e.start) < now || ['Completed','Cancelled','No-Show'].includes(e.extendedProps?.status))
            .sort((a,b) => new Date(b.start) - new Date(a.start));

        renderSideList(upcomingEl, upcoming, 'upcoming');
        renderSideList(historyEl, history, 'past');
    }

    // ---------- Calendar ----------
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    function enhanceDayCells(arg) {
        const el = arg.el;
        if (arg.isPast) return;
        el.setAttribute('title', `Add appointment on ${arg.date.toDateString()}`);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'fc-add-btn';
        btn.innerHTML = `<i class="fa-solid fa-plus"></i><span class="add-label">Add</span>`;
        btn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            editingId = null;
            openBookingModal({ date: arg.date, dateStr: arg.dateStr });
        });
        el.appendChild(btn);
    }

    const calendar = createClinicCalendar({
        element: calendarEl,
        config: {
            dayCellDidMount: enhanceDayCells,
            eventDidMount: function(info) {
                info.el.setAttribute('title', info.event.title);
            },
            dateClick: (info) => {
                const today = new Date(); today.setHours(0,0,0,0);
                if (new Date(info.dateStr) < today) return;
                editingId = null;
                openBookingModal(info);
            },
            events: async (fetchInfo, success, failure) => {
                try {
                    // Note: API.listAppointments is already a full URL with cache buster
                    const u = new URL(API.listAppointments, location.origin);
                    u.searchParams.set('start', fetchInfo.startStr);
                    u.searchParams.set('end', fetchInfo.endStr);
                    const res = await fetch(u.toString(), { credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Failed to load appointments.');
                    const events = await res.json();
                    success(events);
                    renderUpcomingAndHistory(events);
                } catch (err) { failure(err); }
            },
            eventsSet: (events) => {
                const plain = events.map(e => e.toPlainObject ? e.toPlainObject() : e);
                renderUpcomingAndHistory(plain);
            },
            eventClick: (info) => {
                const ep = info.event.extendedProps || {};
                const isPending = ep.status === 'Pending';
                
                const photo = ep.pet_photo_url ? `<div class="swal-pet-thumb" style="background-image:url('${ep.pet_photo_url}')"></div>` : `<div class="swal-pet-thumb placeholder"></div>`;

                const actionsHtml = isPending ? `
                    <div class="actions actions-right">
                        <button class="btn-icon" id="swalEdit" aria-label="Edit" title="Edit"><i class="fa-solid fa-pen"></i></button>
                        <button class="btn-icon btn-danger" id="swalDelete" aria-label="Delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </div>` : '';

                const html = `
                    <div class="swal-appt">
                        <div class="pet-wrap">${photo}<div class="pet-meta">
                            <div class="pet-name">For: <strong>${ep.pet_name || ''}</strong></div>
                            <div class="svc">Service: <strong>${ep.service || ''}</strong></div>
                        </div></div>
                        <div class="when"><b>When:</b> ${new Date(info.event.start).toLocaleString('en-US',{dateStyle:'full', timeStyle:'short'})}</div>
                        <div class="status"><b>Status:</b> ${ep.status || 'N/A'}</div>
                        ${(ep.status === 'Confirmed' && ep.assigned_vet) ? `<div class="vet"><b>Assigned Vet:</b> ${ep.assigned_vet}</div>` : ''}
                        ${ep.notes ? `<div class="notes"><b>Notes:</b> ${String(ep.notes).replace(/</g,'&lt;')}</div>` : ''}
                        ${actionsHtml}
                    </div>`;

                Swal.fire({
                    title: `${ep.service || info.event.title}`,
                    html,
                    showConfirmButton: false,
                    didOpen: () => {
                        if (!isPending) return;

                        document.getElementById('swalEdit')?.addEventListener('click', () => {
                            editingId = info.event.id;
                            Swal.close();
                            const pre = {
                                pet_id: ep.pet_id,
                                service: ep.service,
                                start: info.event.start,
                                notes: ep.notes || ''
                            };
                            openBookingModal(null, pre);
                        });

                        document.getElementById('swalDelete')?.addEventListener('click', async () => {
                            const ok = await Swal.fire({
                                icon: 'warning', title: 'Delete this appointment?', text: 'This action cannot be undone.',
                                showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#e74c3c'
                            });
                            if (ok.isConfirmed) {
                                try {
                                    const fd = new FormData(); fd.append('id', info.event.id);
                                    const res = await fetch(API.deleteAppointment, { method:'POST', body: fd, credentials:'same-origin' });
                                    const data = await res.json();
                                    if (!res.ok || data.ok === false) throw new Error(data.error || 'Failed to delete.');
                                    await Swal.fire({ icon:'success', title:'Deleted', timer:1200, showConfirmButton:false });
                                    calendar.refetchEvents();
                                } catch (err) {
                                    Swal.fire({ icon:'error', title:'Delete failed', text: err.message || String(err) });
                                }
                            }
                        });
                    }
                });
            }
        }
    });
    calendar.render();

    // ---------- Init & Event Listeners----------
    fetchPets();

    historyToggle?.addEventListener('click', () => {
        const isHidden = historyEl.hasAttribute('hidden');
        historyEl.toggleAttribute('hidden', !isHidden);
        historyToggle.querySelector('i').classList.toggle('rotated', isHidden);
    });
    
    dateInput && dateInput.addEventListener('change', () => {
        if (dateInput.value) fetchSlotsFor(dateInput.value, editingId);
        else timeSelect.innerHTML = '<option value="">-- Select a date first --</option>';
    });
    serviceSel && serviceSel.addEventListener('change', () => {
        toggleOtherField();
        if (dateInput.value) fetchSlotsFor(dateInput.value, editingId);
    });

    form && form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!hiddenPetId.value) {
            Swal.fire({ icon:'warning', title:'Select a pet', text:'Please select which pet this appointment is for.' });
            return;
        }
        const btn = form.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; btn.textContent = editingId ? 'Saving...' : 'Submitting...'; }

        try {
            const fd = new FormData(form);
            let url = API.createAppointment;
            if (editingId) { fd.append('id', editingId); url = API.updateAppointment; }

            const res = await fetch(url, { method:'POST', body: fd, credentials:'same-origin' });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.ok === false) throw new Error(data.error || 'Request failed.');

            await Swal.fire({ icon:'success', title: editingId ? 'Updated!' : 'Booked!', timer:1500, showConfirmButton:false });
            resetAndHide();
            calendar.refetchEvents();
        } catch (err) {
            Swal.fire({ icon:'error', title:'Oops…', text: err.message || String(err) });
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = 'Submit Request'; }
        }
    });
});