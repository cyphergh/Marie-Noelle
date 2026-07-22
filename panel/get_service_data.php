<?php
  include('includes/dbconnection.php');

if (isset($_POST['service_id'])) {
    $id = intval($_POST['service_id']);
    $query = mysqli_query($con, "SELECT opening_stock, Cost as price FROM tblservices WHERE ID = $id");
    $row = mysqli_fetch_assoc($query);
    echo json_encode($row);
}
?>
