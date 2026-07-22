<?php 
include('includes/dbconnection.php');
if(isset($_POST['submit']))
  {
      
    //   print_r($_POST);
    $opening_stock=$_POST['opening_stock'];
    
    $old_st =$_POST['opening_stock1'];
    
    $newstaock = $opening_stock + $old_st;
    $id=$_POST['ID'];
 

     
    $query=mysqli_query($con, "update  tblservices set opening_stock='$newstaock' where ID='$id' ");
    if ($query) {
    	echo "<script>alert('Stock Updated.');</script>"; 
    		echo "<script>window.location.href = 'manage-services.php'</script>";   
    
  }
  else
    {
    echo "<script>alert('Something Went Wrong. Please try again.');</script>";  	
    }

  
}
?>