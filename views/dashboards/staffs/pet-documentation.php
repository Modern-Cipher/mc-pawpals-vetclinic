<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['staff']);
$BASE = base_path();
require_once __DIR__ . '/../../partials/role-partials.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Pet Document Management</title>
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/pet-documentation.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php require $sidebar_partial; ?>
<?php require __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content">
    <div class="page-head">
        <h1 class="page-title">Pet Document Management</h1>
    </div>

    <div class="docs-management-layout">
        
        <div class="left-column">
            <div class="main-search-container">
                <input type="search" id="pet-search" class="form-control" placeholder="Search pets by name or owner...">
            </div>
            <div class="left-panel-card">
                <div class="pet-list-scroll-area">
                    <ul id="pet-list-sidebar" class="pet-list-sidebar">
                        <li class="loading-item">Loading pets...</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="right-panel-card">
                <div class="right-panel-content-wrapper">
                    <div id="pet-info-header" class="pet-info-header">
                        <img id="pet-photo" class="pet-photo" src="" alt="Pet Photo">
                        <h3 id="pet-name"></h3>
                    </div>
                
                    <div id="document-vault-section" class="docs-vault-container">
                        <div id="docs-vault" class="docs-vault">
                            </div>
                    </div>

                    <div id="upload-form-section">
                        <form id="pet-docs-form" enctype="multipart/form-data">
                            <input type="hidden" id="pet-id-for-upload" name="pet_id">
                            
                            <div id="drop-zone" class="drop-zone">
                                <div class="drop-zone-prompt">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <p>Drag & Drop files here or click to select</p>
                                    <small>Max file size: 20MB</small>
                                </div>
                                <input type="file" id="file-input-hidden" multiple style="display: none;">
                            </div>

                            <div id="docs-upload-list">
                                </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary" id="upload-btn" disabled>
                                    <i class="fa-solid fa-upload"></i> Upload Files
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . '/../../partials/footer.php'; ?>

<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
<script src="<?= $BASE ?>assets/js/staff-pet-documentation.js"></script>
</body>
</html>