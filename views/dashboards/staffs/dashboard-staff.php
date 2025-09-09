<?php
// views/dashboards/staffs/dashboard-staff.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);

$BASE = base_path();

// Role-aware partials (defines $sidebar_partial, $footer_partial)
require_once __DIR__ . '/../../partials/role-partials.php';

// Show the prompt only once right after login
$just_logged_in = !empty($_SESSION['just_logged_in']);
if ($just_logged_in) unset($_SESSION['just_logged_in']);

// Flag comes from middleware/login
$must_change = !empty($_SESSION['user']['must_change_password']);

$user = $_SESSION['user'] ?? [];
$name = $user['name'] ?? 'Staff';
$avatar = $user['avatar'] ?? ($BASE . 'assets/images/person1.jpg');
$designation = $user['designation'] ?? '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PawPals • Staff Dashboard</title>

  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* light page cosmetics so it looks ok even without a page-specific css */
    .content{padding-top:84px}
    .welcome{display:flex;align-items:center;gap:14px;margin:0 0 14px}
    .welcome img{width:44px;height:44px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb}
    .cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    @media (max-width:1000px){.cards{grid-template-columns:repeat(2,1fr)}}
    @media (max-width:640px){.cards{grid-template-columns:1fr}}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px}
    .card h4{margin:0 0 6px}
    .muted{color:#64748b}
  </style>
</head>
<body>

  <?php require $sidebar_partial; ?>
  <?php require __DIR__ . '/../../partials/topbar.php'; ?>

  <main class="content">
    <div class="welcome">
      <img src="<?= htmlspecialchars($avatar) ?>" alt="">
      <div>
        <h2 style="margin:0">Welcome back, <?= htmlspecialchars($name) ?>!</h2>
        <div class="muted"><?= htmlspecialchars($designation) ?></div>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <h4>Today</h4>
        <div class="muted">Your quick overview appears here.</div>
      </div>
      <div class="card">
        <h4>Tasks</h4>
        <div class="muted">No pending tasks.</div>
      </div>
      <div class="card">
        <h4>Announcements</h4>
        <div class="muted">No new announcements.</div>
      </div>
    </div>
  </main>

  <?php require $footer_partial; ?>

  <script>
  (function(){
    const JUST = <?= $just_logged_in ? 'true':'false' ?>;
    const MUST = <?= $must_change ? 'true':'false' ?>;
    const BASE = <?= json_encode($BASE) ?>;

    if (JUST && MUST) {
      Swal.fire({
        icon: 'warning',
        title: 'Please change your password',
        html: 'For security, you need to set a new password for your staff account.',
        showCancelButton: true,
        confirmButtonText: 'Change now',
        cancelButtonText: 'Maybe later'
      }).then(res=>{
        if(res.isConfirmed){
          // send to profile page (with anchor)
          window.location.href = BASE + 'dashboard/profile#change-password';
        }
      });
    }
  })();
  </script>

  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
</body>
</html>
