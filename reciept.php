<?php
include('panel/includes/dbconnection.php');

if (!isset($_GET['payment_id'])) {
    echo "Invalid request. No ID provided.";
    exit;
}

$date = date('d-m-Y');
$payment_id = $_GET['payment_id'];

// Fetch appointment record using MySQLi
$query = "SELECT * FROM `tblappointment` WHERE payment_id = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "s", $payment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);

if (!$record) {
    echo "No record found for the provided ID.";
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
</head>
<style>
    body {
        width: 60%;
        margin: 0 auto;
        padding: 50px;
        margin-top: 20px;
    }
    table {
        border: 1px solid black;
        width: 100%;
        line-height: inherit;
        text-align: left;
        border-collapse: collapse;
        padding: 20px;
    }
    table, td, th {
        padding: 20px;
    }
</style>
<body>
    <div style="padding:20px;">
        <table>
            <tbody>
                <tr>
                    <td colspan="5">
                        <h2 style="margin-right:49px; text-align:center;">Payment Receipt</h2>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;">
                        <p style="margin-right:49px;"><b>Date:</b> &nbsp; <?php echo $date; ?></p>
                        <p style="margin-right:49px;"><b>Amount:</b> &nbsp; <?php echo htmlspecialchars($record['grand_total']); ?></p>
                    </td>
                </tr>
                <tr>
                    <td><b>Name:</b>&nbsp;&nbsp;&nbsp; <?php echo htmlspecialchars($record['Name']); ?></td>
                    <td><b>Email:</b>&nbsp;&nbsp;&nbsp; <?php echo htmlspecialchars($record['Email']); ?></td>
                    <td><b>Phone:</b>&nbsp;&nbsp;&nbsp; <?php echo htmlspecialchars($record['PhoneNumber']); ?></td>
                </tr>
                <tr>
                    <td style="padding-top:30px; padding-bottom:30px;"><b>Received By:</b>&nbsp; Salon Appointment</td>
                </tr>
            </tbody>
        </table>
    </div>
    <a href="index.php">Go Back</a>
    <button onclick="window.print()">Print this page</button>
</body>
</html>
