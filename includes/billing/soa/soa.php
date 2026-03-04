<?php
include_once __DIR__ . '/../rapid_opms.php';
date_default_timezone_set('Asia/Manila');

if (!isset($_GET['project_id'])) {
    die("Invalid Project");
}

$project_id = (int)$_GET['project_id'];

/* =========================
   GET PROJECT + CUSTOMER
========================= */
$stmt = $conn->prepare("
    SELECT p.name AS project_name,
           p.start_date,
           c.name AS customer_name
    FROM projects p
    JOIN customers c ON c.id = p.customer_id
    WHERE p.id = ? AND p.deleted_at IS NULL
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("Project not found.");
}

/* =========================
   GET BILLINGS
========================= */
$stmt = $conn->prepare("
    SELECT b.id, b.billing_date, b.description, b.amount, b.invoice_number
    FROM billing b
    WHERE b.project_id = ? AND b.deleted_at IS NULL
    ORDER BY b.billing_date ASC
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$billings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   GET PAYMENTS
========================= */
$stmt = $conn->prepare("
    SELECT p.billing_id, p.payment_date, p.amount, p.reference_number
    FROM payments p
    WHERE p.project_id = ?
    ORDER BY p.payment_date ASC
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$payments_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Organize payments per billing */
$payments = [];
foreach ($payments_data as $pay) {
    $payments[$pay['billing_id']][] = $pay;
}

/* =========================
   TOTALS
========================= */
$total_billing = 0;
$total_paid = 0;
foreach ($billings as $b) {
    $total_billing += $b['amount'];
    if (isset($payments[$b['id']])) {
        foreach ($payments[$b['id']] as $p) $total_paid += $p['amount'];
    }
}
$balance = $total_billing - $total_paid;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Statement of Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; }
        .soa-container { max-width: 900px; margin: auto; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-name { font-size: 22px; font-weight: bold; }
        .print-btn { margin-bottom: 20px; }
        @media print { .print-btn { display: none; } }
        ul.payments { padding-left: 1rem; margin-bottom: 0; list-style-type: disc; }
    </style>
</head>
<body>

<div class="soa-container">

    <div class="print-btn text-end">
        <button onclick="window.print()" class="btn btn-dark btn-sm">Print SOA</button>
    </div>

    <div class="header">
        <div class="company-name">YOUR COMPANY NAME</div>
        <div>Company Address Here</div>
        <div>TIN: 000-000-000</div>
        <hr>
        <h4 class="mt-3">STATEMENT OF ACCOUNT</h4>
    </div>

    <div class="row mb-4">
        <div class="col-6">
            <strong>Customer:</strong> <?= htmlspecialchars($project['customer_name']) ?><br>
            <strong>Project:</strong> <?= htmlspecialchars($project['project_name']) ?><br>
        </div>
        <div class="col-6 text-end">
            <strong>Date Generated:</strong> <?= date('M d, Y') ?>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th width="15%">Invoice No.</th>
                <th width="15%">Date</th>
                <th>Description</th>
                <th width="20%" class="text-end">Amount</th>
                <th width="25%">Payments</th>
                <th width="15%" class="text-end">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($billings)): ?>
                <?php foreach ($billings as $b): 
                    $paid = 0;
                    $payment_list = isset($payments[$b['id']]) ? $payments[$b['id']] : [];
                    foreach ($payment_list as $p) $paid += $p['amount'];
                    $bill_balance = $b['amount'] - $paid;
                ?>
                <tr>
                    <td><?= htmlspecialchars($b['invoice_number'] ?? '-') ?></td>
                    <td><?= date('M d, Y', strtotime($b['billing_date'])) ?></td>
                    <td><?= htmlspecialchars($b['description']) ?></td>
                    <td class="text-end">₱<?= number_format($b['amount'],2) ?></td>
                    <td>
                        <?php if (!empty($payment_list)): ?>
                            <ul class="payments">
                                <?php foreach ($payment_list as $p): ?>
                                    <li><?= date('M d, Y', strtotime($p['payment_date'])) ?> - ₱<?= number_format($p['amount'],2) ?> (<?= htmlspecialchars($p['reference_number']) ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-end">₱<?= number_format($bill_balance,2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No billing records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>

        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total Billing</th>
                <th class="text-end">₱<?= number_format($total_billing,2) ?></th>
                <th class="text-end">₱<?= number_format($total_paid,2) ?></th>
                <th class="text-end">₱<?= number_format($balance,2) ?></th>
            </tr>
        </tfoot>
    </table>

</div>

</body>
</html>
