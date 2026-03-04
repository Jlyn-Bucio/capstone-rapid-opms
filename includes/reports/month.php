<?php
// includes/reports/month.php
include_once __DIR__ . '/../../includes/rapid_opms.php';

date_default_timezone_set('Asia/Manila');

/* =========================
   MONTH & YEAR
========================= */
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

/* =========================
   AVAILABLE YEARS
========================= */
$available_years = [];
$stmt = $conn->query("
    SELECT DISTINCT YEAR(billing_date) as y FROM billing WHERE deleted_at IS NULL AND billing_date IS NOT NULL
    UNION
    SELECT DISTINCT YEAR(start_date) as y FROM projects WHERE deleted_at IS NULL AND start_date IS NOT NULL
    ORDER BY y ASC
");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        if ($row['y']) $available_years[] = (int)$row['y'];
    }
}
$available_years = array_unique($available_years);
sort($available_years);
if (empty($available_years)) $available_years = [(int)date('Y')];
if (!in_array($year, $available_years)) $year = $available_years[0];

/* =========================
   MONTH RANGE
========================= */
$available_months = range(1,12);
if (!in_array($month, $available_months)) $month = (int)date('n');

$start_of_month = date('Y-m-d', strtotime("$year-$month-01"));
$end_of_month   = date('Y-m-d', strtotime("last day of $year-$month"));

$start_dt = $start_of_month . ' 00:00:00';
$end_dt   = $end_of_month   . ' 23:59:59';

/* =========================
   CURRENCY FORMATTER
========================= */
if (class_exists('NumberFormatter')) {
    $fmt = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);
} else {
    $fmt = new class {
        public function formatCurrency($amount) {
            return '₱' . number_format((float)$amount,2);
        }
    };
}

/* =========================
   HELPERS
========================= */
function count_range($conn, $table, $dateField, $start, $end) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) total
        FROM `$table`
        WHERE $dateField BETWEEN ? AND ?
          AND deleted_at IS NULL
    ");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

