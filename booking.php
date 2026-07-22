<?php
session_start();
include('panel/includes/dbconnection.php');
include('panel/includes/audit_helper.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $AptNumber = mt_rand(100000000, 999999999);
    $Name = mysqli_real_escape_string($con, $_POST['name']);
    $Email = mysqli_real_escape_string($con, $_POST['email']);
    $PhoneNumber = mysqli_real_escape_string($con, $_POST['phone']);
    $AptDate = mysqli_real_escape_string($con, $_POST['apt_date']);
    $AptTime = mysqli_real_escape_string($con, $_POST['apt_time']);
    $total = mysqli_real_escape_string($con, $_POST['total']);
    $grand_total = mysqli_real_escape_string($con, $_POST['grand_total']);
    $status = '1';
    $Services = isset($_POST['serv_id']) ? implode(",", $_POST['serv_id']) : '';
    $remark = "Booked";
    $date = date('d-m-Y H:i:s');

    $sql = "INSERT INTO tblappointment (AptNumber, Status, Name, Email, PhoneNumber, AptDate, AptTime, Remark, RemarkDate, Services, total, grand_total) 
            VALUES ('$AptNumber','$status', '$Name', '$Email', '$PhoneNumber', '$AptDate', '$AptTime','$remark','$date', '$Services','$total','$grand_total')";

    if (mysqli_query($con, $sql)) {
        $newId = mysqli_insert_id($con);
        
        log_audit_action($con, [
            'user_type' => 'system',
            'user_id' => null,
            'user_name' => 'Customer: ' . $Name,
            'action' => 'create',
            'entity_type' => 'booking',
            'entity_id' => $newId,
            'new_values' => [
                'apt_number' => $AptNumber,
                'customer_name' => $Name,
                'email' => $Email,
                'phone' => $PhoneNumber,
                'apt_date' => $AptDate,
                'apt_time' => $AptTime,
                'services' => $Services,
                'total' => $total,
                'grand_total' => $grand_total,
            ],
            'description' => "New booking #{$AptNumber} created by customer {$Name} for {$AptDate}"
        ]);
        
        include_once 'panel/includes/sms_helper.php';
        send_sms($PhoneNumber);
        
        echo "success";
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
?>