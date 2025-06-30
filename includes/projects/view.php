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

// Fetch project details along with customer information
$stmt = $conn->prepare("
    SELECT p.*, c.name AS customer_name, c.company_name, c.email AS customer_email
    FROM projects p
    LEFT JOIN customers c ON p.customer_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();

if (!$project) {
    echo "<div class='alert alert-danger'>Project not found.</div>";
    exit;
}
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fa fa-clipboard-list me-2"></i>Project Details: <?= htmlspecialchars($project['name']) ?>
            </h5>
            <a href="main.php?page=projects/list" class="btn btn-primary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4>Project Information</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 200px;">Project Name</th>
                            <td><?= htmlspecialchars($project['name']) ?></td>
                        </tr>
                        <tr>
                            <th>Location</th>
                            <td><?= htmlspecialchars($project['location']) ?></td>
                        </tr>
                        <tr>
                            <th>Project Size</th>
                            <td><?= htmlspecialchars($project['size']) ?></td>
                        </tr>
                        <tr>
                            <th>Start Date</th>
                            <td><?= date('F j, Y', strtotime($project['start_date'])) ?></td>
                        </tr>
                         <tr>
                            <th>Project Manager</th>
                            <td><?= htmlspecialchars($project['project_manager']) ?></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?= nl2br(htmlspecialchars($project['description'])) ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-4">
                    <h4>Customer Details</h4>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($project['customer_name'] ?? '') ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($project['company_name'] ?? '') ?></h6>
                            <p class="card-text">
                                <i class="fa fa-envelope me-2"></i>
                                <a href="mailto:<?= htmlspecialchars($project['customer_email'] ?? '') ?>"><?= htmlspecialchars($project['customer_email'] ?? '') ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 