<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// $BASE dapat defined na sa parent page (lahat ng dashboards mo may $BASE = base_path();)
$avatar_default = $BASE . 'assets/images/person1.jpg';

// session data
$user_session_data = $_SESSION['user'] ?? [];

// AVATAR path fallback chain:
// 1) $_SESSION['user']['avatar'] (full URL o absolute path na fina-set sa profile update)
// 2) $_SESSION['user']['avatar_path'] (relative path -> i-prepend natin ng $BASE)
// 3) default image
$user_avatar_path = $avatar_default;
if (!empty($user_session_data['avatar'])) {
    $user_avatar_path = $user_session_data['avatar'];
} elseif (!empty($user_session_data['avatar_path'])) {
    $p = $user_session_data['avatar_path'];
    if (preg_match('~^https?://|^/~i', $p)) {
        $user_avatar_path = $p;
    } else {
        $user_avatar_path = $BASE . ltrim($p, '/');
    }
}

// NAME fallback: name -> (first + last) -> username -> Guest
$full_name_fallback = trim(($user_session_data['first_name'] ?? '') . ' ' . ($user_session_data['last_name'] ?? ''));
$user_name_to_display = $user_session_data['name'] ?? ($full_name_fallback ?: ($user_session_data['username'] ?? 'Guest'));

$user_role = $user_session_data['role'] ?? 'user';
$user_designation = $user_session_data['designation'] ?? null;

// designation fallback depende sa role
$display_designation = $user_designation ?: (
    $user_role === 'admin' ? 'Administrator' :
    ($user_role === 'staff' ? 'Clinic Staff' : 'Pet Owner')
);
?>
<header class="topbar">
  <div class="topbar-brand mobile-only">
    <i class="fa-solid fa-paw"></i><span>PawPals</span>
  </div>

  <div class="grow"></div>

  <div class="topbar-controls desktop-only">
    <div class="user-pill">
      <img src="<?= htmlspecialchars($user_avatar_path) ?>" alt="User" class="avatar">
      <div class="user-meta">
        <strong><?= htmlspecialchars($user_name_to_display) ?></strong>
        <span class="muted"><?= htmlspecialchars($display_designation) ?></span>
      </div>
    </div>
    <button class="icon-btn" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
  </div>

  <div class="topbar-controls mobile-only">
    <button class="icon-btn" aria-label="Notifications"><i class="fa-solid fa-bell"></i></button>
    <img src="<?= htmlspecialchars($user_avatar_path) ?>" alt="User" class="avatar-trigger" id="mobileAvatarTrigger">
  </div>

  <div class="user-dropdown" id="userDropdown">
    <div class="dropdown-user-meta">
      <strong><?= htmlspecialchars($user_name_to_display) ?></strong>
      <span class="muted"><?= htmlspecialchars($display_designation) ?></span>
    </div>
    <a href="<?= $BASE ?>dashboard/profile"><i class="fa-solid fa-circle-user"></i> Profile</a>
    <a href="<?= $BASE ?>auth/logout" id="logoutLinkMobile"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
  </div>
</header>
