<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $company_name = $_POST['company_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $stmt = $conn->prepare("INSERT INTO customers (name, company_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $company_name, $email, $phone, $address);
    if ($stmt->execute()) {
        header("Location: ../../main.php?page=customers/list&success=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
    $conn->close();
}
?> 