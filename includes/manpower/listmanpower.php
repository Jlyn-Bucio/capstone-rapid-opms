<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../rapid_opms.php';

/* ======================================================
   ENSURE MANPOWER TABLE EXISTS
====================================================== */
$conn->query("
    CREATE TABLE IF NOT EXISTS manpower (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year INT NOT NULL DEFAULT YEAR(CURDATE()),
        month INT NOT NULL DEFAULT MONTH(CURDATE()),
        name VARCHAR(255) NOT NULL DEFAULT '',
        position VARCHAR(255) NOT NULL,
        old_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
        meal_allowance DECIMAL(12,2) NOT NULL DEFAULT 0,
        new_rate DECIMAL(12,2) NOT NULL DEFAULT 0,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
") or die($conn->error);

/* ======================================================
   GET SELECTED YEAR & MONTH
====================================================== */
$selected_year = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : null;

$selected_month = isset($_GET['month']) && $_GET['month'] !== ''
    ? (int)$_GET['month']
    : null;

/* ======================================================
   FETCH AVAILABLE YEARS & MONTHS (NON-DELETED)
====================================================== */
$available_years = [];
$year_stmt = $conn->prepare("
    SELECT DISTINCT year 
    FROM manpower 
    WHERE deleted_at IS NULL 
    ORDER BY year DESC
");
$year_stmt->execute();
$year_result = $year_stmt->get_result();
while ($row = $year_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}
$year_stmt->close();

// Month names
$months = [
    1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
];

/* ======================================================
   FETCH MANPOWER LIST (SOFT DELETE SAFE)
====================================================== */
$sql = "SELECT * FROM manpower WHERE deleted_at IS NULL";
$params = [];
$types = "";

if ($selected_year !== null) {
    $sql .= " AND year = ?";
    $params[] = $selected_year;
    $types .= "i";
}

if ($selected_month !== null) {
    $sql .= " AND month = ?";
    $params[] = $selected_month;
    $types .= "i";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container py-4">

<?php if (!empty($_SESSION['success_manpower'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['success_manpower']; unset($_SESSION['success_manpower']); ?>
    </div>
<?php endif; ?>

<div class="mb-4 px-4 py-3 rounded" style="background-color:#d8d8d882; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-user-cog me-2"></i>Manpower Rates
        </h4>

        <div class="d-flex align-items-center gap-2">

            

            <!-- MONTH FILTER -->
            <select id="monthFilter"
                    class="form-select form-select-sm"
                    style="width:auto;"
                    onchange="filterByYearMonth()">
                <option value="">All Months</option>
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?= $num ?>" <?= ($selected_month == $num) ? 'selected' : '' ?>>
                        <?= $name ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- YEAR FILTER -->
            <select id="yearFilter"
                    class="form-select form-select-sm"
                    style="width:auto;"
                    onchange="filterByYearMonth()">
                <option value="">All Years</option>
                <?php foreach ($available_years as $year): ?>
                    <option value="<?= $year ?>" <?= ($selected_year == $year) ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <a href="main.php?page=manpower/createmanpower" class="btn btn-primary">
                <i class="fa fa-plus me-2"></i>Add Manpower
            </a>

        </div>
    </div>
                    
    <div class="table-responsive">
        <table class="table table-sm table-hover text-center align-middle table-bordered">
            <thead class="table-dark text-light small">
                <tr>
                    <th>No</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Old Rate (₱)</th>
                    <th>Meal Allowance (₱)</th>
                    <th>New Rate (₱)</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody class="small">
            <?php if ($result->num_rows > 0): ?>
                <?php $counter = $result->num_rows; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="clickable-row"
                        data-href="main.php?page=manpower/viewmanpower&id=<?= $row['id'] ?>"
                        style="cursor:pointer;">

                        <td><?= $counter-- ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['position']) ?></td>
                        <td class="text-end">₱<?= number_format($row['old_rate'], 2) ?></td>
                        <td class="text-end">₱<?= number_format($row['meal_allowance'], 2) ?></td>
                        <td class="text-end">₱<?= number_format($row['new_rate'], 2) ?></td>

                        <td>
                            <a href="main.php?page=manpower/viewmanpower&id=<?= $row['id'] ?>"
                               class="btn btn-xs btn-outline-info me-1"
                               onclick="event.stopPropagation();">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="main.php?page=manpower/editmanpower&id=<?= $row['id'] ?>"
                               class="btn btn-xs btn-outline-warning me-1"
                               onclick="event.stopPropagation();">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <a href="includes/manpower/deletemanpower.php?id=<?= $row['id'] ?>"
                               class="btn btn-xs btn-outline-danger"
                               onclick="event.stopPropagation(); return confirm('Delete this manpower item?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center text-muted">Empty List</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// FILTER BY YEAR & MONTH
function filterByYearMonth() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;
    let url = 'main.php?page=manpower/list';
    let params = [];

    if (year) params.push('year=' + year);
    if (month) params.push('month=' + month);

    if (params.length > 0) {
        url += '&' + params.join('&');
    }

    window.location.href = url;
}

// CLICK ROW → VIEW (except buttons)
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', e => {
            if (!e.target.closest('a, button, i')) {
                window.location.href = row.dataset.href;
            }
        });
    });
});
</script>
