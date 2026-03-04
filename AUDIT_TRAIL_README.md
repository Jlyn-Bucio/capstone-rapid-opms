 # AUDIT TRAIL SETUP GUIDE

## Overview
The Audit Trail system tracks all changes to records in the system for compliance, accountability, and data recovery purposes. It logs:
- **CREATE**: New records created
- **UPDATE**: Changes to existing records
- **DELETE**: Records deleted (soft delete)
- **RESTORE**: Deleted records restored

## Files Created

1. **database/audit_trail_migration.sql** - Database table schema
2. **includes/audit_trail.php** - Helper functions for logging and retrieving audit data
3. **includes/audit_trail/list.php** - Audit trail list view with filters
4. **includes/audit_trail/view.php** - Detailed audit entry view with before/after comparison

## SETUP STEPS

### 1. Create the Database Table
Run the SQL migration:
```sql
-- Execute the SQL from: database/audit_trail_migration.sql
-- In phpMyAdmin, go to Rapid Concretech database and import/run the SQL
```

**Alternative**: Manual SQL execution in phpMyAdmin
```sql
CREATE TABLE IF NOT EXISTS audit_trails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255),
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    record_name VARCHAR(255),
    old_value LONGTEXT,
    new_value LONGTEXT,
    changes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_record_id (record_id),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Include the Audit Trail Functions
Add this line to the top of `includes/rapid_opms.php` (after the database connection):
```php
include_once __DIR__ . '/audit_trail.php';
```

### 3. Add Audit Trail Link to Sidebar
Edit `includes/dashboard/sidebar.php` and add this menu item:
```php
<a href="main.php?page=audit_trail/list" class="nav-link">
    <i class="fas fa-history me-2"></i><span class="d-none d-sm-inline">Audit Trail</span>
</a>
```

## IMPLEMENTATION IN CRUD OPERATIONS

### Logging CREATE Operations
When creating a new record, call:
```php
logAuditTrail(
    'CREATE',           // Action
    'projects',         // Table name
    $projectId,         // Record ID
    $projectName,       // Human-readable name
    null,               // Old value (null for create)
    $newData,           // New value (array of data)
    "Created new project"  // Optional description
);
```

**Example in create.php:**
```php
// After INSERT query
if ($stmt->affected_rows > 0) {
    // Get inserted ID
    $newProjectId = $conn->insert_id;
    
    logAuditTrail(
        'CREATE',
        'projects',
        $newProjectId,
        $projectName,
        null,
        [
            'name' => $projectName,
            'location' => $location,
            'start_date' => $startDate,
            'customer_id' => $customerId
        ]
    );
}
```

### Logging UPDATE Operations
When updating a record:
```php
// Get old data before update
$oldDataStmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$oldDataStmt->bind_param("i", $projectId);
$oldDataStmt->execute();
$oldData = $oldDataStmt->get_result()->fetch_assoc();
$oldDataStmt->close();

// ... perform UPDATE ...

// Log the change
logAuditTrail(
    'UPDATE',
    'projects',
    $projectId,
    $projectName,
    $oldData,           // Old values
    [                   // New values
        'name' => $newProjectName,
        'location' => $newLocation,
        'start_date' => $newStartDate
    ]
);
```

### Logging DELETE Operations
When soft-deleting a record:
```php
// Get record data before delete
$beforeStmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$beforeStmt->bind_param("i", $projectId);
$beforeStmt->execute();
$beforeData = $beforeStmt->get_result()->fetch_assoc();
$beforeStmt->close();

// ... perform soft DELETE (update deleted_at) ...

logAuditTrail(
    'DELETE',
    'projects',
    $projectId,
    $beforeData['name'],
    $beforeData,        // Store full previous state
    null                // No new value for deleted
);
```

### Logging RESTORE Operations
When restoring a deleted record:
```php
logAuditTrail(
    'RESTORE',
    'projects',
    $projectId,
    $projectName,
    null,               // Was deleted, so no meaningful old data
    $restoredData       // New/restored state
);
```

## USAGE

### Access the Audit Trail
1. Log in to the system
2. Navigate to **Audit Trail** in the sidebar (once integrated)
3. View the list of all changes with filters

### Filter Options
- **Table**: Filter by affected table (projects, billing, customers, etc.)
- **Action**: Filter by action type (CREATE, UPDATE, DELETE, RESTORE)
- **From Date / To Date**: Filter by date range

### View Detailed Changes
Click the **View Details** (eye icon) to see:
- Before/After comparison of changed data
- Full JSON of old and new values
- Timeline of all changes to that specific record
- User and IP address information

## BEST PRACTICES

1. **Always log before/after data**: Capture complete record state for audit purposes
2. **Use meaningful names**: The `record_name` should be human-readable (project name, invoice #, etc.)
3. **Log at appropriate points**: Log after successful commits, not before
4. **Include context**: Use the `changes` field to describe what changed
5. **Test thoroughly**: Verify logging works across all CRUD operations
6. **Retention policy**: Consider archiving old audit entries after 1-2 years for performance
7. **Security**: Restrict audit trail access to admin users only (add role check in view.php)

## SECURITY CONSIDERATIONS

- The audit trail captures IP addresses and user agents for security investigation
- Audit trail data should be considered sensitive and require role-based access control
- Consider adding encryption for sensitive fields in audit entries
- Implement audit trail export for compliance reports (SOX, GDPR, etc.)

## TROUBLESHOOTING

### Audit trails not appearing
1. Verify the table was created successfully
2. Check that `includes/audit_trail.php` is included
3. Ensure `logAuditTrail()` is being called with correct parameters

### Performance issues
1. Drop old audit entries regularly (older than 1-2 years)
2. Ensure indexes are created as per migration script
3. Consider archiving to a separate table if entries grow beyond 100k rows

## Future Enhancements

- [ ] Add email notifications on critical changes
- [ ] Generate compliance reports from audit data
- [ ] Add role-based access control (admin-only viewing)
- [ ] Implement data retention policies
- [ ] Add encrypted storage for sensitive changes
- [ ] Create dashboards showing change frequency by user/table
