<?php
session_start();

include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';
include_once __DIR__ . '/../logger/log.php';

$audit = new AuditLogger($conn);
$log   = new Logger();

if (!isset($_GET['id'])) {
    header("Location: /capstone-rapid-opms/main.php?page=suppliers/list&error=No detail selected");
    exit;
}

$id = (int)$_GET['id'];

// 1️⃣ Fetch supplier name first
$get = $conn->prepare("SELECT name FROM suppliers WHERE id = ?");
$get->bind_param("i", $id);
$get->execute();
$row = $get->get_result()->fetch_assoc();
$supplier_name = $row['name'] ?? 'Unknown Supplier';
$get->close();

// 2️⃣ Soft delete
$stmt = $conn->prepare("UPDATE suppliers SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success_supplier'] = "Supplier deleted successfully.";
} else {
    $_SESSION['error_supplier'] = "Delete failed: " . $stmt->error;
}

$stmt->close();

// 3️⃣ Audit log (before closing connection)
$admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
$description = "Deleted Supplier: {$supplier_name} (ID: {$id}) by '{$admin_name}'";

$audit->log('DELETE', 'Suppliers', $description);
$log->info("Supplier (DELETE): Supplier Name: {$supplier_name} ID: {$id}");

// 4️⃣ Close connection
$conn->close();

// 5️⃣ Redirect
header("Location: /capstone-rapid-opms/main.php?page=suppliers/list");
exit;