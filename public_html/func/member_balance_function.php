<?php
function getMemberBalance($member_id, $delivery_id, $method)
  {
    $delivery_ids = array();
    $balances = array();
    if ( $member_id > 0 )
      {
        $current_delivery_id = $row['delivery_id'];
        $query = '
          SELECT
            username,
            first_name,
            last_name,
            first_name_2,
            last_name_2,
            auth_type
          FROM
            '.TABLE_MEMBER.'
          WHERE
            member_id = "'.mysql_real_escape_string ($member_id).'";';
        $sql = mysql_query("$query");
        $row = mysql_fetch_array($sql);
        $member_name = $row['first_name']." ".$row['last_name'];
        $auth_type = $row['auth_type'];
        if ($row['first_name_2'] || $row['last_name_2'])
          {
            $member_name .= ' / '.$row['first_name_2']." ".$row['last_name_2'];
          }
        $member_name .= ' ('.$row['username'].' #'.$member_id.')';
        $day = date('d') + 1;
        $date = date('Y-m').'-'.$day;
        $sql = mysql_query('
          SELECT delivery_id
          FROM
            '.TABLE_ORDER_CYCLES.'
          WHERE
            delivery_id <= "'.mysql_real_escape_string ($delivery_id).'"');
        while ( $row = mysql_fetch_array($sql) )
          {
            $delivery_ids[] = $row['delivery_id'];
          }
        $previous_balance = 0;
        $amount_paid_total = 0;
        $previous_balance_old = 0;
        $previous_balance_trans = 0;
        $totals_total = 0;
        $amount_paid = 0;
        $prev_grand_total_total = 0;
        $adjustments = 0;
        $prev_grand_total =0;
        $payment_method = '';
        foreach( $delivery_ids as $d_id )
          {
            $handling = 0;
            //amount paid
            // invoice payments
            $amount_paid_total = 0;
            $previous_balance_old = 0;
            $previous_balance_trans = 0;
            $totals_total = 0;
            $amount_paid = 0;
            $prev_grand_total_total = 0;
            $adjustments = 0;
            $prev_grand_total =0;
            $payment_method = '';
            //SUM(transaction_amount) as total //GROUP BY transaction_delivery_id
            //removed the group by in preparation for a later update accomodating handling fees when different payment
            //methods used. right now it assess the handling on the latest payment type applied
            $sql = mysql_query('
              SELECT
                transaction_amount AS total,
                transaction_method
              FROM
                '.TABLE_TRANSACTIONS.' t,
                '.TABLE_TRANS_TYPES.' tt
              WHERE
                transaction_type = ttype_id
                AND ttype_parent = "21"
                AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
                AND transaction_delivery_id = "'.mysql_real_escape_string ($d_id).'"');
            while ( $row = mysql_fetch_array($sql) )
              {
                $amount_paid += $row['total'];
                $payment_method = '('.$row['transaction_method'].')';
                $amount_paid_display .= $row['total'].' '.$payment_method;
              }
            //NEW balance
            $total27 = 0;
            $total29 = 0;
            $total30 = 0;
            $total32 = 0;
            $total33 = 0;
            $total36 = 0;
            $totals_stored = false;
            $sql = mysql_query('
              SELECT
                transaction_type,
                transaction_amount
              FROM
                '.TABLE_TRANSACTIONS.'
              WHERE
                transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
                AND transaction_delivery_id = "'.mysql_real_escape_string ($d_id).'"
                AND
                  (
                    transaction_type = "27"
                    OR transaction_type = "29"
                    OR transaction_type = "30"
                    OR transaction_type = "32"
                    OR transaction_type = "33"
                  )
              ORDER BY transaction_id ASC');
            // this way, the last loop around has the most recent
            while ( $row = mysql_fetch_array($sql) )
              {
                if ( $row['transaction_type'] == 27 )
                  {
                    // basket total
                    $total27 = $row['transaction_amount'];
                  }
                elseif ( $row['transaction_type'] == 29 )
                  {
                    // sales tax
                    $total29 = $row['transaction_amount'];
                  }
                elseif ( $row['transaction_type'] == 30 )
                  {
                    // coop fee
                    $total30 = $row['transaction_amount'];
                  }
                elseif ( $row['transaction_type'] == 32 )
                  {
                    // handling
                    $total32 = $row['transaction_amount'];
                  }
                elseif ( $row['transaction_type'] == 33 )
                  {
                    // delivery
                    $total33 = $row['transaction_amount'];
                  }
                $totals_stored = true;
              }
            if ( $totals_stored )
              {
                // non-taxed adjustments
                $query_trans = '
                  SELECT
                    SUM(transaction_amount) AS total
                  FROM
                    '.TABLE_TRANSACTIONS.' t,
                    '.TABLE_TRANS_TYPES.' tt
                  WHERE
                    transaction_type = ttype_id
                    AND ttype_parent = "20"
                    AND tt.ttype_whereshow = "customer"
                    AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
                    AND transaction_delivery_id = "'.mysql_real_escape_string ($d_id).'"
                    AND transaction_taxed = "0"';
                $sql = mysql_query($query_trans);
                while ( $row = mysql_fetch_array($sql) )
                  {
                    $adj_nontaxed = $row['total'];
                  }
                // taxable adjustments
                $query = '
                  SELECT
                    SUM(transaction_amount * (1 + ('.(strpos ($auth_type, 'institution') ? 'wholesale' : 'retail').'_markup / 100))) as total,
                    '.TABLE_ORDER_CYCLES.'.delivery_date
                  FROM
                    '.TABLE_TRANSACTIONS.'
                  LEFT JOIN '.TABLE_TRANS_TYPES.'
                    ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
                  JOIN
                    '.TABLE_ORDER_CYCLES.' ON delivery_id = "'.mysql_real_escape_string ($d_id).'"
                  WHERE
                    ttype_parent = "20"
                    AND '.TABLE_TRANS_TYPES.'.ttype_whereshow = "customer"
                    AND transaction_member_id = "'.mysql_real_escape_string ($member_id).'"
                    AND transaction_delivery_id = "'.mysql_real_escape_string ($d_id).'"
                    AND transaction_taxed = "1"
                  GROUP BY
                    '.TABLE_ORDER_CYCLES.'.delivery_date';
                if ($sql = mysql_query($query))
                  {
                    $row = mysql_fetch_array($sql);
                    $adjustments = $row['total'];
                    $delivery_date = $row['delivery_date'];
                    $subtotal_1 = $total27 + $total33 + $total29 + $total30 + $adjustments;
                    $total_sent_to_paypal = ($subtotal_1 + .30)/ .971;
                    $surcharge_for_paypal = number_format(round((($total_sent_to_paypal * .029) + .30) + 0.00000001, 2), 2);
                  }
                // Clobber paypal variables if not using paypal
                if ($d_id >= DELIVERY_NO_PAYPAL )
                  {
                    $total_sent_to_paypal = 0;
                    $surcharge_for_paypal = 0;
                  }
                if ( $amount_paid > 0 )
                  {
                    if ( $surcharge_for_paypal == .31 )
                      {
                        $handling = number_format(0, 2);
                        $cashdiscount = number_format(0, 2);
                      }
                    elseif ( $payment_method == '(P)' )
                      {
                        $handling = $surcharge_for_paypal;
                        $cashdiscount = number_format(0, 2);
                      }
                    else
                      {
                        $handling = $surcharge_for_paypal;
                        $cashdiscount = number_format(round(((($amount_paid + $handling) * .029) + .30) + 0.00000001, 2), 2);
                      }
                  }
                else
                  {
                    //not paid yet
                    $payment_method = "";
                    if ( $surcharge_for_paypal == .31 || $surcharge_for_paypal <= 0 )
                      {
                        $handling = number_format(0,2);
                      }
                    else
                      {
                        $handling = $surcharge_for_paypal;
                      }
                    //$cashdiscount = number_format(0,2);
                    $cashdiscount = $handling;
                  }
                if ( $handling < 0 )
                  {
                    $handling = 0;
                  }
                if ( $cashdiscount < 0 || $handling == 0 )
                  {
                    $cashdiscount = 0;
                  }
                $basket_total = $total27 + $adjustments;
                $totals_total = $basket_total + $total29 + $total30 + $total33 + $handling;
                $info .= '<tr><td align="right" style="border-bottom:1px solid #dde;">&nbsp;</td>
                  <td style="border-bottom:1px solid #dde;">NEW: transactions totals '.number_format ($totals_total, 2).' = basket_total '.number_format ($basket_total, 2).' + sales_tax '.$total29.' + (coop fee '.$total30.' + handling '.$handling.')  + delivery '.$total33.'</td></tr>';
                $previous_balance_trans = '';
                $previous_balance_trans = $previous_balance + $totals_total + $adj_nontaxed - $amount_paid - $cashdiscount;
                $previous_balance_trans = round($previous_balance_trans + 0.00000001, 2);
                $info .= '<tr><td align="right" style="border-bottom:1px solid #dde;">&nbsp;</td>
                  <td style="border-bottom:1px solid #dde;">NEW: previous_balance '.number_format ($previous_balance_trans, 2).' =  previous balance '.number_format ($previous_balance, 2).' + transaction totals '.number_format ($totals_total, 2).' + non-taxed net adjustments '.$adj_nontaxed.' - cash discount '.$cashdiscount.' - amount paid '.number_format ($amount_paid, 2).' '.$payment_method.' </td></tr>';
                $previous_balance = $previous_balance_trans;
              }
            if ( $method == "display" )
              {
                $sql = mysql_query('
                  SELECT
                    basket_id,
                    finalized 
                  FROM
                    '.TABLE_BASKET_ALL.'
                  WHERE
                    member_id = "'.mysql_real_escape_string ($member_id).'"
                    AND delivery_id = "'.mysql_real_escape_string ($d_id).'" limit 1');
                $result = mysql_fetch_array($sql);
                $info .= '<tr><td align="right" style="border-bottom:1px solid #666;">';
                if ($delivery_date) $info .= '<b>'.$delivery_date.'</b><br>';
                $info .= $d_id;
                if ($result['basket_id']) $info .= ' / '.$result['basket_id'].'<br>';
                if ( $result['basket_id'] && $result['finalized'] == 1 )
                  {
                    $info .= '<a href="invoice.php?delivery_id='.$d_id.'&basket_id='.$result['basket_id'].'&member_id='.$member_id.'" target="_blank">Finalized</a><br>';
                  }
                if ( $result['basket_id'] )
                  {
                    $info .= '<a href="customer_invoice.php?delivery_id='.$d_id.'&basket_id='.$result['basket_id'].'&member_id='.$member_id.'" target="_blank">In&nbsp;Process</a>';
                  }
                $info .= '</td>
                  <td style="border-bottom:1px solid #666;">'.number_format ($previous_balance, 2).'</td></tr>';
              }
            else
              {
                $total = $totals_total + $adjustments;
                $balances[] = array(
                  'member_id' => $member_id,
                  'delivery_id' => $d_id,
                  'basket_id'=>$result['basket_id'],
                  'total'=>$total,
                  'amount_paid'=>$amount_paid,
                  'payment_method'=>$payment_method,
                  'balance'=>$previous_balance);
              }
            // Clobber the delivery date so it won't show up next time through with the wrong value
            $delivery_date = '';
          }
        // Get membership dues separately
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
            $info .= '
              <tr>
                <td align="right"><a href="membership_history.php?member_id='.$member_id.'" target="_blank">Membership Dues</a>:</td>
                <td>$'.number_format($row['total'], 2).'</td>
              </tr>';
            // Add membership dues to total balance due
            $previous_balance = $previous_balance + $row['total'];
          }
        if ( $previous_balance > 0 )
          {
            $status = 'Owed';
            $display_previous_balance = $previous_balance;
          }
        elseif ( $previous_balance == 0 )
          {
            $status = "";
            $display_previous_balance = '';
          }
        else
          {
            $status = 'Credit';
            $display_previous_balance = 0 - $previous_balance;
          }
        $info .= '<tr id="balance"><td><strong>Balance #'.$member_id.'</td>
          <td><strong>$'.number_format((double)$display_previous_balance, 2).' '.$status.'</strong></td></tr>';
      }
    if ( $method == 'display' )
      {
      $display = '
        <div align="center">
        <form method="POST" action="'.$_SERVER['PHP_SELF'].'#balance" name="lookup_member">
        Enter a member ID to look up their balance:
        <input id="load_target" type="text" name="member_id" maxlength="5">
        <input type="submit" name="submit" value="Lookup">
        </form>
        <div style="margin:0.5em;padding:0.5em;background-color:#ffe;width:50%;border:1px solid #fda;font-size:140%">'.$member_name.'</div>
        <div style="width:800px;height:450px;overflow-y:scroll;border:1px solid black">
          <table style="width:750px;border-collapse:collapse;">
          <tr><th>Deliery Date<br>Del/Basket</th><th>Balance Information</th></tr>
          '.$info.'
          </table>
          <div id="bottom"></div>
        </div>
        <br />
        </div>';
      }
    else
      {
        $display = $balances;
      }
    return $display;
  }
?>