<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');

// Logic for getting values for specific delcode_id values
$where_delcode_id_condition = '1';
$this_delcode = "All Sites";
if (strlen ($_GET['delcode_id']) > 0)
  {
    // Generate the sql condition for delcode_id-specific queries
    $where_delcode_id_condition = 'delcode_id = "'.mysql_real_escape_string ($_GET['delcode_id']).'"';
  }
$sql_delcode = '
  SELECT
    delcode_id,
    delcode
  FROM
    '.TABLE_DELCODE;
$result_delcode = @mysql_query($sql_delcode, $connection) or die("Couldn't execute query 1a.");
// Build the select markup for choosing various delcode_id options
$delcode_select = '
  <form action="'.$PHP_SELF.'" method="get" name="delcode_select">
  <input type="hidden" name="delivery_id" value="'.$_REQUEST['delivery_id'].'">
  <select name="delcode_id" onChange="Load_id()">
    <option value="">Report All Sites</option>';
while ( $row = mysql_fetch_array($result_delcode) )
  {
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    // Set this as the current delcode if this is the current one
    $select_this = '';
    if ($_GET['delcode_id'] == $delcode_id)
      {
        $this_delcode = $delcode;
        $select_this = ' selected';
      }
    $delcode_select .= '
    <option value="'.$delcode_id.'"'.$select_this.'>Report '.$delcode.' Only</option>';
  }
$delcode_select .= '
  </select>
  </form>';

$date_today = date("F j, Y");
$sql_sum = '
  SELECT
    delivery_id,
    SUM(subtotal) AS sub_sum
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.$where_delcode_id_condition.'
  GROUP BY
    delivery_id';
$result_sum = @mysql_query($sql_sum, $connection) or die("Couldn't execute query 1b.");
while ( $row = mysql_fetch_array($result_sum) )
  {
    $subtotal_all = $row['sub_sum'];
  }
$sql_sum2 = '
  SELECT
    delivery_id,
    SUM(coopfee) AS coop_sum,
    SUM(surcharge_for_paypal) AS total_paypal
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.$where_delcode_id_condition.'
  GROUP BY
    '.TABLE_BASKET_ALL.'.delivery_id';
$result_sum2 = @mysql_query($sql_sum2, $connection) or die("Couldn't execute query 2.");
while ( $row = mysql_fetch_array($result_sum2) )
  {
    $coopfee_all = $row['coop_sum'];
    $total_paypal = $row['total_paypal'];
  }
$sql_sum3 = '
  SELECT
    delivery_id,
    SUM(delivery_cost) AS delivery_sum,
    SUM(transcharge) AS transcharge_sum
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.$where_delcode_id_condition.'
  GROUP BY
    '.TABLE_BASKET_ALL.'.delivery_id';
$result_sum3 = @mysql_query($sql_sum3, $connection) or die("Couldn't execute query 3.");
while ( $row = mysql_fetch_array($result_sum3) )
  {
    $delivery_cost_all = $row['delivery_sum'];
    $transcharge_all = $row['transcharge_sum'];
  }
$sql_sum4 = '
  SELECT
    delivery_id,
    SUM(grand_total) AS grandcust_sum
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.$where_delcode_id_condition.'
  GROUP BY
    delivery_id';
$result_sum4 = @mysql_query($sql_sum4, $connection) or die("Couldn't execute query 4.");
while ( $row = mysql_fetch_array($result_sum4) )
  {
    $grand_total_all = $row['grandcust_sum'];
  }
$sql_sum7 = '
  SELECT
    delivery_id,
    SUM(grand_total_coop) AS grandcoop_sum
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.$where_delcode_id_condition.'
  GROUP BY
    delivery_id';
$result_sum7 = @mysql_query($sql_sum7, $connection) or die("Couldn't execute query 5.");
while ( $row = mysql_fetch_array($result_sum7) )
  {
    $grand_total_all_coop = $row['grandcoop_sum'];
  }
$sql_sum6 = '
  SELECT
    sum( quantity ) AS sumq
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN
    '.TABLE_BASKET_ALL.'
  ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.TABLE_BASKET.'.out_of_stock != "1"
    AND '.$where_delcode_id_condition.'
  GROUP BY
    '.TABLE_BASKET_ALL.'.delivery_id';
