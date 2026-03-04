<?php
session_start();
require_once __DIR__ . '/../../includes/rapid_opms.php';
require_once __DIR__ . '/../audit_trail/audit.php';
require_once __DIR__ . '/../logger/log.php';

$audit = new AuditLogger($conn);
$log   = new Logger();

if (!isset($_GET['id'])) {
    header("Location: /capstone-rapid-opms/main.php?page=inventory/list&error=No product selected!");
    exit;
}

$id = (int)$_GET['id'];

// ✅ Get product_title first
$get = $conn->prepare("SELECT product_title FROM inventory WHERE id = ?");
if (!$get) { die("Prepare failed: " . $conn->error); }

$get->bind_param("i", $id);
$get->execute();
$row = $get->get_result()->fetch_assoc();
$product_name = $row['product_title'] ?? 'Unknown Product';
$get->close();

// ✅ Soft delete
$stmt = $conn->prepare("UPDATE inventory SET deleted_at = NOW() WHERE id = ?");
if (!$stmt) { die("Prepare failed: " . $conn->error); }
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// ✅ Audit log
$admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
$description = "Deleted Inventory: {$product_name} (ID: {$id}) by '{$admin_name}'";
$audit->log('DELETE','Inventory',$description);
$log->info("Inventory (DELETE): Product Name: {$product_name} ID: {$id}");
// ✅ Redirect
header("Location: /capstone-rapid-opms/main.php?page=inventory/list");
exit;