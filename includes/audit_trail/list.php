<?php
include_once __DIR__ . '/../../includes/rapid_opms.php'; // $conn
include_once __DIR__ . '/../../includes/logger/log.php';
include_once __DIR__ . '/audit.php'; // AuditLogger class

$log = new Logger(__DIR__ . '/../../logs/app.log', true);

/* ===========================
   HANDLE ADD PROJECT
=========================== */
if (isset($_POST['add_project'])) {
    $project_name = trim($_POST['project_name'] ?? '');
    $project_description = trim($_POST['project_description'] ?? '');
    $project_location = trim($_POST['project_location'] ?? '');
    $project_area = trim($_POST['project_area'] ?? '');

    $stmt = $conn->prepare("INSERT INTO projects (name, description, location, area) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $project_name, $project_description, $project_location, $project_area);
    $stmt->execute();
    $stmt->close();

    $formatted_description = "$project_name    $project_description    $project_location    $project_area";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ===========================
   HANDLE DELETE PROJECT (soft delete)
=========================== */
if (isset($_POST['delete_project'])) {
    $project_id = (int)($_POST['project_id'] ?? 0);

    // Fetch project info
    $stmt = $conn->prepare("SELECT name, description, location, area FROM projects WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$project) die("Project not found");

    // Soft delete
    $stmt = $conn->prepare("UPDATE projects SET deleted_at = NOW() WHERE id=?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $stmt->close();

    $formatted_description = "{$project['name']}    {$project['description']}    {$project['location']}    {$project['area']}";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ===========================
   FILTER AUDIT TRAIL
=========================== */
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$selected_module = $_GET['module'] ?? null;

$sql = "SELECT a.id, a.action, a.module, a.description, a.created_at, u.name AS user_name
        FROM audit_trail a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1=1";

if ($selected_year) {
    $sql .= " AND YEAR(a.created_at) = $selected_year";
}

if ($selected_module) {
    $sql .= " AND a.module = '" . $conn->real_escape_string($selected_module) . "'";
}

$sql .= " ORDER BY a.created_at DESC";

$result = $conn->query($sql);
?>

<div class="container py-4">
    <div class="mb-4 px-4 py-3 rounded" style="background:#d8d8d882;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4><i class="fa fa-shield-alt me-2"></i>Audit Trail</h4>

            <!-- YEAR FILTER -->
            <select id="yearFilter" class="form-select form-select-sm w-auto" onchange="filterByYear()">
                <option value="">All Years</option>
                <?php foreach ($available_years as $year): ?>
                    <option value="<?= $year ?>" <?= ($selected_year == $year) ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <table class="table table-sm table-bordered">
            <thead class="table-dark small">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody class="small">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $id = $row['id'] ?? '';
                        $created_at = $row['created_at'] ?? null;
                        $action = strtoupper($row['action'] ?? '');
                        $module = $row['module'] ?? '';
                        $description = $row['description'] ?? '';
                        $user_name = $row['user_name'] ?? 'System';

                        $rowClass = 'table-default';
                        $badgeClass = 'bg-secondary';
                        if (strpos($action, 'DELETE') !== false) { $rowClass='table-danger'; $badgeClass='bg-danger'; }
                        elseif (strpos($action, 'CREATE') !== false) { $rowClass='table-success'; $badgeClass='bg-success'; }
                        elseif (strpos($action, 'UPDATE') !== false) { $rowClass='table-warning'; $badgeClass='bg-warning'; }
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td><?= htmlspecialchars($id) ?></td>
                            <td><?= $created_at ? date('Y-m-d H:i', strtotime($created_at)) : '' ?></td>
                            <td><?= htmlspecialchars($module) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($action) ?></span></td>
                            <td><?= htmlspecialchars($description) ?></td>
                            <td><?= htmlspecialchars($user_name) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No audit records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function filterByYear() {
    const year = document.getElementById('yearFilter').value;
    window.location.href = 'main.php?page=audit_trail' + (year ? '&year=' + year : '');
}
</script>