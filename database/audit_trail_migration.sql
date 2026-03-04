-- Audit Trail Table
-- Tracks all changes to records in the system

CREATE TABLE IF NOT EXISTS audit_trails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255),
    action VARCHAR(50) NOT NULL,          -- 'CREATE', 'UPDATE', 'DELETE', 'RESTORE'
    table_name VARCHAR(100) NOT NULL,     -- 'projects', 'billing', 'customers', etc.
    record_id INT NOT NULL,
    record_name VARCHAR(255),             -- Project name, Invoice #, Customer name, etc.
    old_value LONGTEXT,                   -- Previous data (JSON)
    new_value LONGTEXT,                   -- New data (JSON)
    changes TEXT,                         -- Description of what changed
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
