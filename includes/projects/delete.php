<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../../includes/rapid_opms.php';

// Check if an ID is provided
if (!isset($_GET['id'])) {
    // Redirect back to the list with an error message
    header("Location: main.php?page=projects/list&error=No project ID provided.");
    exit;
}

$project_id = (int)$_GET['id'];

// Prepare and execute the delete statement
$stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);

if ($stmt->execute()) {
    // Success, redirect with a success message
    $_SESSION['success'] = "Project successfully deleted!";
    header("Location: main.php?page=projects/list");
    exit;
} else {
    // Failure, redirect with an error message
    header("Location: main.php?page=projects/list&error=" . urlencode($stmt->error));
    exit;
}

$stmt->close();
$conn->close();
?>
