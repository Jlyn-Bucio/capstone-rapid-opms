<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../../includes/rapid_opms.php';

// Check if an ID is provided
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No project ID provided.</div>";
    exit;
}

$project_id = (int)$_GET['id'];

// Fetch project details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "<div class='alert alert-danger'>Project not found.</div>";
    exit;
}

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

    // If no errors, update the DB
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE projects SET name = ?, date = ?, location = ?, contractor = ?, size = ?, start_date = ?, customer_id = ?, project_manager = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssssssissi", $name, $date, $location, $contractor, $size, $start_date, $customer_id, $project_manager, $description, $project_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Project successfully updated!";
            header("Location: main.php?page=projects/list");
            exit;
        } else {
            $errors['db'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa fa-edit me-2"></i>Edit Project: <?= htmlspecialchars($project['name']) ?>
            </h5>
            <a href="main.php?page=projects/list" class="btn btn-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
        <div class="card-body">
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
                    <label>Project Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($project['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($project['date']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Location <span class="text-danger">*</span></label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($project['location']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Contractor <span class="text-danger">*</span></label>
                    <input type="text" name="contractor" class="form-control" value="<?= htmlspecialchars($project['contractor']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Project Size <span class="text-danger">*</span></label>
                    <input type="text" name="size" class="form-control" value="<?= htmlspecialchars($project['size']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($project['start_date']) ?>">
                </div>
                <div class="col-md-6">
                    <label>Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select">
                        <option value="">-- Select Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $project['customer_id'] == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?> <?= $c['company_name'] ? '(' . htmlspecialchars($c['company_name']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Project Manager <span class="text-danger">*</span></label>
                    <input type="text" name="project_manager" class="form-control" value="<?= htmlspecialchars($project['project_manager']) ?>">
                </div>
                <div class="col-12">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
