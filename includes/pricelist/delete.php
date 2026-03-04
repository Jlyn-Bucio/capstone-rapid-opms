<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../rapid_opms.php';

/* =========================
   VALIDATE ID
========================= */
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    $_SESSION['error'] = 'Invalid contractor ID.';
    header("Location: /capstone-rapid-opms/main.php?page=pricelist/list");
    exit;
}

/* =========================
   DELETE FROM PRICE_LIST
========================= */
$stmt = $conn->prepare("DELETE FROM price_list WHERE id = ?");
if (!$stmt) {
    $_SESSION['error'] = 'Database error: ' . $conn->error;
    header("Location: /capstone-rapid-opms/main.php?page=pricelist/list");
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_pricelist'] = 'Contractor deleted successfully.';
    } else {
        $_SESSION['error'] = 'Contractor not found or already deleted.';
    }
} else {
    $_SESSION['error'] = 'Failed to delete contractor.';
}

$stmt->close();

/* =========================
   REDIRECT BACK TO LIST
========================= */
header("Location: /capstone-rapid-opms/main.php?page=pricelist/list");
exit;