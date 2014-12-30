<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

$delivery_id = $_REQUEST['delivery_id'];
$basket_id = $_REQUEST['basket_id'];
$member_id = $_REQUEST['member_id'];

if ( $_REQUEST['fin'] == 'unfinalize' && CurrentMember::auth_type('cashier'))
  {
    $sqlf = '
      UPDATE
        '.TABLE_BASKET_ALL.'
      SET
        finalized = "0"
      WHERE
        basket_id = "'.mysql_real_escape_string ($basket_id).'"
        AND member_id = "'.mysql_real_escape_string ($member_id).'"';
    $resultf = @mysql_query($sqlf, $connection) or die(mysql_error());
  }
$hub = "";
$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.basket_id AS big_basket_id,
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET_ALL.'.member_id,
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_BASKET_ALL.'.delivery_id AS basket_delivery_id,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_BASKET_ALL.'.finalized,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.business_name
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
  LEFT JOIN
    '.TABLE_DELCODE.' ON '.TABLE_DELCODE.'.delcode_id = '.TABLE_BASKET_ALL.'.delcode_id
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.TABLE_BASKET_ALL.'.member_id
  ORDER BY
    last_name ASC,
    '.TABLE_BASKET_ALL.'.basket_id DESC';
$rs = @mysql_query($sql, $connection) or die(mysql_error());
$num_orders = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    //$hub = $row['hub'];
    $basket_id = $row['big_basket_id'];
    $basket_delivery_id = $row['basket_delivery_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    //$rte_confirmed = $row['rte_confirmed'];
    $finalized = $row['finalized'];
    include("../func/show_name_last.php");
    $subtotal = '';
    $display .= '<tr bgcolor="#FFFFFF">';
    $display .= '<td valign="top" align="right" id="'.$basket_id.'"># '.$member_id.'</td>';
    if ( $finalized != 1 )
      {
        $display .= '<td valign="top"><a href="orders.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">'.$show_name.'</a></td>';
      }
    else
      {
        $display .= '<td valign="top">'.$show_name.'</td>';
      }
    if ( $basket_delivery_id != $delivery_id )
      {
        $display .= '<td>*Need to <a href="orders_selectmember.php">create an invoice</a> for this cycle*</td>';
      }
    else
      {
        if ( $finalized != 1 )
          {
            $display .= '<td valign="top"><font size="2"><a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">View Temp. Inv.</a></font></td>';
          }
        else
          {
            $display .= '<td></td>';
          }
      }
    if ( $basket_delivery_id != $delivery_id )
      {
        $display .= '<td valign="top" colspan="2">*Has a pre-ordered item in <a href="customer_invoice.php?delivery_id='.$basket_delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">Basket '.$basket_id.'</a> for delivery this month*</td>';
      }
    else
      {
        if ( $finalized != 1 )
          {
            $display .= '<td valign="top" bgcolor="#AEDE86">Not saved as final version</td>';
            $display .= '<td valign="top"></td>';
          }
        else
          {
            $display .='<td valign="top"><a href="invoice.php?basket_id='.$basket_id.'&member_id='.$member_id.'">Final Invoice</a> (Mem# '.$member_id.') </td>';
            $display .= '<td valign="top"><font size=-2><a href="'.$PHP_SELF.'?fin=unfinalize&delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'#'.$basket_id.'">Unfinalize to Edit</a></font></td>';
          }
      }
    $display .= '</tr>';
    $member_id_list .= '#'.$member_id;
  }
$sqlfd = '
  SELECT
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET_ALL.'.member_id,
     '.TABLE_BASKET.'.future_delivery_id
  FROM
    '.TABLE_BASKET_ALL.',
    '.TABLE_DELCODE.',
    '.TABLE_BASKET.'
  WHERE
    '.TABLE_BASKET.'.future_delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
    AND '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
    AND '.TABLE_BASKET_ALL.'.delivery_id != "'.mysql_real_escape_string ($delivery_id).'"
    AND '.TABLE_BASKET_ALL.'.member_id != "'.mysql_real_escape_string ($member_id).'"
  GROUP BY
    '.TABLE_BASKET_ALL.'.member_id
  ORDER BY
    '.TABLE_BASKET_ALL.'.basket_id DESC';
$rsf = @mysql_query($sqlfd, $connection) or die(mysql_error());
while ( $row = mysql_fetch_array($rsf) )
  {
    $basket_id2 = $row['basket_id'];
    $member_id2 = $row['member_id'];
    $future_delivery_id2 = $row['future_delivery_id'];
    $member_id2 = '#'.$member_id2;
    $pos = strpos($member_id_list,$member_id2);
    if ( $pos === false )
      {
        $memneedinvoice .= ' <font color=#FF0000><b> '.$member_id2.'</b></font> &nbsp;';
      }
    else
      {
        $memneedinvoice .= '';
      }
  }

include("../func/show_delivery_date.php");
include("../func/convert_delivery_date.php");

if ( $delivery_id==$current_delivery_id )
  {
    $delivery_date = $current_delivery_date;
  }

$content_list = '
<table width="100%">
  <tr>
    <td align="left">
      <h3>Saved Orders: '.$delivery_date.' ('.$num_orders.' Orders)</h3><!--
      Current Combined SUBTOTAL: <b>$'.number_format($bigtotal,2).'</b> (Subtotal $'.number_format($bigsubtotal,2).' + Adjustments $'.number_format($bigadj,2).')<br>
      (includes adjustments, doesn&#146;t include taxes and delivery charges)<br/>-->
      Click to finalize invoices for members id #:
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1&mf=100">1-100</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=101&mf=200">101-200</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=201&mf=300">201-300</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=301&mf=400">301-400</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=401&mf=500">401-500</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=501&mf=600">501-600</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=601&mf=700">601-700</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=701&mf=800">701-800</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=801&mf=900">801-900</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=901&mf=1000">901-1000</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1001&mf=1100">1101-1200</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1101&mf=1200">1101-1200</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1201&mf=1300">1201-1300</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1301&mf=1400">1301-1400</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1401&mf=1500">1401-1500</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1501&mf=1600">1501-1600</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1601&mf=1700">1601-1700</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1701&mf=1800">1701-1800</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1801&mf=1900">1801-1900</a> |
      <a href="finalize.php?delivery_id='.$delivery_id.'&ms=1901&mf=2000">1901-2000</a>
      <br><br>
    '.($memneedinvoice ? '
      *Need to <a href="orders_selectmember.php">create an invoice</a> for this cycle for these members:'.$memneedinvoice.'<br><br>' : '' ).'
<table width="100%" bgcolor="#dddddd" cellpadding="2" cellspacing="2" border="0">
  <tr bgcolor="#AEDE86">
    <!-- <th valign="bottom" bgcolor="#CC9900"><font face="arial" size="-2">Subtotal</th> -->
    <th>Mem. ID</th>
    <th>Member (Click to Edit Order)</th>
    <!-- <th>Order Completion</th> -->
    <th>Temp. Invoice</th>
    <!-- <th valign="bottom" bgcolor="#ADB6C6"><font face="arial" size="-2">Rte. Mgr<br>Confirmed</th> -->
    <th>Finalized After Delivery</th>
    <th>UnFinalize</th>
  </tr>
  '.$display.'
</table>
</td></tr>
</table>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Order List</span>';
$page_title = 'Delivery Cycle Functions: Order List';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

