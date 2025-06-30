<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';


// Fetch customers for dropdown
$customers = [];
$customer_result = $conn->query("SELECT id, name, company_name FROM customers ORDER BY name ASC");
while ($row = $customer_result->fetch_assoc()) {
    $customers[] = $row;
}

// Fetch projects for dropdown
$projects = [];
$project_result = $conn->query("SELECT id, name FROM projects ORDER BY name ASC");
while ($row = $project_result->fetch_assoc()) {
    $projects[] = $row;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $project_id = trim($_POST['project_id'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $billing_date = trim($_POST['billing_date'] ?? '');
    $customer_id = trim($_POST['customer_id'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation (use * instead of error messages)
    if ($project_id === '') $errors['project_id'] = '*';
    if ($amount === '' || !is_numeric($amount)) $errors['amount'] = '*';
    if ($billing_date === '') $errors['billing_date'] = '*';
    if ($customer_id === '') $errors['customer_id'] = '*';

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO billing (invoice_number, project_id, customer_id, amount, billing_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siidss", $invoice_number, $project_id, $customer_id, $amount, $billing_date, $notes);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Billing record created.";
            
            header("Location: main.php?page=billing/list");
            exit;
        } else {
            $errors['database'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="container py-5">
      <div class="card-body">
        <div class="mb-4 px-4 py-3 rounded" style="background-color: #b0b0b0; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);">
          <h5><i class="fa fa-money-bill me-2"></i>Create New Billing</h5>
        </div>

        <form method="POST" action="">
          <div class="mb-3">
            <label for="invoice_number" class="form-label">Invoice Number</label>
            <input type="text" class="form-control" name="invoice_number" id="invoice_number" value="<?= htmlspecialchars($_POST['invoice_number'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="project_id" class="form-label">Project <span class="text-danger"><?= $errors['project_id'] ?? '' ?></span></label>
            <select name="project_id" id="project_id" class="form-select">
              <option value="">Select project</option>
              <?php foreach ($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($_POST['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="amount" class="form-label">Amount (₱) <span class="text-danger"><?= $errors['amount'] ?? '' ?></span></label>
            <input type="number" step="0.01" class="form-control" name="amount" id="amount" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="billing_date" class="form-label">Billing Date <span class="text-danger"><?= $errors['billing_date'] ?? '' ?></span></label>
            <input type="date" class="form-control" name="billing_date" id="billing_date" value="<?= htmlspecialchars($_POST['billing_date'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label for="customer_id" class="form-label">Customer <span class="text-danger"><?= $errors['customer_id'] ?? '' ?></span></label>
            <select name="customer_id" id="customer_id" class="form-select">
              <option value="">Select customer</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['name']) ?><?= $c['company_name'] ? ' (' . htmlspecialchars($c['company_name']) . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes" class="form-control" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
          </div>

          <?php if (isset($errors['database'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors['database']) ?></div>
          <?php endif; ?>

          <button type="submit" class="btn btn-success">Save Billing</button>
          <a href="main.php?page=billing/list" class="btn btn-secondary">Cancel</a>
        </form>
      </div>

    </div>
  </div>
</div>
<?php ob_end_flush(); ?>