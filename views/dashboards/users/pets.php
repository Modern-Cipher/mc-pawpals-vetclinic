<?php
require_once __DIR__ . '/../../../middleware/auth.php';
require_login(['user']);
$BASE = base_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>My Pets</title>

  <link rel="stylesheet" href="<?= $BASE ?>assets/css/base-dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="<?= $BASE ?>assets/css/pets.css">
</head>
<body>
  <?php require_once __DIR__ . '/../../partials/sidebar-user.php'; ?>

  <!-- Backdrop for all modals (keep one global instance) -->
  <div id="drawerBackdrop" class="backdrop" hidden></div>

  <div class="page-wrapper">
    <?php require_once __DIR__ . '/../../partials/topbar.php'; ?>

    <main class="content">
      <div class="content-container">
        <div class="page-head">
          <h1>My Pets</h1>
          <div class="page-tools">
            <div class="searchbox">
              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
              <input id="petSearch" class="input" type="search" placeholder="Search by name, species, breed..." aria-label="Search pets">
            </div>
            <button id="btnOpenPetModal" class="btn-add-pet" data-tooltip="Add New Pet" type="button">
              <i class="fa-solid fa-plus" aria-hidden="true"></i><span class="btn-label">Add Pet</span>
            </button>
          </div>
        </div>

        <div class="species-chips" id="speciesChips" role="tablist" aria-label="Filter by species">
          <span class="chip active" data-sp="all" role="tab" aria-selected="true">All</span>
          <span class="chip" data-sp="dog" role="tab" aria-selected="false">Dogs</span>
          <span class="chip" data-sp="cat" role="tab" aria-selected="false">Cats</span>
          <span class="chip" data-sp="bird" role="tab" aria-selected="false">Birds</span>
          <span class="chip" data-sp="rabbit" role="tab" aria-selected="false">Rabbits</span>
          <span class="chip" data-sp="hamster" role="tab" aria-selected="false">Hamsters</span>
          <span class="chip" data-sp="fish" role="tab" aria-selected="false">Fish</span>
          <span class="chip" data-sp="reptile" role="tab" aria-selected="false">Reptiles</span>
          <span class="chip" data-sp="other" role="tab" aria-selected="false">Others</span>
        </div>

        <section class="pets-grid" id="petsGrid" aria-live="polite"></section>
      </div>
    </main>

    <?php require_once __DIR__ . '/../../partials/footer-user.php'; ?>
  </div>

  <!-- Add/Edit Pet Modal -->
  <div
    id="petModal"
    class="modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="petModalTitle"
    aria-hidden="true"
  >
    <div class="modal-content" tabindex="-1">
      <div class="modal-header">
        <h3 id="petModalTitle">Add Pet</h3>
        <button id="closePetModal" class="modal-close-btn" type="button" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <form id="petForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="id" id="pet_id">

          <div class="row-2">
            <div class="form-group">
              <label for="pet_name">Pet Name</label>
              <input type="text" id="pet_name" name="name" required>
            </div>
            <div class="form-group">
              <label for="species">Species</label>
              <select id="species" name="species" required>
                <option value="dog">Dog</option>
                <option value="cat">Cat</option>
                <option value="bird">Bird</option>
                <option value="rabbit">Rabbit</option>
                <option value="hamster">Hamster</option>
                <option value="fish">Fish</option>
                <option value="reptile">Reptile</option>
                <option value="other">Other</option>
              </select>
            </div>
          </div>

          <div class="row-2">
            <div class="form-group">
              <label for="breed">Breed</label>
              <input type="text" id="breed" name="breed" placeholder="e.g., Aspin / German Shepherd">
            </div>
            <div class="form-group">
              <label for="sex">Sex</label>
              <select id="sex" name="sex">
                <option value="unknown">Unknown</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </div>
          </div>

          <div class="row-2">
            <div class="form-group">
              <label for="color">Color</label>
              <input type="text" id="color" name="color" placeholder="Brown, Black, etc.">
            </div>
            <div class="form-group">
              <label for="birthdate">Birthdate</label>
              <input type="text" id="birthdate" name="birthdate" placeholder="Select a date">
            </div>
          </div>

          <div class="form-group">
            <label class="custom-checkbox">
              <input type="checkbox" id="sterilized" name="sterilized" value="1">
              <span class="checkbox-visual" aria-hidden="true"></span>
              <span>Neutered/Spayed</span>
            </label>
          </div>

          <div class="form-group">
            <label for="species_other">If "Other", specify</label>
            <input type="text" id="species_other" name="species_other" placeholder="e.g., Tortoise">
          </div>

          <div class="form-group">
            <label for="photo">Photo</label>
            <div class="custom-file-input">
              <input type="file" id="photo" name="photo" accept="image/*">
              <label for="photo" class="file-input-label">
                <i class="fa-solid fa-upload" aria-hidden="true"></i><span>Choose a file...</span>
              </label>
              <span class="file-input-name">No file chosen.</span>
            </div>
            <!-- Preview box -->
            <div id="imagePreview" class="image-preview" aria-label="Selected image preview">Image Preview</div>
            <small class="hint">Max ~ 40MB (server limits apply).</small>
          </div>

          <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" placeholder="Anything the vet should know..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" id="btnCancelPet" class="btn btn-secondary">Cancel</button>
        <button type="submit" form="petForm" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>

  <!-- Details Modal (left anchored) -->
  <div
    id="petDetails"
    class="modal modal-left"
    role="dialog"
    aria-modal="true"
    aria-labelledby="petDetailsTitle"
    aria-hidden="true"
  >
    <div class="modal-content" tabindex="-1">
      <div class="modal-header">
        <h3 id="petDetailsTitle">Pet Details</h3>
        <button id="closePetDetails" class="modal-close-btn" type="button" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body" id="detailsBody"></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>const App = { BASE_URL: '<?= $BASE ?>' };</script>
  <script src="<?= $BASE ?>assets/js/dashboard.js"></script>
  <script src="<?= $BASE ?>assets/js/pets.js"></script>
</body>
</html>
