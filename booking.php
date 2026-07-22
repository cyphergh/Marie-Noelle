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
    $discount_type = isset($_POST['discount_type']) && !empty($_POST['discount_type']) ? "'" . mysqli_real_escape_string($con, $_POST['discount_type']) . "'" : 'NULL';
    $discount_value = isset($_POST['discount_value']) && (float)$_POST['discount_value'] > 0 ? "'" . (float)$_POST['discount_value'] . "'" : 'NULL';
    $discount_amount_val = 0;
    if ($discount_type !== 'NULL') {
        $dt = trim($_POST['discount_type'], "'");
        $dv = (float)$_POST['discount_value'];
        $subtotal = (float)$_POST['total'];
        $tax_percent_booking = 0;
        $ret_tax = mysqli_query($con, "select * from tbl_tax");
        while ($row_tax = mysqli_fetch_array($ret_tax)) { $tax_percent_booking += (float)$row_tax['value']; }
        $pre_disc = $subtotal + ($subtotal * $tax_percent_booking / 100);
        $discount_amount_val = ($dt === 'percentage') ? $pre_disc * min($dv, 100) / 100 : min($dv, $pre_disc);
    }
    $discount_amount = $discount_amount_val > 0 ? "'" . number_format($discount_amount_val, 2, '.', '') . "'" : 'NULL';

    do { $ereceiptToken = (string) mt_rand(1000000000, 9999999999); } while (mysqli_query($con, "SELECT 1 FROM tblappointment WHERE ereceipt_token = '{$ereceiptToken}'")->num_rows > 0);

    $sql = "INSERT INTO tblappointment (AptNumber, Status, Name, Email, PhoneNumber, AptDate, AptTime, Remark, RemarkDate, Services, total, grand_total, discount_type, discount_value, discount_amount, ereceipt_token) 
            VALUES ('$AptNumber','$status', '$Name', '$Email', '$PhoneNumber', '$AptDate', '$AptTime','$remark','$date', '$Services','$total','$grand_total', $discount_type, $discount_value, $discount_amount, '$ereceiptToken')";

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
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $receiptLink = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/ereceipt.php?token=' . urlencode($ereceiptToken);
        send_sms($PhoneNumber, 'View your e-receipt: ' . $receiptLink);
        
        echo "success";
    } else {
        echo "Error: " . mysqli_error($con);
    }
}
?>