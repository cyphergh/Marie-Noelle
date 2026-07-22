<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

$currentAdminId = (int) $_SESSION['bpmsaid'];
$defaultAdminPassword = '12345678';
$defaultAdminPasswordHash = md5($defaultAdminPassword);

function render_admin_row($row, $currentAdminId)
{
    $id = (int) $row['ID'];
    $isCurrentAdmin = $id === (int) $currentAdminId;
    ob_start();
    ?>
    <tr id="admin-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td>
            <strong><?php echo panel_escape($row['AdminName']); ?></strong>
            <?php if ($isCurrentAdmin) { ?>
                <span class="status-chip is-active">You</span>
            <?php } ?>
        </td>
        <td><?php echo panel_escape($row['UserName']); ?></td>
        <td><?php echo panel_escape($row['MobileNumber'] ?: '--'); ?></td>
        <td><?php echo panel_escape($row['Email'] ?: '--'); ?></td>
        <td><?php echo panel_format_date($row['AdminRegdate'], 'd M Y'); ?></td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-admin" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-admin" data-id="<?php echo $id; ?>" <?php echo $isCurrentAdmin ? 'disabled title="You cannot delete your own account."' : ''; ?>>
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php
    return trim(ob_get_clean());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && panel_is_ajax_request()) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

    if ($action === 'create_admin') {
        $adminName = mysqli_real_escape_string($con, trim($_POST['admin_name']));
        $userName = mysqli_real_escape_string($con, trim($_POST['username']));
        $mobileNumber = mysqli_real_escape_string($con, trim($_POST['mobile_number']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));

        if ($adminName === '' || $userName === '') {
            panel_json_response(false, 'Admin name and username are required.');
        }

        $duplicateQuery = mysqli_query($con, "SELECT ID FROM tbladmin WHERE UserName = '{$userName}' LIMIT 1");
        if ($duplicateQuery && mysqli_num_rows($duplicateQuery) > 0) {
            panel_json_response(false, 'That username is already in use.');
        }

        if ($email !== '') {
            $duplicateEmailQuery = mysqli_query($con, "SELECT ID FROM tbladmin WHERE Email = '{$email}' LIMIT 1");
            if ($duplicateEmailQuery && mysqli_num_rows($duplicateEmailQuery) > 0) {
                panel_json_response(false, 'That email address is already in use.');
            }
        }

        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO tbladmin(AdminName, UserName, MobileNumber, Email, Password) VALUES ('{$adminName}', '{$userName}', '{$mobileNumber}', '{$email}', '{$defaultAdminPasswordHash}')"
        );

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to create the admin account.');
        }

        $newId = mysqli_insert_id($con);
        $adminResult = mysqli_query($con, "SELECT * FROM tbladmin WHERE ID = '{$newId}'");
        $adminRow = mysqli_fetch_assoc($adminResult);

        panel_json_response(true, 'Admin account created. Default password: 12345678.', array(
            'row_html' => render_admin_row($adminRow, $currentAdminId),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_admin') {
        $id = (int) $_POST['id'];
        $adminResult = mysqli_query($con, "SELECT * FROM tbladmin WHERE ID = '{$id}' LIMIT 1");
        $adminRow = mysqli_fetch_assoc($adminResult);

        if (!$adminRow) {
            panel_json_response(false, 'Admin account not found.');
        }

        panel_json_response(true, 'Admin account loaded.', array('record' => $adminRow));
    }

    if ($action === 'update_admin') {
        $id = (int) $_POST['id'];
        $adminName = mysqli_real_escape_string($con, trim($_POST['admin_name']));
        $userName = mysqli_real_escape_string($con, trim($_POST['username']));
        $mobileNumber = mysqli_real_escape_string($con, trim($_POST['mobile_number']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));

        if ($adminName === '' || $userName === '') {
            panel_json_response(false, 'Admin name and username are required.');
        }

        $existingAdminQuery = mysqli_query($con, "SELECT ID FROM tbladmin WHERE ID = '{$id}' LIMIT 1");
        if (!$existingAdminQuery || mysqli_num_rows($existingAdminQuery) === 0) {
            panel_json_response(false, 'Admin account not found.');
        }

        $duplicateQuery = mysqli_query($con, "SELECT ID FROM tbladmin WHERE UserName = '{$userName}' AND ID != '{$id}' LIMIT 1");
        if ($duplicateQuery && mysqli_num_rows($duplicateQuery) > 0) {
            panel_json_response(false, 'That username is already in use.');
        }

        if ($email !== '') {
            $duplicateEmailQuery = mysqli_query($con, "SELECT ID FROM tbladmin WHERE Email = '{$email}' AND ID != '{$id}' LIMIT 1");
            if ($duplicateEmailQuery && mysqli_num_rows($duplicateEmailQuery) > 0) {
                panel_json_response(false, 'That email address is already in use.');
            }
        }

        $updateQuery = mysqli_query(
            $con,
            "UPDATE tbladmin SET AdminName = '{$adminName}', UserName = '{$userName}', MobileNumber = '{$mobileNumber}', Email = '{$email}' WHERE ID = '{$id}'"
        );

        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the admin account.');
        }

        $adminResult = mysqli_query($con, "SELECT * FROM tbladmin WHERE ID = '{$id}'");
        $adminRow = mysqli_fetch_assoc($adminResult);

        panel_json_response(true, 'Admin account updated.', array(
            'row_html' => render_admin_row($adminRow, $currentAdminId),
            'record_id' => $id,
        ));
    }

    if ($action === 'delete_admin') {
        $id = (int) $_POST['id'];

        if ($id === $currentAdminId) {
            panel_json_response(false, 'You cannot delete the account you are signed in with.');
        }

        $adminCountResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM tbladmin");
        $adminCountRow = mysqli_fetch_assoc($adminCountResult);
        if ((int) $adminCountRow['total'] <= 1) {
            panel_json_response(false, 'At least one admin account must remain.');
        }

        $deleteQuery = mysqli_query($con, "DELETE FROM tbladmin WHERE ID = '{$id}'");
        if (!$deleteQuery || mysqli_affected_rows($con) === 0) {
            panel_json_response(false, 'Unable to delete the admin account.');
        }

        panel_json_response(true, 'Admin account deleted.', array('record_id' => $id));
    }
}

