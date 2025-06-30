<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Fetch inventory products
$inventory = $conn->query("SELECT * FROM inventory ORDER BY id DESC");
?>

<div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-12">

        <div class="px-3 py-2 rounded mb-3" style="background-color: #d1d1d1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-box-seam me-2"></i>Inventory Management</h4>
    <a href="main.php?page=inventory/create" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Add New Product
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Product</th>
          <th>Category</th>
          <th>Quantity</th>
          <th>In Stock</th>
          <th>Buying Price</th>
          <th>Selling Price</th>
          <th>Date Added</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($inventory->num_rows > 0): ?>
          <?php while ($row = $inventory->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td class="text-start">
                <?= htmlspecialchars($row['product_title']) ?>
              </td>
              <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['category']) ?></span></td>
              <td><?= $row['quantity'] ?></td>
              <td>
                <span class="badge bg-success"><?= $row['quantity'] ?> in stock</span>
              </td>
              <td>₱<?= number_format($row['buying_price'], 2) ?></td>
              <td>₱<?= number_format($row['selling_price'], 2) ?></td>
              <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>
              <td>
                <div class="btn-group" role="group">
                  <a href="main.php?page=inventory/view_product&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info me-1" title="View">
                    <i class="bi bi-eye-fill"></i>
                  </a>
                  <a href="main.php?page=inventory/edit_product&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <a href="includes/inventory/delete_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this product?')">
                    <i class="bi bi-trash-fill"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center">No products found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
