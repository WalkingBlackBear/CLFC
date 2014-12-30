<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


if ( $_REQUEST['delivery_id'] )
  {
    $delivery_id = preg_replace("/[^0-9]/","",$_REQUEST['delivery_id']);
  }
else
  {
    $delivery_id = $_REQUEST['delivery_id'];
  }
if ($_REQUEST['producer_id'] > 0)
  {
    include("producer_finalize.php");
    producer_finalize::finalizeAll($_REQUEST['delivery_id'],$_REQUEST['producer_id']);
    $message = "<H3>The information has been saved.</h3>";
  }
if ( $message )
  {
    $content .= '<div align="center">'.$message.'</div>';
  }

$content .= '
  <form action="'.$_SERVER['PHP_SELF'].'" method="POST">
    <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
    <input type="hidden" name="set" value="1">
    Producer ID: <input type="text" name="producer_id" maxlength="5">
    <input type="submit" name="submit" value="Finalize producer invoice">
  </form>';

$page_title_html = '<span class="title">Treasurer Functions</span>';
$page_subtitle_html = '<span class="subtitle">Finalize Producer Invoices</span>';
$page_title = 'Treasurer Functions: Finalize Producer Invoices';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
