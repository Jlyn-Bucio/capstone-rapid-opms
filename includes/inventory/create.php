<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "rapid_opms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch suppliers
$suppliers = mysqli_query($conn, "SELECT * FROM suppliers ORDER BY name ASC");
?>

<div class="container py-5">

    <div class="mb-4 px-4 py-3 rounded" style="background-color: #b0b0b0; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);">
      <h5><i class="fa fa-plus me-2"></i>Add New Product</h5>
    </div>

    <form action="../../includes/inventory/save.php" method="POST" enctype="multipart/form-data">
      <div class="row">
        
        <!-- Left Column -->
        <div class="col-md-6">
          <h6 class="bg-dark text-white p-2 rounded"><i class="fa fa-box me-2"></i>Product Information</h6>

          <div class="mb-3">
            <label for="product_title" class="form-label">Product Name <span class="text-danger">*</span></label>
            <input type="text" name="product_title" id="product_title" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category" id="category" class="form-select" required>
              <option value="">Select Category</option>
              <option>Cement</option>
              <option>Steel Bars</option>
              <option>Aggregates</option>
              <option>Lumber</option>
              <option>Plumbing</option>
              <option>Electrical</option>
              <option>Hardware</option>
              <option>Tools</option>
              <option>Paint</option>
              <option>Safety Equipment</option>
              <option>Other</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" rows="4"></textarea>
          </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
          <h6 class="bg-black text-white p-2 rounded border"><i class="fa fa-warehouse me-2"></i>Inventory & Price Details</h6>

          <div class="mb-3">
            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="0" required>
          </div>

          <div class="mb-3">
            <label for="in_stock" class="form-label">In Stock <span class="text-danger">*</span></label>
            <input type="number" name="in_stock" id="in_stock" class="form-control" min="0" required>
          </div>

          <div class="mb-3">
            <label for="buying_price" class="form-label">Buying Price (₱) <span class="text-danger">*</span></label>
            <input type="number" name="buying_price" id="buying_price" class="form-control" step="0.01" required>
          </div>

          <div class="mb-3">
            <label for="selling_price" class="form-label">Selling Price (₱) <span class="text-danger">*</span></label>
            <input type="number" name="selling_price" id="selling_price" class="form-control" step="0.01" required>
          </div>

          <h6 class="bg-black text-white p-2 mt-4 rounded border"><i class="fa fa-truck me-2"></i>Supplier Information</h6>

          <div class="mb-3">
            <label for="supplier_id" class="form-label">Supplier</label>
            <select name="supplier_id" id="supplier_id" class="form-select">
              <option value="">None</option>
              <?php while ($row = mysqli_fetch_assoc($suppliers)): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>

      </div> <!-- End row -->

      <div class="mt-4 d-flex justify-content-between">
        <a href="main.php?page=inventory/product_details" class="btn btn-secondary">
          <i class="fa fa-arrow-left me-1"></i> Back to Inventory
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-save me-1"></i> Save Product
        </button>
      </div>

    </form>
  </div>
</div>
