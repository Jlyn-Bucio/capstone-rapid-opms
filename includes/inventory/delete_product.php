<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    header("Location: ../../main.php?page=inventory/list&error=No product ID provided.");
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: ../../main.php?page=inventory/list&success=Product deleted");
    exit;
} else {
    header("Location: ../../main.php?page=inventory/list&error=" . urlencode($stmt->error));
    exit;
}
$stmt->close();
$conn->close();