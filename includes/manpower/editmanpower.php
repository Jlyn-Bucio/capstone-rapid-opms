<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../rapid_opms.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int)$_GET['id'];

// fetch
$stmt = $conn->prepare("SELECT * FROM manpower WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$man = $res->fetch_assoc();
$stmt->close();

if (!$man) {
    echo '<div class="container py-4"><div class="alert alert-warning">Manpower item not found.</div></div>';
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $old_rate = str_replace(',', '', trim($_POST['old_rate'] ?? ''));
    $meal = str_replace(',', '', trim($_POST['meal_allowance'] ?? ''));
    $new_rate = str_replace(',', '', trim($_POST['new_rate'] ?? ''));

    if ($name === '') $errors['name'] = '*';
    if ($position === '') $errors['position'] = '*';
    if ($old_rate === '' || !is_numeric($old_rate)) $errors['old_rate'] = '*';
    if ($meal === '' || !is_numeric($meal)) $errors['meal_allowance'] = '*';
    if ($new_rate === '' || !is_numeric($new_rate)) $errors['new_rate'] = '*';

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE manpower SET name = ?, position = ?, old_rate = ?, meal_allowance = ?, new_rate = ? WHERE id = ?");
        $or = (float)$old_rate;
        $ma = (float)$meal;
        $nr = (float)$new_rate;
        $stmt->bind_param('ssdddi', $name, $position, $or, $ma, $nr, $id);

        if ($stmt->execute()) {
            $_SESSION['success_manpower'] = "Manpower item updated.";
            header("Location: main.php?page=manpower/listmanpower");
            exit;
        } else {
            $errors = "Update failed: " . $conn->error;
        }
    }
}
?>

<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-2">
                <i class="fa fa-edit me-2"></i>Edit Manpower
            </h4>
        </div>

    <div class="card-body">
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form id="manpowerEditForm" method="post" class="row g-3">

         <!-- YEAR -->
        <div class="col-md-2">
          <label class="form-label fw-bold">
            Year: <span class="text-danger"><?= $errors['year'] ?? '*' ?></span>
          </label>
          <input type="number"
                 name="year"
                 id="year"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['year'] ?? date('Y')) ?>"
                 required>
        </div>
        <!-- NAME -->
            <div class="col-md-4">
                <label class="form-label fw-bold">Name: <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? $man['name']) ?>">
            </div>
        <!-- POSITION -->
            <div class="col-md-4">
    <label class="form-label fw-bold">
        Position: <span class="text-danger">*</span>
    </label>

    <select name="position" class="form-select" required>
        <option value="">Select</option>
        <option value="Finisher"
            <?= ($_POST['position'] ?? $man['position']) === 'Finisher' ? 'selected' : '' ?>>
            Finisher
        </option>
        <option value="Asst.Finisher"
            <?= ($_POST['position'] ?? $man['position']) === 'Asst.Finisher' ? 'selected' : '' ?>>
            Asst. Finisher
        </option>
        <option value="Concrete Laborer"
            <?= ($_POST['position'] ?? $man['position']) === 'Concrete Laborer' ? 'selected' : '' ?>>
            Concrete Laborer
        </option>
    </select>
</div>

 <!-- OLD RATE -->
            <div class="col-md-2">
                <label class="form-label fw-bold">Old Rate (₱): <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="old_rate" id="old_rate" class="form-control" value="<?= htmlspecialchars($_POST['old_rate'] ?? number_format($man['old_rate'] ?? 0, 2)) ?>">
            </div>
  <!-- MEAL ALLOWANCE -->
            <div class="col-md-2">
                <label class="form-label fw-bold">Meal Allowance (₱): <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="meal_allowance" id="meal_allowance" class="form-control" value="<?= htmlspecialchars($_POST['meal_allowance'] ?? number_format($man['meal_allowance'] ?? 0, 2)) ?>">
            </div>
    <!-- NEW RATE -->
            <div class="col-md-2">
                <label class="form-label fw-bold">New Rate (₱): <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="new_rate" id="new_rate" class="form-control" value="<?= htmlspecialchars($_POST['new_rate'] ?? number_format($man['new_rate'] ?? 0, 2)) ?>">
            </div>
            
            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                <a href="main.php?page=manpower/listmanpower" class="btn btn-secondary">Cancel
                </a>

                <button type="submit" class="btn btn-success" id="updateBtn" disabled>Update
                </button>
            </div>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const form = document.getElementById('manpowerEditForm');
    const updateBtn = document.getElementById('updateBtn');
    const inputs = form.querySelectorAll('input');

    // Save original values
    const originalValues = {};
    inputs.forEach(input => {
        originalValues[input.name] = input.value.replace(/,/g, '');
    });

    // Numeric formatting
    ['old_rate', 'meal_allowance', 'new_rate'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            let value = this.value.replace(/,/g, '');
            if (value === '') { this.value = ''; return; }
            if (!/^\d*\.?\d*$/.test(value)) return;
            let parts = value.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            this.value = parts.join('.');
            checkChanges();
        });
    });

    // Check if anything changed
    function checkChanges() {
        let changed = false;

        inputs.forEach(input => {
            const current = input.value.replace(/,/g, '');
            if (current !== originalValues[input.name]) {
                changed = true;
            }
        });

        updateBtn.disabled = !changed;
    }

    inputs.forEach(input => {
        input.addEventListener('input', checkChanges);
    });

    // Remove commas before submit
    form.addEventListener('submit', function () {
        inputs.forEach(input => {
            input.value = input.value.replace(/,/g, '');
        });
    });

    // Initial state
    updateBtn.disabled = true;
})();
</script>

