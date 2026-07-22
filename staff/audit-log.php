<?php
include_once __DIR__ . '/includes/layout.php';
include_once __DIR__ . '/../panel/includes/audit_helper.php';
staff_require_login();

$staff = staff_fetch_current($con);
$staffId = (int) $staff['id'];

ensure_audit_table($con);

$filters = [
    'user_type' => 'staff',
    'user_id' => $staffId,
    'action' => isset($_GET['action']) ? $_GET['action'] : '',
    'entity_type' => isset($_GET['entity_type']) ? $_GET['entity_type'] : '',
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : '',
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
    'limit' => 50,
    'offset' => isset($_GET['page']) ? ((int)$_GET['page'] - 1) * 50 : 0
];

$totalRecords = count_audit_log($con, $filters);
$totalPages = ceil($totalRecords / 50);
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$auditResult = get_audit_log($con, $filters);

$summaryQuery = mysqli_query($con, "
    SELECT action, COUNT(*) as count
    FROM audit_log 
    WHERE user_type = 'staff' AND user_id = $staffId
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY action
");
$summary = [];
while ($row = mysqli_fetch_assoc($summaryQuery)) {
    $summary[$row['action']] = $row;
}

$buildQuery = http_build_query(array_filter([
    'action' => $filters['action'],
    'entity_type' => $filters['entity_type'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
    'search' => $filters['search']
], function($v) { return $v !== ''; }));

staff_layout_start('My Activity Log', 'audit', 'Track your account activities');
?>

<div class="staff-section-head mb-4">
    <div class="staff-section-head-left">
        <h2>My Activity Log</h2>
        <p>Track all your actions and changes made to the system.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 24px;">
    <div class="staff-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; border-radius: 12px; color: white; text-align: center;">
        <div style="font-size: 2em; font-weight: bold;"><?php echo $summary['create']['count'] ?? 0; ?></div>
        <div style="font-size: 0.85em; opacity: 0.9;">Created (24h)</div>
    </div>
    <div class="staff-card" style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); padding: 20px; border-radius: 12px; color: white; text-align: center;">
        <div style="font-size: 2em; font-weight: bold;"><?php echo $summary['update']['count'] ?? 0; ?></div>
        <div style="font-size: 0.85em; opacity: 0.9;">Updated (24h)</div>
    </div>
    <div class="staff-card" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); padding: 20px; border-radius: 12px; color: white; text-align: center;">
        <div style="font-size: 2em; font-weight: bold;"><?php echo $summary['delete']['count'] ?? 0; ?></div>
        <div style="font-size: 0.85em; opacity: 0.9;">Deleted (24h)</div>
    </div>
</div>

<div class="staff-card" style="background: #f8f9fa; padding: 16px; border-radius: 12px; margin-bottom: 20px;">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <label style="font-weight: 600; font-size: 0.9em;">Action</label>
            <select name="action" class="form-control">
                <option value="">All</option>
                <option value="create" <?php echo $filters['action'] === 'create' ? 'selected' : ''; ?>>Create</option>
                <option value="update" <?php echo $filters['action'] === 'update' ? 'selected' : ''; ?>>Update</option>
                <option value="delete" <?php echo $filters['action'] === 'delete' ? 'selected' : ''; ?>>Delete</option>
            </select>
        </div>
        <div class="col-md-3">
            <label style="font-weight: 600; font-size: 0.9em;">Entity Type</label>
            <select name="entity_type" class="form-control">
                <option value="">All</option>
                <option value="invoice" <?php echo $filters['entity_type'] === 'invoice' ? 'selected' : ''; ?>>Invoice</option>
                <option value="appointment" <?php echo $filters['entity_type'] === 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                <option value="booking" <?php echo $filters['entity_type'] === 'booking' ? 'selected' : ''; ?>>Booking</option>
                <option value="customer" <?php echo $filters['entity_type'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
            </select>
        </div>
        <div class="col-md-2">
            <label style="font-weight: 600; font-size: 0.9em;">From</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo $filters['date_from']; ?>">
        </div>
        <div class="col-md-2">
            <label style="font-weight: 600; font-size: 0.9em;">To</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo $filters['date_to']; ?>">
        </div>
        <div class="col-md-2" style="display: flex; align-items: flex-end; gap: 10px;">
            <button type="submit" class="staff-button staff-button-primary"><i class="fa fa-search"></i></button>
            <a href="audit-log.php" class="staff-button" style="color: var(--staff-danger);"><i class="fa fa-times"></i></a>
        </div>
    </form>
</div>

<div class="staff-table-card">
    <div class="table-responsive">
        <table class="table" id="auditTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Time</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $cnt = $filters['offset'] + 1;
                if (mysqli_num_rows($auditResult) > 0): 
                    while ($row = mysqli_fetch_assoc($auditResult)): 
                ?>
                    <tr>
                        <td><?php echo $cnt++; ?></td>
                        <td>
                            <strong><?php echo date('d M Y', strtotime($row['created_at'])); ?></strong>
                            <br><small class="staff-muted"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small>
                        </td>
                        <td>
                            <span class="staff-badge <?php echo $row['action'] === 'create' ? 'is-success' : ($row['action'] === 'update' ? 'is-info' : ($row['action'] === 'delete' ? 'is-danger' : '')); ?>">
                                <?php echo ucfirst($row['action']); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo get_entity_type_label($row['entity_type']); ?></strong>
                            <?php if ($row['entity_id'] > 0): ?>
                                <br><small class="staff-muted">#<?php echo $row['entity_id']; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo staff_escape($row['description'] ?: '--'); ?></td>
                        <td>
                            <?php if (!empty($row['old_values']) || !empty($row['new_values'])): ?>
                                <button type="button" class="staff-button btn-sm" onclick="toggleDetails('details-<?php echo $row['id']; ?>')">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <div id="details-<?php echo $row['id']; ?>" style="display: none; background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; font-family: monospace; font-size: 0.85em; max-width: 300px;">
                                    <?php if (!empty($row['old_values'])): ?>
                                        <strong>Before:</strong><br>
                                        <pre style="margin: 5px 0; white-space: pre-wrap; word-break: break-all;"><?php echo $row['old_values']; ?></pre>
                                    <?php endif; ?>
                                    <?php if (!empty($row['new_values'])): ?>
                                        <strong>After:</strong><br>
                                        <pre style="margin: 5px 0; white-space: pre-wrap; word-break: break-all;"><?php echo $row['new_values']; ?></pre>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="staff-muted">--</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php 
                    endwhile; 
                else:
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="staff-empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>No activity records found</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-4">
    <div class="staff-muted">
        Showing <?php echo $filters['offset'] + 1; ?> to <?php echo min($filters['offset'] + 50, $totalRecords); ?> of <?php echo $totalRecords; ?>
    </div>
    <nav>
        <ul class="pagination" style="margin: 0;">
            <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $buildQuery ? '&' . $buildQuery : ''; ?>" style="color: var(--staff-accent);">Previous</a>
                </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $buildQuery ? '&' . $buildQuery : ''; ?>" style="<?php echo $i === $currentPage ? 'background: var(--staff-accent); border-color: var(--staff-accent); color: white;' : 'color: var(--staff-accent);'; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $buildQuery ? '&' . $buildQuery : ''; ?>" style="color: var(--staff-accent);">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

<script>
function toggleDetails(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>

<?php staff_layout_end(); ?>
