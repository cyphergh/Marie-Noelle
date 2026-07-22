<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_service_row($row, $categories)
{
    $id = (int) $row['ID'];
    $type = (int) $row['type'];
    $typeLabel = $type === 1 ? 'Product' : 'Service';
    $stockLabel = $type === 1 ? (int) $row['opening_stock'] : 'No Stock';
    $categoryName = isset($categories[$row['cate_id']]) ? $categories[$row['cate_id']] : 'Unassigned';

    ob_start();
    ?>
    <tr id="service-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td>
            <strong><?php echo panel_escape($row['ServiceName']); ?></strong><br>
            <small><?php echo panel_escape($categoryName); ?></small>
        </td>
        <td><span class="entity-pill"><?php echo panel_escape($typeLabel); ?></span></td>
        <td><?php echo number_format((float) $row['Cost'], 2); ?></td>
        <td class="js-stock-value"><?php echo panel_escape($stockLabel); ?></td>
        <td><?php echo panel_format_date($row['CreationDate']); ?></td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-service" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-service" data-id="<?php echo $id; ?>">
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                </button>
                <?php if ($type === 1) { ?>
                    <button
                        type="button"
                        class="btn btn-warning btn-sm js-stock-service"
                        data-id="<?php echo $id; ?>"
                        data-name="<?php echo panel_escape($row['ServiceName']); ?>"
                        data-stock="<?php echo (int) $row['opening_stock']; ?>"
                    >
                        <i class="fa fa-plus" aria-hidden="true"></i>
                    </button>
                <?php } ?>
            </div>
        </td>
    </tr>
    <?php
    return trim(ob_get_clean());
}

$categoriesResult = mysqli_query($con, "SELECT id, name FROM tbl_category ORDER BY name ASC");
$categories = array();
while ($categoryRow = mysqli_fetch_assoc($categoriesResult)) {
    $categories[$categoryRow['id']] = $categoryRow['name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && panel_is_ajax_request()) {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

    if ($action === 'create_service') {
        $name = mysqli_real_escape_string($con, trim($_POST['sername']));
        $description = mysqli_real_escape_string($con, trim($_POST['des']));
        $cost = (float) $_POST['cost'];
        $cateId = (int) $_POST['cate_id'];
        $type = (int) $_POST['type'];

        if ($name === '' || !$cateId || !in_array($type, array(1, 2), true)) {
            panel_json_response(false, 'Please complete the service form.');
        }

        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO tblservices(ServiceName, Description, Cost, type, cate_id) VALUES ('{$name}', '{$description}', '{$cost}', '{$type}', '{$cateId}')"
        );

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to create the service right now.');
        }

        $newId = mysqli_insert_id($con);
        $serviceResult = mysqli_query($con, "SELECT * FROM tblservices WHERE ID = '{$newId}'");
        $serviceRow = mysqli_fetch_assoc($serviceResult);

        panel_json_response(true, 'Service/Product created successfully.', array(
            'row_html' => render_service_row($serviceRow, $categories),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_service') {
        $id = (int) $_POST['id'];
        $serviceResult = mysqli_query($con, "SELECT * FROM tblservices WHERE ID = '{$id}'");
        $serviceRow = mysqli_fetch_assoc($serviceResult);

        if (!$serviceRow) {
            panel_json_response(false, 'Service/Product not found.');
        }

        panel_json_response(true, 'Service/Product loaded.', array('record' => $serviceRow));
    }

    if ($action === 'update_service') {
        $id = (int) $_POST['id'];
        $name = mysqli_real_escape_string($con, trim($_POST['sername']));
        $description = mysqli_real_escape_string($con, trim($_POST['des']));
        $cost = (float) $_POST['cost'];
        $cateId = (int) $_POST['cate_id'];
        $type = (int) $_POST['type'];

        if ($name === '' || !$cateId || !in_array($type, array(1, 2), true)) {
            panel_json_response(false, 'Please complete the service form.');
        }

        $updateQuery = mysqli_query($con, "UPDATE tblservices SET ServiceName = '{$name}', Description = '{$description}', Cost = '{$cost}', type = '{$type}', cate_id = '{$cateId}' WHERE ID = '{$id}'");
        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the service/product.');
        }

        $serviceResult = mysqli_query($con, "SELECT * FROM tblservices WHERE ID = '{$id}'");
        $serviceRow = mysqli_fetch_assoc($serviceResult);

        panel_json_response(true, 'Service/Product updated successfully.', array(
            'row_html' => render_service_row($serviceRow, $categories),
            'record_id' => $id,
        ));
    }

    if ($action === 'delete_service') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM tblservices WHERE ID = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the service.');
        }

        panel_json_response(true, 'Service/Product deleted.', array('record_id' => $id));
    }

    if ($action === 'add_stock') {
        $id = (int) $_POST['id'];
        $stock = (int) $_POST['opening_stock'];

        $currentResult = mysqli_query($con, "SELECT opening_stock FROM tblservices WHERE ID = '{$id}' AND type = 1");
        $currentRow = mysqli_fetch_assoc($currentResult);

        if (!$currentRow) {
            panel_json_response(false, 'Product not found for stock update.');
        }

        $newStock = (int) $currentRow['opening_stock'] + max(0, $stock);
        $updateQuery = mysqli_query($con, "UPDATE tblservices SET opening_stock = '{$newStock}' WHERE ID = '{$id}'");

        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update stock.');
        }

        $serviceResult = mysqli_query($con, "SELECT * FROM tblservices WHERE ID = '{$id}'");
        $serviceRow = mysqli_fetch_assoc($serviceResult);

        panel_json_response(true, 'Stock updated successfully.', array(
            'row_html' => render_service_row($serviceRow, $categories),
            'record_id' => $id,
        ));
    }
}

