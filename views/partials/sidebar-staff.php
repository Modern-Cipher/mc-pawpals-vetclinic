<?php
// views/partials/sidebar-staff.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';

$BASE = base_path();

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$segments = explode('/', $path);
$last_segment = end($segments);
// Use 'medical' as active slug for both 'medical' and 'medical-form' pages
$active = ($last_segment === 'entry' && in_array('medical', $segments)) ? 'medical' : $last_segment;

$user_id = (int)($_SESSION['user']['id'] ?? 0);
$allowed = staff_allowed_slugs($user_id);

$can = function(array $cand) use ($allowed): bool {
    if (!$allowed) return false;
    foreach ($cand as $c) if (in_array($c, $allowed, true)) return true;
    return false;
};

$show_appointments = $can(['appointments']);
$show_pets = $can(['pets']);
$show_records = $can(['medical', 'medical_records', 'records']);
$show_announcements = $can(['announcements']);
$show_documents = $can(['documents']);
$show_petcare = $can(['petcare']);
$show_schedule = $can(['schedule']);

$activeClass = function(string $slug) use ($active): string {
    return $active === $slug ? 'active' : '';
};
?>
<aside id="sidebar" class="sidebar" aria-label="Sidebar Navigation">
    <button id="sbToggleTop" class="sb-toggle-top" aria-label="Toggle sidebar">
        <i class="fa-solid fa-angles-left"></i>
    </button>

    <div class="brand">
        <i class="fa-solid fa-paw"></i><span class="brand-text">PawPals</span>
    </div>

    <nav class="menu" role="menu">
        <a href="<?= $BASE ?>dashboard/staffs" class="menu-item <?= $activeClass('staffs') ?>" title="Overview" role="menuitem">
            <i class="fa-solid fa-gauge-simple-high"></i><span class="label">Overview</span>
        </a>

        <?php if ($show_appointments): ?>
        <a href="<?= $BASE ?>dashboard/staffs/appointments" class="menu-item <?= $activeClass('appointments') ?>" title="Appointments" role="menuitem">
            <i class="fa-solid fa-calendar-check"></i><span class="label">Appointments</span>
        </a>
        <?php endif; ?>

        <?php if ($show_records): ?>
        <a href="<?= $BASE ?>dashboard/staffs/medical" class="menu-item <?= $activeClass('medical') ?>" title="Medical Records" role="menuitem">
            <i class="fa-solid fa-file-medical"></i><span class="label">Medical Records</span>
        </a>
        <?php endif; ?>
        
        <?php if ($show_documents): ?>
        <a href="<?= $BASE ?>dashboard/staffs/pet-documentation" class="menu-item <?= $activeClass('pet-documentation') ?>" title="Documents" role="menuitem">
            <i class="fa-regular fa-folder-open"></i><span class="label">Documents</span>
        </a>
        <?php endif; ?>

        <?php if ($show_pets): ?>
        <a href="<?= $BASE ?>dashboard/staffs/pets" class="menu-item <?= $activeClass('pets') ?>" title="Pets" role="menuitem">
            <i class="fa-solid fa-dog"></i><span class="label">Pets</span>
        </a>
        <?php endif; ?>

        <?php if ($show_announcements): ?>
        <a href="<?= $BASE ?>dashboard/staffs/announcements" class="menu-item <?= $activeClass('announcements') ?>" title="Announcements" role="menuitem">
            <i class="fa-solid fa-bullhorn"></i><span class="label">Announcements</span>
        </a>
        <?php endif; ?>

        <?php if ($show_petcare): ?>
        <a href="<?= $BASE ?>dashboard/staffs/petcare" class="menu-item <?= $activeClass('petcare') ?>" title="Pet Care Tips" role="menuitem">
            <i class="fa-solid fa-book-medical"></i><span class="label">Pet Care Tips</span>
        </a>
        <?php endif; ?>

        <?php if ($show_schedule): ?>
        <a href="<?= $BASE ?>dashboard/staffs/my-schedule" class="menu-item <?= $activeClass('my-schedule') ?>" title="My Schedule" role="menuitem">
            <i class="fa-solid fa-calendar-alt"></i><span class="label">My Schedule</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-spacer" aria-hidden="true"></div>

    <div class="sidebar-bottom">
        <a href="<?= $BASE ?>dashboard/profile" class="menu-item <?= $activeClass('profile') ?>" title="Profile" role="menuitem">
            <i class="fa-solid fa-circle-user"></i><span class="label">Profile</span>
        </a>
        <a href="<?= $BASE ?>auth/logout" id="logoutLink" class="menu-item danger" title="Logout" role="menuitem">
            <i class="fa-solid fa-right-from-bracket"></i><span class="label">Logout</span>
        </a>
    </div>
</aside>

<script>
document.querySelectorAll('.menu .menu-item').forEach(a=>{
    if(!a.hasAttribute('title')){
        const lbl=a.querySelector('.label')?.textContent?.trim()||a.textContent.trim();
        if(lbl) a.setAttribute('title', lbl);
    }
});
</script>