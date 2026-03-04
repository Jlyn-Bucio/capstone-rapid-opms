<!-- 
****** // With file logging
*** $log = new Logger();
*** $audit = new AuditLogger($conn, $log);

****** // Without file logging
*** $audit = new AuditLogger($conn); 
-->

<?php
class AuditLogger
{
    private mysqli $conn;
    private ?Logger $fileLogger;
    private ?int $userId;

    public function __construct(mysqli $conn, ?Logger $fileLogger = null)
    {
        $this->conn = $conn;
        $this->fileLogger = $fileLogger; // optional
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    public function log(string $action, string $module, string $description): bool
    {
        // Insert into DB
        $stmt = $this->conn->prepare("
            INSERT INTO audit_trail
            SET action = ?, 
                module = ?, 
                description = ?, 
                user_id = ?, 
                created_at = NOW()
        ");
        if (!$stmt) {
            $this->fileLogger?->error("AuditLogger prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("sssi", $action, $module, $description, $this->userId);

        if ($stmt->execute()) {
            // Only log to file if Logger exists
            $this->fileLogger?->info("AUDIT: [$action][$module] $description");
            $stmt->close();
            return true;
        } else {
            $this->fileLogger?->error("AuditLogger execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }
}