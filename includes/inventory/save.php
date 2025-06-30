<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once __DIR__ . '/../../includes/rapid_opms.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['product_title'];
    $category = $_POST['category'];
    $description = isset($_POST['description']) && $_POST['description'] !== '' ? $_POST['description'] : null;
    $quantity = $_POST['quantity'];
    $in_stock = $_POST['in_stock'];
    $buy_price = $_POST['buying_price'];
    $sell_price = $_POST['selling_price'];
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO inventory 
        (product_title, category, description, quantity, in_stock, buying_price, selling_price, supplier_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    // If supplier_id is null, bind as null (i) type, otherwise as int
    if ($supplier_id === null) {
        $stmt->bind_param("sssiiidd", $name, $category, $description, $quantity, $in_stock, $buy_price, $sell_price, $supplier_id);
    } else {
        $stmt->bind_param("sssiiidd", $name, $category, $description, $quantity, $in_stock, $buy_price, $sell_price, $supplier_id);
    }

    if ($stmt->execute()) {
        header("Location: ../../main.php?page=inventory/list&success=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
