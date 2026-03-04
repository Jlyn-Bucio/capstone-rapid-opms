<?php
include_once __DIR__ . '/../audit_trail/audit.php';
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../logger/log.php';

$audit = new AuditLogger($conn);
$log = new Logger();

$project_name = "";

/* =========================
   FETCH CUSTOMERS
========================= */
$customers = [];
$customer_result = $conn->query("SELECT id, name, company_name FROM customers ORDER BY name ASC");
while ($row = $customer_result->fetch_assoc()) {
    $customers[] = $row;
}

/* =========================
   FETCH PROJECTS
========================= */
$projects = [];
$project_result = $conn->query("SELECT id, name FROM projects ORDER BY name ASC");
while ($row = $project_result->fetch_assoc()) {
    $projects[] = $row;
}

$errors = [];

/* =========================
   HANDLE POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $project_id   = (int) ($_POST['project_id'] ?? 0);
    $customer_id  = (int) ($_POST['customer_id'] ?? 0);
    $po_number    = trim($_POST['po_number'] ?? '');
    $amount       = str_replace(',', '', trim($_POST['amount'] ?? ''));
    $billing_date = trim($_POST['billing_date'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    /* VALIDATION */
    if (!$project_id)                          $errors['project_id'] = '*';
    if (!$customer_id)                         $errors['customer_id'] = '*';
    if ($po_number === '')                     $errors['po_number'] = '*';
    if ($amount === '' || !is_numeric($amount)) $errors['amount'] = '*';
    if ($billing_date === '')                  $errors['billing_date'] = '*';

    if (empty($errors)) {

        $stmt = $conn->prepare("
            INSERT INTO billing
            (project_id, customer_id, po_number, amount, billing_date, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisdss",
            $project_id,
            $customer_id,
            $po_number,
            $amount,
            $billing_date,
            $notes
        );

        if ($stmt->execute()) {

            //Get the inserted billing ID
            $billing_id = $stmt->insert_id;

            //Get the project name from project_id
            $stmt_proj = $conn->prepare("SELECT name FROM projects WHERE id = ?");
            $stmt_proj->bind_param("i", $project_id);
            $stmt_proj->execute();
            $stmt_proj->bind_result($project_name);
            $stmt_proj->fetch();
            $stmt_proj->close();

            // Log audit
            $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $description = "Created Billing: {$project_name} (ID: {$billing_id}) by '{$admin_name}'";
            $audit->log('CREATE', 'Billing', $description);

            $log->info("Created Billing for Project: " . $project_name);

            $_SESSION['success_billing'] = "Billing record created.";
            header("Location: main.php?page=billing/list");
            exit;
        } else {
            $errors['database'] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-plus-circle me-2"></i>Create New Billing
      </h4>
    </div>

    <div class="card-body">

      <form method="POST" id="billingForm" class="row g-3">

        <!-- PROJECT -->
        <div class="col-md-6">
          <label class="form-label fw-bold">
            Project:
            <span class="text-danger">*<?= $errors['project_id'] ?? '' ?></span>
          </label>
          <select name="project_id" class="form-select" required>
            <option value="">Select project</option>
            <?php foreach ($projects as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($_POST['project_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- CUSTOMER -->
        <div class="col-md-6">
          <label class="form-label fw-bold">
            Customer:
            <span class="text-danger">*<?= $errors['customer_id'] ?? '' ?></span>
          </label>
          <select name="customer_id" class="form-select" required>
            <option value="">Select customer</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name']) ?>
                <?= $c['company_name'] ? ' (' . htmlspecialchars($c['company_name']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- P.O NO -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            P.O No.:
            <span class="text-danger">*<?= $errors['po_number'] ?? '' ?></span>
          </label>
          <input type="text"
                 class="form-control"
                 name="po_number"
                 required
                 value="<?= htmlspecialchars($_POST['po_number'] ?? '') ?>">
        </div>

        <!-- AMOUNT -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            Amount (₱):
            <span class="text-danger">*<?= $errors['amount'] ?? '' ?></span>
          </label>
          <input type="text"
                 class="form-control"
                 name="amount"
                 id="amount"
                 required
                 value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>">
        </div>

        <!-- BILLING DATE -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            Billing Date:
            <span class="text-danger">*<?= $errors['billing_date'] ?? '' ?></span>
          </label>
          <input type="date"
                 class="form-control"
                 name="billing_date"
                 value="<?= htmlspecialchars($_POST['billing_date'] ?? '') ?>"
                 required>
        </div>

        <!-- NOTES -->
        <div class="col-md-12">
          <label class="form-label fw-bold">Notes:</label>
          <textarea name="notes"
                    class="form-control"
                    rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>

        <?php if (isset($errors['database'])): ?>
          <div class="col-12">
            <div class="alert alert-danger"><?= htmlspecialchars($errors['database']) ?></div>
          </div>
        <?php endif; ?>

        <!-- BUTTONS -->
        <div class="col-12 text-end mt-3">
          <a href="main.php?page=billing/list" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-success">Create Billing</button>
        </div>

      </form>
    </div>
  </div>
</div>
<script>
const amountInput = document.getElementById('amount');

amountInput.addEventListener('input', function () {
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

document.getElementById('billingForm').addEventListener('submit', function () {
    amountInput.value = amountInput.value.replace(/,/g, '');
});
</script>