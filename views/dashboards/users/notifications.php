<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['user']);
$BASE = base_path();
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Notifications</title>
<link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head><body>
<?php require_once __DIR__ . '/../../partials/sidebar-user.php'; ?>
<div id="drawerBackdrop" class="backdrop" hidden></div>
<?php require_once __DIR__ . '/../../partials/topbar.php'; ?>
<main class="content">
  <h1>Notifications</h1>
  <p class="muted">No new notifications.</p>
</main>
<?php require_once __DIR__ . '/../../partials/footer-user.php'; ?>
<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
</body></html>
