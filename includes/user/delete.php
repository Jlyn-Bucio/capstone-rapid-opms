<?php
// Include database connection
include_once __DIR__ . '/../../includes/rapid_opms.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    // Redirect if no ID is provided
    header("Location: main.php?page=user/list");
    exit();
}

// Prepare and execute the delete statement
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    // Set a success message and redirect
    $_SESSION['success_message'] = "User deleted successfully!";
} else {
    // Set an error message if something goes wrong
    $_SESSION['error_message'] = "Error deleting user: " . $stmt->error;
}

$stmt->close();
$conn->close();

// Redirect back to the user list
header("Location: main.php?page=user/list");
exit();
