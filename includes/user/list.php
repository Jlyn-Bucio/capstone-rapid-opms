<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection file
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Fetch users from the database, ordered by ID
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");

// Check if the query was successful
if (!$users) {
    die("Query failed: " . $conn->error);
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-12">

      <?php if (!empty($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['success_message']; ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['info_message'])): ?>
    <div class="alert alert-info">
        <?= $_SESSION['info_message']; ?>
    </div>
    <?php unset($_SESSION['info_message']); ?>
<?php endif; ?>


        <div class="mb-4 px-4 py-3 rounded" style="background-color: #d8d8d882; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fa fa-user-cog me-2"></i>Users List</h4>
           
            <a href="main.php?page=user/create" class="btn btn-primary">
              <i class="fa fa-plus me-2"></i>Add User</a>
            </a>
          </div>

          <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle table-bordered">
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
<?php $counter = $users->num_rows; ?>
<?php while ($row = $users->fetch_assoc()): ?>
  <tr data-href="main.php?page=user/view&id=<?= $row['id'] ?>" style="cursor:pointer;">
    <td><?= $counter-- ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['position']) ?></td>
    <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
    <td>
      <a href="main.php?page=user/view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1">
        <i class="bi bi-eye"></i>
      </a>
      <a href="main.php?page=user/edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1">
        <i class="bi bi-pencil-square"></i>
      </a>
      <a href="main.php?page=user/delete&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger"
         onclick="return confirm('Are you sure you want to delete this user?')">
        <i class="bi bi-trash"></i>
      </a>
    </td>
  </tr>
<?php endwhile; ?>
</tbody>

<script>
// Click anywhere on row to view user, except buttons
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tbody tr[data-href]').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if(!e.target.closest('a, button, i')) {
                window.location.href = row.getAttribute('data-href');
            }
        });
    });
});
</script>
