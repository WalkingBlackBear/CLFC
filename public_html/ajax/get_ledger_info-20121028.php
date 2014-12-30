<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,site_admin,cashier');

include_once ('func.get_ledger_row_markup.php');

////////////////////////////////////////////////////////////////////////////////////
//                                                                                //
// This is the main ajax call to get ledger information for display of            //
// accounting information.                                                        //
// REQUIRED arguments:                       action=get_ledger_info               //
//                                     account_spec=[account_type]:[account_id]   //
//                                                                                //
// OPTIONAL arguments:      group_customer_fee_with=[product|order]               //
//                          group_producer_fee_with=[product|order]               //
//                           group_weight_cost_with=[product|order]               //
//                             group_quantity_cost_with=[product|order]               //
//                          group_extra_charge_with=[product|order]               //
//                                 group_taxes_with=[product|order]               //
//                                   include_header=[true|false]                  //
//                                      delivery_id=[delivery_id]                 //
//                                                                                //
////////////////////////////////////////////////////////////////////////////////////


// Create an array of text_key values and their associated short-forms
// The short-forms are used for CSS. These will be used as, e.g. prid (for group_id) and pridr (for individual row id)

// Set default values. Can be order or product:
$summarize_by = array (
  'adjustment'    => 'order', 
  'customer fee'  => 'order', 
  'producer fee'  => 'order', 
  'weight cost'   => 'product',
  'quantity cost'     => 'product',
  'extra charge'  => 'product',
  'tax'           => 'order'
  );

// Modify default values by the $_REQUEST
if ($_REQUEST['group_customer_fee_with'] == 'product') $summarize_by['customer fee'] = 'product';
if ($_REQUEST['group_producer_fee_with'] == 'product') $summarize_by['producer fee'] = 'product';
if ($_REQUEST['group_weight_cost_with'] == 'product') $summarize_by['weight cost'] = 'product';
if ($_REQUEST['group_quantity_cost_with'] == 'product') $summarize_by['quantity cost'] = 'product';
if ($_REQUEST['group_extra_charge_with'] == 'product') $summarize_by['extra charge'] = 'product';
if ($_REQUEST['group_taxes_with'] == 'product') $summarize_by['tax'] = 'product';

$css_trans_array = array (
  'adjustment'    => 'aj',
  'customer fee'  => 'cf', 
  'producer fee'  => 'pf', 
  'weight cost'   => 'pw', // Same key as "each" -- treat them as a single group
  'quantity cost'     => 'ps', // Same key as "weight" -- treat them  as a single group
  'extra charge'  => 'ec', 
  'tax'           => 'tx'
  );

// Initialize arrays
$static_balance = array();
$summarize = array();
$order_summary_count = array();
$order_summary = array();
$order_summary_singleton = array();
$order_item_count = array();
$order_product_count = array();

// If asked for just the header...
if ($_REQUEST['action'] == 'get_ledger_head')
  {
    $response = get_ledger_header_markup ();
    echo $response;
    exit (0);
  }

