<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
} else {
	if (isset($_POST['submit'])) {

		$uid = intval($_GET['addid']);
		$invoiceid = mt_rand(100000000, 999999999);
		$sid = $_POST['sids'];

		// New fields
		$tax = $_POST['tax'];
		$total = $_POST['total'];
		$staffId = $_POST['name'];
		$payment = $_POST['payment_method'];

		foreach ($sid as $svid) {
			$ret = mysqli_query($con, "INSERT INTO tblinvoice(Userid, ServiceId, BillingId, tax, total, staff, payment_method) 
            VALUES('$uid', '$svid', '$invoiceid', '$tax', '$total', '$staffId','$payment')");
		}

		echo '<script>alert("Invoice created successfully. Invoice number is ' . $invoiceid . '")</script>';
		echo "<script>window.location.href ='invoices.php'</script>";
	}

	?>

	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon || Assign Services</title>
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
						<h3 class="title1">Assign Services</h3>



						<div class="table-responsive bs-example widget-shadow">

							<form method="post">
								<table class="table table-bordered">
									<thead>
										<tr>
											<th>#</th>
											<th>Service Name</th>
											<th>Service Price</th>
											<th>Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$ret = mysqli_query($con, "select *from  tblservices");
										$cnt = 1;
										while ($row = mysqli_fetch_array($ret)) {

											?>

											<tr>
												<th scope="row"><?php echo $cnt; ?></th>
												<td><?php echo $row['ServiceName']; ?></td>
												<td><?php echo $row['Cost']; ?></td>
												<!--<td><input type="checkbox" name="sids[]" value="</?php  echo $row['ID'];?>" >-->

												</td>

												<td><input type="checkbox" class="service-check" name="sids[]"
														value="<?php echo $row['ID']; ?>"
														data-cost="<?php echo $row['Cost']; ?>"></td>

											</tr>
											<?php
											$cnt = $cnt + 1;
										} ?>
										<tr>
											<div class="form-group col-md-6">
												<label>Assign Staff</label>

												<select class="form-control" id="name" name="name" required="true">
													<option value="">Select Staff</option>
													<?php
													$ret1 = mysqli_query($con, "select *from  tbl_staff");
													$cnt = 1;
													while ($row1 = mysqli_fetch_array($ret1)) {

														?>
														<option value="<?php echo $row1['id']; ?>"><?php echo $row1['name']; ?>
														</option>
													<?php } ?>
												</select>
											</div>

											<div class="form-group col-md-6">
												<label>Tax</label>

												<select class="form-control" id="tax" name="tax" required="true">
													<option value="">Select Tax</option>
													<?php
													$ret2 = mysqli_query($con, "select *from  tbl_tax");
													$cnt = 1;
													while ($row2 = mysqli_fetch_array($ret2)) {

														?>
														<option value="<?php echo $row2['value']; ?>">
															<?php echo $row2['name']; ?>
														</option>
													<?php } ?>
												</select>
											</div>
											<td colspan="4" align="center">
												<button type="submit" name="submit" class="btn btn-default">Submit</button>
											</td>
											<div class="form-group col-md-6">
												<label> Total</label>
												<input type="text" class="form-control" id="total" name="total" value=""
													readonly>

											</div>
											<div class="form-group col-md-6">
												<label>Payment Method</label>

												<select class="form-control" id="payment_method" name="payment_method"
													required="true">
													<option value="">Select</option>

													<option value="Cash">Cash</option>
													<option value="Online">Online</option>
													<option value="Debit">Debit</option>
													<option value="Credit">Credit</option>

												</select>
											</div>
										</tr>

									</tbody>
								</table>
							</form>
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

		<script>
			function calculateTotal() {
				let total = 0;

				// Get all checked services
				document.querySelectorAll('.service-check:checked').forEach(function (checkbox) {
					total += parseFloat(checkbox.getAttribute('data-cost')) || 0;
				});

				// Get selected tax rate
				const taxRate = parseFloat(document.getElementById('tax').value) || 0;

				// Calculate tax
				const taxAmount = total * (taxRate / 100);

				// Final total
				const finalTotal = total + taxAmount;

				// Set to total field
				document.getElementById('total').value = finalTotal.toFixed(2);
			}

			// Event listeners
			document.querySelectorAll('.service-check').forEach(function (checkbox) {
				checkbox.addEventListener('change', calculateTotal);
			});

			document.getElementById('tax').addEventListener('change', calculateTotal);
		</script>

		<!--scrolling js-->
		<script src="js/jquery.nicescroll.js"></script>
		<script src="js/scripts.js"></script>
		<!--//scrolling js-->
		<!-- Bootstrap Core JavaScript -->
		<script src="js/bootstrap.js"> </script>
	</body>

	</html>
<?php } ?>