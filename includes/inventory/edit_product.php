<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No product ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_title = $_POST['product_title'];
    $category = $_POST['category'];
    $description = $_POST['description'] !== '' ? $_POST['description'] : null;
    $quantity = $_POST['quantity'];
    $in_stock = $_POST['in_stock'];
    $buying_price = $_POST['buying_price'];
    $selling_price = $_POST['selling_price'];
    $supplier_id = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $stmt = $conn->prepare("UPDATE inventory SET product_title=?, category=?, description=?, quantity=?, in_stock=?, buying_price=?, selling_price=?, supplier_id=? WHERE id=?");
    $stmt->bind_param("sssiiiddi", $product_title, $category, $description, $quantity, $in_stock, $buying_price, $selling_price, $supplier_id, $id);
    if ($stmt->execute()) {
        header("Location: ../../main.php?page=inventory/list&success=1");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: {$stmt->error}</div>";
    }
    $stmt->close();
}
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();
if (!$product) {
    echo "<div class='alert alert-danger'>Product not found.</div>";
    exit;
}
?>
<div class="container py-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fa fa-edit me-2"></i>Edit Product: <?= htmlspecialchars($product['product_title']) ?></h5>
      <a href="main.php?page=inventory/list" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label for="product_title" class="form-label">Product Name</label>
          <input type="text" name="product_title" id="product_title" class="form-control" value="<?= htmlspecialchars($product['product_title']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="category" class="form-label">Category</label>
          <input type="text" name="category" id="category" class="form-control" value="<?= htmlspecialchars($product['category']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea name="description" id="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label for="quantity" class="form-label">Quantity</label>
          <input type="number" name="quantity" id="quantity" class="form-control" value="<?= $product['quantity'] ?>" required>
        </div>
        <div class="mb-3">
          <label for="in_stock" class="form-label">In Stock</label>
          <input type="number" name="in_stock" id="in_stock" class="form-control" value="<?= $product['in_stock'] ?>" required>
        </div>
        <div class="mb-3">
          <label for="buying_price" class="form-label">Buying Price</label>
          <input type="number" name="buying_price" id="buying_price" class="form-control" step="0.01" value="<?= $product['buying_price'] ?>" required>
        </div>
        <div class="mb-3">
          <label for="selling_price" class="form-label">Selling Price</label>
          <input type="number" name="selling_price" id="selling_price" class="form-control" step="0.01" value="<?= $product['selling_price'] ?>" required>
        </div>
        <div class="mb-3">
          <label for="supplier_id" class="form-label">Supplier ID</label>
          <input type="number" name="supplier_id" id="supplier_id" class="form-control" value="<?= htmlspecialchars($product['supplier_id'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div> 