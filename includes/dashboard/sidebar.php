<!-- sidebar.php -->
<div id="sidebar" class="sidebar text-black">
  <div class="sidebar-header text-center py-4">
    <img src="assets/pic/companylogo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 60px;" />
    <h4 class="mb-0">Rapid Concretech</h4>
    <h6>Builders Corporation</h6>
  </div>
  <hr class="my-0" style="border-color: #000;">

  <div class="accordion" id="sidebarMenu">
    <a class="nav-link text-black px-3" href="main.php">
      <i class="fas fa-chart-line"></i><span class="d-none d-sm-inline ms-2">Dashboard</span>
    </a>

    <!-- User Management -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers">
          <i class="fas fa-users"></i><span class="d-none d-sm-inline ms-2">User Management</span>
        </button>
      </h2>
      <div id="collapseUsers" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=user/list" class="nav-link text-black">
            <i class="fas fa-user-cog"></i><span class="d-none d-sm-inline ms-2">Manage Users</span>
          </a>
          <a href="main.php?page=user/create" class="nav-link text-black">
            <i class="fa fa-user-plus"></i><span class="d-none d-sm-inline ms-2">Add User</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Project Management -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProjects">
          <i class="fas fa-project-diagram me-2"></i><span class="d-none d-sm-inline">Project Management</span>
        </button>
      </h2>
      <div id="collapseProjects" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=projects/create" class="nav-link text-black">
            <i class="fas fa-clipboard-list"></i><span class="d-none d-sm-inline ms-2">Project Registration</span>
          </a>
          <a href="main.php?page=projects/list" class="nav-link text-black">
            <i class="fas fa-tasks"></i><span class="d-none d-sm-inline ms-2">Projects Management</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Billing -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBilling">
          <i class="fas fa-file-invoice-dollar me-2"></i><span class="d-none d-sm-inline">Billing</span>
        </button>
      </h2>
      <div id="collapseBilling" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=billing/create" class="nav-link text-black">
            <i class="fas fa-plus-circle"></i><span class="d-none d-sm-inline ms-2">Add Billing</span>
          </a>
          <a href="main.php?page=billing/list" class="nav-link text-black">
            <i class="fas fa-edit"></i><span class="d-none d-sm-inline ms-2">Manage Billing</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Inventory -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInventory">
          <i class="fas fa-boxes me-2"></i><span class="d-none d-sm-inline">Inventory</span>
        </button>
      </h2>
      <div id="collapseInventory" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=inventory/create" class="nav-link text-black">
            <i class="fas fa-plus-square"></i><span class="d-none d-sm-inline ms-2">Add Product</span>
          </a>
          <a href="main.php?page=inventory/list" class="nav-link text-black">
            <i class="fas fa-warehouse"></i><span class="d-none d-sm-inline ms-2">Manage Inventory</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Suppliers -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSuppliers">
          <i class="fas fa-truck me-2"></i><span class="d-none d-sm-inline">Suppliers</span>
        </button>
      </h2>
      <div id="collapseSuppliers" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=suppliers/create" class="nav-link text-black">
            <i class="fas fa-user-plus"></i><span class="d-none d-sm-inline ms-2">Add Supplier</span>
          </a>
          <a href="main.php?page=suppliers/list" class="nav-link text-black">
            <i class="fas fa-cogs"></i><span class="d-none d-sm-inline ms-2">Manage Suppliers</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Customers -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustomers">
          <i class="fas fa-user-friends me-2"></i><span class="d-none d-sm-inline">Customers</span>
        </button>
      </h2>
      <div id="collapseCustomers" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=customers/create" class="nav-link text-black">
            <i class="fas fa-user-plus"></i><span class="d-none d-sm-inline ms-2">Add Customer</span>
          </a>
          <a href="main.php?page=customers/list" class="nav-link text-black">
            <i class="fas fa-user-cog"></i><span class="d-none d-sm-inline ms-2">Manage Customer</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Reports -->
    <div class="accordion-item border-0">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed text-black" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReports">
          <i class="fas fa-chart-pie me-2"></i><span class="d-none d-sm-inline">Reports</span>
        </button>
      </h2>
      <div id="collapseReports" class="accordion-collapse collapse" data-bs-parent="#sidebarMenu">
        <div class="accordion-body ps-4">
          <a href="main.php?page=reports/week" class="nav-link text-black">
            <i class="fas fa-calendar-week"></i><span class="d-none d-sm-inline ms-2">Weekly Reports</span>
          </a>
          <a href="main.php?page=reports/month" class="nav-link text-black">
            <i class="fas fa-calendar-alt"></i><span class="d-none d-sm-inline ms-2">Monthly Reports</span>
          </a>
          <a href="main.php?page=reports/year" class="nav-link text-black">
            <i class="fas fa-calendar"></i><span class="d-none d-sm-inline ms-2">Yearly Reports</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Version -->
    <?php include_once __DIR__ . '/../version.php'; ?>
    <div class="text-center mt-4 px-3 text-muted small">
      <span>Version: <?= APP_VERSION ?></span>
    </div>

    <!-- Logout -->
    <div class="text-center mt-2 px-3">
      <form action="logout.php" method="post">
        <button type="submit" class="btn btn-danger w-100">
          <i class="fas fa-sign-out-alt"></i><span class="d-none d-sm-inline ms-2">Logout</span>
        </button>
      </form>
    </div>
  </div>
</div>
