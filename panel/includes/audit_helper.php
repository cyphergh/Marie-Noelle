<?php
function log_audit_action($con, $params) {
    $defaults = [
        'user_type' => 'staff',
        'user_id' => null,
        'user_name' => 'Unknown',
        'action' => 'create',
        'entity_type' => '',
        'entity_id' => 0,
        'old_values' => null,
        'new_values' => null,
        'description' => ''
    ];
    
    $params = array_merge($defaults, $params);
    
    $userType = mysqli_real_escape_string($con, $params['user_type']);
    $userId = (int) ($params['user_id'] ?? 0);
    $userName = mysqli_real_escape_string($con, $params['user_name']);
    $action = mysqli_real_escape_string($con, $params['action']);
    $entityType = mysqli_real_escape_string($con, $params['entity_type']);
    $entityId = (int) $params['entity_id'];
    $oldValues = $params['old_values'] !== null ? mysqli_real_escape_string($con, is_array($params['old_values']) ? json_encode($params['old_values']) : $params['old_values']) : null;
    $newValues = $params['new_values'] !== null ? mysqli_real_escape_string($con, is_array($params['new_values']) ? json_encode($params['new_values']) : $params['new_values']) : null;
    $description = mysqli_real_escape_string($con, $params['description']);
    $ipAddress = mysqli_real_escape_string($con, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    
    $query = "INSERT INTO audit_log (user_type, user_id, user_name, action, entity_type, entity_id, old_values, new_values, description, ip_address) 
              VALUES ('$userType', " . ($userId > 0 ? $userId : 'NULL') . ", '$userName', '$action', '$entityType', $entityId, " . 
              ($oldValues !== null ? "'$oldValues'" : 'NULL') . ", " . 
              ($newValues !== null ? "'$newValues'" : 'NULL') . ", '$description', '$ipAddress')";
    
    mysqli_query($con, $query);
}

function get_audit_log($con, $filters = []) {
    $defaults = [
        'limit' => 100,
        'offset' => 0,
        'user_type' => null,
        'user_id' => null,
        'action' => null,
        'entity_type' => null,
        'date_from' => null,
        'date_to' => null,
        'search' => null
    ];
    
    $filters = array_merge($defaults, $filters);
    
    $where = [];
    
    if ($filters['user_type']) {
        $where[] = "user_type = '" . mysqli_real_escape_string($con, $filters['user_type']) . "'";
    }
    
    if ($filters['user_id']) {
        $where[] = "user_id = " . (int) $filters['user_id'];
    }
    
    if ($filters['action']) {
        $where[] = "action = '" . mysqli_real_escape_string($con, $filters['action']) . "'";
    }
    
    if ($filters['entity_type']) {
        $where[] = "entity_type = '" . mysqli_real_escape_string($con, $filters['entity_type']) . "'";
    }
    
    if ($filters['date_from']) {
        $where[] = "created_at >= '" . mysqli_real_escape_string($con, $filters['date_from']) . " 00:00:00'";
    }
    
    if ($filters['date_to']) {
        $where[] = "created_at <= '" . mysqli_real_escape_string($con, $filters['date_to']) . " 23:59:59'";
    }
    
    if ($filters['search']) {
        $search = mysqli_real_escape_string($con, $filters['search']);
        $where[] = "(user_name LIKE '%$search%' OR description LIKE '%$search%' OR entity_type LIKE '%$search%')";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $limit = (int) $filters['limit'];
    $offset = (int) $filters['offset'];
    
    $query = "SELECT * FROM audit_log $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    
    return mysqli_query($con, $query);
}

function count_audit_log($con, $filters = []) {
    $defaults = [
        'user_type' => null,
        'user_id' => null,
        'action' => null,
        'entity_type' => null,
        'date_from' => null,
        'date_to' => null,
        'search' => null
    ];
    
    $filters = array_merge($defaults, $filters);
    
    $where = [];
    
    if ($filters['user_type']) {
        $where[] = "user_type = '" . mysqli_real_escape_string($con, $filters['user_type']) . "'";
    }
    
    if ($filters['user_id']) {
        $where[] = "user_id = " . (int) $filters['user_id'];
    }
    
    if ($filters['action']) {
        $where[] = "action = '" . mysqli_real_escape_string($con, $filters['action']) . "'";
    }
    
    if ($filters['entity_type']) {
        $where[] = "entity_type = '" . mysqli_real_escape_string($con, $filters['entity_type']) . "'";
    }
    
    if ($filters['date_from']) {
        $where[] = "created_at >= '" . mysqli_real_escape_string($con, $filters['date_from']) . " 00:00:00'";
    }
    
    if ($filters['date_to']) {
        $where[] = "created_at <= '" . mysqli_real_escape_string($con, $filters['date_to']) . " 23:59:59'";
    }
    
    if ($filters['search']) {
        $search = mysqli_real_escape_string($con, $filters['search']);
        $where[] = "(user_name LIKE '%$search%' OR description LIKE '%$search%' OR entity_type LIKE '%$search%')";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $result = mysqli_query($con, "SELECT COUNT(*) as total FROM audit_log $whereClause");
    $row = mysqli_fetch_assoc($result);
    return (int) $row['total'];
}

function get_action_icon($action) {
    $icons = [
        'create' => '<i class="fa fa-plus-circle" style="color: #28a745;"></i>',
        'update' => '<i class="fa fa-edit" style="color: #17a2b8;"></i>',
        'delete' => '<i class="fa fa-trash" style="color: #dc3545;"></i>',
        'void' => '<i class="fa fa-ban" style="color: #ffc107;"></i>',
        'refund' => '<i class="fa fa-undo" style="color: #6c757d;"></i>',
        'login' => '<i class="fa fa-sign-in" style="color: #6c757d;"></i>',
        'logout' => '<i class="fa fa-sign-out" style="color: #6c757d;"></i>'
    ];
    return $icons[$action] ?? '<i class="fa fa-circle" style="color: #6c757d;"></i>';
}

function get_action_badge_class($action) {
    $classes = [
        'create' => 'bg-success',
        'update' => 'bg-info',
        'delete' => 'bg-danger',
        'void' => 'bg-warning',
        'refund' => 'bg-secondary'
    ];
    return $classes[$action] ?? 'bg-secondary';
}

function get_entity_type_label($type) {
    $labels = [
        'invoice' => 'Invoice',
        'appointment' => 'Appointment',
        'booking' => 'Booking',
        'payment' => 'Payment',
        'customer' => 'Customer',
        'service' => 'Service',
        'staff' => 'Staff',
        'plan' => 'Plan',
        'subscription' => 'Subscription'
    ];
    return $labels[$type] ?? ucfirst($type);
}

function ensure_audit_table($con) {
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_type ENUM('admin', 'staff', 'system') DEFAULT 'staff',
            user_id INT DEFAULT NULL,
            user_name VARCHAR(100) DEFAULT 'Unknown',
            action ENUM('create', 'update', 'delete', 'void', 'refund', 'login', 'logout') NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT DEFAULT 0,
            old_values TEXT,
            new_values TEXT,
            description VARCHAR(500),
            ip_address VARCHAR(45) DEFAULT '0.0.0.0',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_type, user_id),
            INDEX idx_action (action),
            INDEX idx_entity (entity_type, entity_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
