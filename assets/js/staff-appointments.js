document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Robust BASE (works in subfolder) ---------- */
    const phpBase = (window.App && App.BASE_URL) || '/';
    const BASE = (() => {
        if (phpBase && phpBase !== '/') return phpBase.endsWith('/') ? phpBase : (phpBase + '/');
        const parts = location.pathname.split('/').filter(Boolean);
        return parts.length ? `/${parts[0]}/` : '/';
    })();

    const STAFF_ID = (window.App && App.STAFF_ID) || 0;
    const IS_VET = !!(window.App && App.IS_VET);

    /* ---------- API endpoints ---------- */
    const API = {
        list: new URL(BASE + 'api/staffs/appointments/list', location.origin).toString(),
        accept: new URL(BASE + 'api/staffs/appointments/accept', location.origin).toString(),
        status_update: new URL(BASE + 'api/staffs/appointments/status_update', location.origin).toString(),
        roster: new URL(BASE + 'api/staffs/vets/roster', location.origin).toString(),
    };

    const PET_FALLBACK = BASE + 'assets/images/person1.jpg';

    /* ---------- DOM ---------- */
    const tbody = document.getElementById('apptTbody');
    const seg = document.querySelector('.seg');
    const kpiPending = document.getElementById('kpiPending');
    const kpiToday = document.getElementById('kpiToday');
    const kpiMine = document.getElementById('kpiMine');
    const kpiWeek = document.getElementById('kpiWeek');

    let currentScope = 'pending';
    let vetsCache = null;

    /* ---------- Date helpers ---------- */
    function toDateSafe(x) {
        if (!x) return null;
        if (x instanceof Date) return x;
        if (typeof x === 'number') return new Date(x);
        if (typeof x === 'string') {
            const s = x.trim();
            let d = new Date(s);
            if (!Number.isNaN(d.valueOf())) return d;
            d = new Date(s.replace(' ', 'T'));
            if (!Number.isNaN(d.valueOf())) return d;
            const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
            if (m) {
                const [, yy, MM, dd, hh, mm, ss] = m;
                return new Date(+yy, +MM - 1, +dd, +hh, +mm, +(ss || 0));
            }
        }
        return null;
    }

    function fmtDateTime(x) {
        const d = toDateSafe(x);
        if (!d) return '—';
        return d.toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
    }

    /* ---------- UI helpers ---------- */
    function badge(status) {
        const key = String(status || '').toLowerCase().replace(/\s+/g, '');
        const cls = ({ pending: 'pending', confirmed: 'confirmed', completed: 'completed', cancelled: 'cancelled', 'no-show': 'noshow' })[key] || 'pending';
        return `<span class="badge ${cls}">${status || '—'}</span>`;
    }

    async function fetchVets() {
        if (vetsCache) return vetsCache;
        try {
            const r = await fetch(API.roster, { credentials: 'same-origin' });
            if (!r.ok) throw new Error(`Network response was not ok: ${r.statusText}`);
            const j = await r.json().catch(() => ({}));
            vetsCache = (j && j.ok && Array.isArray(j.vets)) ? j.vets : [];
        } catch (err) {
            console.error("Failed to fetch vets:", err);
            vetsCache = [];
        }
        return vetsCache;
    }

    async function fetchAndRender(scope = currentScope) {
        currentScope = scope;
        seg.querySelectorAll('button[data-scope]').forEach(b => {
            b.classList.toggle('active', b.dataset.scope === scope);
            b.setAttribute('aria-selected', b.classList.contains('active') ? 'true' : 'false');
        });

        try {
            const u = new URL(API.list);
            u.searchParams.set('scope', scope);
            u.searchParams.set('cb', Date.now());

            const res = await fetch(u.toString(), { credentials: 'same-origin' });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.ok === false) throw new Error(data.error || 'Server error');

            kpiPending.textContent = data.kpis?.pending ?? 0;
            kpiToday.textContent = data.kpis?.today ?? 0;
            kpiMine.textContent = data.kpis?.mine ?? 0;
            kpiWeek.textContent = data.kpis?.week ?? 0;

            const items = Array.isArray(data.items) ? data.items : [];
            if (!items.length) {
                tbody.innerHTML = `<tr><td class="muted" colspan="6">No data.</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(it => {
                const status = (it.status || '').toLowerCase();
                const whenStr = it.appointment_datetime || it.start || it.when;
                
                let actions = '<button class="btn-xs ghost btn-view" title="View details"><i class="fa-regular fa-eye"></i></button>';
                
                if (status === 'pending') {
                    actions = `<button class="btn-xs primary btn-accept" title="Accept"><i class="fa-solid fa-check"></i> <span>Accept</span></button>` + actions +
                              `<button class="btn-xs warn btn-cancel" title="Cancel"><i class="fa-solid fa-ban"></i></button>`;
                } else if (status === 'confirmed') {
                    actions += `<button class="btn-xs gray btn-noshow" title="Mark as No-Show">No-Show</button>` +
                               `<button class="btn-xs warn btn-cancel" title="Cancel"><i class="fa-solid fa-ban"></i></button>`;
                }

                return `
          <tr 
            data-id="${it.id}"
            data-dt="${whenStr||''}"
            data-service="${(it.service||'').replace(/"/g,'&quot;')}"
            data-notes="${(it.notes||'').replace(/"/g,'&quot;')}"
            data-status="${it.status||''}"
            data-pet="${(it.pet_name||'').replace(/"/g,'&quot;')}"
            data-species="${(it.pet_species||'').replace(/"/g,'&quot;')}"
            data-breed="${(it.pet_breed||'').replace(/"/g,'&quot;')}"
            data-petphoto="${(it.pet_photo_url||'').replace(/"/g,'&quot;')}"
            data-owner="${(it.owner_name||'').replace(/"/g,'&quot;')}"
            data-owneremail="${(it.owner_email||'').replace(/"/g,'&quot;')}"
            data-ownerphone="${(it.owner_phone||'').replace(/"/g,'&quot;')}"
          >
            <td data-label="Date & Time">${fmtDateTime(whenStr)}</td>
            <td data-label="Service / Pet"><div style="font-weight:700">${it.service||'—'}</div><div class="muted">${it.pet_name||'—'}</div></td>
            <td data-label="Pet Owner">
              <div style="font-weight:700">${it.owner_name||'—'}</div>
              <div class="muted">${it.owner_email||''}</div>
            </td>
            <td data-label="Assigned To">
              ${it.assigned_vet && it.assigned_vet.name ? it.assigned_vet.name : '—'}
            </td>
            <td data-label="Status">
              ${badge(it.status)}
              ${(status === 'cancelled' || status === 'no-show') && it.notes ? `<div class="status-note muted">${it.notes}</div>` : ''}
            </td>
            <td class="col-actions">
              <div class="row-actions">${actions}</div>
            </td>
          </tr>`;
            }).join('');
        } catch (err) {
            console.error(err);
            tbody.innerHTML = `<tr><td class="muted" colspan="6">Error loading data.</td></tr>`;
        }
    }

    /* ---------- Modals ---------- */
    function showDetails(tr) {
        const ds = tr.dataset;
        const when = fmtDateTime(ds.dt);
        const pic = (ds.petphoto && ds.petphoto !== 'null' && ds.petphoto !== 'undefined') ? ds.petphoto : PET_FALLBACK;
        const html = `
      <div class="view-grid" style="display:grid;grid-template-columns:96px 1fr;gap:14px;align-items:start;text-align:left">
        <img src="${pic}" alt="" style="width:96px;height:96px;border-radius:12px;object-fit:cover;border:1px solid #e5e7eb;background:#f8fafc" onerror="this.onerror=null;this.src='${PET_FALLBACK}';">
        <div>
          <div style="font-size:18px;font-weight:800;margin-bottom:2px">${ds.pet || 'Pet'}</div>
          <div class="muted" style="margin-bottom:8px">${[ds.species, ds.breed].filter(Boolean).join(' • ')}</div>
          <div><b>Service:</b> ${ds.service || '—'}</div>
          <div><b>When:</b> ${when}</div>
          <div><b>Status:</b> ${ds.status || '—'}</div>
          ${ds.notes ? `<div style="margin-top:6px"><b>Remarks:</b> ${ds.notes}</div>` : ''}
          <hr style="margin:12px 0;border:none;border-top:1px solid #e5e7eb">
          <div style="font-weight:700">Pet Owner</div>
          <div>${ds.owner || '—'}</div>
          <div class="muted">${ds.owneremail || ''}${ds.ownerphone ? (ds.owneremail ? ' • ' : '') + ds.ownerphone : ''}</div>
        </div>
      </div>`;
        Swal.fire({
            title: 'Appointment details',
            html,
            confirmButtonText: 'Close',
            focusConfirm: false
        });
    }

    async function openAcceptModal(tr) {
        let assignToId = null;
        if (IS_VET) {
            const result = await Swal.fire({
                title: 'Accept Appointment?', text: 'This will be assigned to you.', icon: 'question',
                showCancelButton: true, confirmButtonText: 'Yes, Accept It', cancelButtonText: 'Cancel'
            });
            if (result.isConfirmed) assignToId = STAFF_ID;
        } else {
            const vets = await fetchVets();
            if (!vets.length) return Swal.fire({ icon: 'error', title: 'No Veterinarians Found' });

            const select = document.createElement('select');
            select.id = 'vetSelect';
            select.style.cssText = 'width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px;margin-top:10px;';
            select.appendChild(new Option('-- Choose a Veterinarian --', ''));
            vets.forEach(v => select.appendChild(new Option(`${v.name}${v.designation ? ` (${v.designation})` : ''}`, v.id)));
            
            const html = document.createElement('div');
            html.innerHTML = `<div style="text-align:left;margin-bottom:4px"><b>Assign to veterinarian</b></div>`;
            html.appendChild(select);

            const result = await Swal.fire({
                title: 'Accept Appointment?', html, showCancelButton: true, confirmButtonText: 'Accept & Assign',
                preConfirm: () => {
                    const selectedVetId = select.value;
                    if (!selectedVetId) Swal.showValidationMessage('Please choose a veterinarian.');
                    return selectedVetId || false;
                }
            });
            if (result.isConfirmed) assignToId = result.value;
        }

        if (!assignToId) return;

        try {
            const fd = new FormData();
            fd.append('id', tr.dataset.id);
            fd.append('assign_to', assignToId);
            const r = await fetch(API.accept, { method: 'POST', body: fd });
            const j = await r.json();
            if (!r.ok || !j.ok) throw new Error(j.error || 'Request failed');
            await Swal.fire({ icon: 'success', title: 'Accepted!', timer: 1200, showConfirmButton: false });
            fetchAndRender(currentScope);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Acceptance Failed', text: e.message });
        }
    }

    async function openStatusUpdateModal(tr, newStatus) {
        const result = await Swal.fire({
            title: `${newStatus} Appointment`,
            html: `<textarea id="reason" class="swal2-textarea" placeholder="Enter reason or remarks for the pet owner..."></textarea>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Yes, Mark as ${newStatus}`,
            confirmButtonColor: '#ef4444',
            preConfirm: () => {
                const reason = document.getElementById('reason').value;
                if (!reason) {
                    Swal.showValidationMessage('Please provide a reason or remark.');
                    return false;
                }
                return reason;
            }
        });

        if (!result.isConfirmed) return;

        try {
            const fd = new FormData();
            fd.append('id', tr.dataset.id);
            fd.append('status', newStatus);
            fd.append('reason', result.value);

            const r = await fetch(API.status_update, { method: 'POST', body: fd });
            const j = await r.json();
            if (!r.ok || !j.ok) throw new Error(j.error || 'Request failed');

            await Swal.fire({ icon: 'success', title: 'Updated!', timer: 1200, showConfirmButton: false });
            fetchAndRender(currentScope);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Update Failed', text: e.message });
        }
    }

    /* ---------- Delegation & Init ---------- */
    tbody.addEventListener('click', e => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const tr = btn.closest('tr');
        if (!tr) return;

        if (btn.classList.contains('btn-accept')) return openAcceptModal(tr);
        if (btn.classList.contains('btn-cancel')) return openStatusUpdateModal(tr, 'Cancelled');
        if (btn.classList.contains('btn-noshow')) return openStatusUpdateModal(tr, 'No-Show');
        if (btn.classList.contains('btn-view')) return showDetails(tr);
    });

    seg.addEventListener('click', e => {
        const b = e.target.closest('button[data-scope]');
        if (b) fetchAndRender(b.dataset.scope);
    });
    
    document.getElementById('refreshBtn')?.addEventListener('click', () => fetchAndRender(currentScope));

    fetchAndRender('pending');
});