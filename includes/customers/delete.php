<?php
session_start();
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

if (!isset($_GET['id'])) {
    header("Location: /capstone-rapid-opms/main.php?page=customers/list&error=No detail selected");
    exit;
}

$id = (int)$_GET['id'];

// --- Fetch customer name for audit
$stmt_fetch = $conn->prepare("SELECT name FROM customers WHERE id = ?");
$stmt_fetch->bind_param("i", $id);
$stmt_fetch->execute();
$stmt_fetch->bind_result($customer_name);
$stmt_fetch->fetch();
$stmt_fetch->close();

// --- Soft delete customer
$stmt = $conn->prepare("UPDATE customers SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    // This system process the delete action inside list.php not here.
    // --- Audit log
    $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
    $description = "Deleted Customer: {$customer_name} (ID: {$id}) by '{$admin_name}'";
    $audit->log('DELETE', 'Customer', $description);

    $_SESSION['success_customers'] = "Customer deleted successfully.";

} else {
    $_SESSION['error_customers'] = "Delete failed: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: /capstone-rapid-opms/main.php?page=customers/list");
exit;
?>