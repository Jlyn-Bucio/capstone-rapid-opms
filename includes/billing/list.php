<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';

// Fetch billing with JOIN to projects and customers
$sql = "
    SELECT 
        b.id, 
        b.invoice_number, 
        b.amount, 
        b.billing_date,
        p.name AS project_name,
        c.name AS customer_name
    FROM billing b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN customers c ON b.customer_id = c.id
    ORDER BY b.billing_date DESC
";


$result = $conn->query($sql);
?>

<div class="container py-3">
    <div class="row justify-content-center">
      <div class="col-md-12">

        <div class="mb-3 px-3 py-2 rounded" style="background-color: #d1d1d1; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fa fa-file-invoice me-2"></i>Billing Records</h6>
            <a href="main.php?page=billing/create" class="btn btn-sm btn-primary">
              <i class="fa fa-plus me-1"></i> Add Billing
            </a>
          </div>
        

        <div class="card-body pt-0"><br>
          <div class="table-responsive">
            <table class="table table-sm table-hover text-center align-middle">
              <thead class="table-dark text-light small">
                <tr>
                  <th>ID</th>
                  <th>Invoice #</th>
                  <th>Project</th>
                  <th>Customer</th>
                  <th>Amount</th>
                  <th>Billing Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody class="small">
                <?php if ($result && $result->num_rows > 0): ?>
                  <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['id']) ?></td>
                      <td><?= htmlspecialchars($row['invoice_number'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($row['project_name'] ?? 'N/A') ?></td>
                      <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
                      <td>₱<?= number_format($row['amount'], 2) ?></td>
                      <td><?= htmlspecialchars($row['billing_date']) ?></td>
                      <td>
                        <a href="main.php?page=view&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-info me-1" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                        <a href="main.php?page=edit&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-warning me-1" title="Edit">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="main.php?page=delete&id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this billing record?')">
                          <i class="bi bi-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="7" class="text-center">No billing records found.</td>
                  </tr>
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
