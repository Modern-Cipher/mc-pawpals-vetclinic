<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../app/models/Profile.php';
require_login();

$user_id = $_SESSION['user']['id'];
$BASE    = base_path();

// Role-aware partials (defines $sidebar_partial, $footer_partial)
require_once __DIR__ . '/../../partials/role-partials.php';

// Handle POST (profile update + avatar upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $result = Profile::update($user_id, $_POST);

  $file_uploaded = false;
  if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $new_avatar_path = Profile::handleAvatarUpload($user_id, $_FILES['avatar']);
    if ($new_avatar_path) {
      $file_uploaded = true;
    } else {
      $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Profile saved, but avatar upload failed.'];
    }
  }

  if ($result['success']) {
    if ($result['changes_made'] || $file_uploaded) {
      $latest_profile = Profile::getByUserId($user_id);
      if ($latest_profile) {
        $_SESSION['user']['name']        = Profile::formatFullName($latest_profile);
        $_SESSION['user']['avatar']      = $BASE . ($latest_profile['avatar_path'] ?? 'assets/images/person1.jpg');
        $_SESSION['user']['designation'] = $latest_profile['designation'] ?? null;
      }
      $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profile updated successfully!'];
    } else {
      $_SESSION['flash_message'] = ['type' => 'info', 'message' => 'No changes were made.'];
    }
  } else {
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => $result['message']];
  }

  header('Location: ' . $_SERVER['REQUEST_URI']);
  exit;
}

// Flash message (one-time)
if (isset($_SESSION['flash_message'])) {
  $flash_message = $_SESSION['flash_message'];
  unset($_SESSION['flash_message']);
}

// Load profile
$profile = Profile::getByUserId($user_id);
if (!$profile) { die('Error: Could not load user profile.'); }

$display_name = Profile::formatFullName($profile);
$user         = $_SESSION['user'];

// Username cooldown
$username_cooldown_days = 0;
$is_username_disabled   = false;
if (!empty($profile['username_last_changed_at'])) {
  $last_change = new DateTime($profile['username_last_changed_at']);
  $now         = new DateTime();
  $diff        = $now->diff($last_change);
  if ($diff->days < 30) {
    $username_cooldown_days = 30 - $diff->days;
    $is_username_disabled   = true;
  }
}

