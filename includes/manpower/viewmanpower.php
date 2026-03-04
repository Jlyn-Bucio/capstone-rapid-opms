<?php
include_once __DIR__ . '/../rapid_opms.php';

if (!isset($_GET['id'])) {
    header('Location: main.php?page=manpower/listmanpower');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $conn->prepare("SELECT * FROM manpower WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$man = $res->fetch_assoc();
$stmt->close();

if (!$man) {
    echo '<div class="container py-4"><div class="alert alert-warning">Manpower item not found.</div></div>';
    exit;
}
?>

<div class="container py-4">
    <div class="card">
      <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <h4 class="mb-0">
              <i class="fa fa-eye me-2"></i>View Manpower Details
            </h4>

            <a href="main.php?page=manpower/listmanpower" class="btn btn-primary">
              <i class="fa fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <table class="table table-sm table-bordered mb-0">
          <tr>
            <th style="width: 250px;" class="ps-3">Name</th><td class="ps-3"><?= htmlspecialchars($man['name']) ?></td></tr>
          <tr><th style="width: 250px;" class="ps-3">Position</th><td class="ps-3"><?= htmlspecialchars($man['position']) ?></td></tr>
          <tr><th style="width: 250px;" class="ps-3">Old Rate (₱)</th><td class="ps-3">₱<?= number_format($man['old_rate'],2) ?></td></tr>
          <tr><th style="width: 250px;" class="ps-3">Meal Allowance (₱)</th><td class="ps-3">₱<?= number_format($man['meal_allowance'],2) ?></td></tr>
          <tr><th style="width: 250px;" class="ps-3">New Rate (₱)</th><td class="ps-3">₱<?= number_format($man['new_rate'],2) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>