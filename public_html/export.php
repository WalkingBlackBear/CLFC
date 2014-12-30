<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


if($_POST['export'])
  {
    header("Content-type: application/octet-stream"); 
    header("Content-Disposition: attachment; filename=foodcoop-".date('Y-m-d').".csv"); 
    header("Pragma: no-cache"); 
    header("Expires: 0"); 
    print $_POST['export'];
    exit;  
  }


?>