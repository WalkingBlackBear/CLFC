<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

$hub = '';
$random_min = 0;
$random_max = 0;
$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.basket_id AS big_basket_id,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delivery_id AS basket_delivery_id,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.auth_type,
    0 AS finalized,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.preferred_name,
    1 AS rte_confirmed,
    '.TABLE_DELCODE.'.delcode_id,
    '.NEW_TABLE_BASKETS.'.delcode_id,
    '.TABLE_DELCODE.'.hub,
    '.TABLE_ORDER_CYCLES.'.delivery_date
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.TABLE_DELCODE.' USING(delcode_id)
  LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($_GET['delivery_id']).'"
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id
  ORDER BY
    last_name ASC,
    '.NEW_TABLE_BASKETS.'.basket_id DESC';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 785033 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num_orders = mysql_numrows($result);

while ( $row = mysql_fetch_array($result) )
  {
    $hub = $row['hub'];
    $basket_id = $row['big_basket_id'];
    $basket_delivery_id = $row['basket_delivery_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    $rte_confirmed = $row['rte_confirmed'];
    $finalized = $row['finalized'];
    $preferred_name = $row['preferred_name'];
    $delivery_date = $row['delivery_date'];
    $subtotal = '';

    $query_total = '
      SELECT
        SUM(amount) AS total
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND source_type = "member"
        AND text_key != "customer fee"
        AND replaced_by IS NULL';
    $result_total = @mysql_query($query_total,$connection) or die(debug_print ("ERROR: 785033 ", array ($query_total,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    if ( $row_total = mysql_fetch_array($result_total) )
      {
        $total = $row_total['total'];
      }

    $bigtotal = $total + $bigtotal;
    $bigsubtotal = $bigsubtotal + $subtotal;
    $display .= '<tr bgcolor="#FFFFFF">';
    if ( $total < 0 )
      $bgcolor = '#CC9900';
    else
      $bgcolor = '#AEDE86';
    $display .= '<td valign="top" align="right" bgcolor="'.$bgcolor.'" id="'.$basket_id.'">$'.number_format($total,2).'</td>';
    $display .= '<td valign="top" align="right"># '.$member_id.'</td>';
    $display .= '<td valign="top"><a href="product_list.php?type=basket&delivery_id='.$_GET['delivery_id'].'&member_id='.$member_id.'&basket_id='.$basket_id.'"><strong>'.$last_name.':</strong> '.$preferred_name.'</a></td>';
    $display .= '<td valign="top">';
    $sqlp = '
      SELECT
        '.NEW_TABLE_BASKET_ITEMS.'.product_id,
        '.NEW_TABLE_PRODUCTS.'.producer_id
      FROM
        '.NEW_TABLE_BASKET_ITEMS.'
      LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
      WHERE
        '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($member_id).'"
        AND '.NEW_TABLE_BASKETS.'.basket_id = "'.mysql_real_escape_string ($basket_id).'"
        AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != '.NEW_TABLE_BASKET_ITEMS.'.quantity
        AND '.NEW_TABLE_PRODUCTS.'.random_weight != "0"
        AND '.NEW_TABLE_BASKET_ITEMS.'.total_weight = "0"
      ORDER BY producer_id ASC';
    $resultprp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 785033 ", array ($sqlp,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $num = mysql_numrows($resultprp);
    while ( $row = mysql_fetch_array($resultprp) )
      {
        $display .= '<a href="product_list.php?&type=producer_byproduct&producer_id='.$row['producer_id'].'&delivery_id='.$_GET['delivery_id'].'">Weight needed: #'.$row['product_id'].'</a><br>';
      }
    $display .= '</td>';
    $display .= '<td valign="top"><font size="2"><a href="show_report.php?type=customer_invoice&delivery_id='.$_GET['delivery_id'].'&member_id='.$member_id.'">View Invoice</a></font></td>';
    $display .= '</tr>';
    $member_id_list .= '#'.$member_id;
  }

//include("func/show_delivery_date.php");
//include("func/convert_delivery_date.php");
//if ( $_GET['delivery_id'] == ActiveCycle::delivery_id() )
//  {
//    $delivery_date = ActiveCycle::delivery_date();
//  }

$content_list = '
<table width="100%">
  <tr>
    <td align="left">
      <h3>Saved Orders: '.date ('F j, Y', strtotime ($delivery_date)).' ('.$num_orders.' Orders)</h3>
      Current Combined SUBTOTAL: <b>$'.number_format($bigtotal,2).'</b> (Subtotal $'.number_format($bigsubtotal,2).')<br>
      (includes adjustments, doesn&#146;t include taxes and delivery charges)<br/>
      Extra weight-needed total:
      $'.number_format($random_min, 2).' - $'.number_format($random_max, 2).'(Approximate grand total: <strong>$'.number_format($bigtotal + ($random_min + $random_max) / 2, 2).'</strong>)
      <br/>
    '.($memneedinvoice ? '
      *Need to <a href="orders_selectmember.php">create an invoice</a> for this cycle for these members:'.$memneedinvoice.'<br><br>' : '').'
<table bgcolor="#DDDDDD" cellpadding="2" cellspacing="2" border="0">
  <tr bgcolor="#AEDE86">
    <th valign="bottom" bgcolor="#CC9900"><font face="arial" size="-2">Subtotal</th>
    <th>Mem. ID</th>
    <th>Member (Click to Edit Order)</th>
    <th>Order Completion<br>(weight needed for products...)</th>
    <th>Temp. Invoice</th>
  </tr>
  '.$display.'
</table>
</td></tr>
</table>';


$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Order List with Totals</span>';
$page_title = 'Delivery Cycle Functions: Order List with Totals';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

