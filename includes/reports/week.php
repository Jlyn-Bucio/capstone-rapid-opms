<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
date_default_timezone_set('Asia/Manila');

/* =========================
   WEEK + YEAR
========================= */
$week = isset($_GET['week']) ? (int)$_GET['week'] : (int)date('W');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

/* =========================
   AVAILABLE YEARS
   (based on projects.start_date)
========================= */
$available_years = [];
$res = $conn->query("
    SELECT DISTINCT YEAR(start_date) AS y
    FROM projects
    WHERE deleted_at IS NULL AND start_date IS NOT NULL
    ORDER BY y ASC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        if ($row['y']) $available_years[] = (int)$row['y'];
    }
}
$available_years = array_unique($available_years);
sort($available_years);
if (empty($available_years)) $available_years = [(int)date('Y')];
if (!in_array($year, $available_years)) $year = end($available_years);

/* =========================
   AVAILABLE DATE RANGES
   (based on projects.start_date)
========================= */

$date_ranges = [];

// Get current week
$today = date('Y-m-d');
$today_week_start = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$today_week_end = date('Y-m-d', strtotime('sunday this week', strtotime($today)));
$today_week_key = $today_week_start . '|' . $today_week_end;

// Always add current week to ranges
$date_ranges[$today_week_key] = [
    'start' => $today_week_start,
    'end'   => $today_week_end
];

// Add all other weeks from projects
$res = $conn->query("
    SELECT DISTINCT
        DATE_SUB(start_date, INTERVAL WEEKDAY(start_date) DAY) AS week_start,
        DATE_ADD(DATE_SUB(start_date, INTERVAL WEEKDAY(start_date) DAY), INTERVAL 6 DAY) AS week_end
    FROM projects
    WHERE deleted_at IS NULL
      AND start_date IS NOT NULL
    ORDER BY week_start ASC
");

while ($row = $res->fetch_assoc()) {
    $start = $row['week_start'];
    $end   = $row['week_end'];

    $key = $start . '|' . $end;
    $date_ranges[$key] = [
        'start' => $start,
        'end'   => $end
    ];
}

// Sort by date
ksort($date_ranges);



/* =========================
   WEEK RANGE (MON–SUN)
========================= */
// Always default to current week
$selected_range = $_GET['range'] ?? $today_week_key;

// If selected range doesn't exist, use current week
if (!isset($date_ranges[$selected_range])) {
    $selected_range = $today_week_key;
}

$start_of_week = $date_ranges[$selected_range]['start'];
$end_of_week   = $date_ranges[$selected_range]['end'];

$start_dt = $start_of_week . ' 00:00:00';
$end_dt   = $end_of_week   . ' 23:59:59';


/* =========================
   CURRENCY FORMATTER
========================= */
if (class_exists('NumberFormatter')) {
    $fmt = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);
} else {
    $fmt = new class {
        public function formatCurrency($amount) {
            return '₱' . number_format((float)$amount, 2);
        }
    };
}

/* =========================
   HELPER FUNCTIONS
========================= */
function count_range($conn, $table, $dateField, $start, $end) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
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

// ----------------------
// SUM PAID BILLING (filter by project start_date)
function sum_paid_billing($conn, $start, $end) {
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(b.amount),0) AS total
        FROM billing b
        JOIN projects p ON p.id = b.project_id
        WHERE p.start_date BETWEEN ? AND ?    -- <-- use project start_date
          AND LOWER(TRIM(b.status)) = 'paid'
          AND b.deleted_at IS NULL
          AND p.deleted_at IS NULL
    ");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($row['total'] ?? 0);
}

// ----------------------
// SUM ALL BILLING (filter by project start_date)
function sum_all_billing($conn, $start, $end) {
    $stmt = $conn->prepare("
        SELECT IFNULL(SUM(b.amount),0) AS total
        FROM billing b
        JOIN projects p ON p.id = b.project_id
        WHERE p.start_date BETWEEN ? AND ?    -- <-- use project start_date
          AND b.deleted_at IS NULL
          AND p.deleted_at IS NULL
    ");
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)($row['total'] ?? 0);
}


/* =========================
   SUMMARY CARDS
========================= */
$new_customers = count_range($conn, 'customers', 'created_at', $start_dt, $end_dt);
$new_projects  = count_range($conn, 'projects', 'created_at', $start_dt, $end_dt);
$weekly_income = sum_paid_billing($conn, $start_dt, $end_dt);
$weekly_billing = sum_all_billing($conn, $start_dt, $end_dt);

/* =========================
   DAILY REVENUE GRAPH (based on project start_date)
========================= */
$days = [];
$period = new DatePeriod(
    new DateTime($start_of_week),
    new DateInterval('P1D'),
    (new DateTime($end_of_week))->modify('+1 day')
);

// initialize all days to 0
foreach ($period as $d) {
    $days[$d->format('Y-m-d')] = 0;
}

// get billing totals for projects starting in this week
$stmt = $conn->prepare("
    SELECT DATE(b.billing_date) AS day,
           SUM(b.amount) AS total
    FROM billing b
    JOIN projects p ON p.id = b.project_id
    WHERE p.start_date BETWEEN ? AND ?   -- <-- filter by project start_date
      AND b.deleted_at IS NULL
      AND p.deleted_at IS NULL
    GROUP BY DATE(b.billing_date)
");
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $days[$row['day']] = (float)$row['total'];
}
$stmt->close();

// prepare chart labels & data
$chart_labels = [];
$chart_data   = [];
foreach ($days as $d => $v) {
    $chart_labels[] = date('D, M j', strtotime($d));
    $chart_data[]   = $v;
}
$today_label = date('D, M j');




