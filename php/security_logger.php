<?php
// security_logger.php - Centralized logging system

class SecurityLogger {
    private $conn;
    private $log_table = 'security_logs';
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Log security events to database
     * @param string $event_type - Type of event from ENUM
     * @param string $description - Event description
     * @param int|null $user_id - User ID if available
     * @param string|null $user_role - User role if available
     * @return bool - Success status
     */
    public function logEvent($event_type, $description, $user_id = null, $user_role = null, $ip_address = null) {
        try {        
           // Get user info from session if not provided
            if (session_status() === PHP_SESSION_ACTIVE) {
                if ($user_id === null && isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                }
                if ($user_role === null && isset($_SESSION['role'])) {
                    $user_role = $_SESSION['role'];
                }
            }
            
            // Truncate description if too long
            if (strlen($description) > 255) {
                $description = substr($description, 0, 252) . '...';
            }
            
            // Prepare statement
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->log_table} 
                (event_type, user_id, user_role, event_description) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                error_log("Failed to prepare logging statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("sisss", $event_type, $user_id, $user_role, $ip_address, $description);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Failed to log security event: " . $stmt->error);
            }
            
            $stmt->close();
            return $result;
            
        } catch (Exception $e) {
            error_log("Security logging exception: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log authentication success
     */
    public function logAuthSuccess($user_id, $user_role, $description = "User logged in successfully") {
        return $this->logEvent('AUTHENTICATION_SUCCESS', $description, $user_id, $user_role);
    }
    
    /**
     * Log authentication failure
     */
    public function logAuthFailure($description, $attempted_username = null) {
        $full_description = $description;
        if ($attempted_username) {
            // Don't log full username for security, just indicate attempt
            $full_description .= " (Username attempted: " . substr($attempted_username, 0, 3) . "****)";
        }
        return $this->logEvent('AUTHENTICATION_FAILURE', $full_description);
    }
    
    /**
     * Log input validation failure
     */
    public function logInputValidationFailure($field_name, $validation_rule, $additional_info = null) {
        $description = "Input validation failed for field: {$field_name}, Rule: {$validation_rule}";
        if ($additional_info) {
            $description .= " - " . $additional_info;
        }
        return $this->logEvent('INPUT_VALIDATION_FAILURE', $description);
    }
    
    /**
     * Log access control failure
     */
    public function logAccessControlFailure($resource, $required_role = null, $user_role = null) {
        $description = "Access denied to resource: {$resource}";
        if ($required_role) {
            $description .= " (Required: {$required_role}";
            if ($user_role) {
                $description .= ", User: {$user_role}";
            }
            $description .= ")";
        }
        return $this->logEvent('ACCESS_CONTROL_FAILURE', $description);
    }
    
    /**
     * Log application error
     */
    public function logApplicationError($error_message, $context = null) {
        $description = "Application error: " . $error_message;
        if ($context) {
            $description .= " (Context: {$context})";
        }
        return $this->logEvent('APPLICATION_ERROR', $description);
    }
    
    /**
     * Get recent security events (for admin viewing)
     */
    public function getRecentEvents($limit = 100, $event_type = null) {
        try {
            $sql = "SELECT * FROM {$this->log_table}";
            if ($event_type) {
                $sql .= " WHERE event_type = ?";
            }
            $sql .= " ORDER BY event_timestamp DESC LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            
            if ($event_type) {
                $stmt->bind_param("si", $event_type, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $events = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $events;
        } catch (Exception $e) {
            error_log("Error retrieving security events: " . $e->getMessage());
            return false;
        }
    }
}

// Example usage in your authentication script:
/*
// After successful login:
require_once 'security_logger.php';
$logger = new SecurityLogger($conn);
$logger->logAuthSuccess($_SESSION['user_id'], $_SESSION['role']);

// After failed login:
$logger->logAuthFailure("Invalid password", $_POST['username']);

// After input validation failure:
$logger->logInputValidationFailure("email", "invalid email format");

// After access control failure:
$logger->logAccessControlFailure("admin dashboard", "Admin", $_SESSION['role']);

// After application error:
$logger->logApplicationError("Database connection failed", "login.php");
*/
?>