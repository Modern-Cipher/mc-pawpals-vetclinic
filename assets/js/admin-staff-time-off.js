document.addEventListener('DOMContentLoaded', () => {
    const API = {
        list: `${App.BASE_URL}api/staffs/time_off_requests.php`,
        approve: `${App.BASE_URL}api/staffs/approve_time_off.php`,
        deny: `${App.BASE_URL}api/staffs/deny_time_off.php`
    };

    const requestRows = document.getElementById('request-rows');
    const requestCards = document.getElementById('request-cards');

    async function loadRequests() {
        requestRows.innerHTML = `<tr><td colspan="5">Loading...</td></tr>`;
        requestCards.innerHTML = `<div class="loading-message">Loading...</div>`;
        try {
            const res = await fetch(API.list);
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to load requests.');
            
            renderTable(data.items);
            renderCards(data.items);

        } catch (error) {
            console.error(error);
            requestRows.innerHTML = `<tr><td colspan="5">Failed to load requests.</td></tr>`;
            requestCards.innerHTML = `<div class="error-message">Failed to load requests.</div>`;
            Swal.fire('Error', 'Failed to load time off requests. ' + error.message, 'error');
        }
    }

    function getStatusBadge(status) {
        switch(status) {
            case 'approved': return `<span class="badge badge-approved">Approved</span>`;
            case 'denied': return `<span class="badge badge-denied">Denied</span>`;
            default: return `<span class="badge badge-pending">Pending</span>`;
        }
    }

    function getActionButtons(item) {
        switch (item.status) {
            case 'pending':
                return `
                    <button class="btn btn-success btn-sm js-approve" data-id="${item.id}">Approve</button>
                    <button class="btn btn-danger btn-sm js-deny" data-id="${item.id}">Deny</button>
                `;
            case 'approved':
                return `<button class="btn btn-danger btn-sm js-deny" data-id="${item.id}">Cancel</button>`;
            case 'denied':
                return `<button class="btn btn-success btn-sm js-approve" data-id="${item.id}">Re-approve</button>`;
            default:
                return '—';
        }
    }

    function getCardActionButtons(item) {
        switch (item.status) {
            case 'pending':
                return `
                    <button class="icon-btn btn-success js-approve" data-id="${item.id}" title="Approve"><i class="fa-solid fa-check"></i></button>
                    <button class="icon-btn btn-danger js-deny" data-id="${item.id}" title="Deny"><i class="fa-solid fa-xmark"></i></button>
                `;
            case 'approved':
                return `<button class="icon-btn btn-danger js-deny" data-id="${item.id}" title="Cancel"><i class="fa-solid fa-ban"></i></button>`;
            case 'denied':
                return `<button class="icon-btn btn-success js-approve" data-id="${item.id}" title="Re-approve"><i class="fa-solid fa-rotate-right"></i></button>`;
            default:
                return '';
        }
    }

    function renderTable(items) {
        if (items.length === 0) {
            requestRows.innerHTML = `<tr><td colspan="5">No time off requests found.</td></tr>`;
            return;
        }

        requestRows.innerHTML = items.map(item => `
            <tr data-id="${item.id}">
                <td>${item.first_name} ${item.last_name}</td>
                <td>${item.date}</td>
                <td>${item.reason || 'No reason specified'}</td>
                <td>${getStatusBadge(item.status)}</td>
                <td>${getActionButtons(item)}</td>
            </tr>
        `).join('');
    }

    function renderCards(items) {
        if (items.length === 0) {
            requestCards.innerHTML = `<div class="muted">No time off requests found.</div>`;
            return;
        }

        requestCards.innerHTML = items.map(item => `
            <div class="card" data-id="${item.id}">
                <div class="card-title">${item.first_name} ${item.last_name}</div>
                <div class="card-info">Date: ${item.date}</div>
                <div class="card-info">Reason: ${item.reason || 'No reason specified'}</div>
                <div class="card-actions">${getCardActionButtons(item)}</div>
                ${getStatusBadge(item.status)}
            </div>
        `).join('');
    }

    requestRows.addEventListener('click', handleAction);
    requestCards.addEventListener('click', handleAction);

    async function handleAction(e) {
        const approveBtn = e.target.closest('.js-approve');
        const denyBtn = e.target.closest('.js-deny');

        if (!approveBtn && !denyBtn) return;

        const id = approveBtn ? approveBtn.dataset.id : denyBtn.dataset.id;
        const action = approveBtn ? 'approve' : 'deny';
        const actionUrl = approveBtn ? API.approve : API.deny;
        const confirmText = approveBtn ? 'Are you sure you want to approve this request?' : 'Are you sure you want to deny this request?';
        const successText = approveBtn ? 'Request approved!' : 'Request denied!';
        const errorText = `Failed to ${action} request.`;

        const confirmResult = await Swal.fire({
            title: 'Confirm Action',
            text: confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed!'
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(actionUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                Swal.close();
                if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to process.');
                
                Swal.fire('Success', successText, 'success');
                loadRequests(); // Reload the table to show the updated list
            } catch (error) {
                Swal.fire('Error', errorText + ' ' + error.message, 'error');
            }
        }
    }

    loadRequests();
});