// If asked for the body content...
if ($_REQUEST['action'] == 'get_ledger_body')
  {
    // Get the ledger data for whatever account this is
    list($account_type, $account_id) = explode (':',$_REQUEST['account_spec']);
    // Because of the logic following the query, the ORDER BY clause ***MUST*** be
    // FIRST by delivery_id, and SECOND by product_id.
//     $query_order_by = 'delivery_id, basket_id, pvid, bpid';
    // Narrow the results, if requested
    if ($_REQUEST['delivery_id'])
      {
        $query_where = '
        delivery_id = "'.mysql_real_escape_string($_REQUEST['delivery_id']).'"';
      }
    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.transaction_id,
        '.NEW_TABLE_LEDGER.'.source_type,
        '.NEW_TABLE_LEDGER.'.source_key,
        '.NEW_TABLE_LEDGER.'.target_type,
        '.NEW_TABLE_LEDGER.'.target_key,
        '.NEW_TABLE_LEDGER.'.amount,
        '.NEW_TABLE_LEDGER.'.text_key,
        '.NEW_TABLE_LEDGER.'.posted_by,
        '.NEW_TABLE_LEDGER.'.timestamp,
        COALESCE(source_producer.business_name,"") AS source_business_name,
        COALESCE(target_producer.business_name,"") AS target_business_name,
        COALESCE(source_member.preferred_name,"") AS source_preferred_name,
        COALESCE(target_member.preferred_name,"") AS target_preferred_name,
        COALESCE(source_coa.account_number,"") AS source_account_number,
        COALESCE(target_coa.account_number,"") AS target_account_number,
        COALESCE(source_coa.description,"") AS source_description,
        COALESCE(target_coa.description,"") AS target_description,
        COALESCE(CONCAT_WS(" ", source_tax_rates.region_code, source_tax_rates.region_name, source_tax_rates.postal_code),"") AS source_tax_code,
        COALESCE(CONCAT_WS(" ", target_tax_rates.region_code, target_tax_rates.region_name, target_tax_rates.postal_code),"") AS target_tax_code,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.basket_id,0) AS basket_id,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.bpid, 0) AS bpid,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.quantity, 0) AS quantity,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.total_weight, "") AS total_weight,
        COALESCE('.NEW_TABLE_PRODUCTS.'.ordering_unit, "") AS ordering_unit,
        COALESCE('.NEW_TABLE_PRODUCTS.'.pricing_unit, "") AS pricing_unit,
        COALESCE('.NEW_TABLE_PRODUCTS.'.product_id, 0) AS product_id,
        COALESCE('.NEW_TABLE_PRODUCTS.'.product_name, "") AS product_name,
        '.NEW_TABLE_BASKETS.'.locked,
        '.NEW_TABLE_BASKETS.'.delcode_id,
        '.NEW_TABLE_BASKETS.'.delivery_id,
        COALESCE('.TABLE_ORDER_CYCLES.'.delivery_date, 0) AS delivery_date
      FROM
        '.NEW_TABLE_LEDGER.'
      /* Get the linked basket or basket_item table entries */
      LEFT JOIN
        '.NEW_TABLE_BASKETS.' ON
          '.NEW_TABLE_LEDGER.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
      LEFT JOIN
        '.NEW_TABLE_BASKET_ITEMS.' ON
          '.NEW_TABLE_LEDGER.'.bpid = '.NEW_TABLE_BASKET_ITEMS.'.bpid
      LEFT JOIN
        '.TABLE_ORDER_CYCLES.' ON
          '.NEW_TABLE_BASKETS.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
      LEFT JOIN
        '.NEW_TABLE_PRODUCTS.' ON
          '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id
      LEFT JOIN /* Source is a member */
        '.TABLE_MEMBER.' source_member ON
          (source_type = "member" AND source_key = source_member.member_id)
      LEFT JOIN /* Source is a producer */
        '.TABLE_PRODUCER.' source_producer ON
          (source_type = "producer" AND source_key = source_producer.producer_id)
      LEFT JOIN /* Source is internal */
        '.NEW_TABLE_CHART_OF_ACCOUNTS.' source_coa ON
          (source_key = source_coa.account_id)
      LEFT JOIN /* Source is tax */
        '.NEW_TABLE_TAX_RATES.' source_tax_rates ON
          (source_type = "tax" AND source_key = source_tax_rates.tax_id)
      LEFT JOIN /* Target is a member */
        '.TABLE_MEMBER.' target_member ON
          (target_type = "member" AND target_key = target_member.member_id)
      LEFT JOIN /* Target is a producer */
        '.TABLE_PRODUCER.' target_producer ON
          (target_type = "producer" AND target_key = target_producer.producer_id)
      LEFT JOIN /* Target is internal */
        '.NEW_TABLE_CHART_OF_ACCOUNTS.' target_coa ON
          (target_key = target_coa.account_id)
      LEFT JOIN /* Target is tax */
        '.NEW_TABLE_TAX_RATES.' target_tax_rates ON
          (target_type = "tax" AND target_key = target_tax_rates.tax_id)
      WHERE
        ((source_type = "'.mysql_real_escape_string($account_type).'"
            AND source_key = "'.mysql_real_escape_string($account_id).'")
          OR (target_type = "'.mysql_real_escape_string($account_type).'"
            AND target_key = "'.mysql_real_escape_string($account_id).'"))
        AND replaced_by IS NULL'.
        $query_where.'
      /* Not sure why we need this GROUP BY condition, but we do! */
      GROUP BY transaction_id
      ORDER BY
        '.NEW_TABLE_LEDGER.'.delivery_id,
        '.NEW_TABLE_LEDGER.'.delcode_id,
        '.NEW_TABLE_LEDGER.'.basket_id,
        '.NEW_TABLE_LEDGER.'.pvid,
        '.NEW_TABLE_LEDGER.'.bpid';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 869373 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    // Need to know how many rows were returned because we need to iterate ONE MORE THAN that
    // many times in order to capture summary functions (not pretty). This really should be
    // done with some kind of data object... -ROYG
    $number_of_rows = mysql_num_rows ($result);
    $amount = 0;
    while ($number_of_rows > 1 && $row_count ++ <= $number_of_rows) // Cycles ONCE MORE THAN $number_of_rows
      {
        if ($row_count <= $number_of_rows) // Last time through...
          {
            // Get a new row from the database
            $row = mysql_fetch_array($result);
          }
        else
          {
            // Don't change the $row array, but *DO CHANGE* a few of the values
            // in order to force the product and order summary functions to trigger
            $row['delivery_id'] = 0;
            $row['product_id'] = 0;
          }
        // Clear the assigned variables
        $other_account_type = '';
        $other_account_key = '';
        $display_to_from = '';
        $display_quantity = '';
        $detail = '';
        // $balance = 0; // this is the running total, so leave it alone!
        $display_balance = '';
          // VARIABLES RETURNED FROM QUERY: $row[...]
          // source_type,             target_preferred_name,    text_key,              product_name,
          // source_key,              source_account_number,    posted_by,             basket_id,
          // target_type,             target_account_number,    timestamp,             delcode_id
          // target_key,              source_description,       quantity,
          // amount,                  target_description,       total_weight,
          // source_business_name,    source_tax_code,          ordering_unit,
          // target_business_name,    target_tax_code,          pricing_unit,
          // source_preferred_name,   transaction_id,           product_id,

        // Check if this account is the SOURCE account
        if ($row['source_type'] == $account_type && $row['source_key'] == $account_id)
          {
            $other_account_type = $row['target_type'];
            $other_account_key = $row['target_key'];
            $other_account = 'target';
            $amount = 0 - $row['amount']; // Invert the sense of payments *from* the account
          }
        // Otherwise this account is the TARGET account
        elseif ($row['target_type'] == $account_type && $row['target_key'] == $account_id)
          {
            $other_account_type = $row['source_type'];
            $other_account_key = $row['source_key'];
            $other_account = 'source';
            $amount = $row['amount'];
          }
        else
          {
            // TO and FROM the same account... should not happen
            die(debug_print ("error 203: ", "could not determine disposition of transaction".print_r($row,true), basename(__FILE__).' LINE '.__LINE__));
          }
        $balance += $amount;
        if ($other_account_type == 'producer') $display_to_from = 'Producer #'.$other_account_key.': '.$row[$other_account.'_business_name'];
        elseif ($other_account_type == 'member') $display_to_from = 'Member #'.$other_account_key.': '.$row[$other_account.'_preferred_name'];
        elseif ($other_account_type == 'internal') $display_to_from = INTERNAL_DESIGNATION.': '.$row[$other_account.'_account_number'].' ('.$row[$other_account.'_description'].')';
        elseif ($other_account_type == 'tax') $display_to_from = 'Tax: '.$row[$other_account.'_tax_code'];
        else $display_to_from = '['.$other_account_type.'::'.$other_account_key.']';
        if ($row['text_key'] == 'weight cost')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'].' @ '.($row['total_weight'] + 0).' '.$row['pricing_unit'];
          }
        elseif ($row['text_key'] == 'quantity cost')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'];
          }
        elseif ($row['text_key'] == 'extra charge')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'];
          }
        if ($row['product_id']) $detail = '(#'.$row['product_id'].') '.$row['product_name'];
        elseif ($row['delcode_id']) $detail = $row['delcode_id'].' basket';

        // Set up an array of display_data for display output
        $transaction_current['transaction_id']      = $row['transaction_id'];
        $transaction_current['other_account_type']  = $other_account_type;
        $transaction_current['other_account_key']   = $other_account_key;
        $transaction_current['basket_id']           = $row['basket_id'];
        $transaction_current['timestamp']           = $row['timestamp'];
        $transaction_current['bpid']                = $row['bpid'];
        $transaction_current['product_id']          = $row['product_id'];
        $transaction_current['display_to_from']     = $display_to_from;
        $transaction_current['text_key']            = $row['text_key'];
        $text_key                                   = $row['text_key'];
        $transaction_current['source_type']         = $row['source_type'];
        $transaction_current['source_key']          = $row['source_key'];
        $transaction_current['target_type']         = $row['target_type'];
        $transaction_current['target_key']          = $row['target_key'];
        $transaction_current['quantity']            = $row['quantity'];
        $transaction_current['locked']              = $row['locked'];
        $transaction_current['display_quantity']    = $display_quantity;
        $transaction_current['detail']              = $detail;
        $transaction_current['amount']              = $amount;
        $transaction_current['balance']             = $balance;
        $transaction_current['delivery_id']         = $row['delivery_id'];
        $transaction_current['delcode_id']          = $row['delcode_id'];
        $transaction_current['delivery_date']       = $row['delivery_date'];
