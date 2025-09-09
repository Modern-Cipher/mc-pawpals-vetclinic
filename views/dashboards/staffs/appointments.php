<?php
// views/dashboards/staffs/appointments.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
require_once __DIR__ . '/../../../app/models/Profile.php';

$BASE = base_path();
require_once __DIR__ . '/../../partials/role-partials.php';

function isLeadVeterinarian(string $designation): bool {
    $designation = strtolower($designation);
    $negativeKeywords = ['assistant', 'receptionist', 'aide', 'technician', 'trainee', 'intern'];
    foreach ($negativeKeywords as $keyword) {
        if (str_contains($designation, $keyword)) return false;
    }
    $positiveKeywords = ['veterinarian', 'dvm', 'doctor'];
    foreach ($positiveKeywords as $keyword) {
        if (str_contains($designation, $keyword)) return true;
    }
    return false;
}

$user     = $_SESSION['user'] ?? [];
$staff_id = (int)($user['id'] ?? 0);
$name     = $user['name'] ?? 'Staff';

$current_profile = Profile::getByUserId($staff_id);
$designation = $current_profile['designation'] ?? '';
$is_vet = isLeadVeterinarian($designation);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PawPals • Staff Appointments</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/staff-appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php require $sidebar_partial; ?>
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>

    <main class="content">
        <div class="page-head">
            <div>
                <h1 style="margin:0">Appointments</h1>
                <div class="muted">Hello, <?= htmlspecialchars($name) ?> — manage and accept bookings below.</div>
            </div>
            <div class="toolbar">
                <div class="seg" role="tablist" aria-label="Appointment scope">
                    <button class="active" data-scope="pending" role="tab" aria-selected="true" title="Show Pending">Pending</button>
                    <button data-scope="today" role="tab" title="Show Today">Today</button>
                    <button data-scope="mine" role="tab" title="Assigned to me">Assigned</button>
                    <button data-scope="cancelled" role="tab" title="Show Cancelled">Cancelled</button>
                    <button data-scope="noshow" role="tab" title="Show No-Shows">No-Show</button>
                </div>
                <button id="refreshBtn" class="btn" title="Refresh list"><i class="fa-solid fa-rotate"></i><span class="hide-sm">Refresh</span></button>
            </div>
        </div>

        <section class="kpis">
            <div class="kpi"><div class="kpi-num" id="kpiPending">0</div><div class="kpi-label">Pending</div></div>
            <div class="kpi"><div class="kpi-num" id="kpiToday">0</div><div class="kpi-label">Today</div></div>
            <div class="kpi"><div class="kpi-num" id="kpiMine">0</div><div class="kpi-label">Assigned</div></div>
            <div class="kpi"><div class="kpi-num" id="kpiWeek">0</div><div class="kpi-label">This week</div></div>
        </section>

        <div class="card">
            <div class="card-body">
                <div class="table-wrap">
                    <table class="table" id="apptTable">
                        <thead>
                            <tr>
                                <th>Date &amp; Time</th>
                                <th>Service / Pet</th>
                                <th>Pet Owner</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="apptTbody">
                            <tr><td class="muted" colspan="6">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php require $footer_partial; ?>

    <script>
        const App = {
            BASE_URL: '<?= $BASE ?>',
            STAFF_ID: <?= $staff_id ?>,
            IS_VET: <?= $is_vet ? 'true' : 'false' ?>
        };
    </script>
    <script src="<?= $BASE ?>assets/js/staff-appointments.js?v=20250903g"></script>
    <script src="<?= $BASE ?>assets/js/dashboard.js?v=20250903g"></script>
</body>
</html>