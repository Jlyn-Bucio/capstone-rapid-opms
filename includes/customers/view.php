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
    echo '<div class="container py-4"><div class="alert alert-warning">Customer not found.</div></div>';
    exit;
}
?>

<div class="container py-4">
  <div class="card">
    <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
      <h4 class="mb-0">
        <i class="fa fa-eye me-2"></i>View Customer Details
      </h4>

      <a href="main.php?page=customers/list" class="btn btn-primary">
        <i class="fa fa-arrow-left me-2"></i>Back to List
      </a>
    </div>

      <table class="table table-sm table-bordered mb-0">
        <tr><th style="width: 250px;" class="ps-3">Name</th><td class="ps-3"><?= htmlspecialchars($customer['name']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Company</th><td class="ps-3"><?= htmlspecialchars($customer['company_name']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Email</th><td class="ps-3"><?= htmlspecialchars($customer['email']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Phone</th><td class="ps-3"><?= htmlspecialchars($customer['phone']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Address</th><td class="ps-3"><?= nl2br(htmlspecialchars($customer['address'])) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Date Added</th><td class="ps-3"><?= date('M d, Y', strtotime($customer['created_at'])) ?></td></tr>
      </table>
  </div>
</div> 