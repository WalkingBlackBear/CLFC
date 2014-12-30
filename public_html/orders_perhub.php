<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


$display = '
  <tr bgcolor=#EEEEEE><th>HUB</th><th>Total Orders</th><th>Total Sales</th></tr>';
$sql = mysql_query('
  SELECT
    SUM(co.grand_total) AS grand_total,
    dc.hub,
    count(co.basket_id) as total_baskets
  FROM
    '.TABLE_BASKET_ALL.' co,
    '.TABLE_DELCODE.' dc
  WHERE
    co.delcode_id = dc.delcode_id
  GROUP BY
    dc.hub
  ORDER BY
    dc.hub ASC');
$num_orders = mysql_numrows($sql);
while ( $row = mysql_fetch_array($sql) )
  {
    $display .= '
      <tr><td><b>'.$row['hub'].'</b></td>
      <td align=right>'.$row['total_baskets'].'</td><td align=right>$'.number_format($row['grand_total'],2).'</td></tr>
      ';
  }

$content = '
<table width="90%">
  <tr>
    <td align="left">
      <h3>Total Orders and Sales per Hub</h3>
      <table cellpadding="2" cellspacing="2" border="0">
        '.$display.'
      </table>
    </td>
  </tr>
</table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Sales per Hub</span>';
$page_title = 'Reports: Sales per Hub';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
