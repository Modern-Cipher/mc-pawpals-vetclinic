(function () {
  'use strict';

  const BASE = window.APP_BASE || '/';

  /* ================= helpers ================= */
  const fullURL = (p) => {
    if (!p) return '';
    if (/^https?:\/\//i.test(p)) return p;
    if (p.startsWith('/')) return p;
    return BASE + p.replace(/^\/+/, '');
  };

  const escapeHtml = (s) =>
    (s ?? '').toString().replace(/[&<>"']/g, (m) => (
      { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]
    ));

  const avatarHTML = (url, alt = '') =>
    `<img src="${url ? fullURL(url) : (BASE + 'assets/images/person1.jpg')}" alt="${escapeHtml(alt || 'User')}" />`;

  // fractional stars + compact numeric value + tooltip
  const starHTML = (rating) => {
    const v = Math.max(0, Math.min(5, parseFloat(rating || 0)));
    const w = (v / 5) * 100;
    const val = v.toFixed(1);
    const tip = `${val} / 5`;
    return `
      <div class="star-wrap" aria-label="${tip}" title="${tip}" data-tip="${tip}">
        <span class="star-bg">★★★★★</span>
        <span class="star-fill" style="width:${w}%">★★★★★</span>
      </div>
      <span class="ms-1 text-muted small star-val">${val}</span>
    `;
  };

  // status badge with tooltip (includes approver + role)
  const statusBadge = (status, byName = null, byRole = null) => {
    const t = (status || '').toLowerCase();
    const map = { pending: 'Pending', approved: 'approved', archived: 'archived' };
    const cls = `badge-status ${t}`;
    let txt = map[t] || status || '';
    if (byName) txt += ` • by ${byName}${byRole ? ` (${byRole})` : ''}`;
    const tip = `Status: ${txt}`;
    return `<span class="${cls}" title="${tip}" data-tip="${tip}">${txt}</span>`;
  };

  // Friday – August 22, 2025 at 10:03 AM
  const prettyDate = (iso) => {
    if (!iso) return '';
    const d = new Date(String(iso).replace(' ', 'T'));
    const opt = {
      weekday: 'long', month: 'long', day: 'numeric', year: 'numeric',
      hour: 'numeric', minute: '2-digit'
    };
    return d.toLocaleString('en-US', opt).replace(',', ' –').replace(',', '  ');
  };

  // Align sticky search just below the live topbar height
  function setMobileTopbarOffset() {
    const topbar = document.querySelector('.topbar, .admin-topbar, header');
    const h = topbar ? Math.round(topbar.getBoundingClientRect().height) : 64;
    document.documentElement.style.setProperty('--admin-topbar', `${h}px`);
  }
  setMobileTopbarOffset();
  window.addEventListener('resize', setMobileTopbarOffset);

  /* ================= state ================= */
  let RAW = [];
  let table; // DataTables instance

  /* ================= data flow ================= */
  async function loadData() {
    const res = await fetch(`${BASE}api/feedbacks.php?action=list`);
    const json = await res.json();
    if (!json.success) throw new Error(json.message || 'Failed to load');
    RAW = json.rows || [];
    buildTable(RAW);
    buildMobile(RAW);
    updateBadges(RAW);
  }

  function updateBadges(rows) {
    const cnt = { pending: 0, approved: 0, archived: 0 };
    rows.forEach(r => { cnt[(r.status || '').toLowerCase()]++; });
    const set = (id, txt) => { const el = document.getElementById(id); if (el) el.textContent = txt; };
    set('badgePending',  `Pending: ${cnt.pending}`);
    set('badgeApproved', `Approved: ${cnt.approved}`);
    set('badgeArchived', `Archived: ${cnt.archived}`);
  }

  /* ================= desktop table ================= */
  function actionsHTML(r) {
    const id = r.id;
    const btn = (act, icon, title, extra = '') =>
      `<button class="btn-icon ${extra}" data-act="${act}" data-id="${id}"
               title="${title}" data-tip="${title}" aria-label="${title}">
         <i class="fa-solid ${icon}"></i>
       </button>`;
    const A = [];
    if (r.status !== 'approved') A.push(btn('approve', 'fa-circle-check', 'Approve'));
    if (r.status !== 'archived') A.push(btn('archive', 'fa-box-archive', 'Archive'));
    A.push(btn('delete', 'fa-trash', 'Delete', 'danger'));
    return A.join(' ');
  }

  function buildTable(rows) {
    const $table = window.jQuery && window.jQuery('#ratingsTable');
    if (!$table || !$table.length) return;

    if (table) {
      table.clear().rows.add(rows).draw();
      return;
    }

    table = new window.jQuery.fn.dataTable.Api($table.DataTable({
      data: rows,
      columns: [
        { data: 'rating', render: (d) => starHTML(d) },
        {
          data: null, render: (r) => `
            <div class="namebox">
              ${avatarHTML(r.submitter_avatar_url, r.name)}
              <div>
                <div class="fw-semibold">${escapeHtml(r.name || '—')}</div>
                <div class="text-muted small">${escapeHtml(r.email || '')}</div>
              </div>
            </div>
          `
        },
        { data: 'message', render: (d) => `<div class="text-wrap">${escapeHtml(d || '')}</div>` },
        {
          data: null, render: (r) => {
            const whoName = r.status === 'approved' ? r.approved_by_name
                          : (r.status === 'archived' ? r.archived_by_name : null);
            const whoRole = r.status === 'approved' ? r.approved_by_role
                          : (r.status === 'archived' ? r.archived_by_role : null);
            return statusBadge(r.status, whoName, whoRole);
          }
        },
        { data: 'created_at', render: (d) => `<span class="text-muted small">${prettyDate(d)}</span>` },
        { data: null, orderable: false, className: 'text-end', render: (r) => actionsHTML(r) }
      ],
      deferRender: true,
      autoWidth: false,
      pageLength: 10,
      order: [[4, 'desc']],
      dom: '<"dt-top"lfr>t<"dt-bottom"ip>',
      createdRow: (row) => { row.querySelectorAll('td')[0].style.whiteSpace = 'nowrap'; }
    }));

    // desktop action buttons
    $table.on('click', 'button[data-act]', onActionClick);
  }

  /* ================= mobile cards ================= */
  function buildMobile(rows) {
    const wrap = document.getElementById('ratingsMobile');
    if (!wrap) return;

    const q = (document.getElementById('mobileSearch')?.value || '').trim().toLowerCase();
    const filtered = !q ? rows : rows.filter(r =>
      (`${r.name} ${r.email} ${r.message}`).toLowerCase().includes(q)
    );

    wrap.innerHTML = filtered.map(r => {
      const whoName = r.status === 'approved' ? r.approved_by_name
                    : (r.status === 'archived' ? r.archived_by_name : null);
      const whoRole = r.status === 'approved' ? r.approved_by_role
                    : (r.status === 'archived' ? r.archived_by_role : null);

      return `
        <div class="rating-card">
          <div class="rc-head">
            <div class="avatar">
              ${avatarHTML(r.submitter_avatar_url, r.name)}
              <div>
                <div class="rc-title">${escapeHtml(r.name || '—')}</div>
                <div class="rc-email">${escapeHtml(r.email || '')}</div>
              </div>
            </div>
            ${starHTML(r.rating)}
          </div>

          <div class="rc-msg">${escapeHtml(r.message || '')}</div>

          <div class="rc-foot">
            ${statusBadge(r.status, whoName, whoRole)}
            <div class="btns">${actionsHTML(r)}</div>
          </div>

          <div class="date mt-1">${prettyDate(r.created_at)}</div>
        </div>
      `;
    }).join('');

    wrap.hidden = false;
    wrap.querySelectorAll('button[data-act]').forEach(b => b.addEventListener('click', onActionClick));
  }

  /* ================= actions ================= */
  async function onActionClick(e) {
    const btn = e.currentTarget;
    const id = parseInt(btn.dataset.id, 10);
    const act = btn.dataset.act;
    if (!id || !act) return;

    if (act === 'delete') {
      const go = await Swal.fire({
        icon: 'warning',
        title: 'Delete feedback?',
        text: 'This cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#dc2626'
      });
      if (!go.isConfirmed) return;
    }

    const res = await fetch(`${BASE}api/feedbacks.php?action=${act}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id: String(id) })
    });
    const json = await res.json();
    if (!json.success) {
      await Swal.fire({ icon: 'error', title: 'Failed', text: json.message || 'Please try again.' });
      return;
    }
    await loadData();
  }

  /* ================= boot ================= */
  const ms = document.getElementById('mobileSearch');
  if (ms) ms.addEventListener('input', () => buildMobile(RAW));

  loadData().catch(err =>
    Swal.fire({ icon: 'error', title: 'Load failed', text: err.message || String(err) })
  );
})();
