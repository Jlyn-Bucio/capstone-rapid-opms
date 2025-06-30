<?php
// includes/reports/month.php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Get the first and last day of the current month
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-t');

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

// Fetch basic counts for the current month
$new_customers = get_count_for_range($conn, 'customers', $start_of_month, $end_of_month);
$new_projects = get_count_for_range($conn, 'projects', $start_of_month, $end_of_month);
$new_billings = get_count_for_range($conn, 'billing', $start_of_month, $end_of_month);
$new_inventory = get_count_for_range($conn, 'inventory', $start_of_month, $end_of_month);

// Get monthly income from transactions
$monthly_income = get_sum_for_range($conn, 'transactions', 'amount', 'transaction_date', $start_of_month, $end_of_month);

// Get monthly billing amount
$monthly_billing = get_sum_for_range($conn, 'billing', 'amount', 'billing_date', $start_of_month, $end_of_month);

// Get daily statistics for the chart
$daily_stats_query = $conn->prepare("
    SELECT 
        DATE(transaction_date) as date,
        SUM(amount) as total
    FROM transactions 
    WHERE DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY DATE(transaction_date)
    ORDER BY date ASC
");

$daily_stats_query->bind_param("ss", $start_of_month, $end_of_month);
$daily_stats_query->execute();
$daily_stats_result = $daily_stats_query->get_result();

$dates = [];
$totals = [];
$daily_stats = [];

while ($row = $daily_stats_result->fetch_assoc()) {
    $daily_stats[] = $row;
    $dates[] = date('M j', strtotime($row['date']));
    $totals[] = (float)$row['total'];
}
$daily_stats_query->close();

// Get project details for the month
$projects_query = $conn->prepare("
    SELECT p.*, c.name as customer_name 
    FROM projects p 
    JOIN customers c ON p.customer_id = c.id 
    WHERE DATE(p.created_at) BETWEEN ? AND ?
    ORDER BY p.created_at DESC
");

$projects_query->bind_param("ss", $start_of_month, $end_of_month);
$projects_query->execute();
$monthly_projects = $projects_query->get_result()->fetch_all(MYSQLI_ASSOC);
$projects_query->close();
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Monthly Report - <?= date('F Y') ?></h5>
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
                                        Monthly Income</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $fmt->formatCurrency($monthly_income, 'PHP') ?>
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
                                        New Projects</div>
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
                                        New Customers</div>
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
                                        Monthly Billing</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $fmt->formatCurrency($monthly_billing, 'PHP') ?>
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
                            <h6 class="m-0 font-weight-bold text-primary">Daily Revenue Overview</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="dailyRevenueChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Projects</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Project</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($monthly_projects, 0, 5) as $project): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($project['name']) ?></td>
                                            <td><?= htmlspecialchars($project['customer_name']) ?></td>
                                            <td><?= date('M j', strtotime($project['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
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
    const ctx = document.getElementById('dailyRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Daily Revenue',
                data: <?= json_encode($totals) ?>,
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
                    text: 'Daily Revenue for <?= date('F Y') ?>'
                }
            }
        }
    });
});
</script>

