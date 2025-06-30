<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No supplier ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();
$stmt->close();
if (!$supplier) {
    echo "<div class='alert alert-danger'>Supplier not found.</div>";
    exit;
}
?>
<div class="container py-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fa fa-truck me-2"></i>Supplier Details: <?= htmlspecialchars($supplier['name']) ?></h5>
      <a href="main.php?page=suppliers/list" class="btn btn-primary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <table class="table table-bordered">
        <tr><th>Name</th><td><?= htmlspecialchars($supplier['name']) ?></td></tr>
        <tr><th>Contact Person</th><td><?= htmlspecialchars($supplier['contact_person']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($supplier['email']) ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($supplier['phone']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($supplier['address']) ?></td></tr>
        <tr><th>Date Added</th><td><?= date('M d, Y', strtotime($supplier['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>
</div> 