<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name        = trim($_POST['product_title']);
    $category    = trim($_POST['category']);
    $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
    $quantity    = (int)$_POST['quantity'];
    $in_stock    = (int)$_POST['in_stock'];
    $buy_price   = (float)$_POST['buying_price'];
    $sell_price  = (float)$_POST['selling_price'];
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

    // Auto-add new category if it doesn't exist
    if (!empty($category)) {
        $check_cat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check_cat->bind_param("s", $category);
        $check_cat->execute();
        $result = $check_cat->get_result();
        
        if ($result->num_rows === 0) {
            // Insert new category
            $insert_cat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $insert_cat->bind_param("s", $category);
            $insert_cat->execute();
            $insert_cat->close();
        }
        $check_cat->close();
    }

    $stmt = $conn->prepare("
        INSERT INTO inventory
        (product_title, category, description, quantity, in_stock, buying_price, selling_price, supplier_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssiiddi",
        $name,
        $category,
        $description,
        $quantity,
        $in_stock,
        $buy_price,
        $sell_price,
        $supplier_id
    );

    if ($stmt->execute()) {
        $_SESSION['success_inventory'] = "Product created successfully.";
        // Log the creation in the audit trail
        $description = "Created new Inventory: '{$name}' (ID: {$conn->insert_id})";
        $audit->log('CREATE', 'Inventory', $description);
        header("Location: ../../main.php?page=inventory/list");
        exit;
    } else {
        $_SESSION['error_inventory'] = "Failed to create product.";
        header("Location: ../../main.php?page=inventory/create");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>