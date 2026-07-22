<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
	header('location:logout.php');
} else {
	// Code for deletion
	if ($_GET['action'] == 'delete') {
		$id = intval($_GET['id']);
		$query = mysqli_query($con, "delete from tbl_staff where id ='$id'");
		if ($query) {
			echo "<script>alert('Staff deleted.');</script>";
			echo "<script>window.location.href='manage-staff.php'</script>";
		} else {
			echo "<script>alert('Something Went Wrong. Please try again.');</script>";
			echo "<script>window.location.href='manage-staff.php'</script>";
		}
	}



	?>
	<!DOCTYPE HTML>
	<html>

	<head>
		<title>Marie Noelle Spa and Salon || Manage Tax</title>
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

		<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap.css">
		<link href="https://cdn.datatables.net/buttons/3.2.2/css/buttons.bootstrap.css">

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
						<h3 class="title1">Manage Visit</h3>



						<div class="table-responsive bs-example widget-shadow">

							<table id="example" class="table table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Name</th>
										<th>Email</th>
										<th>Visit</th>
										<th>First Visit</th>
										<th>Last Visit</th>
										<th>Retention Days</th>


									</tr>
								</thead>
								<tbody>
									<?php
									$ret = mysqli_query($con, "SELECT 
    u.Name,
    u.Email,
    COUNT(i.id) AS total_visits,
    MIN(i.PostingDate) AS first_visit,
    MAX(i.PostingDate) AS last_visit,
    DATEDIFF(MAX(i.PostingDate), MIN(i.PostingDate)) AS retention_days
FROM 
    tblinvoice i
JOIN 
    tblcustomers u ON u.id = i.Userid WHERE i.type = 0
GROUP BY 
    i.Userid
ORDER BY 
    total_visits DESC;
");
									$cnt = 1;
									while ($row = mysqli_fetch_array($ret)) {

										?>

										<tr>
											<th scope="row"><?php echo $cnt; ?></th>
											<td><?php echo $row['Name']; ?></td>
											<td><?php echo $row['Email']; ?></td>
											<td><?php echo $row['total_visits']; ?></td>
											<td><?php echo date('d-m-Y', strtotime($row['first_visit'])); ?></td>
											<td><?php echo date('d-m-Y', strtotime($row['last_visit'])); ?></td>
											<td><?php echo $row['retention_days']; ?></td>

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



		<!-- The modal -->
		<div class="modal fade" id="flipFlop" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
						<h4 class="modal-title" id="modalLabel">Modal Title</h4>
					</div>
					<div class="modal-body">
						<form action="/html/form_handler.cfm">
							<div class="form-group">
								<label for="first_name">First Name</label>
								<input type="text" class="form-control" id="first_name" name="first_name">
							</div>
							<div class="form-group">
								<label for="last_name">Last Name</label>
								<input type="text" class="form-control" id="last_name" name="last_name">
							</div>
							<button type="submit" class="btn btn-primary">Submit</button>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>




	</body>

	</html>
<?php } ?>