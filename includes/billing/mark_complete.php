<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    header("Location: ../../main.php?page=billing/list&error=No billing ID provided.");
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("UPDATE billing SET status='Complete' WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: ../../main.php?page=billing/list&success=Billing marked as complete");
    exit;
} else {
    header("Location: ../../main.php?page=billing/list&error=" . urlencode($stmt->error));
    exit;
}
$stmt->close();
$conn->close(); 