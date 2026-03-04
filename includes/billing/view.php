<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No billing ID provided.</div>";
    exit;
}

$id = (int)$_GET['id'];

/* ===============================
   FETCH BILLING + PROJECT STATUS
================================ */
$stmt = $conn->prepare("
    SELECT 
        b.*, 
        p.name AS project_name,
        p.deleted_at AS project_deleted_at,
        c.name AS customer_name,
        CASE 
            WHEN p.deleted_at IS NOT NULL THEN 1 
            ELSE 0 
        END AS project_deleted
    FROM billing b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN customers c ON b.customer_id = c.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$billing = $result->fetch_assoc();
$stmt->close();

if (!$billing) {
    echo "<div class='alert alert-danger'>Billing record not found.</div>";
    exit;
}

$projectDeleted = (int)$billing['project_deleted'] === 1;

/* ===============================
   HANDLE MARK AS PAID ACTION
================================ */
if (isset($_GET['mark_paid']) && !$projectDeleted && $billing['status'] !== 'Paid') {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        UPDATE billing 
        SET status = 'Paid', paid_date = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $today, $id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['success_billing'] = "Billing marked as Paid.";
    header("Location: main.php?page=billing/view&id=$id");
    exit;
}
?>

<div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
      <h4 class="mb-0">
        <i class="fa fa-eye me-2"></i>View Billing Details
      </h4>
      <a href="main.php?page=billing/list" class="btn btn-primary">
        <i class="fa fa-arrow-left me-2"></i>Back to List
      </a>
    </div>

    <?php if ($projectDeleted): ?>
      <div class="alert alert-danger rounded-0 mb-0">
        <i class="fa fa-exclamation-triangle me-2"></i>
        This billing is linked to a <strong>deleted project</strong>.  
        Mark as Paid is disabled.
      </div>
    <?php endif; ?>

    <table class="table table-sm table-bordered rounded mb-0">

      <tr>
        <th style="width: 250px;" class="ps-3">Status</th>
        <td class="ps-3">
          <?php
            $today = date('Y-m-d');
            $status = $billing['status'] ?? 'Pending';

            if ($status === 'Paid') {
              echo '<span class="badge bg-success">Paid</span>';
            } elseif (!empty($billing['due_date']) && $billing['due_date'] < $today) {
              echo '<span class="badge bg-danger">Overdue</span>';
            } else {
              echo '<span class="badge bg-warning text-dark">Pending</span>';
            }
          ?>

          <?php if ($status !== 'Paid' && !$projectDeleted): ?>
              <a href="main.php?page=billing/view&id=<?= $billing['id'] ?>&mark_paid=1"
                 class="btn btn-sm btn-success ms-2"
                 onclick="return confirm('Mark this billing as paid?')">
                <i class="fa fa-check"></i> Mark as Paid
              </a>
          <?php elseif ($projectDeleted): ?>
              <button class="btn btn-sm btn-secondary ms-2" disabled
                      title="Cannot mark as paid because project is deleted">
                <i class="fa fa-lock me-1"></i> Mark as Paid
              </button>
          <?php endif; ?>
        </td>
      </tr>

      <tr>
        <th class="ps-3">Paid Date</th>
        <td class="ps-3"><?= htmlspecialchars($billing['paid_date'] ?? 'N/A') ?></td>
      </tr>

      <tr>
        <th class="ps-3">Customer</th>
        <td class="ps-3"><?= htmlspecialchars($billing['customer_name'] ?? '') ?></td>
      </tr>

      <tr>
        <th class="ps-3">Project Name</th>
        <td class="ps-3">
          <?php if (!empty($billing['project_id']) && !$projectDeleted): ?>
              <a href="main.php?page=projects/view&id=<?= $billing['project_id'] ?>"
                class="text-decoration-none fw-bold">
                  <?= htmlspecialchars($billing['project_name'] ?? 'N/A') ?>
              </a>
          <?php elseif ($projectDeleted): ?>
              <?= htmlspecialchars($billing['project_name'] ?? 'N/A') ?>
              <span class="badge bg-danger ms-2">Deleted</span>
          <?php else: ?>
              -
          <?php endif; ?>
        </td>
      </tr>

      <tr>
        <th class="ps-3">Invoice No.</th>
        <td class="ps-3"><?= htmlspecialchars($billing['invoice_number'] ?? '') ?></td>
      </tr>

      <!-- ✅ NEW P.O No -->
      <tr>
        <th class="ps-3">P.O No.</th>
        <td class="ps-3">
          <?= !empty($billing['po_number']) 
                ? htmlspecialchars($billing['po_number']) 
                : 'N/A' ?>
        </td>
      </tr>

      <tr>
        <th class="ps-3">Amount</th>
        <td class="ps-3">₱<?= number_format($billing['amount'] ?? 0, 2) ?></td>
      </tr>

      <tr>
        <th class="ps-3">Billing Date</th>
        <td class="ps-3"><?= htmlspecialchars($billing['billing_date'] ?? '') ?></td>
      </tr>

      <tr>
        <th class="ps-3">Due Date</th>
        <td class="ps-3"><?= htmlspecialchars($billing['due_date'] ?? '') ?></td>
      </tr>

      <tr>
        <th class="ps-3">Description</th>
        <td class="ps-3"><?= nl2br(htmlspecialchars($billing['description'] ?? '')) ?></td>
      </tr>

      <tr>
        <th class="ps-3">Created At</th>
        <td class="ps-3"><?= htmlspecialchars($billing['created_at'] ?? '') ?></td>
      </tr>

      <tr>
        <th class="ps-3">Notes</th>
        <td class="ps-3"><?= nl2br(htmlspecialchars($billing['notes'] ?? '')) ?></td>
      </tr>

    </table>
  </div>
</div>