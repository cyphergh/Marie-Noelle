<div id="page"></div>
<div id="loading"></div>

<?php
$ret1 = mysqli_query($con, "select ID,Name from tblappointment where Status=''");
$num = mysqli_num_rows($ret1);
$adid = $_SESSION['bpmsaid'];
$ret = mysqli_query($con, "select AdminName from tbladmin where ID='$adid'");
$row = mysqli_fetch_array($ret);
$name = $row['AdminName'];
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<div class="sticky-header header-section">
  <div class="header-left">
    <button id="showLeftPush" class="header-toggle" aria-label="Toggle navigation">
      <i class="fa fa-bars"></i>
    </button>
    <div class="header-brand">
      <a href="dashboard.php" class="header-brand-link">
        <img src="images/logo.png" alt="Marie Noelle Spa and Salon logo" class="logo-top">
      </a>
      <div class="header-brand-copy">
        <span class="header-kicker">Marie Noelle Spa and Salon</span>
        <strong>Admin Workspace</strong>
      </div>
    </div>
  </div>

  <div class="header-right">
    <div class="header-tools">
      <div class="header-translate">
        <div id="google_translate_element"></div>
      </div>

      <div class="dropdown head-dpdn header-alert">
        <a href="#" class="dropdown-toggle header-alert-toggle" data-toggle="dropdown" aria-expanded="false">
          <i class="fa fa-bell"></i>
          <span class="header-alert-count"><?php echo $num; ?></span>
        </a>
        <ul class="dropdown-menu header-dropdown">
          <li class="notification_header">
            <h3><?php echo $num; ?> appointment notifications</h3>
          </li>
          <li>
            <div class="notification_desc">
              <?php if ($num > 0) {
                while ($result = mysqli_fetch_array($ret1)) { ?>
                  <a class="dropdown-item" href="view-appointment.php?viewid=<?php echo $result['ID']; ?>">
                    New appointment received from <?php echo $result['Name']; ?>
                  </a>
                <?php }
              } else { ?>
                <a class="dropdown-item" href="all-appointment.php">No new appointments right now</a>
              <?php } ?>
            </div>
          </li>
          <li class="notification_bottom">
            <a href="new-appointment.php">Open notifications</a>
          </li>
        </ul>
      </div>
    </div>

    <div class="profile_details">
      <ul>
        <li class="dropdown profile_details_drop">
          <a href="#" class="dropdown-toggle profile-trigger" data-toggle="dropdown" aria-expanded="false">
            <div class="profile_img">
              <div class="user-name">
                <p><?php echo $name; ?></p>
                <span>Administrator</span>
              </div>
              <i class="fa fa-angle-down lnr"></i>
            </div>
          </a>
          <ul class="dropdown-menu drp-mnu header-dropdown profile-menu">
            <li class="profile-menu-head">
              <div class="profile-summary">
                <strong><?php echo $name; ?></strong>
                <span>Administrator</span>
              </div>
            </li>
            <li><a href="change-password.php"><i class="fa fa-cog me-2"></i> Settings</a></li>
            <li><a href="admin-profile.php"><i class="fa fa-user me-2"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out me-2"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</div>



<script type="text/javascript">
  function googleTranslateElementInit() {
    new google.translate.TranslateElement({ pageLanguage: 'en' }, 'google_translate_element');
  }
</script>

<script type="text/javascript"
  src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script>
$(document).ready(function() {
  function initDrawer() {
    if ($('.sidebar-overlay').length === 0) {
      $('body').append('<div class="sidebar-overlay"></div>');
    }
    
    var $sidebar = $('.sidebar');
    var $overlay = $('.sidebar-overlay');
    var $toggleBtn = $('#showLeftPush');
    
    $toggleBtn.off('click').on('click', function(e) {
      e.preventDefault();
      if (window.innerWidth >= 1200) {
        return;
      }
      $sidebar.toggleClass('cbp-spmenu-open');
      $overlay.toggleClass('active');
    });
    
    $overlay.off('click').on('click', function() {
      $sidebar.removeClass('cbp-spmenu-open');
      $(this).removeClass('active');
    });
    
    $(window).off('resize.drawer').on('resize.drawer', function() {
      if (window.innerWidth >= 1200) {
        $sidebar.removeClass('cbp-spmenu-open');
        $overlay.removeClass('active');
      }
    });
  }
  
  initDrawer();
  
  window.initDrawer = initDrawer;
});
</script>
