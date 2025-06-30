<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../rapid_opms.php';

// Fetch all suppliers
$result = $conn->query("SELECT * FROM suppliers ORDER BY id ASC");
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Optional: show success message from URL query string
$msg = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>

<div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-12">

        <div class="px-3 py-2 rounded mb-3" style="background-color: #d1d1d1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fa fa-truck me-2"></i>Supplier List</h6>
            <a href="create.php" class="btn btn-sm btn-primary"><i class="fa fa-plus me-1"></i> Add Supplier</a>
          </div>
        

        <?php if ($msg): ?>
          <div class="alert alert-success py-2 px-3 small"><?= $msg ?></div>
        <?php endif; ?>

        <div class="card-body pt-0"><br>
          <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle table-bordered">
              <thead class="table-dark text-light small">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Contact Person</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Address</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php while ($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['contact_person']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></td>
                    <td>
                      <a href="main.php?page=view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1" title="View">
                        <i class="bi bi-eye"></i>
                      </a>
                      <a href="main.php?page=edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1" title="Edit">
                        <i class="bi bi-pencil-square"></i>
                      </a>
                      <a href="main.php?page=delete&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this supplier?')">
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
</div>
