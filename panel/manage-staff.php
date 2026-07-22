<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_staff_row($row)
{
    $id = (int) $row['id'];
    $hasPassword = !empty($row['password']);
    ob_start();
    ?>
    <tr id="staff-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td>
            <strong><?php echo panel_escape($row['name']); ?></strong><br>
            <small><?php echo panel_escape($row['email']); ?></small><br>
            <small class="<?php echo $hasPassword ? 'text-success' : 'text-muted'; ?>">
                <?php echo $hasPassword ? 'Staff portal enabled' : 'No portal password yet'; ?>
            </small>
        </td>
        <td><?php echo panel_escape($row['contact']); ?></td>
        <td><?php echo panel_escape($row['address']); ?></td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-staff" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-staff" data-id="<?php echo $id; ?>">
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

    if ($action === 'create_staff') {
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $contact = mysqli_real_escape_string($con, trim($_POST['contact']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));
        $address = mysqli_real_escape_string($con, trim($_POST['address']));
        $passwordHash = md5('12345678');

        if ($name === '' || $contact === '' || $email === '' || $address === '') {
            panel_json_response(false, 'Please complete the staff form.');
        }

        $existingStaffResult = mysqli_query($con, "SELECT id FROM tbl_staff WHERE email = '{$email}' LIMIT 1");
        if ($existingStaffResult && mysqli_num_rows($existingStaffResult) > 0) {
            panel_json_response(false, 'That staff email is already in use.');
        }

        $insertQuery = mysqli_query($con, "INSERT INTO tbl_staff(name, contact, address, email, password) VALUES ('{$name}', '{$contact}', '{$address}', '{$email}', '{$passwordHash}')");

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to add the staff member.');
        }

        $newId = mysqli_insert_id($con);
        $staffResult = mysqli_query($con, "SELECT * FROM tbl_staff WHERE id = '{$newId}'");
        $staffRow = mysqli_fetch_assoc($staffResult);

        panel_json_response(true, 'Staff member added.', array(
            'row_html' => render_staff_row($staffRow),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_staff') {
        $id = (int) $_POST['id'];
        $staffResult = mysqli_query($con, "SELECT * FROM tbl_staff WHERE id = '{$id}'");
        $staffRow = mysqli_fetch_assoc($staffResult);

        if (!$staffRow) {
            panel_json_response(false, 'Staff member not found.');
        }

        panel_json_response(true, 'Staff member loaded.', array('record' => $staffRow));
    }

    if ($action === 'update_staff') {
        $id = (int) $_POST['id'];
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $contact = mysqli_real_escape_string($con, trim($_POST['contact']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));
        $address = mysqli_real_escape_string($con, trim($_POST['address']));
        $passwordRaw = trim(isset($_POST['password']) ? $_POST['password'] : '');

        if ($name === '' || $contact === '' || $email === '' || $address === '') {
            panel_json_response(false, 'Please complete the staff form.');
        }

        $existingStaffResult = mysqli_query($con, "SELECT id FROM tbl_staff WHERE email = '{$email}' AND id != '{$id}' LIMIT 1");
        if ($existingStaffResult && mysqli_num_rows($existingStaffResult) > 0) {
            panel_json_response(false, 'That staff email is already in use.');
        }

        $passwordSql = '';
        if ($passwordRaw !== '') {
            $passwordSql = ", password = '" . md5($passwordRaw) . "'";
        }

        $updateQuery = mysqli_query($con, "UPDATE tbl_staff SET name = '{$name}', email = '{$email}', contact = '{$contact}', address = '{$address}'{$passwordSql} WHERE id = '{$id}'");

        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the staff member.');
        }

        $staffResult = mysqli_query($con, "SELECT * FROM tbl_staff WHERE id = '{$id}'");
        $staffRow = mysqli_fetch_assoc($staffResult);

        panel_json_response(true, 'Staff member updated.', array(
            'row_html' => render_staff_row($staffRow),
            'record_id' => $id,
        ));
    }

    if ($action === 'delete_staff') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM tbl_staff WHERE id = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the staff member.');
        }

        panel_json_response(true, 'Staff member deleted.', array('record_id' => $id));
    }
}

$staffResult = mysqli_query($con, "SELECT * FROM tbl_staff ORDER BY id DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Staff</title>
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
                            <h3 class="title1">Staff</h3>
                            <p>Grow and maintain the team from one working page.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#staffCreateModal">
                            <i class="fa fa-plus"></i> Add Staff
                        </button>
                    </div>
                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="staffTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Staff</th>
                                    <th>Contact</th>
                                    <th>Address</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($staffResult)) { ?>
                                    <?php echo render_staff_row($row); ?>
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

    <div class="modal fade" id="staffCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="staffCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Staff Member</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_staff">
                        <div class="form-group col-md-12">
                            <div class="alert alert-info" style="margin-bottom: 0;">
                                New staff accounts use the default password <strong>12345678</strong> for staff portal login.
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Contact</label>
                            <input type="text" class="form-control" name="contact" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Address</label>
                            <input type="text" class="form-control" name="address" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Staff Member</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="staffEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="staffEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Staff Member</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_staff">
                        <input type="hidden" name="id" id="staffEditId">
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" id="staffEditName" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" id="staffEditEmail" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Contact</label>
                            <input type="text" class="form-control" name="contact" id="staffEditContact" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Address</label>
                            <input type="text" class="form-control" name="address" id="staffEditAddress" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label>New Password</label>
                            <input type="text" class="form-control" name="password" id="staffEditPassword" placeholder="Leave blank to keep the current password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Staff Member</button>
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

        var staffTable = new DataTable('#staffTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#staffTable');

        document.getElementById('staffCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-staff.php', new FormData(form), {
                loadingText: 'Adding staff member...'
            });
            staffTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#staffTable');
            form.reset();
            panelApp.closeModal('#staffCreateModal');
        });

        document.getElementById('staffEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-staff.php', new FormData(form), {
                loadingText: 'Updating staff member...'
            });
            staffTable.row('#staff-row-' + payload.record_id).remove().draw(false);
            staffTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#staffTable');
            panelApp.closeModal('#staffEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-staff');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_staff');
                editData.append('id', editButton.getAttribute('data-id'));

                panelApp.postForm('manage-staff.php', editData, {
                    loadingText: 'Loading staff member...',
                    successMessage: false
                }).then(function (payload) {
                    document.getElementById('staffEditId').value = payload.record.id;
                    document.getElementById('staffEditName').value = payload.record.name || '';
                    document.getElementById('staffEditEmail').value = payload.record.email || '';
                    document.getElementById('staffEditContact').value = payload.record.contact || '';
                    document.getElementById('staffEditAddress').value = payload.record.address || '';
                    document.getElementById('staffEditPassword').value = '';
                    panelApp.openModal('#staffEditModal');
                });
                return;
            }

            var button = event.target.closest('.js-delete-staff');
            if (!button) {
                return;
            }

            var staffId = button.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this staff member?',
                'This action cannot be undone.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_staff');
                    formData.append('id', staffId);

                    panelApp.postForm('manage-staff.php', formData, {
                        loadingText: 'Deleting staff member...'
                    }).then(function (payload) {
                        staffTable.row('#staff-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#staffTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
