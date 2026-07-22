<?php
session_start();
if (strlen($_SESSION['bpmsaid'] == 0)) {
    header('location:logout.php');
    exit;
}

header('location:manage_plan.php');
exit;
