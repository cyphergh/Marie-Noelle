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
        <title>Marie Noelle Spa and Salon || Invoice List</title>
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
                    <div class="tables" id="exampl">
                        <h3 class="title1">Invoice Details</h3>

<?php
                        $invid = intval($_GET['invoiceid']);

                        $ret = mysqli_query($con, "
    SELECT  
        tblinvoice.PostingDate,
        tblinvoice.tax,
        tblinvoice.total,
        tblcustomers.Name,
        tblcustomers.Email,
        tblcustomers.MobileNumber
    FROM tblinvoice 
    JOIN tblcustomers ON tblcustomers.ID = tblinvoice.Userid 
    WHERE tblinvoice.BillingId = '$invid' AND tblinvoice.ServiceId = '0'
    LIMIT 1
");

                        $invoiceData = mysqli_fetch_array($ret);
                    
                        ?>

                        <div class="table-responsive bs-example widget-shadow">
                            <h4>Invoice #<?php echo $invid; ?></h4>
                            <table class="table table-bordered" width="100%" border="1">
                                <tr>
                                    <th colspan="6">Customer Details</th>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td><?php echo $invoiceData['Name']; ?></td>
                                    <th>Contact No.</th>
                                    <td><?php echo $invoiceData['MobileNumber']; ?></td>
                                    <th>Email</th>
                                    <td><?php echo $invoiceData['Email']; ?></td>
                                </tr>
                                <tr>
                                    <th>Invoice Date</th>
                                    <td colspan="3"><?php echo date('d-m-Y', strtotime($invoiceData['PostingDate'])); ?>
                                    </td>
                                </tr>
                            </table>

                            <table class="table table-bordered" width="100%" border="1">
                                <tr>
                                    <th colspan="3">Details</th>
                                </tr>
                                <tr>
                                    <th>#</th>
                                    <th>Service/Product</th>
                                    <th>Cost (GH₵)</th>
                                </tr>

                                <?php
                                $ret = mysqli_query($con, "
            SELECT tblservices.ServiceName, tblservices.Cost  
            FROM tblinvoice 
            JOIN tblservices ON tblservices.ID = tblinvoice.ServiceId 
            WHERE tblinvoice.BillingId = '$invid' AND tblinvoice.ServiceId != '0'
        ");

                                $cnt = 1;
                                $gtotal = 0;
                                while ($row = mysqli_fetch_array($ret)) {
                                    $subtotal = $row['Cost'];
                                    $gtotal += $subtotal;
                                    ?>
                                    <tr>
                                        <th><?php echo $cnt++; ?></th>
                                        <td><?php echo $row['ServiceName']; ?></td>
                                        <td><?php echo number_format($subtotal, 2); ?></td>
                                    </tr>
                                <?php } ?>

                                <tr>
                                    <th colspan="2" style="text-align:center">Total</th>
                                    <th>GH₵<?php echo number_format($gtotal, 2); ?></th>
                                </tr>

                                <tr>
                                    <th colspan="2" style="text-align:center">Tax (<?php echo $invoiceData['tax']; ?>%)</th>
                                    <th>GH₵<?php 
										$taxAmount = ($gtotal * $invoiceData['tax']) / 100;
										echo number_format($taxAmount, 2); 
									?></th>
                                </tr>

                                <tr style="background-color:#e8f5e9;">
                                    <th colspan="2" style="text-align:center">Grand Total</th>
                                    <th>GH₵<?php echo number_format($invoiceData['total'], 2); ?></th>
                                </tr>
                            </table>
                            <p style="margin-top:1%" align="center">
                                <i class="fa fa-print fa-2x" style="cursor: pointer;" OnClick="CallPrint(this.value)"></i>
                            </p>

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
        <script>
            function CallPrint(strid) {
                var prtContent = document.getElementById("exampl");
                var WinPrint = window.open('', '', 'left=0,top=0,width=800,height=900,toolbar=0,scrollbars=0,status=0');
                WinPrint.document.write(prtContent.innerHTML);
                WinPrint.document.close();
                WinPrint.focus();
                WinPrint.print();
            }
        </script>
    </body>

    </html>
<?php } ?>