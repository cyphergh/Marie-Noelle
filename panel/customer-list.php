<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_customer_row($row)
{
    $id = (int) $row['ID'];
    ob_start();
    ?>
    <tr id="customer-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td>
            <strong><?php echo panel_escape($row['Name']); ?></strong><br>
            <small><?php echo panel_escape($row['Email']); ?></small>
        </td>
        <td><?php echo panel_escape($row['MobileNumber']); ?></td>
        <td><?php echo panel_escape($row['Gender']); ?></td>
        <td><?php echo panel_format_date($row['CreationDate']); ?></td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-customer" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-customer" data-id="<?php echo $id; ?>">
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

    if ($action === 'get_customer') {
        $id = (int) $_POST['id'];
        $customerResult = mysqli_query($con, "SELECT * FROM tblcustomers WHERE ID = '{$id}'");
        $customerRow = mysqli_fetch_assoc($customerResult);
        if (!$customerRow) {
            panel_json_response(false, 'Customer not found.');
        }
        panel_json_response(true, 'Customer loaded.', array('record' => $customerRow));
    }

    if ($action === 'update_customer') {
        $id = (int) $_POST['id'];
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $email = mysqli_real_escape_string($con, trim($_POST['email']));
        $mobile = mysqli_real_escape_string($con, trim($_POST['mobilenum']));
        $gender = mysqli_real_escape_string($con, trim($_POST['gender']));

        if ($name === '' || $email === '' || $mobile === '') {
            panel_json_response(false, 'Please complete the customer form.');
        }

        $updateQuery = mysqli_query($con, "UPDATE tblcustomers SET Name = '{$name}', Email = '{$email}', MobileNumber = '{$mobile}', Gender = '{$gender}' WHERE ID = '{$id}'");
        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the customer.');
        }

        $customerResult = mysqli_query($con, "SELECT * FROM tblcustomers WHERE ID = '{$id}'");
        $customerRow = mysqli_fetch_assoc($customerResult);
        panel_json_response(true, 'Customer updated successfully.', array('row_html' => render_customer_row($customerRow), 'record_id' => $id));
    }

    if ($action === 'delete_customer') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM tblcustomers WHERE ID = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the customer.');
        }

        panel_json_response(true, 'Customer deleted.', array('record_id' => $id));
    }
}

$customersResult = mysqli_query($con, "SELECT * FROM tblcustomers ORDER BY ID DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Customers</title>
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
                            <h3 class="title1">Customers</h3>
                            <p>Add guests in a modal and keep the client list live without page refreshes.</p>
                        </div>
                        
                    </div>

                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="customersTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Mobile</th>
                                    <th>Gender</th>
                                    <th>Added</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($customerRow = mysqli_fetch_assoc($customersResult)) { ?>
                                    <?php echo render_customer_row($customerRow); ?>
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

    <div class="modal fade" id="customerEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="customerEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Customer</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_customer">
                        <input type="hidden" name="id" id="customerEditId">
                        <div class="form-group col-md-6"><label>Name</label><input type="text" class="form-control" name="name" id="customerEditName" required></div>
                        <div class="form-group col-md-6"><label>Email</label><input type="email" class="form-control" name="email" id="customerEditEmail" required></div>
                        <div class="form-group col-md-6"><label>Mobile Number</label><input type="text" class="form-control" name="mobilenum" id="customerEditMobile" maxlength="10" pattern="[0-9]+" required></div>
                        <!--<div class="form-group col-md-6"><label>DOB</label><input type="date" class="form-control" name="dob" id="customerEditDob"></div>-->
                        <!--<div class="form-group col-md-6"><label>Anniversary Date</label><input type="date" class="form-control" name="marriage_date" id="customerEditMarriageDate"></div>-->
                        <div class="form-group col-md-6"><label>Gender</label><select class="form-control" name="gender" id="customerEditGender"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option><option value="Transgender">Transgender</option></select></div>
                        <!--<div class="form-group col-md-12"><label>Note</label><textarea class="form-control" name="details" id="customerEditDetails" rows="4"></textarea></div>-->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Customer</button>
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

        var customersTable = new DataTable('#customersTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#customersTable');

        document.getElementById('customerEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('customer-list.php', new FormData(form), { loadingText: 'Updating customer...' });
            customersTable.row('#customer-row-' + payload.record_id).remove().draw(false);
            customersTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#customersTable');
            panelApp.closeModal('#customerEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-customer');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_customer');
                editData.append('id', editButton.getAttribute('data-id'));
                panelApp.postForm('customer-list.php', editData, { loadingText: 'Loading customer...', successMessage: false }).then(function (payload) {
                    document.getElementById('customerEditId').value = payload.record.ID;
                    document.getElementById('customerEditName').value = payload.record.Name || '';
                    document.getElementById('customerEditEmail').value = payload.record.Email || '';
                    document.getElementById('customerEditMobile').value = payload.record.MobileNumber || '';
                    document.getElementById('customerEditDob').value = payload.record.dob || '';
                    document.getElementById('customerEditMarriageDate').value = payload.record.marriage_date && payload.record.marriage_date !== '0000-00-00' ? payload.record.marriage_date : '';
                    document.getElementById('customerEditGender').value = payload.record.Gender || '';
                    document.getElementById('customerEditDetails').value = payload.record.Details || '';
                    panelApp.openModal('#customerEditModal');
                });
                return;
            }

            var button = event.target.closest('.js-delete-customer');
            if (!button) {
                return;
            }

            var customerId = button.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this customer?',
                'All related appointments will also be deleted.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_customer');
                    formData.append('id', customerId);

                    panelApp.postForm('customer-list.php', formData, {
                        loadingText: 'Deleting customer...'
                    }).then(function (payload) {
                        customersTable.row('#customer-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#customersTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
