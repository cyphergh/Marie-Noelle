<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$message = '';
$messageType = 'success';
$staffId = staff_current_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['currentpassword'] ?? '';
    $newPasswordRaw = $_POST['newpassword'] ?? '';
    $confirmPasswordRaw = $_POST['confirmpassword'] ?? '';

    if (empty($currentPassword) || empty($newPasswordRaw) || empty($confirmPasswordRaw)) {
        $message = 'Please complete all password fields.';
        $messageType = 'error';
    } elseif ($newPasswordRaw !== $confirmPasswordRaw) {
        $message = 'New password and confirmation do not match.';
        $messageType = 'error';
    } elseif (strlen($newPasswordRaw) < 8) {
        $message = 'New password must be at least 8 characters long.';
        $messageType = 'error';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $newPasswordRaw)) {
        $message = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
        $messageType = 'error';
    } else {
        $stmt = mysqli_prepare($con, "SELECT id FROM tbl_staff WHERE id = ? AND password = ? LIMIT 1");
        if ($stmt) {
            $hashedCurrent = md5($currentPassword);
            mysqli_stmt_bind_param($stmt, 'is', $staffId, $hashedCurrent);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                mysqli_stmt_close($stmt);
                
                $newHashedPassword = md5($newPasswordRaw);
                $updateStmt = mysqli_prepare($con, "UPDATE tbl_staff SET password = ? WHERE id = ?");
                
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, 'si', $newHashedPassword, $staffId);
                    $updated = mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                    
                    if ($updated) {
                        $message = 'Your password has been changed successfully. Please use your new password next time you log in.';
                        $messageType = 'success';
                    } else {
                        $message = 'Unable to change your password right now. Please try again.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Database error. Please try again later.';
                    $messageType = 'error';
                }
            } else {
                $message = 'Your current password is incorrect.';
                $messageType = 'error';
            }
        } else {
            $message = 'Database error. Please try again.';
            $messageType = 'error';
        }
    }
}

staff_layout_start('Security', 'password', 'Manage your account security');
?>
<section class="staff-form-card">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h2>Change Password</h2>
            <p class="staff-muted mb-0">Update your password to keep your account secure.</p>
        </div>
        <span class="staff-badge">
            <i class="fa fa-shield-halved"></i>
            Security
        </span>
    </div>

    <?php if ($message): ?>
        <div class="staff-note <?php echo $messageType === 'success' ? '' : 'is-danger'; ?>" style="<?php echo $messageType === 'error' ? 'background: var(--staff-danger-soft); color: var(--staff-danger);' : 'background: var(--staff-green-soft); color: var(--staff-green);'; ?>">
            <i class="fa <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo staff_escape($message); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="row g-4 mt-2" id="passwordForm">
        <div class="col-md-12">
            <label class="form-label">Current Password *</label>
            <div class="position-relative">
                <input type="password" class="form-control" name="currentpassword" id="currentPassword" required placeholder="Enter current password" autocomplete="current-password">
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-3" onclick="togglePasswordVisibility('currentPassword')" style="color: var(--staff-muted);">
                    <i class="fa fa-eye" id="currentPasswordIcon"></i>
                </button>
            </div>
        </div>
        
        <div class="col-md-6">
            <label class="form-label">New Password *</label>
            <div class="position-relative">
                <input type="password" class="form-control" name="newpassword" id="newPassword" required placeholder="Enter new password" autocomplete="new-password">
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-3" onclick="togglePasswordVisibility('newPassword')" style="color: var(--staff-muted);">
                    <i class="fa fa-eye" id="newPasswordIcon"></i>
                </button>
            </div>
            <small class="staff-muted mt-2 d-block">Must be at least 8 characters with uppercase, lowercase, and number.</small>
        </div>
        
        <div class="col-md-6">
            <label class="form-label">Confirm New Password *</label>
            <div class="position-relative">
                <input type="password" class="form-control" name="confirmpassword" id="confirmPassword" required placeholder="Confirm new password" autocomplete="new-password">
                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-3" onclick="togglePasswordVisibility('confirmPassword')" style="color: var(--staff-muted);">
                    <i class="fa fa-eye" id="confirmPasswordIcon"></i>
                </button>
            </div>
        </div>

        <div class="col-12">
            <div class="staff-card" style="background: var(--staff-warning-soft); padding: 18px;">
                <div class="d-flex align-items-start gap-3">
                    <i class="fa fa-info-circle" style="font-size: 20px; color: var(--staff-accent-dark); margin-top: 2px;"></i>
                    <div>
                        <strong style="display: block; margin-bottom: 4px; color: var(--staff-accent-dark);">Password Tips</strong>
                        <span style="font-size: 13px; color: var(--staff-accent-dark);">
                            Use a strong password with a mix of letters, numbers, and symbols. Avoid using the same password across multiple sites.
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr style="border-color: var(--staff-border); margin: 8px 0 20px;">
            <button type="submit" class="staff-button staff-button-primary" id="submitBtn">
                <i class="fa fa-lock"></i>
                Update Password
            </button>
        </div>
    </form>
</section>

<script>
    function togglePasswordVisibility(inputId) {
        var input = document.getElementById(inputId);
        var icon = document.getElementById(inputId + 'Icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fa fa-eye';
        }
    }

    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        var newPassword = document.getElementById('newPassword').value;
        var confirmPassword = document.getElementById('confirmPassword').value;
        var submitBtn = document.getElementById('submitBtn');
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New password and confirmation do not match.');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('New password must be at least 8 characters long.');
            return false;
        }
        
        var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/;
        if (!passwordRegex.test(newPassword)) {
            e.preventDefault();
            alert('Password must contain at least one uppercase letter, one lowercase letter, and one number.');
            return false;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
        
        return true;
    });
</script>
<?php staff_layout_end(); ?>
