<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
} else {

	if (isset($_POST['submit'])) {
		$sername = $_POST['sername'];
		$cost = $_POST['cost'];
		$des = $_POST['des'];
		$type = $_POST['type'];
		$cate_id = $_POST['cate_id'];
		$eid = $_GET['editid'];

		$query = mysqli_query($con, "update  tblservices set ServiceName='$sername', Description='$des', Cost='$cost', type='$type',cate_id='$cate_id' where ID='$eid' ");
		if ($query) {

			echo '<script>alert("Service/Product has been Updated")</script>';
			echo "<script>window.location.href = 'manage-services.php'</script>";
		} else {
			echo '<script>alert("Something Went Wrong. Please try again.")</script>';
		}


	}
	?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon | Update Services</title>
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
					<div class="forms">
						<h3 class="title1">Update Services/Product</h3>
						<div class="form-grids row widget-shadow" data-example-id="basic-forms">
							<div class="form-title">
								<h4>Update Services/Product:</h4>
							</div>
							<div class="form-body">
								<form method="post" class="row">

									<?php
									$cid = $_GET['editid'];
									$ret = mysqli_query($con, "select * from  tblservices where ID='$cid'");
									$cnt = 1;
									while ($row = mysqli_fetch_array($ret)) {

										?>


										<div class="form-group col-md-6">
											<label> Type</label>

											<select class="form-control" id="type" name="type" required>
												<option value="">Select</option>
												<option value="1" <?php if ($row['type'] == 1) {
													echo 'selected';
												} ?>>Product
												</option>
												<option value="2" <?php if ($row['type'] == 2) {
													echo 'selected';
												} ?>>Service
												</option>
											</select>

										</div>
										<div class="form-group col-md-6">
											<label>Category</label>

											<select class="form-control" id="cate_id" name="cate_id" required="true">
												<option value="">Select Category</option>
												<?php
												$ret4 = mysqli_query($con, "select *from  tbl_category");
												$cnt = 1;
												while ($row4 = mysqli_fetch_array($ret4)) {

													?>
													<option value="<?php echo $row4['id']; ?>" <?php if ($row['cate_id'] == $row4['id']) {
														   echo 'selected';
													   } ?>>
														<?php echo $row4['name']; ?>
													</option>
												<?php } ?>
											</select>
										</div>

										<div class="form-group col-md-6"> <label for="exampleInputEmail1">Service Name</label>
											<input type="text" class="form-control" id="sername" name="sername"
												placeholder="Service Name" value="<?php echo $row['ServiceName']; ?>"
												required="true">
										</div>
										<div class="form-group col-md-6"> <label>Description</label> <input class="form-control"
												name="des" id="des" rows="5" required="true"
												value="<?php echo $row['Description']; ?>"> </div>
										<div class="form-group col-md-6"> <label for="exampleInputPassword1">Cost</label> <input
												type="text" id="cost" name="cost" class="form-control" placeholder="Cost"
												value="<?php echo $row['Cost']; ?>" required="true"> </div>
									<?php } ?>
									<div class="col-md-12">
										<button type="submit" name="submit" class="btn btn-default">Update</button>
									</div>
								</form>
							</div>

						</div>


					</div>
				</div>
				<?php include_once('includes/footer.php'); ?>
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
	</body>

	</html>
<?php } ?>