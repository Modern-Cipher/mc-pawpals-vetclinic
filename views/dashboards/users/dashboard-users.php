<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['user']);

$user = $_SESSION['user'];
$BASE = base_path();

// role-aware partials (bibigay nito ang $sidebar_partial at $footer_partial)
require_once __DIR__ . '/../../partials/role-partials.php';

// --- ITO YUNG BAGONG DINAGDAG (PHP PART) ---
// Para lumabas ang prompt isang beses lang pagka-login
$just_logged_in = !empty($_SESSION['just_logged_in']);
if ($just_logged_in) unset($_SESSION['just_logged_in']);

// Kinukuha ang flag mula sa session na sinet ng login/middleware
$must_change = !empty($_SESSION['user']['must_change_password']);
// --- END OF BAGONG DINAGDAG ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PawPals – Overview</title>

  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

  <?php require $sidebar_partial; ?>
  <?php require __DIR__ . '/../../partials/topbar.php'; ?>

  <main class="content">
    <h1>Overview</h1>
    <p>Welcome back, <?= htmlspecialchars($user['name'] ?? 'Pet Owner') ?>!</p>

    <section class="stats" style="margin-top: 20px;">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-paw"></i></div>
        <div class="stat-meta">
          <p class="stat-label">My Pets</p>
          <h3 class="stat-value">0</h3>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-meta">
          <p class="stat-label">Upcoming Appointments</p>
          <h3 class="stat-value">0</h3>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-bell"></i></div>
        <div class="stat-meta">
          <p class="stat-label">Notifications</p>
          <h3 class="stat-value">0</h3>
        </div>
      </div>
    </section>

    <section class="panel-grid">
      <article class="panel-card">
        <div class="panel-head">
          <h4>Next Appointment</h4>
          <a class="icon-btn" href="<?= $BASE ?>dashboard/users/appointments" title="View all">
            <i class="fa-solid fa-ellipsis"></i>
          </a>
        </div>
        <div class="panel-body">
          <p class="muted">You have no upcoming appointments. <a href="<?= $BASE ?>dashboard/users/appointments">Book one</a>.</p>
        </div>
      </article>

      <article class="panel-card">
        <div class="panel-head">
          <h4>Recent Medical Records</h4>
          <a class="icon-btn" href="<?= $BASE ?>dashboard/users/records" title="View all">
            <i class="fa-solid fa-ellipsis"></i>
          </a>
        </div>
        <div class="panel-body">
          <p class="muted">No medical records yet. Add your pet first in <a href="<?= $BASE ?>dashboard/users/pets">My Pets</a>.</p>
        </div>
      </article>
    </section>
  </main>

  <?php require $footer_partial; ?>

  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>

  <script>
  (function(){
    const JUST = <?= $just_logged_in ? 'true' : 'false' ?>;
    const MUST = <?= $must_change ? 'true' : 'false' ?>;
    const BASE = <?= json_encode($BASE) ?>;

    // Kung kalolog-in lang at kailangan magpalit ng password
    if (JUST && MUST) {
      Swal.fire({
        icon: 'warning',
        title: 'Please change your password',
        html: 'For security, you need to set a new password for your account after it was reset.',
        showCancelButton: true,
        confirmButtonText: 'Change now',
        cancelButtonText: 'Maybe later'
      }).then(res => {
        if (res.isConfirmed) {
          // Ipadala sa profile page, sa change password section
          window.location.href = BASE + 'dashboard/profile#change-password';
        }
      });
    }
  })();
  </script>
  </body>
</html>