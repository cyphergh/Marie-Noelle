<?php
include_once __DIR__ . '/includes/auth.php';

if (staff_is_logged_in()) {
    header('location: /staff/dashboard.php');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $message = 'Please enter your email and password.';
        $messageType = 'error';
    } elseif (!staff_validate_email($email)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        $stmt = mysqli_prepare($con, "SELECT id, name, email FROM tbl_staff WHERE email = ? AND password = ? LIMIT 1");
        if ($stmt) {
            $hashedPassword = md5($password);
            mysqli_stmt_bind_param($stmt, 'ss', $email, $hashedPassword);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $staff = mysqli_fetch_assoc($result);
                $_SESSION['bpmsstid'] = (int) $staff['id'];
                
                session_regenerate_id(true);
                
                header('location:dashboard.php');
                exit;
            }

            mysqli_stmt_close($stmt);
        }

        $message = 'The email or password you entered is incorrect.';
        $messageType = 'error';
    }
}

$csrfToken = staff_generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Staff Portal Login - Marie Noelle Spa and Salon">
    <meta name="robots" content="noindex, nofollow">
    <title>Staff Login | Marie Noelle Spa and Salon</title>
    <link rel="icon" href="../panel/images/logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --login-bg: #f8f1e7;
            --login-surface: rgba(255, 255, 255, 0.82);
            --login-surface-strong: #ffffff;
            --login-text: #2e221c;
            --login-muted: #726155;
            --login-border: rgba(62, 45, 35, 0.12);
            --login-accent: #b18458;
            --login-accent-dark: #8a5a3a;
            --login-accent-soft: #f5e8da;
            --login-green: #2b5b55;
            --login-shadow: 0 24px 80px rgba(62, 45, 35, 0.12);
            --login-shadow-soft: 0 14px 40px rgba(62, 45, 35, 0.08);
            --login-radius-xl: 36px;
            --login-radius-lg: 28px;
            --login-radius-md: 20px;
            --login-radius-sm: 14px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", sans-serif;
            color: var(--login-text);
            background:
                radial-gradient(circle at 15% 10%, rgba(177, 132, 88, 0.2), transparent 25%),
                radial-gradient(circle at 85% 85%, rgba(43, 91, 85, 0.15), transparent 22%),
                radial-gradient(circle at 50% 50%, rgba(177, 132, 88, 0.08), transparent 40%),
                linear-gradient(160deg, #fcf7f2 0%, #f0e5d8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        ::selection {
            background: rgba(177, 132, 88, 0.25);
        }

        .login-container {
            width: 100%;
            max-width: 1100px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 32px;
            align-items: stretch;
        }

        .login-panel {
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--login-radius-xl);
            background: var(--login-surface);
            box-shadow: var(--login-shadow);
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .login-story {
            padding: 44px;
            background:
                linear-gradient(165deg, rgba(255, 255, 255, 0.92), rgba(251, 244, 236, 0.88)),
                var(--login-surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-story::before {
            content: "";
            position: absolute;
            top: -100px;
            right: -80px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(177, 132, 88, 0.15), rgba(177, 132, 88, 0));
            pointer-events: none;
        }

        .login-story::after {
            content: "";
            position: absolute;
            bottom: -60px;
            left: -40px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(43, 91, 85, 0.1), rgba(43, 91, 85, 0));
            pointer-events: none;
        }

        .login-brand {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            padding: 14px 20px;
            border-radius: 999px;
            background: rgba(177, 132, 88, 0.1);
            position: relative;
            z-index: 1;
        }

        .login-brand img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            border-radius: var(--login-radius-sm);
        }

        .login-brand-text span {
            display: block;
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--login-muted);
        }

        .login-brand-text strong {
            display: block;
            font-family: "Libre Baskerville", serif;
            font-size: 16px;
            margin-top: 2px;
        }

        .login-story h1 {
            margin: 0 0 18px;
            font-family: "Libre Baskerville", serif;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.15;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .login-story > p {
            color: var(--login-muted);
            font-size: 16px;
            line-height: 1.8;
            max-width: 480px;
            position: relative;
            z-index: 1;
        }

        .login-features {
            display: grid;
            gap: 14px;
            margin-top: 32px;
            position: relative;
            z-index: 1;
        }

        .login-feature {
            padding: 20px 22px;
            border-radius: var(--login-radius-md);
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(62, 45, 35, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-feature:hover {
            transform: translateX(6px);
            box-shadow: var(--login-shadow-soft);
        }

        .login-feature i {
            width: 40px;
            height: 40px;
            border-radius: var(--login-radius-sm);
            background: var(--login-accent-soft);
            color: var(--login-accent-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .login-feature strong {
            display: block;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .login-feature span {
            color: var(--login-muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .login-form {
            padding: 44px;
            background: var(--login-surface-strong);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-header {
            margin-bottom: 28px;
        }

        .login-form-header h2 {
            margin: 0 0 10px;
            font-family: "Libre Baskerville", serif;
            font-size: 1.8rem;
        }

        .login-form-header p {
            margin: 0;
            color: var(--login-muted);
            font-size: 14px;
        }

        .login-alert {
            padding: 14px 16px;
            border-radius: var(--login-radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-alert.error {
            background: rgba(166, 60, 60, 0.1);
            color: #a63c3c;
            border: 1px solid rgba(166, 60, 60, 0.15);
        }

        .login-alert.success {
            background: rgba(43, 91, 85, 0.1);
            color: var(--login-green);
            border: 1px solid rgba(43, 91, 85, 0.15);
        }

        .login-form .form-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--login-muted);
            margin-bottom: 8px;
        }

        .login-form .form-control {
            min-height: 54px;
            border: 1px solid var(--login-border);
            border-radius: var(--login-radius-sm);
            background: rgba(255, 255, 255, 0.92);
            padding: 14px 16px;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .login-form .form-control:focus {
            border-color: rgba(177, 132, 88, 0.5);
            box-shadow: 0 0 0 4px rgba(177, 132, 88, 0.1);
            outline: none;
        }

        .login-form .form-control::placeholder {
            color: var(--login-muted);
            opacity: 0.6;
        }

        .login-btn {
            width: 100%;
            min-height: 56px;
            border: none;
            border-radius: var(--login-radius-sm);
            background: linear-gradient(135deg, var(--login-accent), var(--login-accent-dark));
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 16px 32px rgba(177, 132, 88, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(177, 132, 88, 0.3);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }

        .login-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--login-border);
        }

        .login-links a {
            color: var(--login-green);
            font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .login-links a:hover {
            color: var(--login-accent-dark);
        }

        .login-links a i {
            margin-right: 6px;
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            color: var(--login-muted);
            font-size: 12px;
        }

        @media (max-width: 960px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 540px;
            }

            .login-story {
                padding: 36px;
            }

            .login-form {
                padding: 36px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 16px;
            }

            .login-story,
            .login-form {
                padding: 28px;
            }

            .login-story h1 {
                font-size: 1.8rem;
            }

            .login-brand {
                padding: 12px 16px;
            }

            .login-brand img {
                width: 48px;
                height: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <section class="login-panel login-story">
            <div class="login-brand">
                <img src="../panel/images/logo.png" alt="Marie Noelle Spa and Salon">
                <div class="login-brand-text">
                    <span>Staff Portal</span>
                    <strong>Marie Noelle Spa and Salon</strong>
                </div>
            </div>

            <h1>Your workspace, designed for focus and efficiency.</h1>
            <p>Sign in to manage appointments, track payments linked to your services, and stay on top of your work schedule from one elegant dashboard.</p>

            <div class="login-features">
                <div class="login-feature">
                    <i class="fa fa-calendar-check-o"></i>
                    <strong>Appointment Management</strong>
                    <span>Review bookings, create walk-in visits, and manage your daily schedule with ease.</span>
                </div>
                <div class="login-feature">
                    <i class="fa fa-mobile-alt"></i>
                    <strong>Payment Tracking</strong>
                    <span>Monitor invoice lines tied to your services and track payment status.</span>
                </div>
                <div class="login-feature">
                    <i class="fa fa-clock"></i>
                    <strong>Schedule Access</strong>
                    <span>Check shifts, working hours, and upcoming assignments anytime from any device.</span>
                </div>
            </div>
        </section>

        <section class="login-panel login-form">
            <div class="login-form-header">
                <h2>Staff Login</h2>
                <p>Enter your credentials to access the staff portal.</p>
            </div>

            <?php if ($message): ?>
                <div class="login-alert <?php echo staff_escape($messageType); ?>">
                    <i class="fa <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo staff_escape($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo staff_escape($csrfToken); ?>">
                
                <div class="mb-4">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email" 
                           value="<?php echo staff_escape($_POST['email'] ?? ''); ?>"
                           required 
                           autocomplete="email">
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" 
                           required 
                           autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn">
                    <span>Sign In to Portal</span>
                    <i class="fa fa-arrow-right"></i>
                </button>

                <div class="login-links">
                    <a href="../index.php">
                        <i class="fa fa-home"></i>
                        Back to Landing
                    </a>
                    <a href="../panel/">
                        <i class="fa fa-key"></i>
                        Admin Login
                    </a>
                </div>
            </form>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> Marie Noelle Spa and Salon. All rights reserved.
            </div>
        </section>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var email = document.getElementById('email').value.trim();
            var password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Please enter both email and password.');
                return false;
            }

            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }

            return true;
        });
    </script>
</body>
</html>
