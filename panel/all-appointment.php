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

function fetch_customer_map($con)
{
    $result = mysqli_query($con, "SELECT ID, Name, Email, MobileNumber FROM tblcustomers ORDER BY Name ASC");
    $customers = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $customers[$row['ID']] = $row;
    }

    return $customers;
}

function render_appointment_row($row, $customers)
{
    $id = (int) $row['ID'];
    $customerName = isset($customers[$row['Name']]) ? $customers[$row['Name']]['Name'] : 'Unknown customer';
    ob_start();
    ?>
    <tr id="appointment-row-<?php echo $id; ?>">
        <?php echo panel_table_row_number_cell(); ?>
        <td><?php echo panel_escape($row['AptNumber']); ?></td>
        <td><?php echo panel_escape($customerName); ?></td>
        <td><?php echo panel_escape($row['PhoneNumber']); ?></td>
        <td><?php echo panel_format_date($row['AptDate']); ?></td>
        <td><?php echo panel_format_date($row['AptTime'], 'g:i A'); ?></td>
        <td>
            <a href="view-appointment.php?viewid=<?php echo $id; ?>" class="btn btn-primary btn-sm">View</a>
            <button type="button" class="btn btn-danger btn-sm" onclick="deleteAppointment(<?php echo $id; ?>)">Delete</button>
        </td>
    </tr>
    <?php
    return trim(ob_get_clean());
}

