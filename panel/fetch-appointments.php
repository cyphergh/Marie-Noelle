<?php
include('includes/dbconnection.php');

$query = mysqli_query($con, "SELECT * FROM tblappointment WHERE Status='1'");
$events = [];

while ($row = mysqli_fetch_assoc($query)) {
    // Get service IDs like "3,4,6"
    $service_ids = explode(',', $row['Services']);
    $service_names = [];

    foreach ($service_ids as $sid) {
        $sid = intval($sid);
        $squery = mysqli_query($con, "SELECT ServiceName FROM tblservices WHERE ID = $sid");
        if ($srow = mysqli_fetch_assoc($squery)) {
            $service_names[] = $srow['ServiceName'];
        }
    }

    $events[] = [
        'title' => $row['Name'] . ' - ' . implode(', ', $service_names),
        'start' => $row['AptDate'] . 'T' . $row['AptTime'],
        'id'    => $row['ID'],
        'description' => implode(', ', $service_names), // optional
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
