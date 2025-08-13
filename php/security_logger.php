<?php
// security_logger.php - Centralized logging system

class SecurityLogger
{
    private $conn;
    private $log_table = 'security_logs';

    public function __construct($database_connection)
    {
        $this->conn = $database_connection;
    }

    public function logEvent($event_type, $description, $user_id = null, $user_role = null)
    {
        try {
            // Get user info from session if not provided
            if (session_status() === PHP_SESSION_ACTIVE && $event_type !== 'AUTHENTICATION_FAILURE') {
                if ($user_id === null && isset($_SESSION['userID'])) {
                    $user_id = $_SESSION['userID'];
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
                VALUES (?, ?, ?, ?)
            ");

            if (!$stmt) {
                error_log("Failed to prepare logging statement: " . $this->conn->error);
                return false;
            }

            $stmt->bind_param("ssss", $event_type, $user_id, $user_role, $description);
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
    public function logAuthSuccess($user_id, $user_role, $description = "User logged in successfully")
    {
        return $this->logEvent('AUTHENTICATION_SUCCESS', $description, $user_id, $user_role);
    }

    /**
     * Log authentication failure
     */
    public function logAuthFailure($description, $attempted_username = null)
    {
        $full_description = $description;
        if ($attempted_username) {
            $full_description .= " (Username attempted: " . substr($attempted_username, 0, 3) . "****)";
        }

        try {
            // Direct insert - completely bypass logEvent to avoid session override
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->log_table} 
                (event_type, user_id, user_role, event_description) 
                VALUES (?, NULL, NULL, ?)
            ");

            if (!$stmt) {
                error_log("Failed to prepare logging statement: " . $this->conn->error);
                return false;
            }

            $event_type = 'AUTHENTICATION_FAILURE';
            $stmt->bind_param("ss", $event_type, $full_description);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Security logging exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log input validation failure
     */
    public function logInputValidationFailure($field_name, $validation_rule, $additional_info = null)
    {
        $description = "Input validation failed for field: {$field_name}, Rule: {$validation_rule}";
        if ($additional_info) {
            $description .= " - " . $additional_info;
        }
        return $this->logEvent('INPUT_VALIDATION_FAILURE', $description);
    }

    /**
     * Log access control failure
     */
    public function logAccessControlFailureDirect($resource, $required_role = null, $user_role = null, $user_id = null)
    {
        $description = "Access denied to resource: {$resource}";
        if ($required_role) {
            $description .= " (Required: {$required_role}";
            if ($user_role) {
                $description .= ", User: {$user_role}";
            }
            $description .= ")";
        }

        // Direct insert like auth failures
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->log_table} 
                (event_type, user_id, user_role, event_description) 
                VALUES (?, ?, ?, ?)
            ");

            if (!$stmt) {
                error_log("Failed to prepare logging statement: " . $this->conn->error);
                return false;
            }

            $event_type = 'ACCESS_CONTROL_FAILURE';
            $stmt->bind_param("ssss", $event_type, $user_id, $user_role, $description);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Security logging exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log application error
     */
    public function logApplicationError($error_message, $context = null)
    {
        $description = "Application error: " . $error_message;
        if ($context) {
            $description .= " (Context: {$context})";
        }
        return $this->logEvent('APPLICATION_ERROR', $description);
    }

    /**
     * Get recent security events (for admin viewing)
     */
    public function getRecentEvents($limit = 100, $event_type = null)
    {
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
