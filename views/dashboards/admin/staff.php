<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['admin']);
$BASE = base_path();
require_once __DIR__ . '/../../partials/role-partials.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Staff Management</title>

<link rel="stylesheet" href="<?= $BASE ?>assets/css/dashboard-admin.css">
<link rel="stylesheet" href="<?= $BASE ?>assets/css/staff.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php require $sidebar_partial; ?>
<?php require __DIR__ . '/../../partials/topbar.php'; ?>

<main class="content content-staff">
  <div class="page-head">
    <h1 class="page-title">Staff</h1>
    <div class="page-tools desktop-only">
      <div class="searchbox">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="q" type="search" placeholder="Search staff by name, email, username…">
      </div>
      <button id="btnAdd" class="btn btn-primary">
        <i class="fa-solid fa-user-plus"></i><span class="hide-sm">&nbsp;Add Staff</span>
      </button>
    </div>
  </div>

  <div class="page-tools-mobile mobile-only">
    <div class="searchbox">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input id="q-mobile" type="search" placeholder="Search staff…">
    </div>
    <button id="btnAdd-mobile" class="btn btn-primary">
      <i class="fa-solid fa-user-plus"></i>
    </button>
  </div>

  <div class="table-wrap">
    <table class="table" id="tbl">
      <thead><tr>
        <th class="mincol">Photo</th>
        <th>Name</th>
        <th>Email / Username</th>
        <th>Designation</th>
        <th class="mincol">Active</th>
        <th class="mincol">Actions</th>
      </tr></thead>
      <tbody id="rows"><tr><td colspan="6">Loading…</td></tr></tbody>
    </table>
  </div>

  <div class="cards" id="cards"></div>
</main>

<?php require $footer_partial; ?>

<div id="staffModal" class="modal" role="dialog" aria-modal="true" hidden>
  <div class="modal-content show">
    <div class="modal-header">
      <h3 id="modalTitle">Add Staff</h3>
      <button id="closeStaffModal" class="modal-close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <form id="staffForm" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="user_id" id="user_id">

        <div class="row-2">
          <div class="form-group">
            <label>First name*</label>
            <input name="first_name" id="first_name" required>
          </div>
          <div class="form-group">
            <label>Last name*</label>
            <input name="last_name" id="last_name" required>
          </div>
        </div>

        <div class="row-2">
          <div class="form-group">
            <label>Email*</label>
            <input name="email" id="email" type="email" required>
            <div class="hint">We’ll email the temporary password here.</div>
          </div>
          <div class="form-group">
            <label>Username* <span class="muted">(4–20 chars, letters &amp; numbers, must mix)</span></label>
            <input name="username" id="username" required inputmode="latin" pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z0-9]{4,20}">
          </div>
        </div>

        <div class="row-2">
          <div class="form-group">
            <label>Phone</label>
            <input name="phone" id="phone" placeholder="0912 345 6789">
          </div>
          <div class="form-group">
            <label>Designation</label>
            <input name="designation" id="designation" placeholder="e.g., Veterinarian, Receptionist">
          </div>
        </div>

        <div class="row-2">
          <div class="form-group">
            <label>Profile photo (optional)</label>
            <input type="file" name="avatar" id="avatar" accept="image/*">
            <div class="hint">If empty we’ll use the default image (assets/images/person1.jpg).</div>
            <div class="avatar-preview">
              <img id="avatarPreview" src="<?= $BASE ?>assets/images/person1.jpg" alt="Preview">
            </div>
          </div>
          <div></div>
        </div>

        <div class="form-group">
          <label>Permissions</label>
          <div class="perm-grid">
            <label class="perm-card">
              <span>Appointments</span>
              <label class="switch"><input type="checkbox" value="appointments" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Medical Records</span>
              <label class="switch"><input type="checkbox" value="medical" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Pets</span>
              <label class="switch"><input type="checkbox" value="pets" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Pet Owners</span>
              <label class="switch"><input type="checkbox" value="owners" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Announcements</span>
              <label class="switch"><input type="checkbox" value="announcements" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Documents</span>
              <label class="switch"><input type="checkbox" value="documents" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Pet Care Tips</span>
              <label class="switch"><input type="checkbox" value="petcare" class="perm"><span class="slider"></span></label>
            </label>
            <label class="perm-card">
              <span>Set Schedule</span>
              <label class="switch"><input type="checkbox" value="schedule" class="perm"><span class="slider"></span></label>
            </label>
          </div>
        </div>

        <div class="form-group">
          <label>Documents (PDF / images)</label>
          <div id="docsWrap" class="docs-wrap">
            <div class="doc-row">
              <select name="doc_kind[]" class="doc-kind">
                <option value="resume">Resume</option>
                <option value="id">ID</option>
                <option value="license">License</option>
                <option value="photo">Photo</option>
                <option value="other" selected>Other</option>
              </select>
              <div class="file-upload-wrapper">
                <input type="file" name="docs[]" class="doc-file file-upload-input" accept=".pdf,image/*">
                <button type="button" class="file-upload-button">Choose File</button>
                <span class="file-upload-filename">No file chosen</span>
              </div>
              <button type="button" class="doc-btn add" title="Add document"><i class="fa-solid fa-plus"></i></button>
              <button type="button" class="doc-btn remove" title="Remove row"><i class="fa-solid fa-minus"></i></button>
            </div>
          </div>
          <div class="hint">Select a type then choose a file. Allowed: PDF, JPG, PNG, WEBP. Max ~20MB each.</div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelStaff">Cancel</button>
      <button class="btn btn-primary" form="staffForm">Save</button>
    </div>
  </div>
</div>

<div id="docsModal" class="modal" role="dialog" aria-modal="true" hidden>
  <div class="modal-content show">
    <div class="modal-header">
      <h3 id="docsTitle">Documents</h3>
      <button id="closeDocsModal" class="modal-close-btn" aria-label="Close">&times;</button>
    </div>
    <div class="modal-body">
      <div id="docsVault" class="docs-vault">
        <div class="vault-empty">Loading…</div>
      </div>
    </div>
  </div>
</div>

<script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
<script src="<?= $BASE ?>assets/js/dashboard.js"></script>
<script src="<?= $BASE ?>assets/js/admin-staffs.js"></script>
</body>
</html>