<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('producer,producer_admin');


$query = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    date_open <= NOW()
  ORDER BY
    delivery_id DESC';
$sql = mysql_query($query, $connection);
while ( $row = mysql_fetch_array($sql) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];

    include("func/convert_delivery_date.php");
    $display2 .= '<li><a href="show_report.php?type=producer_invoice&delivery_id='.$delivery_id.'">'.$delivery_date.'</a>';
  }

$producer_id = $_SESSION['producer_id_you'];
include("func/show_businessname.php");

$page_title_html = '<span class="title">'.$business_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Past Producer Invoices</span>';
$page_title = $business_name.': Past Producer Invoices';
$page_tab = 'producer_panel';

$content_display2 = '
<div align="center">
<table width="80%">
  <tr>
    <td align="left">
      <h3>All Previous and Current Producer Invoices for '.$business_name.'</h3>
      <ul>
      '.$display2.'
      </ul>
    </td>
  </tr>
</table>
</div>';

include("template_header.php");
echo '
  <!-- CONTENT ENDS HERE -->
  '.$content_display2.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