//        $transaction_current['detail_group']        = $detail_group;


// NEED TO CHECK FOR LAST-PASS AND DO SUMMARY ROWS IN THAT EVENT

// Make sure everything still works if transactions are scattered in the database ordering


        // Check the summary row items first because... if they are true (i.e. needing to summarize)
        // then they will pull prior row information and reset the row. THEN we can add new row
        // information without any worry about clobbering it.

        // Check if we have moved on to a new product (or new customer, even if the same product),
        // then do the product summary stuff...
        if ($transaction_current['product_id'] && // it is a product
          ($transaction_current['product_id'] != $transaction_prior['product_id'] || // it is a changed product
          $transaction_current['basket_id'] != $transaction_prior['basket_id']) && // ... or a changed member
          $transaction_prior['product_id']) // and there was a prior product (to prevent false headers)
          {
            // If there were summary items, display them
            if ($product_summary_count  > 0)
              {
                // If there is only ONE row to summarize, then we will use the singleton value
                // instead of a summary row pointing to it.
                if ($product_summary_count == 1)
                  {
                    $response .= $product_summary_singleton;
                  }
                else
                  {
                    // Add the other detail rows we have saved up
                    $response .= $product_summary;
                    // Then add the current (header/summary row)
                    $response .= get_ledger_row_markup (array(
                      'unique_row_id' => 'gid_'.$transaction_prior['bpid'], // This is used for the CSS id
                      'display_class' => '',
                      'display_scope' => $transaction_prior['basket_id'],
                      'timestamp' => '---'.$counter,
                      'display_to_from' => $static_to_from,
                      'text_key' => '---',
                      'display_quantity' => $static_quantity,
                      'detail' => $transaction_prior['detail'],
                      'detail_group' => 'gidr_'.$transaction_prior['bpid'],
                      'amount' => $product_balance,
                      'css_class' => 'product_summary '),
                      $transaction_prior['balance'],
                    'summary');
                  }
              }
            // And clear values that are completed at this point
            $static_item_count = 0;
            $static_product_count = 0;
            $product_summary = '';
            $product_summary_singleton = '';
            $static_detail = '';
            $product_balance = 0;
            $product_summary_count = 0;
          }
        // Check if we have moved on to a new delivery, then do the basket summary stuff...


