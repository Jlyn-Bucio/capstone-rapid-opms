<?php
include_once __DIR__ . '/../../includes/rapid_opms.php';
if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>No customer ID provided.</div>";
    exit;
}
$id = (int)$_GET['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $company_name = $_POST['company_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $stmt = $conn->prepare("UPDATE customers SET name=?, company_name=?, email=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("sssssi", $name, $company_name, $email, $phone, $address, $id);
    if ($stmt->execute()) {
        header("Location: ../../main.php?page=customers/list&success=1");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: {$stmt->error}</div>";
    }
    $stmt->close();
}
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
      <h5 class="mb-0"><i class="fa fa-edit me-2"></i>Edit Customer: <?= htmlspecialchars($customer['name']) ?></h5>
      <a href="main.php?page=customers/list" class="btn btn-secondary btn-sm">
        <i class="fa fa-arrow-left me-1"></i> Back to List
      </a>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($customer['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label for="company_name" class="form-label">Company</label>
          <input type="text" name="company_name" id="company_name" class="form-control" value="<?= htmlspecialchars($customer['company_name']) ?>">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>">
        </div>
        <div class="mb-3">
          <label for="phone" class="form-label">Phone</label>
          <input type="text" name="phone" id="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>">
        </div>
        <div class="mb-3">
          <label for="address" class="form-label">Address</label>
          <textarea name="address" id="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </form>
    </div>
  </div>
</div> 