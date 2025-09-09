<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_once __DIR__ . '/../../../app/models/PetCare.php';
require_login(['admin']);

$user  = $_SESSION['user'];
$BASE  = base_path();
$rows  = PetCare::all();

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pet Care Tips - PawPals</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/petcare.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div id="drawerBackdrop" class="backdrop" hidden></div>
  <?php require __DIR__ . '/../../partials/topbar.php'; ?>

  <main class="content">
    <div class="settings-container">
      <h1 style="font-weight:700">Pet Care Tips</h1>
      <p>Create and manage pet care articles (text / file / URL). Images and attachments support random 6-char filenames.</p>

      <div class="panel-card">
        <div class="panel-body">

          <!-- Mobile controls -->
          <div class="d-md-none mobile-controls">
            <div class="mobile-search-wrapper">
              <i class="fa-solid fa-search"></i>
              <input type="text" id="mobileSearchInput" class="form-control" placeholder="Search tips...">
            </div>
            <button id="mobileAddBtn" class="btn btn-primary" title="Add tip">
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>

          <!-- Desktop table -->
          <div class="d-none d-md-block">
            <table id="tipsTable" class="table" style="width:100%">
              <thead>
                <tr>
                  <th class="thumb-cell">Image</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Type</th>
                  <th>Published</th>
                  <th class="no-sort">Link</th>
                  <th class="text-end no-sort actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                <tr
                  data-id="<?= (int)$r['id'] ?>"
                  data-title="<?= h($r['title']) ?>"
                  data-summary="<?= h($r['summary'] ?? '') ?>"
                  data-body="<?= h($r['body'] ?? '') ?>"
                  data-category="<?= h($r['category']) ?>"
                  data-type="<?= h($r['content_type']) ?>"
                  data-image="<?= h($r['image_path'] ?? '') ?>"
                  data-file="<?= h($r['file_path'] ?? '') ?>"
                  data-external_url="<?= h($r['external_url'] ?? '') ?>"
                  data-published="<?= (int)$r['is_published'] ?>"
                  data-published_at="<?= h($r['published_at'] ?? '') ?>"
                  data-expires_at="<?= h($r['expires_at'] ?? '') ?>"
                >
                  <td class="thumb-cell">
                    <?php if (!empty($r['image_path'])): ?>
                      <img src="<?= $BASE . h($r['image_path']) ?>" class="thumb zoomable-image" alt="">
                    <?php else: ?>
                      <div class="thumb placeholder"><i class="fa-regular fa-image"></i></div>
                    <?php endif; ?>
                  </td>
                  <td class="title-cell"><strong><?= h($r['title']) ?></strong></td>
                  <td><?= ucfirst(h($r['category'])) ?></td>
                  <td><?= strtoupper(h($r['content_type'])) ?></td>
                  <td><?= $r['is_published'] ? 'Yes' : 'No' ?></td>
                  <td>
                    <?php if (!empty($r['external_url'])): ?>
                      <a href="<?= h($r['external_url']) ?>" class="btn-icon" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    <?php elseif (!empty($r['file_path'])): ?>
                      <a href="<?= $BASE . h($r['file_path']) ?>" class="btn-icon" target="_blank"><i class="fa-solid fa-file"></i></a>
                    <?php else: echo '—'; endif; ?>
                  </td>
                  <td class="text-end actions-col">
                    <div class="actions-wrap">
                      <button class="btn-icon edit-tip" title="Edit"><i class="fa-solid fa-pen"></i></button>
                      <button class="btn-icon delete-tip" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile slider -->
          <div id="mobileCardContainer" class="d-md-none">
            <?php foreach ($rows as $r): ?>
              <div class="tip-card"
                data-id="<?= (int)$r['id'] ?>"
                data-title="<?= h($r['title']) ?>"
                data-summary="<?= h($r['summary'] ?? '') ?>"
                data-body="<?= h($r['body'] ?? '') ?>"
                data-category="<?= h($r['category']) ?>"
                data-type="<?= h($r['content_type']) ?>"
                data-image="<?= h($r['image_path'] ?? '') ?>"
                data-file="<?= h($r['file_path'] ?? '') ?>"
                data-external_url="<?= h($r['external_url'] ?? '') ?>"
                data-published="<?= (int)$r['is_published'] ?>"
                data-published_at="<?= h($r['published_at'] ?? '') ?>"
                data-expires_at="<?= h($r['expires_at'] ?? '') ?>"
              >
                <?php if (!empty($r['image_path'])): ?>
                  <img src="<?= $BASE . h($r['image_path']) ?>" class="tip-card-image zoomable-image" alt="">
                <?php else: ?>
                  <div class="tip-card-no-image"><i class="fa-regular fa-image"></i><span>No Image</span></div>
                <?php endif; ?>
                <div class="tip-card-body">
                  <h5 class="tip-card-title"><?= h($r['title']) ?></h5>
                  <p class="tip-card-description"><?= h(mb_strimwidth((string)($r['summary'] ?? $r['body']), 0, 100, '...')) ?></p>
                  <div class="tip-card-meta">
                    <span class="meta-item"><i class="fa-solid fa-tag"></i> <?= ucfirst(h($r['category'])) ?></span>
                    <span class="meta-item"><span class="status-badge <?= $r['is_published'] ? 'published' : 'draft' ?>"><?= $r['is_published'] ? 'Published' : 'Draft' ?></span></span>
                  </div>
                </div>
                <div class="tip-card-footer">
                  <?php if (!empty($r['external_url'])): ?>
                    <a href="<?= h($r['external_url']) ?>" class="btn-icon" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                  <?php elseif (!empty($r['file_path'])): ?>
                    <a href="<?= $BASE . h($r['file_path']) ?>" class="btn-icon" target="_blank"><i class="fa-solid fa-file"></i></a>
                  <?php endif; ?>
                  <button class="btn-icon edit-tip"><i class="fa-solid fa-pen"></i></button>
                  <button class="btn-icon delete-tip"><i class="fa-solid fa-trash"></i></button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      </div>
    </div>
  </main>

  <!-- Modal -->
  <div id="tipModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal-content">
      <div class="modal-header"><h3 id="tipModalTitle">Add Tip</h3><button id="tipModalClose" class="modal-close-btn" aria-label="Close">&times;</button></div>
      <div class="modal-body">
        <form id="tipForm" enctype="multipart/form-data" class="profile-form">
          <input type="hidden" name="id" id="tipId">

          <div class="form-group"><label for="tipTitle">Title</label><input type="text" id="tipTitle" name="title" required></div>
          <div class="form-group"><label for="tipSummary">Summary (optional)</label><textarea id="tipSummary" name="summary" rows="2" placeholder="Short preview..."></textarea></div>
          <div class="form-grid grid-col-2">
            <div class="form-group"><label for="tipCategory">Category</label>
              <select id="tipCategory" name="category">
                <option value="diet">Diet</option>
                <option value="puppy">Puppy</option>
                <option value="health">Health</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="form-group"><label for="tipType">Content Type</label>
              <select id="tipType" name="content_type">
                <option value="text">Text (write article)</option>
                <option value="file">File (PDF/Doc/XLS/PPT)</option>
                <option value="url">External URL</option>
              </select>
            </div>
          </div>

          <div id="typeTextGroup" class="form-group"><label for="tipBody">Article Body</label><textarea id="tipBody" name="body" rows="5" placeholder="Write the full article here..."></textarea></div>
          <div id="typeFileGroup" class="form-group" hidden>
            <label>Attachment (PDF/Doc/Xls/Ppt, ≤10MB)</label>
            <input type="file" id="tipFile" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
          </div>
          <div id="typeUrlGroup" class="form-group" hidden><label for="tipUrl">External URL</label><input type="url" id="tipUrl" name="external_url" placeholder="https://..."></div>

          <div class="form-grid grid-col-2">
            <div class="form-group"><label for="tipPublishedAt">Publish Date</label><input type="text" id="tipPublishedAt" name="published_at" placeholder="Optional"></div>
            <div class="form-group"><label for="tipExpiresAt">Expiry Date</label><input type="text" id="tipExpiresAt" name="expires_at" placeholder="Optional"></div>
          </div>
          <div class="form-group"><label for="tipStatus">Status</label><select id="tipStatus" name="is_published"><option value="1">Published</option><option value="0">Draft</option></select></div>

          <div class="form-group">
            <label>Image (JPG/PNG/WEBP, ≤4MB)</label>
            <div class="d-flex align-items-center gap-3">
              <div class="ann-img-preview" id="tipImgPrev"><i class="fa-regular fa-image"></i></div>
              <div class="file-upload-wrapper">
                <input type="file" id="tipImage" name="image" class="file-upload-input" accept="image/png,image/jpeg,image/webp">
                <label for="tipImage" class="file-upload-button" title="Choose image"><i class="fa-solid fa-upload"></i></label>
                <span id="tipImageName" class="file-upload-filename">No file chosen</span>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i><span class="btn-text">&nbsp;Save</span></button>
            <button type="button" id="tipCancel" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i><span class="btn-text">&nbsp;Cancel</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php require __DIR__ . '/../../partials/footer.php'; ?>
  <div id="modalBackdrop" class="backdrop" hidden></div>

  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
  <script src="<?= $BASE ?>assets/js/petcare.js"></script>
</body>
</html>
