<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM customers WHERE id = $delete_id");
    header("Location: list.php");
    exit;
}

// Fetch all customers
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");


?>

<div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-12">

        <div class="px-3 py-2 rounded mb-3" style="background-color: #d1d1d1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-people me-2"></i>Customer List</h6>
            <a href="main.php?page=customers/create" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Customer</a>
          </div>
        

        <div class="card-body pt-0"><br>
          <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle table-bordered">
              <thead class="table-dark text-light small">
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Company</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Address</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php if ($result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['id']) ?></td>
                      <td><?= htmlspecialchars($row['name']) ?></td>
                      <td><?= htmlspecialchars($row['company_name']) ?></td>
                      <td><?= htmlspecialchars($row['email']) ?></td>
                      <td><?= htmlspecialchars($row['phone']) ?></td>
                      <td class="text-start"><?= nl2br(htmlspecialchars($row['address'])) ?></td>
                      <td>
                        <a href="main.php?page=view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="main.php?page=edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1" title="Edit">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="main.php?page=delete&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this customer?')">
                          <i class="bi bi-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="7" class="text-center">No customers found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
</div>
      </div>
    </div>
  </div>
</div>

