<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    header("Location: ../../main.php?page=suppliers/list&error=No supplier ID provided.");
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: ../../main.php?page=suppliers/list&success=Supplier deleted");
    exit;
} else {
    header("Location: ../../main.php?page=suppliers/list&error=" . urlencode($stmt->error));
    exit;
}
$stmt->close();
$conn->close(); 