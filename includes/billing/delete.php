<?php
session_start();
include_once __DIR__ . '/../rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';
include_once __DIR__ . '/../logger/log.php';


$audit = new AuditLogger($conn);
$log = new Logger();

$id = (int)$_GET['id'];

// Check status first
$stmt = $conn->prepare("SELECT status, project_id FROM billing WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param('i',  $id);
$stmt->execute();
$res = $stmt->get_result();
$billing = $res->fetch_assoc();
$stmt->close();

if (!$billing) {
    $_SESSION['error_billing'] = 'Billing record not found.';
    header('Location: ../../main.php?page=billing/list');
    exit;
}
 
if ($billing['status'] === 'Paid') {
    $_SESSION['error_billing'] = 'Paid billing cannot be deleted.';
    header('Location: ../../main.php?page=billing/list');
    exit;
}

// Soft delete
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE billing SET deleted_at = ? WHERE id = ?");
$stmt->bind_param('si', $now, $id);
$stmt->execute();
$stmt->close();

$project_name = "";

//Get the project name from project_id
$stmt_proj = $conn->prepare("SELECT name FROM projects WHERE id = ?");
$stmt_proj->bind_param("i", $billing['project_id']); // use project_id not id!!
$stmt_proj->execute();
$stmt_proj->bind_result($project_name);
$stmt_proj->fetch();
$stmt_proj->close();


$admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
$description = "Deleted Billing: {$project_name} (ID: {$id}) by '{$admin_name}'";
$audit->log('DELETE', 'Billing', $description);


$_SESSION['success_billing'] = 'Billing deleted successfully.';
header('Location: ../../main.php?page=billing/list');
exit;