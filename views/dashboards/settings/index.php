<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../app/models/Settings.php';
require_once __DIR__ . '/../../../app/models/SocialLink.php';
require_once __DIR__ . '/../../../app/models/Profile.php';
require_login(['admin']);

$user = $_SESSION['user'];
$user_id = (int)$user['id'];
$BASE = base_path();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_data = $_POST;
    $form_name = '';
    if (isset($post_data['save_branding_settings'])) $form_name = 'branding';
    if (isset($post_data['save_contact_settings']))  $form_name = 'contact';

    if ($form_name) {
        $save_ok = true;
        if ($form_name === 'branding' && isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
            if (!Settings::handleImageUpload('hero_image_path', $_FILES['hero_image'], $user_id)) {
                $save_ok = false;
                $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Failed to upload hero image (must be JPG/PNG/WEBP ≤ 4MB).'];
            }
        }
        if ($save_ok) {
            if (Settings::save($post_data, $user_id)) $_SESSION['flash_message']=['type'=>'success','message'=>'Settings saved successfully!'];
            else $_SESSION['flash_message']=['type'=>'error','message'=>'Failed to save settings to the database.'];
        }
    }
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if (isset($_SESSION['flash_message'])) { $flash_message = $_SESSION['flash_message']; unset($_SESSION['flash_message']); }
$settings = Settings::getAll();
$social_links = SocialLink::getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>General Settings - PawPals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
    <link rel="stylesheet" href="<?= $BASE ?>assets/css/settings.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php require_once __DIR__ . '/../../partials/sidebar.php'; ?>
    <div id="drawerBackdrop" class="backdrop" hidden></div>
    <?php require_once __DIR__ . '/../../partials/topbar.php'; ?>

    <main class="content">
        <div class="settings-container">
            <h1>General Settings</h1>
            <p>Manage the main branding, contact information, and social media links for your website.</p>

            <div class="settings-layout">
                <!-- BRANDING -->
                <form id="brandingForm" method="POST" enctype="multipart/form-data" class="panel-card">
                    <div class="panel-head"><h4>Site Branding & Hero Section</h4></div>
                    <div class="panel-body">
                        <div class="form-grid grid-col-2">
                            <div class="form-group"><label for="clinic_name">Clinic / Brand Name</label><input type="text" id="clinic_name" name="clinic_name" value="<?= htmlspecialchars($settings['clinic_name'] ?? '') ?>" autocomplete="organization"></div>
                            <div class="form-group"><label for="hero_title">Hero Title</label><input type="text" id="hero_title" name="hero_title" value="<?= htmlspecialchars($settings['hero_title'] ?? '') ?>"></div>
                        </div>
                        <div class="form-grid grid-col-2">
                            <div class="form-group"><label for="hero_subtitle">Hero Subtitle / Description</label><textarea name="hero_subtitle" id="hero_subtitle" rows="3"><?= htmlspecialchars($settings['hero_subtitle'] ?? '') ?></textarea></div>
                            <div class="form-group">
                                <label>Hero Image <small class="muted">(JPG/PNG/WEBP, max 4MB)</small></label>
                                <div class="mb-2">
                                    <?php
                                      $heroPath = $settings['hero_image_path'] ?? 'assets/images/veterinarian_2.jpg';
                                      $heroSrc = (str_starts_with($heroPath, 'http') || str_starts_with($heroPath, '/')) ? $heroPath : $BASE . $heroPath;
                                    ?>
                                    <img src="<?= htmlspecialchars($heroSrc) ?>" alt="Current Hero Image" class="hero-preview">
                                </div>
                                <div class="file-upload-wrapper">
                                    <input type="file" name="hero_image" id="hero_image" class="file-upload-input" accept="image/png, image/jpeg, image/webp">
                                    <label for="hero_image" class="file-upload-button has-tooltip" data-tooltip="Choose an image from your device"><i class="fa-solid fa-upload"></i> Choose Image</label>
                                    <span id="heroFileName" class="file-upload-filename">No file chosen</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions"><button id="cancelBrandingBtn" type="button" class="btn btn-secondary" hidden>Cancel</button><button id="saveBrandingBtn" type="submit" name="save_branding_settings" class="btn btn-primary" disabled>Save</button></div>
                </form>

                <!-- CONTACT -->
                <form id="contactForm" method="POST" class="panel-card">
                    <div class="panel-head"><h4>Contact Details & Footer</h4></div>
                    <div class="panel-body">
                        <div class="form-grid grid-col-3">
                            <div class="form-group"><label for="contact_email">System & Contact Email</label><input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" autocomplete="email"></div>
                            <div class="form-group"><label for="contact_phone">Contact Phone</label><input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars(Profile::formatPhoneNumber($settings['contact_phone'] ?? '')) ?>" placeholder="09XX XXX XXXX" maxlength="13" inputmode="numeric" autocomplete="tel"></div>
                            <div class="form-group"><label for="footer_tagline">Footer Tagline</label><textarea name="footer_tagline" id="footer_tagline" rows="1"><?= htmlspecialchars($settings['footer_tagline'] ?? '') ?></textarea></div>
                        </div>
                        <hr class="form-divider">
                        <div class="form-grid grid-col-3">
                            <div class="form-group"><label for="contact_houseno">House/Lot/Bldg No.</label><input type="text" id="contact_houseno" name="contact_houseno" value="<?= htmlspecialchars($settings['contact_houseno'] ?? '') ?>"></div>
                            <div class="form-group"><label for="contact_street">Street</label><input type="text" id="contact_street" name="contact_street" value="<?= htmlspecialchars($settings['contact_street'] ?? '') ?>"></div>
                            <div class="form-group"><label for="contact_barangay">Barangay</label><input type="text" id="contact_barangay" name="contact_barangay" value="<?= htmlspecialchars($settings['contact_barangay'] ?? '') ?>"></div>
                        </div>
                        <div class="form-grid grid-col-3">
                            <div class="form-group"><label for="contact_municipality">Municipality/City</label><input type="text" id="contact_municipality" name="contact_municipality" value="<?= htmlspecialchars($settings['contact_municipality'] ?? '') ?>"></div>
                            <div class="form-group"><label for="contact_province">Province</label><input type="text" id="contact_province" name="contact_province" value="<?= htmlspecialchars($settings['contact_province'] ?? '') ?>"></div>
                            <div class="form-group"><label for="contact_zipcode">ZIP Code</label><input type="text" id="contact_zipcode" name="contact_zipcode" value="<?= htmlspecialchars($settings['contact_zipcode'] ?? '') ?>" maxlength="5" inputmode="numeric"></div>
                        </div>
                    </div>
                    <div class="form-actions"><button id="cancelContactBtn" type="button" class="btn btn-secondary" hidden>Cancel</button><button id="saveContactBtn" type="submit" name="save_contact_settings" class="btn btn-primary" disabled>Save</button></div>
                </form>

                <!-- SOCIAL MEDIA LINKS -->
                <div class="panel-card">
                    <div class="panel-head">
                        <h4>Social Media Links</h4>
                        <button id="addSocialLinkBtn" class="btn btn-primary btn-icon-only has-tooltip" data-tooltip="Add New" aria-label="Add New"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <div class="panel-body">

                        <!-- MOBILE controls + slider -->
                        <div class="d-md-none social-mobile">
                            <div class="social-mobile-controls">
                                <div class="mobile-search-wrapper">
                                    <i class="fa-solid fa-search"></i>
                                    <input type="text" id="mobileSocialSearch" class="form-control" placeholder="Search social links...">
                                </div>
                                <button id="addSocialLinkBtnMobile" class="btn btn-primary btn-icon-only" title="Add">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>

                            <div id="socialMobileContainer" class="social-slider">
                                <?php if (empty($social_links)): ?>
                                    <p class="text-center text-muted mt-2 mb-0">No social links yet.</p>
                                <?php else: ?>
                                    <?php foreach ($social_links as $link): ?>
                                        <div class="social-card"
                                            data-id="<?= (int)$link['id'] ?>"
                                            data-platform="<?= htmlspecialchars($link['platform']) ?>"
                                            data-icon="<?= htmlspecialchars($link['icon_class']) ?>"
                                            data-url="<?= htmlspecialchars($link['url']) ?>"
                                            data-order="<?= isset($link['display_order']) ? (int)$link['display_order'] : 0 ?>"
                                        >
                                            <div class="social-card-head">
                                                <i class="<?= htmlspecialchars($link['icon_class']) ?>"></i>
                                                <div class="info">
                                                    <h5 class="title"><?= htmlspecialchars($link['platform']) ?></h5>
                                                    <div class="url"><?= htmlspecialchars($link['url']) ?></div>
                                                </div>
                                            </div>
                                            <div class="social-card-meta">
                                                <span class="badge order">Order: <?= isset($link['display_order']) ? (int)$link['display_order'] : 0 ?></span>
                                            </div>
                                            <div class="social-card-actions">
                                                <button class="btn-icon edit-social" title="Edit"><i class="fa-solid fa-pencil"></i></button>
                                                <button class="btn-icon delete-social" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                <a class="btn-icon" href="<?= htmlspecialchars($link['url']) ?>" target="_blank" rel="noopener" title="Open"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- DESKTOP table -->
                        <div class="social-table-desktop d-none d-md-block">
                            <div class="table-responsive">
                                <table id="socialLinksTable" class="table table-hover data-table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="d-none">Order</th>
                                            <th>Icon</th>
                                            <th>Platform</th>
                                            <th>URL</th>
                                            <th class="no-sort text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($social_links as $link): ?>
                                            <tr data-id="<?= (int)$link['id'] ?>" data-platform="<?= htmlspecialchars($link['platform']) ?>" data-icon="<?= htmlspecialchars($link['icon_class']) ?>" data-url="<?= htmlspecialchars($link['url']) ?>" data-order="<?= isset($link['display_order']) ? (int)$link['display_order'] : 0 ?>">
                                                <td class="d-none"><?= isset($link['display_order']) ? (int)$link['display_order'] : 0 ?></td>
                                                <td class="icon-cell"><i class="<?= htmlspecialchars($link['icon_class']) ?>"></i></td>
                                                <td><?= htmlspecialchars($link['platform']) ?></td>
                                                <td class="url-cell"><?= htmlspecialchars($link['url']) ?></td>
                                                <td class="actions-cell text-end">
                                                    <button class="btn-icon has-tooltip edit-link" data-tooltip="Edit" aria-label="Edit"><i class="fa-solid fa-pencil"></i></button>
                                                    <button class="btn-icon has-tooltip delete-link" data-tooltip="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="muted">* Sorted by <strong>Order</strong> (ascending), then Platform.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- SOCIAL LINK MODAL -->
    <div id="socialLinkModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="socialLinkModalTitle" hidden>
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="socialLinkModalTitle">Add Social Link</h3>
                <button id="closeSocialLinkModal" class="modal-close-btn" aria-label="Close">&times;</button>
            </div>
            <form id="socialLinkForm">
                <div class="modal-body">
                    <input type="hidden" id="socialLinkId" name="id">

                    <div class="form-group">
                        <label for="socialPlatform">Platform Name</label>
                        <select id="socialPlatform" name="platform" required>
                            <option value="" disabled selected>Select a platform...</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Twitter/X">Twitter/X</option>
                            <option value="YouTube">YouTube</option>
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="TikTok">TikTok</option>
                            <option value="Pinterest">Pinterest</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Viber">Viber</option>
                            <option value="Website">Website</option>
                            <option value="Other">Other (Specify icon)</option>
                        </select>
                    </div>

                    <div class="form-group"><label for="socialIconClass">Font Awesome Icon Class</label><input type="text" id="socialIconClass" name="icon_class" placeholder="e.g., fa-brands fa-facebook" required></div>
                    <div class="form-group"><label for="socialUrl">URL</label><input type="url" id="socialUrl" name="url" placeholder="https://..." required></div>
                    <div class="form-group"><label for="displayOrder">Display Order</label><input type="number" id="displayOrder" name="display_order" min="0" step="1" value="0"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancelSocialLinkModal" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Link</button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../partials/footer.php'; ?>
    <div id="modalBackdrop" class="backdrop" hidden></div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
    <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
    <script src="<?= $BASE ?>assets/js/settings.js"></script>
    <?php if (isset($flash_message)): ?>
    <script>
        const Toast = Swal.mixin({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true});
        Toast.fire({icon:'<?= $flash_message['type'] ?>', title:'<?= addslashes($flash_message['message']) ?>'});
    </script>
    <?php endif; ?>
</body>
</html>
