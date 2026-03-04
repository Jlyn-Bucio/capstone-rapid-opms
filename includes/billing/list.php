<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

/* ===============================
   YEAR + MONTH FILTER
================================ */
$selected_year  = isset($_GET['year']) ? (int)$_GET['year'] : null;
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : null;

/* Get available years */
$years_query = "
    SELECT DISTINCT YEAR(billing_date) AS year
    FROM billing
    WHERE deleted_at IS NULL
    ORDER BY year DESC
";
$years_result = $conn->query($years_query);
$available_years = [];

while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['year'];
}

/* Month names */
$months = [
    1 => 'January', 2 => 'February', 3 => 'March',
    4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September',
    10 => 'October', 11 => 'November', 12 => 'December'
];

/* ===============================
   FETCH BILLING + PROJECT STATUS
================================ */
$sql = "
    SELECT 
        b.id, 
        b.project_id,
        b.invoice_number, 
        b.po_number,            -- ✅ NEW
        b.amount, 
        b.billing_date,
        b.due_date,
        b.status,
        p.name AS project_name,
        p.deleted_at AS project_deleted,
        c.name AS customer_name
    FROM billing b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN customers c ON b.customer_id = c.id
    WHERE b.deleted_at IS NULL
";

/* Apply filters */
if ($selected_year) {
    $sql .= " AND YEAR(b.billing_date) = $selected_year";
}

if ($selected_month) {
    $sql .= " AND MONTH(b.billing_date) = $selected_month";
}

$sql .= " ORDER BY b.id DESC";

$result = $conn->query($sql);
?>

<div class="container py-4">
<div class="mb-4 px-4 py-3 rounded" style="background:#d8d8d882;">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fa fa-edit me-2"></i>Billing List</h4>

    <div class="d-flex gap-2 align-items-center">

        <!-- MONTH FILTER -->
        <select id="monthFilter"
                class="form-select form-select-sm"
                style="width:auto;"
                onchange="filterBilling()">
            <option value="">All Months</option>
            <?php foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>"
                    <?= ($selected_month == $num) ? 'selected' : '' ?>>
                    <?= $name ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- YEAR FILTER -->
        <select id="yearFilter"
                class="form-select form-select-sm"
                style="width:auto;"
                onchange="filterBilling()">
            <option value="">All Years</option>
            <?php foreach ($available_years as $year): ?>
                <option value="<?= $year ?>"
                    <?= ($selected_year == $year) ? 'selected' : '' ?>>
                    <?= $year ?>
                </option>
            <?php endforeach; ?>
        </select>

        <a href="main.php?page=billing/create" class="btn btn-primary">
            <i class="fa fa-plus me-2"></i>Add Billing
        </a>

    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover table-bordered text-center align-middle">

      <thead class="table-dark small">
        <tr>
          <th>#</th>
          <th>Paid</th>
          <th>Invoice No.</th>
          <th>P.O No.</th> <!-- ✅ NEW -->
          <th>Project</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Billing Date</th>
          <th>Actions</th>
        </tr>
      </thead>

      <tbody class="small">
        <?php if ($result && $result->num_rows): ?>
            <?php $i = $result->num_rows; ?>
            <?php while ($row = $result->fetch_assoc()): ?>

            <?php
            $projectDeleted = !empty($row['project_deleted']);
            $rowClass = $projectDeleted ? 'table-danger' : '';
            ?>

            <tr class="<?= $rowClass ?>"
                data-href="main.php?page=billing/view&id=<?= $row['id'] ?>"
                style="cursor:pointer;">

                <td><?= $i-- ?></td>

                <td>
                    <?php
                    if ($row['status'] === 'Paid') {
                        echo '<i class="bi bi-check-circle-fill text-success"></i>';
                    } elseif ($row['status'] === 'Overdue') {
                        echo '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
                    }
                    ?>
                </td>

                <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                <td><?= htmlspecialchars($row['po_number'] ?? '-') ?></td> <!-- ✅ NEW -->
                <td><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
                <td>₱<?= number_format($row['amount'], 2) ?></td>
                <td><?= $row['billing_date'] ?></td>

                <td>

                    <!-- VIEW -->
                    <a href="main.php?page=billing/view&id=<?= $row['id'] ?>"
                       class="btn btn-xs btn-outline-info me-1">
                        <i class="bi bi-eye"></i>
                    </a>

                    <!-- SOA -->
                    <?php if (!empty($row['project_id'])): ?>
                        <a href="main.php?page=billing/soa/view&project_id=<?= $row['project_id'] ?>"
                           class="btn btn-xs btn-outline-primary me-1"
                           title="Generate SOA">
                            <i class="bi bi-receipt"></i>
                        </a>
                    <?php endif; ?>

                    <!-- EDIT -->
                    <?php if ($projectDeleted): ?>
                        <button class="btn btn-xs btn-outline-secondary me-1"
                                disabled title="Project deleted">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                    <?php else: ?>
                        <a href="main.php?page=billing/edit&id=<?= $row['id'] ?>"
                           class="btn btn-xs btn-outline-warning me-1">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    <?php endif; ?>

                    <!-- DELETE -->
                    <?php if ($row['status'] === 'Paid'): ?>
                        <button class="btn btn-xs btn-outline-secondary"
                                disabled title="Paid billing cannot be deleted">
                            <i class="bi bi-lock-fill"></i>
                        </button>
                    <?php else: ?>
                        <a href="includes/billing/delete.php?id=<?= $row['id'] ?>"
                           class="btn btn-xs btn-outline-danger"
                           onclick="return confirm('Delete this billing record?');">
                            <i class="bi bi-trash"></i>
                        </a>
                    <?php endif; ?>

                </td>
            </tr>

            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" class="text-center text-muted">
                    Empty List
                </td>
            </tr>
        <?php endif; ?>
      </tbody>

    </table>
  </div>
</div>
</div>

<script>
function filterBilling() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;

    let url = 'main.php?page=billing/list';
    let params = [];

    if (year) params.push('year=' + year);
    if (month) params.push('month=' + month);

    if (params.length > 0) {
        url += '&' + params.join('&');
    }

    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tbody tr[data-href]').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (!e.target.closest('a, button, i')) {
                window.location.href = row.getAttribute('data-href');
            }
        });
    });
});
</script>