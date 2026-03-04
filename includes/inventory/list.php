<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../../includes/rapid_opms.php';

// ================= GET SELECTED YEAR =================
$selected_year = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : null;

// ================= FETCH AVAILABLE YEARS (SOFT DELETE SAFE) =================
$years_query = "
    SELECT DISTINCT YEAR(created_at) AS year
    FROM inventory
    WHERE deleted_at IS NULL
    ORDER BY year DESC
";
$years_result = $conn->query($years_query);

$available_years = [];
while ($year_row = $years_result->fetch_assoc()) {
    $available_years[] = $year_row['year'];
}

// ================= FETCH INVENTORY PRODUCTS (SOFT DELETE SAFE) =================
$sql = "
    SELECT *
    FROM inventory
    WHERE deleted_at IS NULL
";

if ($selected_year !== null) {
    $sql .= " AND YEAR(created_at) = " . $selected_year;
}

$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql) or die($conn->error);
?>

<div class="container py-4">

    <?php if (!empty($_SESSION['success_inventory'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success_inventory']; unset($_SESSION['success_inventory']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="mb-4 px-4 py-3 rounded"
         style="background-color:#d8d8d882; box-shadow:0 2px 6px rgba(0,0,0,.1);">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">
                <i class="fas fa-warehouse me-2"></i>Inventory Management
            </h4>

            <div class="d-flex align-items-center gap-2">
                <select id="yearFilter"
                        class="form-select form-select-sm"
                        style="width:auto;"
                        onchange="filterByYear()">
                    <option value="">All Years</option>
                    <?php foreach ($available_years as $year): ?>
                        <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <a href="main.php?page=inventory/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Add Product
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered text-center align-middle">
                <thead class="table-dark small">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Buying Price</th>
                        <th>Selling Price</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody class="small">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $i = $result->num_rows; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="clickable-row"
                            data-href="main.php?page=inventory/view_product&id=<?= $row['id'] ?>"
                            style="cursor:pointer;">

                            <td><?= $i-- ?></td>
                            <td><?= htmlspecialchars($row['product_title']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= $row['quantity'] ?> in stock</td>
                            <td>₱<?= number_format($row['buying_price'], 2) ?></td>
                            <td>₱<?= number_format($row['selling_price'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>

                            <td>
                                <a href="main.php?page=inventory/view_product&id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-info me-1"
                                   onclick="event.stopPropagation();">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <a href="main.php?page=inventory/edit_product&id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-warning me-1"
                                   onclick="event.stopPropagation();">
                                    <i class="bi bi-pencil-square"></i>
                                </a>

                                <a href="includes/inventory/delete_product.php?id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-danger"
                                   onclick="event.stopPropagation(); return confirm('Delete this product?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-muted text-center">
                            Empty List
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterByYear() {
    const year = document.getElementById('yearFilter').value;
    const url = new URL(window.location.href);

    if (year) {
        url.searchParams.set('year', year);
    } else {
        url.searchParams.delete('year');
    }

    window.location.href = url.toString();
}

// Row click → view (ignore buttons)
document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', e => {
        if (!e.target.closest('a, button, i')) {
            window.location.href = row.dataset.href;
        }
    });
});
</script>