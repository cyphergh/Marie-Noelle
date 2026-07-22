<?php
include_once __DIR__ . '/includes/layout.php';
include_once __DIR__ . '/../panel/includes/audit_helper.php';
staff_require_login();

$staff = staff_fetch_current($con);
$staffId = (int) $staff['id'];
$staffName = $staff['name'] ?? 'Unknown Staff';
$customerMap = staff_fetch_customer_map($con);
$serviceMap = staff_fetch_service_map($con);

$colCheck = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tblappointment' AND COLUMN_NAME = 'staff_id' AND TABLE_SCHEMA = DATABASE()");
if (mysqli_num_rows($colCheck) == 0) {
    mysqli_query($con, "ALTER TABLE tblappointment ADD COLUMN staff_id INT(11) DEFAULT NULL AFTER Services");
}
ensure_audit_table($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && staff_is_ajax_request()) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

    if ($action === 'claim_booking') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            staff_json_response(false, 'Invalid booking ID.');
        }

        $check = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = $id AND (Status = '' OR Status IS NULL OR Status = '0')");
        if (!$check || mysqli_num_rows($check) === 0) {
            staff_json_response(false, 'Booking not found or already processed.');
        }
        
        $oldRow = mysqli_fetch_assoc($check);

        $result = mysqli_query($con, "UPDATE tblappointment SET Status = '1', staff_id = '{$staffId}' WHERE ID = $id");
        if ($result) {
            log_audit_action($con, [
                'user_type' => 'staff',
                'user_id' => $staffId,
                'user_name' => $staffName,
                'action' => 'update',
                'entity_type' => 'booking',
                'entity_id' => $id,
                'old_values' => $oldRow,
                'new_values' => ['Status' => '1', 'staff_id' => $staffId],
                'description' => "Staff {$staffName} claimed booking #{$oldRow['AptNumber']}"
            ]);
            staff_json_response(true, 'Booking claimed successfully!');
        } else {
            staff_json_response(false, 'Failed to claim booking.');
        }
    }

    if ($action === 'accept_booking') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            staff_json_response(false, 'Invalid booking ID.');
        }

        $check = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = $id AND (Status = '' OR Status IS NULL OR Status = '0')");
        if (!$check || mysqli_num_rows($check) === 0) {
            staff_json_response(false, 'Booking not found or already processed.');
        }
        
        $oldRow = mysqli_fetch_assoc($check);

        $result = mysqli_query($con, "UPDATE tblappointment SET Status = '1', staff_id = '{$staffId}' WHERE ID = $id");
        if ($result) {
            log_audit_action($con, [
                'user_type' => 'staff',
                'user_id' => $staffId,
                'user_name' => $staffName,
                'action' => 'update',
                'entity_type' => 'booking',
                'entity_id' => $id,
                'old_values' => $oldRow,
                'new_values' => ['Status' => '1', 'staff_id' => $staffId],
                'description' => "Staff {$staffName} accepted booking #{$oldRow['AptNumber']}"
            ]);
            staff_json_response(true, 'Booking accepted and claimed successfully!');
        } else {
            staff_json_response(false, 'Failed to accept booking.');
        }
    }

    if ($action === 'reject_booking') {
        $id = (int) ($_POST['id'] ?? 0);
        $reason = mysqli_real_escape_string($con, trim($_POST['reason'] ?? ''));
        
        if ($id <= 0) {
            staff_json_response(false, 'Invalid booking ID.');
        }

        $check = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = $id AND (Status = '' OR Status IS NULL OR Status = '0')");
        if (!$check || mysqli_num_rows($check) === 0) {
            staff_json_response(false, 'Booking not found or already processed.');
        }
        
        $oldRow = mysqli_fetch_assoc($check);

        $result = mysqli_query($con, "UPDATE tblappointment SET Status = '2', staff_id = NULL, Remark = 'Released by staff: " . ($reason ?: 'No reason provided') . "' WHERE ID = $id");
        if ($result) {
            log_audit_action($con, [
                'user_type' => 'staff',
                'user_id' => $staffId,
                'user_name' => $staffName,
                'action' => 'update',
                'entity_type' => 'booking',
                'entity_id' => $id,
                'old_values' => $oldRow,
                'new_values' => ['Status' => '2', 'staff_id' => null, 'Remark' => "Released by staff: " . ($reason ?: 'No reason provided')],
                'description' => "Staff {$staffName} released booking #{$oldRow['AptNumber']}" . ($reason ? " - Reason: {$reason}" : '')
            ]);
            staff_json_response(true, 'Booking released and returned to queue.');
        } else {
            staff_json_response(false, 'Failed to release booking.');
        }
    }

    if ($action === 'complete_booking') {
        $id = (int) ($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            staff_json_response(false, 'Invalid booking ID.');
        }

        $check = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = $id AND Status = '1'");
        if (!$check || mysqli_num_rows($check) === 0) {
            staff_json_response(false, 'Booking not found or not yet accepted.');
        }
        
        $oldRow = mysqli_fetch_assoc($check);

        $result = mysqli_query($con, "UPDATE tblappointment SET Status = '3' WHERE ID = $id");
        if ($result) {
            log_audit_action($con, [
                'user_type' => 'staff',
                'user_id' => $staffId,
                'user_name' => $staffName,
                'action' => 'update',
                'entity_type' => 'booking',
                'entity_id' => $id,
                'old_values' => $oldRow,
                'new_values' => ['Status' => '3'],
                'description' => "Staff {$staffName} completed booking #{$oldRow['AptNumber']}"
            ]);
            staff_json_response(true, 'Booking marked as completed!');
        } else {
            staff_json_response(false, 'Failed to complete booking.');
        }
    }
}

