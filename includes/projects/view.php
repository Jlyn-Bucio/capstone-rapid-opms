<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../../includes/rapid_opms.php';

// Render helper: show blank when no data provided in form
function displayField($value)
{
    if ($value === null) {
        return '';
    }

    if (is_string($value) && trim($value) === '') {
        return '';
    }

    if ($value === 0 || $value === '0' || $value === '0.00') {
        return '';
    }

    return htmlspecialchars((string)$value);
}

// Format numbers with commas; blank out empty/zero-like inputs
function formatNumber($value, $decimals = null)
{
    if ($value === null) {
        return '';
    }

    if (is_string($value) && trim($value) === '') {
        return '';
    }

    if ($value === 0 || $value === '0' || $value === '0.00') {
        return '';
    }

    if (!is_numeric($value)) {
        return displayField($value);
    }

    if ($decimals === null) {
        $valueStr = (string)$value;
        $decimals = (strpos($valueStr, '.') !== false)
            ? max(0, strlen(substr(strrchr($valueStr, '.'), 1)))
            : 0;
    }

    return number_format((float)$value, $decimals, '.', ',');
}

/*
|--------------------------------------------------------------------------
| MODE DETECTION
|--------------------------------------------------------------------------
*/
if (isset($_GET['date'])) {

    $mode = 'date';
    $date = $_GET['date'];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo "<div class='alert alert-danger'>Invalid date format.</div>";
        exit;
    }

} elseif (isset($_GET['id'])) {

    $mode = 'single';
    $project_id = (int)$_GET['id'];

} else {
    echo "<div class='alert alert-danger'>Invalid request.</div>";
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA FETCHING
|--------------------------------------------------------------------------
*/
if ($mode === 'single') {

    // Fetch project details (including optional status column if present)
    $stmt = $conn->prepare("\n        SELECT p.*, c.name AS customer_name, c.company_name, c.email AS customer_email\n        FROM projects p\n        LEFT JOIN customers c ON p.customer_id = c.id\n        WHERE p.id = ?\n    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$project) {
        echo "<div class='alert alert-danger'>Project not found.</div>";
        exit;
    }

    $project_status = $project['status'] ?? 'Pending';
}

if ($mode === 'date') {

    $stmt = $conn->prepare("\n        SELECT *\n        FROM projects\n        WHERE DATE(start_date) = ?\n        ORDER BY start_date ASC\n    ");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $projects = $stmt->get_result();
    $stmt->close();
}
?>

<?php if ($mode === 'single'): ?>

<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <h4 class="mb-0">
                <i class="fas fa-eye me-2"></i>View Project Details
            </h4>

            <a href="main.php?page=projects/list" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

            <div class="row">
                <div class="col-lg-6 mb-3">
                    <table class="table table-sm table-bordered mb-0">
                        <tr>
                            <th style="width: 250px;" class="ps-3">Status</th>
                            <td class="ps-3">
                                <?php
                                $status = $project_status ?? 'Pending';

                                switch ($status) {
                                    case 'Ongoing':
                                        echo '<i class="bi bi-tools text-warning me-1"></i> Ongoing';
                                        break;

                                    case 'Finished':
                                        echo '<i class="bi bi-check-circle-fill text-success me-1"></i> Finished';
                                        break;

                                    case 'Cancelled':
                                        echo '<i class="bi bi-x-circle-fill text-danger me-1"></i> Cancelled';
                                        break;

                                    case 'Pending':
                                    default:
                                        echo '<span class="text-muted">Pending</span>';
                                        break;
                                }
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th style="width: 250px;" class="ps-3">Project Name</th>
                            <td class="ps-3"><?= displayField($project['name']) ?></td>
                        </tr>

                        <?php
                        $billing_stmt = $conn->prepare("\n                            SELECT id, invoice_number \n                            FROM billing \n                            WHERE project_id = ? AND deleted_at IS NULL\n                            ORDER BY id ASC\n                        ");
                        $billing_stmt->bind_param("i", $project['id']);
                        $billing_stmt->execute();
                        $billing_result = $billing_stmt->get_result();
                        $billing_stmt->close();
                        ?>
                        <tr>
                            <th class="ps-3">Invoice No.</th>
                            <td class="ps-3">
                                <?php if ($billing_result && $billing_result->num_rows > 0): ?>
                                    <?php while ($b = $billing_result->fetch_assoc()): ?>
                                        <a href="main.php?page=billing/view&id=<?= $b['id'] ?>" class="text-decoration-none me-2">
                                            <?= displayField($b['invoice_number']) ?>
                                        </a>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr>
                            <th style="width: 250px;" class="ps-3">Date</th>
                            <td class="ps-3"><?= isset($project['date']) && $project['date'] ? date('F j, Y', strtotime($project['date'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <th style="width: 250px;" class="ps-3">Start Date</th>
                            <td class="ps-3"><?= isset($project['start_date']) && $project['start_date'] ? date('F j, Y', strtotime($project['start_date'])) : '-' ?></td>
                        </tr>
                        <tr>
                            <th style="width: 250px;" class="ps-3">Location</th>
                            <td class="ps-3"><?= displayField($project['location']) ?></td>
                        </tr>
                        <tr>
                            <th style="width: 250px;" class="ps-3">Contractor</th>
                            <td class="ps-3"><?= displayField($project['contractor'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <th style="width: 250px;" class="ps-3">Project Size</th>
                            <td class="ps-3"><?= formatNumber($project['size'] ?? '') ?></td>
                        </tr>

                        <tr>
                            <th style="width: 250px;" class="ps-3">Customer</th>
                            <td class="ps-3">
                                <?= displayField($project['customer_name'] ?? '') ?>
                                <?php if (!empty($project['company_name'])): ?>
                                    (<?= displayField($project['company_name']) ?>)
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="col-lg-6 mb-3">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 250px;" class="ps-3">Charge/s</th>
                                <th style="width: 90px;" class="ps-3">Area</th>
                                <th style="width: 110px;" class="ps-3">Unit Cost</th>
                                <th style="width: 120px;" class="ps-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="width: 250px;" class="ps-3">Straight to Finish — m²</td>
                                <td class="ps-3"><?= formatNumber($project['straight_finish_area'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['straight_finish_unit_cost'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['straight_finish_amount'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="ps-3">Rough Finish — m²</td>
                                <td class="ps-3"><?= formatNumber($project['rough_finish_area'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['rough_finish_unit_cost'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['rough_finish_amount'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="ps-3">Suspended Volume — m³</td>
                                <td class="ps-3"><?= formatNumber($project['suspended_volume_area'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['suspended_volume_unit_cost'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['suspended_volume_amount'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="ps-3">Mobilization Fee — lot</td>
                                <td class="ps-3"><?= formatNumber($project['mobilization_fee_area'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['mobilization_fee_unit_cost'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['mobilization_fee_amount'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="ps-3">Idle Time Charge — hrs</td>
                                <td class="ps-3"><?= formatNumber($project['idle_time_area'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['idle_time_unit_cost'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['idle_time_amount'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <td class="ps-3">Cancellation Fee — lot</td>
                                <td class="ps-3"><?= formatNumber($project['cancellation_fee_area'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['cancellation_fee_unit_cost'] ?? '') ?></td>
                                <td class="ps-3"><?= formatNumber($project['cancellation_fee_amount'] ?? '') ?></td>
                            </tr>
                            <tr class="fw-bold">
                                <td class="text-end">Total Amount:</td>
                                <td colspan="3" class="text-end"><?= formatNumber($project['total_amount'] ?? '') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>


<?php if ($mode === 'date'): ?>

<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center p-3">
            <h4 class="mb-0">
                <i class="fas fa-calendar-day me-2"></i>
                Projects on <?= date('F j, Y', strtotime($date)) ?>
            </h4>
            <a href="main.php?page=dashboard/main" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>

            <div class="card-body">
                <?php if ($projects->num_rows === 0): ?>
                    <div class="alert alert-warning">No projects scheduled on this date.</div>
                <?php else: ?>
                    <div class="list-group">
                        <?php while ($row = $projects->fetch_assoc()): ?>
                            <a href="main.php?page=projects/view&id=<?= $row['id'] ?>"
                               class="list-group-item list-group-item-action">
                                <strong><?= htmlspecialchars($row['name'] ?? '') ?></strong><br>
                                📍 <?= htmlspecialchars($row['location'] ?? '') ?><br>
                                👤 <?= htmlspecialchars($row['project_manager'] ?? '') ?>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
