<?php
// Include database connection
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';

$audit = new AuditLogger($conn);

$error = '';
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header("Location: main.php?page=user/list");
    exit();
}

// Fetch the existing user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

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
        // Backend check: update only if there are changes
        $hasChanges = ($name !== $user['name'] || $email !== $user['email'] || $position !== $user['position'] || !empty($password));

        if ($hasChanges) {
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET name=?, email=?, position=?, password=? WHERE id=?");
                $update_stmt->bind_param("ssssi", $name, $email, $position, $hashed, $user_id);
            } else {
                $update_stmt = $conn->prepare("UPDATE users SET name=?, email=?, position=? WHERE id=?");
                $update_stmt->bind_param("sssi", $name, $email, $position, $user_id);
            }

            if ($update_stmt->execute()) {
                $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';

                $changes = [];

                if ($name !== $user['name']) {
                    $changes[] = "Name changed from '{$user['name']}' to '{$name}'";
                }

                if ($email !== $user['email']) {
                    $changes[] = "Email changed from '{$user['email']}' to '{$email}'";
                }

                if ($position !== $user['position']) {
                    $changes[] = "Position changed from '{$user['position']}' to '{$position}'";
                }

                if (!empty($password)) {
                    $changes[] = "Password was updated";
                }

                $change_text = $changes ? implode("; ", $changes) : "User details updated.";

                $log_description = "User '{$name}' (ID: {$user_id}) was updated by {$admin_name}. Changes: {$change_text}";

                $audit->log('UPDATE', 'User', $log_description);

                $_SESSION['success_message'] = "User updated successfully.";
                header("Location: main.php?page=user/list");
                exit();
            } else {
                $error = "Database error: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $_SESSION['info_message'] = "No changes detected, update skipped.";
            header("Location: main.php?page=user/list");
            exit();
        }
    }
}
?>

<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-2">
                <i class="fa fa-edit me-2"></i>Edit User
            </h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="row g-3" action="main.php?page=user/edit&id=<?= $user_id ?>">
                <div class="col-md-4">
                    <label><strong>Name: </strong><span class="text-danger">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label><strong>Email Address: </strong><span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label><strong>Position: </strong><span class="text-danger">*</span></label>
                    <select id="position" name="position" class="form-select" required>
                        <option value="" disabled>Select a position</option>
                        <option value="Admin" <?= $user['position'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="Manager" <?= $user['position'] === 'Manager' ? 'selected' : '' ?>>Manager</option>
                        <option value="Project Management" <?= $user['position'] === 'Project Management' ? 'selected' : '' ?>>Project Management</option>
                        <option value="Accountant" <?= $user['position'] === 'Accountant' ? 'selected' : '' ?>>Accountant</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label><strong>New Password</strong> (leave blank to keep current password):</label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="new-password">
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                    <a href="main.php?page=user/list" class="btn btn-secondary">Cancel</a>
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
    const fieldsToWatch = ['name', 'email', 'position', 'password'];

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

            if (id === 'password' && val !== '') {
                hasChanges = true;
            } else if (id !== 'password' && val !== originalValues[id]) {
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