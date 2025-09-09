<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['user']);
$BASE = base_path();
$user_sidebar_partial = __DIR__ . '/../../partials/sidebar-user.php';
$user_footer_partial = __DIR__ . '/../../partials/footer-user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>My Pet Documents</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/user-pet-documents.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php require_once $user_sidebar_partial; ?>
<?php require_once __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content">
    <div class="page-head">
        <h1 class="page-title">My Pet Documents</h1>
        <p class="page-subtitle">View and download documents for each of your pets.</p>
    </div>

    <div class="pets-documents-container" id="pets-documents-container">
        <div class="loading-message">
            <i class="fa-solid fa-spinner fa-spin"></i> Loading your pet documents...
        </div>
    </div>
</main>

<?php require_once $user_footer_partial; ?>
<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
<script src="<?= $BASE ?>assets/js/user-pet-documents.js"></script> 
</body>
</html>