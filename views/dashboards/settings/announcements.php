<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../app/models/Announcement.php';
require_login(['admin']);

$user    = $_SESSION['user'];
$user_id = (int)$user['id'];
$BASE    = base_path();
$rows    = Announcement::all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Announcements - PawPals</title>

  <!-- Vendor CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <!-- Theme -->
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/announcements.css">
</head>
<body>
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div id="drawerBackdrop" class="backdrop" hidden></div>
  <?php require __DIR__ . '/../../partials/topbar.php'; ?>

  <main class="content">
    <div class="settings-container">
      <h1 style="font-weight:700">Announcements</h1>
      <p>Create and manage announcements for dashboard/landing.</p>

      <div class="panel-card">
        <div class="panel-body">
          <!-- Mobile: search + add -->
          <div class="d-md-none mobile-controls">
            <div class="mobile-search-wrapper">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="mobileSearchInput" class="form-control" placeholder="Search announcements...">
            </div>
            <button id="mobileAddBtn" class="btn btn-primary" title="Add announcement" aria-label="Add">
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>

          <!-- Desktop table -->
          <div class="d-none d-md-block">
            <table id="annTable" class="table" style="width:100%">
              <thead>
                <tr>
                  <th class="thumb-cell">Image</th>
                  <th>Title</th>
                  <th>Audience</th>
                  <th>Location</th>
                  <th>Published</th>
                  <th class="no-sort">Link</th>
                  <th class="text-end no-sort actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($rows as $r): ?>
                <tr
                  data-id="<?= (int)$r['id'] ?>"
                  data-title="<?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-body="<?= htmlspecialchars((string)($r['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-audience="<?= htmlspecialchars((string)($r['audience'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-location="<?= htmlspecialchars((string)($r['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-published="<?= (int)$r['is_published'] ?>"
                  data-published_at="<?= htmlspecialchars((string)($r['published_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-expires_at="<?= htmlspecialchars((string)($r['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-image="<?= htmlspecialchars((string)($r['image_path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-external_url="<?= htmlspecialchars((string)($r['external_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                >
                  <td class="thumb-cell">
                    <?php if (!empty($r['image_path'])): ?>
                      <img src="<?= $BASE . htmlspecialchars((string)$r['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="thumb zoomable-image">
                    <?php else: ?>
                      <div class="thumb placeholder" title="No image"><i class="fa-regular fa-image"></i></div>
                    <?php endif; ?>
                  </td>
                  <td class="title-cell"><strong><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                  <td><?= htmlspecialchars(ucfirst($r['audience']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars(ucfirst($r['location']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= $r['is_published'] ? 'Yes' : 'No' ?></td>
                  <td>
                    <?php if (!empty($r['external_url'])): ?>
                      <a href="<?= htmlspecialchars((string)$r['external_url'], ENT_QUOTES, 'UTF-8') ?>" class="btn-icon" target="_blank" rel="noopener" title="Open link"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    <?php else: echo '—'; endif; ?>
                  </td>
                  <td class="text-end actions-col">
                    <div class="actions-wrap">
                      <button class="btn-icon edit-ann" title="Edit"><i class="fa-solid fa-pen"></i></button>
                      <button class="btn-icon delete-ann" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile cards -->
          <div id="mobileCardContainer" class="d-md-none">
            <?php if (empty($rows)): ?>
              <p class="text-center text-muted mt-3">No announcements found.</p>
            <?php else: foreach ($rows as $r): ?>
              <div class="ann-card"
                data-id="<?= (int)$r['id'] ?>"
                data-title="<?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-body="<?= htmlspecialchars((string)($r['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-audience="<?= htmlspecialchars((string)($r['audience'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-location="<?= htmlspecialchars((string)($r['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-published="<?= (int)$r['is_published'] ?>"
                data-published_at="<?= htmlspecialchars((string)($r['published_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-expires_at="<?= htmlspecialchars((string)($r['expires_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-image="<?= htmlspecialchars((string)($r['image_path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                data-external_url="<?= htmlspecialchars((string)($r['external_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              >
                <?php if (!empty($r['image_path'])): ?>
                  <img src="<?= $BASE . htmlspecialchars((string)$r['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="ann-card-image zoomable-image">
                <?php else: ?>
                  <div class="ann-card-no-image"><i class="fa-regular fa-image"></i><span>No Image</span></div>
                <?php endif; ?>
                <div class="ann-card-body">
                  <h5 class="ann-card-title"><?= htmlspecialchars((string)($r['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                  <p class="ann-card-description"><?= htmlspecialchars(mb_strimwidth((string)($r['body'] ?? ''), 0, 100, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                  <div class="ann-card-meta">
                    <span class="meta-item"><i class="fa-solid fa-users"></i> <?= htmlspecialchars(ucfirst($r['audience']), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="meta-item"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars(ucfirst($r['location']), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="meta-item"><span class="status-badge <?= $r['is_published'] ? 'published' : 'draft' ?>"><?= $r['is_published'] ? 'Published' : 'Draft' ?></span></span>
                  </div>
                </div>
                <div class="ann-card-footer">
                  <?php if (!empty($r['external_url'])): ?>
                    <a href="<?= htmlspecialchars((string)$r['external_url'], ENT_QUOTES, 'UTF-8') ?>" class="btn-icon" target="_blank" rel="noopener" title="Open link"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                  <?php endif; ?>
                  <button class="btn-icon edit-ann" title="Edit"><i class="fa-solid fa-pen"></i></button>
                  <button class="btn-icon delete-ann" title="Delete"><i class="fa-solid fa-trash"></i></button>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Modal -->
  <div id="annModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="annModalTitle">Add Announcement</h3>
        <button id="annModalClose" class="modal-close-btn" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <form id="annForm" enctype="multipart/form-data" class="profile-form">
          <input type="hidden" name="id" id="annId">
          <div class="form-group">
            <label for="annTitle">Title</label>
            <input type="text" id="annTitle" name="title" required>
          </div>
          <div class="form-group">
            <label for="annBody">Body</label>
            <textarea id="annBody" name="body" rows="4" required></textarea>
          </div>
          <div class="form-group">
            <label for="annUrl">External URL (Optional)</label>
            <input type="url" id="annUrl" name="external_url" placeholder="https://example.com/read-more">
          </div>

          <div class="form-grid grid-col-2">
            <div class="form-group">
              <label for="annAudience">Audience</label>
              <select id="annAudience" name="audience" required>
                <option value="all">All</option><option value="admins">Admins</option><option value="staff">Staff</option><option value="owners">Pet Owners</option>
              </select>
            </div>
            <div class="form-group">
              <label for="annLocation">Location</label>
              <select id="annLocation" name="location" required>
                <option value="dashboard">Dashboard</option><option value="landing">Landing</option><option value="both">Both</option>
              </select>
            </div>
          </div>

          <div class="form-grid grid-col-2">
            <div class="form-group">
              <label for="annPublishedAt">Publish Date</label>
              <input type="text" id="annPublishedAt" name="published_at" placeholder="Optional">
            </div>
            <div class="form-group">
              <label for="annExpiresAt">Expiry Date</label>
              <input type="text" id="annExpiresAt" name="expires_at" placeholder="Optional">
            </div>
          </div>

          <div class="form-group">
            <label for="annStatus">Status</label>
            <select id="annStatus" name="is_published">
              <option value="1">Published</option>
              <option value="0">Draft</option>
            </select>
          </div>

          <div class="form-group">
            <label>Image</label>
            <div class="d-flex align-items-center gap-3">
              <div class="ann-img-preview" id="annImgPrev"><i class="fa-regular fa-image"></i></div>
              <div class="file-upload-wrapper">
                <input type="file" id="annImage" name="image" class="file-upload-input" accept="image/png,image/jpeg,image/webp">
                <label for="annImage" class="file-upload-button" title="Choose image">
                  <i class="fa-solid fa-upload"></i>
                </label>
                <span id="annImageName" class="file-upload-filename" title="Selected file">No file chosen</span>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Save</span>
            </button>
            <button type="button" id="annCancel" class="btn btn-secondary">
              <i class="fa-solid fa-xmark"></i><span class="btn-text">&nbsp;Cancel</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/../../partials/footer.php'; ?>
  <div id="modalBackdrop" class="backdrop" hidden></div>

  <!-- Vendor JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <!-- App -->
  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
  <script src="<?= $BASE ?>assets/js/announcements.js"></script>
</body>
</html>
