<?php
$current_path_footer = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$active_page_footer  = basename($current_path_footer);
$BASE_URL_FOR_FOOTER = base_path();
?>
<footer class="admin-footer">
    <p>&copy; <?= date('Y') ?> PawPals. All rights reserved.</p>
</footer>

<nav class="bottom-bar" aria-label="Mobile Navigation">
    <a href="#" class="bb-item" id="mobileUsersTrigger" title="Users">
      <i class="fa-solid fa-users-gear"></i><span>Users</span>
    </a>

    <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/admin" class="bb-item" title="Reports">
      <i class="fa-solid fa-chart-line"></i><span>Reports</span>
    </a>

    <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/admin"
      class="bb-item <?= ($active_page_footer === 'admin') ? 'active' : '' ?>"
      title="Dashboard">
      <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/admin" class="bb-item" title="Audit Logs">
      <i class="fa-solid fa-clipboard-list"></i><span>Audit Logs</span>
    </a>

    <button class="bb-item" id="mobileSettingsTrigger" title="Settings">
      <i class="fa-solid fa-gear"></i><span>Settings</span>
    </button>
</nav>

<div id="drawerBackdrop" class="backdrop" hidden></div>

<div id="settingsDrawer" class="mobile-drawer" role="dialog" aria-modal="true" aria-labelledby="settingsDrawerTitle">
    <div class="drawer-header">
      <h4 id="settingsDrawerTitle">System Settings</h4>
      <button id="closeSettingsDrawer" class="modal-close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="drawer-content">
      <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/settings/general" class="drawer-link"><i class="fa-solid fa-sliders"></i><span>General</span></a>
      <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/settings/clinic-hours" class="drawer-link"><i class="fa-solid fa-clock"></i><span>Clinic Hours</span></a>
      <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/settings/announcements" class="drawer-link"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
      <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/petcare" class="drawer-link"><i class="fa-solid fa-heart-pulse"></i><span>Pet Care</span></a>
      <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/ratings" class="drawer-link"><i class="fa-solid fa-star"></i><span>Rating</span></a>
    </div>
</div>

<div id="usersDrawer" class="mobile-drawer" role="dialog" aria-modal="true" aria-labelledby="usersActionTitle">
    <div class="drawer-header">
      <h4 id="usersActionTitle">User Management</h4>
      <button id="closeUsersDrawer" class="modal-close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="drawer-content">
      <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/admin/staff" class="drawer-link">
        <i class="fa-solid fa-user-tie"></i><span>Staff</span>
      </a>

     <a href="<?= $BASE_URL_FOR_FOOTER ?>dashboard/admin/pet-owner" class="drawer-link">
          <i class="fa-solid fa-dog"></i><span>Pet Owners</span>
      </a>
    </div>
</div>

<script>
    // EXACT same behavior na gamit ng dashboard.js ('.show' classes)
    document.addEventListener('DOMContentLoaded', () => {
        const drawerBackdrop = document.getElementById('drawerBackdrop');

        // Settings
        const settingsTrigger = document.getElementById('mobileSettingsTrigger');
        const settingsDrawer = document.getElementById('settingsDrawer');
        const closeSettingsDrawer = document.getElementById('closeSettingsDrawer');

        // Users
        const usersTrigger = document.getElementById('mobileUsersTrigger');
        const usersDrawer = document.getElementById('usersDrawer');
        const closeUsersDrawer = document.getElementById('closeUsersDrawer');

        function openDrawer(drawerEl) {
            if (!drawerEl) return;
            // isara ibang drawer kung bukas
            document.querySelectorAll('.mobile-drawer.show').forEach(d => d.classList.remove('show'));
            // show backdrop + drawer
            drawerBackdrop?.removeAttribute('hidden');
            drawerBackdrop?.classList.add('show');
            drawerEl.classList.add('show');
            document.body.classList.add('no-scroll');
        }

        function closeAllDrawers() {
            drawerBackdrop?.classList.remove('show');
            document.querySelectorAll('.mobile-drawer').forEach(d => d.classList.remove('show'));
            document.body.classList.remove('no-scroll');
            // delay bago i-hide para may transition
            setTimeout(() => drawerBackdrop?.setAttribute('hidden', ''), 300);
        }

        // Triggers
        settingsTrigger?.addEventListener('click', () => openDrawer(settingsDrawer));
        usersTrigger?.addEventListener('click', (e) => {
            e.preventDefault();
            openDrawer(usersDrawer);
        });

        // Close buttons
        closeSettingsDrawer?.addEventListener('click', closeAllDrawers);
        closeUsersDrawer?.addEventListener('click', closeAllDrawers);

        // Backdrop click
        drawerBackdrop?.addEventListener('click', closeAllDrawers);

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllDrawers();
        });
    });
</script>