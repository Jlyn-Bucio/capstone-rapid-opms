<?php
include_once __DIR__ . '/../audit_trail/audit.php';

$conn = new mysqli("localhost", "root", "", "rapid_opms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);


$audit = new AuditLogger($conn);

// Helpers
function inputValue($value)
{
    if ($value === null) return '';
    if (is_string($value) && trim($value) === '') return '';
    if ($value === 0 || $value === '0' || $value === '0.00') return '';
    return htmlspecialchars((string)$value);
}

// Format helper: return blank when empty/zero, otherwise comma with 2 decimals
function displayAmount($value)
{
    $num = str_replace(',', '', (string)$value);
    if ($num === '' || (float)$num == 0.0) return '';
    return number_format((float)$num, 2, '.', ',');
}

// Fetch ONLY active (not deleted) customers for dropdown
$customers = [];

$customerStmt = $conn->prepare("
    SELECT id, name, company_name
    FROM customers
    WHERE deleted_at IS NULL
    ORDER BY name ASC
");

$customerStmt->execute();
$customerResult = $customerStmt->get_result();

while ($row = $customerResult->fetch_assoc()) {
    $customers[] = $row;
}

$customerStmt->close();


// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $date = trim($_POST['date']);
    $location = trim($_POST['location']);
    // $contractor = trim($_POST['contractor']);
    $size = trim($_POST['size']);
    $start_date = trim($_POST['start_date']);
    $customer_id = (int)$_POST['customer_id'];
    $project_manager = trim($_POST['project_manager'] ?? '');
    $description = trim($_POST['description']);

    // Numeric helpers for charge fields (strip commas)
    $num = function($key) {
        $raw = $_POST[$key] ?? '';
        $clean = str_replace(',', '', $raw);
        return $clean === '' ? 0 : (float)$clean;
    };

    $straight_finish_area = $num('straight_finish_area');
    $straight_finish_unit_cost = $num('straight_finish_unit_cost');
    $straight_finish_amount = $num('straight_finish_amount');

    $rough_finish_area = $num('rough_finish_area');
    $rough_finish_unit_cost = $num('rough_finish_unit_cost');
    $rough_finish_amount = $num('rough_finish_amount');

    $suspended_volume_area = $num('suspended_volume_area');
    $suspended_volume_unit_cost = $num('suspended_volume_unit_cost');
    $suspended_volume_amount = $num('suspended_volume_amount');

    $mobilization_fee_area = $num('mobilization_fee_area');
    $mobilization_fee_unit_cost = $num('mobilization_fee_unit_cost');
    $mobilization_fee_amount = $num('mobilization_fee_amount');

    $idle_time_area = $num('idle_time_area');
    $idle_time_unit_cost = $num('idle_time_unit_cost');
    $idle_time_amount = $num('idle_time_amount');

    $cancellation_fee_area = $num('cancellation_fee_area');
    $cancellation_fee_unit_cost = $num('cancellation_fee_unit_cost');
    $cancellation_fee_amount = $num('cancellation_fee_amount');

    $total_amount = $num('total_amount');

    

    // Basic validation
    // Validate if customer still exists and not deleted
$checkCustomer = $conn->prepare("
    SELECT id FROM customers 
    WHERE id = ? AND deleted_at IS NULL
");
$checkCustomer->bind_param("i", $customer_id);
$checkCustomer->execute();
$checkResult = $checkCustomer->get_result();

if ($checkResult->num_rows === 0) {
    $errors['customer_id'] = "Selected customer is invalid or already deleted.";
}
$checkCustomer->close();

    if (empty($name)) $errors['name'] = "Project name is required.";
    if (empty($date)) $errors['date'] = "Date is required.";
    if (empty($location)) $errors['location'] = "Location is required.";
    if ($customer_id === 0) $errors['customer_id'] = "Please select a valid customer.";

    // If no errors, insert into DB
    if (empty($errors)) {
        // Start transaction to ensure both project and billing are created together
        $useTransaction = true;
        try {
            $conn->begin_transaction();
        } catch (Exception $e) {
            // Transactions not supported, continue without transaction
            $useTransaction = false;
        }
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO projects (
                    name, date, location, size, start_date, customer_id, project_manager, description,
                    straight_finish_area, straight_finish_unit_cost, straight_finish_amount,
                    rough_finish_area, rough_finish_unit_cost, rough_finish_amount,
                    suspended_volume_area, suspended_volume_unit_cost, suspended_volume_amount,
                    mobilization_fee_area, mobilization_fee_unit_cost, mobilization_fee_amount,
                    idle_time_area, idle_time_unit_cost, idle_time_amount,
                    cancellation_fee_area, cancellation_fee_unit_cost, cancellation_fee_amount,
                    total_amount
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    ?
                )
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param(
                "sssssis" . "s" . str_repeat("d", 19),
                $name,
                $date,
                $location,
                $size,
                $start_date,
                $customer_id,
                $project_manager,
                $description,

                $straight_finish_area,
                $straight_finish_unit_cost,
                $straight_finish_amount,

                $rough_finish_area,
                $rough_finish_unit_cost,
                $rough_finish_amount,

                $suspended_volume_area,
                $suspended_volume_unit_cost,
                $suspended_volume_amount,

                $mobilization_fee_area,
                $mobilization_fee_unit_cost,
                $mobilization_fee_amount,

                $idle_time_area,
                $idle_time_unit_cost,
                $idle_time_amount,

                $cancellation_fee_area,
                $cancellation_fee_unit_cost,
                $cancellation_fee_amount,

                $total_amount
            );

            if (!$stmt->execute()) {
                throw new Exception("Project insert failed: " . $stmt->error);
            }

            // ================== GET PROJECT ID ==================
            $project_id = $conn->insert_id;
            $stmt->close();
        
            // ================== AUTO-GENERATE INVOICE NUMBER ==================
            // Format: INV-2026001, INV-2026002, etc.
            $year = date('Y');
            $amount = $total_amount;
            $billing_date = $date;
            $notes = $description;

            $invResult = $conn->query("
                SELECT invoice_number
                FROM billing
                WHERE invoice_number LIKE 'INV-{$year}%'
                ORDER BY invoice_number DESC
                LIMIT 1
            ");

            $startNumber = 1;

            if ($invResult && $invResult->num_rows > 0) {
                $lastInv = $invResult->fetch_assoc()['invoice_number'];

                // SAFELY extract running number (after INV-YYYY)
                $prefix = 'INV-' . $year;
                $lastNumber = (int) str_replace($prefix, '', $lastInv);
                $startNumber = $lastNumber + 1;
            }

            $nextNumber = $startNumber;
            
            $retry = true;
            $maxRetries = 10; // Prevent infinite loop
            $attempt = 0;
            $billingSuccess = false;

            while ($retry && $attempt < $maxRetries) {
                $attempt++;
                
                $invoice_number = 'INV-' . $year . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                // Check if invoice number already exists
                $checkStmt = $conn->prepare("SELECT id FROM billing WHERE invoice_number = ?");
                $checkStmt->bind_param("s", $invoice_number);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                $checkStmt->close();
                
                if ($checkResult && $checkResult->num_rows > 0) {
                    // Invoice already exists, increment and try next number
                    $nextNumber++;
                    $retry = true;
                    continue;
                }

                $billingStmt = $conn->prepare("
                    INSERT INTO billing 
                    (invoice_number, project_id, customer_id, amount, billing_date, notes)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if (!$billingStmt) {
                    throw new Exception("Billing prepare failed: " . $conn->error);
                }

                $billingStmt->bind_param(
                    "siidss",
                    $invoice_number,
                    $project_id,
                    $customer_id,
                    $amount,
                    $billing_date,
                    $notes
                );

                if ($billingStmt->execute()) {
                    // SUCCESS
                    $billingSuccess = true;
                    $retry = false;
                    $billingStmt->close();
                } else {
                    $errorCode = $billingStmt->errno;
                    $errorMsg = $billingStmt->error;
                    $billingStmt->close();
                    
                    // Check if it's a duplicate entry error (1062 = Duplicate entry)
                    if ($errorCode == 1062 || strpos($errorMsg, 'Duplicate entry') !== false) {
                        // Duplicate invoice → increment and try next number
                        $nextNumber++;
                        $retry = true;
                    } else {
                        // Other database error
                        throw new Exception("Billing insert failed: " . $errorMsg);
                    }
                }
            }
            
            if (!$billingSuccess) {
                throw new Exception("Failed to generate unique invoice number after {$maxRetries} attempts. Last tried: {$invoice_number}, Started from: INV-{$year}" . str_pad($startNumber, 3, '0', STR_PAD_LEFT));
            }

            // Commit transaction if both inserts succeeded
            if ($useTransaction) {
                $conn->commit();
            }
            
            // Success - redirect (use ob_clean to clear any output)
            if (ob_get_level()) {
                ob_clean();
            }

            $admin_name = $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'System';
            $description = "Created Project: {$name} (ID: {$project_id}) by '{$admin_name}'";
            $audit->log('CREATE', 'Project', $description);

            $_SESSION['success_projects'] = "Project successfully registered!";
            header("Location: main.php?page=billing/list");
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on any error
            if ($useTransaction) {
                $conn->rollback();
            }
            $errors['db'] = $e->getMessage();
        }
    }
}

?>

        <div class="container py-4">
            <div class="card">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fa fa-clipboard-list me-2"></i>Create New Project</h4>
                </div>

                <div class="card-body">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="row g-3">
                        
                        <div class="col-md-6">
                            <label><strong>Project Name: </strong></label>
                            <label><span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <label><strong>Date: </strong></label>
                            <label><span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" required value="<?= htmlspecialchars($_POST['date'] ?? '') ?>">
                        </div>

                        <div class="col-md-2">
                            <label><strong>Start Date: </strong></label>
                            <label><span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" required value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label><strong>Project Size: </strong></label>
                            <input type="text" name="size" id="projectSize" class="form-control" required value="<?= inputValue($_POST['size'] ?? '') ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label><strong>Location: </strong></label>
                            <label><span class="text-danger">*</span></label>
                            <input type="text" name="location" class="form-control" required value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                        </div>

                        <!-- <div class="col-md-3">
                            <label><strong>Contractor: </strong></label>
                            <label><span class="text-danger">*</span></label>
                            <input type="text" name="contractor" class="form-control" required value="<?= htmlspecialchars($_POST['contractor'] ?? '') ?>">
                        </div> -->

                        <div class="col-md-6">
                            <label><strong>Contractor / Customer: </strong></label>
                            <label><span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select">
                                <option value="">-- Select Customer --</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?> <?= $c['company_name'] ? '(' . htmlspecialchars($c['company_name']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <br>
                        </div>
                        
                    <!-- Charge/s Section -->
                    <div class="mb-3"><strong>Charge/s:</strong></div>

                    <!-- Straight to Finish -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" value="Straight to Finish — m²" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="straight_finish_area" class="form-control area-field" placeholder="Area" value="<?= inputValue(displayAmount($_POST['straight_finish_area'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="straight_finish_unit_cost" class="form-control unitcost-field" placeholder="Unit Cost" value="<?= inputValue(displayAmount($_POST['straight_finish_unit_cost'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="straight_finish_amount" class="form-control amount-field" placeholder="Amount" readonly value="<?= inputValue(displayAmount($_POST['straight_finish_amount'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Rough Finish -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" value="Rough Finish — m²" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="rough_finish_area" class="form-control area-field" placeholder="Area" value="<?= inputValue(displayAmount($_POST['rough_finish_area'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="rough_finish_unit_cost" class="form-control unitcost-field" placeholder="Unit Cost" value="<?= inputValue(displayAmount($_POST['rough_finish_unit_cost'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="rough_finish_amount" class="form-control amount-field" placeholder="Amount" readonly value="<?= inputValue(displayAmount($_POST['rough_finish_amount'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Suspended Volume -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" value="Suspended Volume — m³" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="suspended_volume_area" class="form-control area-field" placeholder="Area" value="<?= inputValue(displayAmount($_POST['suspended_volume_area'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="suspended_volume_unit_cost" class="form-control unitcost-field" placeholder="Unit Cost" value="<?= inputValue(displayAmount($_POST['suspended_volume_unit_cost'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="suspended_volume_amount" class="form-control amount-field" placeholder="Amount" readonly value="<?= inputValue(displayAmount($_POST['suspended_volume_amount'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Mobilization Fee -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" value="Mobilization Fee — lot" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="mobilization_fee_area" class="form-control area-field" placeholder="Area" value="<?= inputValue(displayAmount($_POST['mobilization_fee_area'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="mobilization_fee_unit_cost" class="form-control unitcost-field" placeholder="Unit Cost" value="<?= inputValue(displayAmount($_POST['mobilization_fee_unit_cost'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="mobilization_fee_amount" class="form-control amount-field" placeholder="Amount" readonly value="<?= inputValue(displayAmount($_POST['mobilization_fee_amount'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Idle Time -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" value="Idle Time Charge — hrs" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="idle_time_area" class="form-control area-field" placeholder="--" value="<?= inputValue(displayAmount($_POST['idle_time_area'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="idle_time_unit_cost" class="form-control unitcost-field" placeholder="--" value="<?= inputValue(displayAmount($_POST['idle_time_unit_cost'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="idle_time_amount" class="form-control amount-field manual-amount-field" data-manual="true" placeholder="Amount" value="<?= inputValue(displayAmount($_POST['idle_time_amount'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Cancellation Fee -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-4">
                            <input type="text" class="form-control" value="Cancellation Fee — lot" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="cancellation_fee_area" class="form-control area-field" placeholder="--" value="<?= inputValue(displayAmount($_POST['cancellation_fee_area'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="cancellation_fee_unit_cost" class="form-control unitcost-field" placeholder="--" value="<?= inputValue(displayAmount($_POST['cancellation_fee_unit_cost'] ?? '')) ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="cancellation_fee_amount" class="form-control amount-field manual-amount-field" data-manual="true" placeholder="Amount" value="<?= inputValue(displayAmount($_POST['cancellation_fee_amount'] ?? '')) ?>">
                        </div>
                    </div>

                                <div class="row mb-2 align-items-center">
                    <!-- Label -->
                    <div class="col-md-9 text-end">
                        <span class="fw-bold">
                            Total Amount:
                        </span>
                    </div>

                    <!-- Amount textbox (same size as above amounts) -->
                    <div class="col-md-3">
                        <input type="text"
                            id="totalAmount"
                            name="total_amount"
                            class="form-control amount-field text-end fw-bold"
                            value="<?= inputValue(displayAmount($_POST['total_amount'] ?? '')) ?>"
                            readonly>
                    </div>
                </div>

                        <div class="col-12">
                            <label><strong>Remarks: </strong></label>
                            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <a href="main.php?page=projects/list" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Create Project</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
<script>
function calculateProjectSize() {
    // Get the three area fields: Straight to Finish, Rough Finish, Suspended Volume
    const straightArea = cleanNumber(document.querySelector('input[name="straight_finish_area"]')?.value || '');
    const roughArea = cleanNumber(document.querySelector('input[name="rough_finish_area"]')?.value || '');
    const suspendedArea = cleanNumber(document.querySelector('input[name="suspended_volume_area"]')?.value || '');
    
    // Calculate total
    const totalSize = straightArea + roughArea + suspendedArea;
    
    // Update Project Size field
    const projectSizeInput = document.getElementById('projectSize');
    if (projectSizeInput) {
        if (totalSize > 0) {
            projectSizeInput.value = formatWithGrouping(totalSize);
        } else {
            projectSizeInput.value = '';
        }
    }
}

function calculateAmount() {
    let total = 0;

    // Loop through each Amount field
    document.querySelectorAll('.amount-field').forEach(amountInput => {
        const isManual = amountInput.dataset.manual === 'true';
        let amount = 0;

        if (isManual) {
            // Keep user-entered manual amount as-is; only use it for totaling
            amount = cleanNumber(amountInput.value);
        } else {
            const row = amountInput.closest('.row');
            const rawArea = row.querySelector('.area-field')?.value || '';
            const rawUnit = row.querySelector('.unitcost-field')?.value || '';

            // If both fields are empty, leave amount blank and skip formatting
            if (rawArea.replace(/,/g, '').trim() === '' && rawUnit.replace(/,/g, '').trim() === '') {
                amountInput.value = '';
                return;
            }

            const area = cleanNumber(rawArea);
            const unitCost = cleanNumber(rawUnit);
            amount = area * unitCost;
            // Format with commas and 2 decimal places
            amountInput.value = amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        total += amount;
    });

    // Update Total Amount with commas
    const totalAmountInput = document.getElementById('totalAmount');
    if(totalAmountInput){
        totalAmountInput.value = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    
    // Also calculate project size
    calculateProjectSize();
}

function cleanNumber(value) {
    // Remove grouping commas so parsing still works after formatting
    if (value === null || value === undefined) return 0;
    const raw = value.toString().replace(/,/g, '').trim();
    if (raw === '') return 0;
    const num = parseFloat(raw);
    return Number.isNaN(num) ? 0 : num;
}

function formatWithGrouping(value) {
    const str = value.toString();
    const [, decimals = ''] = str.split('.');
    // Preserve the number of decimals the user typed
    const fractionDigits = decimals.length > 0 ? decimals.length : 0;
    return value.toLocaleString('en-US', {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits
    });
}

// Recalculate and format whenever Area or Unit Cost changes
document.querySelectorAll('.area-field, .unitcost-field').forEach(input => {
    input.addEventListener('input', () => {
        const raw = input.value.replace(/,/g, '').trim();
        if (raw === '') {
            input.value = '';
            calculateAmount();
            // Only update project size if it's one of the three specific area fields
            if (input.name === 'straight_finish_area' || input.name === 'rough_finish_area' || input.name === 'suspended_volume_area') {
                calculateProjectSize();
            }
            return;
        }

        const numericVal = parseFloat(raw);
        input.value = Number.isNaN(numericVal) ? '' : formatWithGrouping(numericVal);
        calculateAmount();
        // Only update project size if it's one of the three specific area fields
        if (input.name === 'straight_finish_area' || input.name === 'rough_finish_area' || input.name === 'suspended_volume_area') {
            calculateProjectSize();
        }
    });
});

// Manually entered amounts (idle time, cancellation) still included in totals
document.querySelectorAll('.manual-amount-field').forEach(input => {
    input.addEventListener('input', () => {
        const raw = input.value.replace(/,/g, '').trim();
        // Avoid formatting while typing to prevent cursor jumps and allow deletions
        if (raw === '' || Number.isNaN(parseFloat(raw))) {
            calculateAmount();
            return;
        }
        calculateAmount();
    });

    // Format only after typing is done
    input.addEventListener('blur', () => {
        const raw = input.value.replace(/,/g, '').trim();
        if (raw === '') {
            input.value = '';
            return;
        }
        const numericVal = parseFloat(raw);
        input.value = Number.isNaN(numericVal)
            ? ''
            : numericVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
});

// Initialize on page load with formatted inputs
document.querySelectorAll('.area-field, .unitcost-field').forEach(input => {
    const raw = (input.value || '').replace(/,/g, '').trim();
    if (raw === '') return;

    const numericVal = parseFloat(raw);
    if (!Number.isNaN(numericVal)) {
        input.value = formatWithGrouping(numericVal);
    }
});
document.querySelectorAll('.manual-amount-field').forEach(input => {
    const raw = (input.value || '').replace(/,/g, '').trim();
    if (raw === '') return;

    const numericVal = parseFloat(raw);
    if (!Number.isNaN(numericVal)) {
        input.value = numericVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
});
calculateAmount();
calculateProjectSize();
</script>