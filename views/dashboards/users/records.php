<?php
// views/dashboards/users/records.php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['user']);
$BASE = base_path();
$V = 'v=' . date('YmdHis'); // Versioning para sa CSS/JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Medical Records Summary</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css?<?= $V ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/user-records.css?<?= $V ?>">
</head>
<body>
    <?php require_once __DIR__ . '/../../partials/sidebar-user.php'; ?>
    <div class="page-wrapper">
        <?php require_once __DIR__ . '/../../partials/topbar.php'; ?>
        <main class="content">
            
            <!-- START OF FIX: Idinagdag ang content-container div -->
            <div class="content-container">
                <div class="page-head">
                    <h1>Health Summary</h1>
                </div>

                <!-- Upcoming Reminders Section -->
                <section id="upcomingReminders" class="records-section">
                    <h2><i class="fa-solid fa-bell icon"></i> Upcoming Reminders</h2>
                    <div id="remindersList" class="card-list">
                        <!-- Loading or content here -->
                    </div>
                </section>

                <!-- Recent Health Records Section -->
                <section id="recentRecords" class="records-section">
                    <h2><i class="fa-solid fa-notes-medical icon"></i> Recent Health Records</h2>
                    <div id="recentList" class="card-list">
                        <!-- Loading or content here -->
                    </div>
                </section>
            </div>
            <!-- END OF FIX -->

        </main>
        <?php require_once __DIR__ . '/../../partials/footer-user.php'; ?>
    </div>

    <script>
        const App = { 
            BASE_URL: '<?= $BASE ?>',
            APPOINTMENTS_URL: '<?= $BASE ?>dashboard/users/appointments' // URL para sa redirect
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?= $BASE ?>assets/js/dashboard.js?<?= $V ?>"></script>
    <script src="<?= $BASE ?>assets/js/user-records.js?<?= $V ?>"></script>
</body>
</html>