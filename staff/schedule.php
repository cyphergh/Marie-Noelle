<?php
include_once __DIR__ . '/includes/layout.php';
staff_require_login();

$staffId = staff_current_id();
$today = date('Y-m-d');

$upcomingShifts = mysqli_query($con, "SELECT * FROM tbl_staff_schedule WHERE staff_id = '{$staffId}' AND shift_date >= '{$today}' ORDER BY shift_date ASC, start_time ASC");
$pastShifts = mysqli_query($con, "SELECT * FROM tbl_staff_schedule WHERE staff_id = '{$staffId}' AND shift_date < '{$today}' ORDER BY shift_date DESC, start_time DESC LIMIT 20");

$upcomingCount = mysqli_num_rows($upcomingShifts);
$totalShifts = $upcomingCount + mysqli_num_rows($pastShifts);

staff_layout_start('Schedule', 'schedule', 'Your work shifts and assignments');
?>
<div class="staff-grid cards-3 mb-4">
    <div class="staff-card">
        <div class="staff-stat-icon green">
            <i class="fa fa-calendar-check"></i>
        </div>
        <div class="staff-stat-label">Total Shifts</div>
        <p class="staff-stat-value"><?php echo $totalShifts; ?></p>
        <p class="staff-stat-help">All shifts assigned to you.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon accent">
            <i class="fa fa-calendar-plus"></i>
        </div>
        <div class="staff-stat-label">Upcoming Shifts</div>
        <p class="staff-stat-value"><?php echo $upcomingCount; ?></p>
        <p class="staff-stat-help">Shifts scheduled from today onwards.</p>
    </div>

    <div class="staff-card">
        <div class="staff-stat-icon" style="background: var(--staff-green-soft); color: var(--staff-green);">
            <i class="fa fa-clock"></i>
        </div>
        <div class="staff-stat-label">Operating Hours</div>
        <p class="staff-stat-value" style="font-size: 1.6rem;">09:00 - 17:00</p>
        <p class="staff-stat-help">Monday to Sunday.</p>
    </div>
</div>

<section class="staff-table-card mb-4">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h2>Upcoming Shifts</h2>
            <p class="staff-muted mb-0">Your scheduled work shifts and assignments.</p>
        </div>
        <span class="staff-badge is-success">
            <i class="fa fa-calendar"></i>
            Future
        </span>
    </div>

    <?php if ($upcomingShifts && mysqli_num_rows($upcomingShifts) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($upcomingShifts)): ?>
                        <tr>
                            <td>
                                <strong><?php echo staff_format_date($row['shift_date']); ?></strong>
                            </td>
                            <td class="staff-muted">
                                <?php echo staff_escape($row['start_time']); ?>
                            </td>
                            <td class="staff-muted">
                                <?php echo staff_escape($row['end_time']); ?>
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
            <i class="fa fa-calendar-times"></i>
            <p>No upcoming shifts scheduled. Check back later or contact your administrator.</p>
        </div>
    <?php endif; ?>
</section>

<?php if ($pastShifts && mysqli_num_rows($pastShifts) > 0): ?>
<section class="staff-table-card">
    <div class="staff-section-head">
        <div class="staff-section-head-left">
            <h2>Past Shifts</h2>
            <p class="staff-muted mb-0">Your completed work history.</p>
        </div>
        <span class="staff-badge">
            <i class="fa fa-history"></i>
            History
        </span>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($pastShifts)): ?>
                    <tr style="opacity: 0.7;">
                        <td>
                            <strong><?php echo staff_format_date($row['shift_date']); ?></strong>
                        </td>
                        <td class="staff-muted">
                            <?php echo staff_escape($row['start_time']); ?>
                        </td>
                        <td class="staff-muted">
                            <?php echo staff_escape($row['end_time']); ?>
                        </td>
                        <td>
                            <span class="staff-badge <?php echo $row['status'] === 'Working' ? 'is-success' : ''; ?>">
                                <?php echo staff_escape($row['status'] ?: 'Completed'); ?>
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
</section>
<?php endif; ?>
<?php staff_layout_end(); ?>
