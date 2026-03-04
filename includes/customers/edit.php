<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No customer ID provided.</div>";
    exit;
}

$id = (int)$_GET['id'];

// --- Fetch old customer data
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$customer) {
    echo "<div class='alert alert-danger'>Customer not found.</div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']);
    $company_name = trim($_POST['company_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $address      = trim($_POST['address']);

    // --- Update customer
    $stmt = $conn->prepare("
        UPDATE customers SET
            name = ?,
            company_name = ?,
            email = ?,
            phone = ?,
            address = ?
        WHERE id = ?
    ");
    $stmt->bind_param("sssssi", $name, $company_name, $email, $phone, $address, $id);

    if ($stmt->execute()) {

        // --- Compare old vs new values and log only modified fields
        $fields = [
            'name' => [$customer['name'], $name],
            'company_name' => [$customer['company_name'], $company_name],
            'email' => [$customer['email'], $email],
            'phone' => [$customer['phone'], $phone],
            'address' => [$customer['address'], $address]
        ];

        $changes = [];
        foreach ($fields as $field => [$old, $new]) {
            if ((string)$old !== (string)$new) {
                $changes[] = "[$field: {$old} → {$new}]";
            }
        }

        if (!empty($changes)) {
            $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $audit_desc = "Edited Customer (ID: $id) by '{$admin_name}': " . implode(', ', $changes);
            $audit->log('UPDATE', 'Customer', $audit_desc);
        }

        $_SESSION['success_customers'] = "Customer updated successfully.";
        header("Location: main.php?page=customers/list&id=".$id);
        exit();

    } else {
        echo "<div class='alert alert-danger'>Error: {$stmt->error}</div>";
    }
    $stmt->close();
}
?>

<div class="container py-4">
<div class="card">
    <div class="d-flex justify-content-between align-items-center">
        <h4 class="mb-2">
            <i class="fa fa-edit me-2"></i>Edit Customer
        </h4>
    </div>

    <div class="card-body">
      
      <form method="POST" class="row g-3">

        <div class="col-md-4">
          <label for="name" class="form-label"><strong>Name: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="company_name" class="form-label"><strong>Company: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="company_name" id="company_name" class="form-control" value="<?= htmlspecialchars($customer['company_name']) ?>">
        </div>
        <div class="col-md-4">
          <label for="email" class="form-label"><Strong>Email: </Strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>">
        </div>
        <div class="col-md-4">
          <label for="phone" class="form-label"><strong>Phone: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>">
        </div>

        <div class="col-md-8">
          <label for="address" class="form-label"><strong>Address: </strong> <span class="text-danger">*</span></label>
          <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($customer['address']) ?>">
        </div>
        
        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
          <a href="main.php?page=customers/list" class="btn btn-secondary">Cancel</a>
          <button type="submit" id="updateBtn" class="btn btn-success" disabled>Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const updateBtn = document.getElementById('updateBtn');

    const originalValues = {};
    const fieldsToWatch = ['name', 'company_name', 'email', 'phone', 'address'];

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

            const val = (el.value || '').trim();
            if (val !== originalValues[id]) {
                hasChanges = true;
            }
        });

        // Toggle state + color
        updateBtn.disabled = !hasChanges;
    }

    setTimeout(() => {
        storeOriginalValues();
        checkChanges();
    }, 200);

    fieldsToWatch.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', checkChanges);
            el.addEventListener('change', checkChanges);
        }
    });
});
</script> 