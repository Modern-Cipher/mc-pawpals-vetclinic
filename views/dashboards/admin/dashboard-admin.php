<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['admin']);

$user = $_SESSION['user'];
$BASE = base_path();

// LOAD role-aware partial paths
require_once __DIR__ . '/../../partials/role-partials.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PawPals Admin Dashboard</title>

  <link rel="stylesheet" href="<?= $BASE ?>assets/css/dashboard-admin.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

  <?php require $sidebar_partial; ?>   <!-- <<— IMPORTANT: actual sidebar -->
  <?php require __DIR__ . '/../../partials/topbar.php'; ?>

  <main class="content">
    <h1>Admin Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($user['name']) ?>!</p>

    <section class="stats" style="margin-top: 20px;">
      <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-people-group"></i></div><div class="stat-meta"><p class="stat-label">Total Pet Owners</p><h3 class="stat-value">1,245</h3></div></div>
      <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-dog"></i></div><div class="stat-meta"><p class="stat-label">Total Pets</p><h3 class="stat-value">2,876</h3></div></div>
      <div class="stat-card"><div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div><div class="stat-meta"><p class="stat-label">Appointments</p><h3 class="stat-value">312</h3></div></div>
    </section>

    <section class="panel-grid">
      <article class="panel-card">
        <div class="panel-head"><h4>Recent Appointments</h4><button class="icon-btn"><i class="fa-solid fa-ellipsis"></i></button></div>
        <div class="panel-body">
          <ul class="mini-list">
            <li><span>Vaccination – Luna (Dog)</span><time>Today, 2:30 PM</time></li>
            <li><span>Checkup – Miko (Cat)</span><time>Today, 4:00 PM</time></li>
            <li><span>Deworming – Chip (Pup)</span><time>Tomorrow, 10:00 AM</time></li>
          </ul>
        </div>
      </article>

      <article class="panel-card">
        <div class="panel-head"><h4>Announcements</h4><button class="icon-btn"><i class="fa-solid fa-ellipsis"></i></button></div>
        <div class="panel-body"><p class="muted">No announcements yet. Create one from the sidebar.</p></div>
      </article>
    </section>
  </main>

  <?php require $footer_partial; ?>     <!-- <<— IMPORTANT: role-aware footer (may drawerBackdrop na ito) -->

  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
</body>
</html>
