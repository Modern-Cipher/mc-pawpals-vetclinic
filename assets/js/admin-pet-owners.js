// assets/js/admin-pet-owners.js
document.addEventListener('DOMContentLoaded', () => {
    const ensureSlash = s => (s.endsWith('/') ? s : s + '/');
    function detectBaseFromScript() {
        try {
            const scripts = document.getElementsByTagName('script');
            for (let i = scripts.length - 1; i >= 0; i--) {
                const src = scripts[i].src || '';
                const m = src.match(/^(https?:\/\/[^/]+\/.*?\/)assets\/js\/admin-pet-owners\.js/i);
                if (m) return ensureSlash(m[1]);
            }
        } catch (_) {}
        return '/';
    }
    const BASE = ensureSlash(
        (window.App?.BASE_URL && window.App.BASE_URL.length > 1) ? window.App.BASE_URL : detectBaseFromScript()
    );

    const API = {
        list:    `${BASE}api/pet-owners/list.php`,
        toggle:  `${BASE}api/pet-owners/toggle_active.php`,
        resetPw: `${BASE}api/pet-owners/reset_password.php`,
    };

    const rowsEl = document.getElementById('rows');
    const cardsEl = document.getElementById('cards');
    const searchInput = document.getElementById('q');
    const filterBtns = document.querySelectorAll('.filters .btn');
    
    let cache = [];
    let currentFilter = 'all';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const fallbackAvatar = `${BASE}assets/images/person1.jpg`;

    // --- BAGONG HELPER FUNCTION PARA SA DATE FORMAT ---
    const formatDate = dateString => {
        if (!dateString) return 'Never';
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Invalid Date';
            
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            return date.toLocaleDateString('en-US', options);
        } catch (e) {
            return 'Invalid Date';
        }
    };
    // --- END OF HELPER FUNCTION ---

    const verifiedBadge = isVerified => isVerified
        ? `<span class="badge-verified">Verified</span>`
        : `<span class="badge-unverified">Unverified</span>`;

    const rowTpl = owner => `
        <tr data-id="${owner.id}">
            <td class="avatar-td"><img class="avatar" src="${esc(owner.avatar_url || fallbackAvatar)}" alt="Avatar" title="Click to zoom" onerror="this.src='${fallbackAvatar}'"></td>
            <td>${esc(owner.first_name)} ${esc(owner.last_name)}</td>
            <td>${esc(owner.email)}<div class="muted" style="font-size:12px">${esc(owner.username)}</div></td>
            <td>${verifiedBadge(owner.is_verified)}</td>
            <td>${formatDate(owner.last_login_at)}</td> <td class="mincol">
                <label class="switch" title="Toggle active status"><input type="checkbox" class="js-active" ${owner.is_active ? 'checked' : ''}><span class="slider"></span></label>
            </td>
            <td class="mincol">
                <div class="actions">
                    <button class="iconbtn js-reset" title="Reset password"><i class="fa-solid fa-key"></i></button>
                </div>
            </td>
        </tr>`;

    const cardTpl = owner => `
        <div class="card" data-id="${owner.id}">
            <div class="card-head">
                <div style="display:flex;align-items:center;gap:10px">
                    <img class="avatar" src="${esc(owner.avatar_url || fallbackAvatar)}" alt="Avatar" title="Click to zoom">
                    <div><b>${esc(owner.first_name)} ${esc(owner.last_name)}</b><div class="muted" style="font-size:12px">${esc(owner.email)}</div></div>
                </div>
                ${verifiedBadge(owner.is_verified)}
            </div>
            <div class="card-details">
                Username: ${esc(owner.username)}<br>
                Phone: ${esc(owner.phone || 'N/A')}<br>
                Last Login: ${formatDate(owner.last_login_at)} </div>
            <div class="card-footer">
                <label class="switch" title="Toggle active status"><input type="checkbox" class="js-active" ${owner.is_active ? 'checked' : ''}><span class="slider"></span></label>
                <div class="actions">
                    <button class="iconbtn js-reset" title="Reset password"><i class="fa-solid fa-key"></i></button>
                </div>
            </div>
        </div>`;

    function render(list) {
        rowsEl.innerHTML = list.length ? list.map(rowTpl).join('') : `<tr><td colspan="7">No pet owners found.</td></tr>`;
        cardsEl.innerHTML = list.length ? list.map(cardTpl).join('') : `<div class="muted">No pet owners found.</div>`;
    }

    async function load() {
        try {
            const res = await fetch(API.list, { cache: 'no-store' });
            const j = await res.json();
            if (!res.ok || !j.ok) throw new Error(j.error || 'Server error');
            cache = j.items || [];
            applyFilters();
        } catch (e) {
            console.error(e);
            rowsEl.innerHTML = `<tr><td colspan="7">Failed to load pet owners.</td></tr>`;
        }
    }

    function applyFilters() {
        const k = (searchInput.value || '').toLowerCase();
        let list = cache.filter(o => {
            if (currentFilter === 'verified' && !o.is_verified) return false;
            if (currentFilter === 'unverified' && o.is_verified) return false;
            return true;
        });
        if (k) {
            list = list.filter(o =>
                `${o.first_name} ${o.last_name} ${o.email} ${o.username}`.toLowerCase().includes(k)
            );
        }
        render(list);
    }
    
    searchInput?.addEventListener('input', applyFilters);
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            applyFilters();
        });
    });

    // Event listener for toggle
    document.body.addEventListener('change', async (e) => {
        if (!e.target.classList.contains('js-active')) return;
        const wrap = e.target.closest('tr, .card');
        if (!wrap) return;
        const id = +wrap.dataset.id;
        const on = e.target.checked ? '1' : '0';
        try {
            const res = await fetch(API.toggle, { method: 'POST', body: new URLSearchParams({ user_id: id, is_active: on }) });
            const j = await res.json();
            if (!res.ok || !j.ok) throw new Error(j.error);
        } catch (err) {
            e.target.checked = !e.target.checked;
            Swal.fire({ icon: 'error', title: 'Toggle failed', text: err.message || 'Server error' });
        }
    });

    // Event listener for image zoom and reset password
    document.body.addEventListener('click', async (e) => {
        const img = e.target.closest('img.avatar');
        if (img) {
            Swal.fire({
                imageUrl: img.src,
                imageAlt: 'Avatar',
                showConfirmButton: false,
                showCloseButton: true,
                width: 'auto',
                padding: '0.25em'
            });
            return;
        }

        const wrap = e.target.closest('tr, .card');
        if (!wrap) return;
        const id = +wrap.dataset.id;
        if (e.target.closest('.js-reset')) {
            const owner = cache.find(o => o.id === id);
            const ok = await Swal.fire({
                icon: 'warning',
                title: 'Reset Password?',
                html: `This will generate a new password for <b>${esc(owner.first_name)} ${esc(owner.last_name)}</b> and email it to them.`,
                showCancelButton: true
            });
            if (!ok.isConfirmed) return;
            Swal.fire({ title: 'Sending…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(API.resetPw, { method: 'POST', body: new URLSearchParams({ user_id: id }) });
                const j = await res.json();
                Swal.close();
                Swal.fire({
                    icon: j.ok ? 'success' : 'error',
                    title: j.ok ? 'Reset Sent' : 'Failed',
                    text: j.ok ? 'The user will receive an email with their new temporary password.' : 'Server error'
                });
            } catch (err) {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Failed', text: 'A server error occurred.' });
            }
        }
    });
    
    load();
});