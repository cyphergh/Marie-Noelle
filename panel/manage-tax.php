<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_tax_row($row)
{
    $id = (int) $row['id'];
    ob_start();
    ?>
    <tr id="tax-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td><?php echo panel_escape($row['name']); ?></td>
        <td><?php echo panel_escape($row['value']); ?>%</td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-tax" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-tax" data-id="<?php echo $id; ?>">
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

    if ($action === 'create_tax') {
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $value = mysqli_real_escape_string($con, trim($_POST['value']));

        if ($name === '' || $value === '') {
            panel_json_response(false, 'Please complete the tax form.');
        }

        $insertQuery = mysqli_query($con, "INSERT INTO tbl_tax(name, value, delete_status) VALUES ('{$name}', '{$value}', '0')");

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to add the tax.');
        }

        $newId = mysqli_insert_id($con);
        $taxResult = mysqli_query($con, "SELECT * FROM tbl_tax WHERE id = '{$newId}'");
        $taxRow = mysqli_fetch_assoc($taxResult);

        panel_json_response(true, 'Tax added successfully.', array(
            'row_html' => render_tax_row($taxRow),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_tax') {
        $id = (int) $_POST['id'];
        $taxResult = mysqli_query($con, "SELECT * FROM tbl_tax WHERE id = '{$id}'");
        $taxRow = mysqli_fetch_assoc($taxResult);
        if (!$taxRow) {
            panel_json_response(false, 'Tax not found.');
        }
        panel_json_response(true, 'Tax loaded.', array('record' => $taxRow));
    }

    if ($action === 'update_tax') {
        $id = (int) $_POST['id'];
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $value = mysqli_real_escape_string($con, trim($_POST['value']));
        if ($name === '' || $value === '') {
            panel_json_response(false, 'Please complete the tax form.');
        }
        $updateQuery = mysqli_query($con, "UPDATE tbl_tax SET name = '{$name}', value = '{$value}' WHERE id = '{$id}'");
        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the tax.');
        }
        $taxResult = mysqli_query($con, "SELECT * FROM tbl_tax WHERE id = '{$id}'");
        $taxRow = mysqli_fetch_assoc($taxResult);
        panel_json_response(true, 'Tax updated.', array('row_html' => render_tax_row($taxRow), 'record_id' => $id));
    }

    if ($action === 'delete_tax') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM tbl_tax WHERE id = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the tax.');
        }

        panel_json_response(true, 'Tax deleted.', array('record_id' => $id));
    }
}

$taxResult = mysqli_query($con, "SELECT * FROM tbl_tax ORDER BY id DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Tax</title>
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
                            <h3 class="title1">Tax</h3>
                            <p>Keep tax rates in one place and add new rules through a modal flow.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#taxCreateModal">
                            <i class="fa fa-plus"></i> Add Tax
                        </button>
                    </div>
                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="taxTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tax Name</th>
                                    <th>Value</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($taxResult)) { ?>
                                    <?php echo render_tax_row($row); ?>
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

    <div class="modal fade" id="taxEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="taxEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Tax</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_tax">
                        <input type="hidden" name="id" id="taxEditId">
                        <div class="form-group col-md-6">
                            <label>Tax Name</label>
                            <input type="text" class="form-control" name="name" id="taxEditName" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Value (%)</label>
                            <input type="number" class="form-control" name="value" id="taxEditValue" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Tax</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="taxCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="taxCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Tax</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_tax">
                        <div class="form-group col-md-6">
                            <label>Tax Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Value (%)</label>
                            <input type="number" class="form-control" name="value" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Tax</button>
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

        var taxTable = new DataTable('#taxTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#taxTable');

        document.getElementById('taxCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-tax.php', new FormData(form), {
                loadingText: 'Adding tax...'
            });
            taxTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#taxTable');
            form.reset();
            panelApp.closeModal('#taxCreateModal');
        });

        document.getElementById('taxEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-tax.php', new FormData(form), { loadingText: 'Updating tax...' });
            taxTable.row('#tax-row-' + payload.record_id).remove().draw(false);
            taxTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#taxTable');
            panelApp.closeModal('#taxEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-tax');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_tax');
                editData.append('id', editButton.getAttribute('data-id'));
                panelApp.postForm('manage-tax.php', editData, { loadingText: 'Loading tax...', successMessage: false }).then(function (payload) {
                    document.getElementById('taxEditId').value = payload.record.id;
                    document.getElementById('taxEditName').value = payload.record.name || '';
                    document.getElementById('taxEditValue').value = payload.record.value || '';
                    panelApp.openModal('#taxEditModal');
                });
                return;
            }

            var button = event.target.closest('.js-delete-tax');
            if (!button) {
                return;
            }

            var taxId = button.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this tax?',
                'This action cannot be undone.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_tax');
                    formData.append('id', taxId);

                    panelApp.postForm('manage-tax.php', formData, {
                        loadingText: 'Deleting tax...'
                    }).then(function (payload) {
                        taxTable.row('#tax-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#taxTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
