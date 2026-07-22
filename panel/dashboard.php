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
	<title>Marie Noelle Spa and Salon | Admin Dashboard</title>

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
	<!-- chart -->
	<script src="js/Chart.js"></script>
	<!-- //chart -->
	<!--Calender-->
	<link rel="stylesheet" href="css/clndr.css" type="text/css" />
	<script src="js/underscore-min.js" type="text/javascript"></script>
	<script src="js/moment-2.2.1.js" type="text/javascript"></script>
	<script src="js/clndr.js" type="text/javascript"></script>
	<script src="js/site.js" type="text/javascript"></script>
	<!--End Calender-->
	<!-- Metis Menu -->
	<script src="js/metisMenu.min.js"></script>
	<script src="js/custom.js"></script>
	<link href="css/custom.css" rel="stylesheet">


	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
	<link href="https://cdn.datatables.net/2.2.2/css/dataTables.bootstrap.css">
	<link href="https://cdn.datatables.net/buttons/3.2.2/css/buttons.bootstrap.css">

	<!-- FullCalendar CSS -->
	<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />

	<!--//Metis Menu -->
</head>

<body class="cbp-spmenu-push">
	<div class="main-content">



		<?php include_once('includes/sidebar.php'); ?>

		<?php include_once('includes/header.php'); ?>

		<!-- main content start-->
		<div id="page-wrapper" class="row calender">

			<div class="main-page">
				<div class="widget-shadow form-grids" style="margin-bottom: 24px;">
					<div class="form-body">
						<h3 class="title1" style="margin-bottom: 8px;">Dashboard Overview</h3>
						<p style="color:#766a5f;font-size:15px;">A modern snapshot of customer activity, appointments, sales, and the latest bookings.</p>
					</div>
				</div>

				<div class="row">
					<div class="row mb-2">
						<div class="col-md-3 mb-2">
							<?php $query1 = mysqli_query($con, "Select * from tblcustomers");
							$totalcust = mysqli_num_rows($query1);
							?>
							<div class="dashboard-boxes bg1 ">
								<i class="ti ti-user fs"></i>

								<div class="text-end">
									<label> <?php echo $totalcust; ?></label>
									<h4>Total Customer</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>
						<div class="col-md-3 mb-2">
							<?php $query2 = mysqli_query($con, "Select * from tblappointment");
							$totalappointment = mysqli_num_rows($query2);
							?>
							<div class="dashboard-boxes bg2">
								<i class="ti ti-list fs"></i>


								<div class="text-end">

									<label> <?php echo $totalappointment; ?></label>
									<h4>Total Appointment</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>
						<div class="col-md-3 mb-2">
							<?php $query3 = mysqli_query($con, "Select * from tblappointment where Status='1'");
							$totalaccapt = mysqli_num_rows($query3);
							?>
							<div class="dashboard-boxes bg3">
								<i class="ti ti-check fs"></i>

								<div class="text-end">
									<label><?php echo $totalaccapt; ?></label>
									<h4>Total Accepted Apt</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>

						<div class="col-md-3 mb-2">
							<?php $query4 = mysqli_query($con, "Select * from tblappointment where Status='2'");
							$totalrejapt = mysqli_num_rows($query4);
							?>
							<div class="dashboard-boxes bg4">
								<i class="ti ti-file fs"></i>


								<div class="text-end">
									<label> <?php echo $totalrejapt; ?></label>
									<h4>Total Rejected Apt</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>
						<div class="col-md-3 mb-2">
							<?php $query5 = mysqli_query($con, "Select * from  tblservices");
							$totalser = mysqli_num_rows($query5);
							?>
							<div class="dashboard-boxes bg5">
								<i class="ti ti-hotel-service fs"></i>


								<div class="text-end">
									<label> <?php echo $totalser; ?></label>
									<h4>Total Services</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>
						<div class="col-md-3 mb-2">
							<?php
							$query6 = mysqli_query($con, "SELECT SUM(total * (1 + tax/100)) as grand_total FROM tblinvoice WHERE date(PostingDate)=CURDATE()");
							$row = mysqli_fetch_array($query6);
							$todysale = $row['grand_total'] ?: 0;
							?>
							<div class="dashboard-boxes bg6">
								<i class="ti ti-tags fs"></i>


								<div class="text-end">
									<label><?php echo number_format($todysale, 2); ?></label>
									<h4>Today Sales</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>





						<div class="col-md-3 mb-2">
							<?php
							$query7 = mysqli_query($con, "SELECT SUM(total * (1 + tax/100)) as grand_total FROM tblinvoice WHERE date(PostingDate)=CURDATE()-1");
							$row7 = mysqli_fetch_array($query7);
							$yesterdaysale = $row7['grand_total'] ?: 0;
							?>
							<div class="dashboard-boxes bg7">
								<i class="ti ti-credit-card fs"></i>


								<div class="text-end">
									<label><?php echo number_format($yesterdaysale, 2); ?></label>
									<h4>Yesterday Sales</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>


						<div class="col-md-3 mb-2">
							<?php
							$query8 = mysqli_query($con, "SELECT SUM(total * (1 + tax/100)) as grand_total FROM tblinvoice WHERE date(PostingDate)>=(DATE(NOW()) - INTERVAL 7 DAY)");
							$row8 = mysqli_fetch_array($query8);
							$tseven = $row8['grand_total'] ?: 0;
							?>
							<div class="dashboard-boxes bg8">
								<i class="ti ti-files fs"></i>


								<div class="text-end">
									<label><?php echo number_format($tseven, 2); ?></label>
									<h4>Last Sevendays Sale</h4>

								</div>
							</div>
							<div class="clearfix"> </div>
						</div>


						<div class="col-md-3 mb-2">
							<?php
							$query9 = mysqli_query($con, "SELECT SUM(total * (1 + tax/100)) as grand_total FROM tblinvoice");
							$row9 = mysqli_fetch_array($query9);
							$totalsale = $row9['grand_total'] ?: 0;
							?>
							<div class="dashboard-boxes bg9">
								<i class="ti ti-pig-money fs"></i>


								<div class="text-end">
									<label><?php echo number_format($totalsale, 2); ?></label>
									<h4>Total Sales</h4>

								</div>
							</div>

							<div class="clearfix"> </div>
						</div>






						<div class="clearfix"> </div>
					</div>




				</div>

				<h3 class="title1" style="margin-top: 18px;">Recent Appointments</h3>
				<div class="table-responsive bs-example widget-shadow">
					<table id="example" class="table table-bordered mt-3">
						<thead>
							<tr>
								<th>#</th>
								<th> Appointment Number</th>
								<th>Name</th>
								<th>Mobile Number</th>
								<th>Appointment Date</th>
								<th>Appointment Time</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$ret = mysqli_query($con, "select *from  tblappointment where Status='1' ");
							$cnt = 1;
							while ($row = mysqli_fetch_array($ret)) {

								?>

								<tr>
									<th scope="row"><?php echo $cnt; ?></th>
									<td><?php echo $row['AptNumber']; ?></td>
									<td><?php echo $row['Name']; ?></td>
									<td><?php echo $row['PhoneNumber']; ?></td>
									<td><?php echo $row['AptDate']; ?></td>
									<td><?php echo $row['AptTime']; ?></td>
									<td><a href="view-appointment.php?viewid=<?php echo $row['ID']; ?>">View</a></td>
								</tr> <?php
								$cnt = $cnt + 1;
							} ?>
						</tbody>
					</table>

				</div>


				<div class="mt-3 calendar-shell">
					<div id="calendar"></div>
				</div>



			</div>
			<div class="clearfix"> </div>
		</div>
	</div>
	<!--footer-->
	<?php include_once('includes/footer.php'); ?>
	<!--//footer-->
	</div>

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

	<!-- FullCalendar JS -->
	<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>


	<script>
		new DataTable('#example', {
			layout: {
				topStart: {
					buttons: ['copy', 'excel', 'pdf', 'colvis']
				}
			}
		});
	</script>

	<!-- full calender script -->
	<!--<script>-->
	<!--  document.addEventListener('DOMContentLoaded', function () {-->
	<!--    const calendarEl = document.getElementById('calendar');-->

	<!--    const calendar = new FullCalendar.Calendar(calendarEl, {-->
	<!--      initialView: 'dayGridMonth',-->
	<!--      headerToolbar: {-->
	<!--        left: 'prev,next today',-->
	<!--        center: 'title',-->
	<!--        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'-->
	<!--      },-->
	<!--      events: [-->
	<!--        {-->
	<!--          title: 'Team Meeting',-->
	<!--          start: '2025-06-03T10:30:00',-->
	<!--          end: '2025-06-03T12:30:00'-->
	<!--        },-->
	<!--        {-->
	<!--          title: 'Project Deadline',-->
	<!--          start: '2025-06-08',-->
	<!--          color: '#ff0000'-->
	<!--        },-->
	<!--        {-->
	<!--          title: 'Conference',-->
	<!--          start: '2025-06-13',-->
	<!--          end: '2025-06-15'-->
	<!--        }-->
	<!--      ]-->
	<!--    });-->

	<!--    calendar.render();-->
	<!--  });-->
	<!--</script>-->

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const calendarEl = document.getElementById('calendar');

			const calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				headerToolbar: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
				},
				events: 'fetch-appointments.php' // 🔄 Dynamic endpoint
			});

			calendar.render();
		});
	</script>




</body>

</html>
