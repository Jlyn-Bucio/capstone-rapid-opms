<?php
// db connection
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name         = trim($_POST['name']);
    $company_name = trim($_POST['company_name']);
    $email        = trim($_POST['email']);
    $phone        = trim($_POST['phone']);
    $address      = trim($_POST['address']);

    // Basic validation
    if (!$name || !$address) {
        $error = "Name and Address are required.";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO customers (name, company_name, email, phone, address) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssss", $name, $company_name, $email, $phone, $address);

        if ($stmt->execute()) {

            // --- Get the inserted customer ID
            $customer_id = $stmt->insert_id;

            // --- Log audit
            $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $description = "Created Customer: {$name} (ID: {$customer_id}) by '{$admin_name}'";
            $audit->log('CREATE', 'Customer', $description);

            $_SESSION['success_customers'] = 'Customer created successfully.';
            header('Location: main.php?page=customers/list');
            exit;

        } else {
            $error = 'Database error: ' . $stmt->error;
        }

        $stmt->close();
    }
}
?>
<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>Create New Customer</h4>
        </div>

        <div class="card-body">

            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label"><strong>Name: </strong><span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label for="company_name" class="form-label"><strong>Company Name: </strong><span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company_name ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label for="email" class="form-label"><strong>Email: </strong><span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label for="phone" class="form-label"><strong>Phone: </strong><span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>

                <div class="col-md-8">
                    <label for="address" class="form-label"><strong>Address: </strong> <span class="text-danger">*</span></label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($address ?? '') ?>">
                </div>

                <div class="text-end">
                    <a href="main.php?page=customers/list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">Create Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
