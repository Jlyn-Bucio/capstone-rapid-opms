<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php';
include_once __DIR__ . '/../../../includes/rapid_opms.php';

use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('Asia/Manila');

if (!isset($_GET['project_id'])) die("Invalid Project ID");
$project_id = (int)$_GET['project_id'];

/* =========================
   LOGO PATH
========================= */
$logo_path = __DIR__ . '/../../../assets/pic/logo-bgremoved.png';
if (!file_exists($logo_path)) die("Logo not found: $logo_path");

// convert to base64
$logo_data = base64_encode(file_get_contents($logo_path));
$logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
$logo_src = 'data:image/'.$logo_type.';base64,'.$logo_data;

/* =========================
   GET PROJECT + CUSTOMER
========================= */
$stmt = $conn->prepare("
    SELECT p.name AS project_name, c.name AS customer_name, c.address, c.phone
    FROM projects p
    JOIN customers c ON c.id = p.customer_id
    WHERE p.id = ? AND p.deleted_at IS NULL
");

$stmt->bind_param("i", $project_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) die("Project not found.");

/* =========================
   GET BILLINGS
========================= */
$stmt = $conn->prepare("
    SELECT b.id, b.invoice_number, b.billing_date, b.description, b.amount
    FROM billing b
    WHERE b.project_id = ? AND b.deleted_at IS NULL
    ORDER BY b.billing_date ASC
");

$stmt->bind_param("i", $project_id);
$stmt->execute();
$billings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* =========================
   GET PAYMENTS
========================= */
$stmt = $conn->prepare("
    SELECT p.billing_id, p.payment_date, p.amount, p.reference_number
    FROM payments p
    WHERE p.project_id = ?
    ORDER BY p.payment_date ASC
");

$stmt->bind_param("i", $project_id);
$stmt->execute();
$payments_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* Organize payments per billing */
$payments = [];

foreach ($payments_data as $pay) {
    $payments[$pay['billing_id']][] = $pay;
}

/* Totals */
$total_billing = 0;
$total_paid = 0;

foreach ($billings as $b) {
    $total_billing += $b['amount'] ?? 0;
    if (isset($payments[$b['id']])) {
        foreach ($payments[$b['id']] as $p) {
            $total_paid += $p['amount'] ?? 0;
        }
    }
}

$balance = $total_billing - $total_paid;

/* =========================
   START HTML
========================= */
$html = '
<html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h2 { text-align: center; margin: 5px; }
            hr { border: 1px solid #000; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 12px; }
            table, th, td { border: 1px solid #000; }
            th, td { padding: 6px; text-align: left; }
            th { background-color: #eee; }
            ul { margin: 0; padding-left: 15px; }
            .text-end { text-align: right; }
        </style>
    </head>

    <body>

        <!-- COMPANY HEADER -->
        <table style="width:100%; border:none; text-align:center;">

            <tr>
                <td style="border:none; text-align:center;">
                    <img src="'.$logo_src.'" style="width:60px; vertical-align:middle; margin-right:8px;">
                    <span style="font-size:25px; font-weight:bold; vertical-align:middle;">
                        RAPID CONCRETECH BUILDERS CORPORATION
                    </span>
                    <span style="font-size:12px; font-style:italic;">
                        "Where skills and technology meet quality and efficiency"
                    </span>
                </td>
            </tr>
        </table>

        <hr>        

        <p style="text-align:center; font-size:22px;"><strong>STATEMENT OF ACCOUNT</strong></p>

        <table style="width:100%; border:none; margin-top:10px; font-size:14px;">

            <tr>
                <td style="width:60%; border:none;">
                    <strong>Client Name:</strong> '.htmlspecialchars($project['customer_name'] ?? '').'<br>
                    <strong>Address:</strong> '.htmlspecialchars($project['address'] ?? '').'<br>
                    <strong>Phone:</strong> '.htmlspecialchars($project['phone'] ?? '-').'
                </td>
                <td style="width:40%; border:none; text-align:right;">
                    <strong>Statement Date:</strong> '.date('M d, Y').'<br>
                    <strong>Date Due: </strong>ASAP
                </td>
            </tr>
        </table>
        <br><br>

        <p style="font-size:12.5px;">We are billing you the amount below representing the concrete floor finishing services rendered to your project/s.</p>
        <p style="font-size:12.5px; margin-top:-10px;">Attached herewith are the following Accomplishment Report forms/ Payment Request forms/ Service Invoice for your reference.</p>

        <table>
            <thead>
                <tr>
                    <th style="text-align:center; width:12%;">Date</th>
                    <th style="text-align:center;">Description</th>
                    <th style="text-align:center;width:15%;">Project</th>
                    <th style="text-align:center;width:15%;">P.O NO.</th>
                    <th style="text-align:center;width:20%;">Payment</th>
                    <th style="text-align:center;width:12%;" class="text-end">Balance</th>
                    <th style="text-align:center;width:15%;">Check Details</th>
                </tr>
            </thead>
            <tbody>
            ';

            /* =========================
            LOOP BILLINGS
            ========================= */
            foreach($billings as $b){
                $paid = 0;
                $payment_list = $payments[$b['id']] ?? [];
                foreach ($payment_list as $p) { $paid += $p['amount'] ?? 0; }
                $bill_balance = ($b['amount'] ?? 0) - $paid;
                $due_date = date('Y-m-d', strtotime(($b['billing_date'] ?? date('Y-m-d')). ' +30 days'));
                $balance_style = ($bill_balance > 0 && date('Y-m-d') > $due_date) ? 'color:red;font-weight:bold;' : '';

                // Description includes invoice number
                $description = '';
                if(!empty($b['invoice_number'] ?? '')){
                    $description .= htmlspecialchars($b['invoice_number'] ?? '').'<br>';
                }
                $description .= htmlspecialchars($b['description'] ?? '');

                $html .= '<tr>
                    <td>'.date('M d, Y', strtotime($b['billing_date'] ?? date('Y-m-d'))).'</td>
                    <td>'.$description.'</td>
                    <td>'.htmlspecialchars($project['project_name'] ?? '').'</td>
                    <td>'.htmlspecialchars($project['customer_name'] ?? '').'</td>
                    <td>';

                if(!empty($payment_list)){
                    $html .= '<ul>';
                    foreach($payment_list as $p){
                        $html .= '<li>'.
                            date('M d, Y', strtotime($p['payment_date'] ?? date('Y-m-d'))).
                            ' - ₱'.number_format($p['amount'] ?? 0,2).
                            '</li>';
                    }
                    $html .= '</ul>';
                } else { 
                    $html .= '-'; 
                }

                $html .= '</td>
                    <td class="text-end" style="'.$balance_style.'">₱'.number_format($bill_balance,2).'</td>
                    <td>';

                if(!empty($payment_list)){
                    $html .= implode('<br>', array_map(function($p){
                        return htmlspecialchars($p['reference_number'] ?? '');
                    }, $payment_list));
                } else { 
                    $html .= '-'; 
                }

                $html .= '</td></tr>';
            }

            /* =========================
            FOOTER TOTALS
            ========================= */
            $html .= '
            </tbody>

            <tfoot>
                <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end">₱'.number_format($total_billing,2).'</th>
                <th class="text-end">₱'.number_format($total_paid,2).'</th>
                <th class="text-end">₱'.number_format($balance,2).'</th>
                <th></th>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top:20px; font-size:11px;">
            Thank you! We look to provide you with quality service again. 
            Please refer all your clarifications on billing matters, if any, to the undersigned or call 418-3947 / 285-0539.<br>
            <strong>Important:</strong>
            If expanded withholding tax will be deducted from your 
            payments please furnish us with original copy of BIR Form 2307; otherwise the amount that was deducted and
            withheld shall remain outstanding and demandable for payment.
        </div>

        <br><br>

        <table style="width:100%; border:none; margin-top:40px;">
        <tr>
            <td style="width:50%; border:none; text-align:center;">
                _______________________<br>
                Prepared By
            </td>
            <td style="width:50%; border:none; text-align:center;">
                _______________________<br>
                Prepared By
                <br><br><br>
                _______________________<br>
                Checked By
                <br><br><br>
                _______________________<br>
                Approved By
            </td>
        </tr>
        </table>
    </body>
</html>
';

/* =========================
   GENERATE PDF
========================= */
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

if (ob_get_length()) ob_end_clean();

$dompdf->stream("SOA_Project_{$project_id}.pdf", ["Attachment" => false]);
exit;