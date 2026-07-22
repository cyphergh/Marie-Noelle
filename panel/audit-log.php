<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');
include('includes/audit_helper.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

ensure_audit_table($con);

$adminId = $_SESSION['bpmsaid'];
$adminResult = mysqli_query($con, "SELECT AdminName FROM tbladmin WHERE ID = '$adminId'");
$adminRow = mysqli_fetch_assoc($adminResult);
$adminName = $adminRow['AdminName'] ?? 'Admin';

$filters = [
    'user_type' => isset($_GET['user_type']) ? $_GET['user_type'] : '',
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
    SELECT 
        action,
        COUNT(*) as count,
        MAX(created_at) as last_occurrence
    FROM audit_log 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY action
");
$summary = [];
while ($row = mysqli_fetch_assoc($summaryQuery)) {
    $summary[$row['action']] = $row;
}

$buildQuery = http_build_query(array_filter([
    'user_type' => $filters['user_type'],
    'action' => $filters['action'],
    'entity_type' => $filters['entity_type'],
    'date_from' => $filters['date_from'],
    'date_to' => $filters['date_to'],
    'search' => $filters['search']
], function($v) { return $v !== ''; }));
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Audit Log</title>
    <link rel="icon" type="image/x-icon" href="images/logo.png">
    <script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
    <link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
    <link href="css/style.css" rel='stylesheet' type='text/css' />
    <link href="css/font-awesome.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap.css">
    <link href="https://cdn.datatables.net/buttons/3.2.2/css/buttons.bootstrap.css">
    <script src="js/jquery-1.11.1.min.js"></script>
    <script src="js/modernizr.custom.js"></script>
    <link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
    <link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/wow.min.js"></script>
    <script>new WOW().init();</script>
    <script src="js/metisMenu.min.js"></script>
    <script src="js/custom.js"></script>
    <link href="css/custom.css" rel="stylesheet">
    <style>
        .audit-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .audit-summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
            text-align: center;
        }
        .audit-summary-card.create { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .audit-summary-card.update { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); }
        .audit-summary-card.delete { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .audit-summary-card .count { font-size: 2em; font-weight: bold; }
        .audit-summary-card .label { font-size: 0.85em; opacity: 0.9; }
        
        .filter-form { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .filter-form .form-group { margin-bottom: 10px; }
        .filter-form label { font-weight: 600; font-size: 0.9em; }
        
        .audit-table { font-size: 0.9em; }
        .audit-table th { background: #2e4758; color: white; }
        .audit-table tbody tr:hover { background: #f5f5f5; }
        
        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .action-badge.create { background: #d4edda; color: #155724; }
        .action-badge.update { background: #d1ecf1; color: #0c5460; }
        .action-badge.delete { background: #f8d7da; color: #721c24; }
        .action-badge.void { background: #fff3cd; color: #856404; }
        .action-badge.refund { background: #e2e3e5; color: #383d41; }
        
        .entity-badge {
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        
        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .user-badge.admin { color: #dc3545; }
        .user-badge.staff { color: #28a745; }
        .user-badge.system { color: #6c757d; }
        
        .pagination-container { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .pagination-info { color: #6c757d; }
        
        .details-toggle {
            cursor: pointer;
            color: #007bff;
        }
        .details-toggle:hover { text-decoration: underline; }
        
        .json-viewer {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85em;
            max-height: 200px;
            overflow: auto;
            margin-top: 5px;
        }
        .json-viewer pre { margin: 0; white-space: pre-wrap; word-break: break-all; }
        
        .clear-filters { color: #dc3545; text-decoration: none; }
        .clear-filters:hover { text-decoration: underline; }
    </style>
</head>
<body class="cbp-spmenu-push">
    <div class="main-content">
        <?php include_once('includes/sidebar.php'); ?>
        <?php include_once('includes/header.php'); ?>
        <div id="page-wrapper">
            <div class="main-page">
                <div class="tables">
                    <div class="page-toolbar">
                        <div>
                            <h3 class="title1">Audit Log</h3>
                            <p>Track all financial and system activities</p>
                        </div>
                    </div>
                    
                    <div class="audit-summary">
                        <div class="audit-summary-card create">
                            <div class="count"><?php echo $summary['create']['count'] ?? 0; ?></div>
                            <div class="label">Created (24h)</div>
                        </div>
                        <div class="audit-summary-card update">
                            <div class="count"><?php echo $summary['update']['count'] ?? 0; ?></div>
                            <div class="label">Updated (24h)</div>
                        </div>
                        <div class="audit-summary-card delete">
                            <div class="count"><?php echo $summary['delete']['count'] ?? 0; ?></div>
                            <div class="label">Deleted (24h)</div>
                        </div>
                        <div class="audit-summary-card">
                            <div class="count"><?php echo $totalRecords; ?></div>
                            <div class="label">Total Records</div>
                        </div>
                    </div>
                    
                    <div class="filter-form">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label>User Type</label>
                                <select name="user_type" class="form-control">
                                    <option value="">All</option>
                                    <option value="admin" <?php echo $filters['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="staff" <?php echo $filters['user_type'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="system" <?php echo $filters['user_type'] === 'system' ? 'selected' : ''; ?>>System</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Action</label>
                                <select name="action" class="form-control">
                                    <option value="">All</option>
                                    <option value="create" <?php echo $filters['action'] === 'create' ? 'selected' : ''; ?>>Create</option>
                                    <option value="update" <?php echo $filters['action'] === 'update' ? 'selected' : ''; ?>>Update</option>
                                    <option value="delete" <?php echo $filters['action'] === 'delete' ? 'selected' : ''; ?>>Delete</option>
                                    <option value="void" <?php echo $filters['action'] === 'void' ? 'selected' : ''; ?>>Void</option>
                                    <option value="refund" <?php echo $filters['action'] === 'refund' ? 'selected' : ''; ?>>Refund</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Entity Type</label>
                                <select name="entity_type" class="form-control">
                                    <option value="">All</option>
                                    <option value="invoice" <?php echo $filters['entity_type'] === 'invoice' ? 'selected' : ''; ?>>Invoice</option>
                                    <option value="appointment" <?php echo $filters['entity_type'] === 'appointment' ? 'selected' : ''; ?>>Appointment</option>
                                    <option value="booking" <?php echo $filters['entity_type'] === 'booking' ? 'selected' : ''; ?>>Booking</option>
                                    <option value="customer" <?php echo $filters['entity_type'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="payment" <?php echo $filters['entity_type'] === 'payment' ? 'selected' : ''; ?>>Payment</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo $filters['date_from']; ?>">
                            </div>
                            <div class="col-md-2">
                                <label>To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo $filters['date_to']; ?>">
                            </div>
                            <div class="col-md-2">
                                <label>Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo panel_escape($filters['search']); ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                                <a href="audit-log.php" class="clear-filters"><i class="fa fa-times"></i> Clear Filters</a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="table-responsive bs-example widget-shadow">
                        <table class="table audit-table" id="auditTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
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
                                            <strong><?php echo date('d M Y', strtotime($row['created_at'])); ?></strong><br>
                                            <small><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="user-badge <?php echo $row['user_type']; ?>">
                                                <i class="fa fa-<?php echo $row['user_type'] === 'admin' ? 'user-shield' : ($row['user_type'] === 'staff' ? 'user' : 'cog'); ?>"></i>
                                                <?php echo panel_escape($row['user_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="action-badge <?php echo $row['action']; ?>">
                                                <?php echo get_action_icon($row['action']); ?>
                                                <?php echo ucfirst($row['action']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="entity-badge"><?php echo get_entity_type_label($row['entity_type']); ?></span>
                                            <?php if ($row['entity_id'] > 0): ?>
                                                <br><small>#<?php echo $row['entity_id']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo panel_escape($row['description'] ?: '--'); ?></td>
                                        <td><small><?php echo $row['ip_address']; ?></small></td>
                                        <td>
                                            <?php if (!empty($row['old_values']) || !empty($row['new_values'])): ?>
                                                <span class="details-toggle" onclick="toggleDetails('details-<?php echo $row['id']; ?>')">
                                                    <i class="fa fa-eye"></i> View
                                                </span>
                                                <div id="details-<?php echo $row['id']; ?>" class="json-viewer" style="display: none;">
                                                    <?php if (!empty($row['old_values'])): ?>
                                                        <strong>Before:</strong>
                                                        <pre><?php echo $row['old_values']; ?></pre>
                                                    <?php endif; ?>
                                                    <?php if (!empty($row['new_values'])): ?>
                                                        <strong>After:</strong>
                                                        <pre><?php echo $row['new_values']; ?></pre>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">--</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php 
                                    endwhile; 
                                else:
                                ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fa fa-inbox" style="font-size: 2em;"></i>
                                            <p>No audit records found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Showing <?php echo $filters['offset'] + 1; ?> to <?php echo min($filters['offset'] + 50, $totalRecords); ?> of <?php echo $totalRecords; ?> records
                        </div>
                        <nav>
                            <ul class="pagination">
                                <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo $buildQuery ? '&' . $buildQuery : ''; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $buildQuery ? '&' . $buildQuery : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo $buildQuery ? '&' . $buildQuery : ''; ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once('includes/footer.php'); ?>
    </div>

    <script src="js/classie.js"></script>
    <script>
        var menuLeft = document.getElementById('cbp-spmenu-s1'),
            showLeftPush = document.getElementById('showLeftPush'),
            body = document.body;
        showLeftPush.onclick = function () {
            classie.toggle(this, 'active');
            classie.toggle(body, 'cbp-spmenu-push-toright');
            classie.toggle(menuLeft, 'cbp-spmenu-open');
            disableOther('showLeftPush');
        };
        function disableOther(button) {
            if (button !== 'showLeftPush') {
                classie.toggle(showLeftPush, 'disabled');
            }
        }
    </script>
    <script src="js/jquery.nicescroll.js"></script>
    <script src="js/scripts.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    
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
</body>
</html>
