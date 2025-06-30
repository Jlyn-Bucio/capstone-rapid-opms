<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/includes/rapid_opms.php'; // Update path accordingly

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $name = trim($_POST['name']);
    $contact_person = trim($_POST['contact_person']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Basic validation
    if (empty($name)) {
        die('Supplier Name is required.');
    }

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);

    if ($stmt->execute()) {
        // Redirect to suppliers list or success page
        header("Location: index.php?page=supplier/list&msg=Supplier added successfully");
        exit;
    } else {
        die("Execute failed: " . $stmt->error);
    }
} else {
    // If not POST, redirect to create form
    header("Location: create.php");
    exit;
}
