<?php
include_once __DIR__ . '/../rapid_opms.php';

/* =======================
   CURRENCY FORMATTER
======================= */
if (class_exists('NumberFormatter')) {
    $fmt = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);
} else {
    $fmt = new class {
        public function formatCurrency($amount, $currency = 'PHP') {
            return '₱' . number_format((float)$amount, 2, '.', ',');
        }
    };
}

$start_of_week = date('Y-m-d', strtotime('monday this week'));
$end_of_week   = date('Y-m-d', strtotime('sunday this week'));

/* =======================
   AJAX: TOP 10 CLIENTS
======================= */
if (isset($_GET['load']) && $_GET['load'] === 'top_clients') {

    $year_start = date('Y-01-01 00:00:00');
    $year_end   = date('Y-12-31 23:59:59');

    $stmt = $conn->prepare("
    SELECT c.name, SUM(b.amount) total_spent
    FROM billing b
    JOIN customers c ON c.id = b.customer_id
    WHERE b.billing_date BETWEEN ? AND ?
    GROUP BY b.customer_id
    ORDER BY total_spent DESC
    LIMIT 10
");
    $stmt->bind_param("ss", $year_start, $year_end);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$rows) {
        echo "<tr><td colspan='3' class='text-center text-muted'>No data</td></tr>";
    } else {
        foreach ($rows as $i => $r) {
            echo "<tr>
                    <td>".($i+1)."</td>
                    <td>".htmlspecialchars($r['name'])."</td>
                    <td>".$fmt->formatCurrency($r['total_spent'])."</td>
                  </tr>";
        }
    }
    exit;
}

