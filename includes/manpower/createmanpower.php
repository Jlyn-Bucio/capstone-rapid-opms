<?php
include_once __DIR__ . '/../rapid_opms.php';

/* =========================
   INITIALIZE VARIABLES
========================= */
$error  = '';
$errors = [];

/* =========================
   ENSURE TABLE EXISTS
========================= */
$conn->query("CREATE TABLE IF NOT EXISTS manpower (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL DEFAULT YEAR(CURDATE()),
    name VARCHAR(255) NOT NULL DEFAULT '',
    position VARCHAR(255) NOT NULL,
    old_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
    meal_allowance DECIMAL(12,2) NOT NULL DEFAULT 0,
    new_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($conn->error);

/* =========================
   HANDLE POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $year  = trim($_POST['year'] ?? '');
    $name  = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $old_rate = str_replace(',', '', trim($_POST['old_rate'] ?? ''));
    $meal_allowance = str_replace(',', '', trim($_POST['meal_allowance'] ?? ''));
    $new_rate = str_replace(',', '', trim($_POST['new_rate'] ?? ''));

    // ✅ ADDED: force year before saving (server-side)
    if ($year === '') {
        $errors['year'] = '*';
        $error = 'Please fill up all required fields correctly.';
    }

    if ($year === '' || !is_numeric($year)) $errors['year'] = '*';
    if ($name === '') $errors['name'] = '*';
    if ($position === '') $errors['position'] = '*';
    if ($old_rate === '' || !is_numeric($old_rate)) $errors['old_rate'] = '*';
    if ($meal_allowance === '' || !is_numeric($meal_allowance)) $errors['meal_allowance'] = '*';
    if ($new_rate === '' || !is_numeric($new_rate)) $errors['new_rate'] = '*';

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO manpower (year, name, position, old_rate, meal_allowance, new_rate)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $y = (int)$year;
        $o = (float)$old_rate;
        $m = (float)$meal_allowance;
        $n = (float)$new_rate;

        $stmt->bind_param(
            'issddd',
            $y,
            $name,
            $position,
            $o,
            $m,
            $n
        );

        if ($stmt->execute()) {
            $_SESSION['success_manpower'] = 'Manpower item created.';
            header('Location: main.php?page=manpower/listmanpower');
            exit;
        } else {
            $error = 'Database error: ' . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-user-plus me-2"></i>Create New Manpower
      </h4>
    </div>

    <div class="card-body">

      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="row g-3">

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

        <!-- NAME -->
        <div class="col-md-6">
          <label class="form-label fw-bold">
            Name: <span class="text-danger"><?= $errors['name'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="name"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 required>
        </div>

        <!-- POSITION -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            Position: <span class="text-danger"><?= $errors['position'] ?? '*' ?></span>
          </label>
          <select name="position" class="form-select" required>
            <option value="">Select</option>
            <option value="Finisher" <?= ($_POST['position'] ?? '') === 'Finisher' ? 'selected' : '' ?>>Finisher</option>
            <option value="Asst.Finisher" <?= ($_POST['position'] ?? '') === 'Asst.Finisher' ? 'selected' : '' ?>>Asst. Finisher</option>
            <option value="Concrete Laborer" <?= ($_POST['position'] ?? '') === 'Concrete Laborer' ? 'selected' : '' ?>>Concrete Laborer</option>
          </select>
        </div>

        <!-- OLD RATE -->
        <div class="col-md-2">
          <label class="form-label fw-bold">
            Old Rate (₱): <span class="text-danger"><?= $errors['old_rate'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="old_rate"
                 id="old_rate"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['old_rate'] ?? '') ?>">
        </div>

        <!-- MEAL ALLOWANCE -->
        <div class="col-md-2">
          <label class="form-label fw-bold">
            Meal (₱): <span class="text-danger"><?= $errors['meal_allowance'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="meal_allowance"
                 id="meal_allowance"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['meal_allowance'] ?? '') ?>">
        </div>

        <!-- NEW RATE -->
        <div class="col-md-2">
          <label class="form-label fw-bold">
            New Rate (₱): <span class="text-danger"><?= $errors['new_rate'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="new_rate"
                 id="new_rate"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['new_rate'] ?? '') ?>">
        </div>

        <!-- BUTTONS -->
        <div class="col-12 text-end mt-3">
          <a href="main.php?page=manpower/listmanpower" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-success" id="saveBtn">Create Manpower</button>

        </div>
      </form>
    </div>
  </div>
</div>

<!-- NUMBER FORMATTING -->
<script>
(function(){
  function setup(id){
    const el = document.getElementById(id);
    if(!el) return;
    el.addEventListener('input', function(){
      let v = this.value.replace(/,/g,'');
      if(v === '') return;
      if(!/^\d*\.?\d*$/.test(v)) return;
      let p = v.split('.');
      p[0] = p[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      this.value = p.join('.');
    });
  }
  setup('old_rate');
  setup('meal_allowance');
  setup('new_rate');
})();
</script>