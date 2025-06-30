<?php
// Include database connection
include_once __DIR__ . '/../../includes/rapid_opms.php';

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm  = $_POST["confirm_password"];
    $position = $_POST["position"];

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
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, position) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed, $position);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "User created successfully!";
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
    <div class="row justify-content-center">

        <div class="card-body">
          <div class="mb-4 px-4 py-3 rounded" style="background-color: #b0b0b0; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);">
            <h5><i class="fa fa-user-plus fa-lg me-3"></i>Create New User</h5>
        </div>
        <div class="card-body">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="main.php?page=user/create">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
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
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-plus me-1"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