$servicesResult = mysqli_query($con, "SELECT * FROM tblservices ORDER BY ID DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Services & Products</title>
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
                            <h3 class="title1">Services & Products</h3>
                            <p>Manage the catalog here and add new services without leaving the list.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#serviceCreateModal">
                            <i class="fa fa-plus"></i> Add Service/Product
                        </button>
                    </div>

                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="servicesTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Service/Product</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($serviceRow = mysqli_fetch_assoc($servicesResult)) { ?>
                                    <?php echo render_service_row($serviceRow, $categories); ?>
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

    <div class="modal fade" id="serviceCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="serviceCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Add Service or Product</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_service">
                        <div class="form-group col-md-6">
                            <label>Type</label>
                            <select class="form-control" name="type" required>
                                <option value="">Select</option>
                                <option value="1">Product</option>
                                <option value="2">Service</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Category</label>
                            <select class="form-control" name="cate_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $categoryId => $categoryName) { ?>
                                    <option value="<?php echo $categoryId; ?>"><?php echo panel_escape($categoryName); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" class="form-control" name="sername" placeholder="Name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Cost</label>
                            <input type="number" class="form-control" name="cost" placeholder="Cost" step="0.01" min="0" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" name="des" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serviceStockModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="serviceStockForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Update Product Stock</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="add_stock">
                        <input type="hidden" name="id" id="serviceStockId">
                        <div class="form-group col-md-12">
                            <label>Product</label>
                            <input type="text" class="form-control" id="serviceStockName" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Current Stock</label>
                            <input type="text" class="form-control" id="serviceStockCurrent" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Add Stock</label>
                            <input type="number" class="form-control" name="opening_stock" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serviceEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="serviceEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">Edit Service or Product</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_service">
                        <input type="hidden" name="id" id="serviceEditId">
                        <div class="form-group col-md-6">
                            <label>Type</label>
                            <select class="form-control" name="type" id="serviceEditType" required>
                                <option value="">Select</option>
                                <option value="1">Product</option>
                                <option value="2">Service</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Category</label>
                            <select class="form-control" name="cate_id" id="serviceEditCategory" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $categoryId => $categoryName) { ?>
                                    <option value="<?php echo $categoryId; ?>"><?php echo panel_escape($categoryName); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Name</label>
                            <input type="text" class="form-control" name="sername" id="serviceEditName" placeholder="Name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Cost</label>
                            <input type="number" class="form-control" name="cost" id="serviceEditCost" placeholder="Cost" step="0.01" min="0" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" name="des" id="serviceEditDescription" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Item</button>
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

        var servicesTable = new DataTable('#servicesTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#servicesTable');

        function replaceServiceRow(rowHtml, recordId) {
            servicesTable.row('#service-row-' + recordId).remove().draw(false);
            servicesTable.row.add($(rowHtml)[0]).draw(false);
            panelApp.renumberTableRows('#servicesTable');
        }

        document.getElementById('serviceCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-services.php', new FormData(form), {
                loadingText: 'Creating service...'
            });

            servicesTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#servicesTable');
            form.reset();
            panelApp.closeModal('#serviceCreateModal');
        });

        document.getElementById('serviceEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-services.php', new FormData(form), {
                loadingText: 'Updating service...'
            });

            replaceServiceRow(payload.row_html, payload.record_id);
            panelApp.closeModal('#serviceEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-service');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_service');
                editData.append('id', editButton.getAttribute('data-id'));

                panelApp.postForm('manage-services.php', editData, {
                    loadingText: 'Loading item...',
                    successMessage: false
                }).then(function (payload) {
                    document.getElementById('serviceEditId').value = payload.record.ID;
                    document.getElementById('serviceEditType').value = payload.record.type || '';
                    document.getElementById('serviceEditCategory').value = payload.record.cate_id || '';
                    document.getElementById('serviceEditName').value = payload.record.ServiceName || '';
                    document.getElementById('serviceEditCost').value = payload.record.Cost || '';
                    document.getElementById('serviceEditDescription').value = payload.record.Description || '';
                    panelApp.openModal('#serviceEditModal');
                });
                return;
            }

            var deleteButton = event.target.closest('.js-delete-service');
            if (deleteButton) {
                var serviceId = deleteButton.getAttribute('data-id');
                var serviceName = deleteButton.getAttribute('data-name') || 'this service/product';
                
                showDeleteConfirm(
                    'Are you sure you want to delete ' + serviceName + '?',
                    'This action cannot be undone.',
                    function() {
                        var formData = new FormData();
                        formData.append('ajax_action', 'delete_service');
                        formData.append('id', serviceId);

                        panelApp.postForm('manage-services.php', formData, {
                            loadingText: 'Deleting...'
                        }).then(function (payload) {
                            servicesTable.row('#service-row-' + payload.record_id).remove().draw(false);
                            panelApp.renumberTableRows('#servicesTable');
                        });
                    }
                );
                return;
            }

            var stockButton = event.target.closest('.js-stock-service');
            if (stockButton) {
                document.getElementById('serviceStockId').value = stockButton.getAttribute('data-id');
                document.getElementById('serviceStockName').value = stockButton.getAttribute('data-name');
                document.getElementById('serviceStockCurrent').value = stockButton.getAttribute('data-stock');
                document.querySelector('#serviceStockForm [name="opening_stock"]').value = '';
                $('#serviceStockModal').modal('show');
            }
        });

        document.getElementById('serviceStockForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-services.php', new FormData(form), {
                loadingText: 'Updating stock...'
            });

            replaceServiceRow(payload.row_html, payload.record_id);
            form.reset();
            panelApp.closeModal('#serviceStockModal');
        });
    </script>
</body>
</html>
