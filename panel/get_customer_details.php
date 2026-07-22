<?php
include('includes/dbconnection.php');

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $query = mysqli_query($con, "SELECT Email, MobileNumber FROM tblcustomers WHERE ID = '$id'");
    $data = mysqli_fetch_assoc($query);

    echo json_encode([
        'email' => $data['Email'],
        'phone' => $data['MobileNumber']
    ]);
}
?>
