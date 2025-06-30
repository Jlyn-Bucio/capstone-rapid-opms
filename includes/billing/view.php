<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No billing ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
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
      <h5 class="mb-0"><i class="fa fa-file-invoice-dollar me-2"></i>Billing Details: #<?= $billing['id'] ?></h5>
      <a href="main.php?page=billing/list" class="btn btn-primary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <table class="table table-bordered">
        <tr><th>Status</th><td>
          <?php
            $today = date('Y-m-d');
            $status = $billing['status'];
            if ($status === 'Complete') {
              echo '<span class="badge bg-success">Complete</span>';
            } elseif ($billing['due_date'] && $billing['due_date'] < $today) {
              echo '<span class="badge bg-danger">Overdue</span>';
            } else {
              echo '<span class="badge bg-warning text-dark">Pending</span>';
            }
          ?>
          <?php if ($billing['status'] !== 'Complete'): ?>
            <a href="includes/billing/mark_complete.php?id=<?= $billing['id'] ?>" class="btn btn-sm btn-success ms-2" onclick="return confirm('Mark this billing as complete?')">
              <i class="fa fa-check"></i> Mark as Complete
            </a>
          <?php endif; ?>
        </td></tr>
        <tr><th>Customer ID</th><td><?= htmlspecialchars($billing['customer_id']) ?></td></tr>
        <tr><th>Project ID</th><td><?= htmlspecialchars($billing['project_id']) ?></td></tr>
        <tr><th>Amount</th><td>₱<?= number_format($billing['amount'], 2) ?></td></tr>
        <tr><th>Billing Date</th><td><?= htmlspecialchars($billing['billing_date']) ?></td></tr>
        <tr><th>Due Date</th><td><?= htmlspecialchars($billing['due_date']) ?></td></tr>
        <tr><th>Description</th><td><?= nl2br(htmlspecialchars($billing['description'])) ?></td></tr>
        <tr><th>Created At</th><td><?= htmlspecialchars($billing['created_at']) ?></td></tr>
        <tr><th>Invoice #</th><td><?= htmlspecialchars($billing['invoice_number']) ?></td></tr>
        <tr><th>Notes</th><td><?= nl2br(htmlspecialchars($billing['notes'])) ?></td></tr>
      </table>
    </div>
  </div>
</div> 