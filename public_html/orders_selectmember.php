<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,cashier,site_admin');

if (! $_REQUEST['target_delivery_id'] ) $_REQUEST['target_delivery_id'] = ActiveCycle::delivery_id();

if ( $_REQUEST['qy'] == 'jk' )
  {
    if ( !$_REQUEST['member_id_list'] && !$_REQUEST['member_id_typed'] )
      {
        $message2 = '<H3>Please enter a Member.</h3>';
      }
    elseif ( !$_REQUEST['member_id_list'] && $_REQUEST['member_id_typed'] )
      {
        $sql5 = '
          SELECT
            member_id
          FROM
            '.TABLE_MEMBER.'
          WHERE
            member_id = "'.mysql_real_escape_string ($_REQUEST['member_id_typed']).'"';
        $result5 = @mysql_query($sql5, $connection) or die(mysql_error());
        $num5 = mysql_numrows($result5);
        if ( $num5 <= 0 )
          {
            $message2 = '<H3>This Member ID does not exist. Please enter another number or
                    select from the list</h3>';
          }
        else
          {
        $member_id = $_REQUEST['member_id_typed'];
        $selectmember = 'pass';
          }
      }
    else
      {
        $member_id = $_REQUEST['member_id_list'];
        $selectmember = "pass";
      }
    if ( !$_REQUEST['delcode_id'] )
      {
        $message2 = '<H3>Please enter a Pickup or Delivery Location.</h3>';
      }
    elseif ( !$_REQUEST['deltype'] )
      {
        $message2 = '<h3>Please choose Home, Work, or Pick up.</h3>';
      }
    elseif ( !$_REQUEST['payment_method'] )
      {
        $message2 = '<H3>Please choose a Payment Method.</h3>';
      }
    if (( $selectmember == 'pass' ) && $_REQUEST['delcode_id'] && $_REQUEST['deltype'] && $_REQUEST['payment_method'])
      {
        $sql4 = '
          SELECT
            delivery_id,
            member_id,
            basket_id
          FROM
            '.TABLE_BASKET_ALL.'
          WHERE
            delivery_id = "'.mysql_real_escape_string ($_REQUEST['target_delivery_id']).'"
            AND member_id = "'.mysql_real_escape_string ($member_id).'"';
        $result4 = @mysql_query($sql4,$connection) or die(mysql_error());
        $num4 = mysql_numrows($result4);
        while ( $row = mysql_fetch_array($result4) )
          {
            $basket_id = $row['basket_id'];
          }
        if ( $num4 == 1 )
          {
            $message2 = '<H3>This order has already been submitted. Click here to <a href="orders.php?member_id='.$member_id.'&basket_id='.$basket_id.'">edit the order</a></h3>';
          }
        else
          {
            $show_page = 'no';

            $sql2 = '
              SELECT
                delcharge,
                transcharge
              FROM
                '.TABLE_DELCODE.'
              WHERE
                delcode_id = "'.mysql_real_escape_string ($_REQUEST['delcode_id']).'"';
            $result2 = @mysql_query($sql2, $connection) or die("Couldn't execute query 2.");
            while ( $row = mysql_fetch_array($result2) )
              {
                $delcharge = $row['delcharge'];
                $transcharge = $row['transcharge'];
              }
            $sqlc = '
              SELECT coopfee
              FROM
                '.TABLE_ORDER_CYCLES.'
              WHERE
                delivery_id = "'.mysql_real_escape_string ($_REQUEST['target_delivery_id']).'"';
            $resultc = @mysql_query($sqlc, $connection) or die("Couldn't execute query coop fee.");
            while ( $row = mysql_fetch_array($resultc) )
              {
                $coopfee = $row['coopfee'];
              }
            $sql6 = '
              SELECT
                order_cost,
                order_cost_type
              FROM
                '.TABLE_MEMBER.'
              RIGHT JOIN '.TABLE_MEMBERSHIP_TYPES.' ON '.TABLE_MEMBER.'.membership_type_id = '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id
              WHERE
                 member_id = "'.$member_id.'"';
            $result6 = @mysql_query($sql6, $connection) or die("Couldn't execute query 2.");
            while ( $row = mysql_fetch_array($result6) )
              {
                $order_cost = $row['order_cost'];
                $order_cost_type = $row['order_cost_type'];
              }

            $sqlo = '
            INSERT INTO
              '.TABLE_BASKET_ALL.'
                (
                  basket_id,
                  member_id,
                  delivery_id,
                  coopfee,
                  delcode_id,
                  deltype,
                  delivery_cost,
                  order_cost,
                  order_cost_type,
                  transcharge,
                  payment_method,
                  order_date
                )
              VALUES
                (
                  "'.mysql_real_escape_string ($basket_id).'",
                  "'.mysql_real_escape_string ($member_id).'",
                  "'.mysql_real_escape_string ($_REQUEST['target_delivery_id']).'",
                  "'.mysql_real_escape_string ($coopfee).'",
                  "'.mysql_real_escape_string ($_REQUEST['delcode_id']).'",
                  "'.mysql_real_escape_string ($_REQUEST['deltype']).'",
                  "'.mysql_real_escape_string ($delcharge).'",
                  "'.mysql_real_escape_string ($order_cost).'",
                  "'.mysql_real_escape_string ($order_cost_type).'",
                  "'.mysql_real_escape_string ($transcharge).'",
                  "'.mysql_real_escape_string ($_REQUEST['payment_method']).'",
                  now()
                )';
            $resulto = @mysql_query($sqlo,$connection) or die(mysql_error());
            $basket_id = mysql_insert_id();
            $message3 = '<br><br><h3>This customer&#146;s basket is now established.</h3>
              <form action="orders.php" method="post">
                <input type="hidden" name="basket_id" value="'.$basket_id.'">
                <input type="hidden" name="member_id" value="'.$member_id.'">
                <input type="hidden" name="delivery_id" value="'.$_REQUEST['target_delivery_id'].'">
                <input name="where" type="submit" value="Click to Begin Filling Order">
              </form><br><br>';
          }
      }
  }
