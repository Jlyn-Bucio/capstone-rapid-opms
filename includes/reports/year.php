<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
date_default_timezone_set('Asia/Manila');

/* =========================
   Year range
========================= */

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

/* Query available years from database */
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

/* If no years found, show current year */
if (empty($available_years)) {
    $available_years = [(int)date('Y')];
}

/* Prefer current year if it has data; otherwise use selected if available or first available */
$current_year = (int)date('Y');
if (!in_array($selected_year, $available_years)) {
    $selected_year = in_array($current_year, $available_years) ? $current_year : ($available_years[0] ?? $current_year);
}

$start_dt = "$selected_year-01-01 00:00:00";
$end_dt   = "$selected_year-12-31 23:59:59";

/* =========================
   BILLING DATA (non-deleted)
========================= */
$stmt = $conn->prepare("
    SELECT b.amount, b.status, b.billing_date,
           p.name AS project_name,
           c.name AS customer_name,
           b.project_id
    FROM billing b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN customers c ON b.customer_id = c.id
    WHERE b.deleted_at IS NULL
      AND b.billing_date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$billing_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================
   Initialize arrays
========================= */
$chart_data = array_fill(0, 12, 0);      // Monthly Revenue
$project_totals = [];                    // Top Projects
$yearly_income = 0;                      // Paid only
$yearly_billing = 0;                     // All non-deleted billings

/* =========================
   Process billing
========================= */
foreach ($billing_list as $b) {
    $amount = (float)($b['amount'] ?? 0);
    $status = strtolower($b['status'] ?? '');

    // Add to yearly billing (all non-deleted statuses)
    $yearly_billing += $amount;

    // Add to yearly income if Paid
    if ($status === 'paid') {
        $yearly_income += $amount;
    }

    // Add to monthly revenue
    $month_index = (int)date('n', strtotime($b['billing_date'])) - 1;
    $chart_data[$month_index] += $amount;

    // Track top projects
    $pid = $b['project_id'] ?? 0;
    if (!isset($project_totals[$pid])) {
        $project_totals[$pid] = [
            'name' => $b['project_name'] ?? 'Unnamed Project',
            'customer_name' => $b['customer_name'] ?? 'Unknown Customer',
            'total_billing' => 0
        ];
    }
    $project_totals[$pid]['total_billing'] += $amount;
}

/* =========================
   Top 5 projects
========================= */
usort($project_totals, fn($a,$b) => $b['total_billing'] <=> $a['total_billing']);
$top_projects = array_slice($project_totals, 0, 10);

/* =========================
   MONTHLY PROJECTS (Project List - based on start_date)
========================= */
$monthlyProjects = array_fill(0, 12, 0);

$stmt = $conn->prepare("
    SELECT start_date
    FROM projects
    WHERE deleted_at IS NULL
      AND start_date BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (empty($row['start_date'])) continue;

    $month_index = (int)date('n', strtotime($row['start_date'])) - 1;
$monthlyProjects[$month_index]++;

}

$stmt->close();


/* =========================
   New Projects (created_at - yearly total)
========================= */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM projects
    WHERE deleted_at IS NULL
      AND created_at BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$new_projects = (int)($row['total'] ?? 0);

/* =========================
   New Customers (yearly total)
========================= */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM customers
    WHERE deleted_at IS NULL
      AND created_at BETWEEN ? AND ?
");
$stmt->bind_param("ss", $start_dt, $end_dt);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$new_customers = (int)($row['total'] ?? 0);


/* =========================
   Chart labels
========================= */
$chart_labels = [];
for ($m = 1; $m <= 12; $m++) {
    $chart_labels[] = date('M', mktime(0,0,0,$m,1));
}
$current_month = date('M');

/* =========================
   Currency formatter
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
?>
<div class="container-fluid py-4">
    <div class="card mb-4">
        <div class="card-body">
            <?php
            $current_year = (int)date('Y');
            $year_selected = $selected_year;
            ?>

            <form method="get" class="d-flex justify-content-between align-items-start">
                <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? 'reports/year') ?>">

                <h4 class="mb-3 fw-bold mb-0">Yearly Report — <?= $selected_year ?></h4>

                    <div class="ms-3">
                        <select name="year" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?= $y ?>" <?= $y == $year_selected ? 'selected' : '' ?>><?= $y ?><?= $y === $current_year ? ' (this year)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <!-- auto-submit on change; no button -->
                    </div>
            </form>
        </div>
        <div class="row mb-0">
            <div class="col-md-3">
                <div class="card p-3 text-white" style="background:#6f42c1;">
                    <strong>Yearly Income (PAID)</strong>
                    <h5><?= $fmt->formatCurrency($yearly_income) ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white" style="background:#20c997;">
                    <strong>New Projects</strong>
                    <h5><?= $new_projects ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white" style="background:#0dcaf0;">
                    <strong>New Customers</strong>
                    <h5><?= $new_customers ?></h5>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 text-white" style="background:#fd7e14;">
                    <strong>Yearly Billing (All)</strong>
                    <h5><?= $fmt->formatCurrency($yearly_billing) ?></h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 g-3">
        <!-- Monthly Revenue -->
        <div class="col-lg-8">
            <div class="card h-100">
                <h4 class="fw-bold">Monthly Revenue</h4>
                <div class="card-body">
                    <canvas id="yearRevenueChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Monthly Projects -->
        <div class="col-lg-4">
            <div class="card h-100">
                <h4 class="fw-bold">Monthly Projects</h4>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="projectChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>

   
    <div class="card">
        <h4 class="fw-bold">Top Projects</h4>
        <div class="card-body table-responsive">
            <table class="table table-sm">
                <thead><tr><th>NO.</th><th>Project</th><th>Customer</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php $counter = 1; foreach ($top_projects as $p): ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['customer_name']) ?></td>
                            <td><?= $fmt->formatCurrency($p['total_billing']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const labels = <?= json_encode($chart_labels) ?>;
    const revenueData = <?= json_encode($chart_data) ?>;   // Monthly Revenue (non-deleted)
    const projectsData = <?= json_encode($monthlyProjects) ?>; // Monthly Projects from Project List
    const currentMonth = "<?= $current_month ?>";

    const barColors = labels.map(m =>
        m === currentMonth ? 'rgba(220,53,69,0.8)' : 'rgba(13,110,253,0.6)'
    );

    // MONTHLY REVENUE (BAR)
    new Chart(document.getElementById('yearRevenueChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue',
                data: revenueData,
                backgroundColor: barColors,
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });


// MONTHLY PROJECTS (LINE)
new Chart(document.getElementById('projectChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            label: 'Projects',
            data: projectsData,
            borderColor: 'rgba(0,123,255,0.8)',
            backgroundColor: 'rgba(0,123,255,0.2)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                beginAtZero: true,
                ticks: {
                    stepSize: 1,           // <- integer steps
                    callback: function(v) { return Math.round(v); } // <- remove decimals
                }
            }
        }
    }
});

});
</script>
