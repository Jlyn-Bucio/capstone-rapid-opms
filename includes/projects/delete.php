<?php
session_start();
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

$id = (int)$_GET['id'];

// Fetch project info (name + status) and check deleted_at
$stmt = $conn->prepare("SELECT name, status FROM projects WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$project = $res->fetch_assoc();
$stmt->close();

if (!$project) {
    $_SESSION['error_project'] = 'Project record not found.';
    header('Location: ../../main.php?page=projects/list');
    exit;
}

if ($project['status'] === 'Paid') {
    $_SESSION['error_project'] = 'Paid project cannot be deleted.';
    header('Location: ../../main.php?page=projects/list');
    exit;
}

// Soft delete
$now = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE projects SET deleted_at = ? WHERE id = ?");
$stmt->bind_param('si', $now, $id);
$stmt->execute();
$stmt->close();

// Use the fetched project name for audit log
$admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
$description = "Deleted Project: {$project['name']} (ID: {$id}) by '{$admin_name}'";
$audit->log('DELETE', 'Project', $description);

$_SESSION['success_project'] = 'Project deleted successfully.';
header('Location: ../../main.php?page=projects/list');
exit;