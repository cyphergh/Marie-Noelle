<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$staff = staff_fetch_current($con);
$staffId = (int) $staff['id'];
$today = date('Y-m-d');
$customerMap = staff_fetch_customer_map($con);
$serviceMap = staff_fetch_service_map($con);

$todayAppointments = 0;
$todayResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM tblappointment WHERE AptDate = '{$today}'");
if ($todayResult) {
    $row = mysqli_fetch_assoc($todayResult);
    $todayAppointments = (int) $row['total'];
}

$pendingAppointments = 0;
$pendingResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM tblappointment WHERE Status = '' OR Status IS NULL OR Status = '0'");
if ($pendingResult) {
    $row = mysqli_fetch_assoc($pendingResult);
    $pendingAppointments = (int) $row['total'];
}

$invoiceSummary = array('invoice_count' => 0, 'total_amount' => 0);
$invoiceResult = mysqli_query($con, "SELECT COUNT(*) AS invoice_count, COALESCE(SUM(total), 0) AS total_amount FROM tblinvoice WHERE ServiceId = '0' AND staff = '{$staffId}'");
if ($invoiceResult) {
    $invoiceSummary = mysqli_fetch_assoc($invoiceResult);
}

$upcomingShifts = 0;
$scheduleResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbl_staff_schedule WHERE staff_id = '{$staffId}' AND shift_date >= '{$today}'");
if ($scheduleResult) {
    $row = mysqli_fetch_assoc($scheduleResult);
    $upcomingShifts = (int) $row['total'];
}

$recentAppointments = mysqli_query($con, "SELECT * FROM tblappointment WHERE Status != '3' AND Status != '2' ORDER BY ID DESC LIMIT 5");
$upcomingSchedule = mysqli_query($con, "SELECT * FROM tbl_staff_schedule WHERE staff_id = '{$staffId}' ORDER BY shift_date ASC, start_time ASC LIMIT 5");

staff_layout_start('Dashboard', 'dashboard', 'Overview of your workspace and activity');
?>
<div class="staff-grid cards-4">
    <div class="staff-card">
        <div class="staff-stat-icon accent">
            <i class="fa fa-calendar-day"></i>
        </div>
        <div class="staff-stat-label">Today's Appointments</div>
        <p class="staff-stat-value"><?php echo $todayAppointments; ?></p>
        <p class="staff-stat-help">Bookings scheduled for <?php echo staff_format_date($today); ?>.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon warning">
            <i class="fa fa-clock"></i>
        </div>
        <div class="staff-stat-label">Pending Bookings</div>
        <p class="staff-stat-value"><?php echo $pendingAppointments; ?></p>
        <p class="staff-stat-help">Appointments awaiting status updates.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon green">
            <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="staff-stat-label">My Invoices</div>
        <p class="staff-stat-value"><?php echo (int) $invoiceSummary['invoice_count']; ?></p>
        <p class="staff-stat-help">Payment lines linked to your staff record.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon accent">
            <i class="fa fa-calendar-week"></i>
        </div>
        <div class="staff-stat-label">Upcoming Shifts</div>
        <p class="staff-stat-value"><?php echo $upcomingShifts; ?></p>
        <p class="staff-stat-help">Future schedule entries published.</p>
    </div>
</div>

