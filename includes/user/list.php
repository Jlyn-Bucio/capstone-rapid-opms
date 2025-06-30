<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Fetch users from the database, ordered by ID
$users = $conn->query("SELECT * FROM users ORDER BY id ASC");

// Check if the query was successful
if (!$users) {
    die("Query failed: " . $conn->error);
}
?>

<div class="container py-3">
    <div class="row justify-content-center">
      <div class="col-md-12">

        <div class="mb-3 px-3 py-2 rounded" style="background-color: #d1d1d1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fa fa-users me-2"></i>User Management</h6>
            <a href="main.php?page=user/create" class="btn btn-sm btn-primary">
              <i class="fa fa-plus me-1"></i> Add User
            </a>
          </div>
        </div>

        <div class="card-body pt-0"><br>
          <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle">
              <thead class="table-dark text-light small">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Position</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php while ($row = $users->fetch_assoc()): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['position']) ?></td>
                    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                    <td>
                      <a href="main.php?page=user/view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1" title="View">
                        <i class="bi bi-eye"></i>
                      </a>
                      <a href="main.php?page=user/edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1" title="Edit">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                      <a href="main.php?page=user/delete&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