/* =======================
   AJAX: CALENDAR EVENTS
======================= */
if (isset($_GET['load']) && $_GET['load'] === 'calendar_events') {

    $events = [];
    $q = $conn->query("
        SELECT id, name, start_date, end_date
        FROM projects
        WHERE start_date IS NOT NULL
          AND end_date IS NOT NULL
    ");

    while ($r = $q->fetch_assoc()) {
    $events[] = [
        'id'    => $r['id'],
        'title' => $r['name'],
        'start' => date('Y-m-d', strtotime($r['start_date'])),
        'end'   => date('Y-m-d', strtotime($r['end_date'].' +1 day')),
        'allDay'=> true
    ];
}


    echo json_encode($events);
    exit;
}

/* =======================
   AJAX: PROJECTS BY DATE
======================= */
if (isset($_GET['load']) && $_GET['load'] === 'projects_by_date') {

    $date = $_GET['date'];

    $stmt = $conn->prepare("
        SELECT name, location, project_manager
        FROM projects
        WHERE DATE(start_date) <= ?
          AND DATE(end_date) >= ?
    ");
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    exit;
}

/* =======================
   AJAX: SALES REPORTS
======================= */
if (isset($_GET['load']) && $_GET['load'] === 'sales_reports') {

    $start_dt = $start_of_week . ' 00:00:00';
    $end_dt = $end_of_week . ' 23:59:59';
    $weekly  = $conn->query("SELECT SUM(amount) t FROM billing WHERE billing_date BETWEEN '$start_dt' AND '$end_dt' AND deleted_at IS NULL")
                    ->fetch_assoc()['t'] ?? 0;

    $monthly = $conn->query("SELECT SUM(amount) t FROM billing WHERE billing_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")
                    ->fetch_assoc()['t'] ?? 0;

    $yearly  = $conn->query("SELECT SUM(amount) t FROM billing WHERE billing_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')")
                    ->fetch_assoc()['t'] ?? 0;

    echo json_encode([
        'weekly'  => $fmt->formatCurrency($weekly),
        'monthly' => $fmt->formatCurrency($monthly),
        'yearly'  => $fmt->formatCurrency($yearly)
    ]);
    exit;
}

/* =======================
   INITIAL LOAD (PAGE LOAD)
======================= */
$start_dt = $start_of_week . ' 00:00:00';
$end_dt = $end_of_week . ' 23:59:59';
$weekly_sales  = $conn->query("SELECT SUM(amount) t FROM billing WHERE billing_date BETWEEN '$start_dt' AND '$end_dt' AND deleted_at IS NULL")
                      ->fetch_assoc()['t'] ?? 0;

$monthly_sales = $conn->query("SELECT SUM(amount) t FROM billing WHERE billing_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")
                      ->fetch_assoc()['t'] ?? 0;

$yearly_sales  = $conn->query("SELECT SUM(amount) t FROM billing WHERE billing_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')")
                      ->fetch_assoc()['t'] ?? 0;

$year_start = date('Y-01-01 00:00:00');
$year_end   = date('Y-12-31 23:59:59');

$stmt = $conn->prepare("
    SELECT c.name, SUM(b.amount) total_spent
    FROM billing b
    JOIN customers c ON c.id = b.customer_id
    WHERE b.billing_date BETWEEN ? AND ?
    GROUP BY b.customer_id
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->bind_param("ss", $year_start, $year_end);
$stmt->execute();
$top_clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container-fluid py-4">

  <h3 class="mb-4">Dashboard</h3>

  <!-- SALES CARDS -->
  <div class="row">
    <div class="col-md-4 mb-4">
      <div class="card shadow">
        <div class="card-body">
          <h5><strong>Weekly Sales</strong></h5>
          <h3 class="text-end" id="weeklySales"><?= $fmt->formatCurrency($weekly_sales) ?></h3>
        </div>
      </div>
    </div>

    <div class="col-md-4 mb-4">
      <div class="card shadow">
        <div class="card-body">
          <h5><strong>Monthly Sales</strong></h5>
          <h3 class="text-end" id="monthlySales"><?= $fmt->formatCurrency($monthly_sales) ?></h3>
        </div>
      </div>
    </div>

    <div class="col-md-4 mb-4">
      <div class="card shadow">
        <div class="card-body">
          <h5><strong>Yearly Sales</strong></h5>
          <h3 class="text-end" id="yearlySales"><?= $fmt->formatCurrency($yearly_sales) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- CALENDAR + TOP CLIENTS -->
  <div class="row">

    <!-- PROJECT CALENDAR -->
    <div class="col-md-6 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h5 class="mb-3"><strong>Project Calendar</strong></h5>
          <div id="calendar"></div>
        </div>
      </div>
    </div>

    <!-- TOP 10 CLIENTS -->
    <div class="col-md-6 mb-4">
      <div class="card shadow h-100">
          <h5><strong>Top 10 High Purchase Clients (<?= date('Y') ?>)</strong></h5>

        <div class="card-body table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Client</th> 
                <th>Total Spent</th>
              </tr>
            </thead>
            <tbody id="topClientsBody">
              <?php if (!$top_clients): ?>
                <tr>
                  <td colspan="3" class="text-center text-muted">No data</td>
                </tr>
              <?php else: foreach ($top_clients as $i => $c): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($c['name']) ?></td>
                  <td><?= $fmt->formatCurrency($c['total_spent']) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- AUTO REFRESH (REPORTS + TOP CLIENTS) -->
<script>
function refreshTopClients() {
    fetch('main.php?page=dashboard&load=top_clients')
        .then(r => r.text())
        .then(html => document.getElementById('topClientsBody').innerHTML = html);
}

function refreshReports() {
    fetch('main.php?page=dashboard&load=sales_reports')
        .then(r => r.json())
        .then(d => {
            document.getElementById('weeklySales').textContent  = d.weekly;
            document.getElementById('monthlySales').textContent = d.monthly;
            document.getElementById('yearlySales').textContent  = d.yearly;
        });
}

</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('calendar');
    if (!el) return;

    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',

        dayMaxEvents: true, 

        events: 'main.php?page=dashboard&load=calendar_events',

        dateClick: function(info) {
            fetch('main.php?page=dashboard&load=projects_by_date&date=' + info.dateStr)
                .then(r => r.json())
                .then(rows => {

                    if (!rows.length) {
                        alert('No projects on this date');
                        return;
                    }

                    let msg = 'Projects on ' + info.dateStr + ':\n\n';

                    rows.forEach((p, i) => {
                        msg += (i+1) + '. ' + p.name +
                               '\n   Manager: ' + p.project_manager +
                               '\n   Location: ' + p.location + '\n\n';
                    });

                    alert(msg);
                });
        }
    });

    calendar.render();
});
</script>