<div class="staff-grid cards-3 mt-4">
    <div class="staff-card">
        <div class="staff-section-head">
            <div class="staff-section-head-left">
                <h2>Earnings Snapshot</h2>
            </div>
            <span class="staff-badge is-success">
                <i class="fa fa-chart-line"></i>
                Live Data
            </span>
        </div>
        <div class="row g-3">
            <div class="col-sm-6">
                <div class="staff-card" style="background: rgba(255,255,255,0.72); box-shadow:none; padding: 18px;">
                    <div class="staff-stat-label">Total Revenue</div>
                    <p class="staff-stat-value" style="font-size: 1.8rem;">GH₵ <?php echo staff_format_money($invoiceSummary['total_amount']); ?></p>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="staff-card" style="background: rgba(255,255,255,0.72); box-shadow:none; padding: 18px;">
                    <div class="staff-stat-label">Invoice Count</div>
                    <p class="staff-stat-value" style="font-size: 1.8rem; color: var(--staff-green);"><?php echo (int) $invoiceSummary['invoice_count']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="staff-card" style="grid-column: span 2;">
        <div class="staff-section-head">
            <div class="staff-section-head-left">
                <h2>Quick Actions</h2>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="appointments.php" data-spa="appointments.php" class="staff-card" style="display: flex; align-items: center; gap: 14px; text-decoration: none; cursor: pointer;">
                    <div class="staff-stat-icon green" style="margin-bottom: 0;">
                        <i class="fa fa-calendar-plus-o"></i>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 14px;">New Appointment</strong>
                        <span class="staff-muted" style="font-size: 12px;">Create walk-in booking</span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="schedule.php" data-spa="schedule.php" class="staff-card" style="display: flex; align-items: center; gap: 14px; text-decoration: none; cursor: pointer;">
                    <div class="staff-stat-icon accent" style="margin-bottom: 0;">
                        <i class="fa fa-clock"></i>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 14px;">View Schedule</strong>
                        <span class="staff-muted" style="font-size: 12px;">Check your shifts</span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="payments.php" data-spa="payments.php" class="staff-card" style="display: flex; align-items: center; gap: 14px; text-decoration: none; cursor: pointer;">
                    <div class="staff-stat-icon" style="margin-bottom: 0; background: rgba(43, 91, 85, 0.1); color: var(--staff-green);">
                        <i class="fa fa-credit-card"></i>
                    </div>
                    <div>
                        <strong style="display: block; font-size: 14px;">Payment History</strong>
                        <span class="staff-muted" style="font-size: 12px;">Review invoices</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="staff-grid cards-2 mt-4">
    <section class="staff-table-card">
        <div class="staff-section-head">
            <div class="staff-section-head-left">
                <h2>Recent Appointments</h2>
            </div>
            <a class="staff-button staff-button-primary" href="appointments.php" data-spa="appointments.php">
                <i class="fa fa-arrow-right"></i>
                View All
            </a>
        </div>
        <?php if ($recentAppointments && mysqli_num_rows($recentAppointments) > 0): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Date & Time</th>
                            <th>Services</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($recentAppointments)): 
                            $customer = staff_resolve_customer($row['Name'], $customerMap);
                            $statusInfo = staff_get_appointment_status($row['Status']);
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo staff_escape($customer['Name']); ?></strong><br>
                                    <small class="staff-muted"><?php echo staff_escape($customer['Email'] ?: $row['Email'] ?: '--'); ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 600;"><?php echo staff_format_date($row['AptDate']); ?></span><br>
                                    <small class="staff-muted"><?php echo staff_format_date($row['AptTime'], 'g:i A'); ?></small>
                                </td>
                                <td class="staff-muted">
                                    <?php echo staff_escape(staff_service_names($row['Services'], $serviceMap)); ?>
                                </td>
                                <td>
                                    <span class="staff-badge <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="staff-empty-state">
                <i class="fa fa-calendar-times-o"></i>
                <p>No appointments found.</p>
            </div>
        <?php endif; ?>
    </section>

    <section class="staff-table-card">
        <div class="staff-section-head">
            <div class="staff-section-head-left">
                <h2>Upcoming Schedule</h2>
            </div>
            <a class="staff-button" href="schedule.php" data-spa="schedule.php">
                <i class="fa fa-arrow-right"></i>
                View All
            </a>
        </div>
        <?php if ($upcomingSchedule && mysqli_num_rows($upcomingSchedule) > 0): ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shift Hours</th>
                            <th>Status</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($upcomingSchedule)): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo staff_format_date($row['shift_date']); ?></td>
                                <td class="staff-muted">
                                    <?php echo staff_escape($row['start_time']); ?> - <?php echo staff_escape($row['end_time']); ?>
                                </td>
                                <td>
                                    <span class="staff-badge <?php echo $row['status'] === 'Working' ? 'is-success' : ''; ?>">
                                        <?php echo staff_escape($row['status'] ?: 'Scheduled'); ?>
                                    </span>
                                </td>
                                <td class="staff-muted">
                                    <?php echo staff_escape($row['note'] ?: '--'); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="staff-empty-state">
                <i class="fa fa-clock"></i>
                <p>No upcoming shifts scheduled.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
    .staff-stat-icon.warning {
        background: var(--staff-warning-soft);
        color: var(--staff-warning);
    }
</style>
<?php staff_layout_end(); ?>
