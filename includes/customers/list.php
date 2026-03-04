<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);
// ===============================
// DELETE (SOFT DELETE)
// ===============================
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    $stmt = $conn->prepare("UPDATE customers SET deleted_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();


    // --- Fetch customer name for audit
    $stmt_fetch = $conn->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt_fetch->bind_param("i", $delete_id);
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($customer_name);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    // --- Audit log
    $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
    $description = "Deleted Customer: {$customer_name} (ID: {$delete_id}) by '{$admin_name}'";
    $audit->log('DELETE', 'Customer', $description);

    $_SESSION['success_customers'] = "Customer deleted successfully!";
    header("Location: main.php?page=customers/list");
    exit;
}

// ===============================
// MONTH & YEAR FILTER
// ===============================
$months = [
    '01' => 'January', '02' => 'February', '03' => 'March',
    '04' => 'April', '05' => 'May', '06' => 'June',
    '07' => 'July', '08' => 'August', '09' => 'September',
    '10' => 'October', '11' => 'November', '12' => 'December'
];

$selected_month = $_GET['month'] ?? '';
$selected_year  = $_GET['year'] ?? '';

// Get available years
$available_years = [];
$year_query = $conn->query("SELECT DISTINCT YEAR(created_at) as year FROM customers WHERE deleted_at IS NULL ORDER BY year DESC");
while ($row = $year_query->fetch_assoc()) {
    $available_years[] = $row['year'];
}

// ===============================
// FETCH CUSTOMERS WITH FILTER
// ===============================
$sql = "SELECT * FROM customers WHERE deleted_at IS NULL";

$params = [];
$types  = "";

if (!empty($selected_year)) {
    $sql .= " AND YEAR(created_at) = ?";
    $params[] = $selected_year;
    $types .= "i";
}

if (!empty($selected_month)) {
    $sql .= " AND MONTH(created_at) = ?";
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

    <?php if (!empty($_SESSION['success_customers'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success_customers']; unset($_SESSION['success_customers']); ?>
        </div>
    <?php endif; ?>

    <div class="mb-4 px-4 py-3 rounded" style="background-color:#d8d8d882; box-shadow:0 2px 6px rgba(0,0,0,.1);">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="fa fa-user-cog me-2"></i>Customer List
            </h4>

            <div class="d-flex align-items-center gap-2">

                <!-- MONTH FILTER -->
                <select id="monthFilter"
                        class="form-select form-select-sm"
                        style="width:auto;"
                        onchange="filterCustomers()">
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
                        onchange="filterCustomers()">
                    <option value="">All Years</option>
                    <?php foreach ($available_years as $year): ?>
                        <option value="<?= $year ?>" <?= ($selected_year == $year) ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <a href="main.php?page=customers/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Add Customer
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle table-bordered">
                <thead class="table-dark text-light small">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody class="small">
                <?php if ($result->num_rows > 0): ?>
                    <?php $counter = $result->num_rows; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="clickable-row"
                            data-href="main.php?page=customers/view&id=<?= $row['id'] ?>"
                            style="cursor:pointer;">

                            <td><?= $counter-- ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['address'])) ?></td>

                            <td>
                                <a href="main.php?page=customers/view&id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-info me-1"
                                   onclick="event.stopPropagation();">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="main.php?page=customers/edit&id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-warning me-1"
                                   onclick="event.stopPropagation();">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <a href="main.php?page=customers/list&delete_id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-danger"
                                   onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this customer?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">Empty List</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterCustomers() {
    const year = document.getElementById('yearFilter').value;
    const month = document.getElementById('monthFilter').value;

    let url = 'main.php?page=customers/list';

    if (year) url += '&year=' + year;
    if (month) url += '&month=' + month;

    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function (e) {
            if (!e.target.closest('a, button, i')) {
                window.location.href = this.dataset.href;
            }
        });
    });
});
</script>
