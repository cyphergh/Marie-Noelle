<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
include('includes/panel_crud_helpers.php');
include('includes/audit_helper.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
}

ensure_audit_table($con);

$adminId = $_SESSION['bpmsaid'];
$adminResult = mysqli_query($con, "SELECT AdminName FROM tbladmin WHERE ID = '$adminId'");
$adminRow = mysqli_fetch_assoc($adminResult);
$adminName = $adminRow['AdminName'] ?? 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && panel_is_ajax_request()) {
	$action = isset($_POST['ajax_action']) ? $_POST['ajax_action'] : '';

	if ($action === 'delete_invoice') {
		$billingId = mysqli_real_escape_string($con, $_POST['billing_id']);

		if (empty($billingId)) {
			panel_json_response(false, 'Invalid invoice ID.');
		}

		// Get invoice details before deletion
		$invoiceQuery = mysqli_query($con, "SELECT * FROM tblinvoice WHERE BillingId = '{$billingId}' LIMIT 1");
		$invoiceData = mysqli_fetch_assoc($invoiceQuery);

		$deleteQuery = mysqli_query($con, "DELETE FROM tblinvoice WHERE BillingId = '{$billingId}'");

		if (!$deleteQuery) {
			panel_json_response(false, 'Unable to delete the invoice.');
		}

		// Log the deletion
		log_audit_action($con, [
			'user_type' => 'admin',
			'user_id' => (int) $adminId,
			'user_name' => $adminName,
			'action' => 'delete',
			'entity_type' => 'invoice',
			'entity_id' => 0,
			'old_values' => $invoiceData,
			'description' => "Admin {$adminName} deleted invoice #{$billingId}" . ($invoiceData ? " for customer ID {$invoiceData['Userid']}" : '')
		]);

		panel_json_response(true, 'Invoice deleted.', array('billing_id' => $billingId));
	}
}

function render_invoice_row($row, $cnt)
{
	ob_start();
	?>
	<tr>
		<th scope="row"><?php echo $cnt; ?></th>
		<td><?php echo panel_escape($row['BillingId']); ?></td>
		<td><?php echo panel_escape($row['Name']); ?></td>
		<td><?php echo panel_format_date($row['PostingDate']); ?></td>
		<td>
			<a class="btn btn-primary btn-sm" href="view-invoice.php?invoiceid=<?php echo $row['BillingId']; ?>">View</a>
			<button type="button" class="btn btn-danger btn-sm js-delete-invoice" data-billing-id="<?php echo $row['BillingId']; ?>">
				<i class="fa fa-trash-o"></i>
			</button>
		</td>
	</tr>
	<?php
	return trim(ob_get_clean());
}
?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon || Invoice</title>
		<link rel="icon" type="image/x-icon" href="images/logo.png">
		<script
			type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
		<!-- Bootstrap Core CSS -->
		<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
		<!-- Custom CSS -->
		<link href="css/style.css" rel='stylesheet' type='text/css' />
		<!-- font CSS -->
		<!-- font-awesome icons -->
		<link href="css/font-awesome.css" rel="stylesheet">
		<!-- //font-awesome icons -->
		<!-- js-->
		<script src="js/jquery-1.11.1.min.js"></script>
		<script src="js/modernizr.custom.js"></script>
		<!--webfonts-->
		<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic'
			rel='stylesheet' type='text/css'>
		<!--//webfonts-->
		<!--animate-->
		<link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
		<script src="js/wow.min.js"></script>
		<script>
			new WOW().init();
		</script>
		<!--//end-animate-->
		<!-- Metis Menu -->
		<script src="js/metisMenu.min.js"></script>
		<script src="js/custom.js"></script>
		<link href="css/custom.css" rel="stylesheet">

		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap.css">
		<link href="https://cdn.datatables.net/buttons/3.2.2/css/buttons.bootstrap.css">
		<!--//Metis Menu -->
	</head>

	<body class="cbp-spmenu-push">
		<div class="main-content">
			<!--left-fixed -navigation-->
			<?php include_once('includes/sidebar.php'); ?>
			<!--left-fixed -navigation-->
			<!-- header-starts -->
			<?php include_once('includes/header.php'); ?>
			<!-- //header-ends -->
			<!-- main content start-->
			<div id="page-wrapper">
				<div class="main-page">
					<div class="tables">
						<h3 class="title1">Invoice List</h3>



						<div class="table-responsive bs-example widget-shadow">

							<table id="invoiceTable" class="table table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Invoice Id</th>
										<th>Customer Name</th>
										<th>Invoice Date</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$ret = mysqli_query($con, "SELECT c.Name, i.BillingId, i.PostingDate, i.total 
										FROM tblinvoice i
										JOIN tblcustomers c ON c.ID = i.Userid 
										WHERE i.ServiceId = '0' 
										ORDER BY i.ID DESC");
									$cnt = 1;
									while ($row = mysqli_fetch_array($ret)) {

										?>

										<tr id="invoice-row-<?php echo $row['BillingId']; ?>">
											<th scope="row"><?php echo $cnt; ?></th>
											<td><?php echo $row['BillingId']; ?></td>
											<td><?php echo $row['Name']; ?></td>
											<td><?php echo date('d-m-Y', strtotime($row['PostingDate'])); ?></td>
											<td>
												<a class="btn btn-primary btn-sm" href="view-invoice.php?invoiceid=<?php echo $row['BillingId']; ?>">View</a>
												<button type="button" class="btn btn-danger btn-sm js-delete-invoice" data-billing-id="<?php echo $row['BillingId']; ?>">
													<i class="fa fa-trash-o"></i>
												</button>
											</td>

										</tr> <?php
										$cnt = $cnt + 1;
									} ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!--footer-->
			<?php include_once('includes/footer.php'); ?>
			<!--//footer-->
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

		<!-- Classie -->
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
		<!--scrolling js-->
		<script src="js/jquery.nicescroll.js"></script>
		<script src="js/scripts.js"></script>
		<!--//scrolling js-->
		<!-- Bootstrap Core JavaScript -->
		<script src="js/bootstrap.js"> </script>
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

			var invoiceTable = new DataTable('#invoiceTable', {
				layout: {
					topStart: {
						buttons: ['copy', 'excel', 'pdf', 'colvis']
					}
				}
			});

			document.addEventListener('click', function (event) {
				var deleteButton = event.target.closest('.js-delete-invoice');
				if (!deleteButton) {
					return;
				}

				var billingId = deleteButton.getAttribute('data-billing-id');
				showDeleteConfirm(
					'Are you sure you want to delete this invoice?',
					'This action cannot be undone.',
					function() {
						var formData = new FormData();
						formData.append('ajax_action', 'delete_invoice');
						formData.append('billing_id', billingId);

						fetch('invoices.php', {
							method: 'POST',
							body: formData,
							headers: {
								'X-Requested-With': 'XMLHttpRequest'
							}
						})
						.then(function(response) {
							return response.json();
						})
						.then(function(payload) {
							if (payload.success) {
								var row = document.getElementById('invoice-row-' + payload.billing_id);
								if (row) {
									invoiceTable.row(row).remove().draw(false);
								}
								alert('Invoice deleted successfully.');
							} else {
								alert(payload.message || 'Failed to delete invoice.');
							}
						})
						.catch(function(error) {
							alert('Failed to delete invoice.');
						});
					}
				);
			});
		</script>

	</body>

	</html>
