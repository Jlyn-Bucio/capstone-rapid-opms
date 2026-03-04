<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../../includes/rapid_opms.php';

$product_title = $_POST['product_title'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$quantity = $_POST['quantity'] ?? 0;
$in_stock = $_POST['in_stock'] ?? 0;
$buying_price = $_POST['buying_price'] ?? 0;
$selling_price = $_POST['selling_price'] ?? 0;
$supplier_id = $_POST['supplier_id'] ?? NULL;

// --- save new category if not exists
$stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
$stmt->bind_param("s", $category);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $insertCat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $insertCat->bind_param("s", $category);
    $insertCat->execute();
    $insertCat->close();
}
$stmt->close();

// --- insert product
$stmt2 = $conn->prepare("INSERT INTO inventory (product_title, category, description, quantity, in_stock, buying_price, selling_price, supplier_id) VALUES (?,?,?,?,?,?,?,?)");
$stmt2->bind_param("sssiidds", $product_title, $category, $description, $quantity, $in_stock, $buying_price, $selling_price, $supplier_id);
if ($stmt2->execute()) {
    $_SESSION['success_inventory'] = "Product created successfully!";
} else {
    $_SESSION['error_inventory'] = "Failed to create product!";
}
$stmt2->close();

header("Location: ../../main.php?page=inventory/create");
exit;