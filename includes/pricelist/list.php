<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../rapid_opms.php';

// ================= GET SELECTED YEAR & MONTH =================
$selected_year = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : null;

$selected_month = isset($_GET['month']) && $_GET['month'] !== ''
    ? (int)$_GET['month']
    : null;

// ================= FETCH PRICE LIST =================
$sql = "SELECT id, year, month, contractor, straight_finish, rough_finish FROM price_list WHERE 1=1";

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

// ================= FETCH AVAILABLE YEARS =================
$available_years = [];
$years_result = $conn->query("SELECT DISTINCT year FROM price_list ORDER BY year DESC");
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}

// Month names
$months = [
    1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
    7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'
];
?>
 
<div class="container py-4">
  <div class="row">
    <div class="col-12">

      <?php if (!empty($_SESSION['success_pricelist'])): ?>
        <div class="alert alert-success">
          <?= $_SESSION['success_pricelist']; unset($_SESSION['success_pricelist']); ?>
        </div>
      <?php endif; ?>

      <div class="mb-4 px-4 py-3 rounded"
           style="background-color:#d8d8d882; box-shadow:0 2px 6px rgba(0,0,0,.1);">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0">
            <i class="fa fa-tags me-2"></i>Contractor Pricelist
          </h4>
          <div class="d-flex align-items-center gap-2">

              

              <!-- MONTH FILTER -->
              <select id="monthFilter" class="form-select form-select-sm" style="width:auto;" onchange="filterByYearMonth()">
                <option value="">All Months</option>
                <?php foreach ($months as $num => $name): ?>
                  <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>>
                    <?= $name ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <!-- YEAR FILTER -->
              <select id="yearFilter" class="form-select form-select-sm" style="width: auto;" onchange="filterByYearMonth()">
                <option value="">All Years</option>
                <?php foreach ($available_years as $year): ?>
                  <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                    <?= $year ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <a href="main.php?page=pricelist/create" class="btn btn-primary">
                <i class="fa fa-plus me-2"></i>Add Contractor
              </a>
          </div>
       </div>

        <div class="table-responsive">
          <table class="table table-sm table-hover text-center align-middle table-bordered">
            <thead class="table-dark text-light small">
              <tr>
                <th rowspan="2">#</th>
                <th rowspan="2">CONTRACTOR</th>
                <th colspan="2">PRICE</th>
                <th rowspan="2">ACTIONS</th>
              </tr>
              <tr>
                <th>STRAIGHT TO FINISH</th>
                <th>ROUGH FINISH</th>
              </tr>
            </thead>

            <tbody class="small">
            <?php if ($result->num_rows > 0): ?>
                <?php $counter = $result->num_rows; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr data-href="main.php?page=pricelist/view&id=<?= $row['id'] ?>" style="cursor:pointer;">
                        <td><?= $counter-- ?></td>
                        <td><?= htmlspecialchars($row['contractor']) ?></td>
                        <td>₱<?= number_format($row['straight_finish'], 2) ?></td>
                        <td>₱<?= number_format($row['rough_finish'], 2) ?></td>
                        <td>
                            <a href="main.php?page=pricelist/view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1" title="View">
                              <i class="bi bi-eye"></i>
                            </a>
                            <a href="main.php?page=pricelist/edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1" title="Edit">
                              <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="/capstone-rapid-opms/includes/pricelist/delete.php?id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger" title="Delete" onclick="return confirm('Delete this contractor item?')">
                              <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">Empty List</td>
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
// Filter pricelist by year & month
function filterByYearMonth() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;

    let url = 'main.php?page=pricelist/list';
    let params = [];

    if (year) params.push('year=' + year);
    if (month) params.push('month=' + month);

    if (params.length > 0) url += '&' + params.join('&');

    window.location.href = url;
}

// Make table rows clickable, except for buttons/icons
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
