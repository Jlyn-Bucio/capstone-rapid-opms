<?php
// db connection
include_once __DIR__ . '/../../includes/rapid_opms.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $company_name = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Basic validation (optional, you can expand)
    if (!$name || !$address) {
        $error = "Name and Address are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (name, company_name, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $company_name, $email, $phone, $address);

        if ($stmt->execute()) {
            $success = "Customer created successfully!";
            // Clear form values
            $name = $company_name = $email = $phone = $address = '';
        } else {
            $error = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>
<div class="container py-5">
    <div class="row justify-content-center">

        <div class="card-body">
          <div class="mb-4 px-4 py-3 rounded" style="background-color: #b0b0b0; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);">
            <h5><i class="fa fa-user-plus fa-lg me-3"></i>Create New Customer</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label>Company Name</label>
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company_name ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label>Phone</label>
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="address" name="address" class="form-control" value="<?= htmlspecialchars($address ?? '') ?>">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="reset" class="btn btn-secondary">Reset</button>
                    <button type="submit" class="btn btn-primary">Create Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
