<?php
// views/partials/footer-staff.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../middleware/auth.php';

$BASE = base_path();

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

$has_more_items = $show_pets || $show_announcements || $show_documents || $show_petcare || $show_schedule;

$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$active_page = basename($current_path);
?>
<footer class="admin-footer">
    <p>&copy; <?= date('Y') ?> PawPals. All rights reserved.</p>
</footer>

<nav class="bottom-bar" aria-label="Mobile Navigation">
    <a href="<?= $BASE ?>dashboard/staffs" class="bb-item" title="Overview">
        <i class="fa-solid fa-gauge-simple-high"></i><span>Overview</span>
    </a>

    <?php if ($show_appointments): ?>
    <a href="<?= $BASE ?>dashboard/staffs/appointments" class="bb-item" title="Appointments">
        <i class="fa-solid fa-calendar-check"></i><span>Appts</span>
    </a>
    <?php endif; ?>
    
    <?php if ($show_records): ?>
    <a href="<?= $BASE ?>dashboard/staffs/medical" class="bb-item" title="Medical Records">
        <i class="fa-solid fa-notes-medical"></i><span>Records</span>
    </a>
    <?php endif; ?>

    <?php if ($has_more_items): ?>
    <button class="bb-item" id="moreTrigger" title="More">
        <i class="fa-solid fa-ellipsis-h"></i><span>More</span>
    </button>
    <?php endif; ?>
</nav>

<div id="staffDrawerBackdrop" class="backdrop" hidden></div>

<div id="staffMoreDrawer" class="mobile-drawer" role="dialog" aria-modal="true" aria-labelledby="staffMoreDrawerTitle">
    <div class="drawer-header">
        <h4 id="staffMoreDrawerTitle">More Actions</h4>
        <button id="closeStaffMoreDrawer" class="modal-close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="drawer-content">
        <?php if ($show_pets): ?>
        <a href="<?= $BASE ?>dashboard/staffs/pets" class="drawer-link">
            <i class="fa-solid fa-dog"></i><span>Pets</span>
        </a>
        <?php endif; ?>
        <?php if ($show_announcements): ?>
        <a href="<?= $BASE ?>dashboard/staffs/announcements" class="drawer-link">
            <i class="fa-solid fa-bullhorn"></i><span>Announcements</span>
        </a>
        <?php endif; ?>
        <?php if ($show_documents): ?>
        <a href="<?= $BASE ?>dashboard/staffs/pet-documentation" class="drawer-link">
            <i class="fa-regular fa-folder-open"></i><span>Documents</span>
        </a>
        <?php endif; ?>
        <?php if ($show_petcare): ?>
        <a href="<?= $BASE ?>dashboard/staffs/petcare" class="drawer-link">
            <i class="fa-solid fa-book-medical"></i><span>Pet Care Tips</span>
        </a>
        <?php endif; ?>
        <?php if ($show_schedule): ?>
        <a href="<?= $BASE ?>dashboard/staffs/my-schedule" class="drawer-link">
            <i class="fa-solid fa-calendar-alt"></i><span>My Schedule</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const drawerBackdrop = document.getElementById('staffDrawerBackdrop');
        const moreTrigger = document.getElementById('moreTrigger');
        const moreDrawer = document.getElementById('staffMoreDrawer');
        const closeMoreDrawer = document.getElementById('closeStaffMoreDrawer');

        function openDrawer(drawerEl) {
            if (!drawerEl) return;
            drawerBackdrop?.removeAttribute('hidden');
            drawerBackdrop?.classList.add('show');
            drawerEl.classList.add('show');
            document.body.classList.add('no-scroll');
        }

        function closeAllDrawers() {
            drawerBackdrop?.classList.remove('show');
            document.querySelectorAll('.mobile-drawer').forEach(d => d.classList.remove('show'));
            document.body.classList.remove('no-scroll');
            setTimeout(() => drawerBackdrop?.setAttribute('hidden', ''), 300);
        }

        moreTrigger?.addEventListener('click', () => openDrawer(moreDrawer));
        closeMoreDrawer?.addEventListener('click', closeAllDrawers);
        drawerBackdrop?.addEventListener('click', closeAllDrawers);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAllDrawers();
        });
    });
</script>