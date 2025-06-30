<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No billing ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $project_id = $_POST['project_id'];
    $amount = $_POST['amount'];
    $billing_date = $_POST['billing_date'];
    $due_date = $_POST['due_date'];
    $description = $_POST['description'];
    $invoice_number = $_POST['invoice_number'];
    $notes = $_POST['notes'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE billing SET customer_id=?, project_id=?, amount=?, billing_date=?, due_date=?, description=?, invoice_number=?, notes=?, status=? WHERE id=?");
    $stmt->bind_param("iidssssssi", $customer_id, $project_id, $amount, $billing_date, $due_date, $description, $invoice_number, $notes, $status, $id);
    if ($stmt->execute()) {
        header("Location: ../../main.php?page=billing/list&success=1");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: {$stmt->error}</div>";
    }
    $stmt->close();
}
$stmt = $conn->prepare("SELECT * FROM billing WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$billing = $result->fetch_assoc();
$stmt->close();
if (!$billing) {
    echo "<div class='alert alert-danger'>Billing record not found.</div>";
    exit;
}
?>
<div class="container py-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fa fa-edit me-2"></i>Edit Billing: #<?= $billing['id'] ?></h5>
      <a href="main.php?page=billing/list" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label for="customer_id" class="form-label">Customer ID</label>
          <input type="number" name="customer_id" id="customer_id" class="form-control" value="<?= htmlspecialchars($billing['customer_id']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="project_id" class="form-label">Project ID</label>
          <input type="number" name="project_id" id="project_id" class="form-control" value="<?= htmlspecialchars($billing['project_id']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="amount" class="form-label">Amount</label>
          <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="<?= htmlspecialchars($billing['amount']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="billing_date" class="form-label">Billing Date</label>
          <input type="date" name="billing_date" id="billing_date" class="form-control" value="<?= htmlspecialchars($billing['billing_date']) ?>">
        </div>
        <div class="mb-3">
          <label for="due_date" class="form-label">Due Date</label>
          <input type="date" name="due_date" id="due_date" class="form-control" value="<?= htmlspecialchars($billing['due_date']) ?>">
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea name="description" id="description" class="form-control" rows="2"><?= htmlspecialchars($billing['description']) ?></textarea>
        </div>
        <div class="mb-3">
          <label for="invoice_number" class="form-label">Invoice #</label>
          <input type="text" name="invoice_number" id="invoice_number" class="form-control" value="<?= htmlspecialchars($billing['invoice_number']) ?>">
        </div>
        <div class="mb-3">
          <label for="notes" class="form-label">Notes</label>
          <textarea name="notes" id="notes" class="form-control" rows="2"><?= htmlspecialchars($billing['notes']) ?></textarea>
        </div>
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select name="status" id="status" class="form-select">
            <option value="Pending" <?= $billing['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Overdue" <?= $billing['status'] === 'Overdue' ? 'selected' : '' ?>>Overdue</option>
            <option value="Complete" <?= $billing['status'] === 'Complete' ? 'selected' : '' ?>>Complete</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div> 