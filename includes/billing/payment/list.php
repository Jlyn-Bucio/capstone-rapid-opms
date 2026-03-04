<?php
include_once __DIR__ . '/../rapid_opms.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$result = $conn->query("
    SELECT p.*, b.invoice_number
    FROM payments p
    LEFT JOIN billing b ON p.billing_id = b.id
    WHERE project_id = $project_id
    ORDER BY payment_date DESC
");
?>

<div class="container py-4">
    <h4>Payments for Project #<?= $project_id ?></h4>
    <table class="table table-bordered table-sm mt-3">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Reference</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                <td>₱<?= number_format($row['amount'],2) ?></td>
                <td><?= $row['payment_date'] ?></td>
                <td><?= htmlspecialchars($row['reference_number']) ?></td>
                <td><?= htmlspecialchars($row['notes']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
