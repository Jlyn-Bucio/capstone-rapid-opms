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

<div class="container py-4">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                    <h4 class="mb-0"><i class="fas fa-eye me-2"></i>View User Details</h4>
                    <a href="main.php?page=user/list" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>
                
                <table class="table table-sm table-bordered mb-0">
                    <tr>
                        <th style="width: 250px;" class="ps-3">User ID</th><td class="ps-3"><?= htmlspecialchars($user['id']) ?></td>
                    </tr>

                    <tr>
                        <th style="width: 250px;" class="ps-3">Date Created</th><td class="ps-3"><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($user['created_at']))) ?></td>
                    </tr>

                    <tr>
                        <th style="width: 250px;" class="ps-3">Name</th><td class="ps-3"><?= htmlspecialchars($user['name']) ?></td>
                    </tr>

                    <tr>
                        <th style="width: 250px;" class="ps-3">Email Address</th><td class="ps-3"><?= htmlspecialchars($user['email']) ?></td>
                    </tr>

                    <tr>
                        <th style="width: 250px;" class="ps-3">Position</th><td class="ps-3"><?= htmlspecialchars($user['position']) ?></td>
                    </tr>
                </table>
    </div>
</div>
