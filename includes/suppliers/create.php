<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

// --- instantiate audit logger
$audit = new AuditLogger($conn);

// --- initialize error variable
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // --- insert supplier
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $name, $contact_person, $email, $phone, $address);

    if ($stmt->execute()) {
        // --- get new supplier ID
        $supplier_id = $conn->insert_id;

        // --- audit log
        $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
        $audit_desc = "Created Supplier '{$name}' (ID: $supplier_id) by '{$admin_name}'";
        $audit->log('CREATE', 'Supplier', $audit_desc);

        $_SESSION['success_supplier'] = "Supplier created successfully.";
        header("Location: main.php?page=suppliers/list&id=".$supplier_id);
        exit;
    } else {
        // assign error to $error variable
        $error = "Database error: {$stmt->error}";
    }

    $stmt->close();
}
?>

<div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fa-solid fa-boxes-packing me-2"></i>Create New Supplier
      </h4>
    </div>

    <div class="card-body">
      <!-- Display error if any -->
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- form here -->

      <form method="POST" class="row g-3">

        <!-- SUPPLIER NAME -->
        <div class="col-md-6">
          <label class="form-label fw-bold">
            Supplier's Name:
            <span class="text-danger"><?= $errors['name'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="name"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 required>
        </div>

        <!-- CONTACT PERSON -->
        <div class="col-md-6">
          <label class="form-label fw-bold">
            Contact Person:
            <span class="text-danger"><?= $errors['contact_person'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="contact_person"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>"
                 required>
        </div>

        <!-- EMAIL -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            Email:
            <span class="text-danger"><?= $errors['email'] ?? '*' ?></span>
          </label>
          <input type="email"
                 name="email"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 required>
        </div>

        <!-- PHONE -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            Contact Number:
            <span class="text-danger"><?= $errors['phone'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="phone"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                 required>
        </div>

        <!-- ADDRESS -->
        <div class="col-md-4">
          <label class="form-label fw-bold">
            Address:
            <span class="text-danger"><?= $errors['address'] ?? '*' ?></span>
          </label>
          <input type="text"
                 name="address"
                 class="form-control"
                 value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                 required>
        </div>

        <!-- BUTTONS -->
        <div class="col-12 text-end mt-3">
          <a href="main.php?page=suppliers/list" class="btn btn-secondary">
            Cancel
          </a>
          <button type="submit" class="btn btn-success">
            Create Supplier
          </button>
        </div>

      </form>
    </div>
  </div>
</div>