$customers = fetch_customer_map($con);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';
    
    if ($action === 'customer_details') {
        $id = (int) $_POST['id'];
        $customer = isset($customers[$id]) ? $customers[$id] : null;

        if (!$customer) {
            panel_json_response(false, 'Customer not found.');
        }

        panel_json_response(true, 'Customer loaded.', array(
            'email' => $customer['Email'],
            'phone' => $customer['MobileNumber'],
        ));
    }

    if ($action === 'create_appointment') {
        $appointmentNumber = mt_rand(100000000, 999999999);
        $customerId = (int) ($_POST['Name'] ?? 0);
        $email = mysqli_real_escape_string($con, trim($_POST['Email'] ?? ''));
        $phone = mysqli_real_escape_string($con, trim($_POST['PhoneNumber'] ?? ''));
        $aptDate = mysqli_real_escape_string($con, trim($_POST['AptDate'] ?? ''));
        $aptTime = mysqli_real_escape_string($con, trim($_POST['AptTime'] ?? ''));
        $services = isset($_POST['services']) && is_array($_POST['services']) ? $_POST['services'] : array();
        $serviceList = mysqli_real_escape_string($con, implode(',', $services));

        if (!$customerId || $email === '' || $phone === '' || $aptDate === '' || $aptTime === '' || empty($services)) {
            panel_json_response(false, 'Please complete the appointment form.');
        }

        do { $ereceiptToken = (string) mt_rand(1000000000, 9999999999); } while (mysqli_query($con, "SELECT 1 FROM tblappointment WHERE ereceipt_token = '{$ereceiptToken}'")->num_rows > 0);

        $insertQuery = mysqli_query(
            $con,
            "INSERT INTO tblappointment(AptNumber, Name, Email, PhoneNumber, AptDate, AptTime, Services, Remark, Status, total, grand_total, payment_id, order_id, payment_status, ereceipt_token) 
             VALUES ('{$appointmentNumber}', '{$customerId}', '{$email}', '{$phone}', '{$aptDate}', '{$aptTime}', '{$serviceList}', '', '0', '0', '0', '', '', 'Pending', '{$ereceiptToken}')"
        );

        if (!$insertQuery) {
            $error = mysqli_error($con);
            panel_json_response(false, 'Unable to create the appointment: ' . $error);
        }

        $newId = mysqli_insert_id($con);
        $appointmentResult = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = '{$newId}'");
        $appointmentRow = mysqli_fetch_assoc($appointmentResult);
        
        // Log the creation
        log_audit_action($con, [
            'user_type' => 'admin',
            'user_id' => (int) $adminId,
            'user_name' => $adminName,
            'action' => 'create',
            'entity_type' => 'appointment',
            'entity_id' => $newId,
            'new_values' => $appointmentRow,
            'description' => "Admin {$adminName} created appointment #{$appointmentNumber} for customer ID {$customerId}"
        ]);

        include_once 'includes/sms_helper.php';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $receiptLink = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/ereceipt.php?token=' . urlencode($ereceiptToken);
        send_sms($phone, 'View your e-receipt: ' . $receiptLink);

        panel_json_response(true, 'Appointment created successfully.', array(
            'row_html' => render_appointment_row($appointmentRow, $customers),
            'record_id' => $newId,
            'appointment_number' => $appointmentNumber,
        ));
    }

    if ($action === 'delete_appointment') {
        $id = (int) $_POST['id'];
        
        if ($id <= 0) {
            panel_json_response(false, 'Invalid appointment ID.');
        }
        
        // Get appointment info first
        $aptQuery = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID = $id");
        
        if (!$aptQuery || mysqli_num_rows($aptQuery) === 0) {
            panel_json_response(false, 'Appointment not found.');
        }
        
        $aptRow = mysqli_fetch_assoc($aptQuery);
        $services = $aptRow['Services'];
        $customerName = mysqli_real_escape_string($con, $aptRow['Name']);
        
        mysqli_begin_transaction($con);
        
        try {
            // Look up customer ID from tblcustomers using the appointment's Name field
            $customerQuery = mysqli_query($con, "SELECT ID FROM tblcustomers WHERE Name = '$customerName' LIMIT 1");
            $customerId = 0;
            
            if ($customerQuery && mysqli_num_rows($customerQuery) > 0) {
                $customerRow = mysqli_fetch_assoc($customerQuery);
                $customerId = (int) $customerRow['ID'];
            }
            
            // Delete related invoices
            if (!empty($services)) {
                $serviceIds = array_filter(array_map('intval', explode(',', $services)));
                
                if (!empty($serviceIds)) {
                    $serviceIdList = implode(',', $serviceIds);
                    
                    if ($customerId > 0) {
                        // Delete invoices matching customer and services
                        $invoiceQuery = mysqli_query($con, "
                            SELECT BillingId FROM tblinvoice 
                            WHERE Userid = $customerId 
                            AND ServiceId IN ($serviceIdList)
                            ORDER BY ID DESC LIMIT 1
                        ");
                        
                        if ($invoiceQuery && mysqli_num_rows($invoiceQuery) > 0) {
                            $invoiceRow = mysqli_fetch_assoc($invoiceQuery);
                            $billingId = (int) $invoiceRow['BillingId'];
                            mysqli_query($con, "DELETE FROM tblinvoice WHERE BillingId = $billingId");
                        }
                    } else {
                        // No customer found - delete orphaned invoices by service only
                        mysqli_query($con, "DELETE FROM tblinvoice WHERE ServiceId IN ($serviceIdList)");
                    }
                }
            }
            
            // Delete the appointment
            $deleteResult = mysqli_query($con, "DELETE FROM tblappointment WHERE ID = $id");
            
            if (!$deleteResult) {
                throw new Exception('Failed to delete appointment');
            }
            
            // Log the deletion
            log_audit_action($con, [
                'user_type' => 'admin',
                'user_id' => (int) $adminId,
                'user_name' => $adminName,
                'action' => 'delete',
                'entity_type' => 'appointment',
                'entity_id' => $id,
                'old_values' => $aptRow,
                'description' => "Admin {$adminName} deleted appointment #{$aptRow['AptNumber']} for {$aptRow['Name']}"
            ]);
            
            mysqli_commit($con);
            panel_json_response(true, 'Appointment deleted successfully.');
            
        } catch (Exception $e) {
            mysqli_rollback($con);
            panel_json_response(false, $e->getMessage());
        }
    }
}

