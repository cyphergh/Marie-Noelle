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
      <title>Marie Noelle Spa and Salon | Add Customers</title>
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
      <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                  <h3 class="title1">Add Invoice</h3>
                  <div class="form-grids row widget-shadow" data-example-id="basic-forms">
                     <div class="form-title">
                        <h4>Invoice Builder</h4>
                        <p style="margin-top:8px;color:#766a5f;">Select a customer, add products, calculate tax automatically, and submit the invoice with the same workflow.</p>
                     </div>

                     <div class="form-body">
                        <form method="POST" action="add_invoice.php" class="row" enctype="multipart/form-data">

                           <div class="form-group col-md-6">
                              <label>Customer Name</label>
                              <select class="form-control" id="name" name="Userid" required="true">
                                 <option value="">Select Name</option>
                                 <?php
                                 $ret2 = mysqli_query($con, "select *from  tblcustomers");
                                 $cnt = 1;
                                 while ($row2 = mysqli_fetch_array($ret2)) {

                                    ?>
                                    <option value="<?php echo $row2['ID']; ?>" data-name="<?php echo $row2['Name']; ?>">
                                       <?php echo $row2['Name']; ?>
                                    </option>
                                 <?php } ?>
                              </select>
                           </div>

                           <div class="form-group col-md-6">
                              <label>Email</label>
                              <input type="text" id="email" name="" class="form-control" placeholder="Email" required>
                           </div>
                           <div class="form-group col-md-6">
                              <label>Phone</label>
                              <input type="text" id="phone" name="" class="form-control" placeholder="Phone Number"
                                 required>
                           </div>

                           <div class="form-group col-md-6">
                              <label>Date</label>
                              <input type="date" id="inv_date" name="" value="<?php echo date('Y-m-d'); ?>"
                                 class="form-control" required>
                           </div>

                           <div class="col-md-12">
                              <div class="mydiv  align-items-center">

                                 <div class="form-group row control-group after-add-more subdiv">

                                    <div class="col-sm-3  align-items-end">
                                       <label>Add Product</label>
                                       <button class="btn btn-success btn-lg add-more d-flex btn-sm" type="button"
                                          title="Add Product"><i class="fa fa-plus"></i></button>
                                    </div>
                                 </div>
                              </div>
                              <div class="copy hide">
                                 <div class="form-group control-group row subdiv">
                                    <div class="col-sm-3">
                                       <label>Product</label>&nbsp;&nbsp;&nbsp;&nbsp;
                                       <select class="form-control service-dropdown" name="ServiceId[]">
                                          <option value="">Select Name</option>
                                          <?php
                                          $ret2 = mysqli_query($con, "select *from  tblservices where type = 1 ");
                                          $cnt = 1;
                                          while ($row2 = mysqli_fetch_array($ret2)) {

                                             ?>
                                             <option value="<?php echo $row2['ID']; ?>"><?php echo $row2['ServiceName']; ?>
                                             </option>
                                          <?php } ?>
                                       </select>
                                    </div>
                                    <div class="col-sm-2">
                                       <label>Avail Qty</label>
                                       <input type="text" name="" class="form-control available-qty" readonly="">
                                    </div>
                                    <div class="col-sm-1">
                                       <label>Qty</label>
                                       <input type="text" name="qty[]" class="form-control qty-input">
                                    </div>
                                    <div class="col-sm-2">
                                       <label>Price</label>
                                       <input type="text" name="" class="form-control price" readonly="">
                                    </div>
                                    <div class="col-sm-2">
                                       <label>Total</label>
                                       <input type="text" name="" class="form-control total">
                                    </div>
                                    <div class="col-sm-1 d-flex align-items-end mt-5">
                                       <button class="btn btn-danger remove btn-lg btn-sm" type="button"><i
                                             class="fa fa-minus"></i></button>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="form-group row ">
                              <label class="col-sm-6 control-label mt-5"> Total Amount</label>
                              <div class="col-sm-3 mt-5">
                                 <input type="text" name="" id="advance_total" onblur="myFunction()" class="form-control">
                              </div>
                           </div>
                           <div class="form-group row">
                              <label class="col-sm-6 control-label ">Tax</label>
                              <div class="col-sm-3">
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
                              </div>
                           </div>

                           <div class="form-group row">
                              <label class="col-sm-6 control-label"> Grand Amount</label>
                              <div class="col-sm-3">
                                 <input type="text" name="total" id="grand_total" onblur="myFunction()"
                                    class="form-control">
                              </div>
                           </div>
                           <div class="form-group row">
                              <label class="col-sm-6 control-label">Payment Method</label>
                              <div class="col-sm-3">
                                 <select name="payment_method" class="form-control select3">
                                    <option value="1">CASH</option>
                                    <option value="2">ONLINE</option>
                                    <option value="3">CHEQUE</option>
                                    <option value="4">DEBIT CARD</option>
                                    <option value="5">CREDIT CARD</option>
                                    <option value="6">UPI</option>
                                    <option value="7">NET BANKING</option>
                                    <option value="8">PAYTM</option>
                                    <option value="9">GOOGLE PAY</option>
                                    <option value="10">PHONEPE</option>
                                    <option value="11">BANK TRANSFER</option>
                                 </select>
                              </div>
                           </div>
                           <button type="submit" name="submit"
                              class="btn btn-primary btn-flat m-b-30 m-t-30">Create Invoice</button>

                           <!--<div class="col-md-12">-->
                           <!--	<button type="submit" name="submit" class="btn btn-default">Add</button> -->
                           <!--</div>-->
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
         <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
         <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
         <script>
            $(document).ready(function () {
               $('#name').on('change', function () {
                  var customerId = $(this).val();
                  var selectedName = $('#name option:selected').data('name'); // fetch name from data-name

                  // Set hidden field to selected name
                  $('#customer_name').val(selectedName);

                  if (customerId != '') {
                     $.ajax({
                        url: 'get_customer_details.php',
                        method: 'POST',
                        data: { id: customerId },
                        dataType: 'json',
                        success: function (data) {
                           $('#email').val(data.email);
                           $('#phone').val(data.phone);
                        }
                     });
                  } else {
                     $('#email').val('');
                     $('#phone').val('');
                     $('#customer_name').val('');
                  }
               });
            });

         </script>


         <script>
            $(document).on('change', '.service-dropdown', function () {
               var serviceId = $(this).val();
               var parentRow = $(this).closest('.subdiv');

               if (serviceId) {
                  $.ajax({
                     url: 'get_service_data.php',
                     method: 'POST',
                     data: { service_id: serviceId },
                     dataType: 'json',
                     success: function (response) {
                        parentRow.find('.available-qty').val(response.opening_stock);
                        parentRow.find('.price').val(response.price);
                     }
                  });
               } else {
                  parentRow.find('.available-qty').val('');
                  parentRow.find('.price').val('');
               }
            });

            $(document).on('input', '.qty-input', function () {
               var parentRow = $(this).closest('.subdiv');
               var qty = parseFloat(parentRow.find('.qty-input').val()) || 0;
               var availQty = parseFloat(parentRow.find('.available-qty').val()) || 0;
               var price = parseFloat(parentRow.find('.price').val()) || 0;


               if (qty > availQty) {
                  alert("Entered quantity exceeds available stock!");
                  $(this).val(''); // Clear invalid input
                  parentRow.find('.total').val('');
                  return;
               }
               var total = qty * price;
               parentRow.find('.total').val(total.toFixed(2));
            });
         </script>


         <script>
            function calculateRowTotal(row) {
               var qty = parseFloat(row.find('.qty-input').val()) || 0;
               var price = parseFloat(row.find('.price').val()) || 0;
               var total = qty * price;
               row.find('.total').val(total.toFixed(2));
            }

            function calculateTotalAmount() {
               var totalAmount = 0;

               $('.total').each(function () {
                  totalAmount += parseFloat($(this).val()) || 0;
               });

               $('#advance_total').val(totalAmount.toFixed(2));

               // Get selected tax percentage
               var taxPercent = parseFloat($('#tax').val()) || 0;
               var taxAmount = (totalAmount * taxPercent) / 100;

               var grandTotal = totalAmount + taxAmount;
               $('#grand_total').val(grandTotal.toFixed(2));
            }

            // Event: qty or price input changes
            $(document).on('input', '.qty-input, .price', function () {
               var row = $(this).closest('.subdiv');
               calculateRowTotal(row);
               calculateTotalAmount();
            });

            // Event: tax selection changes
            $('#tax').on('change', function () {
               calculateTotalAmount();
            });

            // Event: remove row
            $(document).on('click', '.remove', function () {
               $(this).closest('.subdiv').remove();
               calculateTotalAmount();
            });
         </script>


         <script type="text/javascript">
            $("body").on("click", ".remove", function () {
               $(this).parents(".control-group").remove();
            });

            $(".add-more").on('click', function () {
               var html = $(".copy").html();
               $(".after-add-more").before(html);
               show_no();
            });

            $("body").on("click", ".remove", function () {
               $(this).parents(".control-group").remove();
               show_no();
            });
            function show_no() {
               var row_cnt = 0;
               $(".sr_no").each(function () {
                  row_cnt++;
                  $(this).html(row_cnt);
               });
            }
         </script>
         <script>
            $(document).ready(function () {
               $('.select2').select2();
            });
         </script>
   </body>

   </html>
<?php } ?>
