<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../rapid_opms.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ================= VALIDATE ID ================= */
$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: main.php?page=pricelist/list");
    exit;
}

/* ================= FETCH DATA ================= */
$stmt = $conn->prepare("
    SELECT year, contractor, straight_finish, rough_finish
    FROM price_list
    WHERE id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row) {
    echo "<div class='alert alert-danger'>Contractor not found.</div>";
    exit;
}

$error = '';

/* ================= UPDATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $year = $_POST['year'] ?? '';
    $contractor = trim($_POST['contractor'] ?? '');
    $straight_finish = $_POST['straight_finish'] ?? '';
    $rough_finish = $_POST['rough_finish'] ?? '';

    if (
        $year === '' || !is_numeric($year) ||
        $contractor === '' ||
        !is_numeric($straight_finish) ||
        !is_numeric($rough_finish)
    ) {
        $error = "Please fill all required fields correctly.";
    } else {

        $stmt = $conn->prepare("
            UPDATE price_list
            SET year = ?, contractor = ?, straight_finish = ?, rough_finish = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isddi", $year, $contractor, $straight_finish, $rough_finish, $id);

        if ($stmt->execute()) {
            $_SESSION['success_pricelist'] = "Contractor updated successfully.";
            header("Location: main.php?page=pricelist/list");
            exit;
        } else {
            $error = "Update failed: " . $conn->error;
        }
    }
}
?>

<!-- ================= UI ================= -->
<div class="container py-4">
    <div class="card">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-2">
                <i class="fa fa-edit me-2"></i>Edit Contractor Price
            </h4>
        </div>

        <div class="card-body">
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form id="editForm" method="POST" class="row g-3">

                <!-- Year -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Year: <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        name="year"
                        id="year"
                        class="form-control"
                        value="<?= htmlspecialchars($row['year']) ?>"
                        required
                    >
                </div>

                <!-- Contractor Name -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        Contractor Name: <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="contractor"
                        id="contractor"
                        class="form-control"
                        value="<?= htmlspecialchars($row['contractor']) ?>"
                        required
                    >
                </div>

                <!-- Straight Finish -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Straight Finish Price: <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        name="straight_finish"
                        id="straight_finish"
                        class="form-control"
                        value="<?= $row['straight_finish'] ?>"
                        required
                    >
                </div>

                <!-- Rough Finish -->
                <div class="col-md-2">
                    <label class="form-label fw-bold">
                        Rough Finish Price: <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        name="rough_finish"
                        id="rough_finish"
                        class="form-control"
                        value="<?= $row['rough_finish'] ?>"
                        required
                    >
                </div>

                <!-- Buttons -->
                <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                    <a href="main.php?page=pricelist/list" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="btn btn-success"
                        id="updateBtn"
                        disabled
                    >
                        Update
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<!-- ================= AUTO ENABLE/DISABLE SCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', () => {

    const original = {
        year: document.getElementById('year').value,
        contractor: document.getElementById('contractor').value,
        straight_finish: document.getElementById('straight_finish').value,
        rough_finish: document.getElementById('rough_finish').value
    };

    const inputs = document.querySelectorAll('#editForm input');
    const updateBtn = document.getElementById('updateBtn');

    function checkChanges() {
        const changed =
            document.getElementById('year').value !== original.year ||
            document.getElementById('contractor').value !== original.contractor ||
            document.getElementById('straight_finish').value !== original.straight_finish ||
            document.getElementById('rough_finish').value !== original.rough_finish;

        updateBtn.disabled = !changed;
    }

    inputs.forEach(input => {
        input.addEventListener('input', checkChanges);
    });
});
</script>