if ($amount == -5.16) $response .= '<tr><td colspan="9">AMOUNT IS $5.16 FOR TRANSACTION_ID:'.$transaction_current['transaction_id'].'</td></tr>';


        if ($transaction_current['delivery_id'] != $transaction_prior['delivery_id'])
          {
            // Display a summary row for each of the order summary items
            foreach (array_keys ($summarize_by, 'order') as $this_text_key)
              {
                // Correct the balances by adding the summary balances back in...
                $transaction_current['balance'] += $order_balance[$this_text_key];
                $transaction_prior['balance'] += $order_balance[$this_text_key];
                $balance = $transaction_current['balance'];
                // If there is only ONE row to summarize, then we will use the singleton value
                // instead of a summary row pointing to it.
                if ($order_summary_count[$this_text_key] == 1)
                  {
                    // We used an unusual placeholder earlier so we could replace it now with the current balance
                    $response .= str_replace ('{([T][B][D])}', number_format ($transaction_current['balance'], 2), $order_summary_singleton[$this_text_key]);
                  }
                elseif ($order_summary_count[$this_text_key] > 1)
                  {
                    // Add the other detail rows we have saved up BEFORE the summary line
                    $response .= $order_summary[$this_text_key];
                    // Then add the summary row that totals them all
                    $response .= get_ledger_row_markup (array(
                      'unique_row_id' => $css_trans_array[$this_text_key].'id_'.$transaction_prior['bpid'], // This is used for the CSS id
                      'display_class' => '',
                      'display_scope' => $transaction_prior['basket_id'],
                      'timestamp' => '---',
                      'display_to_from' => '---',
                      'text_key' => $this_text_key,
                      'display_quantity' => $order_product_count[$this_text_key].' products ('.$order_item_count[$this_text_key].' items)',
                      'detail' => 'Total '.$this_text_key.' for order '.$transaction_prior['delivery_id'].' ('.$transaction_prior['delivery_date'].')',
                      'detail_group' => $css_trans_array[$this_text_key].'idr_'.$transaction_prior['delivery_id'],
                      'amount' => $order_balance[$this_text_key],
                      'css_class' => 'order_summary '),
                    $transaction_prior['balance'],
                    'summary');
                  }
                // ELSE the $order_summary_count[$this_text_key] <= 0 (nothing to do)
                // Clear values that are completed at this point
                $order_item_count[$this_text_key] = 0;
                $order_product_count[$this_text_key] = 0;
                $order_summary[$this_text_key] = '';
                $order_summary_singleton[$this_text_key] = '';
                $order_balance[$this_text_key] = 0;
                $order_summary_count[$this_text_key] = 0;
              }
            // Finally, send a row-separator
            $response .= '
              <tr class="row_sep"><td colspan="10"></td></tr>';
          }
        // Check if text_key is configured for summary at the product level
        if ($summarize_by[$text_key] == 'product')
          {
            // Set to access the entire group by CSS with class = gidr_[bpid]
            // gidr is used for product-level groupings
            $transaction_current['display_class'] = 'gidr_'.$transaction_current['bpid'];
            // display_scope is just some possibly-useful information to display about this row
            $transaction_current['display_scope'] = $transaction_current['basket_id'];
            // Capture all this group's rows into $product_summary
            $product_summary .= get_ledger_row_markup ($transaction_current, '', 'detail');
            // Set a css_class for display
            $transaction_current['display_class'] = 'product_summary '; // treat like product summary since it sorts with them
            // If there ends up being only ONE product in the summary, then we will use it INSTEAD OF the summary row
            $product_summary_singleton .= get_ledger_row_markup ($transaction_current, $transaction_current['balance'], 'singleton');
            // Keep track of a monetary balance on this key
            $product_balance += $amount;
            // Keep track of how many summary items are being represented
            $product_summary_count ++;
            // Probably will want these next two ONLY for product costs (i.e. key_id = 'pr')
              $static_item_count += $transaction_current['quantity']; // Number of individual items in the order
              $static_product_count += 1; // Number of distinct products in the order
            // ALSO probably will want these next three ONLY for product costs (i.e. key_id = 'pr')
              $static_to_from = $transaction_current['display_to_from'];
              $static_quantity = $transaction_current['display_quantity'];
              $static_detail = $transaction_current['detail'];
            // Set some flags
            $summarize_by_product = true;
            $transaction_row_okay_to_push = true;
          }
        // Check if text_key is configured for sumarizing at the order level
        elseif ($summarize_by[$text_key] == 'order')
          {
            // Set to access the entire group by CSS with class = [text_key_id]idr_[bpid]
            $transaction_current['display_class'] = $css_trans_array[$text_key].'idr_'.$transaction_current['delivery_id'];
            // display_scope is just some possibly-useful information to display about this row
            $transaction_current['display_scope'] = $transaction_current['basket_id'];
            // Capture all this group's rows for the entire order into a summary based on the key_id
            $order_summary[$text_key] .= get_ledger_row_markup ($transaction_current, '', 'detail');
            // If there ends up being only ONE product in the summary, then we will use it INSTEAD OF the summary row
            // Set a css_class for display
            $transaction_current['display_class'] = 'order_summary ';
            // Since we do not yet know the (running total) balance, we will use an unlikely (but not impossible)
            // replacement string as a placeholder. This is bad form, but will work 99.99+% of the time.
            $order_summary_singleton[$text_key] .= get_ledger_row_markup ($transaction_current, '{([T][B][D])}', 'singleton');
            // Keep track of a monetary balance on this key
            $order_balance[$text_key] += $amount;
            // Since we will handle the balance at the order-level, put it back the way it was before this transaction
            $balance -= $amount;
            $transaction_current['balance'] = $balance; // Keep this current
            // Keep track of how many summary items are being represented
            $order_summary_count[$text_key] ++;
            // Probably will want these next two ONLY for product costs (i.e. key_id = 'pr')
              $order_item_count[$text_key] += $transaction_current['quantity'];
              $order_product_count[$text_key] += 1;
            // Set some flags
            $summarize_by_delivery = true;
            $transaction_row_okay_to_push = true;
          }
        // Else no summary was requested for [this_text_key], so just send it to the results
        else
          {
            $response .= get_ledger_row_markup ($transaction_current, $transaction_current['balance'], 'normal');
          }
        // Push the current transaction data into transaction_prior
        $transaction_prior = $transaction_current;
      }
    if (! $number_of_rows)
      {
        $response = 'No information to display';
      };
    echo $response;
    exit(0);
  }
?>