<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../rapid_opms.php';

/* =========================
   GET SELECTED YEAR & MONTH
========================= */
$selected_year = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : null;

$selected_month = isset($_GET['month']) && $_GET['month'] !== ''
    ? (int)$_GET['month']
    : null;

/* =========================
   FETCH AVAILABLE YEARS
========================= */
$years_query = "
    SELECT DISTINCT YEAR(created_at) AS year
    FROM suppliers
    WHERE deleted_at IS NULL
    ORDER BY year DESC
";
$years_result = $conn->query($years_query);

$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}

/* =========================
   MONTH NAMES
========================= */
$months = [
    1 => 'January', 2 => 'February', 3 => 'March',
    4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September',
    10 => 'October', 11 => 'November', 12 => 'December'
];

/* =========================
   FETCH SUPPLIERS (SOFT DELETE)
========================= */
$query = "SELECT * FROM suppliers WHERE deleted_at IS NULL";

if ($selected_year) {
    $query .= " AND YEAR(created_at) = " . $selected_year;
}

if ($selected_month) {
    $query .= " AND MONTH(created_at) = " . $selected_month;
}

/* DESCENDING ID */
$query .= " ORDER BY id DESC";

$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-12">

        <?php if (!empty($_SESSION['success_supplier'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success_supplier'] ?>
        </div>
        <?php unset($_SESSION['success_supplier']); ?>
        <?php endif; ?>

            <div class="mb-4 px-4 py-3 rounded"
                 style="background-color:#d8d8d882; box-shadow:0 2px 6px rgba(0,0,0,.1);">

                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>Supplier List
                    </h4>

                    <div class="d-flex align-items-center gap-2">
                        

                        <!-- MONTH FILTER -->
                        <select id="monthFilter" class="form-select form-select-sm" style="width:auto;" onchange="filterSuppliers()">
                            <option value="">All Months</option>
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- YEAR FILTER -->
                        <select id="yearFilter" class="form-select form-select-sm" style="width:auto;" onchange="filterSuppliers()">
                            <option value="">All Years</option>
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <a href="main.php?page=suppliers/create" class="btn btn-primary">
                            <i class="fa fa-plus me-2"></i>Add Supplier
                        </a>
                    </div>
                </div>

                <div class="card-body pt-0"><br>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover text-center align-middle table-bordered">
                            <thead class="table-dark text-light small">
                                <tr>
                                    <th>#</th>
                                    <th>Supplier's Name</th>
                                    <th>Contact Person</th>
                                    <th>Email</th>
                                    <th>Contact Number</th>
                                    <th>Address</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody class="small">
                            <?php if ($result->num_rows > 0): ?>
                                <?php $counter = $result->num_rows; ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="clickable-row"
                                        data-href="main.php?page=suppliers/view&id=<?= $row['id'] ?>"
                                        style="cursor:pointer;">

                                        <td><?= $counter-- ?></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_person']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td><?= htmlspecialchars($row['phone']) ?></td>
                                        <td><?= htmlspecialchars($row['address']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>

                                        <td>
                                            <a href="main.php?page=suppliers/view&id=<?= $row['id'] ?>"
                                               class="btn btn-xs btn-outline-info me-1"
                                               title="View"
                                               onclick="event.stopPropagation();">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="main.php?page=suppliers/edit&id=<?= $row['id'] ?>"
                                               class="btn btn-xs btn-outline-warning me-1"
                                               title="Edit"
                                               onclick="event.stopPropagation();">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <a href="includes/suppliers/delete.php?id=<?= $row['id'] ?>"
                                               class="btn btn-xs btn-outline-danger"
                                               title="Delete"
                                               onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this supplier?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Empty List</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>

                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function filterSuppliers() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    let url = new URL(window.location.href);

    if (year) {
        url.searchParams.set('year', year);
    } else {
        url.searchParams.delete('year');
    }

    if (month) {
        url.searchParams.set('month', month);
    } else {
        url.searchParams.delete('month');
    }

    window.location.href = url.toString();
}

// ROW CLICK → VIEW
document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', function () {
        window.location.href = this.dataset.href;
    });
});
</script>
