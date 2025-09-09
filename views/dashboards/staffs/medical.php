<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
$BASE = base_path();
$V = 'v=20250909-final-pro-updated'; // The Final Pro Version
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Medical Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css?<?= $V ?>">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/staff-medical.css?<?= $V ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
<?php require_once __DIR__ . '/../../partials/sidebar-staff.php'; ?>

<div class="content-wrapper">
    <?php require_once __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="content medical-page">
        <div class="page-head"><h1>Medical Records</h1></div>
        <div class="two-cols">
            <section class="col-left card">
                <div class="search-container">
                    <div class="form-group">
                        <label for="petSearch">Search Pet / Owner</label>
                        <div class="search-box">
                            <input type="text" id="petSearch" placeholder="Type name, owner, username, breed...">
                            <i class="fa-solid fa-search search-icon"></i>
                        </div>
                    </div>
                </div>
                <div id="searchListWrap" class="list-wrap" hidden>
                    <div class="list-head"><h4>Search Results</h4></div>
                    <div id="searchList" class="pet-list"></div>
                </div>
                <div id="todayWrap" class="list-wrap">
                    <div class="list-head"><h4>Today</h4></div>
                    <div id="listToday" class="pet-list"><p class="muted">Loading...</p></div>
                </div>
                <div id="upcomingWrap" class="list-wrap">
                    <div class="list-head foldable"><h4>Upcoming</h4><i class="fa-solid fa-chevron-down list-fold"></i></div>
                    <div id="listUpcoming" class="pet-list"><p class="muted">Loading...</p></div>
                </div>
                <div id="historyWrap" class="list-wrap">
                    <div class="list-head foldable"><h4>History (My Completed Cases)</h4><i class="fa-solid fa-chevron-down list-fold"></i></div>
                    <div id="listHistory" class="pet-list"><p class="muted">Loading...</p></div>
                </div>
            </section>

            <section class="col-right">
                <div class="record-view" hidden>
                    <!-- Pet Info Card -->
                    <div class="card pet-details-card">
                        <div id="petHeader"></div>
                        <div id="petHeaderDetails"></div>
                    </div>

                    <!-- Tabs & Records Card -->
                    <div class="card medical-tabs-card">
                        <div class="tabs">
                            <button class="tab-link active" data-tab="tab-soap">S.O.A.P.</button>
                            <button class="tab-link" data-tab="tab-vaccination">Vaccination</button>
                            <button class="tab-link" data-tab="tab-deworming">Deworming</button>
                            <button class="tab-link" data-tab="tab-prevention">Prevention</button>
                            <button class="tab-link" data-tab="tab-medication">Medications</button>
                            <button class="tab-link" data-tab="tab-allergy">Allergies</button>
                        </div>
                        <div class="tab-content-wrapper">
                            <div id="tab-soap" class="tab-content active"></div>
                            <div id="tab-vaccination" class="tab-content"></div>
                            <div id="tab-deworming" class="tab-content"></div>
                            <div id="tab-prevention" class="tab-content"></div>
                            <div id="tab-medication" class="tab-content"></div>
                            <div id="tab-allergy" class="tab-content"></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<div id="recordModal" class="modal" hidden>
    <div class="modal-content">
        <div class="modal-header"><h3 id="modalTitle">Add Record</h3></div>
        <form id="recordForm" novalidate>
            <div class="modal-body" id="formFields"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveModalBtn">Save Record</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer-staff.php'; ?>
<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/staff-medical.js?<?= $V ?>"></script>
<script src="<?= $BASE ?>assets/js/dashboard.js?<?= $V ?>"></script>
</body>
</html>
