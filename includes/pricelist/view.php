<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../rapid_opms.php';

/* =========================
   VALIDATE ID
========================= */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: main.php?page=pricelist/list');
    exit;
}

$id = (int) $_GET['id'];

/* =========================
   FETCH CONTRACTOR PRICE
========================= */
$stmt = $conn->prepare("
    SELECT 
        year,
        contractor,
        straight_finish,
        rough_finish
    FROM price_list
    WHERE id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$price = $result->fetch_assoc();
$stmt->close();

if (!$price) {
    echo '
    <div class="container py-4">
        <div class="alert alert-warning">
            Contractor price not found.
        </div>
        <a href="main.php?page=pricelist/list" class="btn btn-secondary">
            Back to List
        </a>
    </div>';
    exit;
}
?>

<div class="container py-4">
    <div class="card">

        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <h4 class="mb-0">
                <i class="fas fa-eye me-2"></i>View Contractor Details
            </h4>
            <a href="main.php?page=pricelist/list" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>

        <table class="table table-sm table-bordered mb-0">
            <tr>
                <th style="width:250px" class="ps-3">Year</th>
                <td class="ps-3">
                    <?= htmlspecialchars($price['year']) ?>
                </td>
            </tr>
            <tr>
                <th class="ps-3">Contractor</th>
                <td class="ps-3">
                    <?= htmlspecialchars($price['contractor']) ?>
                </td>
            </tr>
            <tr>
                <th class="ps-3">Straight to Finish Price</th>
                <td class="ps-3">
                    ₱<?= number_format((float)$price['straight_finish'], 2) ?>
                </td>
            </tr>
            <tr>
                <th class="ps-3">Rough Finish Price</th>
                <td class="ps-3">
                    ₱<?= number_format((float)$price['rough_finish'], 2) ?>
                </td>
            </tr>
        </table>

    </div>
</div>