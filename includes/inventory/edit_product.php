<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====================
// INIT SESSION & INCLUDES
// ====================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn); // <-- initialize audit logger

// ====================
// VALIDATE ID
// ====================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo "<div class='alert alert-danger'>Invalid Product ID.</div>";
    exit;
}

// ====================
// FETCH PRODUCT
// ====================
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "<div class='alert alert-danger'>Product not found.</div>";
    exit;
}

// ====================
// FETCH SUPPLIERS
// ====================
$suppliers_result = $conn->query("SELECT * FROM suppliers ORDER BY name ASC");

// ====================
// FETCH CATEGORIES FROM DATABASE
// ====================
$categories_result_query = $conn->query("SELECT name FROM categories ORDER BY name ASC");
$categories = [];
while ($row = $categories_result_query->fetch_assoc()) {
    $categories[] = $row['name'];
}

// ====================
// HANDLE POST (UPDATE)
// ====================
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_title  = $_POST['product_title'];
    $category       = $_POST['category'];
    $description    = $_POST['description'] !== '' ? $_POST['description'] : null;
    $quantity       = $_POST['quantity'];
    $in_stock       = $_POST['in_stock'];
    $buying_price   = $_POST['buying_price'];
    $selling_price  = $_POST['selling_price'];
    $supplier_id    = !empty($_POST['supplier_id']) ? $_POST['supplier_id'] : null;

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

    // ====================
    // Track changes for audit
    // ====================
    $changes = [];
    foreach (['product_title','category','description','quantity','in_stock','buying_price','selling_price','supplier_id'] as $field) {
        if ($product[$field] != ($$field ?? null)) {
            $changes[] = "$field: '{$product[$field]}' => '{$$field}'";
        }
    }

    // ====================
    // UPDATE INVENTORY
    // ====================
    $stmt = $conn->prepare("
        UPDATE inventory SET
            product_title = ?,
            category = ?,
            description = ?,
            quantity = ?,
            in_stock = ?,
            buying_price = ?,
            selling_price = ?,
            supplier_id = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssiiiddi",
        $product_title,
        $category,
        $description,
        $quantity,
        $in_stock,
        $buying_price,
        $selling_price,
        $supplier_id,
        $id
    );

    if ($stmt->execute()) {

        // ====================
        // AUDIT LOG
        // ====================
        $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $audit_desc = "Edited Inventory (ID: $id) for project '{$project_name}' by '{$admin_name}': "
                . implode(', ', $changes);
            $audit->log('UPDATE', 'Inventory', $audit_desc);
        $_SESSION['success_inventory'] = "Product updated successfully.";
        header("Location: main.php?page=inventory/list");
        exit;

    } else {
        $error = "Update failed: " . $conn->error;
    }
    $stmt->close();
}
?>

<div class="container py-4">
    <div class="card shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3 p-3">
            <h4 class="mb-0"><i class="fa fa-edit me-2"></i>Edit Product</h4>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3">

                <!-- Product Name -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Product Name: <span class="text-danger">*</span></label>
                    <input type="text" id="product_title" name="product_title" class="form-control"
                           required value="<?= htmlspecialchars($product['product_title']) ?>">
                </div>

                <!-- Category Dropdown -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Category: <span class="text-danger">*</span></label>
                    <select id="categorySelect" name="category" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($product['category'] === $cat) ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="newCategoryInput" class="form-control mt-2" placeholder="Type new category" style="display:none;" />
                </div>

                <!-- Supplier -->
                <div class="col-md-4">
                    <label class="form-label fw-bold">Supplier: </label>
                    <select id="supplier_id" name="supplier_id" class="form-select">
                        <option value="">None</option>
                        <?php while ($row = $suppliers_result->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>" <?= ($product['supplier_id'] == $row['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Quantity -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Quantity: <span class="text-danger">*</span></label>
                    <input type="number" id="quantity" name="quantity" class="form-control" value="<?= $product['quantity'] ?>" required>
                </div>

                <!-- In Stock -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">In Stock: <span class="text-danger">*</span></label>
                    <input type="number" id="in_stock" name="in_stock" class="form-control" value="<?= $product['in_stock'] ?>" required>
                </div>

                <!-- Buying Price -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Buying Price (₱): <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="buying_price" name="buying_price" class="form-control" value="<?= $product['buying_price'] ?>" required>
                </div>

                <!-- Selling Price -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">Selling Price (₱): <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" id="selling_price" name="selling_price" class="form-control" value="<?= $product['selling_price'] ?>" required>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label fw-bold">Description: </label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <!-- Buttons -->
                <div class="col-12 text-end">
                    <a href="main.php?page=inventory/list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="updateBtn" disabled>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const updateBtn = document.getElementById('updateBtn');
    const categorySelect = document.getElementById('categorySelect');
    const newCategoryInput = document.getElementById('newCategoryInput');

    const fieldsToWatch = ['product_title','categorySelect','description','quantity','in_stock','buying_price','selling_price','supplier_id'];
    const originalValues = {};

    function storeOriginalValues() {
        fieldsToWatch.forEach(id => {
            const el = document.getElementById(id);
            if (el) originalValues[id] = (el.value || '').trim();
        });
    }

    function checkChanges() {
        let hasChanges = false;
        fieldsToWatch.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            if ((el.value || '').trim() !== originalValues[id]) hasChanges = true;
        });
        updateBtn.disabled = !hasChanges;
    }

    // Initialize original values
    setTimeout(() => { storeOriginalValues(); checkChanges(); }, 200);

    // Listen for changes
    fieldsToWatch.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', checkChanges);
        el.addEventListener('change', checkChanges);
    });

    // Dynamic "Other" category
    function addCategoryToDropdown(value) {
        if (!value) return;
        const exists = Array.from(categorySelect.options).some(opt => opt.value.toLowerCase() === value.toLowerCase());
        if (exists) return;

        const option = document.createElement('option');
        option.text = value;
        option.value = value;
        const otherOption = categorySelect.querySelector('option[value="Other"]');
        categorySelect.insertBefore(option, otherOption);
        categorySelect.value = value;
    }

    categorySelect.addEventListener('change', function() {
        if (this.value === 'Other') {
            newCategoryInput.style.display = 'block';
            newCategoryInput.focus();
        } else {
            newCategoryInput.style.display = 'none';
        }
    });

    newCategoryInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addCategoryToDropdown(this.value.trim());
            this.value = '';
            this.style.display = 'none';
        }
    });

    newCategoryInput.addEventListener('blur', function() {
        addCategoryToDropdown(this.value.trim());
        this.value = '';
        this.style.display = 'none';
    });

});
</script>