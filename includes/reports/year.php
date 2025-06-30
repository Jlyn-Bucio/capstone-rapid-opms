<?php
// includes/reports/year.php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Get the start and end dates of the current year
$current_year = date('Y');
$start_of_year = date('Y-m-d', strtotime("first day of january $current_year"));
$end_of_year = date('Y-m-d', strtotime("last day of december $current_year"));

// Initialize the formatter for currency
$fmt = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);

// Helper function to execute a count query for a date range
function get_count_for_range($conn, $table, $start_date, $end_date) {
    $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'] ?? 0;
}

// Helper function to get sum for a date range
function get_sum_for_range($conn, $table, $amount_field, $date_field, $start_date, $end_date) {
    $sql = "SELECT SUM($amount_field) as total FROM `{$table}` WHERE DATE($date_field) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

// Fetch counts for the current year
$new_customers = get_count_for_range($conn, 'customers', $start_of_year, $end_of_year);
$new_projects = get_count_for_range($conn, 'projects', $start_of_year, $end_of_year);
$new_billings = get_count_for_range($conn, 'billing', $start_of_year, $end_of_year);
$new_inventory = get_count_for_range($conn, 'inventory', $start_of_year, $end_of_year);

// Get yearly income from transactions
$yearly_income = get_sum_for_range($conn, 'transactions', 'amount', 'transaction_date', $start_of_year, $end_of_year);

// Get yearly billing amount
$yearly_billing = get_sum_for_range($conn, 'billing', 'amount', 'billing_date', $start_of_year, $end_of_year);

// Get monthly statistics for the chart
$monthly_stats_query = $conn->prepare("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(amount) as total
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
    ORDER BY month ASC
");

$monthly_stats_query->bind_param("ss", $start_of_year, $end_of_year);
$monthly_stats_query->execute();
$monthly_stats_result = $monthly_stats_query->get_result();

$months = [];
$totals = [];
$monthly_stats = [];

while ($row = $monthly_stats_result->fetch_assoc()) {
    $monthly_stats[] = $row;
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $totals[] = (float)$row['total'];
}
$monthly_stats_query->close();

// Get top projects for the year
$projects_query = $conn->prepare("
    SELECT 
        p.*, 
        c.name as customer_name,
        (SELECT SUM(amount) FROM billing WHERE project_id = p.id) as total_billing
    FROM projects p 
    JOIN customers c ON p.customer_id = c.id 
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    ORDER BY total_billing DESC
    LIMIT 5
");

$projects_query->bind_param("ss", $start_of_year, $end_of_year);
$projects_query->execute();
$top_projects = $projects_query->get_result()->fetch_all(MYSQLI_ASSOC);
$projects_query->close();

// Get monthly project counts for the secondary chart
$project_counts_query = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM projects 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

$project_counts_query->bind_param("ss", $start_of_year, $end_of_year);
$project_counts_query->execute();
$project_counts_result = $project_counts_query->get_result();

$project_months = [];
$project_counts = [];

while ($row = $project_counts_result->fetch_assoc()) {
    $project_months[] = date('M Y', strtotime($row['month'] . '-01'));
    $project_counts[] = (int)$row['count'];
}
$project_counts_query->close();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Yearly Report - <?= $current_year ?></h5>
        </div>
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Yearly Income</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $fmt->formatCurrency($yearly_income, 'PHP') ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Projects</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $new_projects ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $new_customers ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Yearly Billing</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $fmt->formatCurrency($yearly_billing, 'PHP') ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Revenue Overview</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyRevenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Projects by Revenue</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Customer</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_projects as $project): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($project['name']) ?></td>
                                            <td><?= htmlspecialchars($project['customer_name']) ?></td>
                                            <td><?= $fmt->formatCurrency($project['total_billing'], 'PHP') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Trends -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Project Trends</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="projectTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Revenue Chart
    const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?= json_encode($totals) ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.5)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Revenue for <?= $current_year ?>'
                }
            }
        }
    });

    // Project Trends Chart
    const trendsCtx = document.getElementById('projectTrendsChart').getContext('2d');
    new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($project_months) ?>,
            datasets: [{
                label: 'Number of Projects',
                data: <?= json_encode($project_counts) ?>,
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Monthly Project Count for <?= $current_year ?>'
                }
            }
        }
    });
});
</script>