$result_sum6 = @mysql_query($sql_sum6, $connection) or die("Couldn't execute query 6.");
while ( $row = mysql_fetch_array($result_sum6) )
  {
    $quantity_all = $row['sumq'];
  }
$surcharge = '';
$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.*,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.first_name_2,
    '.TABLE_MEMBER.'.last_name_2,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_ORDER_CYCLES.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_PAY.'.*,
    DATE_FORMAT(order_date, "%b %d, %Y") AS last_modified,
    DATE_FORMAT(delivery_date, "%M %d, %Y") AS delivery_date
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.TABLE_BASKET_ALL.'.delivery_id
  LEFT JOIN
    '.TABLE_PAY.' ON '.TABLE_PAY.'.payment_method = '.TABLE_BASKET_ALL.'.payment_method
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
    AND '.$where_delcode_id_condition.'
  GROUP BY '.TABLE_BASKET_ALL.'.basket_id
  ORDER BY
    last_name ASC,
    business_name ASC';
$result = @mysql_query($sql, $connection) or die(mysql_error());
$numtotal = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $business_name = $row['business_name'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $delivery_date = $row['delivery_date'];
    $cust_salestax = "";
    $sql_sums = '
      SELECT
        collected_statetax,
        collected_citytax,
        collected_countytax
      FROM
        '.TABLE_CUSTOMER_SALESTAX.'
      WHERE
        '.TABLE_CUSTOMER_SALESTAX.'.basket_id = "'.mysql_real_escape_string ($row["basket_id"]).'"';
    $result_sums = @mysql_query($sql_sums, $connection) or die("Couldn't execute query sales tax.");
    while ( $row2 = mysql_fetch_array($result_sums) )
      {
        $cust_salestax = $row2['collected_statetax']+$row2['collected_citytax']+$row2['collected_countytax'];
      }
    $draft_emailed = '';
    if ( $row['draft_emailed'] )
      {
        $draft_emailed = 'Y';
      }
    $final_invoice = '';
    if ( $row['finalized'] )
      {
        $final_invoice = 'Y';
      }
    // Non-taxed Adjustments total to use in cash discount calculation from transactions table
    $sqladjnont_new = mysql_query('
      SELECT
        SUM(transaction_amount) AS adjustment_sum
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_type = "36"
        AND transaction_taxed = "0"
        AND transaction_basket_id = "'.mysql_real_escape_string ($row["basket_id"]).'"
      ORDER BY
        transaction_id DESC
      LIMIT 1');
    $adjnont_new = mysql_fetch_array ($sqladjnont_new);
    $subtotal_1 = $row['subtotal'] + $row['coopfee'] + $row['transcharge'] + $row['delivery_cost'] + $cust_salestax + $row['sh'] + $adjnont_new['adjustment_sum'];
    if ( $row['payment_method'] == 'P' )
      {
        $p_chk = 'checked';
        $c_chk = '';
        //$minus_paypal = "<br>-$surcharge for not paying by check/cash";
        $minus_paypal = '';
      }
    elseif ( $row['payment_method'] == 'C' )
      {
        $c_chk = 'checked';
        $p_chk = '';
        $surcharge = '';
        $minus_paypal = '';
      }
    else
      {
        $c_chk = '';
        $p_chk = '';
        $surcharge = '';
        $minus_paypal = '';
      }
    if ( $row['subtotal'] <= 0 )
      {
        $cash_discount = .31;
      }
    else
      {
        //$total_sent_to_paypal = (($subtotal_1 + .30)/ .971);
        $cash_discount = number_format ((double)(((($subtotal_1 + .30) / .971) * .029) + .30), 4);
      }
    // Set the cash_discount to zero if it is negative or if we aren't doing paypal surcharges
    if ( $subtotal_1 <= 0 || $_GET['delivery_id'] >= DELIVERY_NO_PAYPAL )
      {
        $cash_discount = 0;
      }
    $coop_charges = $row['sh'] + $cash_discount;
    // no discount given if not pay by check
    if ( $row['payment_method'] == 'P' )
      {
        $cash_discount = 0;
      }
    $quantity_mem = '';
    $sql_sum8 = '
      SELECT
        sum( quantity ) AS sumq
      FROM
        '.TABLE_BASKET.'
      LEFT JOIN
        '.TABLE_BASKET_ALL.'
      ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
        AND '.TABLE_BASKET.'.out_of_stock != "1"
        AND '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($row["member_id"]).'"
      GROUP BY
        '.TABLE_BASKET_ALL.'.delivery_id';
    $result_sum8 = @mysql_query($sql_sum8, $connection) or die("Couldn't execute query 8.");
    while ( $row8 = mysql_fetch_array($result_sum8) )
      {
        $quantity_mem = $row8['sum_mem'];
      }
    // to override the amount paid from the customer_basket_overall
    $sql_t = mysql_query('
      SELECT
        SUM(transaction_amount) as amount_paid
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_type = "23"
        AND transaction_member_id = "'.mysql_real_escape_string ($row["member_id"]).'"
        AND transaction_delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"');
    $result_t = mysql_fetch_array($sql_t);
    if ( $result_t['amount_paid'] > 0 )
      {
        $row['amount_paid'] = $result_t['amount_paid'];
      }
    $amount_paid = number_format((double)$row['amount_paid'], 2);
    $discrepancy = 0;
    $discrepancy = $row['grand_total'] - $surcharge - $row['amount_paid'];
    $prev_paypal_total = 0;
    $prev_grand_total_total = 0;
    $amount_paid_total = 0;
    //$delivery_id_previous = $delivery_id-1;
    $sqldp = '
      SELECT
        member_id,
        delivery_id,
        SUM(surcharge_for_paypal) AS prev_paypal,
        SUM(grand_total) AS prev_grand_total,
        SUM(amount_paid) AS amount_paid
      FROM
        '.TABLE_BASKET_ALL.'
      WHERE
        member_id = "'.$row["member_id"].'"
        AND delivery_id < "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
      GROUP BY
        member_id';
    $result_bal = @mysql_query($sqldp, $connection) or die(mysql_error());
    while ( $rowdp = mysql_fetch_array($result_bal) )
      {
        $prev_paypal = $rowdp['prev_paypal'];
        $prev_grand_total = $rowdp['prev_grand_total'];
        $amount_paid = $rowdp['amount_paid'];
        $prev_paypal_total = $prev_paypal_total + $prev_paypal + 0;
        $prev_grand_total_total = $prev_grand_total_total + $prev_grand_total + 0;
        $amount_paid_total = $amount_paid_total + $amount_paid + 0;
        $previous_balance = $prev_grand_total_total - $prev_paypal_total - $amount_paid_total;
      }
    if ( $discrepancy_previous )
      {
        $discrep_color = 'bgcolor="#FFCC66"';
      }
    else
      {
        $discrep_color = '';
      }
    include("../func/show_name_last.php");
    if ( $row['finalized'] != 1 )
      {
        $unfinalized = '<font size="-2" color="#880000"><br>Unfinalized</font>';
      }
    else
      {
        $unfinalized = '';
      }
    $coop_donations = '';
    $non_coop_donations = '';
    // Donations - for OKFood Co-op, it's subcategory 56
    $sql_donations = mysql_query('
      SELECT
        '.TABLE_BASKET.'.producer_id,
        SUM((item_price*quantity) + (extra_charge*quantity)) AS product_sum
      FROM
        '.TABLE_BASKET.'
      LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      WHERE
        '.TABLE_BASKET.'.subcategory_id = "56"
        AND '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
        AND '.TABLE_BASKET_ALL.'.basket_id = "'.mysql_real_escape_string ($row['basket_id']).'"
      GROUP BY
        '.TABLE_BASKET.'.product_id');
    while ( $donations = mysql_fetch_array($sql_donations) )
      {
        $non_coop_donations = $non_coop_donations + $donations['product_sum'];
      }
    // Adjustments total
    $sqladj_new = mysql_query('
      SELECT
        SUM(transaction_amount) AS adjustment_sum
      FROM
        '.TABLE_TRANSACTIONS.',
        '.TABLE_TRANS_TYPES.'
      WHERE
        transaction_type != "36"
        AND transaction_type = ttype_id
        AND ttype_parent = "20"
        AND transaction_basket_id = "'.mysql_real_escape_string ($row["basket_id"]).'"');
    $adj_new = mysql_fetch_array($sqladj_new);
    // Work Credit adjustments
    $sqladjw_new = mysql_query('
      SELECT
        SUM(transaction_amount) AS adjustment_sum
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        (
          transaction_type = "3"
          OR transaction_type = "11"
          OR transaction_type = "12"
          OR transaction_type = "15"
        )
        AND transaction_basket_id = "'.mysql_real_escape_string ($row["basket_id"]).'"');
    $adjw_new = mysql_fetch_array($sqladjw_new);
    // Missing ticket item adjustments
        $sqladjm_new = mysql_query('
      SELECT
        SUM(transaction_amount) AS adjustment_sum
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_type = "8"
        AND transaction_basket_id = "'.mysql_real_escape_string ($row['basket_id']).'"');
    $adjm_new = mysql_fetch_array($sqladjm_new);
    // Producer Discount (adjt_id=14, When a producer wants to refund something to a customer or wants to give a special discount.)
        $sqladjp_new = mysql_query('
      SELECT
        SUM(transaction_amount) AS adjustment_sum
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_type = "14"
        AND transaction_basket_id = "'.mysql_real_escape_string ($row["basket_id"]).'"');
    $adjp_new = mysql_fetch_array($sqladjp_new);
    // Producer Payment Credit (adjt_id=4, where a producer's member account is credited revenue from the co-op
        $sqladjpc_new = mysql_query('
      SELECT
        SUM(transaction_amount) AS adjustment_sum
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_type = "4"
        AND transaction_basket_id = "'.mysql_real_escape_string ($row["basket_id"]).'"');
    $adjpc_new = mysql_fetch_array($sqladjpc_new);
    // Remaining adjustments that aren't work credits, missing, producer discounts, or producer credits
    $other_adjustments = number_format((double)$adj['adjustment_sum'], 2, '.', '')
      + number_format((double)$adj_new['adjustment_sum'], 2, '.', '')
      + number_format((double)$adjw_new['adjustment_sum'], 2, '.', '')
      + number_format((double)$adjm_new['adjustment_sum'], 2, '.', '')
      + number_format((double)$adjp_new['adjustment_sum'], 2, '.', '')
      + number_format((double)$adjpc_new['adjustment_sum'], 2, '.', '');
    //$nonadj_total = $row['subtotal']-number_format($adj['adjustment_sum'], 2);
    $grand_total = number_format((double)$row['subtotal'], 2, '.', '')
      + number_format((double)$cust_salestax, 2, '.', '')
      + number_format((double)$coop_charges, 2, '.', '')
      + number_format((double)$row['delivery_cost'], 2, '.', '')
      + number_format((double)$adjm_new['adjustment_sum'], 2, '.', '')
      + number_format((double)$adjp_new['adjustment_sum'], 2, '.', '');
    //- number_format($coop_donations, 2) - number_format($non_coop_donations, 2);
    //$invoice_total = $grand_total+number_format($adj['adjustment_sum'], 2, '.', '')-$cash_discount;
    //$invoice_total = $grand_total+number_format($adj['adjustment_sum'], 2, '.', '')-$cash_discount-number_format($adj_new['adjustment_sum'], 2, '.', '')+$adjnont_new['adjustment_sum'];
    $invoice_total = $row['grand_total'];
    // overall totals
    $total_salestax = $total_salestax + $cust_salestax + 0;
    $coop_charges_total = $coop_charges_total + number_format((double)$coop_charges, 2, '.', '') + 0;
    $home_delivery_total = $home_delivery_total+number_format((double)$row['delivery_cost'], 2, '.', '') + 0;
    $missing_total = $missing_total + number_format((double)$adjm_new['adjustment_sum'], 2, '.', '');
    $nonadj_total_total = $nonadj_total_total + number_format((double)$nonadj_total, 2, '.', '') + 0;
    $workcredit_total = $workcredit_total + number_format((double)$adjw_new['adjustment_sum'], 2, '.', '');
    $producer_discount_total = $producer_discount_total + number_format((double)$adjp_new['adjustment_sum'], 2, '.', '');
    $producer_credit_total = $producer_credit_total + number_format((double)$adjpc_new['adjustment_sum'], 2, '.', '');
    $other_adjustments_total = $other_adjustments_total + $other_adjustments + 0;
    $adjustment_total =$adjustment_total + number_format((double)$adj['adjustment_sum'], 2, '.', '') + number_format((double)$adj_new['adjustment_sum'], 2, '.', '');
    $coop_donations_total = $coop_donations_total + $coop_donations + 0;
    $non_coop_donations_total = $non_coop_donations_total + $non_coop_donations + 0;
    $total_quantity = $total_quantity + $quantity_mem + 0;
    $invoice_total_total = 0 + $invoice_total_total + $invoice_total;
    $cash_discount_total = $cash_discount_total + number_format((double)$cash_discount, 2, '.', '') + 0;
    $total_for_accountants = $total_for_accountants + $grand_total + 0;
    if ( $_REQUEST['spreadsheet'] )
      {
        $show_name = "";
        if ( $business_name )
          {
            $show_name .= $business_name.", ";
          }
        if ( $last_name )
          {
            $show_name .= $last_name.", ".$first_name;
          }
      }
    $display_month .= '
      <tr>
        <td align="right" valign="top"><font face="arial" size="-1"><b># '.$row['member_id'].'</b></td>
        <td align="left" valign="top"><font face="arial" size="-1"><b><a href="customer_invoice.php?member_id='.$row['member_id'].'&basket_id='.$row['basket_id'].'&delivery_id='.$_REQUEST['delivery_id'].'">'.$show_name.'</a></b>&nbsp;&nbsp;</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$row['subtotal'], 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$cust_salestax, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$coop_charges, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$row['delivery_cost'], 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$adjm_new['adjustment_sum'], 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$adjp_new['adjustment_sum'], 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$coop_donations, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$non_coop_donations, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$grand_total, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$adjpc_new['adjustment_sum'], 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$other_adjustments, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$adjw_new['adjustment_sum'], 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">'.$surcharge.' '.$row['payment_method'].'</td>
        <td align="right" valign="top"><font face="arial" size="-1">'.number_format ((double)$quantity_mem, 0).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$cash_discount, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1"><b>$'.number_format((double)$row['grand_total'], 2).'</b> '.$minus_paypal.'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$previous_balance, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$invoice_total, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.$row['amount_paid'].' '.$unfinalized.'</td>
        <td align="right" valign="top"><font face="arial" size="-1">$'.number_format((double)$discrepancy, 2).'</td>
        <td align="right" valign="top"><font face="arial" size="-2"><i>'.$row['last_modified'].'</i></td>
      </tr>';
  }
$display_totals .= '
  <tr>
    <td colspan="2" align="center" bgcolor="#AEDE86"><br><b>T O T A L S</b><br><br></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$subtotal_all, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$total_salestax, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$coop_charges_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$home_delivery_total,2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$missing_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$producer_discount_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$coop_donations_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$non_coop_donations_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$total_for_accountants, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$producer_credit_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$other_adjustments_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$workcredit_total, 2).'</b></td>
    <td>&nbsp;</td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>'.$total_quantity.'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$cash_discount_total, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$grand_total_all, 2).'</b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b><!--previous--></b></td>
    <td align="right" valign="top"><font face="arial" size="-1"><br><b>$'.number_format((double)$invoice_total_total,2).'</b></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>';
$fontface="arial";


if (!$_REQUEST['spreadsheet'])
  {
    $content_report .= '
<font face="'.$fontface.'">
<h2>Delivery Date '.$delivery_date.' for '.$this_delcode.'</h2>
Choose another site to report: '.$delcode_select.'
<b>Total Products Sold: '.$quantity_all.' Products &nbsp;&nbsp;&nbsp;
Total Orders: '.$numtotal.'</b> &nbsp;&nbsp;&nbsp;<font size="-1">(Print Landscape for best results.)</font>
<br>Click here to <a href="ctotals_onebutton.php?delivery_id='.$_REQUEST['delivery_id'].'">Update Customer Payments</a> |
Click here for <a href="totals_saved.php">Previous Order Cycles</a>
<br><font color="#CC9900">'.$message.'</font>
<hr>
<h3>Grand Total: $'.number_format((double)$total_for_accountants,2).'</h3>
(Products Subtotal(#1) + Sales tax(#2) + '.ucfirst (ORGANIZATION_TYPE).' Charges(#3) + Home Delivery (#4) + (-)Missing Ticket Items(#5)
+ (-)Producer discounts(#6)
<!-- + (-)Donations (#7,#8) --><br>
<small>NOTE: This page may scroll horizontally.  Look for the horizontal scroll-bar is at the bottom &darr;</small>';
  }
$content_report .= '
<table class="gridtable">';

if (!$_REQUEST['spreadsheet'])
  {
    $content_report .= $display_totals;
    $heading_color = 'bgcolor="#DDDDDD"';
    $heading_color_orangeaccou = 'bgcolor="#CC9900"';
    $heading_color_blue = 'bgcolor="#ADB6C6"';
  }
$content_report .= '
  <tr '.$heading_color.'>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">Mem. ID</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">Member Name</th>
    <th valign="bottom" '.$heading_color_blue.'><font face="'.$fontface.'" size="-1">1. Product Subtotal</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">2. Sales Tax</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">3. '.ucfirst (ORGANIZATION_TYPE).' Charges</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">4. Home Delivery</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">5. Missing Ticket Items</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">6. Producer Discounts</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">7. Donations to the '.ucfirst (ORGANIZATION_TYPE).'</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">8. Donations to Non-profit Members</th>
    <th valign="bottom" '.$heading_color_blue.'><font face="'.$fontface.'" size="-1">9. Grand Total</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">10. Producer Credits</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">11. Other Adjustments</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">12. Work Credits, Mileage, Home Delivery Credit</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">13. Check/<br>Paypal</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">14. # Prod.</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">15. Cash Discount</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">16. Total II</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">17. Previous Credit or Amount Due</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">18. Invoice Total</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">19. Amount Paid</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">20. Discrepancy</th>
    <th valign="bottom"><font face="'.$fontface.'" size="-1">Last Modified</th>
  </tr>
  '.$display_month;

if (!$_REQUEST['spreadsheet'])
  {
    $content_report .= $display_totals;
  }

$content_report .= '
</table>
<br/>
<strong>Key:</strong><br/>
1. Product Subtotal <br/>Subtotal, first line in the totals section of the customer invoice.<br/><br/>
2. Sales Tax<br/>State, county, and city tax assessed on the taxable subtotal<br/><br/>
3. '.ucfirst (ORGANIZATION_TYPE).' Charges <br/>Shipping & Handling<br/><br/>
4. Home Delivery <br/>Delivery charge for work/home delivery of order<br/><br/>
5. Missing Ticket Items<br/>Item missing at producer check-in<br/><br/>
6. Producer Discounts <br/>When a producer wants to refund something to a customer or wants to give a special discount.<br/><br/>
9. Grand Total <br/>SUBTOTAL 2 on the customer invoice<br/><br/>
10. Producer Credits <br/>Where a producer&#146;s member account is credited revenue from the '.ORGANIZATION_TYPE.'<br/><br/>
11. Other Adjustments <br/>Any remaining adjustments not covered elsewhere<br/><br/>
12. Work Credits, Mileage, Home Delivery Credit <br/>Adjustments: Work credit + delivery day mileage + home delivery credit + Work credit applied to membership fee<br/><br/>
16. Total II<br/>Grand total - non-taxed adjustments - cash discount<br/><br/>
18. Invoice Total<br/>Total II +/- previous balance<br/><br/>
19. Amount Paid<br/>If this column has an &quot;unfinalized&quot; note and you find the totals incorrect on this chart, finalize and unfinalize so that the current numbers get saved and show in this chart.<br/>';

$page_specific_css .= '
<style type="text/css">
small {
  font-size:0.9em;
  color:#006;
  font-weight:bold;
  }
</style>';

$page_specific_javascript .= '
<script type="text/javascript">
function Load_id()
  {
    var delcode_id = document.delcode_select.delcode_id.options[document.delcode_select.delcode_id.selectedIndex].value;
    var delivery_id = document.delcode_select.delivery_id.value;
    location = "?delivery_id=" + delivery_id + "&delcode_id=" + delcode_id;
  }
</script>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Customer Totals</span>';
$page_title = 'Accounting: Customer Totals';
$page_tab = 'cashier_panel';


if ($_REQUEST['spreadsheet'])
  {
    echo $content_report;
  }
else
  {
    include("template_header.php");
    echo '
      <!-- CONTENT BEGINS HERE -->
      '.$content_report.'
      <!-- CONTENT ENDS HERE -->';
    include("template_footer.php");
  }