$sqlm = '
  SELECT
    member_id,
    preferred_name
  FROM
    '.TABLE_MEMBER.'
  ORDER BY
    last_name,
    preferred_name ASC';
$resultm = @mysql_query($sqlm, $connection) or die("Couldn't execute query -m.");
while ( $row = mysql_fetch_array($resultm) )
  {
    $member_id_list = $row['member_id'];
    $preferred_name = $row['preferred_name'];
    $mem_first = '
                    <option value="">Choose a member name</option>';
    $display_mem .= '
                    <option value="'.$member_id_list.'">'.$preferred_name.' ('.$member_id_list.')</option>';
  }
$display_deltype = '
  <input type="radio" name="deltype" value="H">Home Delivery
  <input type="radio" name="deltype" value="W">Work Delivery
  <input type="radio" name="deltype" value="P">Pick-up';
$sqldc = '
  SELECT *
  FROM
    '.TABLE_DELCODE.'
  WHERE
    inactive != "1"
  ORDER BY
    delcode ASC';
$rs = @mysql_query($sqldc, $connection) or die("Couldn't execute query.");
while ( $row = mysql_fetch_array($rs) )
  {
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    $delcharge = $row['delcharge'];
    $delcode_first = '
                    <option value="">Choose a pickup or delivery option</option>';
    $display_delcode .= '
                    <option value="'.$delcode_id.'">'.$delcode.'</option>';
  }
$sqlpay = '
  SELECT
    *
  FROM
    '.TABLE_PAY.'';
$rs = @mysql_query($sqlpay, $connection) or die("Couldn't execute query.");
while ( $row = mysql_fetch_array($rs) )
  {
    $payment_method = $row['payment_method'];
    $payment_desc = $row['payment_desc'];
    $pay_first = '
                    <option value="">Choose a payment method</option>';
    $display_pay .= '
                    <option value="'.$payment_method.'">'.$payment_desc.'</option>';
  }

$query = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    1
  ORDER BY
    delivery_id DESC
  LIMIT 10';

$result = @mysql_query($query, $connection) or die("Couldn't execute query coop fee.");
while ( $row = mysql_fetch_array($result) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];
    $date_checked = '';
    if ($delivery_id == ActiveCycle::delivery_id()) $date_checked = ' selected';
    $delivery_select .= '
      <option value="'.$delivery_id.'"'.$date_checked.'>'.$delivery_date.'</option>';
  }

$display .= '
  <form action="orders_selectmember.php" method="post">
    <div align="center">
      <table cellpadding="4" cellspacing="0" border="0" bgcolor="#ADB6C6">
        <tr>
          <td bgcolor="#FFFFFF" colspan="9" align="left">
            <h3>Entering Orders: Select a Member to Begin an Order</h3><font color="#990000">'.$message2.'</font></td>
        </tr>
        <tr>
          <td><br></td>
          <td colspan="8" align="left">
            <table>
              <tr>
                <td><b>Member associated with this Order:</b></td>
                <td>
                  <select name="member_id_list">
                    '.$mem_first.'
                    '.$display_mem.'
                  </select> <b>OR</b> Enter ID #
                  <input type="text" name="member_id_typed" value="'.$_REQUEST['member_id_typed'].'" size="3" maxlength="8">
                </td>
              </tr>
              <tr>
                <td><b>Delivery Type:</b> </td>
                <td>'.$display_deltype.'</td>
              </tr>
              <tr>
                <td><b>Pickup/ Delivery Locations:</b></td>
                <td>
                  <select name="delcode_id">
                    '.$delcode_first.'
                    '.$display_delcode.'
                  </select>
                </td>
              </tr>
              <tr>
                <td><b>Delivery Date:</b> </td>
                <td><select name="target_delivery_id">'.$delivery_select.'</select></td>
              </tr>
              <tr>
                <td><b>Payment Method:</b></td>
                <td>
                  <select name="payment_method">
                    '.$pay_first.'
                    '.$display_pay.'
                  </select>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="9" align="right">
            <input type="hidden" name="qy" value="jk">';
if ( $basket_id )
  {
    $display .= '
            <input type="hidden" name="basket_id" value="'.$basket_id.'">';
  }
if ( $member_id )
  {
    $display .= '
            <input type="hidden" name="member_id" value="'.$member_id.'">';
  }
$display .= '
            <input type="hidden" name="delivery_id" value="'.$_REQUEST['target_delivery_id'].'">
            <input name="where" type="submit" value="Click to Begin Filling Order">
          </td>
        </tr>
      </table>
  </form>
  </div></div>';

$content_select = '
  '.($show_page == 'no' ? '' : $display).'
  <div align="center">
  '.$message3;

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Open an Order</span>';
$page_title = 'Delivery Cycle Functions: Open an Order';
$page_tab = 'member_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_select.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

