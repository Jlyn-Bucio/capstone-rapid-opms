<?php
session_start();
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);
$admin_username = $_SESSION['username'] ?? $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'System';
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    $_SESSION['error_message'] = "No user ID provided.";
    header("Location: main.php?page=user/list");
    exit();
}

// Fetch user info before deletion
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: main.php?page=user/list");
    exit();
}

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "User deleted successfully.";

    // Friendly audit description
    $description = "User '{$user['name']}' (Email: {$user['email']}, ID: {$user_id}) was deleted by '{$admin_username}' on " . date('Y-m-d H:i:s');
    $audit->log('DELETE', 'User', $description);

} else {
    $_SESSION['error_message'] = "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: main.php?page=user/list");
exit();