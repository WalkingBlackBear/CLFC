<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');

// Will work so long as Membership Receivables are transaction_type = 24

$date_today = date("F j, Y");

// Set variables that used to be global
$payment_method = $_REQUEST['payment_method'];
$delivery_id = $_REQUEST['delivery_id'];
$amount_paid = $_REQUEST['amount_paid'];
$membership_amount_paid = $_REQUEST['membership_amount_paid'];
$member_id = $_REQUEST['member_id'];
$transaction_batchno = $_REQUEST['transaction_batchno'];
$transaction_memo = $_REQUEST['transaction_memo'];
$transaction_comments = $_REQUEST['transaction_comments'];
$narrow_by_delcode_id = $_REQUEST['narrow_by_delcode_id'];

// Figure out how to narrow by delcode_id
if ($narrow_by_delcode_id == "")
  {
    $narrow_by_delcode_id_where = '
      AND 1';
  }
else
  {
    $narrow_by_delcode_id_where = '
      AND delcode_id = "'.mysql_real_escape_string ($narrow_by_delcode_id).'"';
  }

// Get a selector for delcode_ids for this cycle
$sql_delcode_id = '
  SELECT
    DISTINCT '.TABLE_BASKET_ALL.'.delcode_id,
    '.TABLE_DELCODE.'.delcode
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN
    '.TABLE_DELCODE.'
  ON '.TABLE_DELCODE.'.delcode_id = '.TABLE_BASKET_ALL.'.delcode_id
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  ORDER BY
    delcode';
$result_delcode_id = @mysql_query($sql_delcode_id, $connection) or die("".mysql_error()."");
$select_delcode_id = '
  <select name="narrow_by_delcode_id" id="delcode_id">
      <option value="" select="selected">ALL DELIVERY LOCATIONS</option>';
while ( $row = mysql_fetch_object($result_delcode_id) )
  {
    $select_delcode_id .= '
      <option value="'.$row->delcode_id.'"'.($row->delcode_id == $narrow_by_delcode_id ? ' selected="selected"' : '').'>'.$row->delcode.'</option>';
  }
$select_delcode_id .= '
  </select>';
