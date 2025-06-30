<?php
// includes/reports.php

// Determine which report tab to show. Default to 'day'.
$tab = $_GET['tab'] ?? 'day';

// Define the available tabs and their corresponding files
$tabs = [
    'day' => 'Daily Report',
    'week' => 'Weekly Report',
    'month' => 'Monthly Report',
    'year' => 'Yearly Report'
];

// The file to include for the active tab
$report_file = __DIR__ . "/reports/{$tab}.php";
?>

<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header pb-0">
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                <?php foreach ($tabs as $key => $title): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $key === $tab ? 'active' : '' ?>" 
                           href="main.php?page=reports&tab=<?= $key ?>" 
                           role="tab">
                           <?= $title ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card-body">
            <!-- Tab Content -->
            <div class="tab-content" id="reportTabsContent">
                <div class="tab-pane fade show active" role="tabpanel">
                    <?php
                    // Include the corresponding report file
                    if (file_exists($report_file)) {
                        include $report_file;
                    } else {
                        echo "<div class='alert alert-danger'>Report not found for tab: {$tab}</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>