function sum_billing($conn, $start, $end, $status = null) {
    $sql = "SELECT IFNULL(SUM(amount),0) AS total FROM billing WHERE billing_date BETWEEN ? AND ? AND deleted_at IS NULL";
    if ($status) $sql .= " AND status = ?";
    $stmt = $conn->prepare($sql);
    if ($status) {
        $stmt->bind_param("sss", $start, $end, $status);
    } else {
        $stmt->bind_param("ss", $start, $end);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($row['total'] ?? 0);
}

/* =========================
   SUMMARY DATA
========================= */
$new_customers = count_range($conn, 'customers', 'created_at', $start_dt, $end_dt);
$new_projects  = count_range($conn, 'projects', 'created_at', $start_dt, $end_dt);

$monthly_income  = sum_billing($conn, $start_dt, $end_dt, 'paid'); // paid only
$monthly_billing = sum_billing($conn, $start_dt, $end_dt);           // all billings

/* =========================
   DAILY REVENUE (PAID)
========================= */
$monthTotals = [];
$period = new DatePeriod(
    new DateTime($start_of_month),
    new DateInterval('P1D'),
    (new DateTime($end_of_month))->modify('+1 day')
);
foreach ($period as $d) $monthTotals[$d->format('Y-m-d')] = 0;

// daily totals
$stmt = $conn->prepare("
    SELECT DATE(billing_date) AS day, IFNULL(SUM(amount),0) AS total
    FROM billing
    WHERE billing_date BETWEEN ? AND ?
      AND deleted_at IS NULL
    GROUP BY DATE(billing_date)
");
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $monthTotals[$row['day']] = (float)$row['total'];
}
$stmt->close();

$chart_labels = [];
$chart_data   = [];
foreach ($monthTotals as $day => $total) {
    $chart_labels[] = date('M j', strtotime($day));
    $chart_data[]   = $total;
}
$monthly_total = array_sum($chart_data);
$today_label   = date('M j');

/* =========================
   NEWLY CREATED PROJECTS (based on created_at)
========================= */
$newly_created_q = $conn->prepare("
    SELECT 
        p.id, 
        p.name, 
        p.created_at, 
        p.start_date, 
        c.name AS customer_name,
        IFNULL(SUM(b.amount),0) AS total_billing
    FROM projects p
    JOIN customers c ON c.id = p.customer_id AND c.deleted_at IS NULL
    LEFT JOIN billing b ON b.project_id = p.id 
        AND b.deleted_at IS NULL 
        AND b.billing_date BETWEEN ? AND ?
    WHERE p.deleted_at IS NULL
      AND p.created_at BETWEEN ? AND ?
    GROUP BY p.id, p.name, p.created_at, p.start_date, c.name
    ORDER BY p.created_at DESC
");

$newly_created_q->bind_param("ssss", $start_dt, $end_dt, $start_dt, $end_dt);
$newly_created_q->execute();
$newly_created_projects = $newly_created_q->get_result()->fetch_all(MYSQLI_ASSOC);
$newly_created_q->close();

/* =========================
   MONTHLY PROJECTS (based on start_date)
========================= */
$projects_q = $conn->prepare("
    SELECT 
        p.id, 
        p.name, 
        p.created_at, 
        p.start_date, 
        c.name AS customer_name,
        IFNULL(SUM(b.amount),0) AS total_billing
    FROM projects p
    JOIN customers c ON c.id = p.customer_id AND c.deleted_at IS NULL
    LEFT JOIN billing b ON b.project_id = p.id 
        AND b.deleted_at IS NULL 
        AND b.billing_date BETWEEN ? AND ?
    WHERE p.deleted_at IS NULL
      AND (
            p.start_date BETWEEN ? AND ?  -- projects started this month
            OR b.id IS NOT NULL            -- or has billing in this month
          )
    GROUP BY p.id, p.name, p.created_at, p.start_date, c.name
    ORDER BY p.start_date ASC
");

// Bind parameters: first 2 for billing_date, next 2 for start_date
$projects_q->bind_param("ssss", $start_dt, $end_dt, $start_dt, $end_dt);
$projects_q->execute();
$monthly_projects = $projects_q->get_result()->fetch_all(MYSQLI_ASSOC);
$projects_q->close();


/* =========================
   Add "(this month)" label
========================= */
foreach ($monthly_projects as &$project) {
    $start = date('Y-m-d', strtotime($project['start_date']));
    if ($start >= date('Y-m-01') && $start <= date('Y-m-t')) {
        $project['label'] = $project['name'] . ' (this month)';
    } else {
        $project['label'] = $project['name'];
    }
}
unset($project);
?>

<!-- =========================
     HTML STARTS
========================= -->
<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-body">
            <?php
            $today_month = (int)date('n');
            $today_year = (int)date('Y');
            $month_selected = $month;
            $current_year = $year;
            ?>
            <form method="get" class="d-flex justify-content-between align-items-start">
                <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? 'reports/month') ?>">
                <h4 class="mb-3 fw-bold mb-0">
                    Monthly Report — <?= date('F Y', strtotime($start_of_month)) ?>
                </h4>

                <div class="ms-3">
                    <select name="month" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                        <?php foreach ($available_months as $m): ?>
                            <?php $label = date('F', mktime(0,0,0,$m,1)); ?>
                            <?php if ($m === $today_month && $current_year === $today_year) $label .= ' (this month)'; ?>
                            <option value="<?= $m ?>" <?= $m == $month_selected ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="year" class="form-select form-select-sm d-inline w-auto ms-2" onchange="this.form.submit()">
                        <?php foreach ($available_years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

    <!-- SUMMARY CARDS -->
    <div class="row mb-0">
        <div class="col-md-3">
            <div class="card text-white p-3" style="background:#6f42c1;">
               <div><strong>Monthly Income (PAID)</strong></div>
                <h5><?= $fmt->formatCurrency($monthly_income) ?></h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white p-3" style="background:#20c997;">
                <div><strong>New Projects</strong></div>
                <h5><?= $new_projects ?></h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white p-3" style="background:#0dcaf0;">
                <div><strong>New Customers</strong></div>
                <h5><?= $new_customers ?></h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white p-3" style="background:#fd7e14;">
                <div><strong>Monthly Billing (All)</strong></div>
                <h5><?= $fmt->formatCurrency($monthly_billing) ?></h5>
            </div>
        </div>
    </div>
    </div>

    <!-- DAILY REVENUE CHART & RECENT PROJECTS -->
    <div class="row">
        <!-- DAILY REVENUE CHART -->
        <div class="col-6 mb-4">
            <div class="card h-100">
                <h4><strong>Daily Revenue (This Month)</strong></h4>
                <div class="card-body">
                    <canvas id="monthlyRevenueChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- RECENT PROJECTS -->
        <div class="col-6 mb-4">
            <div class="card h-100">
                <h4 class="fw-bold">Recent Projects</h4>
                <div class="card-body table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>Project</th>
                                <th>Customer</th>
                                <th>Created</th>
                                <th>Start Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($monthly_projects)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No projects this month</td>
                            </tr>
                        <?php else: $counter = 1; foreach (array_slice($monthly_projects, 0, 5) as $p): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($p['name']) ?></td>
                                <td><?= htmlspecialchars($p['customer_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                                <td><?= date('M j, Y', strtotime($p['start_date'])) ?></td>
                                <td class="text-end"><?= $fmt->formatCurrency($p['total_billing']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = <?= json_encode($chart_labels) ?>;
    const data   = <?= json_encode($chart_data) ?>;
    const today  = "<?= $today_label ?>";

    const pointColors = labels.map(l => l === today ? 'rgba(220,53,69,1)' : 'rgba(25,135,84,1)');
    const pointRadius = labels.map(l => l === today ? 7 : 3);

    new Chart(document.getElementById('monthlyRevenueChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Daily Revenue',
                data,
                borderWidth: 2,
                tension: 0.35,
                fill: true,
                borderColor: 'rgba(25,135,84,1)',
                backgroundColor: 'rgba(25,135,84,0.15)',
                pointBackgroundColor: pointColors,
                pointRadius: pointRadius
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                title: { display: false },
                tooltip: {
                    callbacks: {
                        footer: () => 'Monthly Total: ₱<?= number_format($monthly_total,2) ?>'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '₱' + v.toLocaleString() }
                }
            }
        }
    });
});
</script>
