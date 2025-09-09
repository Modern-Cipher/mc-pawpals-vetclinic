<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['admin']);
require_once __DIR__ . '/../../../config/connection.php';

$BASE = base_path();
$pdo  = db();
$counts = ['pending'=>0,'approved'=>0,'archived'=>0];
try {
  $q = $pdo->query("SELECT status, COUNT(*) c FROM feedbacks GROUP BY status");
  foreach ($q->fetchAll() as $r) $counts[$r['status']] = (int)$r['c'];
} catch(Throwable $e){}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ratings & Feedback</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/ratings.css">
</head>
<body>
<?php require __DIR__.'/../../partials/sidebar.php'; ?>
<div id="drawerBackdrop" class="backdrop" hidden></div>
<?php require __DIR__.'/../../partials/topbar.php'; ?>

<main class="content">
  <div class="panel-card">
    <div class="panel-head">
      <h4 class="m-0">Ratings & Feedback</h4>
      <div class="d-flex align-items-center gap-2">
        <span id="badgePending"  class="badge bg-warning text-dark">Pending: <?= $counts['pending'] ?></span>
        <span id="badgeApproved" class="badge bg-success">Approved: <?= $counts['approved'] ?></span>
        <span id="badgeArchived" class="badge bg-secondary">Archived: <?= $counts['archived'] ?></span>
      </div>
    </div>

    <div class="panel-body">
      <div class="table-responsive d-none d-md-block">
        <table id="ratingsTable" class="table table-hover align-middle" style="width:100%">
          <thead>
            <tr>
              <th style="width:100px">Rating</th>
              <th style="min-width:260px">Name / Email</th>
              <th>Message</th>
              <th>Status</th>
              <th>Created</th>
              <th class="text-end no-sort" style="width:150px">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <!-- Mobile -->
      <div class="d-md-none">
        <div class="ratings-mobile-search">
          <input id="mobileSearch" class="form-control" placeholder="Search feedback..." />
        </div>
        <div id="ratingsMobile" class="ratings-mobile" hidden></div>
      </div>

      <small class="muted d-block mt-2">Approve to show on the public testimonials. Archive to hide.</small>
    </div>
  </div>
</main>

<?php require __DIR__.'/../../partials/footer.php'; ?>
<div id="modalBackdrop" class="backdrop" hidden></div>

<script>window.APP_BASE='<?= $BASE ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= $BASE ?>assets/js/ratings.js"></script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
</body>
</html>