$staffList = mysqli_query($con, "SELECT id, name FROM tbl_staff ORDER BY name ASC");

$pendingBookings = mysqli_query($con, "
    SELECT a.*, s.name as staff_name 
    FROM tblappointment a 
    LEFT JOIN tbl_staff s ON a.staff_id = s.id
    WHERE a.Status = '' OR a.Status IS NULL OR a.Status = '0' 
    ORDER BY a.AptDate ASC, a.AptTime ASC
");

$processingBookings = mysqli_query($con, "
    SELECT a.*, s.name as staff_name 
    FROM tblappointment a 
    LEFT JOIN tbl_staff s ON a.staff_id = s.id
    WHERE a.Status = '1' 
    ORDER BY a.AptDate ASC, a.AptTime ASC
");

staff_layout_start('Bookings', 'bookings', 'View and manage appointment bookings');
?>

<div class="staff-section-head mb-4">
    <div class="staff-section-head-left">
        <h2>Booking Requests</h2>
        <p>Review incoming appointments and accept or reject them.</p>
    </div>
</div>

<section class="staff-table-card mb-4">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h3><i class="fa fa-clock" style="color: var(--staff-warning);"></i> Pending Bookings</h3>
        </div>
        <span class="staff-badge" id="pendingCount"><?php echo mysqli_num_rows($pendingBookings); ?></span>
    </div>
    
    <?php if ($pendingBookings && mysqli_num_rows($pendingBookings) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0" id="pendingBookingsTable">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Customer</th>
                        <th>Services</th>
                        <th>Date & Time</th>
                        <th>Contact</th>
                        <th>Assigned To</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($pendingBookings)): 
                        $customer = staff_resolve_customer($row['Name'], $customerMap);
                        $isClaimed = !empty($row['staff_id']);
                        $isMyBooking = (int)$row['staff_id'] === $staffId;
                    ?>
                        <tr id="booking-row-<?php echo (int) $row['ID']; ?>">
                            <td>
                                <strong style="font-weight: 700;"><?php echo staff_escape($row['AptNumber'] ?: '#' . $row['ID']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo staff_escape($customer['Name']); ?></strong>
                                <?php if (!empty($customer['Email'])): ?>
                                    <br><small class="staff-muted"><?php echo staff_escape($customer['Email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="staff-muted"><?php echo staff_escape(staff_service_names($row['Services'], $serviceMap)); ?></span>
                            </td>
                            <td>
                                <strong><?php echo staff_format_date($row['AptDate']); ?></strong>
                                <br><small class="staff-muted"><?php echo staff_format_date($row['AptTime'], 'g:i A'); ?></small>
                            </td>
                            <td>
                                <?php echo staff_escape($row['PhoneNumber'] ?: '--'); ?>
                            </td>
                            <td>
                                <?php if ($isClaimed): ?>
                                    <?php if ($isMyBooking): ?>
                                        <span class="staff-badge is-success">
                                            <i class="fa fa-user-check"></i> You
                                        </span>
                                    <?php else: ?>
                                        <span class="staff-badge">
                                            <i class="fa fa-user"></i> <?php echo staff_escape($row['staff_name'] ?: 'Staff'); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="staff-badge" style="background: rgba(177, 132, 88, 0.15); color: var(--staff-accent-dark);">
                                        <i class="fa fa-user-clock"></i> Unclaimed
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if (!$isClaimed): ?>
                                    <button type="button" class="staff-button staff-button-primary btn-sm" onclick="claimBooking(<?php echo (int) $row['ID']; ?>)" style="margin-right: 8px;">
                                        <i class="fa fa-hand-pointer"></i> Claim
                                    </button>
                                <?php elseif ($isMyBooking): ?>
                                    <button type="button" class="staff-button btn-sm" onclick="showRejectModal(<?php echo (int) $row['ID']; ?>)" style="background: var(--staff-danger-soft); color: var(--staff-danger); border-color: var(--staff-danger-soft);">
                                        <i class="fa fa-times"></i> Release
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="staff-empty-state">
            <i class="fa fa-check-circle"></i>
            <p>No pending bookings. All caught up!</p>
        </div>
    <?php endif; ?>
</section>

<section class="staff-table-card">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h3><i class="fa fa-calendar-check" style="color: var(--staff-green);"></i> My Accepted Bookings</h3>
        </div>
    </div>
    
    <?php if ($processingBookings && mysqli_num_rows($processingBookings) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Booking #</th>
                        <th>Customer</th>
                        <th>Services</th>
                        <th>Date & Time</th>
                        <th>Assigned To</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($processingBookings)): 
                        $customer = staff_resolve_customer($row['Name'], $customerMap);
                        $isMyBooking = (int)$row['staff_id'] === $staffId;
                    ?>
                        <tr id="accepted-row-<?php echo (int) $row['ID']; ?>">
                            <td>
                                <strong style="font-weight: 700;"><?php echo staff_escape($row['AptNumber'] ?: '#' . $row['ID']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo staff_escape($customer['Name']); ?></strong>
                            </td>
                            <td>
                                <span class="staff-muted"><?php echo staff_escape(staff_service_names($row['Services'], $serviceMap)); ?></span>
                            </td>
                            <td>
                                <strong><?php echo staff_format_date($row['AptDate']); ?></strong>
                                <br><small class="staff-muted"><?php echo staff_format_date($row['AptTime'], 'g:i A'); ?></small>
                            </td>
                            <td>
                                <?php if ($isMyBooking): ?>
                                    <span class="staff-badge is-success">
                                        <i class="fa fa-user-check"></i> You
                                    </span>
                                <?php else: ?>
                                    <span class="staff-badge">
                                        <i class="fa fa-user"></i> <?php echo staff_escape($row['staff_name'] ?: 'Assigned'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($isMyBooking): ?>
                                    <button type="button" class="staff-button btn-sm" onclick="completeBooking(<?php echo (int) $row['ID']; ?>)" style="background: var(--staff-green); color: white; border-color: var(--staff-green);">
                                        <i class="fa fa-check-double"></i> Mark as Done
                                    </button>
                                <?php else: ?>
                                    <span class="staff-muted"><i class="fa fa-lock"></i> Assigned to <?php echo staff_escape($row['staff_name'] ?: 'another staff'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="staff-empty-state">
            <i class="fa fa-calendar-times-o"></i>
            <p>No upcoming accepted bookings.</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="rejectBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--staff-radius-lg); border: 0;">
            <form id="rejectBookingForm">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h3 class="modal-title" style="font-family: 'Libre Baskerville', serif;">
                            <i class="fa fa-times-circle" style="color: var(--staff-danger); margin-right: 10px;"></i>
                            Release Booking
                        </h3>
                        <p class="staff-muted mb-0" style="margin-top: 6px;">Release this booking back to the queue.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="ajax_action" value="reject_booking">
                    <input type="hidden" name="id" id="rejectBookingId">
                    <div class="mb-3">
                        <label class="form-label">Reason for release (optional)</label>
                        <textarea class="form-control" name="reason" id="rejectReason" rows="3" placeholder="e.g., No longer available"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="staff-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="staff-button" style="background: var(--staff-danger); color: white; border-color: var(--staff-danger);">
                        <i class="fa fa-sign-out-alt"></i> Confirm Release
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var rejectModalInstance = null;

    function initRejectModal() {
        var modalEl = document.getElementById('rejectBookingModal');
        var form = document.getElementById('rejectBookingForm');

        if (!modalEl || !form) return;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            rejectModalInstance = new bootstrap.Modal(modalEl);
        }

        form.onsubmit = async function (e) {
            e.preventDefault();
            
            var formData = new FormData(form);
            var bookingId = formData.get('id');

            try {
                var payload = await window.StaffPortal.postForm('bookings.php', formData, {
                    loadingText: 'Rejecting booking...',
                    successMessage: false
                });

                var row = document.getElementById('booking-row-' + bookingId);
                if (row) {
                    row.remove();
                }

                var pendingCount = document.getElementById('pendingCount');
                if (pendingCount) {
                    var count = parseInt(pendingCount.textContent) - 1;
                    pendingCount.textContent = Math.max(0, count);
                }

                if (rejectModalInstance) {
                    rejectModalInstance.hide();
                }

                window.StaffPortal.showToast(payload.message || 'Booking rejected.', 'success');
                form.reset();
            } catch (error) {
                window.StaffPortal.showToast(error.message || 'Failed to reject booking.', 'error');
            }
        };
    }

    window.showRejectModal = function (id) {
        var form = document.getElementById('rejectBookingForm');
        var idInput = document.getElementById('rejectBookingId');
        if (idInput) idInput.value = id;
        
        var modalTitle = form.closest('.modal-content').querySelector('.modal-title');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fa fa-times-circle" style="color: var(--staff-danger); margin-right: 10px;"></i> Release Booking';
        }
        
        var reasonLabel = form.querySelector('label');
        var reasonTextarea = form.querySelector('textarea');
        if (reasonLabel) reasonLabel.textContent = 'Reason for release (optional)';
        if (reasonTextarea) reasonTextarea.placeholder = 'e.g., No longer available';
        
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fa fa-sign-out-alt"></i> Confirm Release';
        }
        
        if (rejectModalInstance) {
            rejectModalInstance.show();
        }
    };

    window.claimBooking = async function (id) {
        if (!confirm('Claim this booking? You will be assigned to handle this appointment.')) return;

        var formData = new FormData();
        formData.append('ajax_action', 'claim_booking');
        formData.append('id', id);

        try {
            var payload = await window.StaffPortal.postForm('bookings.php', formData, {
                loadingText: 'Claiming booking...',
                successMessage: false
            });

            var row = document.getElementById('booking-row-' + id);
            if (row) {
                row.remove();
            }

            var pendingCount = document.getElementById('pendingCount');
            if (pendingCount) {
                var count = parseInt(pendingCount.textContent) - 1;
                pendingCount.textContent = Math.max(0, count);
            }

            window.StaffPortal.showToast(payload.message || 'Booking claimed!', 'success');
            
            setTimeout(function() {
                location.reload();
            }, 1000);
        } catch (error) {
            window.StaffPortal.showToast(error.message || 'Failed to claim booking.', 'error');
        }
    };

    window.acceptBooking = async function (id) {
        if (!confirm('Accept this booking?')) return;

        var formData = new FormData();
        formData.append('ajax_action', 'accept_booking');
        formData.append('id', id);

        try {
            var payload = await window.StaffPortal.postForm('bookings.php', formData, {
                loadingText: 'Accepting booking...',
                successMessage: false
            });

            var row = document.getElementById('booking-row-' + id);
            if (row) {
                row.remove();
            }

            var pendingCount = document.getElementById('pendingCount');
            if (pendingCount) {
                var count = parseInt(pendingCount.textContent) - 1;
                pendingCount.textContent = Math.max(0, count);
            }

            window.StaffPortal.showToast(payload.message || 'Booking accepted!', 'success');
        } catch (error) {
            window.StaffPortal.showToast(error.message || 'Failed to accept booking.', 'error');
        }
    };

    window.completeBooking = async function (id) {
        if (!confirm('Mark this booking as completed?')) return;

        var formData = new FormData();
        formData.append('ajax_action', 'complete_booking');
        formData.append('id', id);

        try {
            var payload = await window.StaffPortal.postForm('bookings.php', formData, {
                loadingText: 'Completing booking...',
                successMessage: false
            });

            var row = document.getElementById('accepted-row-' + id);
            if (row) {
                row.remove();
            }

            window.StaffPortal.showToast(payload.message || 'Booking marked as done!', 'success');
        } catch (error) {
            window.StaffPortal.showToast(error.message || 'Failed to complete booking.', 'error');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRejectModal);
    } else {
        initRejectModal();
    }
})();
</script>
<?php staff_layout_end(); ?>
