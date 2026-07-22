<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_subscription_row($row)
{
    $id = (int) $row['id'];
    $isActive = $row['status'] === 'active';
    ob_start();
    ?>
    <tr id="subscription-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td><?php echo panel_escape($row['customer_name']); ?></td>
        <td><?php echo panel_escape($row['plan_name']); ?></td>
        <td><?php echo panel_format_date($row['start_date']); ?></td>
        <td><?php echo panel_format_date($row['end_date']); ?></td>
        <td>
            <span class="status-chip <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>">
                <?php echo panel_escape($row['status']); ?>
            </span>
        </td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-subscription" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-subscription" data-id="<?php echo $id; ?>">
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php
    return trim(ob_get_clean());
}

function fetch_subscription_row($con, $id)
{
    $query = mysqli_query(
        $con,
        "SELECT um.*, c.Name AS customer_name, mp.plan_name
         FROM user_memberships um
         LEFT JOIN tblcustomers c ON c.ID = um.user_id
         LEFT JOIN membership_plans mp ON mp.id = um.plan_id
         WHERE um.id = '{$id}'"
    );

    return mysqli_fetch_assoc($query);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && panel_is_ajax_request()) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

    if ($action === 'create_subscription') {
        $userId = (int) $_POST['user_id'];
        $planId = (int) $_POST['plan_id'];

        if (!$userId || !$planId) {
            panel_json_response(false, 'Please choose a customer and a plan.');
        }

        $planResult = mysqli_query($con, "SELECT duration_days FROM membership_plans WHERE id = '{$planId}'");
        $plan = mysqli_fetch_assoc($planResult);

        if (!$plan) {
            panel_json_response(false, 'Selected plan could not be found.');
        }

        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+' . (int) $plan['duration_days'] . ' days'));

        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO user_memberships (user_id, plan_id, start_date, end_date, status) VALUES ('{$userId}', '{$planId}', '{$start}', '{$end}', 'active')"
        );

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to create the subscription.');
        }

        $newId = mysqli_insert_id($con);
        $row = fetch_subscription_row($con, $newId);

        panel_json_response(true, 'Subscription added successfully.', array(
            'row_html' => render_subscription_row($row),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_subscription') {
        $id = (int) $_POST['id'];
        $row = fetch_subscription_row($con, $id);
        if (!$row) {
            panel_json_response(false, 'Subscription not found.');
        }
        panel_json_response(true, 'Subscription loaded.', array('record' => $row));
    }

    if ($action === 'update_subscription') {
        $id = (int) $_POST['id'];
        $userId = (int) $_POST['user_id'];
        $planId = (int) $_POST['plan_id'];
        if (!$userId || !$planId) {
            panel_json_response(false, 'Please choose a customer and a plan.');
        }
        $planResult = mysqli_query($con, "SELECT duration_days FROM membership_plans WHERE id = '{$planId}'");
        $plan = mysqli_fetch_assoc($planResult);
        if (!$plan) {
            panel_json_response(false, 'Selected plan could not be found.');
        }
        $start = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+' . (int) $plan['duration_days'] . ' days'));
        $updateQuery = mysqli_query($con, "UPDATE user_memberships SET user_id = '{$userId}', plan_id = '{$planId}', start_date = '{$start}', end_date = '{$end}' WHERE id = '{$id}'");
        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the subscription.');
        }
        $row = fetch_subscription_row($con, $id);
        panel_json_response(true, 'Subscription updated successfully.', array('row_html' => render_subscription_row($row), 'record_id' => $id));
    }

    if ($action === 'delete_subscription') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM user_memberships WHERE id = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the subscription.');
        }

        panel_json_response(true, 'Subscription deleted.', array('record_id' => $id));
    }
}

