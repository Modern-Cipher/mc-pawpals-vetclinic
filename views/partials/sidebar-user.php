<?php
$current_path      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_dir_parts = explode('/', trim($current_path, '/'));
$active_page       = end($current_dir_parts);
$BASE              = base_path(); 

function is_active($slug, $cur){ return $slug === $cur ? 'active' : ''; }
function tip_attr($t){ return ' title="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '"'; }
?>
<aside id="sidebar" class="sidebar" aria-label="Sidebar Navigation">
    <button id="sbToggleTop" class="sb-toggle-top" aria-label="Toggle sidebar">
        <i class="fa-solid fa-angles-left"></i>
    </button>

    <div class="brand">
        <i class="fa-solid fa-paw"></i><span class="brand-text">PawPals</span>
    </div>

    <nav class="menu" role="menu">
        <a href="<?= $BASE ?>dashboard/users" class="menu-item <?= is_active('users', $active_page) ?>" <?= tip_attr('Overview') ?> role="menuitem">
            <i class="fa-solid fa-house"></i><span class="label">Overview</span>
        </a>
        <a href="<?= $BASE ?>dashboard/users/pets" class="menu-item <?= is_active('pets', $active_page) ?>" <?= tip_attr('My Pets') ?> role="menuitem">
            <i class="fa-solid fa-dog"></i><span class="label">My Pets</span>
        </a>
        <a href="<?= $BASE ?>dashboard/users/appointments" class="menu-item <?= is_active('appointments', $active_page) ?>" <?= tip_attr('Appointments') ?> role="menuitem">
            <i class="fa-solid fa-calendar-check"></i><span class="label">Appointments</span>
        </a>
        <a href="<?= $BASE ?>dashboard/users/records" class="menu-item <?= is_active('records', $active_page) ?>" <?= tip_attr('Health Summary') ?> role="menuitem">
            <i class="fa-solid fa-file-medical"></i><span class="label">Health Summary</span>
        </a>

        <a href="<?= $BASE ?>dashboard/users/documents" class="menu-item <?= is_active('documents', $active_page) ?>" <?= tip_attr('Pet Documents') ?> role="menuitem">
            <i class="fa-solid fa-folder-open"></i><span class="label">Pet Documents</span>
        </a>
        
        <a href="<?= $BASE ?>dashboard/users/notifications" class="menu-item <?= is_active('notifications', $active_page) ?>" <?= tip_attr('Announcements') ?> role="menuitem">
            <i class="fa-solid fa-bullhorn"></i><span class="label">Announcements</span>
        </a>
    </nav>

    <div class="sidebar-spacer" aria-hidden="true"></div>

    <div class="sidebar-bottom">
        <a href="<?= $BASE ?>dashboard/ratings" class="menu-item <?= is_active('ratings', $active_page) ?>" <?= tip_attr('Give Feedback') ?> role="menuitem">
            <i class="fa-solid fa-star-half-stroke"></i><span class="label">Give Feedback</span>
        </a>
        <a href="<?= $BASE ?>dashboard/profile" class="menu-item <?= is_active('profile', $active_page) ?>" <?= tip_attr('Profile') ?> role="menuitem">
            <i class="fa-solid fa-circle-user"></i><span class="label">Profile</span>
        </a>
        <a href="<?= $BASE ?>auth/logout" id="logoutLink" class="menu-item danger" <?= tip_attr('Logout') ?> role="menuitem">
            <i class="fa-solid fa-right-from-bracket"></i><span class="label">Logout</span>
        </a>
    </div>
</aside>