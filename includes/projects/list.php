<?php

/* DB connection */
$conn = new mysqli("localhost", "root", "", "rapid_opms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* Get selected year & month */
$selected_year  = isset($_GET['year']) ? (int)$_GET['year'] : null;
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : null;

/* Get available years */
$years_query = "
    SELECT DISTINCT YEAR(date) AS year
    FROM projects
    WHERE deleted_at IS NULL
    ORDER BY year DESC
";
$years_result = $conn->query($years_query);
$available_years = [];

while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['year'];
}

/* Fetch projects */
$query = "
    SELECT p.*, 
           c.name AS customer_name, 
           c.company_name,
           (
             SELECT COUNT(*) 
             FROM billing b 
             WHERE b.project_id = p.id 
             AND b.status = 'Paid'
             AND b.deleted_at IS NULL
           ) AS paid_billings
    FROM projects p
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.deleted_at IS NULL
";

/* Apply filters */
if ($selected_year) {
    $query .= " AND YEAR(p.date) = $selected_year";
}

if ($selected_month) {
    $query .= " AND MONTH(p.date) = $selected_month";
}

$query .= " ORDER BY p.id DESC";

$result = $conn->query($query);

/* Month names */
$months = [
    1 => 'January', 2 => 'February', 3 => 'March',
    4 => 'April', 5 => 'May', 6 => 'June',
    7 => 'July', 8 => 'August', 9 => 'September',
    10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<div class="container py-4">

<?php if (!empty($_SESSION['success_projects'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success_projects']; unset($_SESSION['success_projects']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
<div class="col-md-12">

<div class="mb-4 px-4 py-3 rounded"
     style="background-color:#d8d8d882; box-shadow:0 2px 6px rgba(0,0,0,0.1);">

    <div class="d-flex justify-content-between align-items-center">

        <h4 class="mb-0">
            <i class="fa fa-tasks me-2"></i>Projects List
        </h4>

        <div class="d-flex align-items-center gap-2">

            <!-- MONTH FILTER -->
            <select id="monthFilter"
                    class="form-select form-select-sm"
                    style="width:auto;"
                    onchange="filterProjects()">
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
                    onchange="filterProjects()">
                <option value="">All Years</option>
                <?php foreach ($available_years as $year): ?>
                    <option value="<?= $year ?>"
                        <?= ($selected_year == $year) ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <a href="main.php?page=projects/create"
               class="btn btn-primary">
                <i class="fa fa-plus me-2"></i>Add Project
            </a>

        </div>
    </div>

<br>

<div class="table-responsive">
<table class="table table-sm table-bordered table-hover text-center align-middle">

<thead class="table-dark text-light small">
<tr>
    <th>ID</th>
    <th>Status</th>
    <th>Project</th>
    <th>Contractor / Customer</th>
    <th>Date</th>
    <th>Location</th>
    <th>Size</th>
    <th>Start</th>
    <th>Actions</th>
</tr>
</thead>

<tbody class="small">

<?php if ($result->num_rows > 0): ?>
<?php $counter = $result->num_rows; ?>

<?php while ($row = $result->fetch_assoc()): ?>
<tr data-href="main.php?page=projects/view&id=<?= $row['id'] ?>" style="cursor:pointer;">

<td><?= $counter-- ?></td>

<td>
<?php
$status = $row['status'] ?? 'Pending';
switch ($status){
    case 'Ongoing':
        echo '<i class="bi bi-tools text-warning"></i>';
        break;
    case 'Finished':
        echo '<i class="bi bi-check-circle-fill text-success"></i>';
        break;
    case 'Cancelled':
        echo '<i class="bi bi-x-circle-fill text-danger"></i>';
        break;
    default:
        echo '<span class="text-muted"></span>';
}
?>
</td>

<td><?= htmlspecialchars($row['name']) ?></td>

<td>
<?= htmlspecialchars($row['customer_name']) ?>
<?= $row['company_name'] ? ' ('.htmlspecialchars($row['company_name']).')' : '' ?>
</td>

<td><?= htmlspecialchars($row['date']) ?></td>
<td><?= htmlspecialchars($row['location']) ?></td>
<td><?= htmlspecialchars($row['size']) ?></td>
<td><?= htmlspecialchars($row['start_date']) ?></td>

<td>
<a href="main.php?page=projects/view&id=<?= $row['id'] ?>"
   class="btn btn-xs btn-outline-info me-1"
   title="View">
    <i class="bi bi-eye"></i>
</a>

<!-- EDIT = ALWAYS ENABLED -->
<a href="main.php?page=projects/edit&id=<?= $row['id'] ?>"
   class="btn btn-xs btn-outline-warning me-1"
   title="Edit">
    <i class="bi bi-pencil-square"></i>
</a>

<?php if ($row['paid_billings'] > 0): ?>
    <!-- DELETE LOCKED -->
    <button class="btn btn-xs btn-outline-secondary"
            disabled
            title="Cannot delete project with Paid billing">
        <i class="bi bi-lock-fill"></i>
    </button>
<?php else: ?>
    <!-- DELETE ENABLED -->
    <a href="includes/projects/delete.php?id=<?= $row['id'] ?>"
       class="btn btn-xs btn-outline-danger"
       onclick="return confirm('Are you sure you want to delete this project?')"
       title="Delete">
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
</div>
</div>

<script>
function filterProjects() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;

    let url = 'main.php?page=projects/list';
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
