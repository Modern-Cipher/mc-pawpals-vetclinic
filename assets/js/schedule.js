document.addEventListener('DOMContentLoaded', () => {
    const API = {
        loadHours: `${App.BASE_URL}api/staffs/schedule/hours.php`,
        updateHours: `${App.BASE_URL}api/staffs/schedule/update_hours.php`,
        loadTimeOff: `${App.BASE_URL}api/staffs/schedule/time_off.php`,
        addTimeOff: `${App.BASE_URL}api/staffs/schedule/add_time_off.php`,
        deleteTimeOff: `${App.BASE_URL}api/staffs/schedule/delete_time_off.php`
    };

    const hoursModal = document.getElementById('hoursModal');
    const timeOffModal = document.getElementById('timeOffModal');
    const editHoursBtn = document.getElementById('editHoursBtn');
    const addTimeOffBtn = document.getElementById('addTimeOffBtn');
    const hoursForm = document.getElementById('hoursForm');
    const hoursFormBody = document.getElementById('hours-form-body');
    const timeOffDateInput = document.getElementById('timeOffDate');

    // Initialize Flatpickr on the date input field
    flatpickr(timeOffDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today"
    });

    // Function to open modals
    const openModal = (modal) => {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Disable scrolling
    };

    // Function to close modals
    const closeModal = (modal) => {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // Enable scrolling again
    };

    // Event listeners to close modals
    document.getElementById('closeHoursModal').addEventListener('click', () => closeModal(hoursModal));
    document.getElementById('cancelHoursBtn').addEventListener('click', () => closeModal(hoursModal));
    document.getElementById('closeTimeOffModal').addEventListener('click', () => closeModal(timeOffModal));
    document.getElementById('cancelTimeOffBtn').addEventListener('click', () => closeModal(timeOffModal));

    // Handle form submission for weekly hours
    hoursForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        await updateHours();
    });

    // Handle form submission for time off request
    document.getElementById('timeOffForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const date = timeOffDateInput.value;
        const reason = document.getElementById('timeOffReason').value;

        if (!date) {
            Swal.fire('Error', 'Please select a date for your time off.', 'error');
            return;
        }

        Swal.fire({ title: 'Submitting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await fetch(API.addTimeOff, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ date, reason, staffId: App.staffId })
            });
            const data = await res.json();
            Swal.close();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to submit request.');

            closeModal(timeOffModal);
            Swal.fire('Success', 'Your time off request has been submitted.', 'success');
            loadTimeOff();
        } catch (error) {
            Swal.fire('Error', 'Failed to submit time off request. ' + error.message, 'error');
        }
    });

    // Handle time-off deletion
    document.getElementById('time-off-list').addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.js-delete-time-off');
        if (!deleteBtn) return;

        const id = deleteBtn.dataset.id;
        const confirmResult = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        });

        if (confirmResult.isConfirmed) {
            Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            try {
                const res = await fetch(API.deleteTimeOff, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                Swal.close();
                if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to delete time off.');

                Swal.fire('Deleted!', 'The time off request has been deleted.', 'success');
                loadTimeOff();
            } catch (error) {
                Swal.fire('Error', 'Failed to delete time off request. ' + error.message, 'error');
            }
        }
    });

    // Load Hours Data for the main view
    async function loadHours() {
        try {
            const res = await fetch(API.loadHours);
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to load hours.');
            renderHoursList(data.items);
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Failed to load weekly schedule. ' + error.message, 'error');
        }
    }

    // Load Hours Data and populate the edit modal form
    async function loadHoursForEdit() {
        try {
            const res = await fetch(API.loadHours);
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to load hours.');
            renderHoursForm(data.items);
            openModal(hoursModal);
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Failed to prepare form. ' + error.message, 'error');
        }
    }

    // Update Hours via API
    async function updateHours() {
        const hoursData = Array.from(hoursFormBody.querySelectorAll('.day-row')).map(row => {
            const dayOfWeek = row.dataset.day;
            const isOpen = row.querySelector('.is-open-checkbox').checked;
            const startTime = row.querySelector('.start-time').value;
            const endTime = row.querySelector('.end-time').value;
            return { day_of_week: dayOfWeek, is_open: isOpen, start_time: startTime, end_time: endTime };
        });

        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await fetch(API.updateHours, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hours: hoursData })
            });
            const data = await res.json();
            Swal.close();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to save changes.');

            closeModal(hoursModal);
            Swal.fire('Success', 'Your weekly schedule has been updated.', 'success');
            loadHours();
        } catch (error) {
            Swal.fire('Error', 'Failed to save schedule. ' + error.message, 'error');
        }
    }

    function renderHoursList(items) {
        const hoursList = document.getElementById('hours-list');
        hoursList.innerHTML = '';
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        daysOfWeek.forEach((day, index) => {
            const item = items.find(i => i.day_of_week == index) || { day_of_week: index, is_open: 0, start_time: '00:00:00', end_time: '00:00:00' };
            const li = document.createElement('li');
            li.className = 'schedule-item';
            const status = item.is_open ? 'open' : 'closed';
            const statusText = item.is_open ? 'Open' : 'Closed';
            const hoursText = item.is_open ? `${item.start_time.slice(0, 5)} - ${item.end_time.slice(0, 5)}` : 'N/A';
            li.innerHTML = `
                <div class="day">${day}</div>
                <div class="hours">${hoursText}</div>
                <span class="badge ${status}">${statusText}</span>
            `;
            hoursList.appendChild(li);
        });
    }

    function renderHoursForm(items) {
        hoursFormBody.innerHTML = '';
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // This array will hold unique days to prevent duplicates
        const uniqueDays = daysOfWeek.map((day, index) => {
            return items.find(i => i.day_of_week == index) || { day_of_week: index, is_open: 0, start_time: '00:00:00', end_time: '00:00:00' };
        });

        uniqueDays.forEach(item => {
            const dayName = daysOfWeek[item.day_of_week];
            const div = document.createElement('div');
            div.className = 'form-group day-row';
            div.dataset.day = item.day_of_week;
            const isChecked = item.is_open == 1 ? 'checked' : '';
            const isDisabled = item.is_open == 0 ? 'disabled' : '';
            div.innerHTML = `
                <div class="day-header">
                    <label>${dayName}</label>
                    <div class="form-check-group">
                        <input type="checkbox" class="is-open-checkbox" id="open-${item.day_of_week}" ${isChecked}>
                        <label for="open-${item.day_of_week}">Open</label>
                    </div>
                </div>
                <div class="day-times">
                    <input type="time" class="form-control start-time" value="${item.start_time.slice(0, 5)}" ${isDisabled}>
                    <span>-</span>
                    <input type="time" class="form-control end-time" value="${item.end_time.slice(0, 5)}" ${isDisabled}>
                </div>
            `;
            hoursFormBody.appendChild(div);

            const checkbox = div.querySelector('.is-open-checkbox');
            const timeInputs = div.querySelectorAll('.start-time, .end-time');
            checkbox.addEventListener('change', (e) => {
                timeInputs.forEach(input => input.disabled = !e.target.checked);
            });
        });
    }
    
    // Load Time Off Data
    async function loadTimeOff() {
        try {
            const res = await fetch(API.loadTimeOff);
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Failed to load time off.');
            renderTimeOffList(data.items);
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Failed to load time off requests. ' + error.message, 'error');
        }
    }

    function renderTimeOffList(items) {
        const timeOffList = document.getElementById('time-off-list');
        timeOffList.innerHTML = '';
        if (!items || items.length === 0) {
            timeOffList.innerHTML = `<li class="muted">No time off requests found.</li>`;
        } else {
            items.forEach(item => {
                const li = document.createElement('li');
                li.className = 'schedule-item';
                
                let statusText;
                let statusClass;
                switch(item.status) {
                    case 'approved':
                        statusText = 'Approved';
                        statusClass = 'open';
                        break;
                    case 'denied':
                        statusText = 'Denied';
                        statusClass = 'closed';
                        break;
                    default:
                        statusText = 'Pending';
                        statusClass = 'closed'; // or maybe 'pending' class
                        break;
                }

                // If the status is pending, show the delete button
                const deleteButtonHtml = item.status === 'pending'
                    ? `<button class="icon-btn-sm js-delete-time-off" data-id="${item.id}" title="Delete"><i class="fa-solid fa-times"></i></button>`
                    : '';

                li.innerHTML = `
                    <div class="day">${item.date}</div>
                    <div class="hours">${item.reason || 'No reason specified'}</div>
                    <span class="badge ${statusClass}">
                        ${statusText} 
                        ${deleteButtonHtml}
                    </span>
                `;
                timeOffList.appendChild(li);
            });
        }
    }

    // Open the Hours Modal when the Edit button is clicked
    editHoursBtn.addEventListener('click', () => {
        loadHoursForEdit();
    });

    // Open the Time Off Modal when the Add button is clicked
    addTimeOffBtn.addEventListener('click', () => {
        openModal(timeOffModal);
    });
    
    loadHours();
    loadTimeOff();
});