<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['admin']);
$BASE = base_path();
require_once __DIR__ . '/../../partials/role-partials.php';
require_once __DIR__ . '/../../../app/models/Settings.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Clinic Hours Settings</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/dashboard-admin.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/clinic-hours.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<?php require $sidebar_partial; ?>
<?php require __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content">
    <div class="page-head">
        <h1 class="page-title">Clinic Hours</h1>
    </div>
    
    <div class="panel-card">
        <div class="panel-body">
            <p class="muted">Manage the default opening hours for the clinic. These hours will be used for appointment scheduling.</p>
            <div class="table-container">
                <table class="table table-hours">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Status</th>
                            <th>Hours</th>
                            <th>Break Time</th>
                            <th>Slot Duration (mins)</th>
                        </tr>
                    </thead>
                    <tbody id="schedule-body">
                        </tbody>
                </table>
            </div>
            <div class="form-actions-right">
                 <button id="btnSaveAll" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save All</button>
            </div>
        </div>
    </div>
</main>

<?php require $footer_partial; ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const App = { BASE_URL: '<?= $BASE ?>' };
    const API = {
        load: `${App.BASE_URL}api/settings/clinic_hours.php`,
        update: `${App.BASE_URL}api/settings/clinic_hours_update.php`
    };

    const scheduleBody = document.getElementById('schedule-body');
    const daysOfWeek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    let scheduleData = [];

    async function loadSchedule() {
        try {
            const res = await fetch(API.load);
            const data = await res.json();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to load schedule.');
            scheduleData = data.items;
            renderSchedule();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
            scheduleBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Failed to load clinic hours.</td></tr>`;
        }
    }

    function renderSchedule() {
        scheduleBody.innerHTML = '';
        scheduleData.forEach(day => {
            const row = document.createElement('tr');
            row.dataset.id = day.id;
            row.innerHTML = `
                <td data-label="Day">${daysOfWeek[day.day_of_week]}</td>
                <td data-label="Status">
                    <div class="day-toggle">
                        <label class="switch">
                            <input type="checkbox" class="js-toggle-day" ${day.is_open ? 'checked' : ''}>
                            <span class="slider"></span>
                        </label>
                        <span class="label">${day.is_open ? 'Open' : 'Closed'}</span>
                    </div>
                </td>
                <td data-label="Hours">
                    <div class="input-pair">
                        <input type="text" class="form-control time-picker js-start-time" value="${day.start_time}" ${!day.is_open ? 'disabled' : ''}>
                        <span>to</span>
                        <input type="text" class="form-control time-picker js-end-time" value="${day.end_time}" ${!day.is_open ? 'disabled' : ''}>
                    </div>
                </td>
                <td data-label="Break Time">
                    <div class="input-pair">
                        <input type="text" class="form-control time-picker js-break-start" value="${day.break_start || ''}" placeholder="Optional" ${!day.is_open ? 'disabled' : ''}>
                        <span>to</span>
                        <input type="text" class="form-control time-picker js-break-end" value="${day.break_end || ''}" placeholder="Optional" ${!day.is_open ? 'disabled' : ''}>
                    </div>
                </td>
                <td data-label="Slot Duration (mins)">
                    <input type="number" class="form-control js-slot-minutes" value="${day.slot_minutes}" min="15" step="15" ${!day.is_open ? 'disabled' : ''}>
                </td>
            `;
            scheduleBody.appendChild(row);
        });

        initTimePickers();
    }

    function initTimePickers() {
        flatpickr(".time-picker", {
            enableTime: true,
            noCalendar: true,
            dateFormat: "H:i",
            time_24hr: true,
            minuteIncrement: 15
        });
    }

    scheduleBody.addEventListener('change', (e) => {
        const row = e.target.closest('tr');
        if (!row) return;

        const id = row.dataset.id;
        const day = scheduleData.find(d => d.id == id);
        if (!day) return;

        if (e.target.classList.contains('js-toggle-day')) {
            day.is_open = e.target.checked;
            row.querySelector('.label').textContent = day.is_open ? 'Open' : 'Closed';
            row.querySelectorAll('input').forEach(input => {
                if (input !== e.target) {
                    input.disabled = !day.is_open;
                }
            });
            if (!day.is_open) {
                day.start_time = '00:00';
                day.end_time = '00:00';
                day.break_start = null;
                day.break_end = null;
                day.slot_minutes = 30;
                row.querySelector('.js-start-time').value = '00:00';
                row.querySelector('.js-end-time').value = '00:00';
                row.querySelector('.js-break-start').value = '';
                row.querySelector('.js-break-end').value = '';
                row.querySelector('.js-slot-minutes').value = 30;
            }
        }
    });

    document.getElementById('btnSaveAll').addEventListener('click', async () => {
        const updatedData = [];
        scheduleBody.querySelectorAll('tr').forEach(row => {
            const id = row.dataset.id;
            const isOpen = row.querySelector('.js-toggle-day').checked;
            const start_time = row.querySelector('.js-start-time').value || '00:00';
            const end_time = row.querySelector('.js-end-time').value || '00:00';
            const break_start = row.querySelector('.js-break-start').value || null;
            const break_end = row.querySelector('.js-break-end').value || null;
            const slot_minutes = row.querySelector('.js-slot-minutes').value || 30;

            updatedData.push({
                id,
                is_open: isOpen,
                start_time,
                end_time,
                break_start,
                break_end,
                slot_minutes
            });
        });

        Swal.fire({
            title: 'Saving changes...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const res = await fetch(API.update, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ hours: updatedData })
            });
            const data = await res.json();
            Swal.close();
            if (!res.ok || !data.ok) throw new Error(data.error || 'Failed to update schedule.');

            Swal.fire('Success', 'Clinic hours have been updated!', 'success');
            loadSchedule();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    });

    loadSchedule();
</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
</body>
</html>