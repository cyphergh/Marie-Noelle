<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (isset($_POST['submit'])) {
    
    $type = 1;
    $uid = $_POST['Userid'];
    $invoiceid = mt_rand(100000000, 999999999);
    $sidArr = $_POST['ServiceId'];
    $qtyArr = $_POST['qty'];
    $tax = $_POST['tax'];
    $totalArr = $_POST['total'];
    $payment = $_POST['payment_method'];
// print_r($totalArr); exit;
    foreach ($sidArr as $index => $serviceId) {
        // Skip empty service IDs (common if the user didn't select the last row)
        if (!empty($serviceId)) {
            $qty = $qtyArr[$index] ?? 0;
            // $total = $totalArr[$index] ?? 0;
// print_r($total); exit; 
            $ret = mysqli_query($con, "INSERT INTO tblinvoice(Userid, ServiceId, BillingId, tax, qty, total, payment_method, type) 
                VALUES('$uid', '$serviceId', '$invoiceid', '$tax', '$qty', '$totalArr', '$payment', '$type')");
                
                
             $res_stock = mysqli_query($con, "SELECT opening_stock FROM tblservices WHERE ID = '$serviceId'");
        $row_stock = mysqli_fetch_assoc($res_stock);
        $current_stock = $row_stock['opening_stock'];

        // Calculate new stock
        $new_stock = $current_stock - $qty;
        if ($new_stock < 0) $new_stock = 0; // prevent negative stock

        // Update stock in tblservices
        mysqli_query($con, "UPDATE tblservices SET opening_stock = '$new_stock' WHERE ID = '$serviceId'");    
                
        }
    }

    echo '<script>alert("Invoice created successfully. Invoice number is ' . $invoiceid . '")</script>';
    
    
    
    echo "<script>window.location.href ='order.php'</script>";
}


?>