<?php
// views/partials/footer-user.php
$BASE = base_path();
$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_dir_parts = explode('/', trim($current_path, '/'));
$active_page = end($current_dir_parts);
function is_active_footer($slug, $cur){ return $slug === $cur ? 'active' : ''; }
?>
<footer class="admin-footer">
  <p>&copy; <?= date('Y') ?> PawPals. All rights reserved.</p>
</footer>

<nav class="bottom-bar" aria-label="Mobile Navigation">
    <a href="<?= $BASE ?>dashboard/users" class="bb-item <?= is_active_footer('users', $active_page) ?>" title="Overview">
        <i class="fa-solid fa-house"></i><span>Overview</span>
    </a>
    <a href="<?= $BASE ?>dashboard/users/pets" class="bb-item <?= is_active_footer('pets', $active_page) ?>" title="My Pets">
        <i class="fa-solid fa-dog"></i><span>Pets</span>
    </a>
    <a href="<?= $BASE ?>dashboard/users/appointments" class="bb-item <?= is_active_footer('appointments', $active_page) ?>" title="Appointments">
        <i class="fa-solid fa-calendar-check"></i><span>Appts</span>
    </a>
    <button class="bb-item" id="userMoreTrigger" title="More">
        <i class="fa-solid fa-ellipsis-h"></i><span>More</span>
    </button>
</nav>

<div id="userDrawerBackdrop" class="backdrop" hidden></div>

<div id="userMoreDrawer" class="mobile-drawer" role="dialog" aria-modal="true" aria-labelledby="userMoreDrawerTitle">
    <div class="drawer-header">
        <h4 id="userMoreDrawerTitle">More Options</h4>
        <button id="closeUserMoreDrawer" class="modal-close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="drawer-content">
        <a href="<?= $BASE ?>dashboard/users/records" class="drawer-link <?= is_active_footer('records', $active_page) ?>">
            <i class="fa-solid fa-file-medical"></i><span>Health Summary</span>
        </a>
        <a href="<?= $BASE ?>dashboard/users/documents" class="drawer-link <?= is_active_footer('documents', $active_page) ?>">
            <i class="fa-solid fa-folder-open"></i><span>Pet Documents</span>
        </a>
        <a href="<?= $BASE ?>dashboard/users/notifications" class="drawer-link <?= is_active_footer('notifications', $active_page) ?>">
            <i class="fa-solid fa-bullhorn"></i><span>Announcements</span>
        </a>
        <a href="<?= $BASE ?>dashboard/ratings" class="drawer-link <?= is_active_footer('ratings', $active_page) ?>">
            <i class="fa-solid fa-star-half-stroke"></i><span>Give Feedback</span>
        </a>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Script para sa User More Drawer
        const userDrawerBackdrop = document.getElementById('userDrawerBackdrop');
        const userMoreTrigger = document.getElementById('userMoreTrigger');
        const userMoreDrawer = document.getElementById('userMoreDrawer');
        const closeUserMoreDrawer = document.getElementById('closeUserMoreDrawer');

        function openDrawer(drawerEl) {
            if (!drawerEl) return;
            userDrawerBackdrop?.removeAttribute('hidden');
            userDrawerBackdrop?.classList.add('show');
            drawerEl.classList.add('show');
            document.body.classList.add('no-scroll');
        }

        function closeAllDrawers() {
            userDrawerBackdrop?.classList.remove('show');
            document.querySelectorAll('.mobile-drawer').forEach(d => d.classList.remove('show'));
            document.body.classList.remove('no-scroll');
            setTimeout(() => userDrawerBackdrop?.setAttribute('hidden', ''), 300);
        }

        userMoreTrigger?.addEventListener('click', () => openDrawer(userMoreDrawer));
        closeUserMoreDrawer?.addEventListener('click', closeAllDrawers);
        userDrawerBackdrop?.addEventListener('click', closeAllDrawers);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllDrawers();
        });
    });
</script>