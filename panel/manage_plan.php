<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_plan_row($row)
{
    $id = (int) $row['id'];
    ob_start();
    ?>
    <tr id="plan-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td>
            <strong><?php echo panel_escape($row['plan_name']); ?></strong><br>
            <small><?php echo panel_escape($row['description']); ?></small>
        </td>
        <td><?php echo number_format((float) $row['price'], 2); ?></td>
        <td><?php echo (int) $row['duration_days']; ?> days</td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-plan" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-plan" data-id="<?php echo $id; ?>">
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

    if ($action === 'create_plan') {
        $planName = mysqli_real_escape_string($con, trim($_POST['plan_name']));
        $description = mysqli_real_escape_string($con, trim($_POST['description']));
        $durationDays = (int) $_POST['duration_days'];
        $price = (float) $_POST['price'];

        if ($planName === '' || $description === '' || $durationDays <= 0) {
            panel_json_response(false, 'Please complete the membership plan form.');
        }

        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO membership_plans (plan_name, description, duration_days, price) VALUES ('{$planName}', '{$description}', '{$durationDays}', '{$price}')"
        );

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to create the plan.');
        }

        $newId = mysqli_insert_id($con);
        $planResult = mysqli_query($con, "SELECT * FROM membership_plans WHERE id = '{$newId}'");
        $planRow = mysqli_fetch_assoc($planResult);

        panel_json_response(true, 'Plan created successfully.', array(
            'row_html' => render_plan_row($planRow),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_plan') {
        $id = (int) $_POST['id'];
        $planResult = mysqli_query($con, "SELECT * FROM membership_plans WHERE id = '{$id}'");
        $planRow = mysqli_fetch_assoc($planResult);
        if (!$planRow) {
            panel_json_response(false, 'Plan not found.');
        }
        panel_json_response(true, 'Plan loaded.', array('record' => $planRow));
    }

    if ($action === 'update_plan') {
        $id = (int) $_POST['id'];
        $planName = mysqli_real_escape_string($con, trim($_POST['plan_name']));
        $description = mysqli_real_escape_string($con, trim($_POST['description']));
        $durationDays = (int) $_POST['duration_days'];
        $price = (float) $_POST['price'];
        if ($planName === '' || $description === '' || $durationDays <= 0) {
            panel_json_response(false, 'Please complete the membership plan form.');
        }
        $updateQuery = mysqli_query($con, "UPDATE membership_plans SET plan_name = '{$planName}', duration_days = '{$durationDays}', description = '{$description}', price = '{$price}' WHERE id = '{$id}'");
        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the plan.');
        }
        $planResult = mysqli_query($con, "SELECT * FROM membership_plans WHERE id = '{$id}'");
        $planRow = mysqli_fetch_assoc($planResult);
        panel_json_response(true, 'Plan updated successfully.', array('row_html' => render_plan_row($planRow), 'record_id' => $id));
    }

    if ($action === 'delete_plan') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM membership_plans WHERE id = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the plan.');
        }

        panel_json_response(true, 'Plan deleted.', array('record_id' => $id));
    }
}

$planResult = mysqli_query($con, "SELECT * FROM membership_plans ORDER BY id DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Plans</title>
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
                            <h3 class="title1">Membership Plans</h3>
                            <p>Create and manage membership offers from a single page.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#planCreateModal">
                            <i class="fa fa-plus"></i> Add Plan
                        </button>
                    </div>
                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="planTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($planResult)) { ?>
                                    <?php echo render_plan_row($row); ?>
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

    <div class="modal fade" id="planEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="planEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Membership Plan</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_plan">
                        <input type="hidden" name="id" id="planEditId">
                        <div class="form-group col-md-6"><label>Name</label><input type="text" class="form-control" name="plan_name" id="planEditName" required></div>
                        <div class="form-group col-md-6"><label>Price</label><input type="number" class="form-control" name="price" id="planEditPrice" step="0.01" min="0" required></div>
                        <div class="form-group col-md-6"><label>Duration</label><input type="number" class="form-control" name="duration_days" id="planEditDuration" min="1" required></div>
                        <div class="form-group col-md-12"><label>Description</label><textarea class="form-control" name="description" id="planEditDescription" rows="4" required></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="planCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="planCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Membership Plan</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_plan">
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" class="form-control" name="plan_name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Price</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Duration</label>
                            <input type="number" class="form-control" name="duration_days" min="1" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Plan</button>
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

        var planTable = new DataTable('#planTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#planTable');

        document.getElementById('planCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage_plan.php', new FormData(form), {
                loadingText: 'Creating plan...'
            });
            planTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#planTable');
            form.reset();
            panelApp.closeModal('#planCreateModal');
        });

        document.getElementById('planEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage_plan.php', new FormData(form), { loadingText: 'Updating plan...' });
            planTable.row('#plan-row-' + payload.record_id).remove().draw(false);
            planTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#planTable');
            panelApp.closeModal('#planEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-plan');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_plan');
                editData.append('id', editButton.getAttribute('data-id'));
                panelApp.postForm('manage_plan.php', editData, { loadingText: 'Loading plan...', successMessage: false }).then(function (payload) {
                    document.getElementById('planEditId').value = payload.record.id;
                    document.getElementById('planEditName').value = payload.record.plan_name || '';
                    document.getElementById('planEditPrice').value = payload.record.price || '';
                    document.getElementById('planEditDuration').value = payload.record.duration_days || '';
                    document.getElementById('planEditDescription').value = payload.record.description || '';
                    panelApp.openModal('#planEditModal');
                });
                return;
            }

            var button = event.target.closest('.js-delete-plan');
            if (!button) {
                return;
            }

            var planId = button.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this membership plan?',
                'Active subscriptions will be affected.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_plan');
                    formData.append('id', planId);

                    panelApp.postForm('manage_plan.php', formData, {
                        loadingText: 'Deleting plan...'
                    }).then(function (payload) {
                        planTable.row('#plan-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#planTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