// Check for any needed updates to post into the database
if ( $_REQUEST['updatevalues'] == "ys"
     && !empty($payment_method)
     && $_SESSION['valid_update_key'] == $_POST['valid_update_key'])
  {
  $sql77 = '
    SELECT
      delivery_id,
      basket_id
    FROM
      '.TABLE_BASKET_ALL.'
    LEFT JOIN
      '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
    WHERE
      '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
      '.$narrow_by_delcode_id_where.'
    ORDER BY
      last_name ASC,
      business_name ASC';
  $result77 = @mysql_query($sql77, $connection) or die("".mysql_error()."");
  while ( $row = mysql_fetch_array($result77) )
    {
      $basket_id = $row['basket_id'];
      $sql_pay = '
        SELECT
          payment_method
        FROM
          '.TABLE_BASKET_ALL.'
        WHERE
          basket_id = "'.mysql_real_escape_string ($basket_id).'"';
      $result_pay = @mysql_query($sql_pay, $connection) or die("".mysql_error()."");
      while ( $row = mysql_fetch_array($result_pay) )
        {
          // If changing the payment method, then unfinalize the invoice
          $payment_method_previous = $row['payment_method'];
          if ( $payment_method_previous != $payment_method[$basket_id] && strlen ($payment_method[$basket_id]) > 0)
            {
              $finalized2 = "0";
              $payment_method2 = $payment_method[$basket_id];
            }
          // Otherwise, keep the invoice finalize status as it was before
          else
            {
              $finalized2 = $finalized[$basket_id];
              $payment_method2 = $payment_method_previous;
            }
          $amount_paid_update = preg_replace("/[^0-9\.\-]/","",$amount_paid[$basket_id]);
          $membership_amount_paid_update = preg_replace("/[^0-9\.\-]/","",$membership_amount_paid[$basket_id]);
          $payment_method2 = preg_replace("/[^a-zA-Z]/","",$payment_method2);
          $finalized2 = preg_replace("/[^0-9]/","",$finalized2);
          $member_id = preg_replace("/[^0-9]/","",$_POST['member_id'][$basket_id]);
          $batchno = preg_replace("/[^0-9]/","",$_POST['transaction_batchno'][$basket_id]);
          $memo = strip_tags($_POST['transaction_memo'][$basket_id]);
          $comments = strip_tags($_POST['transaction_comments'][$basket_id]);
          if ( $member_id && ($amount_paid_update != 0 || ($payment_method_previous != $payment_method[$basket_id])) || $membership_amount_paid_update != 0)
            {
              if ( $amount_paid_update != 0 )
                {
                  $sqlu = '
                    UPDATE
                      '.TABLE_BASKET_ALL.'
                    SET
                      payment_method = "'.mysql_real_escape_string ($payment_method2).'",
                      amount_paid = amount_paid '.($amount_paid_update > 0 ? "+" : "").' '.mysql_real_escape_string ($amount_paid_update).',
                      order_date = now()
                    WHERE
                      basket_id = "'.mysql_real_escape_string ($basket_id).'"
                      AND delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
                  $resultu = @mysql_query($sqlu, $connection) or die(mysql_error());
                  $message[$member_id] = 'Shopping payment for member #'.$member_id.' was posted. ';
                }
              elseif ( $payment_method_previous != $payment_method[$basket_id] )
                {
                  // only change payment method if no amount chosen
                  $sqlu = '
                    UPDATE '.TABLE_BASKET_ALL.'
                    SET
                      payment_method = "'.mysql_real_escape_string ($payment_method2).'",
                      finalized = "'.mysql_real_escape_string ($finalized2).'",
                      order_date = now()
                    WHERE
                      basket_id = "'.mysql_real_escape_string ($basket_id).'"
                      AND delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
                  $resultu = @mysql_query($sqlu, $connection) or die(mysql_error());
                  $message[$member_id] = 'Payment method for member #'.$member_id.' was updated. ';
                }
              $member_id = preg_replace("/[^0-9]/","",$_POST['member_id'][$basket_id]);
              $batchno = preg_replace("/[^0-9]/","",$_POST['transaction_batchno'][$basket_id]);
              $memo = strip_tags($_POST['transaction_memo'][$basket_id]);
              $comments = strip_tags($_POST['transaction_comments'][$basket_id]);
              $query = '
                INSERT INTO
                  '.TABLE_TRANSACTIONS.'
                    (
                      transaction_type,
                      transaction_name,
                      transaction_amount,
                      transaction_user,
                      transaction_member_id,
                      transaction_basket_id,
                      transaction_delivery_id,
                      transaction_timestamp,
                      transaction_batchno,
                      transaction_memo,
                      transaction_comments,
                      transaction_method
                    )
                VALUES
                  (
                    "23",
                    "Invoice Payment",
                    "'.mysql_real_escape_string ($amount_paid_update).'",
                    "'.mysql_real_escape_string ($_SESSION['member_id']).'",
                    "'.mysql_real_escape_string ($member_id).'",
                    "'.mysql_real_escape_string ($basket_id).'",
                    "'.mysql_real_escape_string ($delivery_id).'",
                    now(),
                    "'.mysql_real_escape_string ($batchno).'",
                    "'.mysql_real_escape_string ($memo).'",
                    "'.mysql_real_escape_string ($comments).'",
                    "'.mysql_real_escape_string ($payment_method2).'")';
              $sql = mysql_query($query);
              if ( $member_id && ($membership_amount_paid_update != 0) )
                {
                  // In this query, we change the sign of the membership amount paid
                  // because it must add in a positive sense with membership receivables
                  $query = '
                    INSERT INTO
                      '.TABLE_TRANSACTIONS.'
                        (
                          transaction_type,
                          transaction_name,
                          transaction_amount,
                          transaction_user,
                          transaction_member_id,
                          transaction_basket_id,
                          transaction_delivery_id,
                          transaction_timestamp,
                          transaction_batchno,
                          transaction_memo,
                          transaction_comments,
                          transaction_method
                        )
                    VALUES
                      (
                        "25",
                        "Membership Payment Received",
                        "'.mysql_real_escape_string (($membership_amount_paid_update * -1)).'",
                        "'.mysql_real_escape_string ($_SESSION['member_id']).'",
                        "'.mysql_real_escape_string ($member_id).'",
                        "'.mysql_real_escape_string ($basket_id).'",
                        "'.mysql_real_escape_string ($delivery_id).'",
                        now(),
                        "'.mysql_real_escape_string ($batchno).'",
                        "'.mysql_real_escape_string ($memo).'",
                        "'.mysql_real_escape_string ($comments).'",
                        "'.mysql_real_escape_string ($payment_method2).'")';
                  $sql = mysql_query($query);
                  $message[$member_id] .= 'Membership payment for member #'.$member_id.' was posted. ';
                }
              // Remember this member_id (i.e. keep track of the last member_id updated so
              // we can jump to that location on the receive payments page
              $jump_to_member_id = $member_id;
            }
        }
    }
}
// Set a new valid_update_key
$_SESSION['valid_update_key'] = uniqid('', true);

