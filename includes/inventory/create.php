<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

// --- after successfully inserting the product
$product_id = $conn->insert_id; // ID of the newly created product
$product_title = trim($_POST['product_title'] ?? '');
$admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';

// --- create audit description
$description = "Created new Inventory: '{$product_title}' (ID: {$product_id}) by '{$admin_name}'";
// --- log it
$audit->log('CREATE', 'Inventory', $description);


// Fetch suppliers
$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name ASC");
// Fetch categories from database
$categories_result = $conn->query("SELECT name FROM categories ORDER BY name ASC");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['name'];
}
?>
      <?php if (isset($_SESSION['success_inventory'])): ?>
        <div class="alert alert-success">
          <?= $_SESSION['success_inventory']; unset($_SESSION['success_inventory']); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['error_inventory'])): ?>
        <div class="alert alert-danger">
          <?= $_SESSION['error_inventory']; unset($_SESSION['error_inventory']); ?>
        </div>
      <?php endif; ?>

      <form action="includes/inventory/save.php" method="POST" enctype="multipart/form-data">
  
      <div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center p-3">
      <h4 class="mb-0">
        <i class="fas fa-plus-square me-2"></i>Create New Product
      </h4>
    </div>

    <div class="card-body">

        <div class="row">

          <!-- LEFT -->
          <div class="col-md-6">
            <h6 class="bg-dark text-white p-2 rounded">
              <i class="fa fa-box me-2"></i>Product Information
            </h6>

            <div class="mb-3">
              <label class="form-label fw-bold">Product Name <span class="text-danger">*</span></label>
              <input type="text" name="product_title" class="form-control" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Category <span class="text-danger">*</span></label>
              <select name="category" id="categorySelect" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                  <option><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
                <option value="Other">Other</option>
              </select>

              <!-- Input for typing new category, hidden by default -->
              <input type="text" id="newCategoryInput" class="form-control mt-2" placeholder="Type new category" style="display:none;" />
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Description</label>
              <textarea name="description" class="form-control" rows="4"></textarea>
            </div>
          </div>

          <!-- RIGHT -->
          <div class="col-md-6">
            <h6 class="bg-black text-white p-2 rounded">
              <i class="fa fa-warehouse me-2"></i>Inventory & Price
            </h6>

            <div class="mb-3">
              <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
              <input type="number" name="quantity" class="form-control" min="0" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">In Stock <span class="text-danger">*</span></label>
              <input type="number" name="in_stock" class="form-control" min="0" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Buying Price (₱) <span class="text-danger">*</span></label>
              <input type="number" name="buying_price" class="form-control" step="0.01" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Selling Price (₱) <span class="text-danger">*</span></label>
              <input type="number" name="selling_price" class="form-control" step="0.01" required>
            </div>

            <h6 class="bg-black text-white p-2 rounded mt-4">
              <i class="fa fa-truck me-2"></i>Supplier
            </h6>

            <div class="mb-3">
              <select name="supplier_id" class="form-select">
                <option value="">None</option>
                <?php while ($row = $suppliers->fetch_assoc()): ?>
                  <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

          </div>
        </div>

        <div class="text-end mt-3">
          <a href="main.php?page=inventory/list" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-success">
            Create Product
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
const categorySelect = document.getElementById('categorySelect');
const newCategoryInput = document.getElementById('newCategoryInput');

function addCategoryToDropdown(value) {
    if (!value) return;

    // Check kung existing na
    const exists = Array.from(categorySelect.options).some(
        opt => opt.value.toLowerCase() === value.toLowerCase()
    );
    if (exists) return;

    // Create new option
    const option = document.createElement('option');
    option.text = value;
    option.value = value;

    // Insert bago ang "Other"
    const otherOption = categorySelect.querySelector('option[value="Other"]');
    categorySelect.insertBefore(option, otherOption);

    // Piliin ang bagong category
    categorySelect.value = value;
}

// Show input kapag "Other" ang pinili
categorySelect.addEventListener('change', function() {
    if (this.value === 'Other') {
        newCategoryInput.style.display = 'block';
        newCategoryInput.focus();
    } else {
        newCategoryInput.style.display = 'none';
    }
});

// Add category kapag Enter pinindot
newCategoryInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addCategoryToDropdown(this.value.trim());
        this.value = '';
        this.style.display = 'none';
    }
});

// Add category kapag nag-blur (focus out)
newCategoryInput.addEventListener('blur', function() {
    addCategoryToDropdown(this.value.trim());
    this.value = '';
    this.style.display = 'none';
});
</script>