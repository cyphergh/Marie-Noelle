<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
}
?>
<!DOCTYPE HTML>
<html>

<head>
	<title>Marie Noelle Spa and Salon | Staff Sales Report</title>
	<link rel="icon" type="image/x-icon" href="images/logo.png">
	<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
	<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
	<link href="css/style.css" rel='stylesheet' type='text/css' />
	<link href="css/font-awesome.css" rel="stylesheet">
	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/modernizr.custom.js"></script>
	<link href='//fonts.googleapis.com/css?family=Roboto+Condensed:400,300,300italic,400italic,700,700italic' rel='stylesheet' type='text/css'>
	<link href="css/animate.css" rel="stylesheet" type="text/css" media="all">
	<script src="js/wow.min.js"></script>
	<script>
		new WOW().init();
	</script>
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
				<div class="forms">
					<h3 class="title1">Staff Sales Report</h3>
					<div class="form-grids row widget-shadow" data-example-id="basic-forms">
						<div class="form-title">
							<h4>Staff Sales Report</h4>
						</div>
						<div class="form-body">
							<form method="post" class="row" name="bwdatesreport" action="" enctype="multipart/form-data">
								<div class="form-group col-md-6"> <label for="exampleInputEmail1">From Date</label>
									<input type="date" class="form-control1" name="fromdate" id="fromdate" value="" required='true'>
								</div>
								<div class="form-group col-md-6"> <label for="exampleInputPassword1">To Date</label>
									<input type="date" class="form-control1" name="todate" id="todate" value="" required='true'>
								</div>
								<div class="col-md-12">
									<button type="submit" name="submit" class="btn btn-default">Generate Report</button>
								</div>
							</form>
						</div>

						<div class="tables">
							<?php
							if (isset($_POST['submit'])) {
								$fdate = $_POST['fromdate'];
								$tdate = $_POST['todate'];

								$total = 0;
								$cnt = 1;

								$ret = mysqli_query($con, "SELECT staff, SUM(total + (total * tax / 100)) as total_sales, COUNT(*) as invoice_count FROM tblinvoice WHERE ServiceId = '0' AND PostingDate BETWEEN '$fdate' AND '$tdate' GROUP BY staff");
								?>
								<div class="table-responsive bs-example widget-shadow">
									<table id="example" class="table table-bordered">
										<thead>
											<tr>
												<th>#</th>
												<th>Staff Name</th>
												<th>Invoice Count</th>
												<th>Total Sales (GH₵)</th>
											</tr>
										</thead>
										<tbody>
											<?php
											while ($row = mysqli_fetch_array($ret)) {
												$staffId = $row['staff'];
												
												$rett = mysqli_query($con, "SELECT name FROM tbl_staff WHERE id = '$staffId'");
												$rowt = mysqli_fetch_array($rett);
												$staffName = $rowt['name'] ?? 'Unknown Staff';
												
												$totalSales = $row['total_sales'];
												$total += $totalSales;
											?>
												<tr>
													<th scope='row'><?php echo $cnt; ?></th>
													<td><?php echo $staffName; ?></td>
													<td><?php echo $row['invoice_count']; ?></td>
													<td><?php echo number_format($totalSales, 2); ?></td>
												</tr>
											<?php
												$cnt++;
											}
											?>
											<tr>
												<th colspan="3" class="text-right">Total</th>
												<td><strong><?php echo number_format($total, 2); ?></strong></td>
											</tr>
										</tbody>
									</table>
								</div>
							<?php
							}
							?>
						</div>
					</div>
				</div>
			</div>
			<?php include_once('includes/footer.php'); ?>
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
		<script src="js/bootstrap.js"> </script>
</body>

</html>
