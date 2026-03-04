<?php
// Include database connection and AuditLogger
include_once __DIR__ . '/../../includes/rapid_opms.php';
include_once __DIR__ . '/../audit_trail/audit.php';
$audit = new AuditLogger($conn);

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"] ?? '');
    $email    = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm  = $_POST["confirm_password"] ?? '';
    $position = $_POST["position"] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($position)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "A user with this email address already exists.";
        } else {
            // Hash password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, position) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $position);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id; // newly created user ID
                $_SESSION['success_message'] = "User created successfully!";

                // Friendly audit description
                $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
                $description = "User '{$name}' (Email: {$email}, ID: {$user_id}, Position: {$position}) was created by '{$admin_name}' on " . date('Y-m-d H:i:s');
                $audit->log('CREATE', 'User', $description);

                header("Location: main.php?page=user/list");
                exit();
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fa fa-user-plus me-2"></i>Create New User
            </h4>
        </div>

            <div class="card-body">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3" action="main.php?page=user/create">
                        
                        <div class="col-md-4">
                            <label for="name" class="form-label"><strong>Name: </strong><span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label for="email" class="form-label"><strong>Email Address: </strong><span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label for="password" class="form-label"><strong>Password: </strong><span class="text-danger">*</span></label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label for="confirm_password" class="form-label"><strong>Confirm Password: </strong><span class="text-danger">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label for="position" class="form-label"><strong>Position: </strong><span class="text-danger">*</span></label>
                            <select id="position" name="position" class="form-select" required>
                                <option value="" disabled selected>Select a position</option>
                                <option value="Admin">Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Project Management">Project Management</option>
                                <option value="Accountant">Accountant</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="main.php?page=user/list" class="btn btn-secondary">Cancel</a>
                            <button class="btn btn-success" type="submit">Create User</button>
                        </div>
                    </form>

            </div>
        </div>
    </div>
</div>
