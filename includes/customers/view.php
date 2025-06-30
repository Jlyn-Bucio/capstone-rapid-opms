<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No customer ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();
if (!$customer) {
    echo "<div class='alert alert-danger'>Customer not found.</div>";
    exit;
}
?>
<div class="container py-4">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fa fa-user me-2"></i>Customer Details: <?= htmlspecialchars($customer['name']) ?></h5>
      <a href="main.php?page=customers/list" class="btn btn-primary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <table class="table table-bordered">
        <tr><th>Name</th><td><?= htmlspecialchars($customer['name']) ?></td></tr>
        <tr><th>Company</th><td><?= htmlspecialchars($customer['company_name']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($customer['email']) ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($customer['phone']) ?></td></tr>
        <tr><th>Address</th><td><?= nl2br(htmlspecialchars($customer['address'])) ?></td></tr>
        <tr><th>Date Added</th><td><?= date('M d, Y', strtotime($customer['created_at'])) ?></td></tr>
      </table>
    </div>
  </div>
</div> 