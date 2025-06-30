<?php
// includes/reports/day.php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Get the current date
$today = date('Y-m-d');

// Helper function to execute a count query for a specific date
function get_count_for_day($conn, $table, $date) {
    $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'] ?? 0;
}

// Fetch counts for today
$new_customers = get_count_for_day($conn, 'customers', $today);
$new_projects = get_count_for_day($conn, 'projects', $today);
$new_billings = get_count_for_day($conn, 'billing', $today);
$new_inventory = get_count_for_day($conn, 'inventory', $today); 