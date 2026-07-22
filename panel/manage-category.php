<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

function render_category_row($row)
{
    $id = (int) $row['id'];
    $isActive = (int) $row['status'] === 1;
    ob_start();
    ?>
    <tr id="category-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td><?php echo panel_escape($row['name']); ?></td>
        <td>
            <span class="status-chip <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>">
                <?php echo $isActive ? 'Active' : 'Inactive'; ?>
            </span>
        </td>
        <td>
            <div class="table-action-group">
                <button type="button" class="btn btn-primary btn-sm js-edit-category" data-id="<?php echo $id; ?>"><i class="fa fa-edit"></i></button>
                <button type="button" class="btn btn-danger btn-sm js-delete-category" data-id="<?php echo $id; ?>">
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

    if ($action === 'create_category') {
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $status = (int) $_POST['status'];

        if ($name === '' || !in_array($status, array(1, 2), true)) {
            panel_json_response(false, 'Please complete the category form.');
        }

        $insertQuery = mysqli_query($con, "INSERT INTO tbl_category(name, status) VALUES ('{$name}', '{$status}')");

        if (!$insertQuery) {
            panel_json_response(false, 'Unable to add the category.');
        }

        $newId = mysqli_insert_id($con);
        $categoryResult = mysqli_query($con, "SELECT * FROM tbl_category WHERE id = '{$newId}'");
        $categoryRow = mysqli_fetch_assoc($categoryResult);

        panel_json_response(true, 'Category added successfully.', array(
            'row_html' => render_category_row($categoryRow),
            'record_id' => $newId,
        ));
    }

    if ($action === 'get_category') {
        $id = (int) $_POST['id'];
        $categoryResult = mysqli_query($con, "SELECT * FROM tbl_category WHERE id = '{$id}'");
        $categoryRow = mysqli_fetch_assoc($categoryResult);

        if (!$categoryRow) {
            panel_json_response(false, 'Category not found.');
        }

        panel_json_response(true, 'Category loaded.', array('record' => $categoryRow));
    }

    if ($action === 'update_category') {
        $id = (int) $_POST['id'];
        $name = mysqli_real_escape_string($con, trim($_POST['name']));
        $status = (int) $_POST['status'];

        if ($name === '' || !in_array($status, array(1, 2), true)) {
            panel_json_response(false, 'Please complete the category form.');
        }

        $updateQuery = mysqli_query($con, "UPDATE tbl_category SET name = '{$name}', status = '{$status}' WHERE id = '{$id}'");
        if (!$updateQuery) {
            panel_json_response(false, 'Unable to update the category.');
        }

        $categoryResult = mysqli_query($con, "SELECT * FROM tbl_category WHERE id = '{$id}'");
        $categoryRow = mysqli_fetch_assoc($categoryResult);
        panel_json_response(true, 'Category updated successfully.', array('row_html' => render_category_row($categoryRow), 'record_id' => $id));
    }

    if ($action === 'delete_category') {
        $id = (int) $_POST['id'];
        $deleteQuery = mysqli_query($con, "DELETE FROM tbl_category WHERE id = '{$id}'");

        if (!$deleteQuery) {
            panel_json_response(false, 'Unable to delete the category.');
        }

        panel_json_response(true, 'Category deleted.', array('record_id' => $id));
    }
}

$categoryResult = mysqli_query($con, "SELECT * FROM tbl_category ORDER BY id DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Categories</title>
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
                            <h3 class="title1">Categories</h3>
                            <p>Control service groups from the same page where you review them.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#categoryCreateModal">
                            <i class="fa fa-plus"></i> Add Category
                        </button>
                    </div>
                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="categoryTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Category Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($categoryResult)) { ?>
                                    <?php echo render_category_row($row); ?>
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

    <div class="modal fade" id="categoryEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="categoryEditForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Edit Category</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="update_category">
                        <input type="hidden" name="id" id="categoryEditId">
                        <div class="form-group col-md-12">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" id="categoryEditName" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Status</label>
                            <select class="form-control" name="status" id="categoryEditStatus" required>
                                <option value="1">Active</option>
                                <option value="2">Deactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="categoryCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Category</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_category">
                        <div class="form-group col-md-12">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="form-group col-md-12">
                            <label>Status</label>
                            <select class="form-control" name="status" required>
                                <option value="1">Active</option>
                                <option value="2">Deactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Category</button>
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

        var categoryTable = new DataTable('#categoryTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#categoryTable');

        document.getElementById('categoryCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-category.php', new FormData(form), {
                loadingText: 'Adding category...'
            });
            categoryTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#categoryTable');
            form.reset();
            panelApp.closeModal('#categoryCreateModal');
        });

        document.getElementById('categoryEditForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('manage-category.php', new FormData(form), { loadingText: 'Updating category...' });
            categoryTable.row('#category-row-' + payload.record_id).remove().draw(false);
            categoryTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#categoryTable');
            panelApp.closeModal('#categoryEditModal');
        });

        document.addEventListener('click', function (event) {
            var editButton = event.target.closest('.js-edit-category');
            if (editButton) {
                var editData = new FormData();
                editData.append('ajax_action', 'get_category');
                editData.append('id', editButton.getAttribute('data-id'));
                panelApp.postForm('manage-category.php', editData, { loadingText: 'Loading category...', successMessage: false }).then(function (payload) {
                    document.getElementById('categoryEditId').value = payload.record.id;
                    document.getElementById('categoryEditName').value = payload.record.name || '';
                    document.getElementById('categoryEditStatus').value = payload.record.status || '1';
                    panelApp.openModal('#categoryEditModal');
                });
                return;
            }

            var button = event.target.closest('.js-delete-category');
            if (!button) {
                return;
            }

            var categoryId = button.getAttribute('data-id');
            showDeleteConfirm(
                'Are you sure you want to delete this category?',
                'Services using this category may become unassigned.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_category');
                    formData.append('id', categoryId);

                    panelApp.postForm('manage-category.php', formData, {
                        loadingText: 'Deleting category...'
                    }).then(function (payload) {
                        categoryTable.row('#category-row-' + payload.record_id).remove().draw(false);
                        panelApp.renumberTableRows('#categoryTable');
                    });
                }
            );
        });
    </script>
</body>
</html>