$serviceOptions = mysqli_query($con, "SELECT ID, ServiceName FROM tblservices WHERE type = 2 ORDER BY ServiceName ASC");
$appointmentResult = mysqli_query($con, "SELECT * FROM tblappointment ORDER BY ID DESC");
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Marie Noelle Spa and Salon || Appointments</title>
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
                            <h3 class="title1">Appointments</h3>
                            <p>Create appointments in a modal and push the new booking straight into the live list.</p>
                        </div>
                        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#appointmentCreateModal">
                            <i class="fa fa-plus"></i> Add Appointment
                        </button>
                    </div>

                    <div class="table-responsive bs-example widget-shadow crud-card">
                        <table id="appointmentTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Appointment Number</th>
                                    <th>Name</th>
                                    <th>Mobile Number</th>
                                    <th>Appointment Date</th>
                                    <th>Appointment Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($appointmentResult)) { ?>
                                    <?php echo render_appointment_row($row, $customers); ?>
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

    <div class="modal fade" id="appointmentCreateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="appointmentCreateForm">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Appointment</h4>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="ajax_action" value="create_appointment">
                        <div class="form-group col-md-6">
                            <label>Customer Name</label>
                            <select class="form-control" id="appointmentCustomer" name="Name" required>
                                <option value="">Select Name</option>
                                <?php foreach ($customers as $customer) { ?>
                                    <option value="<?php echo $customer['ID']; ?>"><?php echo panel_escape($customer['Name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Email</label>
                            <input type="text" id="appointmentEmail" name="Email" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Phone</label>
                            <input type="text" id="appointmentPhone" name="PhoneNumber" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Appointment Date</label>
                            <input type="date" name="AptDate" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Appointment Time</label>
                            <input type="time" name="AptTime" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Services</label>
                            <select name="services[]" class="form-control" multiple required>
                                <?php while ($service = mysqli_fetch_assoc($serviceOptions)) { ?>
                                    <option value="<?php echo $service['ID']; ?>"><?php echo panel_escape($service['ServiceName']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-default">Save Appointment</button>
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
        var appointmentTable = new DataTable('#appointmentTable', {
            layout: {
                topStart: {
                    buttons: ['copy', 'excel', 'pdf', 'colvis']
                }
            }
        });

        panelApp.renumberTableRows('#appointmentTable');

        document.getElementById('appointmentCustomer').addEventListener('change', function (event) {
            var customerId = event.currentTarget.value;

            if (!customerId) {
                document.getElementById('appointmentEmail').value = '';
                document.getElementById('appointmentPhone').value = '';
                return;
            }

            var formData = new FormData();
            formData.append('ajax_action', 'customer_details');
            formData.append('id', customerId);

            panelApp.postForm('all-appointment.php', formData, {
                loadingText: 'Loading customer details...',
                successMessage: false
            }).then(function (payload) {
                document.getElementById('appointmentEmail').value = payload.email || '';
                document.getElementById('appointmentPhone').value = payload.phone || '';
            });
        });

        document.getElementById('appointmentCreateForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            var form = event.currentTarget;
            var payload = await panelApp.postForm('all-appointment.php', new FormData(form), {
                loadingText: 'Creating appointment...',
                successMessage: false
            });
            appointmentTable.row.add($(payload.row_html)[0]).draw(false);
            panelApp.renumberTableRows('#appointmentTable');
            form.reset();
            document.getElementById('appointmentEmail').value = '';
            document.getElementById('appointmentPhone').value = '';
            panelApp.closeModal('#appointmentCreateModal');
            panelApp.showToast('Appointment #' + payload.appointment_number + ' created successfully.', 'success');
        });

        var pendingDeleteId = null;
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

        async function deleteAppointment(id) {
            showDeleteConfirm(
                'Are you sure you want to delete this appointment?',
                'This will also delete related invoices.',
                function() {
                    var formData = new FormData();
                    formData.append('ajax_action', 'delete_appointment');
                    formData.append('id', id);

                    panelApp.setLoading(true, 'Deleting appointment...');
                    fetch('all-appointment.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'xmlhttprequest'
                        }
                    })
                    .then(function(response) {
                        var contentType = response.headers.get('content-type');
                        if (!contentType || contentType.indexOf('application/json') === -1) {
                            return response.text().then(function(text) {
                                console.log('Non-JSON response:', text);
                                throw new Error('Invalid server response');
                            });
                        }
                        return response.json();
                    })
                    .then(function(payload) {
                        if (payload.success) {
                            var row = document.getElementById('appointment-row-' + id);
                            if (row) {
                                appointmentTable.row(row).remove().draw(false);
                            }
                            panelApp.renumberTableRows('#appointmentTable');
                            panelApp.showToast('Appointment deleted successfully.', 'success');
                        } else {
                            panelApp.showToast(payload.message || 'Failed to delete appointment.', 'error');
                        }
                    })
                    .catch(function(error) {
                        panelApp.showToast('Failed to delete appointment.', 'error');
                    })
                    .finally(function() {
                        panelApp.setLoading(false);
                    });
                }
            );
        }

        window.deleteAppointment = deleteAppointment;
    </script>
</body>
</html>
