<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member');


// if (isset ($_SESSION['basket_id']))
//   {
//     $basket_id = $_SESSION['basket_id'];
//   }
// else
//   {
//     $basket_id = 0;
//   };

$sql = '
  SELECT
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.*
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.NEW_TABLE_BASKETS.'.delivery_id
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = '.mysql_real_escape_string ($_SESSION['member_id']).'
    AND '.TABLE_ORDER_CYCLES.'.delivery_date < "'.ActiveCycle::delivery_date().'"
  ORDER BY
    '.TABLE_ORDER_CYCLES.'.delivery_date DESC';

$rs = @mysql_query($sql, $connection) or die(debug_print ("ERROR: 896239 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($rs) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];
    include("func/convert_delivery_date.php");
    $display .= '<li> <a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'">'.$delivery_date.'</a><br>';
  }

$content_orders = '
  <div align="center">
    <table width="80%">
      <tr>
        <td align="left">
          <h3>All Previous Customer Invoices for '.$_SESSION['show_name'].'</h3>
          <ul>
          '.$display.'
          </ul>
        </td>
      </tr>
    </table>
  </div>';

$page_title_html = '<span class="title">Order Info</span>';
$page_subtitle_html = '<span class="subtitle">Past Customer Invoices</span>';
$page_title = 'Past Customer Invoices';
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_orders.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
