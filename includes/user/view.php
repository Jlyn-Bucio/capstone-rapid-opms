<?php
// Include database connection
include_once __DIR__ . '/../../includes/rapid_opms.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    // Redirect if no ID is provided
    header("Location: main.php?page=user/list");
    exit();
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Handle user not found
    die("User not found.");
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>View User Details</h5>
                    <a href="main.php?page=user/list" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>User ID:</strong></p>
                            <p><?= htmlspecialchars($user['id']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Date Created:</strong></p>
                            <p><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($user['created_at']))) ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong></p>
                            <p><?= htmlspecialchars($user['name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email Address:</strong></p>
                            <p><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>Position:</strong></p>
                            <p><?= htmlspecialchars($user['position']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="main.php?page=user/edit&id=<?= $user['id'] ?>" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