$customers = mysqli_query($con, "SELECT ID, Name FROM tblcustomers ORDER BY Name ASC");
$plans = mysqli_query($con, "SELECT id, plan_name, price FROM membership_plans ORDER BY plan_name ASC");
$subscriptionResult = mysqli_query(
    $con,
    "SELECT um.*, c.Name AS customer_name, mp.plan_name
     FROM user_memberships um
     LEFT JOIN tblcustomers c ON c.ID = um.user_id
     LEFT JOIN membership_plans mp ON mp.id = um.plan_id
     ORDER BY um.id DESC"
);
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Subscriptions</title>
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
                            <h3 class="title1">Subscriptions</h3>
                            <p>Create subscriptions in a modal and keep the list updated in place.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#subscriptionCreateModal">
                            <i class="fa fa-plus"></i> Add Subscription
                        </button>
                    </div>
                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="subscriptionTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($subscriptionResult)) { ?>
                                    <?php echo render_subscription_row($row); ?>
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

    <div class="modal fade" id="subscriptionEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="subscriptionEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Subscription</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_subscription">
                        <input type="hidden" name="id" id="subscriptionEditId">
                        <div class="form-group col-md-6">
                            <label>Customer</label>
                            <select class="form-control" name="user_id" id="subscriptionEditUser" required>
                                <option value="">Select</option>
                                <?php
                                mysqli_data_seek($customers, 0);
                                while ($customer = mysqli_fetch_assoc($customers)) { ?>
                                    <option value="<?php echo $customer['ID']; ?>"><?php echo panel_escape($customer['Name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Plan</label>
                            <select class="form-control" name="plan_id" id="subscriptionEditPlan" required>
                                <option value="">Choose a Plan</option>
                                <?php
                                mysqli_data_seek($plans, 0);
                                while ($plan = mysqli_fetch_assoc($plans)) { ?>
                                    <option value="<?php echo $plan['id']; ?>">
                                        <?php echo panel_escape($plan['plan_name']); ?> - <?php echo number_format((float) $plan['price'], 2); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="subscriptionCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="subscriptionCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Subscription</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_subscription">
                        <div class="form-group col-md-6">
                            <label>Customer</label>
                            <select class="form-control" name="user_id" required>
                                <option value="">Select</option>
                                <?php while ($customer = mysqli_fetch_assoc($customers)) { ?>
                                    <option value="<?php echo $customer['ID']; ?>"><?php echo panel_escape($customer['Name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Plan</label>
                            <select class="form-control" name="plan_id" required>
                                <option value="">Choose a Plan</option>
                                <?php while ($plan = mysqli_fetch_assoc($plans)) { ?>
                                    <option value="<?php echo $plan['id']; ?>">
                                        <?php echo panel_escape($plan['plan_name']); ?> - <?php echo number_format((float) $plan['price'], 2); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Subscription</button>
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

        var subscriptionTable = new DataTable('#subscriptionTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#subscriptionTable');

        document.getElementById('subscriptionCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage_subscribe.php', new FormData(form), {
                loadingText: 'Creating subscription...'
            });
            subscriptionTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#subscriptionTable');
            form.reset();
            panelApp.closeModal('#subscriptionCreateModal');
        });

        document.getElementById('subscriptionEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage_subscribe.php', new FormData(form), { loadingText: 'Updating subscription...' });
            subscriptionTable.row('#subscription-row-' + payload.record_id).remove().draw(false);
            subscriptionTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#subscriptionTable');
            panelApp.closeModal('#subscriptionEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-subscription');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_subscription');
                editData.append('id', editButton.getAttribute('data-id'));
                panelApp.postForm('manage_subscribe.php', editData, { loadingText: 'Loading subscription...', successMessage: false }).then(function (payload) {
                    document.getElementById('subscriptionEditId').value = payload.record.id;
                    document.getElementById('subscriptionEditUser').value = payload.record.user_id || '';
                    document.getElementById('subscriptionEditPlan').value = payload.record.plan_id || '';
                    panelApp.openModal('#subscriptionEditModal');
                });
                return;
            }

            var button = event.target.closest('.js-delete-subscription');
            if (!button) {
                return;
            }

            var subId = button.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this subscription?',
                'This action cannot be undone.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_subscription');
                    formData.append('id', subId);

                    panelApp.postForm('manage_subscribe.php', formData, {
                        loadingText: 'Deleting subscription...'
                    }).then(function (payload) {
                        subscriptionTable.row('#subscription-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#subscriptionTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
