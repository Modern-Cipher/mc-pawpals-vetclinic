<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['admin']);
$BASE = base_path();
require_once __DIR__ . '/../../partials/role-partials.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Staff Time Off</title>

<link rel="stylesheet" href="<?= $BASE ?>assets/css/dashboard-admin.css">
<link rel="stylesheet" href="<?= $BASE ?>assets/css/staff-time-off.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php require $sidebar_partial; ?>
<?php require __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content content-time-off">
    <div class="page-head">
        <h1 class="page-title">Staff Time Off Requests</h1>
    </div>

    <div class="table-wrap">
        <table class="table" id="requests-table">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th class="min-col">Actions</th>
                </tr>
            </thead>
            <tbody id="request-rows">
                <tr><td colspan="5">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="cards" id="request-cards">
        </div>
</main>

<?php require $footer_partial; ?>

<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
<script src="<?= $BASE ?>assets/js/admin-staff-time-off.js"></script>
</body>
</html>