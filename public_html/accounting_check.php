<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin');


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                     //
//   The purpose of this script is to compare accounting information in the customer_basket_overall table and the      //
//   transactions table.  The values in these two tables should match, and when they do not, there is likely to be     //
//   some kind of accounting problem that should be resolved.  Most notably, this may be necessary to have corrected   //
//   before transitioning into a more robust accounting system.                                                        //
//                                                                                                                     //
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Set up styles for this page
$output = '
    </font>
    <style type="text/css">
      body { font-family:verdana,arial,sans-serif; }
      td { border: 1px solid #000;font-size:80%; }
      .lg { border-left:2px solid #000;vertical-align:top; }
      .rg { border-right:2px solid #000;vertical-align:top; }
      .money { text-align:right; }
      table { border-collapse:collapse;margin:auto; }
      .small { font-size:70%; }
      .normal { background-color:#fff; }
      .highlight { background-color:#faa; }
      tr.h2 th { background-color:#eef; color:#006; }
      a { color:#666;text-decoration:none; }
      a:hover { color:#00f; }
      p { margin:1em; }
      .message { font-size:1.3em;color:#800;text-align:right; }
      th {text-align:center;border-left:1px solid #999;font-size:70%;background-color:#468;padding:3px;color:#ffc;}
      table {border:1px solid #999;}
      .page_link {display:block;background-color:#ffe;padding:2px 15px;float:left;margin-bottom:5px;border:1px solid #999;border-left:0;}
      .page_link:hover {background-color:#dcb;}
      .this_page {display:block;background-color:#fed;padding:2px 15px;float:left;margin-bottom:5px;border:1px solid #999;border-left:0;}
      .page_link_text {display:block;padding:2px 15px;float:left;margin-bottom:5px;border-top:1px solid #999;border:1px solid #999;}
    </style>';

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// First see if we must handle any update requests
if ($_POST['action'] == 'Update Basket')
  {
    $basket_id = $_POST['basket_id'];
    $field = $_POST['update_field'];
    $value = $_POST[$field];
    $page = $_GET['page'];
    $query = '
      UPDATE
        '.TABLE_BASKET_ALL.'
      SET '.mysql_real_escape_string ($field).' = '.mysql_real_escape_string ($value).'
      WHERE
        basket_id = '.mysql_real_escape_string ($basket_id);
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    $message = '<p class="message">Update completed &ndash; rows affected: '.mysql_affected_rows().' </p>';
  }
elseif ($_POST['action'] == 'Update Tax Table')
  {
    $basket_id = $_POST['basket_id'];
    $collected_citytax = $_POST['collected_citytax'];
    $collected_countytax = $_POST['collected_countytax'];
    $collected_statetax = $_POST['collected_statetax'];
    $page = $_GET['page'];
    $query = '
      UPDATE
        '.TABLE_CUSTOMER_SALESTAX.'
      SET
        collected_citytax = '.mysql_real_escape_string ($collected_citytax).',
        collected_countytax = '.mysql_real_escape_string ($collected_countytax).',
        collected_statetax = '.mysql_real_escape_string ($collected_statetax).'
      WHERE
        basket_id = '.mysql_real_escape_string ($basket_id);
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    $message = '<p class="message">Update completed &ndash; rows affected: '.mysql_affected_rows().' </p>';
  }
elseif ($_POST['action'] == 'Add Transaction')
  {
    $page = $_GET['page'];
    $query = '
      INSERT INTO
        '.TABLE_TRANSACTIONS.'
      SET
        transaction_type = "'.mysql_real_escape_string ($_POST['transaction_type']).'",
        transaction_name = "'.mysql_real_escape_string ($_POST['transaction_name']).'",
        transaction_amount = "'.mysql_real_escape_string ($_POST['transaction_amount']).'",
        transaction_user = "'.mysql_real_escape_string ($_POST['transaction_user']).'",
        transaction_producer_id = "'.mysql_real_escape_string ($_POST['transaction_producer_id']).'",
        transaction_member_id = "'.mysql_real_escape_string ($_POST['transaction_member_id']).'",
        transaction_basket_id = "'.mysql_real_escape_string ($_POST['transaction_basket_id']).'",
        transaction_delivery_id = "'.mysql_real_escape_string ($_POST['transaction_delivery_id']).'",
        transaction_taxed = "'.mysql_real_escape_string ($_POST['transaction_taxed']).'",
        transaction_timestamp = "'.mysql_real_escape_string ($_POST['transaction_timestamp']).'",
        transaction_batchno = "'.mysql_real_escape_string ($_POST['transaction_batchno']).'",
        transaction_memo = "'.mysql_real_escape_string ($_POST['transaction_memo']).'",
        transaction_comments = "'.mysql_real_escape_string ($_POST['transaction_comments']).'",
        transaction_method = "'.mysql_real_escape_string ($_POST['transaction_method']).'"';
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    $message = '<p class="message">Update completed &ndash; rows affected: '.mysql_affected_rows().' </p>';
  }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Then see if there is an edit request of some kind
if ($_GET['action'] == 'edit_basket')
  {
    $page = $_GET['page'];
    $basket_id = $_GET['basket_id'];
    // There is no customer_basket_overall field for collected tax... that is stored in the customer_salestax table
    if ($_GET['field'] != 'collected_tax')
      {
        $query = '
          SELECT
            *
          FROM
            '.TABLE_BASKET_ALL.'
          WHERE
            basket_id = '.mysql_real_escape_string ($basket_id);
        $result = @mysql_query($query, $connection) or die("".mysql_error()."");
        if ( $row = mysql_fetch_object($result) )
          {
            $basket_field_array = array ('basket_id', 'member_id', 'subtotal', 'delivery_id', 'coopfee', 'delcode_id', 'deltype', 'delivery_cost', 'transcharge', 'sh', 'payment_method', 'surcharge_for_paypal', 'grand_total', 'grand_total_coop', 'prev_balance', 'amount_paid', 'order_date', 'rte_confirmed', 'finalized', 'msg_unique');
            $output .= '
                  <form action="'.$_SERVER['PHP_SELF'].'?page='.$page.'#'.$basket_id.'" method="post">
                    <table border="1">';
            foreach ($basket_field_array as $field)
              {
                if ($_GET['field'] == $field)
                  {
                    $output .= '
                      <tr><td class="highlight">'.$field.'</td><td class="highlight"><input type="text" name="'.$field.'" value="'.$row->$field.'"></td></tr>';
                  }
                else
                  {
                    $output .= '
                      <tr><td class="normal">'.$field.'</td><td class="normal">'.$row->$field.'</td></tr>';
                  }
              }
            $output .= '
                      <tr><td class="normal">invoice_content</td><td class="normal">'.strlen ($row->invoice_content).' bytes</td></tr>
                      <tr>
                        <td align="center"><input type="reset"></td>
                        <td align="center"><input type="submit" name="action" value="Update Basket"></td>
                      </tr>
                    </table>
                  <input type="hidden" name="basket_id" value="'.$basket_id.'">
                  <input type="hidden" name="update_field" value="'.$_GET['field'].'">
                  </form>';
          }
      }
    // Handle the case where we have been asked to update the collected_tax value
    else
      {
        $query = '
          SELECT
            *
          FROM
            '.TABLE_CUSTOMER_SALESTAX.'
          WHERE
            basket_id = '.mysql_real_escape_string ($basket_id);
        $result = @mysql_query($query, $connection) or die("".mysql_error()."");
        if ( $row = mysql_fetch_object($result) )
          {
            $basket_field_array = array ('id_ctx', 'delivery_id', 'basket_id', 'taxable_total', 'exempt_total', 'taxrate_state', 'collected_statetax', 'copo_city', 'taxrate_city', 'collected_citytax', 'copo_county', 'taxrate_county', 'collected_countytax', 'last_modified');
            $output .= '
                  <form action="'.$_SERVER['PHP_SELF'].'?page='.$page.'#'.$basket_id.'" method="post">
                    <table border="1">';
            foreach ($basket_field_array as $field)
              {
                if (substr ($field, 0, 9) == 'collected')
                  {
                    $output .= '
                      <tr><td class="highlight">'.$field.'</td><td class="highlight"><input type="text" name="'.$field.'" value="'.$row->$field.'"></td></tr>';
                  }
                else
                  {
                    $output .= '
                      <tr><td class="normal">'.$field.'</td><td class="normal">'.$row->$field.'</td></tr>';
                  }
              }
            $output .= '
                      <tr>
                        <td align="center"><input type="reset"></td>
                        <td align="center"><input type="submit" name="action" value="Update Tax Table"></td>
                      </tr>
                    </table>
                  <input type="hidden" name="basket_id" value="'.$basket_id.'">
                  </form>';
          }
      }
    include("template_header.php");
    echo $output;
    include("template_footer.php");
    exit (1); // exit here so as not to show the main table
  }
// Handle the case where we must update (add) a transaction
elseif ($_GET['action'] == 'edit_trans')
  {
    $transaction_id = $_GET['transaction_id'];
    $transaction_type = $_GET['transaction_type'];
    $basket_id = $_GET['basket_id'];
    $query = '
      SELECT
        *
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_id = '.mysql_real_escape_string ($transaction_id);
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    if ( $row = mysql_fetch_object($result) )
      {
        if ($transaction_type == 23)
          {
            $output .= '
              <p>NOTE: Transactions may not be deleted.  Because this transaction was an invoice payment
              , making changes below will result in the posting of a new transaction that will ADD TO the
              prior amount.</p>';
          }
        else
          {
            $output .= '
              <p>NOTE: Transactions may not be deleted.  Because this transaction will be replacing a
              finalization summary value, making changes below will result in the posting of a new transaction
              that will SUPERCEDE OF the prior value.</p>';
          }
        $basket_field_array = array ('transaction_id', 'transaction_type', 'transaction_name', 'transaction_amount', 'transaction_user', 'transaction_producer_id', 'transaction_member_id', 'transaction_basket_id', 'transaction_delivery_id', 'transaction_taxed', 'transaction_timestamp', 'transaction_batchno', 'transaction_memo', 'transaction_comments', 'transaction_method');
        $output .= '
              <form action="'.$_SERVER['PHP_SELF'].'?page='.$page.'#'.$basket_id.'" method="post">
                <table border="1">';
        foreach ($basket_field_array as $field)
          {
            if ($field == 'transaction_amount' || $field == 'transaction_batchno' || $field == 'transaction_memo' || $field == 'transaction_comments')
              {
                // Allow editing of the transaction amount and modification of batchno, memo, and comments
                $output .= '
                  <tr><td class="highlight">'.$field.'</td><td class="highlight"><input type="text" name="'.$field.'" value="'.$row->$field.'"></td></tr>';
              }
            elseif ($field == 'transaction_id')
              {
                // Do not set a value for transaction_id (because we will be inserting a new transaction)
                $output .= '
                  <tr><td class="normal">'.$field.'</td><td class="normal"><strike>'.$row->$field.'</strike> *</td></tr>';
              }
            elseif ($field == 'transaction_user')
              {
                // Change the transaction_user to the currently-logged-in user
                $output .= '
                  <tr><td class="normal">'.$field.'</td><td class="normal"><input type="hidden" name="'.$field.'" value="'.$_SESSION['member_id'].'"><strike>'.$row->$field.'</strike> '.$_SESSION['member_id'].' *</td></tr>';
              }
            elseif ($field == 'transaction_timestamp')
              {
                // Change the transaction_user to the currently-logged-in user
                $output .= '
                  <tr><td class="normal">'.$field.'</td><td class="normal"><input type="hidden" name="'.$field.'" value="'.date ('Y-m-d H:m:s').'"><strike>'.$row->$field.'</strike><br>'.date ('Y-m-d H:m:s').' *</td></tr>';
              }
            else
              {
                $output .= '
                  <tr><td class="normal">'.$field.'</td><td class="normal"><input type="hidden" name="'.$field.'" value="'.$row->$field.'">'.$row->$field.'</td></tr>';
              }
          }
        $output .= '
                  <tr>
                    <td align="center"><input type="reset"></td>
                    <td align="center"><input type="submit" name="action" value="Add Transaction"></td>
                  </tr>
                </table>
              </form>
          <p>* New transactions will have a different value for transaction_id.<br>
          * New transactions will be added as the current user.<br>
          * The current date/time will be used for the new transaction.</p>';
      }
    include("template_header.php");
    echo $output;
    include("template_footer.php");
    exit (1); // exit here so as not to show the main table
  }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

// Initialize variables
$show_only_errors = false;  // Set this value to show all baskets or only error-baskets
$member_array = array ();
$delivery_array = array ();

if ($_GET['page'] > 1)
  {
    $page = floor($_GET['page']);
  }
else
  {
    $page = 1;
  }

// Get a list of all the members to process
$query = '
  SELECT
    member_id
  FROM
    '.TABLE_MEMBER.'
  ORDER BY
    member_id';
$result = @mysql_query($query, $connection) or die("".mysql_error()."");
while ( $row = mysql_fetch_object($result) )
  {
    array_push ($member_array, $row->member_id);
  }

// Set up the page quicklinks
$low_index = 0;
$number_of_pages = ceil (count ($member_array) / PER_PAGE);
for ( $page_number = 1; $page_number <= $number_of_pages; $page_number ++ )
  {
    $low_index = PER_PAGE * ($page_number - 1);
    $high_index = $low_index + PER_PAGE - 1;
    if ($high_index >= count ($member_array))
      {
        $high_index = count ($member_array) - 1;
      }
    if ($page_number == $page)
      {
        $page_links .= '
          <span class="this_page">'.($member_array[$low_index]).' - '.($member_array[$high_index]).'</span> ';
      }
    else
      {
        $page_links .= '
          <a class="page_link" href="'.$_SERVER['PHP_SELF'].'?page='.$page_number.'">'.($member_array[$low_index]).' - '.($member_array[$high_index]).'</a> ';
        }
  }

$output .= '
      '.$message.'
      <table border="1">
      <caption><span class="page_link_text">Members... </span>'.$page_links.'</caption>';

// Cycle through all the PER_PAGE number of members in this group and compare results between transactions and basket data
$low_index = PER_PAGE * ($page - 1);
foreach (array_slice($member_array, $low_index, PER_PAGE) as $member_id)
  {
    unset ($delivery_array);
    $delivery_array = array();
    // Get a list of all used delivery_id values from the customer_basket_overall table
    $query = '
      SELECT
        DISTINCT delivery_id
      FROM
        '.TABLE_BASKET_ALL.'
      WHERE
        member_id = '.mysql_real_escape_string ($member_id).'
      ORDER BY
        delivery_id';
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    while ( $row = mysql_fetch_object($result) )
      {
        array_push ($delivery_array, $row->delivery_id);
      }

    // Clear variables from any prior iterations
    unset ($delivery_id, $basket_id, $surcharge_for_paypal, $grand_total, $amount_paid,
           $transaction_grand_total, $transaction_amount_paid, $transaction_surcharge_for_paypal,
           $transaction_coop_fee, $transaction_delivery_charge, $transaction_sales_tax, $transaction_basket_total,
           $edit_trans_ap, $sh, $delivery_cost, $collected_tax, $subtotal);
    $basket_id = array ();
    $surcharge_for_paypal = array ();
    $grand_total = array ();
    $amount_paid = array ();
    $transaction_grand_total = array ();
    $transaction_amount_paid = array ();
    $transaction_surcharge_for_paypal = array ();
    $transaction_coop_fee = array ();
    $transaction_delivery_charge = array ();
    $transaction_sales_tax = array ();
    $transaction_basket_total  = array ();
    $edit_trans_ap = array ();

    // Get the basket information for this member
    $query = '
      SELECT
        '.TABLE_BASKET_ALL.'.delivery_id,
        '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.subtotal,
        '.TABLE_BASKET_ALL.'.delivery_cost,
        '.TABLE_BASKET_ALL.'.sh,
        '.TABLE_BASKET_ALL.'.surcharge_for_paypal,
        '.TABLE_BASKET_ALL.'.grand_total,
        '.TABLE_BASKET_ALL.'.amount_paid,
        collected_citytax + collected_countytax + collected_statetax AS collected_tax
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN
        '.TABLE_CUSTOMER_SALESTAX.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_CUSTOMER_SALESTAX.'.basket_id
      WHERE
        '.TABLE_BASKET_ALL.'.member_id = '.mysql_real_escape_string ($member_id).'
      ORDER BY
        '.TABLE_BASKET_ALL.'.delivery_id';
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    while ( $row = mysql_fetch_object($result) )
      {
        $delivery_id = $row->delivery_id;
        $basket_id[$delivery_id] = $row->basket_id;
        $subtotal[$delivery_id] = $row->subtotal;
        $delivery_cost[$delivery_id] = $row->delivery_cost;
        $sh[$delivery_id] = $row->sh;
        $surcharge_for_paypal[$delivery_id] = $row->surcharge_for_paypal;
        $grand_total[$delivery_id] = $row->grand_total;
        $amount_paid[$delivery_id] = $row->amount_paid;
        $collected_tax[$delivery_id] = $row->collected_tax;
      }

    // Get the transaction data for this member
    $query = '
      SELECT
        transaction_id,
        transaction_basket_id AS basket_id,
        transaction_delivery_id AS delivery_id,
        transaction_type,
        transaction_amount
      FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_member_id = '.mysql_real_escape_string ($member_id).'
        AND
          (
               transaction_type = 34 /* Subtotal 2 */
            OR transaction_type = 23 /* Invoice Payment */
            OR transaction_type = 32 /* Handling Fee */

            OR transaction_type = 33 /* Delivery Charge */
            OR transaction_type = 30 /* Customer Coop Fee */
            OR transaction_type = 29 /* Sales Tax */
            OR transaction_type = 27 /* Basket Total */
          )
      ORDER BY
        delivery_id,
        transaction_id';
    $result = @mysql_query($query, $connection) or die("".mysql_error()."");
    while ( $row = mysql_fetch_object($result) )
      {
        $delivery_id = $row->delivery_id;
        $basket_id[$delivery_id] = $row->basket_id;
        // For finalized invoices, we take only the *last* value, so clobber the prior ones for this member/basket
        if ($row->transaction_type == 34)
          {
            $transaction_grand_total[$delivery_id] = $row->transaction_amount;
            $transaction_id_gt[$delivery_id] = $row->transaction_id;
          }
        // For the next line (payments) we sum all values (the only exception?)
        if ($row->transaction_type == 23)
          {
            $transaction_amount_paid[$delivery_id] = $transaction_amount_paid[$delivery_id] + $row->transaction_amount;
            $edit_trans_ap[$delivery_id] .= '<br><span class="small"><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$row->transaction_id.'&transaction_type=23&amp;basket_id='.$row->basket_id.'">Change:</a>&nbsp;'.$row->transaction_amount.'</span>';
          }
        if ($row->transaction_type == 32)
          {
            $transaction_surcharge_for_paypal[$delivery_id] = $row->transaction_amount;
            $transaction_id_pp[$delivery_id] = $row->transaction_id;
          }
        if ($row->transaction_type == 30)
          {
            $transaction_coop_fee[$delivery_id] = $row->transaction_amount;
            $transaction_id_sh[$delivery_id] = $row->transaction_id;
          }
        if ($row->transaction_type == 33)
          {
            $transaction_delivery_charge[$delivery_id] = $row->transaction_amount;
            $transaction_id_dc[$delivery_id] = $row->transaction_id;
          }
        if ($row->transaction_type == 29)
          {
            $transaction_sales_tax[$delivery_id] = $row->transaction_amount;
            $transaction_id_ct[$delivery_id] = $row->transaction_id;
          }
        if ($row->transaction_type == 27)
          {
            $transaction_basket_total[$delivery_id] = $row->transaction_amount;
            $transaction_id_bt[$delivery_id] = $row->transaction_id;
          }
      }


    $output .= '
        <tr class="h1">
          <th class="lg">Member ID</th>
          <th>Delivery ID</th>
          <th>Basket ID</th>
          <th class="lg rg" colspan="2">Grand Total</th>
          <th class="lg rg" colspan="2">Amount Paid</th>
          <th class="lg rg" colspan="2">Surcharge for Paypal</th>
          <th class="lg rg" colspan="2">'.ucfirst (ORGANIZATION_TYPE).' Fee</th>
          <th class="lg rg" colspan="2">Delivery Charge</th>
          <th class="lg rg" colspan="2">Sales Tax</th>
          <th class="lg rg" colspan="2">Basket Total</th>
        </tr>
        <tr class="h2">
          <th class="lg">'.$member_id.'</th>
          <th></th>
          <th></th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>

          <th class="lg">Basket</th>
          <th class="rg">Trans</th>
        </tr>
          ';
    foreach ($delivery_array as $delivery_id)
      {
        // Clear the error counter
        $error_count = 0;

        // Check for discrepancies between customer_baskets and transactions table
        if (number_format ((double)$grand_total[$delivery_id], 2) == number_format ((double)$transaction_grand_total[$delivery_id], 2))
          {
            $style_gt = 'normal';
            $edit_basket_gt = '';
            $edit_trans_gt = '';
          }
        else
          {
            $error_count++;
            $style_gt = 'highlight';
            $edit_basket_gt = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=grand_total" class="small">Edit Basket</a>';
            $edit_trans_gt = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$transaction_id_gt[$delivery_id].'&transaction_type=34&amp;basket_id='.$basket_id[$delivery_id].'" class="small">Change Trans.</a>';
          }

        // Check for cases where the transactions table is empty (these probably need to be refinalized)
        if (number_format ((double)$grand_total[$delivery_id], 2) != 0 && number_format ((double)$transaction_grand_total[$delivery_id], 2) == 0)
          {
            $transaction_grand_total[$delivery_id] = '[NULL]';
            $edit_trans_gt = ''; // There is no transaction to edit
          }

        if (number_format ((double)$amount_paid[$delivery_id], 2) == number_format ((double)$transaction_amount_paid[$delivery_id], 2))
          {
            $style_ap = 'normal';
            $edit_basket_ap = '';
            $edit_trans_ap[$delivery_id] = '';
          }
        else
          {
            $error_count++;
            $style_ap = 'highlight';
            $edit_basket_ap = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=amount_paid" class="small">Edit Basket</a>';
            // $edit_trans_ap[$delivery_id] = '<br><a href="'.$_SERVER['PHP_SELF'].'?action=edit_trans&amp;transaction_id='.$transaction_id_gt[$delivery_id].'&transaction_type=23" class="small">Change Trans.</a>';
          }

        if (number_format ((double)$surcharge_for_paypal[$delivery_id], 2) == number_format ((double)$transaction_surcharge_for_paypal[$delivery_id], 2))
          {
            $style_pp = 'normal';
            $edit_basket_pp = '';
            $edit_trans_pp = '';
          }
        else
          {
            $error_count++;
            $style_pp = 'highlight';
            $edit_basket_pp = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=surcharge_for_paypal" class="small">Edit Basket</a>';
            $edit_trans_pp = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$transaction_id_pp[$delivery_id].'&transaction_type=32&amp;basket_id='.$basket_id[$delivery_id].'" class="small">Change Trans.</a>';
          }

        if (number_format ((double)$sh[$delivery_id], 2) == number_format ((double)$transaction_coop_fee[$delivery_id], 2))
          {
            $style_sh = 'normal';
            $edit_basket_sh = '';
            $edit_trans_sh = '';
          }
        else
          {
            $error_count++;
            $style_sh = 'highlight';
            $edit_basket_sh = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=sh" class="small">Edit Basket</a>';
            $edit_trans_sh = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$transaction_id_sh[$delivery_id].'&transaction_type=30&amp;basket_id='.$basket_id[$delivery_id].'" class="small">Change Trans.</a>';
          }



        if (number_format ((double)$delivery_cost[$delivery_id], 2) == number_format ((double)$transaction_delivery_charge[$delivery_id], 2))
          {
            $style_dc = 'normal';
            $edit_basket_dc = '';
            $edit_trans_dc = '';
          }
        else
          {
            $error_count++;
            $style_dc = 'highlight';
            $edit_basket_dc = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=delivery_cost" class="small">Edit Basket</a>';
            $edit_trans_dc = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$transaction_id_dc[$delivery_id].'&transaction_type=33&amp;basket_id='.$basket_id[$delivery_id].'" class="small">Change Trans.</a>';
          }

        if (number_format ((double)$collected_tax[$delivery_id], 2) == number_format ((double)$transaction_sales_tax[$delivery_id], 2))
          {
            $style_ct = 'normal';
            $edit_basket_ct = '';
            $edit_trans_ct = '';
          }
        else
          {
            $error_count++;
            $style_ct = 'highlight';
            $edit_basket_ct = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=collected_tax" class="small">Edit Basket</a>';
            $edit_trans_ct = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$transaction_id_ct[$delivery_id].'&transaction_type=29&amp;basket_id='.$basket_id[$delivery_id].'" class="small">Change Trans.</a>';
          }

        if (number_format ((double)$subtotal[$delivery_id], 2) == number_format ((double)$transaction_basket_total[$delivery_id], 2))
          {
            $style_bt = 'normal';
            $edit_basket_bt = '';
            $edit_trans_bt = '';
          }
        else
          {
            $error_count++;
            $style_bt = 'highlight';
            $edit_basket_bt = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_basket&amp;basket_id='.$basket_id[$delivery_id].'&field=subtotal" class="small">Edit Basket</a>';
            $edit_trans_bt = '<br><a href="'.$_SERVER['PHP_SELF'].'?page='.$page.'&action=edit_trans&amp;transaction_id='.$transaction_id_bt[$delivery_id].'&transaction_type=27&amp;basket_id='.$basket_id[$delivery_id].'" class="small">Change Trans.</a>';
          }

        // If there was any error, give a link to the finalized and in-process invoice
        if ($error_count > 0 || $show_only_errors == false)
          {
            $fin_link_gt = '<a href="invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id[$delivery_id].'&member_id='.$member_id.'" class="small" target="_blank">Finalized&nbsp;Inv.</a>';
            $inproc_link_gt = '<br><a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id[$delivery_id].'&member_id='.$member_id.'" class="small" target="_blank">In-process&nbsp;Inv.</a>';
          }
        else
          {
            $fin_link_gt = '';
            $inproc_link_gt = '';
          }

        if ($error_count > 0 || $show_only_errors == false)
          {
            $output .= '
            <tr>
              <td class="lg">'.$fin_link_gt.$inproc_link_gt.'</td>
              <td>'.$delivery_id.'</td>
              <td id='.$basket_id[$delivery_id].'>'.$basket_id[$delivery_id].'</td>

              <td class="lg money '.$style_gt.'">'.number_format ((double)$grand_total[$delivery_id], 2).$edit_basket_gt.'</td>
              <td class="rg money '.$style_gt.'">'.number_format ((double)$transaction_grand_total[$delivery_id], 2).$edit_trans_gt.'</td>

              <td class="lg money '.$style_ap.'">'.number_format ((double)$amount_paid[$delivery_id], 2).$edit_basket_ap.'</td>
              <td class="rg money '.$style_ap.'">'.number_format ((double)$transaction_amount_paid[$delivery_id], 2).$edit_trans_ap[$delivery_id].'</td>

              <td class="lg money '.$style_pp.'">'.number_format ((double)$surcharge_for_paypal[$delivery_id], 2).$edit_basket_pp.'</td>
              <td class="rg money '.$style_pp.'">'.number_format ((double)$transaction_surcharge_for_paypal[$delivery_id], 2).$edit_trans_pp.'</td>

              <td class="lg money '.$style_sh.'">'.number_format ((double)$sh[$delivery_id], 2).$edit_basket_sh.'</td>
              <td class="rg money '.$style_sh.'">'.number_format ((double)$transaction_coop_fee[$delivery_id], 2).$edit_trans_sh.'</td>

              <td class="lg money '.$style_dc.'">'.number_format ((double)$delivery_cost[$delivery_id], 2).$edit_basket_dc.'</td>
              <td class="rg money '.$style_dc.'">'.number_format ((double)$transaction_delivery_charge[$delivery_id], 2).$edit_trans_dc.'</td>

              <td class="lg money '.$style_ct.'">'.number_format ((double)$collected_tax[$delivery_id], 2).$edit_basket_ct.'</td>
              <td class="rg money '.$style_ct.'">'.number_format ((double)$transaction_sales_tax[$delivery_id], 2).$edit_trans_ct.'</td>

              <td class="lg money '.$style_bt.'">'.number_format ((double)$subtotal[$delivery_id], 2).$edit_basket_bt.'</td>
              <td class="rg money '.$style_bt.'">'.number_format ((double)$transaction_basket_total[$delivery_id], 2).$edit_trans_bt.'</td>
            </tr>';
          }
      }
  }

$fontface='arial';

$content .= '
<font face="'.$fontface.'">
  '.$output.'
</table>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Accounting Check</span>';
$page_title = 'Admin Maintenance: Accounting Check';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
