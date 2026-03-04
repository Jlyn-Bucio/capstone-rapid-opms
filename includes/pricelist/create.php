<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../rapid_opms.php';

/* =========================
   ENSURE TABLE EXISTS
========================= */
$conn->query("
CREATE TABLE IF NOT EXISTS price_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    contractor VARCHAR(255) NOT NULL,
    straight_finish DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    rough_finish DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($conn->error);

/* =========================
   HANDLE FORM SUBMIT
========================= */
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $year = trim($_POST['year'] ?? '');
    $contractor = trim($_POST['contractor'] ?? '');
    $straight_finish = str_replace(',', '', trim($_POST['straight_finish'] ?? ''));
    $rough_finish = str_replace(',', '', trim($_POST['rough_finish'] ?? ''));

    // Validation
    if ($year === '' || !is_numeric($year)) {
        $errors['year'] = '*';
    }

    if ($contractor === '') {
        $errors['contractor'] = '*';
    }

    if ($straight_finish === '' || !is_numeric($straight_finish)) {
        $errors['straight_finish'] = '*';
    }

    if ($rough_finish === '' || !is_numeric($rough_finish)) {
        $errors['rough_finish'] = '*';
    }

    if (empty($errors)) {

        $stmt = $conn->prepare("
            INSERT INTO price_list (year, contractor, straight_finish, rough_finish)
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmt) {
            $errors['database'] = $conn->error;
        } else {

            $y  = (int)$year;
            $sf = (float)$straight_finish;
            $rf = (float)$rough_finish;

            $stmt->bind_param('isdd', $y, $contractor, $sf, $rf);

            if ($stmt->execute()) {
            $_SESSION['success_pricelist'] = 'Contractor price created.';
            header('Location: main.php?page=pricelist/list');
            exit;
            } else {
                $error = 'Database error: ' . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!-- =========================
     UI
========================= -->
<div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fa fa-plus-circle me-2"></i>Create New Contractor
      </h4>
    </div>

    <div class="card-body">

    <?php if (isset($errors['database'])): ?>
      <div class="alert alert-danger mt-3">
        <?= htmlspecialchars($errors['database']) ?>
      </div>
    <?php endif; ?>

    <form method="post" id="priceForm" class="row g-3">

      <!-- Year -->
      <div class="col-md-2">
        <label class="form-label fw-bold">
          Year: <span class="text-danger">*<?= $errors['year'] ?? '' ?></span>
        </label>
        <input
          type="number"
          name="year"
          class="form-control"
          value="<?= htmlspecialchars($_POST['year'] ?? date('Y')) ?>"
          required>
      </div>

      <!-- Contractor -->
      <div class="col-md-4">
        <label class="form-label fw-bold">
          Contractor: <span class="text-danger">*<?= $errors['contractor'] ?? '' ?></span>
        </label>
        <input
          type="text"
          name="contractor"
          class="form-control"
          required
          value="<?= htmlspecialchars($_POST['contractor'] ?? '') ?>">
      </div>

      <!-- Rates -->
      <div class="col-md-3">
        <label class="form-label fw-bold">
          Straight to Finish (₱): <span class="text-danger">*<?= $errors['straight_finish'] ?? '' ?></span>
        </label>
        <input
          type="text"
          name="straight_finish"
          id="straight_finish"
          class="form-control"
          placeholder="0.00"
          value="<?= htmlspecialchars($_POST['straight_finish'] ?? '') ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label fw-bold">
          Rough Finish (₱): <span class="text-danger">*<?= $errors['rough_finish'] ?? '' ?></span>
        </label>
        <input
          type="text"
          name="rough_finish"
          id="rough_finish"
          class="form-control"
          placeholder="0.00"
          value="<?= htmlspecialchars($_POST['rough_finish'] ?? '') ?>">
      </div>

      <!-- Buttons -->
      <div class="text-end mt-4">
        <a href="main.php?page=pricelist/list" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-success">Create Contractor</button>
      </div>

    </form>
    </div>
  </div>
</div>

<!-- =========================
     NUMBER FORMAT SCRIPT
========================= -->
<script>
(function () {
  function setupNumericFormatting(id, formId) {
    const el = document.getElementById(id);
    if (!el) return;

    el.addEventListener('input', function () {
      let value = this.value.replace(/,/g, '');
      if (value === '') {
        this.value = '';
        return;
      }

      if (!/^\d*\.?\d*$/.test(value)) return;

      let parts = value.split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      this.value = parts.join('.');
    });

    const form = document.getElementById(formId);
    if (form) {
      form.addEventListener('submit', function () {
        el.value = el.value.replace(/,/g, '');
      });
    }
  }

  setupNumericFormatting('straight_finish', 'priceForm');
  setupNumericFormatting('rough_finish', 'priceForm');
})();
</script>