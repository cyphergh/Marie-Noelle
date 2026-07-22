<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
} else {

	if (isset($_POST['submit'])) {
		$customer_name = $_POST['user_id'];
		$plan_id = $_POST['plan_id'];

		$eid = $_GET['editid'];

		// Get new plan duration
		$plan_res = mysqli_query($con, "SELECT duration_days FROM membership_plans WHERE id = $plan_id");
		$plan = mysqli_fetch_assoc($plan_res);
		$start_date = date('Y-m-d');
		$end_date = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));

		$update = mysqli_query($con, "UPDATE user_memberships SET 
        user_id = '$customer_name',
     
        plan_id = '$plan_id',
        start_date = '$start_date',
        end_date = '$end_date'
        WHERE id = $eid");


		if ($update) {
			echo '<script>alert("Subscription has been Updated")</script>';
			echo "<script>window.location.href = 'manage_subscribe.php'</script>";

		} else {
			echo '<script>alert("Something Went Wrong. Please try again.")</script>';
		}



	}




	?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon | Update Staff</title>
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
						<h3 class="title1">Update User Subscription </h3>
						<div class="form-grids row widget-shadow" data-example-id="basic-forms">
							<div class="form-title">
								<h4>Update User Subscription :</h4>
							</div>
							<div class="form-body">
								<form method="post" class="row">

									<?php
									$cid = $_GET['editid'];
									$ret = mysqli_query($con, "select * from  user_memberships where id ='$cid'");
									$cnt = 1;
									while ($row = mysqli_fetch_array($ret)) {

										?>


										<div class="form-group col-md-6">
											<label>Customer</label>

											<select class="form-control" id="user_id" name="user_id" required="true">
												<option value="">Select</option>
												<?php
												$ret2 = mysqli_query($con, "select *from  tblcustomers");
												$cnt = 1;
												while ($row2 = mysqli_fetch_array($ret2)) {

													?>
													<option value="<?php echo $row2['ID']; ?>" <?php if ($row2['ID'] == $row['user_id']) {
														   echo 'selected';
													   } ?>>
														<?php echo $row2['Name']; ?>
													</option>
												<?php } ?>
											</select>
										</div>

										<div class="form-group col-md-6"> <label for="exampleInputPassword1">Plan</label>
											<select name="plan_id" class="form-control" required>
												<option value="">Choose a Plan</option>
												<?php
												$plans = mysqli_query($con, "SELECT * FROM membership_plans");
												while ($plan = mysqli_fetch_assoc($plans)) { ?>
													<option value='<?php echo $plan['id']; ?>' <?php if ($plan['id'] == $row['plan_id']) {
														   echo 'selected';
													   } ?>>
														<?php echo $plan['plan_name'] . "-GH₵" . $plan['price']; ?>
													</option>
												<?php }
												?>
											</select>
										</div>
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