<?php
// Show any errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

<div class="container py-5">
    <div class="row justify-content-center">

        <div class="card-body">
          <div class="mb-4 px-4 py-3 rounded" style="background-color: #b0b0b0; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);">
            <h5><i class="fa fa-plus me-2"></i>Add New Supplier</h5>
        </div>
        <div class="card-body">
    <form action="includes/suppliers/save.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="contact_person" class="form-label">Contact Person</label>
            <input type="text" id="contact_person" name="contact_person" class="form-control">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control">
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control">
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <input type="text" id="address" name="address" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Save Supplier</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