$adminResult = mysqli_query($con, "SELECT * FROM tbladmin ORDER BY ID DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Admin Management</title>
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
                            <h3 class="title1">Admin Management</h3>
                            <p>Add, edit, and remove admin accounts from the same workspace.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#adminCreateModal">
                            <i class="fa fa-plus"></i> Add Admin
                        </button>
                    </div>
                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="adminTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($adminResult)) { ?>
                                    <?php echo render_admin_row($row, $currentAdminId); ?>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once('includes/footer.php'); ?>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title text-danger"><i class="fa fa-exclamation-triangle"></i> Confirm Delete</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <p id="deleteConfirmMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
                        <p class="text-muted"><small id="deleteConfirmWarning"></small></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="adminCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Admin</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_admin">
                        <div class="form-group col-md-12">
                            <div class="alert alert-info" style="margin-bottom: 0;">
                                New admin accounts start with the default password <strong><?php echo panel_escape($defaultAdminPassword); ?></strong>.
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Admin Name</label>
                            <input type="text" class="form-control" name="admin_name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mobile Number</label>
                            <input type="text" class="form-control" name="mobile_number">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email Address</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Create Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="adminEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Admin</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_admin">
                        <input type="hidden" name="id" id="adminEditId">
                        <div class="form-group col-md-6">
                            <label>Admin Name</label>
                            <input type="text" class="form-control" name="admin_name" id="adminEditName" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" id="adminEditUsername" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Mobile Number</label>
                            <input type="text" class="form-control" name="mobile_number" id="adminEditMobile">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email Address</label>
                            <input type="email" class="form-control" name="email" id="adminEditEmail">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
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
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.bootstrap.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.2/js/buttons.bootstrap.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.2/js/buttons.colVis.min.js"></script>
    <script>
        var pendingDeleteCallback = null;

        function showDeleteConfirm(message, warning, callback) {
            document.getElementById('deleteConfirmMessage').textContent = message;
            document.getElementById('deleteConfirmWarning').textContent = warning || '';
            pendingDeleteCallback = callback;
            $('#deleteConfirmModal').modal('show');
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            $('#deleteConfirmModal').modal('hide');
            if (pendingDeleteCallback) {
                pendingDeleteCallback();
                pendingDeleteCallback = null;
            }
        });

        var adminTable = new DataTable('#adminTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#adminTable');

        document.getElementById('adminCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-admins.php', new FormData(form), {
                loadingText: 'Creating admin account...'
            });
            adminTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#adminTable');
            form.reset();
            panelApp.closeModal('#adminCreateModal');
        });

        document.getElementById('adminEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-admins.php', new FormData(form), {
                loadingText: 'Updating admin account...'
            });
            adminTable.row('#admin-row-' + payload.record_id).remove().draw(false);
            adminTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#adminTable');
            panelApp.closeModal('#adminEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-admin');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_admin');
                editData.append('id', editButton.getAttribute('data-id'));
                panelApp.postForm('manage-admins.php', editData, {
                    loadingText: 'Loading admin account...',
                    successMessage: false
                }).then(function (payload) {
                    document.getElementById('adminEditId').value = payload.record.ID;
                    document.getElementById('adminEditName').value = payload.record.AdminName || '';
                    document.getElementById('adminEditUsername').value = payload.record.UserName || '';
                    document.getElementById('adminEditMobile').value = payload.record.MobileNumber || '';
                    document.getElementById('adminEditEmail').value = payload.record.Email || '';
                    panelApp.openModal('#adminEditModal');
                });
                return;
            }

            var deleteButton = event.target.closest('.js-delete-admin');
            if (!deleteButton || deleteButton.hasAttribute('disabled')) {
                return;
            }

            var adminId = deleteButton.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this admin account?',
                'This action cannot be undone.',
                function() {
                    var deleteData = new FormData();
                    deleteData.append('ajax_action', 'delete_admin');
                    deleteData.append('id', adminId);

                    panelApp.postForm('manage-admins.php', deleteData, {
                        loadingText: 'Deleting admin account...'
                    }).then(function (payload) {
                        adminTable.row('#admin-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#adminTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
