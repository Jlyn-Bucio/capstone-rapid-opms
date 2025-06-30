<?php
// includes/reports.php

// Default to monthly report if no period is specified
$period = $_GET['period'] ?? 'month';

// Define the available reporting periods and their corresponding file names
$periods = [
    'day' => 'day.php',
    'week' => 'week.php',
    'month' => 'month.php',
    'year' => 'year.php',
];

// Validate the selected period
if (!array_key_exists($period, $periods)) {
    $period = 'month'; // Default to month if invalid
}

$report_file = $periods[$period];

// Include the data logic file for the selected period
include_once __DIR__ . "/{$report_file}";
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Company Reports</h5>
            <p class="mb-0 text-muted">An overview of company activities based on the selected period.</p>
        </div>
        <div class="card-body">
            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link <?= $period === 'day' ? 'active' : '' ?>" href="main.php?page=reports&period=day">Daily</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $period === 'week' ? 'active' : '' ?>" href="main.php?page=reports&period=week">Weekly</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $period === 'month' ? 'active' : '' ?>" href="main.php?page=reports&period=month">Monthly</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $period === 'year' ? 'active' : '' ?>" href="main.php?page=reports&period=year">Yearly</a>
                </li>
            </ul>

            <div class="mt-4">
                <h6 class="text-uppercase text-muted ls-1 mb-4">Summary for the <?= htmlspecialchars($period) ?></h6>
                <div class="row">
                    <!-- New Customers -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">New Customers</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $new_customers ?? 0 ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Projects -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">New Projects</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $new_projects ?? 0 ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New Billings -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">New Billings</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $new_billings ?? 0 ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                            <i class="fas fa-file-invoice-dollar"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     <!-- New Inventory Items -->
                     <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card card-stats shadow-sm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h5 class="card-title text-uppercase text-muted mb-0">New Inventory Items</h5>
                                        <span class="h2 font-weight-bold mb-0"><?= $new_inventory ?? 0 ?></span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-warning text-white rounded-circle shadow">
                                            <i class="fas fa-boxes"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 