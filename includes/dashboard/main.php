<?php
// includes/dashboard/main.php
include_once __DIR__ . '/../rapid_opms.php';

$fmt = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);

// --- Sales Calculations ---
// Weekly Sales
$start_of_week = date('Y-m-d', strtotime('monday this week'));
$weekly_sales_query = $conn->query("SELECT SUM(amount) as total FROM billing WHERE billing_date >= '{$start_of_week}'");
$weekly_sales = $weekly_sales_query->fetch_assoc()['total'] ?? 0;

// Monthly Sales
$start_of_month = date('Y-m-01');
$monthly_sales_query = $conn->query("SELECT SUM(amount) as total FROM billing WHERE billing_date >= '{$start_of_month}'");
$monthly_sales = $monthly_sales_query->fetch_assoc()['total'] ?? 0;

// Yearly Sales
$start_of_year = date('Y-01-01');
$yearly_sales_query = $conn->query("SELECT SUM(amount) as total FROM billing WHERE billing_date >= '{$start_of_year}'");
$yearly_sales = $yearly_sales_query->fetch_assoc()['total'] ?? 0;


// --- Top Tier Purchase Client ---
$top_client_query = $conn->query("
    SELECT c.name, SUM(b.amount) as total_spent
    FROM billing b
    JOIN customers c ON b.customer_id = c.id
    GROUP BY b.customer_id
    ORDER BY total_spent DESC
    LIMIT 1
");
$top_client = $top_client_query->fetch_assoc();

?>

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
      <div class="col-md-6">
        <h3>Dashboard</h3>
      </div>
      <div class="col-md-6 d-flex justify-content-md-end align-items-center gap-2">
        <span id="realtimeClock" class="text-muted small"></span>
      </div>
    </div>

    <!-- Sales Cards -->
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Weekly Sales</h5>
                    <h4 class="text-end"><?= $fmt->formatCurrency($weekly_sales, 'PHP') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Monthly Sales</h5>
                    <h4 class="text-end"><?= $fmt->formatCurrency($monthly_sales, 'PHP') ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card h-100">
                <div class="card-body">
                    <h5 class="card-title">Yearly Sales</h5>
                    <h4 class="text-end"><?= $fmt->formatCurrency($yearly_sales, 'PHP') ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
      <!-- Project Calendar -->
      <div class="col-md-8 mb-4">
        <div class="card dashboard-card h-100">
          <div class="card-body">
            <h5 class="card-title">Project Calendar</h5>
            <div id="calendar"></div>
          </div>
        </div>
      </div>

      <!-- Top-Tier Purchase Client -->
      <div class="col-md-4 mb-4">
        <div class="card dashboard-card h-100">
          <div class="card-body">
            <h5 class="card-title">Top-Tier Purchase Client</h5>
            <?php if ($top_client): ?>
                <h5><?= htmlspecialchars($top_client['name']) ?></h5>
                <p class="text-muted">Total Spent: <?= $fmt->formatCurrency($top_client['total_spent'], 'PHP') ?></p>
            <?php else: ?>
                <p>No data</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
</div>
