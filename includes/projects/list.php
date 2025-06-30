<?php
$conn = new mysqli("localhost", "root", "", "rapid_opms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch projects with customer info
$query = "
  SELECT p.*, c.name AS customer_name, c.company_name 
  FROM projects p 
  LEFT JOIN customers c ON p.customer_id = c.id 
  ORDER BY p.id DESC";
$result = $conn->query($query);
?>

<div class="container py-3">
    <div class="mb-3 px-3 py-2 rounded" style="background-color: #d1d1d1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
      <div class="d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fa fa-list me-2"></i>Projects List</h6><br>
        <a href="main.php?page=projects/create" class="btn btn-sm btn-primary">
          <i class="fa fa-plus me-1"></i> New
        </a>
      </div>
   

    <div class="card-body pt-0"><br>
      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success py-2 px-3 mb-2"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover text-center align-middle">
          <thead class="table-dark text-light small">
            <tr>
              <th>ID</th>
              <th>Project</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Location</th>
              <th>Contractor</th>
              <th>Size</th>
              <th>Start</th>
              <th>Manager</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="small">
            <?php if ($result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= $row['id'] ?></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td>
                    <?= htmlspecialchars($row['customer_name']) ?>
                    <?= $row['company_name'] ? ' (' . htmlspecialchars($row['company_name']) . ')' : '' ?>
                  </td>
                  <td><?= htmlspecialchars($row['date']) ?></td>
                  <td><?= htmlspecialchars($row['location']) ?></td>
                  <td><?= htmlspecialchars($row['contractor']) ?></td>
                  <td><?= htmlspecialchars($row['size']) ?></td>
                  <td><?= htmlspecialchars($row['start_date']) ?></td>
                  <td><?= htmlspecialchars($row['project_manager']) ?></td>
                  <td>
                    <a href="main.php?page=projects/view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="main.php?page=projects/edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                    <a href="main.php?page=projects/delete&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger" title="Delete" onclick="return confirm('Are you sure?')">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="10" class="text-center text-muted">No projects found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
  </div>


