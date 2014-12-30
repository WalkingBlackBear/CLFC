<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

include("../func/gen_invoice.php");


// DEBUG... is this the correct default?
$use = 'admin';

if ($_POST['use'] == 'adminfinalize')
  {
    $use = 'adminfinalize';
  }

$display_page = geninvoice($_GET['member_id'], $_GET['basket_id'], $_GET['delivery_id'], $use);



$page_title_html = '<span class="title">'.$show_name.'</span>';
$page_subtitle_html = '<span class="subtitle">In-process Invoice</span>';
$page_title = strip_tags ($show_name).': In-process Invoice';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.($message != '' ? '<br><center><b>'.$message.'</b></center><br><br>' : '').'
  '.$display_page.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
