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
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
      <h4 class="mb-0"><i class="fa fa-eye me-2"></i>View Product Details</h4>
      <a href="main.php?page=inventory/list" class="btn btn-primary ">
        <i class="fa fa-arrow-left me-2"></i>Back to List
      </a>
    </div>

      <table class="table table-sm table-bordered mb-0">
        <tr>
          <th style="width: 250px;" class="ps-3">Product Name</th><td class="ps-3"><?= htmlspecialchars($product['product_title']) ?></td></tr>
        <tr>
          <th style="width: 250px;" class="ps-3">Category</th><td class="ps-3"><?= htmlspecialchars($product['category']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Description</th><td class="ps-3"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Quantity</th><td class="ps-3"><?= $product['quantity'] ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">In Stock</th><td class="ps-3"><?= $product['in_stock'] ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Buying Price</th><td class="ps-3">₱<?= number_format($product['buying_price'], 2) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Selling Price</th><td class="ps-3">₱<?= number_format($product['selling_price'], 2) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Supplier ID</th><td class="ps-3"><?= $product['supplier_id'] ?? 'None' ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Date Added</th><td class="ps-3"><?= date('M d, Y', strtotime($product['created_at'])) ?></td></tr>
      </table>
  </div>
</div>
