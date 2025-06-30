<?php
// includes/reports/day.php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Get the current date
$today = date('Y-m-d');

// Initialize the formatter for currency
$fmt = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);

// Helper function to execute a count query for a specific day
function get_count_for_day($conn, $table, $date) {
    $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'] ?? 0;
}

// Helper function to get sum for a specific day
function get_sum_for_day($conn, $table, $amount_field, $date_field, $date) {
    $sql = "SELECT SUM($amount_field) as total FROM `{$table}` WHERE DATE($date_field) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

// Fetch counts for today
$new_customers = get_count_for_day($conn, 'customers', $today);
$new_projects = get_count_for_day($conn, 'projects', $today);
$new_billings = get_count_for_day($conn, 'billing', $today);
$new_inventory = get_count_for_day($conn, 'inventory', $today);

// Get today's income from transactions
$daily_income = get_sum_for_day($conn, 'transactions', 'amount', 'transaction_date', $today);

// Get today's billing amount
$daily_billing = get_sum_for_day($conn, 'billing', 'amount', 'billing_date', $today);

// Get hourly statistics for the chart
$hourly_stats_query = $conn->prepare("
    SELECT 
        HOUR(transaction_date) as hour,
        SUM(amount) as total
    FROM transactions 
    WHERE DATE(transaction_date) = ?
    GROUP BY HOUR(transaction_date)
    ORDER BY hour ASC
");

$hourly_stats_query->bind_param("s", $today);
$hourly_stats_query->execute();
$hourly_stats_result = $hourly_stats_query->get_result();

$hours = array_fill(0, 24, 0); // Array for 24 hours of the day
while ($row = $hourly_stats_result->fetch_assoc()) {
    $hours[(int)$row['hour']] = (float)$row['total'];
}
$hourly_stats_query->close();

$chart_labels = array_map(function($h) { return "{$h}:00"; }, range(0, 23));
$chart_data = array_values($hours);

// Get recent projects for today
$projects_query = $conn->prepare("
    SELECT p.*, c.name as customer_name 
    FROM projects p 
    JOIN customers c ON p.customer_id = c.id 
    WHERE DATE(p.created_at) = ?
    ORDER BY p.created_at DESC
");

$projects_query->bind_param("s", $today);
$projects_query->execute();
$daily_projects = $projects_query->get_result()->fetch_all(MYSQLI_ASSOC);
$projects_query->close();

?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daily Report - <?= date('F j, Y', strtotime($today)) ?></h5>
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
                                        Daily Income</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $fmt->formatCurrency($daily_income, 'PHP') ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                                        Daily Billing</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?= $fmt->formatCurrency($daily_billing, 'PHP') ?>
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
                            <h6 class="m-0 font-weight-bold text-primary">Hourly Revenue Overview</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="hourlyRevenueChart"></canvas>
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
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($daily_projects)): ?>
                                            <tr><td colspan="3" class="text-center">No projects created today.</td></tr>
                                        <?php else: ?>
                                            <?php foreach (array_slice($daily_projects, 0, 5) as $project): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($project['name']) ?></td>
                                                <td><?= htmlspecialchars($project['customer_name']) ?></td>
                                                <td><?= date('h:i A', strtotime($project['created_at'])) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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
    const ctx = document.getElementById('hourlyRevenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Hourly Revenue',
                data: <?= json_encode($chart_data) ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                fill: true,
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
                    text: 'Hourly Revenue for Today'
                }
            }
        }
    });
});
</script> 