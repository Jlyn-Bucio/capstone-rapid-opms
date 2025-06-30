<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No product ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
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
      <h5 class="mb-0"><i class="fa fa-box me-2"></i>Product Details: <?= htmlspecialchars($product['product_title']) ?></h5>
      <a href="main.php?page=inventory/list" class="btn btn-primary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <table class="table table-bordered">
        <tr><th>Product Name</th><td><?= htmlspecialchars($product['product_title']) ?></td></tr>
        <tr><th>Category</th><td><?= htmlspecialchars($product['category']) ?></td></tr>
        <tr><th>Description</th><td><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></td></tr>
        <tr><th>Quantity</th><td><?= $product['quantity'] ?></td></tr>
        <tr><th>In Stock</th><td><?= $product['in_stock'] ?></td></tr>
        <tr><th>Buying Price</th><td>₱<?= number_format($product['buying_price'], 2) ?></td></tr>
        <tr><th>Selling Price</th><td>₱<?= number_format($product['selling_price'], 2) ?></td></tr>
        <tr><th>Supplier ID</th><td><?= $product['supplier_id'] ?? 'None' ?></td></tr>
        <tr><th>Date Added</th><td><?= date('M d, Y', strtotime($product['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>
</div>
