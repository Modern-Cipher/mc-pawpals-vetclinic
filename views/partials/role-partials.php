<?php
// views/partials/role-partials.php
// Sets $sidebar_partial and $footer_partial based on logged-in user's role.
// Safe fallbacks are provided in case a file is missing.

if (session_status() === PHP_SESSION_NONE) session_start();

$role = $_SESSION['user']['role'] ?? 'user';

// Defaults (admin layout)
$sidebar_partial = __DIR__ . '/sidebar.php';
$footer_partial  = __DIR__ . '/footer.php';

// Map per role
switch ($role) {
  case 'admin':
    $sidebar_partial = __DIR__ . '/sidebar.php';
    $footer_partial  = __DIR__ . '/footer.php';
    break;

  case 'staff':
    $sidebar_partial = __DIR__ . '/sidebar-staff.php';
    $footer_partial  = __DIR__ . '/footer-staff.php';
    break;

  default: // 'user' / pet owner
    $sidebar_partial = __DIR__ . '/sidebar-user.php';
    $footer_partial  = __DIR__ . '/footer-user.php';
    break;
}

// Safety: if any target file is missing, fall back to user/admin versions
if (!file_exists($sidebar_partial)) {
  $sidebar_partial = __DIR__ . '/sidebar-user.php';
  if (!file_exists($sidebar_partial)) {
    $sidebar_partial = __DIR__ . '/sidebar.php';
  }
}
if (!file_exists($footer_partial)) {
  $footer_partial = __DIR__ . '/footer-user.php';
  if (!file_exists($footer_partial)) {
    $footer_partial = __DIR__ . '/footer.php';
  }
}
