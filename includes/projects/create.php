<?php

$conn = new mysqli("localhost", "root", "", "rapid_opms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch customers for dropdown
$customers = [];
$customerResult = $conn->query("SELECT id, name, company_name FROM customers ORDER BY name ASC");
while ($row = $customerResult->fetch_assoc()) {
    $customers[] = $row;
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);
    $location = trim($_POST['location']);
    $contractor = trim($_POST['contractor']);
    $size = trim($_POST['size']);
    $start_date = trim($_POST['start_date']);
    $customer_id = (int)$_POST['customer_id'];
    $project_manager = trim($_POST['project_manager']);
    $description = trim($_POST['description']);

    // Basic validation
    if (empty($name)) $errors['name'] = "Project name is required.";
    if (empty($date)) $errors['date'] = "Date is required.";
    if (empty($location)) $errors['location'] = "Location is required.";
    if ($customer_id === 0) $errors['customer_id'] = "Please select a valid customer.";

    // If no errors, insert into DB
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO projects (name, date, location, contractor, size, start_date, customer_id, project_manager, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssiss", $name, $date, $location, $contractor, $size, $start_date, $customer_id, $project_manager, $description);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Project successfully registered!";
            header("Location: main.php?page=projects/list");
            exit;
        } else {
            $errors['db'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="container py-5">

      <div class="card-body">
        <div class="mb-4 px-4 py-3 rounded" style="background-color: #b0b0b0; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);">
            <h5><i class="fa fa-clipboard-list me-3"></i>Project Registration</h5>
            </div>
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="row g-3">
                <div class="col-md-6">
                    <label>Project Name</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label>Date</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label>Location</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label>Contractor</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="text" name="contractor" class="form-control" value="<?= htmlspecialchars($_POST['contractor'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label>Project Size</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="text" name="size" class="form-control" value="<?= htmlspecialchars($_POST['size'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label>Start Date</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label>Customer</label>
                    <label><span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select">
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> <?= $c['company_name'] ? '(' . htmlspecialchars($c['company_name']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Project Manager</label>
                    <label><span class="text-danger">*</span></label>
                    <input type="text" name="project_manager" class="form-control" value="<?= htmlspecialchars($_POST['project_manager'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Save Project</button>
                </div>
            </form>

    </div>
</div>
</body>
</html>
