<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$staff = staff_fetch_current($con);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = (int) $staff['id'];
    $name = mysqli_real_escape_string($con, trim($_POST['name'] ?? ''));
    $email = mysqli_real_escape_string($con, trim($_POST['email'] ?? ''));
    $contact = mysqli_real_escape_string($con, trim($_POST['contact'] ?? ''));
    $address = mysqli_real_escape_string($con, trim($_POST['address'] ?? ''));

    if (empty($name) || empty($email) || empty($contact) || empty($address)) {
        $message = 'Please complete all profile fields.';
        $messageType = 'error';
    } elseif (!staff_validate_email($email)) {
        $message = 'Please provide a valid email address.';
        $messageType = 'error';
    } else {
        $stmt = mysqli_prepare($con, "SELECT id FROM tbl_staff WHERE email = ? AND id != ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $email, $staffId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $message = 'That email address is already used by another staff account.';
                $messageType = 'error';
            } else {
                mysqli_stmt_close($stmt);
                
                $updateStmt = mysqli_prepare($con, "UPDATE tbl_staff SET name = ?, email = ?, contact = ?, address = ? WHERE id = ?");
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, 'ssssi', $name, $email, $contact, $address, $staffId);
                    $updated = mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                    
                    if ($updated) {
                        $message = 'Your profile has been updated successfully.';
                        $messageType = 'success';
                        $staff = staff_fetch_current($con);
                    } else {
                        $message = 'Unable to update your profile right now.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Database error. Please try again.';
                    $messageType = 'error';
                }
            }
        }
    }
}

staff_layout_start('My Profile', 'profile', 'Manage your personal information');
?>
<section class="staff-form-card">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h2>Update Your Profile</h2>
            <p class="staff-muted mb-0">Keep your personal information up to date.</p>
        </div>
        <span class="staff-badge">
            <i class="fa fa-user"></i>
            Personal Details
        </span>
    </div>

    <?php if ($message): ?>
        <div class="staff-note <?php echo $messageType === 'success' ? '' : 'is-danger'; ?>" style="<?php echo $messageType === 'error' ? 'background: var(--staff-danger-soft); color: var(--staff-danger);' : 'background: var(--staff-green-soft); color: var(--staff-green);'; ?>">
            <i class="fa <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo staff_escape($message); ?>
        </div>
    <?php endif; ?>

    <form method="post" class="row g-4 mt-2">
        <div class="col-md-6">
            <label class="form-label">Full Name *</label>
            <input type="text" class="form-control" name="name" value="<?php echo staff_escape($staff['name'] ?? ''); ?>" required placeholder="Enter your full name">
        </div>
        <div class="col-md-6">
            <label class="form-label">Email Address *</label>
            <input type="email" class="form-control" name="email" value="<?php echo staff_escape($staff['email'] ?? ''); ?>" required placeholder="Enter your email">
        </div>
        <div class="col-md-6">
            <label class="form-label">Contact Number *</label>
            <input type="tel" class="form-control" name="contact" value="<?php echo staff_escape($staff['contact'] ?? ''); ?>" required placeholder="Enter contact number">
        </div>
        <div class="col-md-6">
            <label class="form-label">Address *</label>
            <input type="text" class="form-control" name="address" value="<?php echo staff_escape($staff['address'] ?? ''); ?>" required placeholder="Enter your address">
        </div>
        
        <?php if (!empty($staff['StaffId'])): ?>
        <div class="col-12">
            <div class="staff-card" style="background: var(--staff-green-soft); padding: 18px;">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa fa-id-badge" style="font-size: 24px; color: var(--staff-green);"></i>
                    <div>
                        <div class="staff-stat-label" style="margin-bottom: 4px;">Staff ID</div>
                        <strong style="font-size: 18px; color: var(--staff-green);"><?php echo staff_escape($staff['StaffId']); ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-12">
            <hr style="border-color: var(--staff-border); margin: 8px 0 20px;">
            <button type="submit" class="staff-button staff-button-primary">
                <i class="fa fa-save"></i>
                Save Changes
            </button>
        </div>
    </form>
</section>

<section class="staff-table-card mt-4">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h2>Account Information</h2>
            <p class="staff-muted mb-0">Basic details about your staff account.</p>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="staff-card" style="padding: 20px;">
                <div class="staff-stat-label">Account Status</div>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <span class="staff-badge is-success">
                        <i class="fa fa-check-circle"></i>
                        Active
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="staff-card" style="padding: 20px;">
                <div class="staff-stat-label">Last Login</div>
                <p class="staff-stat-value" style="font-size: 1.2rem; margin-top: 8px;">
                    <?php echo !empty($staff['LastLogin']) ? staff_format_date($staff['LastLogin'], 'd M Y, g:i A') : 'First login'; ?>
                </p>
            </div>
        </div>
    </div>
</section>
<?php staff_layout_end(); ?>
