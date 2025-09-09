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
<title>Pet Owner Management</title>

<link rel="stylesheet" href="<?= $BASE ?>assets/css/dashboard-admin.css">
<link rel="stylesheet" href="<?= $BASE ?>assets/css/staff.css">
<link rel="stylesheet" href="<?= $BASE ?>assets/css/admin-pet-owners.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php require $sidebar_partial; ?>
<?php require __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content">
    <div class="page-head">
        <h1 class="page-title">Pet Owners</h1>
        <div class="page-tools">
            <div class="filters">
                <button class="btn active" data-filter="all">All</button>
                <button class="btn" data-filter="verified">Verified</button>
                <button class="btn" data-filter="unverified">Unverified</button>
            </div>
            <div class="searchbox">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input id="q" type="search" placeholder="Search by name, email, username…">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table" id="tbl">
            <thead>
                <tr>
                    <th class="mincol">Photo</th>
                    <th>Name</th>
                    <th>Email / Username</th>
                    <th>Status</th> <th>Last Login</th>
                    <th class="mincol">Active</th>
                    <th class="mincol">Actions</th>
                </tr>
            </thead>
            <tbody id="rows"><tr><td colspan="7">Loading…</td></tr></tbody>
        </table>
    </div>

    <div class="cards" id="cards"></div>
</main>

<?php require $footer_partial; ?>

<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
<script src="<?= $BASE ?>assets/js/admin-pet-owners.js"></script>
</body>
</html>