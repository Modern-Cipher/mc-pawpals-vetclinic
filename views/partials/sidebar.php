<?php
$current_path      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_dir_parts = explode('/', trim($current_path, '/'));
$parent_page       = $current_dir_parts[1] ?? '';
$active_page       = end($current_dir_parts);
$user_role         = $_SESSION['user']['role'] ?? 'guest';
$BASE              = base_path();

function is_active($slug, $cur){ return $slug === $cur ? 'active' : ''; }

/** Native tooltip only (no extra CSS) */
function tip_attr($text){
    $t = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return ' title="'.$t.'"';
}

$settings_open = ($parent_page === 'settings');
$users_open    = in_array($active_page, ['staff', 'pet-owner', 'staff_time_off']); // Updated: open state for User Management
?>
<aside id="sidebar" class="sidebar" aria-label="Sidebar Navigation">
    <button id="sbToggleTop" class="sb-toggle-top" aria-label="Toggle sidebar">
        <i class="fa-solid fa-angles-left"></i>
    </button>

    <div class="brand">
        <i class="fa-solid fa-paw"></i><span class="brand-text">PawPals</span>
    </div>

    <nav class="menu" role="menu">
        <?php if ($user_role === 'admin'): ?>
            <a href="<?= $BASE ?>dashboard/admin"
               class="menu-item <?= is_active('admin', $active_page) ?>"
               <?= tip_attr('Dashboard') ?> role="menuitem"
               <?= is_active('admin',$active_page) ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-gauge-high"></i><span class="label">Dashboard</span>
            </a>

            <div class="menu-item-container has-submenu <?= $users_open ? 'open' : '' ?> <?= is_active('users', $parent_page) ?>">
                <a href="#"
                   class="menu-item submenu-toggle"
                   <?= tip_attr('User Management') ?> role="button"
                   aria-haspopup="true" aria-expanded="<?= $users_open ? 'true' : 'false' ?>">
                    <i class="fa-solid fa-users-gear"></i><span class="label">User Management</span>
                    <i class="fa-solid fa-chevron-right submenu-arrow"></i>
                </a>

                <ul class="sub-menu" role="menu">
                    <li class="sub-menu-item <?= is_active('staff', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/admin/staff"
                           <?= tip_attr('Staff') ?> role="menuitem"
                           <?= is_active('staff',$active_page) ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-user-tie"></i><span class="label">Staff</span>
                        </a>
                    </li>

                   <li class="sub-menu-item <?= is_active('pet-owner', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/admin/pet-owner"
                           <?= tip_attr('Pet Owners') ?> role="menuitem">
                           <i class="fa-solid fa-dog"></i><span class="label">Pet Owners</span>
                        </a>
                    </li>

                   <li class="sub-menu-item <?= is_active('staff_time_off', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/admin/staff_time_off"
                           <?= tip_attr('Staff Time Off') ?> role="menuitem">
                           <i class="fa-solid fa-calendar-check"></i><span class="label">Staff Time Off</span>
                        </a>
                    </li>
                </ul>
            </div>

            <a href="<?= $BASE ?>dashboard/admin"
               class="menu-item <?= is_active('reports', $active_page) ?>"
               <?= tip_attr('Reports') ?> role="menuitem"
               <?= is_active('reports',$active_page) ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-chart-line"></i><span class="label">Reports</span>
            </a>

            <a href="<?= $BASE ?>dashboard/admin"
               class="menu-item <?= is_active('audit', $active_page) ?>"
               <?= tip_attr('Audit Logs') ?> role="menuitem"
               <?= is_active('audit',$active_page) ? 'aria-current="page"' : '' ?>>
                <i class="fa-solid fa-clipboard-list"></i><span class="label">Audit Logs</span>
            </a>

            <div class="menu-item-container has-submenu <?= $settings_open ? 'open' : '' ?> <?= is_active('settings', $parent_page) ?>">
                <a href="#"
                   class="menu-item submenu-toggle"
                   <?= tip_attr('System Settings') ?> role="button"
                   aria-haspopup="true" aria-expanded="<?= $settings_open ? 'true' : 'false' ?>">
                    <i class="fa-solid fa-gear"></i><span class="label">System Settings</span>
                    <i class="fa-solid fa-chevron-right submenu-arrow"></i>
                </a>

                <ul class="sub-menu" role="menu">
                    <li class="sub-menu-item <?= is_active('general', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/settings/general"
                           <?= tip_attr('General') ?> role="menuitem"
                           <?= is_active('general',$active_page) ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-sliders"></i><span class="label">General</span>
                        </a>
                    </li>
                    <li class="sub-menu-item <?= is_active('clinic-hours', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/settings/clinic-hours"
                           <?= tip_attr('Clinic Hours') ?> role="menuitem"
                           <?= is_active('clinic-hours',$active_page) ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-clock"></i><span class="label">Clinic Hours</span>
                        </a>
                    </li>
                    <li class="sub-menu-item <?= is_active('announcements', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/settings/announcements"
                           <?= tip_attr('Announcements') ?> role="menuitem"
                           <?= is_active('announcements',$active_page) ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-bullhorn"></i><span class="label">Announcements</span>
                        </a>
                    </li>
                    <li class="sub-menu-item <?= is_active('petcare', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/petcare"
                           <?= tip_attr('Pet Care') ?> role="menuitem"
                           <?= is_active('petcare',$active_page) ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-heart-pulse"></i><span class="label">Pet Care</span>
                        </a>
                    </li>
                    <li class="sub-menu-item <?= is_active('ratings', $active_page) ?>">
                        <a href="<?= $BASE ?>dashboard/ratings"
                           <?= tip_attr('Rating') ?> role="menuitem"
                           <?= is_active('ratings',$active_page) ? 'aria-current="page"' : '' ?>>
                            <i class="fa-solid fa-star"></i><span class="label">Rating</span>
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-spacer" aria-hidden="true"></div>

    <div class="sidebar-bottom">
        <a href="<?= $BASE ?>dashboard/profile"
           class="menu-item <?= is_active('profile', $active_page) ?>"
           <?= tip_attr('Profile') ?> role="menuitem"
           <?= is_active('profile',$active_page) ? 'aria-current="page"' : '' ?>>
            <i class="fa-solid fa-circle-user"></i><span class="label">Profile</span>
        </a>

        <a href="<?= $BASE ?>auth/logout"
           id="logoutLink"
           class="menu-item danger"
           <?= tip_attr('Logout') ?> role="menuitem">
            <i class="fa-solid fa-right-from-bracket"></i><span class="label">Logout</span>
        </a>
    </div>
</aside>

<script>
document.querySelectorAll('.menu .menu-item, .sub-menu a').forEach(a=>{
    if(!a.hasAttribute('title')){
        const lbl = a.querySelector('.label')?.textContent?.trim() || a.textContent.trim();
        if(lbl) a.setAttribute('title', lbl);
    }
});
</script>