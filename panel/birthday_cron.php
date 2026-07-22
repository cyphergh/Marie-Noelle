<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require 'includes/dbconnection.php'; // DB connection

// Get tomorrow's date in MM-DD format
$tomorrow = date('m-d', strtotime('+1 day'));

// Query customers with birthday tomorrow
$query = "SELECT * FROM tblcustomers WHERE DATE_FORMAT(dob, '%m-%d') = '$tomorrow'";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_assoc($result)) {
    $email = $row['Email'];
    $name = $row['Name'];
    $dob = date('d M', strtotime($row['dob']));

    // Email subject and body
    $subject = "🎉 Early Birthday Wishes & Special Treat Just for You!";
    $message = "
        <p>Dear <strong>$name</strong>,</p>
        <p>We noticed your birthday is on <strong>$dob</strong>, and we just couldn't wait to send you our warmest wishes!</p>
        <p><strong>Happy Birthday in advance!</strong> 🎂🥳</p>

        <hr>
        <p><strong>🎁 As a special treat, we'd love to pamper you!</strong></p>
        <p>Contact us for personalized services curated just for your special day.</p>
        <p>Let us make your birthday memorable with a relaxing and luxurious experience at our salon!</p>
        <hr>

        <p>Warm regards,<br>Solon Shop</p>
    ";

    // Fetch SMTP config
    $res_email = mysqli_query($con, "SELECT * FROM emailsetting LIMIT 1");
    $email_config = mysqli_fetch_assoc($res_email);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $email_config['smtp_server'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_config['smtp_username'];
        $mail->Password   = $email_config['smtp_password'];
        $mail->SMTPSecure = $email_config['smtp_type']; // e.g., tls
        $mail->Port       = $email_config['stmp_port'];

        $mail->setFrom($email_config['smtp_username'], 'Salon Shop');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        echo "Birthday reminder sent to $email<br>";
    } catch (Exception $e) {
        echo "Email to $email failed. Error: {$mail->ErrorInfo}<br>";
    }
}
?>
