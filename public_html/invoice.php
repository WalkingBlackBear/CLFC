<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier,member_admin');

$sql = '
  SELECT
    basket_id,
    '.TABLE_BASKET_ALL.'.member_id,
    preferred_name,
    invoice_content
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($_GET['member_id']).'"
    AND basket_id = "'.mysql_real_escape_string ($_GET['basket_id']).'"
    AND finalized = "1"';
$rs = @mysql_query($sql,$connection) or die("Couldn't execute query.");
$num = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $preferred_name = $row['preferred_name'];
    $invoice_content = $row['invoice_content'];
  }

$page_title_html = '<span class="title">'.$preferred_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Finalized Invoice</span>';
$page_title = strip_tags ($preferred_name).': Finalized Invoice';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$invoice_content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
