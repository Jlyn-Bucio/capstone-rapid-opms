<?php
include_once __DIR__ . '/../rapid_opms.php';
date_default_timezone_set('Asia/Manila');

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$billing_id = isset($_GET['billing_id']) ? (int)$_GET['billing_id'] : 0;

if (!$project_id || !$billing_id) {
    echo "<div class='alert alert-danger'>Invalid Project or Billing</div>";
    return;
}

/* Fetch Billing info */
$stmt = $conn->prepare("SELECT * FROM billing WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $billing_id);
$stmt->execute();
$billing = $stmt->get_result()->fetch_assoc();

if (!$billing) {
    echo "<div class='alert alert-danger'>Billing not found.</div>";
    return;
}

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $reference = $_POST['reference_number'] ?? null;
    $notes = $_POST['notes'] ?? null;

    $stmt = $conn->prepare("
        INSERT INTO payments (project_id, billing_id, amount, payment_date, reference_number, notes)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iidsss", $project_id, $billing_id, $amount, $payment_date, $reference, $notes);
    $stmt->execute();

    /* Optional: Update Billing status */
    $stmt2 = $conn->prepare("
        SELECT IFNULL(SUM(amount),0) AS paid
        FROM payments
        WHERE billing_id = ?
    ");
    $stmt2->bind_param("i", $billing_id);
    $stmt2->execute();
    $paid_data = $stmt2->get_result()->fetch_assoc();
    $paid = $paid_data['paid'];

    if ($paid >= $billing['amount']) {
        $status = "Paid";
    } elseif ($paid > 0) {
        $status = "Partial";
    } else {
        $status = "Unpaid";
    }

    $stmt3 = $conn->prepare("UPDATE billing SET status = ? WHERE id = ?");
    $stmt3->bind_param("si", $status, $billing_id);
    $stmt3->execute();

    echo "<div class='alert alert-success'>Payment recorded successfully!</div>";
}
?>

<div class="container py-4">
    <h4>Add Payment for Invoice: <?= htmlspecialchars($billing['invoice_number']) ?></h4>
    <form method="POST" class="mt-3">
        <div class="mb-2">
            <label>Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" required
                   max="<?= $billing['amount'] ?>">
        </div>
        <div class="mb-2">
            <label>Payment Date</label>
            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="mb-2">
            <label>Reference Number</label>
            <input type="text" name="reference_number" class="form-control">
        </div>
        <div class="mb-2">
            <label>Notes</label>
            <textarea name="notes" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Save Payment</button>
        <a href="main.php?page=billing/view&id=<?= $billing_id ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>
