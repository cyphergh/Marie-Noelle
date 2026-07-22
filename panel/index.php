<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (isset($_POST['login'])) {
    $adminuser = $_POST['username'];
    $password = md5($_POST['password']);
    $query = mysqli_query($con, "SELECT ID FROM tbladmin WHERE UserName='$adminuser' && Password='$password'");
    $ret = mysqli_fetch_array($query);
    if ($ret > 0) {
        $_SESSION['bpmsaid'] = $ret['ID'];
        echo "<script>document.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Invalid Details');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Marie Noelle Spa and Salon | Login</title>
<link rel="icon" href="images/logo.png" type="image/x-icon">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;700&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="css/font-awesome.css">

<!-- Bootstrap -->
<link href="css/bootstrap.css" rel="stylesheet">

<!-- Custom CSS -->
<link href="css/style.css" rel="stylesheet">
<link href="css/custom.css" rel="stylesheet">

<style>
.login-shell {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    background:
        radial-gradient(circle at top left, rgba(177, 106, 63, 0.28), transparent 26%),
        radial-gradient(circle at bottom right, rgba(31, 107, 99, 0.18), transparent 24%),
        linear-gradient(135deg, #f4ede5 0%, #efe5d9 100%);
}

.login-story {
    padding: 72px 64px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    color: #2b241d;
}

.login-brand {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    padding: 10px 16px;
    border: 1px solid rgba(61, 48, 33, 0.10);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.6);
    width: fit-content;
}

.login-brand img {
    width: 42px;
    height: 42px;
    object-fit: contain;
}

.login-brand span {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: #766a5f;
}

.login-brand strong {
    display: block;
    font-size: 18px;
}

.login-copy h1 {
    max-width: 520px;
    margin: 0 0 18px;
    font-size: 56px;
    line-height: 1.05;
    font-weight: 800;
    letter-spacing: -0.04em;
}

.login-copy p {
    max-width: 520px;
    font-size: 18px;
    line-height: 1.7;
    color: #6d6359;
}

.login-points {
    display: grid;
    gap: 16px;
    margin-top: 32px;
}

.login-point {
    padding: 18px 20px;
    border: 1px solid rgba(61, 48, 33, 0.10);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.52);
    backdrop-filter: blur(8px);
}

.login-point strong {
    display: block;
    margin-bottom: 6px;
    font-size: 16px;
}

.login-panel {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 36px;
}

.login-card {
    width: 100%;
    max-width: 460px;
    padding: 40px 36px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 32px;
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(18px);
    box-shadow: 0 30px 80px rgba(61, 48, 33, 0.14);
}

.login-card-top {
    margin-bottom: 26px;
    text-align: left;
}

.login-card-top h4 {
    margin: 0 0 8px;
    font-size: 30px;
    font-weight: 800;
    color: #2b241d;
}

.login-card-top p {
    color: #766a5f;
    font-size: 15px;
}

.login-form label {
    display: block;
    margin-bottom: 8px;
    color: #2b241d;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.login-form .form-control {
    height: 52px;
    margin-bottom: 18px;
    border: 1px solid rgba(61, 48, 33, 0.14);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: none;
}

.login-form .form-control:focus {
    border-color: rgba(177, 106, 63, 0.5);
    box-shadow: 0 0 0 4px rgba(177, 106, 63, 0.12);
}

.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 18px;
    top: 17px;
    cursor: pointer;
    color: #8c7d71;
}

.btn-login {
    width: 100%;
    height: 54px;
    border: none;
    border-radius: 16px;
    background: linear-gradient(135deg, #b16a3f, #8a4f2b);
    color: #fff;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.03em;
    box-shadow: 0 16px 34px rgba(177, 106, 63, 0.24);
}

.btn-login:hover {
    background: linear-gradient(135deg, #c17649, #8a4f2b);
}

.btn-staff-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 52px;
    margin-top: 12px;
    border: 1px solid rgba(43, 91, 85, 0.18);
    border-radius: 16px;
    background: rgba(43, 91, 85, 0.08);
    color: #2b5b55;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    text-decoration: none;
}

.btn-staff-login:hover {
    color: #214843;
    text-decoration: none;
    background: rgba(43, 91, 85, 0.14);
}

.links {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 18px;
    font-size: 14px;
}

.links a {
    color: #8a4f2b;
    text-decoration: none;
}

.links a:hover {
    text-decoration: underline;
}

@media (max-width: 991px) {
    .login-shell {
        grid-template-columns: 1fr;
    }

    .login-story {
        padding: 36px 24px 12px;
    }

    .login-copy h1 {
        font-size: 40px;
    }

    .login-panel {
        padding: 24px;
    }
}

@media (max-width: 576px) {
    .login-card {
        padding: 28px 22px;
        border-radius: 24px;
    }

    .links {
        flex-direction: column;
    }
}
</style>
</head>
<body class="login">
<div class="login-shell">
    <section class="login-story">
        <div class="login-brand">
            <img src="images/logo.png" alt="Marie Noelle Spa and Salon logo">
            <div>
                <span>Marie Noelle Spa and Salon</span>
                <strong>Admin Panel</strong>
            </div>
        </div>

        <div class="login-copy">
            <h1>Run bookings, staff, billing, and reports from one calm workspace.</h1>
            <p>The new interface keeps the same backend functionality, but gives your team a cleaner place to manage appointments and day-to-day salon operations.</p>
            <div class="login-points">
                <div class="login-point">
                    <strong>Operations at a glance</strong>
                    <span>Track appointments, customers, sales, and schedules with less visual clutter.</span>
                </div>
                <div class="login-point">
                    <strong>Built for fast admin work</strong>
                    <span>Cleaner forms, stronger contrast, and easier table scanning across the dashboard.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="login-panel">
        <div class="login-card">
            <div class="login-card-top">
                <h4>Welcome back</h4>
                <p>Sign in to continue to the Marie Noelle Spa and Salon administration area.</p>
            </div>
            <form role="form" method="post" action="" class="login-form">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>

                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" class="form-control" name="password" placeholder="Enter your password" id="password" required>
                    <i class="fa fa-eye toggle-password"></i>
                </div>

                <input type="submit" name="login" value="Sign In" class="btn-login">
            </form>
            <a href="../staff/" class="btn-staff-login">Staff Login</a>
            <div class="links">
                <a href="forgot-password.php">Forgot password?</a>
                <a href="../index.php">Back to Home</a>
            </div>
        </div>
    </section>
</div>

<script src="js/jquery-1.11.1.min.js"></script>
<script>
$(document).ready(function(){
    $(".toggle-password").click(function(){
        let input = $("#password");
        let icon = $(this);
        if(input.attr("type") === "password"){
            input.attr("type","text");
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
        }else{
            input.attr("type","password");
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
        }
    });
});
</script>
</body>
</html>
