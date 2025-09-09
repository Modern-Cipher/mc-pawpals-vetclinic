// assets/js/user-records.js
document.addEventListener('DOMContentLoaded', () => {
    const ensureSlash = s => (s.endsWith('/') ? s : s + '/');
    const detectBaseFromScript = () => {
        try {
            const scripts = document.getElementsByTagName('script');
            for (let i = scripts.length - 1; i >= 0; i--) {
                const src = scripts[i].src || '';
                const m = src.match(/^(https?:\/\/[^/]+\/[^/]+\/)assets\/js\/user-records\.js/i);
                if (m) return ensureSlash(m[1]);
            }
        } catch {}
        return '/mc-pawpals-veterinary-clinic/';
    };

    const BASE_URL = (window.App?.BASE_URL && window.App.BASE_URL.length > 2)
      ? ensureSlash(window.App.BASE_URL)
      : detectBaseFromScript();

    const APPOINTMENTS_URL = window.App?.APPOINTMENTS_URL || `${BASE_URL}dashboard/users/appointments`;
    const API_URL = `${BASE_URL}api/users/medical-summary.php`;

    const remindersList = document.getElementById('remindersList');
    const recentList = document.getElementById('recentList');

    const escapeHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmtDateOnly = s => s ? new Date(s.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'N/A';
    const NO_IMG_SVG = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" fill="%23f3f4f6"/><g fill="%239ca3af"><circle cx="24" cy="20" r="8"/><rect x="13" y="31" width="22" height="9" rx="4"/></g></svg>';

    let allRecentRecords = [];

    function renderReminders(reminders) {
        if (!reminders || reminders.length === 0) {
            remindersList.innerHTML = '<p class="muted">No upcoming health reminders. All pets are up-to-date!</p>';
            return;
        }
        remindersList.innerHTML = reminders.map(r => `
            <div class="summary-card reminder-card">
                <img src="${r.pet_photo_url || NO_IMG_SVG}" alt="${escapeHtml(r.pet_name)}" class="pet-avatar">
                <div class="card-details">
                    <div class="pet-name">${escapeHtml(r.pet_name)}</div>
                    <div class="record-type">${escapeHtml(r.type)}</div>
                    <div class="due-date"><strong>Due:</strong> ${fmtDateOnly(r.due_date)}</div>
                </div>
                <a href="${APPOINTMENTS_URL}" class="btn btn-primary btn-book">Book Now</a>
            </div>
        `).join('');
    }

    function renderRecentRecords(records) {
        allRecentRecords = records; // Store for modal
        if (!records || records.length === 0) {
            recentList.innerHTML = '<p class="muted">No recent medical records found.</p>';
            return;
        }
        recentList.innerHTML = records.map(r => `
            <div class="summary-card">
                <img src="${r.pet_photo_url || NO_IMG_SVG}" alt="${escapeHtml(r.pet_name)}" class="pet-avatar">
                <div class="card-details">
                    <div class="pet-name">${escapeHtml(r.pet_name)}</div>
                    <div class="record-type">${escapeHtml(r.type)}</div>
                    <div class="record-info">
                        <span class="date">${fmtDateOnly(r.record_date)}</span> - ${escapeHtml(r.details)}
                    </div>
                </div>
                ${r.type === 'Consultation' ? `<button class="btn btn-secondary btn-view-details" data-record-id="${r.id}">View Details</button>` : ''}
            </div>
        `).join('');
    }

    async function loadSummary() {
        remindersList.innerHTML = '<p class="muted">Loading reminders...</p>';
        recentList.innerHTML = '<p class="muted">Loading recent records...</p>';
        try {
            const response = await fetch(API_URL);
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Failed to load health summary.');
            }
            renderReminders(data.reminders);
            renderRecentRecords(data.recent_records);
        } catch (error) {
            console.error('Error fetching health summary:', error);
            const errorMsg = '<p class="error">Could not load health summary. Please try again later.</p>';
            remindersList.innerHTML = errorMsg;
            recentList.innerHTML = errorMsg;
        }
    }
    
    recentList.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.btn-view-details');
        if (!viewBtn) return;
        
        const recordId = viewBtn.dataset.recordId;
        const record = allRecentRecords.find(r => r.id == recordId);
        
        if (record) {
            // --- START OF FIX: Professional-looking modal ---
            Swal.fire({
                title: `<i class="fa-solid fa-file-waveform"></i> Consultation for ${escapeHtml(record.pet_name)}`,
                html: `
                    <div class="swal-soap-details">
                        <div class="soap-meta"><strong>Date:</strong> ${fmtDateOnly(record.record_date)}</div>
                        <div class="soap-section">
                            <h4>Subjective</h4>
                            <p>${escapeHtml(record.full_details.subjective || 'N/A')}</p>
                        </div>
                        <div class="soap-section">
                            <h4>Objective</h4>
                            <p>${escapeHtml(record.full_details.objective || 'N/A')}</p>
                        </div>
                        <div class="soap-section">
                            <h4>Assessment</h4>
                            <p>${escapeHtml(record.full_details.assessment || 'N/A')}</p>
                        </div>
                         <div class="soap-section">
                            <h4>Plan</h4>
                            <p>${escapeHtml(record.full_details.plan || 'N/A')}</p>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false, // Tinanggal ang "Close" button
                customClass: {
                    popup: 'professional-swal',
                    title: 'professional-swal-title',
                    htmlContainer: 'professional-swal-container',
                },
            });
            // --- END OF FIX ---
        }
    });

    loadSummary();
});