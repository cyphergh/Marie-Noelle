<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../../panel/includes/dbconnection.php';

function staff_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function staff_is_logged_in()
{
    return !empty($_SESSION['bpmsstid']);
}

function staff_is_ajax_request()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function staff_json_response($success, $message, $payload = array(), $httpCode = 200)
{
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');
    }

    echo json_encode(array_merge(
        array(
            'success' => (bool) $success,
            'message' => $message,
        ),
        $payload
    ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    exit;
}

function staff_current_id()
{
    return isset($_SESSION['bpmsstid']) ? (int) $_SESSION['bpmsstid'] : 0;
}

function staff_require_login()
{
    if (!staff_is_logged_in()) {
        if (staff_is_ajax_request()) {
            staff_json_response(false, 'Authentication required.', array(), 401);
        }
        header('location:index.php');
        exit;
    }
}

function staff_generate_csrf_token()
{
    if (empty($_SESSION['staff_csrf_token'])) {
        $_SESSION['staff_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['staff_csrf_token'];
}

function staff_validate_csrf_token($token)
{
    return isset($_SESSION['staff_csrf_token']) && hash_equals($_SESSION['staff_csrf_token'], $token);
}

function staff_fetch_current($con)
{
    $staffId = staff_current_id();
    if (!$staffId) {
        return null;
    }

    $stmt = mysqli_prepare($con, "SELECT * FROM tbl_staff WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, 'i', $staffId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        mysqli_stmt_close($stmt);
        return null;
    }

    $staff = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $staff;
}

function staff_format_date($value, $format = 'd M Y')
{
    if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00' || $value === '00:00:00') {
        return '--';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '--';
    }

    return date($format, $timestamp);
}

function staff_format_money($value)
{
    return number_format((float) $value, 2);
}

function staff_fetch_customer_map($con)
{
    $customers = array();
    $result = mysqli_query($con, "SELECT ID, Name, Email, MobileNumber FROM tblcustomers");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $customers[(int) $row['ID']] = $row;
        }
    }
    return $customers;
}

function staff_fetch_service_map($con)
{
    $services = array();
    $result = mysqli_query($con, "SELECT ID, ServiceName, Cost, type FROM tblservices");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $services[(int) $row['ID']] = $row;
        }
    }
    return $services;
}

function staff_resolve_customer($rowValue, $customerMap)
{
    $customerId = (int) $rowValue;
    if ($customerId && isset($customerMap[$customerId])) {
        return $customerMap[$customerId];
    }

    return array(
        'Name' => $rowValue ?: 'Walk-in customer',
        'Email' => '',
        'MobileNumber' => '',
    );
}

function staff_service_names($serviceIds, $serviceMap)
{
    if (empty($serviceIds)) {
        return 'Not specified';
    }

    $names = array();
    $ids = array_filter(array_map('trim', explode(',', (string) $serviceIds)));

    foreach ($ids as $id) {
        $serviceId = (int) $id;
        if ($serviceId && isset($serviceMap[$serviceId])) {
            $names[] = $serviceMap[$serviceId]['ServiceName'];
        }
    }

    if (empty($names)) {
        return 'Not specified';
    }

    return implode(', ', $names);
}

function staff_get_appointment_status($status)
{
    $status = trim((string) $status);
    
    if ($status === '3') {
        return array('label' => 'Done', 'class' => 'is-info');
    } elseif ($status === '1' || $status === 'Accepted') {
        return array('label' => 'Accepted', 'class' => 'is-success');
    } elseif ($status === '2' || $status === 'Rejected') {
        return array('label' => 'Rejected', 'class' => 'is-danger');
    } else {
        return array('label' => 'Pending', 'class' => '');
    }
}

function staff_get_status_badge_class($status)
{
    $status = trim((string) $status);
    
    if ($status === '3') {
        return 'is-info';
    } elseif ($status === '1' || $status === 'Accepted') {
        return 'is-success';
    } elseif ($status === '2' || $status === 'Rejected') {
        return 'is-danger';
    }
    return '';
}

function staff_sanitize_input($input)
{
    if (is_array($input)) {
        return array_map('staff_sanitize_input', $input);
    }
    return trim(strip_tags((string) $input));
}

function staff_validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function staff_validate_phone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 9 && strlen($phone) <= 15;
}

function staff_validate_date($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function staff_validate_time($time)
{
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
}
