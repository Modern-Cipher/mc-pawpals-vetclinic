<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
$BASE = base_path();
require_once __DIR__ . '/../../partials/role-partials.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>My Schedule</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/schedule.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<?php require $sidebar_partial; ?>
<?php require __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content">
    <div class="page-head">
        <h1 class="page-title">My Schedule</h1>
    </div>

    <div class="schedule-grid">
        <div class="panel-card">
            <div class="panel-body">
                <div class="panel-head-flex">
                    <h4>My Weekly Hours</h4>
                    <button id="editHoursBtn" class="btn btn-secondary btn-sm"><i class="fa-solid fa-edit"></i> Edit</button>
                </div>
                <ul id="hours-list" class="schedule-list">
                </ul>
            </div>
        </div>
        
        <div class="panel-card">
            <div class="panel-body">
                <div class="panel-head-flex">
                    <h4>Time Off Requests</h4>
                    <button id="addTimeOffBtn" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add</button>
                </div>
                <ul id="time-off-list" class="schedule-list">
                </ul>
            </div>
        </div>
    </div>
</main>

<?php require $footer_partial; ?>

<div id="hoursModal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="hoursModalTitle">Edit Weekly Schedule</h3>
            <button id="closeHoursModal" class="modal-close-btn" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="hoursForm">
                <div id="hours-form-body">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancelHoursBtn">Cancel</button>
            <button class="btn btn-primary" form="hoursForm">Save Changes</button>
        </div>
    </div>
</div>

<div id="timeOffModal" class="modal" role="dialog" aria-modal="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="timeOffModalTitle">Add Time Off Request</h3>
            <button id="closeTimeOffModal" class="modal-close-btn" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="timeOffForm">
                <div class="form-group">
                    <label for="timeOffDate">Date</label>
                    <input type="text" class="form-control" id="timeOffDate" required>
                </div>
                <div class="form-group">
                    <label for="timeOffReason">Reason (Optional)</label>
                    <textarea class="form-control" id="timeOffReason" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancelTimeOffBtn">Cancel</button>
            <button class="btn btn-primary" form="timeOffForm">Submit Request</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const App = { 
        BASE_URL: '<?= $BASE ?>',
        staffId: '<?= $_SESSION['user']['id'] ?? 'null' ?>'
    };
</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
<script src="<?= $BASE ?>assets/js/schedule.js"></script>
</body>
</html>