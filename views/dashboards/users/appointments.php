<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['user']);
$BASE = base_path();
$V = 'v=20250903j'; // cache-bust
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>My Appointments</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/appointments.css?<?= $V ?>">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php require_once __DIR__ . '/../../partials/sidebar-user.php'; ?>
<div class="content-wrapper">
    <?php require_once __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="content">
        <div class="page-head">
            <h1>My Appointments</h1>
        </div>

        <div class="appointment-layout">
            <aside class="side-panel">
                <h3>Upcoming</h3>
                <div id="upcomingList"><p class="muted">No upcoming appointments.</p></div>
                
                <div class="history-section">
                    <button type="button" class="history-header" id="historyToggle">
                        <h3>History</h3>
                        <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div id="historyList" hidden><p class="muted">No past appointments.</p></div>
                </div>
            </aside>

            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
    </main>
    <?php require_once __DIR__ . '/../../partials/footer-user.php'; // THIS LINE ADDS THE FOOTER BACK ?>
</div>

<div id="bookingModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="bookingModalTitle" hidden>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="bookingModalTitle">Book an Appointment</h3>
            <button id="closeBookingModal" class="modal-close-btn" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="bookingForm" novalidate>
                <input type="hidden" id="appointment_id" name="id" value="">
                <div class="form-group">
                    <label>For which pet?</label>
                    <input type="hidden" name="pet_id" id="pet_id">
                    <div id="petPicker" class="pet-picker" aria-expanded="false">
                        <button type="button" id="petTrigger" class="pet-trigger">
                            <span class="pet-thumb placeholder"></span>
                            <span class="pet-label">Select a pet</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <ul id="petMenu" class="pet-menu" role="listbox">
                            <li class="pet-empty">Loading...</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <label for="service">Service</label>
                    <select name="service" id="service" required>
                        <option value="">-- Select a service --</option>
                        <option value="Consultation">Consultation / Check-up</option>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Deworming">Deworming</option>
                        <option value="Grooming">Grooming</option>
                        <option value="Surgery">Surgery</option>
                        <option value="Other">Other</option>
                    </select>
                    </div>
                <div class="row-2">
                    <div class="form-group">
                        <label for="appointment_date">Date</label>
                        <input type="text" name="appointment_date" id="appointment_date" readonly placeholder="Select from calendar">
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Time</label>
                        <select name="appointment_time" id="appointment_time" required>
                            <option value="">-- Select a date first --</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Reason / Notes</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="e.g., My dog has been coughing..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancelBooking">Cancel</button>
            <button type="submit" form="bookingForm" class="btn btn-primary">Submit Request</button>
        </div>
    </div>
</div>

<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js?<?= $V ?>"></script>
<script src="<?= $BASE ?>assets/js/calendar-component.js?<?= $V ?>"></script>
<script src="<?= $BASE ?>assets/js/appointments.js?<?= $V ?>"></script>
</body>
</html>