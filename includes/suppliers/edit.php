<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No supplier ID provided.</div>";
    exit;
}

$id = (int)$_GET['id'];

// --- fetch supplier
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();
$stmt->close();

if (!$supplier) {
    echo "<div class='alert alert-danger'>Supplier not found.</div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Get POST values
    $name = $_POST['name'];
    $contact_person = $_POST['contact_person'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // --- Compare old vs new values for audit
    $fields = [
        'name' => [$supplier['name'], $name],
        'contact_person' => [$supplier['contact_person'], $contact_person],
        'email' => [$supplier['email'], $email],
        'phone' => [$supplier['phone'], $phone],
        'address' => [$supplier['address'], $address]
    ];

    $changes = [];
    foreach ($fields as $field => [$old, $new]) {
        if ((string)$old !== (string)$new) {
            $changes[] = "[$field: {$old} → {$new}]";
        }
    }

    // --- Update supplier
    $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, email=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $contact_person, $email, $phone, $address, $id);

    if ($stmt->execute()) {
        // --- Audit logging (only if changes exist)
        if (!empty($changes)) {
            $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $audit_desc = "Edited Supplier (ID: $id) by '{$admin_name}': " . implode(', ', $changes);
            $audit->log('UPDATE', 'Supplier', $audit_desc);
        }

        $_SESSION['success_supplier'] = "Supplier updated.";
        header("Location: main.php?page=suppliers/list&id=$id");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error: {$stmt->error}</div>";
    }

    $stmt->close();
}
?>

<div class="container py-4">
  <div class="card">
    <div class=" d-flex justify-content-between align-items-center">
      <h4 class="mb-2"><i class="fa fa-edit me-2"></i>Edit Supplier</h4>
    </div>

    <div class="card-body">

      <form method="POST" class="row g-3">

        <div class="col-md-4">
          <label for="name" class="form-label"><strong>Name: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($supplier['name']) ?>" required>
        </div>

        <div class="col-md-4">
          <label for="contact_person" class="form-label"><strong>Contact Person: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?= htmlspecialchars($supplier['contact_person']) ?>">
        </div>

        <div class="col-md-4">
          <label for="email" class="form-label"><strong>Email: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($supplier['email']) ?>">
        </div>

        <div class="col-md-4">
          <label for="phone" class="form-label"><strong>Phone: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($supplier['phone']) ?>">
        </div>

        <div class="col-4">
          <label for="address" class="form-label"><strong>Address: </strong></label>
            <label><span class="text-danger">*</span></label>
          <input type="text" name="address" id="address" class="form-control" value="<?= htmlspecialchars($supplier['address']) ?>">
        </div>
        
        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
          <a href="main.php?page=suppliers/list" class="btn btn-secondary">Cancel</a>
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
    const fieldsToWatch = ['name', 'contact_person', 'email', 'phone', 'address'];

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