<?php
session_start();

include_once __DIR__ . '/../../includes/rapid_opms.php';

if (!isset($_GET['id'])) {
    header("Location: /capstone-rapid-opms/main.php?page=manpower/listmanpower&error=No detail selected");
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("UPDATE manpower SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success_manpower'] = "Manpower deleted successfully.";
} else {
    $_SESSION['error_manpower'] = "Delete failed: " . $stmt->error;
}

$stmt->close();
$conn->close();

header("Location: /capstone-rapid-opms/main.php?page=manpower/listmanpower");
exit;