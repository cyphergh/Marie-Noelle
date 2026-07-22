<?php
//$host = "localhost";
//$user = "u194207665_spa";
//$password = "@123Welcome098";
//$database = "u194207665_spa";
//$con = mysqli_connect($host, $user, $password, $database);
//if (mysqli_connect_errno()) {
//  echo "Connection Fail" . mysqli_connect_error();
//}

 $host = "localhost";
 $user = "root";
 $password = "toor";
 $database = "spa";
 $con = mysqli_connect($host, $user, $password, $database);
 if (mysqli_connect_errno()) {
   echo "Connection Fail" . mysqli_connect_error();
 }

?>
