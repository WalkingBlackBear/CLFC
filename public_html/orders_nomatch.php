<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin');

$hub = '';
$sql2 = '
  SELECT
    '.TABLE_BASKET_ALL.'.basket_id AS basket_id_big
  FROM
    '.TABLE_BASKET_ALL.'
  GROUP BY
    '.TABLE_BASKET_ALL.'.basket_id
  ORDER BY
    basket_id ASC';
$rs2 = @mysql_query($sql2, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Listing customer orders ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
$num_orders2 = mysql_numrows($rs2);
while ( $row = mysql_fetch_array($rs2) )
  {
    $basket_id_big = $row['basket_id_big'];
    $basket_id_big_list .= "#$basket_id_big";
  }
$sql = '
  SELECT
    '.TABLE_BASKET.'.basket_id AS basket_id_small
  FROM
    '.TABLE_BASKET.'
  GROUP BY
    '.TABLE_BASKET.'.basket_id
  ORDER BY
    basket_id ASC';
$rs = @mysql_query($sql, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Listing customer orders ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
$num_orders1 = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $basket_id_small = $row['basket_id_small'];
    $basket_id_small = "#$basket_id_small";
    $pos = strpos($basket_id_big_list,$basket_id_small);
    if ( $pos === false )
      {
        $display .= '<b>no match for '.$basket_id_small.' in basket small list</b><br> ';
      }
    else
      {
        $display .= '';
      }
  }

$content_orders = '
<table width="100%">
  <tr>
    <td align="left">
      <h3>An admin check for basket items with non-matching overall baskets</h3>
      '.($display ? $display : 'All&#146;s good, everything has a match across basket tables from items to overall.').'
    </td>
  </tr>
</table>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Check for Missing Baskets</span>';
$page_title = 'Admin Maintenance: Check for Missing Baskets';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_orders.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

