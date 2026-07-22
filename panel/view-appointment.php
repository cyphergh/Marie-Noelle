<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
?>

<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['bpmsaid'] == 0)) {
  header('location:logout.php');
} else {


  if (isset($_POST['submit'])) {

    //   print_r($_POST);exit;
    $cid = $_GET['viewid'];
    $name = $_POST['Userid'];
    $remark = $_POST['remark'];
    $status = $_POST['status'];
    $tax = $_POST['tax'];
    $grand_total = $_POST['total'];
    $total = $_POST['service_total'];
    $staffId = $_POST['staff'];
    $paymentStatus = $_POST['payment_status'];
    $paymentMethod = $_POST['payment_method'];
    $posting_date = date('Y-m-d H:i:s');


    if ($status == 1) {


      // First update the appointment
      $update = mysqli_query($con, "UPDATE tblappointment 
        SET Remark='$remark', Status='$status',total ='$total',  grand_total='$grand_total', payment_status='$paymentStatus', payment_method='$paymentMethod' 
        WHERE ID='$cid'");
      //  print_r($_POST); exit;
      if ($update) {
        $invoiceid = mt_rand(100000000, 999999999);

        $res = mysqli_query($con, "SELECT * FROM tblappointment WHERE ID='$cid'");
        $row = mysqli_fetch_assoc($res);
        $service_ids = explode(",", $row['Services']);

        $book_date = date('d-M-Y', strtotime($row['AptDate']));
        $book_time = date('g:i A', strtotime($row['AptTime']));
        $book_id = $row['AptNumber'];

        $serviceTotal = 0;
        foreach ($service_ids as $svid) {
          $svid = intval($svid);
          if ($svid > 0) {
            $svc = mysqli_query($con, "SELECT Cost FROM tblservices WHERE ID = $svid");
            if ($svc && $svcRow = mysqli_fetch_assoc($svc)) {
              $serviceTotal += (float) $svcRow['Cost'];
            }
          }
        }

        $serviceTotalFormatted = number_format($serviceTotal, 2, '.', '');

        $insert = mysqli_query($con, "INSERT INTO tblinvoice (
              Userid, ServiceId, BillingId, staff, tax, total, PostingDate, payment_method
          ) VALUES (
              '$name', '0', '$invoiceid', '$staffId', '$tax', '$serviceTotalFormatted', '$posting_date', '$paymentMethod'
          )");

        foreach ($service_ids as $svid) {
          $svid = intval($svid);
          if ($svid > 0) {
            mysqli_query($con, "INSERT INTO tblinvoice (
                Userid, ServiceId, BillingId, staff, tax, total, PostingDate, payment_method
            ) VALUES (
                '$name', '$svid', '$invoiceid', '$staffId', '0', '0', '$posting_date', '$paymentMethod'
            )");
          }
        }




        //          $res_email = mysqli_query($con, "SELECT * FROM emailsetting ");
        //     // $row_email = mysqli_fetch_assoc($res);
        // while ($row=mysqli_fetch_array($res_email)) {
        //     $smtp_server = $row['smtp_server'];
        //             $smtp_password = $row['smtp_password'];
        //             $smtp_enc = $row['smtp_type'];
        //             $smtp_username = $row['smtp_username'];
        //             $smtp_port = $row['stmp_port'];
        //             $email = $row['email'];


        // }


        //  $dt = date('Y-m-d H:i:s');

        //         $msg1 = "Dear Customer, <br>
        // Your Appointment Booked, Your Booking Id is  $book_id please come on $book_date Time: $book_time  Have new message from <br> 


        //  ";

        //         $mail = new PHPMailer(true);

        //         $mail->isSMTP();
        //         $mail->Host       = $smtp_server;
        //         $mail->SMTPAuth   = true;
        //         $mail->Username   = $smtp_username;
        //         $mail->Password   = $smtp_password;
        //         $mail->SMTPSecure = $smtp_enc;
        //         $mail->Port       = $smtp_port;

        //         $mail->setFrom($smtp_username);
        //         $mail->addAddress($email);

        //         $mail->isHTML(true);
        //         $mail->Subject = 'Salon Appointment: ' . $dt;
        //         $mail->Body    = $msg1;
        //         $mail->AltBody = $msg1;

        //         $mail->send();


        // print_r($smtp_password);exit;

        echo '<script>alert("Appointment Book and Email Send successfully.");</script>';
        echo "<script>window.location.href = 'all-appointment.php'</script>";
        //  exit;
      } else {
        echo "<script>alert('Something went wrong.');</script>";
      }


    } else if ($status == 2) {

      $update = mysqli_query($con, "UPDATE tblappointment 
        SET Remark='$remark', Status='$status' 
        WHERE ID='$cid'");

      echo '<script>alert("Appointment Rejected");</script>';
      echo "<script>window.location.href = 'all-appointment.php'</script>";

    }






  }
  ?>


  <!DOCTYPE HTML>
  <html>

  <head>
    <title>Marie Noelle Spa and Salon || View Appointment</title>
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
            <h3 class="title1">View Appointment</h3>



            <div class="table-responsive bs-example widget-shadow">
              <p style="font-size:16px; color:red" align="center"> <?php if ($msg) {
                echo $msg;
              } ?> </p>
              <h4>View Appointment:</h4>
              <?php
              $cid = $_GET['viewid'];
              $ret = mysqli_query($con, "select * from tblappointment where ID='$cid'");
              $cnt = 1;
              while ($row = mysqli_fetch_array($ret)) {

                //  $ret4=mysqli_query($con,"select * from tblcustomers where ID ='".$row['Name']."' ");
                // $row4=mysqli_fetch_array($ret4);
            
                ?>
                <table class="table table-bordered">
                  <tr>
                    <th>Appointment Number</th>
                    <td><?php echo $row['AptNumber']; ?></td>
                  </tr>
                  <tr>
                    <th>Name</th>
                    <td><?php
                    $retr = mysqli_query($con, "select * from tblcustomers where ID='" . $row['Name'] . "' ");
                    // $cnt=1;
                    while ($rowr = mysqli_fetch_array($retr)) {

                      echo $rowr['Name'];
                    } ?></td>
                  </tr>

                  <tr>
                    <th>Email</th>
                    <td><?php echo $row['Email']; ?></td>
                  </tr>
                  <tr>
                    <th>Mobile Number</th>
                    <td><?php echo $row['PhoneNumber']; ?></td>
                  </tr>
                  <tr>
                    <th>Appointment Date</th>
                    <td><?php echo date('d-M-Y', strtotime($row['AptDate'])); ?></td>
                  </tr>

                  <tr>
                    <th>Appointment Time</th>
                    <td><?php echo date('H:i', strtotime($row['AptTime'])); ?></td>
                  </tr>

                  <tr>
                    <th>Services</th>
                    <td>
                      <?php
                      $service_ids = explode(",", $row['Services']);
                      $service_names = [];
                      $total_cost = 0;
                      foreach ($service_ids as $sid) {
                        $sid = intval($sid);
                        $res = mysqli_query($con, "SELECT ServiceName , Cost FROM tblservices WHERE ID = $sid");
                        if ($srow = mysqli_fetch_assoc($res)) {
                          $service_names[] = $srow['ServiceName'] . " (GH₵" . number_format($srow['Cost'], 2) . ")";
                          $total_cost += $srow['Cost'];
                        }
                      }

                      echo implode(", ", $service_names);
                      ?>
                    </td>
                  </tr>
                  <tr>
                    <th>Subtotal</th>
                    <td>GH₵<?php echo number_format($total_cost, 2); ?></td>
                  </tr>
                  <tr>
                    <th>Tax</th>
                    <td>GH₵<?php 
                      $taxPercent = 0;
                      $ret2 = mysqli_query($con, "select SUM(value) as total_tax from tbl_tax");
                      if ($row2 = mysqli_fetch_assoc($ret2)) {
                        $taxPercent = (float)($row2['total_tax'] ?: 0);
                      }
                      $taxAmount = $total_cost * ($taxPercent / 100);
                      echo number_format($taxAmount, 2); 
                      ?> (<?php echo $taxPercent; ?>%)
                    </td>
                  </tr>
                  <tr>
                    <th>Grand Total</th>
                    <td><strong style="color: green;">GH₵<?php echo number_format($total_cost + $taxAmount, 2); ?></strong></td>
                  </tr>
                  <tr>
                    <th>Apply Date</th>
                    <td><?php echo date('d-M-Y', strtotime($row['ApplyDate'])); ?></td>
                  </tr>


                  <tr>
                    <th>Status</th>
                    <td> <?php
                    if ($row['Status'] == "1") {
                      echo "Selected";
                    }

                    if ($row['Status'] == "2") {
                      echo "Rejected";
                    }

                    ; ?></td>
                  </tr>
                </table>
                <table class="table table-bordered">
                  <?php if ($row['Remark'] == "") { ?>


                    <form name="submit" method="post" enctype="multipart/form-data">

                      <tr>
                        <th>Remark :</th>
                        <td>
                          <textarea name="remark" placeholder="" rows="3" cols="14" class="form-control wd-450"
                            required="true"></textarea>
                        </td>
                      </tr>

                      <tr>
                        <th>Status :</th>

                        <td>
                          <select name="status" id="status" class="form-control wd-450" required="true">
                            <option value="">Select</option>
                            <option value="1">Selected</option>
                            <option value="2">Rejected</option>
                          </select> <br>


                        </td>
                        <input type='hidden' id='Userid' name="Userid" value='<?php echo $row['Name']; ?>'>

                        <input type='hidden' id='service_total' name="service_total" value=' <?php echo $total_cost; ?>'>
                      <tr id="tax_row" style="display: none;">
                        <th>Tax (%) :</th>
                        <td>
                          <!--<input type="number" name="tax" id="tax" class="form-control wd-450" required>-->
                          <select class="form-control" id="tax" name="tax" required="true">
                            <option value="">Select Tax</option>
                            <?php
                            $ret2 = mysqli_query($con, "select *from  tbl_tax");
                            $cnt = 1;
                            while ($row2 = mysqli_fetch_array($ret2)) {

                              ?>
                              <option value="<?php echo $row2['value']; ?>"><?php echo $row2['name']; ?></option>
                            <?php } ?>
                          </select>
                        </td>
                      </tr>


                      <tr id="staff_row" style="display: none;">
                        <th>Assign Staff :</th>
                        <td>
                          <select class="form-control" id="staff" name="staff">
                            <option value="">Select Staff</option>
                            <?php
                            $ret2 = mysqli_query($con, "select *from tbl_staff");
                            while ($row2 = mysqli_fetch_array($ret2)) {
                              ?>
                              <option value="<?php echo $row2['id']; ?>"><?php echo $row2['name']; ?></option>
                            <?php } ?>
                          </select>
                        </td>
                      </tr>
                      <tr id="total_row" style="display: none;">
                        <th>Total :</th>
                        <td>
                          <input type="text" name="total" id="total" class="form-control wd-450" readonly>
                        </td>
                      </tr>

                      <tr id="payment_row" style="display: none;">
                        <th>Payment Status :</th>
                        <td>
                          <select class="form-control" id="payment_status" name="payment_status" required="true">
                            <option value="Paid">Paid</option>
                            <option value="Unpaid">Unpaid</option>
                          </select>
                        </td>
                      </tr>

                      <tr id="payment_method_row" style="display: none;">
                        <th>Payment Method :</th>
                        <td>
                          <select class="form-control" id="payment_method" name="payment_method">
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                          </select>
                        </td>
                      </tr>


                      <tr align="center">
                        <td colspan="2"><button type="submit" name="submit" class="btn btn-az-primary pd-x-20">Submit</button>
                        </td>
                      </tr>
                    </form>
                  <?php } else { ?>
                  </table>
                  <table class="table table-bordered">
                    <tr>
                      <th>Remark</th>
                      <td><?php echo $row['Remark']; ?></td>
                    </tr>


                    <tr>
                      <th>Remark date</th>
                      <td><?php echo date('d-m-Y', strtotime($row['ApplyDate'])); ?> </td>
                    </tr>

                  </table>
                <?php } ?>
                  <table class="table table-bordered">
                    <tr>
                      <th>Remark</th>
                      <td><?php echo $row['Remark']; ?></td>
                    </tr>


                    <tr>
                      <th>Remark date</th>
                      <td><?php echo date('d-m-Y', strtotime($row['ApplyDate'])); ?> </td>
                    </tr>
                    <tr>
                      <td colspan="2" align="center">
                        <button type="button" class="btn btn-danger" onclick="deleteAppointment(<?php echo $row['ID']; ?>)">
                          <i class="fa fa-trash"></i> Delete Appointment
                        </button>
                      </td>
                    </tr>

                  </table>
                <?php } ?>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(document).ready(function () {
        function toggleFieldsByStatus(status) {
          if (status === '1') {
            $('#tax_row').show();
            $('#total_row').show();
            $('#staff').closest('tr').show();
            $('#payment_row').show();
            $('#payment_method_row').show();

            $('#tax').attr('required', true);
            $('#staff').attr('required', true);
            $('#payment_status').attr('required', true);
            $('#payment_method').attr('required', true);
          } else {
            $('#tax_row').hide();
            $('#total_row').hide();
            $('#staff').closest('tr').hide();
            $('#payment_row').hide();
            $('#payment_method_row').hide();

            $('#tax').val('').removeAttr('required');
            $('#staff').val('').removeAttr('required');
            $('#total').val('');
            $('#payment_status').val('Unpaid').removeAttr('required');
            $('#payment_method').val('Cash').removeAttr('required');
          }
        }

        $('#status').on('change', function () {
          toggleFieldsByStatus($(this).val());
        });

        $('#tax').on('change', function () {
          var taxRate = parseFloat($(this).val()) || 0;
          var baseTotal = parseFloat($('#service_total').val()) || 0;

          var taxAmount = (baseTotal * taxRate) / 100;
          var finalTotal = baseTotal + taxAmount;

          $('#total').val(finalTotal.toFixed(2));
        });

        // Initialize on page load
        toggleFieldsByStatus($('#status').val());

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

        async function deleteAppointment(id) {
          showDeleteConfirm(
            'Are you sure you want to delete this appointment?',
            'This will also delete related invoices.',
            function() {
              fetch('delete-appointment.php', {
                method: 'POST',
                body: new URLSearchParams({ id: id }),
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                  'X-Requested-With': 'xmlhttprequest'
                }
              })
              .then(function(response) {
                return response.json();
              })
              .then(function(result) {
                if (result.success) {
                  alert('Appointment deleted successfully.');
                  window.location.href = 'all-appointment.php';
                } else {
                  alert(result.message || 'Failed to delete appointment.');
                }
              })
              .catch(function(error) {
                alert('Failed to delete appointment.');
              });
            }
          );
        }
      });
    </script>


  </body>

  </html>
<?php } ?>