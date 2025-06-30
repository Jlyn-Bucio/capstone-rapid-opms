<?php
// Include database connection
include_once __DIR__ . '/../../includes/rapid_opms.php';

$error = '';
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    // Redirect or show an error if no ID is provided
    header("Location: main.php?page=user/list");
    exit();
}

// Fetch the existing user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $position = $_POST["position"];
    $password = $_POST["password"] ?? '';

    if (empty($name) || empty($email) || empty($position)) {
        $error = "All fields are required.";
    } else {
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, position = ?, password = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $name, $email, $position, $hashed, $user_id);
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, position = ? WHERE id = ?");
            $update_stmt->bind_param("sssi", $name, $email, $position, $user_id);
        }

        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "User updated successfully!";
            header("Location: main.php?page=user/list");
            exit();
        } else {
            $error = "Database error: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}
$stmt->close();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit User</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="main.php?page=user/edit&id=<?= $user_id ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                            <select id="position" name="position" class="form-select" required>
                                <option value="" disabled>Select a position</option>
                                <option value="Admin" <?= $user['position'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="Manager" <?= $user['position'] === 'Manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="Project Management" <?= $user['position'] === 'Project Management' ? 'selected' : '' ?>>Project Management</option>
                                <option value="Accountant" <?= $user['position'] === 'Accountant' ? 'selected' : '' ?>>Accountant</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="main.php?page=user/list" class="btn btn-secondary">Cancel</a>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-save me-1"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