// Recent Projects list
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.created_at, p.start_date,
           c.name AS customer_name,
           COALESCE(SUM(b.amount), 0) AS total_billing
    FROM projects p
    INNER JOIN customers c ON c.id = p.customer_id
       AND c.deleted_at IS NULL
    LEFT JOIN billing b ON b.project_id = p.id
       AND b.deleted_at IS NULL
    WHERE p.start_date BETWEEN ? AND ?
      AND p.deleted_at IS NULL
    GROUP BY p.id
    ORDER BY p.start_date ASC
");

$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$weekly_projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$new_projects = count($weekly_projects);
$stmt->close();
?>


<div class="container-fluid py-2">
    <div class="card mb-2">
        <div class="card-body">

            <?php
            // =========================
            // SAFE VARIABLE SETUP
            // =========================
            $current_year  = isset($year) ? (int)$year : (int)date('Y');
            $week_selected = isset($week) ? (int)$week : (int)date('W');

            $today_week = (int)date('W');
            $today_year = (int)date('Y');

            $available_weeks = $available_weeks ?? [];
            $available_years = $available_years ?? [$current_year];

            $start_display = isset($start_of_week)
                ? date('M j', strtotime($start_of_week))
                : date('M j');

            $end_display = isset($end_of_week)
                ? date('M j, Y', strtotime($end_of_week))
                : date('M j, Y');
            ?>

            <form method="get" class="d-flex justify-content-between align-items-center">
    <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? 'reports/week') ?>">

    <!-- LEFT SIDE TITLE -->
    <h4 class="fw-bold mb-0">
        Weekly Report —
        <?= date('M j', strtotime($start_of_week)) ?>
        to
        <?= date('M j, Y', strtotime($end_of_week)) ?>
    </h4>

    <!-- RIGHT SIDE DROPDOWNS -->
    <div class="d-flex align-items-center">

        <!-- DATE RANGE DROPDOWN -->
        <select name="range"
        class="form-select form-select-sm w-auto me-2"
        onchange="this.form.submit()">

    <?php 
    $today = date('Y-m-d'); // current date
    foreach ($date_ranges as $key => $range): 
        $start = $range['start'];
        $end = $range['end'];
        $label = date('M j', strtotime($start)) . ' – ' . date('M j', strtotime($end));

        // Check if today is within this range
        if ($today >= $start && $today <= $end) {
            $label .= ' (this week)';
        }
    ?>
        <option value="<?= $key ?>" <?= $key == $selected_range ? 'selected' : '' ?>>
            <?= $label ?>
        </option>
    <?php endforeach; ?>

</select>

        <!-- YEAR DROPDOWN -->
        <select name="year"
                class="form-select form-select-sm w-auto"
                onchange="this.form.submit()">

            <?php foreach ($available_years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $current_year ? 'selected' : '' ?>>
                    <?= $y ?>
                </option>
            <?php endforeach; ?>

        </select>

    </div>
</form>

        </div>

        <div class="row mb-0">
            <div class="col-md-3">
                <div class="card text-white p-3" style="background:#6f42c1;">
                    <strong>Weekly Income (PAID)</strong>
                    <h5><?= $fmt->formatCurrency($weekly_income) ?></h5>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-white p-3" style="background:#20c997;">
                    <strong>New Projects</strong>
                    <h5><?= $new_projects ?></h5>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-white p-3" style="background:#0dcaf0;">
                    <strong>New Customers</strong>
                    <h5><?= $new_customers ?></h5>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-white p-3" style="background:#fd7e14;">
                    <strong>Weekly Billing (All)</strong>
                    <h5><?= $fmt->formatCurrency($weekly_billing) ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- CHART -->
        <div class="col-6 mb-4">
            <div class="card h-100">
                <h4 class="fw-bold">Daily Revenue (Mon–Sun)</h4>
                <div class="card-body">
                    <canvas id="weeklyRevenueChart" height="280"></canvas>
                </div>
            </div>
        </div>

        <!-- RECENT PROJECTS -->
         <div class="col-6 mb-4">
            <div class="card h-100">
                <h4 class="fw-bold">Recent Projects</h4>
                <div class="card-body p-2">
                    <table class="table table-sm mb-0 small">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>Project</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Start</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$weekly_projects): ?>
                            <tr>
                                <td colspan="6" class="text-center py-2">
                                    No projects this week
                                </td>
                            </tr>
                        <?php else: $counter = 1; foreach ($weekly_projects as $p): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td class="text-truncate" style="max-width:120px;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </td>
                                <td class="text-truncate" style="max-width:110px;">
                                    <?= htmlspecialchars($p['customer_name']) ?>
                                </td>
                                <td><?= date('M j', strtotime($p['created_at'])) ?></td>
                                <td><?= date('M j', strtotime($p['start_date'])) ?></td>
                                <td class="text-end">
                                    <?= $fmt->formatCurrency($p['total_billing']) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
const labels = <?= json_encode($chart_labels) ?>;
const data   = <?= json_encode($chart_data) ?>;
const today  = "<?= $today_label ?>";

new Chart(document.getElementById('weeklyRevenueChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Weekly Revenue (All Billing)',
            data,
            borderWidth: 3,
            tension: 0.35,
            fill: true,
            borderColor: 'rgba(78,115,223,1)',
            backgroundColor: 'rgba(78,115,223,0.15)',
            pointRadius: labels.map(l => l === today ? 8 : 4)
        }]
    },
    options: {
        plugins: {
            legend: { display: false },
            title:  { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => '₱' + v.toLocaleString()
                }
            }
        }
    }
});
</script>