// End of update loop.
$sql_sum = '
  SELECT
    delivery_id,
    SUM(subtotal) AS sub_sum
  FROM
    '.TABLE_BASKET_ALL.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY delivery_id';
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
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY '.TABLE_BASKET_ALL.'.delivery_id';
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
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY '.TABLE_BASKET_ALL.'.delivery_id';
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
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY delivery_id';
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
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY delivery_id';
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
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
    AND '.TABLE_BASKET.'.out_of_stock != "1"
  GROUP BY
    '.TABLE_BASKET_ALL.'.delivery_id';
$result_sum6 = @mysql_query($sql_sum6, $connection) or die("Couldn't execute query 6.");
while ( $row = mysql_fetch_array($result_sum6) )
  {
    $quantity_all = $row['sumq'];
  }
$surcharge = "";
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
    '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
    AND '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
    '.$narrow_by_delcode_id_where.'
  GROUP BY
    '.TABLE_BASKET_ALL.'.basket_id
  ORDER BY
    last_name ASC,
    business_name ASC';
$result = @mysql_query($sql, $connection) or die("Couldn't execute query 1.");
$numtotal = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $business_name = $row['business_name'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $delcode = $row['delcode'];
    $delivery_cost = $row['delivery_cost'];
    $transcharge = $row['transcharge'];
    $delivery_date = $row['delivery_date'];
    $payment_method = $row['payment_method'];
    $payment_desc = $row['payment_desc'];
    $surcharge_for_paypal = $row['surcharge_for_paypal'];
    $subtotal = $row['subtotal'];
    $coopfee = $row['coopfee'];
    $grand_total_cust = $row['grand_total'];
    $grand_total_coop = $row['grand_total_coop'];
    $last_modified = $row['last_modified'];
    $prev_balance = $row['prev_balance'];
    $amount_paid = $row['amount_paid'];
    $draft_emailed = $row['draft_emailed'];
    $finalized = $row['finalized'];

    // Get all prior membership dues
    $query = '
      SELECT
        SUM('.TABLE_TRANSACTIONS.'.transaction_amount) AS total,
        '.TABLE_TRANS_TYPES.'.ttype_parent
      FROM
        '.TABLE_TRANSACTIONS.'
      LEFT JOIN
        '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
      WHERE
        transaction_delivery_id <= "'.mysql_real_escape_string ($delivery_id).'"
        AND ttype_parent = "40"
        AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
      GROUP BY transaction_member_id';
    $sql = @mysql_query($query) or die(mysql_error());
    if ($row = mysql_fetch_array($sql))
      {
        $membership_dues = $row['total'];
      }
    // Get membership amounts charged this time
    $query = '
      SELECT
        SUM('.TABLE_TRANSACTIONS.'.transaction_amount) AS total,
        '.TABLE_TRANS_TYPES.'.ttype_parent
      FROM
        '.TABLE_TRANSACTIONS.'
      LEFT JOIN
        '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
      WHERE
        transaction_delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
        AND ttype_parent = "40"
        AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
        AND
          (
             transaction_type = 24
          OR transaction_type = 64
          )
      GROUP BY transaction_member_id';
    $sql = @mysql_query($query) or die(mysql_error());
    if ($row = mysql_fetch_array($sql))
      {
        $membership_dues_charged = $row['total'];
      }
    // And membership amounts paid this time
    $query = '
      SELECT
        SUM('.TABLE_TRANSACTIONS.'.transaction_amount) AS total,
        '.TABLE_TRANS_TYPES.'.ttype_parent
      FROM
        '.TABLE_TRANSACTIONS.'
      LEFT JOIN
        '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
      WHERE
        transaction_delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
        AND ttype_parent = "40"
        AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
        AND transaction_type != 24
        AND transaction_type != 64
      GROUP BY transaction_member_id';
    $sql = @mysql_query($query) or die(mysql_error());
    if ($row = mysql_fetch_array($sql))
      {
        $membership_dues_paid = $row['total'] * -1;
      }
    else
      {
        $membership_dues_paid = 0;
      }
    if ( $current_basket_id < 0 )
      {
        $current_basket_id = $row['basket_id'];
      }
    while ( $current_basket_id != $basket_id )
      {
        $current_basket_id = $basket_id;
        $cust_salestax = "";
        $sql_sums = '
          SELECT
            collected_statetax,
            collected_citytax,
            collected_countytax
          FROM
            '.TABLE_CUSTOMER_SALESTAX.'
          WHERE
            customer_salestax.basket_id = "'.mysql_real_escape_string ($basket_id).'"';
        $result_sums = @mysql_query($sql_sums, $connection) or die("Couldn't execute query sales tax.");
        while ( $row = mysql_fetch_array($result_sums) )
          {
            $collected_statetax = $row['collected_statetax'];
            $collected_citytax = $row['collected_citytax'];
            $collected_countytax = $row['collected_countytax'];
            $cust_salestax = $collected_statetax + $collected_citytax + $collected_countytax;
            $total_salestax = $cust_salestax + $total_salestax + 0;
          }
        $draft_emailed = '';
        if ( $draft_emailed )
          {
            $draft_emailed = 'Y';
          }
        $final_invoice = '';
        if ( $finalized == 1 )
          {
            $final_invoice = 'Y';
          }
        if ( $payment_method == 'P')
          {
            $p_chk = "checked";
            $c_chk = "";
            if ( $delivery_id > DELIVERY_NO_PAYPAL )
              {
                $subtotal_1 = $subtotal + $coopfee + $transcharge + $delivery_cost + $cust_salestax;
                $total_sent_to_paypal = ($subtotal_1 + .30) / .971;
                //$surcharge = number_format((($total_sent_to_paypal*.029) + .30),2);
                $surcharge = number_format($surcharge_for_paypal, 2);
                if ($surcharge_for_paypal) $minus_paypal = "<br>-$surcharge for paying by check/cash";
              }
          }
        elseif ( $payment_method == 'C' )
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
        $quantity_mem = '';
        $sql_sum8 = '
          SELECT
            sum(quantity) AS sum_mem
          FROM
            '.TABLE_BASKET.'
          LEFT JOIN
            '.TABLE_BASKET_ALL.'
          ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
          WHERE
            '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
            AND '.TABLE_BASKET.'.out_of_stock != "1"
            AND '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($member_id).'"
          GROUP BY
            '.TABLE_BASKET_ALL.'.delivery_id';
        $result_sum8 = @mysql_query($sql_sum8, $connection) or die("Couldn't execute query 8.");
        while ( $row = mysql_fetch_array($result_sum8) )
          {
            $quantity_mem = $row['sum_mem'];
          }
        // to override the amount paid from the customer_basket_overall
        $sql_t = mysql_query('
          SELECT
            SUM(transaction_amount) as amount_paid FROM
            '.TABLE_TRANSACTIONS.'
          WHERE
            transaction_type = "23"
            AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
            AND transaction_delivery_id = "'.mysql_real_escape_string ($delivery_id).'"');
        $result_t = mysql_fetch_array($sql_t);
        if ( $result_t['amount_paid'] > 0 )
          {
            $amount_paid = $result_t['amount_paid'];
          }
        $amount_paid = number_format($amount_paid, 2, '.', '');
        $discrepancy = 0;
        $discrepancy = $grand_total_cust - $surcharge - $amount_paid;
        if ( $delivery_id == 10 )
          {
            $delivery_id_previous = $delivery_id - 2;
          }
        else
          {
            $delivery_id_previous = $delivery_id - 1;
          }
        $discrepancy_previous = '';
        $grand_total_cust_previous = '';
        $surcharge_previous = '';
        $amount_paid_previous = '';
        $sqldp = '
          SELECT
            '.TABLE_BASKET_ALL.'.delivery_id,
            '.TABLE_BASKET_ALL.'.grand_total,
            '.TABLE_BASKET_ALL.'.amount_paid,
            '.TABLE_BASKET_ALL.'.surcharge_for_paypal
          FROM
            '.TABLE_BASKET_ALL.'
          WHERE
            '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id_previous).'"
            AND '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($member_id).'"';
        $resultdp = @mysql_query($sqldp, $connection) or die("Couldn't execute query for previous discrepancy.");
        while ( $row = mysql_fetch_array($resultdp) )
          {
            $grand_total_cust_previous = $row['grand_total'];
            $surcharge_previous = number_format($row['surcharge_for_paypal'], 2);
            $amount_paid_previous = $row['amount_paid'];
          }
        $transaction_batchno = "";
        $transaction_memo = "";
        $transaction_comments = "";
        $sqlt = mysql_query('
          SELECT
            transaction_batchno,
            transaction_memo,
            transaction_comments 
          FROM
            '.TABLE_TRANSACTIONS.' t
          WHERE
            t.transaction_type ="23"
            AND t.transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
            AND t.transaction_basket_id ="'.mysql_real_escape_string ($basket_id).'"
          ORDER BY
            transaction_id DESC LIMIT 1');
        $trans = mysql_fetch_array($sqlt);
        $discrepancy_previous = $grand_total_cust_previous - $surcharge_previous - $amount_paid_previous;
        if ( $discrepancy_previous != 0)
          {
            $discrep_class = 'discrep';
          }
        else
          {
            $discrep_class = '';
          }
        include("../func/show_name_last.php");

        if ($finalized != 1 )
          {
            $finalized_class = 'unfinal';
          }
        else
          {
            $finalized_class = '';
          }
        if ( $finalized != 1 )
          {
            $unfinalized = '<font size="-2" color="#800000"><br>(Unfinalized)</font>';
          }
        else
          {
            $unfinalized = '';
          }

        // Colorize cells:
        $shopping_still_due = round ($grand_total_cust - $membership_dues - $membership_dues_paid - $amount_paid, 2);
        if ($shopping_still_due == 0)
          {
            $shopping_due_class = 'good';
          }
        elseif ($shopping_still_due < 0) // Overpaid
          {
            $shopping_due_class = 'warn';
          }
        else // Underpaid
          {
            $shopping_due_class = 'alert';
          }

        $membership_still_due = round ($membership_dues, 2);
        if ($membership_still_due == 0)
          {
            $membership_due_class = 'good';
          }
        elseif ($membership_still_due < 0) // Overpaid
          {
            $membership_due_class = 'warn';
          }
        else // Underpaid
          {
            $membership_due_class = 'alert';
          }

        $total_still_due = round ($discrepancy - $membership_dues_paid + $discrepancy_previous);
        if ($total_still_due == 0)
          {
            $total_due_class = 'good';
          }
        elseif ($total_still_due < 0) // Overpaid
          {
            $total_due_class = 'warn';
          }
        else // Underpaid
          {
            $total_due_class = 'alert';
          }

                  $display_month .= '
                    <tr'.($jump_to_member_id == $member_id ? ' id="jump_target"' : "").' class='.$discrep_class.$finalized_class.'>
                      <td align="right" valign="top"><font face="arial" size="-1"><b># '.$member_id.'</b></td>
                      <td align="left" valign="top"><font face="arial" size="-1">
                        <b><a href="customer_invoice.php?member_id='.$member_id.'&basket_id='.$basket_id.'&delivery_id='.$delivery_id.'" target="_blank">'.$show_name.'</a></b>'.$unfinalized.'</td>
                      <td align="left" valign="top"><font face="arial" size="-1">
                        <input type=radio name="payment_method['.$basket_id.']" value="P" '.$p_chk.'>P<br>
                        <input type=radio name="payment_method['.$basket_id.']" value="C" '.$c_chk.'>C</td>


                      <td align="right" valign="top" class="'.$discrep_class.'"><font face="arial" size="-1">$'.number_format($discrepancy_previous, 2).'</td>


                      <td align="right" valign="top" class="'.$discrep_class.'"><font face="arial" size="-1">$'.number_format((double)($amount_paid + $membership_dues_paid), 2).'</td>
                      <td align="right" valign="top" class="'.$shopping_due_class.'"><nobr><font face="arial" size="-1"><b>$'.number_format((double)($grand_total_cust - $membership_dues - $membership_dues_paid), 2).'</b> '.$minus_paypal.'<br>
                        $'.number_format((double)($amount_paid), 2).'<br>
                        <input type="text" name="amount_paid['.$basket_id.']" size="5" maxlength="10" id="shopping_amount'.$member_id.'"> </nobr></td>
                      <td align="right" valign="top" class="'.$membership_due_class.'"><nobr><font face="arial" size="-1">$'.number_format($membership_dues + $membership_dues_paid, 2).'<br>
                        $'.number_format($membership_dues_paid, 2).'<br>
                        <input type="text" name="membership_amount_paid['.$basket_id.']" size="5" maxlength="10" id="membership_amount'.$member_id.'"> </nobr></td>

                      <td align="right" valign="top" class="'.$total_due_class.'"><font face="arial" size="-1">$'.number_format((double)($discrepancy - $membership_dues_paid + $discrepancy_previous), 2).'
                        <input type="hidden" name="member_id['.$basket_id.']" value="'.$member_id.'">
                        <input type="hidden" name="finalized['.$basket_id.']" value="'.$finalized.'"></td>
                      <td><input type="input" name="transaction_batchno['.$basket_id.']" value="'.$trans['transaction_batchno'].'" maxlength="8" size="4" id="batchno'.$member_id.'"></td>
                      <td><input type="input" name="transaction_memo['.$basket_id.']" value="'.$trans['transaction_memo'].'" maxlength="20" size="10"></td>
                      <td><input type="input" name="transaction_comments['.$basket_id.']" value="'.$trans['transaction_comments'].'" maxlength="200"></td>
                      <td align="right" valign="top"><font face="arial" size="-2"><i>'.$last_modified.'</i></td>
                    </tr>';
                  $amount_paid_total = $amount_paid_total + $amount_paid + 0;
      }
  }
$fontface='arial';

$page_specific_javascript = '
  <script type="text/javascript" src="auto_fill_ctotals.js"></script>';

$page_specific_css = '
  <style type="text/css">
    td.discrep {
      background-color: #ddc;
      }
    td.unfinal {
      background-color: #ddd;
      }
    td.good {
      background-color: #dfd;
      }
    td.warn {
      background-color: #eeb;
      }
    td.alert {
      background-color: #edd;
      }
  </style>';

$content_ctotals .= '
<h2>Delivery Date '.$delivery_date.'</h2>
<b>Total Products Sold: '.$quantity_all.' Products &nbsp;&nbsp;&nbsp;
Total Orders: '.$numtotal.'</b> &nbsp;&nbsp;&nbsp;<font size="-1">(Print Landscape for best results.)</font>
<br>Click here for <a href="ctotals_reports.php?delivery_id='.$delivery_id.'">Customer Totals Report</a> |
Click here for <a href="totals_saved.php">Previous Reports</a>

<h2>Auto-fill form</h2>
<p>Paste sales data from Excel into the text area below to auto-fill the form. The first column should be member id#,
the second and third columns should be the shopping amount paid and membership dues payment, respectively. Dollar
amounts with a $ prefix will have the $ removed, and rows with empty dollar amounts will be ignored.</p>
<form onsubmit="form_auto_fill(); return(false);">
<textarea id="auto_fill_box" cols="30" rows="4"></textarea><br>
Batch #:<input type="text" id="auto_fill_batchno"><br>
<input type="submit" value="Auto Fill">
</form>

<br><p class="error_list">'.(count($message) ? implode ('<br>', $message) : '').'</p>
<hr>
<form action="'.$_SERVER['PHP_SELF'].'?delivery_id='.$delivery_id.'" method="post">
Narrow by delivery location'.$select_delcode_id.'
<input type="submit" name="where" value="SET LOCATION">
</form><br><br>

<form action="'.$_SERVER['PHP_SELF'].'?delivery_id='.$delivery_id.'&updatevalues=ys#jump_target" method="post">
<input type="hidden" name="narrow_by_delcode_id" value="'.$narrow_by_delcode_id.'">
<input type="hidden" name="valid_update_key" value="'.$_SESSION['valid_update_key'].'">
<table cellpadding="2" cellspacing="0" border="1">
  <tr>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Mem. ID</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Member Name</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Payment Method</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Previous<br>Discrepency</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Amount Paid So Far</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Shopping <br>Due / Pmt.</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Membership <br>Due / Pmt.</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Total Still Owed</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Batch No.</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Memo</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Comments</th>
    <th valign="bottom" bgcolor="#DDDDDD"><font face="'.$fontface.'" size="-2">Last Modified</th>
  </tr>
'.$display_month.'
  <tr><td bgcolor="#AEDE86" colspan="5" align="right">
  </td><td bgcolor="#AEDE86" align="right">
  <b>$'.number_format($amount_paid_total,2).'</b>
  </td><td bgcolor="#AEDE86" colspan="6" align="right">
    <input name="where" type="submit" value="SAVE CHANGES">
  </td></tr>
</table>
  </form>';

$page_title_html = '<span class="title">Treasurer Functions</span>';
$page_subtitle_html = '<span class="subtitle">Order Breakdown by Customer</span>';
$page_title = 'Treasurer Functions: Order Breakdown by Customer';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_ctotals.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
