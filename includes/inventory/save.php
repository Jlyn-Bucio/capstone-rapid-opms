<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['product_title'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $in_stock = $_POST['in_stock'];
    $buy_price = $_POST['buying_price'];
    $sell_price = $_POST['selling_price'];
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;



    // Insert into database
    $stmt = $conn->prepare("INSERT INTO inventory 
        (name, category, description, quantity, in_stock, buy_price, sell_price, supplier_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssii ddsi", $name, $category, $description, $quantity, $in_stock, $buy_price, $sell_price, $supplier_id);

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