// Helpers / options
function format_address($p) {
  $parts = [$p['address_line1'], $p['address_street'], $p['address_city'], $p['address_province'], $p['address_zip']];
  return implode(', ', array_filter($parts));
}
$full_address = format_address($profile);
$suffixes     = ['Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>My Profile - PawPals</title>

  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/profile.css">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- small hint styles (for username availability messages) -->
  <style>
    .hint{font-size:.85rem;color:var(--text-medium)}
    .ok-hint{color:#16a34a}
    .err-hint{color:#e74c3c}
    .field-error{border-color:#e74c3c!important; box-shadow:0 0 0 3px rgba(231,76,60,.15)!important;}
  </style>

</head>
<body>

  <?php require $sidebar_partial; ?>
  <?php require __DIR__ . '/../../partials/topbar.php'; ?>

  <main class="content">
    <h1>My Profile</h1>
    <p>Manage your personal information and account settings.</p>

    <?php if (isset($flash_message)): ?>
      <script>
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: t => {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
          }
        });
        Toast.fire({
          icon: '<?= $flash_message['type'] ?>',
          title: '<?= addslashes($flash_message['message']) ?>'
        });
      </script>
    <?php endif; ?>

    <div class="profile-layout">
      <!-- LEFT: Summary -->
      <div class="panel-card profile-summary-card">
        <div class="panel-body">
          <img
            src="<?= $BASE . htmlspecialchars($profile['avatar_path'] ?? 'assets/images/person1.jpg') ?>"
            alt="Avatar"
            class="profile-avatar-large"
          >
          <h3 class="profile-name"><?= htmlspecialchars($display_name) ?></h3>
          <p class="profile-designation"><?= htmlspecialchars($profile['designation'] ?? 'N/A') ?></p>

          <hr class="profile-divider">

          <div class="profile-contact-info">
            <p><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($profile['email']) ?></p>
            <p><i class="fa-solid fa-phone"></i> <?= htmlspecialchars(Profile::formatPhoneNumber($profile['phone'] ?? 'Not available')) ?></p>
            <p><i class="fa-solid fa-map-marker-alt"></i> <?= htmlspecialchars($full_address ?: 'No address provided') ?></p>
          </div>

          <button id="changePasswordBtn" class="btn btn-secondary">
            <i class="fa-solid fa-key"></i> Change Password
          </button>
        </div>
      </div>

      <!-- RIGHT: Edit form -->
      <div class="panel-card profile-details-card">
        <div class="panel-head"><h4>Edit Information</h4></div>
        <div class="panel-body">
          <form id="profileForm" method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="form-group">
              <label>Change Profile Picture</label>
              <div class="file-upload-wrapper">
                <input type="file" name="avatar" id="avatar" class="file-upload-input" accept="image/png, image/jpeg">
                <label for="avatar" class="file-upload-button"><i class="fa-solid fa-upload"></i> Choose File</label>
                <span id="fileName" class="file-upload-filename">No file chosen</span>
              </div>
            </div>

            <div class="form-group">
              <label for="username">Username</label>
              <input
                type="text"
                id="username"
                name="username"
                value="<?= htmlspecialchars($profile['username']) ?>"
                <?= $is_username_disabled ? 'disabled' : '' ?>
              >
              <?php if ($is_username_disabled): ?>
                <small class="hint">You can change your username again in <?= $username_cooldown_days ?> day(s).</small>
              <?php else: ?>
                <small id="usernameHint" class="hint"></small>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="email">Email</label>
              <input type="text" id="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" disabled>
            </div>

            <hr class="form-divider">

            <div class="form-grid">
              <div class="form-group">
                <label for="prefix">Prefix</label>
                <input type="text" id="prefix" name="prefix" value="<?= htmlspecialchars($profile['prefix'] ?? '') ?>" placeholder="e.g., Dr.">
              </div>
              <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($profile['first_name']) ?>">
              </div>
              <div class="form-group">
                <label for="middle_name">Middle Name</label>
                <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($profile['middle_name'] ?? '') ?>">
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($profile['last_name']) ?>">
              </div>
              <div class="form-group">
                <label for="suffix">Suffix</label>
                <select name="suffix" id="suffix">
                  <option value="">None</option>
                  <?php foreach ($suffixes as $suffix): ?>
                    <option value="<?= $suffix ?>" <?= (($profile['suffix'] ?? '') === $suffix) ? 'selected' : '' ?>>
                      <?= $suffix ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input
                type="tel"
                id="phone"
                name="phone"
                value="<?= htmlspecialchars(Profile::formatPhoneNumber($profile['phone'] ?? '')) ?>"
                placeholder="09XX XXX XXXX"
                maxlength="13"
                inputmode="numeric"
              >
            </div>

            <div class="form-group">
              <label for="designation">Designation</label>
              <input type="text" id="designation" name="designation" value="<?= htmlspecialchars($profile['designation'] ?? '') ?>" placeholder="e.g., Clinic Administrator">
            </div>

            <hr class="form-divider">

            <div class="form-group">
              <label for="address_line1">House/Lot/Bldg No.</label>
              <input type="text" id="address_line1" name="address_line1" value="<?= htmlspecialchars($profile['address_line1'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="address_street">Street Address</label>
              <input type="text" id="address_street" name="address_street" value="<?= htmlspecialchars($profile['address_street'] ?? '') ?>">
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="address_city">City</label>
                <input type="text" id="address_city" name="address_city" value="<?= htmlspecialchars($profile['address_city'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label for="address_province">Province</label>
                <input type="text" id="address_province" name="address_province" value="<?= htmlspecialchars($profile['address_province'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label for="address_zip">ZIP Code</label>
                <input type="text" id="address_zip" name="address_zip" value="<?= htmlspecialchars($profile['address_zip'] ?? '') ?>" maxlength="5" inputmode="numeric">
              </div>
            </div>

            <div class="form-actions">
              <button id="cancelChangesBtn" type="button" class="btn btn-secondary" hidden>Cancel</button>
              <button id="saveChangesBtn" type="submit" class="btn btn-primary" disabled>Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <?php require $footer_partial; ?>

  <!-- Change Password Modal -->
  <div id="passwordModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>Change Password</h3>
        <button id="closeModalBtn" class="modal-close-btn">&times;</button>
      </div>
      <div class="modal-body">
        <form id="passwordChangeForm">
          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
          </div>
          <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>
          <div class="modal-footer">
            <button type="button" id="cancelModalBtn" class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Backdrop used by the password modal -->
  <div id="modalBackdrop" class="backdrop" hidden></div>

  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
  <script src="<?= $BASE ?>assets/js/profile.js"></script>
</body>
</html>
