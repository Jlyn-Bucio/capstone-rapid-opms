<?php
ob_start(); // Start output buffering
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: loggin.php");
    exit();
}

// Include database connection
include_once 'includes/rapid_opms.php';

// Fetch upcoming and current project dates for the calendar
$project_events = [];
$today_for_query = date('Y-m-d');
$result = $conn->query("SELECT id, name, start_date FROM projects WHERE start_date >= '{$today_for_query}'");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $date = date('Y-m-d', strtotime($row['start_date']));
        if (!isset($project_events[$date])) {
            $project_events[$date] = [];
        }
        $project_events[$date][] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Rapid Concretech</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/user.css">
  <link rel="stylesheet" href="assets/css/reports.css">
  
</head>
<style>

  /* CSS Variables */
  :root {
    --bg-body:rgb(255, 255, 255, 0.23);
    --bg-sidebar: white;
    --bg-accordion: white;
    --bg-accordion-active: rgba(255, 255, 255, 0.23);
    --bg-accordion-hover: rgba(255, 255, 255, 0.15);
    --bg-card:rgba(170, 170, 170, 0.56);
    --bg-dashboard:rgba(140, 212, 73, 0.56);
    --bg-overlay: rgba(0, 0, 0, 0.4);
    --bg-logout: rgb(180, 76, 76);
    --bg-logout-hover: rgb(4, 4, 4);
    --text-dark: #000;
    --text-light: #fff;
    --font-main: Arial, sans-serif;
  }

  body {
    font-family: var(--font-main);
    margin: 0;
    padding: 0;
    background: var(--bg-body);
  }

  /* Sidebar */
  .sidebar {
    width: 280px;
    height: 100vh;
    position: fixed;
    background: var(--bg-sidebar);
    overflow-y: auto;
    padding: 1rem;
    transition: transform 0.3s ease;
    z-index: 1050;
  }

  .sidebar.hidden {
    transform: translateX(-100%);
  }

  .sidebar h4,
  .sidebar h6 {
    text-align: center;
  }

  .sidebar img {
    display: block;
    margin: 0 auto 0.5rem;
    width: 70px;
  }

  .sidebar .nav-link {
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0;
    text-decoration: none;
    transition: background-color 0.3s;
  }

  .sidebar .nav-link:hover {
    background-color: var(--bg-accordion);
    text-decoration: none;
  }

  .sidebar .submenu .nav-link {
    padding-left: 1.5rem;
    font-size: 0.9rem;
  }

  /* Accordion */
  .accordion-item {
    background: var(--bg-sidebar) !important;
    border: none;
    box-shadow: none;
  }

  .accordion-button {
    background: var(--bg-accordion) !important;
    color: var(--text-dark) !important;
    border: none;
    border-radius: 5px !important;
    margin-bottom: 0.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: none;
  }

  .accordion-button:not(.collapsed) {
    background: var(--bg-accordion-active) !important;
    color: var(--text-dark) !important;
  }

  .accordion-body {
    background: var(--bg-sidebar);
    padding: 0.5rem 0;
  }

  .accordion-button::after {
    filter: brightness(0.3);
  }

  /* Main Content */
  .main-content {
    padding: 1rem;
    background: var(--bg-body);
    margin-left: 280px;
  }

  /* Card Styling */
  .card {
    background-color: var(--bg-card);
    padding: 1.5rem;
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
    color: var(--text-dark);
  }
  .dashboard-card {
    background-color: var(--bg-dashboard);
  }
  .card:hover {
    transform: scale(1.02);
    transition: transform 0.2s ease-in-out;
  }


  /* Burger Button */
  .burger-btn {
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1100;
    background: transparent;
    border: none;
    font-size: 1.8rem;
    cursor: pointer;
    color: #333;
    display: none;
  }

  /* Overlay */
  .overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background: var(--bg-overlay);
    z-index: 1040;
  }

  .overlay.show {
    display: block;
  }

  /* Logout Button */
  .logout-link {
    text-align: center;
    margin-top: 1rem;
  }

  .logout-link a {
    color: var(--text-light);
    background-color: var(--bg-logout);
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }

  .logout-link a:hover {
    background-color: var(--bg-logout-hover);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar {
      transform: translateX(-100%);
      position: fixed;
    }

    .sidebar.show {
      transform: translateX(0);
    }

    .burger-btn {
      display: block;
    }

    .main-content {
      margin-left: 0;
    }
  }

  .calendar-day {
    text-align: center;
    padding: 0.5rem 0;
    border-radius: 50%;
    transition: background-color 0.2s;
    position: relative;
  }
  .calendar-day.today {
    background-color: #0d6efd; /* Bootstrap Primary Blue */
    color: white;
    font-weight: bold;
  }
  .calendar-day.scheduled {
    background-color: #ffc107; /* Bootstrap Warning Yellow */
    color: black;
  }
  .calendar-day.today.scheduled {
    background-image: linear-gradient(45deg, #0d6efd 50%, #ffc107 50%);
  }
  .calendar-day a {
    color: inherit;
    text-decoration: none;
    display: block;
  }
</style>  
<body >

<!-- Burger button for mobile -->
<button class="burger-btn" id="burgerBtn" aria-label="Toggle Sidebar">&#9776;</button>

<!-- Sidebar -->
<?php include 'includes/dashboard/sidebar.php'; ?>

<!-- Overlay for mobile sidebar -->
<div class="overlay" id="overlay"></div>

<!-- Main content section -->

<?php
  $page = isset($_GET['page']) ? basename($_GET['page']) : '';
?>

<div class="main-content <?= in_array($page, ['create', 'list', 'edit', 'delete']) ? $page . '-page' : '' ?>">

<?php
$page = $_GET['page'] ?? '';

if ($page) {
    $file = "includes/{$page}.php"; // supports subfolders like 'projects/create'
    
    if (file_exists($file)) {
        include $file;
    } else {
        echo "<p class='text-danger p-4'>Page not found: {$file}</p>";
    }
} else {
    include 'includes/dashboard/main.php'; // default dashboard
}
?>

<script>
  const burgerBtn = document.getElementById('burgerBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

  burgerBtn.addEventListener('click', () => {
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
  });

  overlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
  });

  // Optional: Close sidebar when resizing to desktop
  window.addEventListener('resize', () => {
    if(window.innerWidth > 768) {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    }
  });

  // Calendar JS from your original code
  const calendarEl = document.getElementById("calendar");
  if (calendarEl) {
    const scheduledEvents = <?= json_encode($project_events); ?>;
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    function renderCalendar(month, year) {
      const firstDay = new Date(year, month, 1);
      const lastDay = new Date(year, month + 1, 0);
      const todayDateString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

      let html = '<div class="d-flex justify-content-between align-items-center bg-black text-white p-2">';
      html += `<button onclick="changeMonth(-1)" class="btn btn-sm btn-light">&lt;</button>`;
      html += `<span class="fw-bold">${firstDay.toLocaleString('default', { month: 'long' })} ${year}</span>`;
      html += `<button onclick="changeMonth(1)" class="btn btn-sm btn-light">&gt;</button>`;
      html += '</div>';

      html += '<div class="text-center mt-2">';
      html += '<div class="row fw-bold">';
      ['S','M','T','W','T','F','S'].forEach(d => html += `<div class="col">${d}</div>`);
      html += '</div>';

      let day = 1;
      let startDay = firstDay.getDay();
      for (let i = 0; i < 6; i++) {
        html += '<div class="row">';
        for (let j = 0; j < 7; j++) {
          if (i === 0 && j < startDay) {
            html += '<div class="col">&nbsp;</div>';
          } else if (day > lastDay.getDate()) {
            html += '<div class="col">&nbsp;</div>';
          } else {
            const currentDateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            let classes = 'calendar-day';
            let content = day;
            let projectsToday = scheduledEvents[currentDateString];

            if (currentDateString === todayDateString) {
              classes += ' today';
            }
            if (projectsToday) {
              classes += ' scheduled';
              const projectNames = projectsToday.map(p => p.name).join(', ');
              const firstProjectId = projectsToday[0].id;
              content = `<a href="main.php?page=projects/view&id=${firstProjectId}" title="${projectNames}">${day}</a>`;
            }
            
            html += `<div class="col"><div class="${classes}">${content}</div></div>`;
            day++;
          }
        }
        html += '</div>';
      }
      html += '</div>';

      calendarEl.innerHTML = html;
    }

    function changeMonth(offset) {
      currentMonth += offset;
      if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
      } else if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
      }
      renderCalendar(currentMonth, currentYear);
    }

    renderCalendar(currentMonth, currentYear);
  }

  // Real-time clock update
  function updateClock() {
    const now = new Date();
    const options = { 
      year: 'numeric', month: 'long', day: 'numeric', 
      hour: '2-digit', minute: '2-digit', second: '2-digit',
      hour12: true 
    };
    document.getElementById('realtimeClock').textContent = now.toLocaleString('en-US', options);
  }

  updateClock();
  setInterval(updateClock, 1000);
  </script>
<?php ob_end_flush(); ?>
</body>
</html>
