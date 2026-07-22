<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
} else {



	?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon || B/W date Reports</title>
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

		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap.css">
		<link href="https://cdn.datatables.net/buttons/3.2.2/css/buttons.bootstrap.css">


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
						<h3 class="title1">Sales reports</h3>



						<div class="table-responsive bs-example widget-shadow">
							<h4>Sales reports:</h4>
							<?php
							$fdate = $_POST['fromdate'];
							$tdate = $_POST['todate'];

							?>
							<h5 align="center" style="color:blue">Report from <?php echo $fdate ?> to <?php echo $tdate ?>
							</h5>

							<table id="example" class="table table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Invoice Id</th>
										<th>Customer Name</th>
										<th>Invoice Date</th>
										<th>Subtotal</th>
										<th>Tax</th>
										<th>Total</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$grandTotal = 0;
									$ret = mysqli_query($con, "
										SELECT DISTINCT  
											tblcustomers.Name,
											tblinvoice.BillingId,
											tblinvoice.PostingDate,
											tblinvoice.total as subtotal,
											tblinvoice.tax
										FROM tblcustomers   
										JOIN tblinvoice ON tblcustomers.ID = tblinvoice.Userid  
										WHERE date(tblinvoice.PostingDate) between '$fdate' and '$tdate'
									");
									$cnt = 1;
									while ($row = mysqli_fetch_array($ret)) {
										$taxPercent = (float)($row['tax'] ?: 0);
										$subtotal = (float)($row['subtotal'] ?: 0);
										$taxAmount = $subtotal * ($taxPercent / 100);
										$totalWithTax = $subtotal + $taxAmount;
										$grandTotal += $totalWithTax;
										?>

										<tr>
											<th scope="row"><?php echo $cnt; ?></th>
											<td><?php echo $row['BillingId']; ?></td>
											<td><?php echo $row['Name']; ?></td>
											<td><?php echo date('d-m-Y', strtotime($row['PostingDate'])); ?></td>
											<td>GH₵<?php echo number_format($subtotal, 2); ?></td>
											<td><?php echo $taxPercent; ?>%</td>
											<td><strong>GH₵<?php echo number_format($totalWithTax, 2); ?></strong></td>
											<td><a href="view-invoice.php?invoiceid=<?php echo $row['BillingId']; ?>">View</a>
											</td>

										</tr>


										<?php
										$cnt = $cnt + 1;


									}

									?>






								</tbody>
								<tfoot style="background-color:#f5f5f5;font-weight:bold;">
									<td colspan="5" style="text-align:right;">Grand Total:</td>
									<td>GH₵<?php echo number_format($grandTotal, 2); ?></td>
									<td></td>
								</tfoot>


							</table>
						</div>
					</div>
				</div>
			</div>
			<!--footer-->
			<?php include_once('includes/footer.php'); ?>
			<!--//footer-->
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
			new DataTable('#example', {
				layout: {
					topStart: {
						buttons: ['copy', 'excel', 'pdf', 'colvis']
					}
				}
			});
		</script>


	</body>

	</html>
<?php } ?>