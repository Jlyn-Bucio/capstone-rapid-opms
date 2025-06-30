<!-- dashboard.php -->
<div class="container py-5">
    <div class="row align-items-center mb-4">
      <div class="col-md-6">
        <h3>Dashboard</h3>
      </div>
      <div class="col-md-6 d-flex justify-content-md-end align-items-center gap-2">
        <span id="realtimeClock" class="text-muted small"></span>
        <span class="badge bg-primary">Admin</span>
      </div>
    </div>

    <div class="row">
      <!-- Calendar -->
      <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Calendar For Schedules</h5>
            <div id="calendar"></div>
          </div>
        </div>
      </div>

      <!-- Total Weekly Sales -->
      <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Total Weekly Sales</h5>
            <h4 class="text-end">₱0.00</h4>
          </div>
        </div>
      </div>

      <!-- Project Accomplishment -->
      <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Project Accomplishment</h5>
            <p>0 completed out of 4 projects</p>
          </div>
        </div>
      </div>

      <!-- Total Monthly Sales -->
      <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Total Monthly Sales</h5>
            <h4 class="text-end">₱0.00</h4>
          </div>
        </div>
      </div>

      <!-- Area Accomplishment -->
      <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Area Accomplishment</h5>
            <p>Design - 0%</p>
            <p>Construction - 0%</p>
            <p>Planning - 0%</p>
            <p>Implementation - 0%</p>
          </div>
        </div>
      </div>

      <!-- Top-Tier Purchase Client -->
      <div class="col-md-6 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Top-Tier Purchase Client</h5>
            <!-- Dynamic client list goes here -->
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
