<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

/* ===============================
   VALIDATE ID
================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid billing ID.</div>";
    exit;
}

$id = (int)$_GET['id'];

/* ===============================
   FETCH BILLING + PROJECT STATUS
================================ */
$stmt = $conn->prepare("
    SELECT 
        b.*,
        p.deleted_at AS project_deleted
    FROM billing b
    LEFT JOIN projects p ON p.id = b.project_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$billing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$billing) {
    echo "<div class='alert alert-danger'>Billing not found.</div>";
    exit;
}

$isProjectDeleted = !empty($billing['project_deleted']);


/* ======================================================
   ===== NEW ===== ADD PAYMENT (SEPARATE FORM)
====================================================== */
if (isset($_POST['add_payment']) && !$isProjectDeleted) {

    $payment_date = $_POST['payment_date'];
    $amount       = str_replace(',', '', $_POST['payment_amount']);
    $mode_of_payment = $_POST['mode_of_payment'];
    $notes        = trim($_POST['notes']);

    if ($amount > 0) {

        $insert = $conn->prepare("
            INSERT INTO payments 
            (billing_id, payment_date, amount, mode_of_payment, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert->bind_param("isdss", $id, $payment_date, $amount, $mode_of_payment, $notes);
        $insert->execute();
        $insert->close();

        // recompute total paid
        $sum = $conn->prepare("
            SELECT IFNULL(SUM(amount),0) as total_paid 
            FROM payments 
            WHERE billing_id=?
        ");
        $sum->bind_param("i", $id);
        $sum->execute();
        $total_paid = $sum->get_result()->fetch_assoc()['total_paid'];
        $sum->close();

        $balance = $billing['amount'] - $total_paid;

        // auto update status
        if ($balance <= 0) {
            $conn->query("UPDATE billing SET status='Paid' WHERE id=$id");
        }

        header("Location: main.php?page=billing/edit&id=$id");
        exit;
    }
}


/* ===============================
   GET TOTAL PAID
================================ */
$paidQuery = $conn->prepare("
    SELECT IFNULL(SUM(amount),0) as total_paid 
    FROM payments 
    WHERE billing_id = ?
");
$paidQuery->bind_param("i", $id);
$paidQuery->execute();
$paidResult = $paidQuery->get_result()->fetch_assoc();
$total_paid = $paidResult['total_paid'];
$paidQuery->close();

$balance = $billing['amount'] - $total_paid;


/* ===============================
   UPDATE BILLING INFO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_payment']) && !$isProjectDeleted) {

    // --- Get submitted values
    $amount       = str_replace(',', '', $_POST['amount']);
    $billing_date = $_POST['billing_date'];
    $due_date     = $_POST['due_date'];
    $invoice_no   = trim($_POST['invoice_number']);
    $po_number    = trim($_POST['po_number']);
    $description  = trim($_POST['description']);
    $status       = $_POST['status'];

    // --- Fetch old billing data
    $old_stmt = $conn->prepare("SELECT * FROM billing WHERE id = ?");
    $old_stmt->bind_param("i", $id);
    $old_stmt->execute();
    $old_billing = $old_stmt->get_result()->fetch_assoc();
    $old_stmt->close();

    // --- Update billing
    $stmt = $conn->prepare("
        UPDATE billing SET
            amount = ?,
            billing_date = ?,
            due_date = ?,
            invoice_number = ?,
            po_number = ?,
            description = ?,
            status = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "dssssssi",
        $amount,
        $billing_date,
        $due_date,
        $invoice_no,
        $po_number,
        $description,
        $status,
        $id
    );

    if ($stmt->execute()) {

        // --- Fetch project name
        $proj_stmt = $conn->prepare("SELECT name FROM projects WHERE id = ?");
        $proj_stmt->bind_param("i", $old_billing['project_id']);
        $proj_stmt->execute();
        $proj_stmt->bind_result($project_name);
        $proj_stmt->fetch();
        $proj_stmt->close();

        // --- Build audit description only for modified fields
        $fields = [
            'amount' => [$old_billing['amount'], $amount],
            'billing_date'   => [$old_billing['billing_date'], $billing_date],
            'due_date' => [$old_billing['due_date'], $due_date],
            'invoice_number' => [$old_billing['invoice_number'], $invoice_no],
            'po_number' => [$old_billing['po_number'], $po_number],
            'description' => [$old_billing['description'], $description],
            'status' => [$old_billing['status'], $status]
        ];

        $changes = [];
        foreach ($fields as $field => [$old, $new]) {
            if ((string)$old !== (string)$new) {
                $changes[] = "[$field: {$old} → {$new}]";
            }
        }

        if (!empty($changes)) {
            $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $audit_desc = "Edited Billing (ID: $id) for project '{$project_id}' by '{$admin_name}': "
                . implode(', ', $changes);

            $audit->log('UPDATE', 'Billing', $audit_desc);
        }
        // else: nothing changed → no audit log
    }

    $stmt->close();
    header("Location: main.php?page=billing/edit&id=$id");
    exit;
}
?>

<div class="container py-4">
  <div class="card">
    <div class="card-body">

      <!-- ===============================
          BILLING EDIT FORM
      ================================ -->
      <form method="POST" class="row g-3">

        <div class="col-md-2">
          <label class="form-label fw-bold">Amount: <span class="text-danger">*</span></label>
          <input type="text" name="amount"
                class="form-control"
                value="<?= number_format($billing['amount'],2) ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">A.R. No: <span class="text-danger">*</span></label>
          <input type="text" name="invoice_number"
                class="form-control"
                value="<?= htmlspecialchars($billing['invoice_number']) ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">P.O No:</label>
          <input type="text" name="po_number"
                class="form-control"
                value="<?= htmlspecialchars($billing['po_number']) ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Billing Date: <span class="text-danger">*</span></label>
          <input type="date" name="billing_date"
                class="form-control"
                value="<?= $billing['billing_date'] ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Due Date: <span class="text-danger">*</span></label>
          <input type="date" name="due_date"
                class="form-control"
                value="<?= $billing['due_date'] ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label fw-bold">Status: <span class="text-danger">*</span></label>
          <select name="status" class="form-select">
            <option value="Pending" <?= $billing['status']=='Pending'?'selected':'' ?>>Pending</option>
            <option value="Overdue" <?= $billing['status']=='Overdue'?'selected':'' ?>>Overdue</option>
            <option value="Paid" <?= $billing['status']=='Paid'?'selected':'' ?>>Paid</option>
            <option value="Cancelled" <?= $billing['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label fw-bold">Description</label>
          <textarea name="description"
                    class="form-control"
                    rows="2"><?= htmlspecialchars($billing['description']) ?></textarea>
        </div>

        <div class="col-12 text-end mt-3">
          <a href="main.php?page=billing/list" class="btn btn-secondary">Cancel</a>
          <button class="btn btn-success" type="submit" id="updateBtn" disabled>Update</button>
        </div>
      </form>

      <hr>

      <!-- ===============================
          BILLING SUMMARY
      ================================ -->
      <div class="row mb-4">
          <div class="col-md-4">
              <strong>Total Amount</strong><br>
              ₱ <?= number_format($billing['amount'],2) ?>
          </div>
          <div class="col-md-4">
              <strong>Total Paid</strong><br>
              <span class="text-success">
                  ₱ <?= number_format($total_paid,2) ?>
              </span>
          </div>
          <div class="col-md-4">
              <strong>Balance</strong><br>
              <span class="<?= $balance <= 0 ? 'text-success' : 'text-danger' ?>">
                  ₱ <?= number_format($balance,2) ?>
              </span>
          </div>
      </div>
      <br><br>

      <!-- ======================================================
         ===== NEW ===== ADD PAYMENT FORM
      ====================================================== -->

      <h5 class="fw-bold">Add Payment</h5>

      <form method="POST" class="row g-3">
        <input type="hidden" name="add_payment" value="1">

        <div class="col-md-3">
          <label class="form-label">Payment Date</label>
          <input type="date" name="payment_date" class="form-control" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Amount</label>
          <input type="text" name="payment_amount" class="form-control" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Mode of Payment</label>
            <select name="mode_of_payment" class="form-select" required>
                <option value="">Select Mode</option>
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="Bank">Bank</option>
            </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Notes</label>
          <input type="text" name="notes" class="form-control">
        </div>

        <div class="col-12 text-end">
          <button type="submit" class="btn btn-primary">
          Add Payment
          </button>
        </div>
      </form>

      <!-- ======================================================
          ===== NEW ===== PAYMENT HISTORY TABLE
      ====================================================== -->
      <hr>
      <h5 class="fw-bold">Payment History</h5>

      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Mode of Payment</th>
            <th class="text-end">Amount</th>
            <th>Notes</th>
          </tr>
        </thead>

        <tbody>
          <?php
          $payments = $conn->prepare("
          SELECT * FROM payments
          WHERE billing_id = ?
          ORDER BY payment_date ASC
          ");
          $payments->bind_param("i", $id);
          $payments->execute();
          $resultPayments = $payments->get_result();

          if ($resultPayments->num_rows > 0):
          while($pay = $resultPayments->fetch_assoc()):
          ?>

          <tr>
            <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
            <td><?= htmlspecialchars($pay['mode_of_payment']) ?></td>
            <td class="text-end text-success">
              ₱ <?= number_format($pay['amount'],2) ?>
            </td>
            <td><?= htmlspecialchars($pay['notes']) ?></td>
          </tr>
          <?php endwhile; else: ?>
          <tr>
            <td colspan="4" class="text-center text-muted">
              No payments yet
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const updateBtn = document.getElementById('updateBtn');

    const originalValues = {};

    // SAME STYLE AS PROJECTS
    const fieldsToWatch = [
        'amount',
        'invoice_number',
        'po_number',
        'billing_date',
        'due_date',
        'status',
        'description'
    ];

    function cleanValue(name, value) {
        if (name === 'amount') {
            return (value || '').replace(/,/g,'').trim();
        }
        return (value || '').trim();
    }

    function storeOriginalValues() {
        fieldsToWatch.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el) {
                originalValues[name] = cleanValue(name, el.value);
            }
        });
    }

    function checkChanges() {
        let hasChanges = false;

        fieldsToWatch.forEach(name => {
            const el = document.querySelector(`[name="${name}"]`);
            if (!el) return;

            const currentValue = cleanValue(name, el.value);

            if (currentValue !== originalValues[name]) {
                hasChanges = true;
            }
        });

        updateBtn.disabled = !hasChanges;
    }

    // Format amount input (same style handling)
    const amountInput = document.querySelector('[name="amount"]');
    if (amountInput) {
        amountInput.addEventListener('input', () => {
            let raw = amountInput.value.replace(/,/g,'');
            if(!/^\d*\.?\d*$/.test(raw)) return;

            const num = parseFloat(raw);
            if (!isNaN(num)) {
                amountInput.value = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            checkChanges();
        });
    }

    // Store original values AFTER formatting
    setTimeout(() => {
        storeOriginalValues();
        checkChanges();
    }, 200);

    fieldsToWatch.forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        if (el) {
            el.addEventListener('input', checkChanges);
            el.addEventListener('change', checkChanges);
        }
    });

});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const paymentAmountInput = document.querySelector('[name="payment_amount"]');

    if (paymentAmountInput) {
        paymentAmountInput.addEventListener('input', () => {
            let raw = paymentAmountInput.value.replace(/,/g,'');

            if(!/^\d*\.?\d*$/.test(raw)) return;

            const num = parseFloat(raw);
            if (!isNaN(num)) {
                paymentAmountInput.value = num.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });
    }

});
</script>