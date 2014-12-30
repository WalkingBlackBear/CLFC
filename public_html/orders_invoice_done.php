<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member');


$sql = '
  SELECT
    member_id,
    delivery_id,
    invoice_content
  FROM
    '.NEW_TABLE_BASKETS.'
  WHERE
    member_id = '.mysql_real_escape_string ($_SESSION['member_id']).'
    AND delivery_id = '.mysql_real_escape_string ($_GET['delivery_id']);
$rs = @mysql_query($sql,$connection) or die(debug_print ("ERROR: 763089 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $invoice_content = $row['invoice_content'];
  }


$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Finalized Invoice</span>';
$page_title = strip_tags ($_SESSION['show_name']).': Finalized Invoice';
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$invoice_content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
