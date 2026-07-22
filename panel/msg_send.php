<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

include('includes/dbconnection.php');

// Get tomorrow's date
$tomorrow = date('Y-m-d', strtotime('+1 day'));


$query = "SELECT * FROM tblappointment WHERE Status = 1 AND AptDate = '$tomorrow'";
$result = mysqli_query($con, $query);



// Get email SMTP settings once
$res_email = mysqli_query($con, "SELECT * FROM emailsetting LIMIT 1");
$email_settings = mysqli_fetch_assoc($res_email);

$smtp_server   = $email_settings['smtp_server'];
$smtp_password = $email_settings['smtp_password'];
$smtp_enc      = $email_settings['smtp_type'];
$smtp_username = $email_settings['smtp_username'];
$smtp_port     = $email_settings['stmp_port'];



while ($row = mysqli_fetch_assoc($result)) {
    $email    = $row['Email'];
    $name     = $row['Name'];
    $aptDate  = date('d-M-Y', strtotime($row['AptDate']));
    $aptTime  = date('h:i A', strtotime($row['AptTime']));
    $book_id  = $row['AptNumber'];
// print_r($email);exit;

$query4 = "SELECT * FROM tblcustomers WHERE ID = '$name'";
$result4 = mysqli_query($con, $query4);
$row4 = mysqli_fetch_assoc($result4);

$cust_name = $row4['Name'];
// print_r($row4['Name']); exit;

    // Email message content
    $message = "
        Dear $cust_name,<br><br>
        This is a reminder that your appointment (Booking ID: <strong>$book_id</strong>) is scheduled for <strong>$aptDate</strong> at <strong>$aptTime</strong>.<br><br>
        Please be on time.<br><br>
        Regards,<br>
        Salon Shop
    ";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp_server;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = $smtp_enc;
        $mail->Port       = $smtp_port;

        $mail->setFrom($smtp_username, 'Salon Shop');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Appointment Reminder - $aptDate at $aptTime";
        $mail->Body    = $message;

        $mail->send();
        // Optionally log or update tblappointment to indicate reminder sent
    } catch (Exception $e) {
        // Log the error if needed
        error_log("Email failed to {$email}. Error: {$mail->ErrorInfo}");
    }
}
?>
