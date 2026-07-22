<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
} else {

	if (isset($_POST['submit'])) {
		$staff_id = $_POST['staff_id'];
		$shift_date = $_POST['shift_date'];
		$start_time = $_POST['start_time'];
		$end_time = $_POST['end_time'];
		$status = $_POST['status'];
		// $note = $_POST['note'];

		$query = "INSERT INTO tbl_staff_schedule (staff_id, shift_date, start_time, end_time, status) 
          VALUES ('$staff_id', '$shift_date', '$start_time', '$end_time', '$status')";

		if (mysqli_query($con, $query)) {
			echo "<script>alert('Schedule saved successfully'); window.location='view_schedule.php';</script>";
		} else {
			echo "Error: " . mysqli_error($con);
		}


	}
	?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon | Add Services</title>
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
		<script src="http://js.nicedit.com/nicEdit-latest.js" type="text/javascript"></script>
		<script type="text/javascript">bkLib.onDomLoaded(nicEditors.allTextAreas);</script>
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
						<h3 class="title1">Add Staff Schedulling</h3>
						<div class="form-grids row widget-shadow" data-example-id="basic-forms">
							<div class="form-title">
								<h4>Staff Schedulling:</h4>
							</div>
							<div class="form-body">
								<form method="post" class="row">

									<div class="form-group col-md-6">
										<label>Staff</label>

										<select class="form-control" id="staff_id" name="staff_id" required="true">
											<option value="">Select Staff</option>
											<?php
											$ret2 = mysqli_query($con, "select *from  tbl_staff");
											$cnt = 1;
											while ($row2 = mysqli_fetch_array($ret2)) {

												?>
												<option value="<?php echo $row2['id']; ?>"><?php echo $row2['name']; ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="form-group col-md-6">
										<label>Shift Date</label>
										<input type="date" name="shift_date" class="form-control" required>
									</div>

									<div class="form-group col-md-6">
										<label>Shift Start Time</label>
										<input type="time" name="start_time" class="form-control" required>
									</div>

									<div class="form-group col-md-6">
										<label>Shift End Time</label>
										<input type="time" name="end_time" class="form-control" required>
									</div>

									<div class="form-group col-md-6">
										<label>Status</label>
										<select name="status" class="form-control" required>
											<option value="">Select</option>
											<option value="Working">Working</option>
											<option value="Day Off">Day Off</option>
										</select>
									</div>

									<div class="col-md-12">
										<button type="submit" name="submit" class="btn btn-default">Add</button>
								</form>
							</div>
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