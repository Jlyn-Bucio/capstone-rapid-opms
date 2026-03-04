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

    <div class=" d-flex justify-content-between align-items-center p-3 border-bottom">
      <h4 class="mb-0"><i class="fa fa-eye me-2"></i>View Supplier Details</h4>
      <a href="main.php?page=suppliers/list" class="btn btn-primary">
        <i class="fa fa-arrow-left me-2"></i>Back to List
      </a>
    </div>

      <table class="table table-sm table-bordered mb-0">
        <tr><th style="width: 250px;" class="ps-3">Name</th><td class="ps-3"><?= htmlspecialchars($supplier['name']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Contact Person</th><td class="ps-3"><?= htmlspecialchars($supplier['contact_person']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Email</th><td class="ps-3"><?= htmlspecialchars($supplier['email']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Phone</th><td class="ps-3"><?= htmlspecialchars($supplier['phone']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Address</th><td class="ps-3"><?= htmlspecialchars($supplier['address']) ?></td></tr>
        <tr><th style="width: 250px;" class="ps-3">Date Added</th><td class="ps-3"><?= date('M d, Y', strtotime($supplier['created_at'])) ?></td></tr>
      </table>
  </div>
</div> 