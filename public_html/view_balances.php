<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,site_admin,cashier');

if ($_REQUEST['action'] == 'get_account_hint')
  {
    if ($_REQUEST['query'])
      {
        $escaped_search = mysql_real_escape_string($_REQUEST['query']);
        $combined_accounts = array ();
        // Queue up all the queries and run them in parallel to intermix the results... then sort.
        $query_producer = '
          SELECT
            producer_id,
            preferred_name,
            first_name,
            last_name,
            first_name_2,
            last_name_2,
            '.TABLE_PRODUCER.'.business_name
          FROM
            '.TABLE_PRODUCER.'
          LEFT JOIN '.TABLE_MEMBER.' USING (member_id)
          WHERE
            '.TABLE_PRODUCER.'.business_name LIKE "%'.$escaped_search.'%"
            OR preferred_name LIKE "%'.$escaped_search.'%"
            OR CONCAT_WS(" ", first_name, last_name, first_name_2, last_name_2) LIKE "%'.$escaped_search.'%"
            OR CONCAT("p", producer_id) = "'.$escaped_search.'" /* Like producer:64 */
          ORDER BY '.TABLE_PRODUCER.'.business_name, last_name
          LIMIT 20';
        $query_member = '
          SELECT
            member_id,
            preferred_name,
            first_name,
            last_name,
            first_name_2,
            last_name_2,
            business_name
          FROM
            '.TABLE_MEMBER.'
          WHERE
            preferred_name LIKE "%'.$escaped_search.'%"
            OR CONCAT_WS(" ", first_name, last_name, first_name_2, last_name_2) LIKE "%'.$escaped_search.'%"
            OR business_name LIKE "%'.$escaped_search.'%"
            OR CONCAT("m", member_id) = "'.$escaped_search.'" /* Like member:64 */
          ORDER BY preferred_name, last_name
          LIMIT 20';
        $query_tax = '
          SELECT
            tax_id,
            region_code,
            region_name,
            postal_code
          FROM
            '.NEW_TABLE_TAX_RATES.'
          WHERE
            region_code LIKE "%'.$escaped_search.'%"
            OR region_name LIKE "%'.$escaped_search.'%"
            OR postal_code LIKE "%'.$escaped_search.'%"
          ORDER BY region_name, postal_code
          LIMIT 20';
        $query_internal = '
          SELECT
            account_id,
            account_number,
            sub_account_number,
            description
          FROM
            '.NEW_TABLE_CHART_OF_ACCOUNTS.'
          WHERE
            CONCAT_WS(" ", account_number, sub_account_number) LIKE "%'.$escaped_search.'%"
            OR description LIKE "%'.$escaped_search.'%"
          ORDER BY account_number
          LIMIT 20';
        $result_producer = @mysql_query($query_producer, $connection) or die(debug_print ("ERROR: 869373 ", array ($query_producer,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_producers = mysql_num_rows($result_producer);
        $result_member = @mysql_query($query_member, $connection) or die(debug_print ("ERROR: 027325 ", array ($query_member,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_members = mysql_num_rows($result_member);
        $result_tax = @mysql_query($query_tax, $connection) or die(debug_print ("ERROR: 896274 ", array ($query_tax,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_taxes = mysql_num_rows($result_tax);
        $result_internal = @mysql_query($query_internal, $connection) or die(debug_print ("ERROR: 893582 ", array ($query_internal,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//        $how_many_internals = mysql_num_rows($result_internal);
        $wait_for_all_four = 0;
        // Query producers, members, taxes, internal accounts one-at-a-time until there are either a total
        // of 20 results or all four come up empty.
        while (count($combined_accounts) < 20 && $wait_for_all_four < 4)
          {
            if ($row_producer = mysql_fetch_array($result_producer))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $first_last = $row_member['first_name'].' '.$row_member['last_name'];
                $first_last2 = $row_member['first_name_2'].' '.$row_member['last_name_2'];
                if (stripos(' '.$row_producer['business_name'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = 'Producer '.$row_producer['producer_id'].': '.$row_producer['business_name'].' / '.$row_producer['preferred_name'];
                elseif (stripos(' '.$row_producer['preferred_name'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = 'Producer '.$row_producer['producer_id'].': '.$row_producer['business_name'].' / '.$row_producer['preferred_name'];
                elseif (stripos(' '.$first_last, $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = 'Producer '.$row_producer['producer_id'].': '.$row_producer['business_name'].' / '.$row_producer['preferred_name'];
                elseif (stripos(' '.$first_last2, $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = 'Producer '.$row_producer['producer_id'].': '.$row_producer['business_name'].' / '.$row_producer['preferred_name'];
                elseif (stripos(' p'.$row_producer['producer_id'], $_REQUEST['query'])) $combined_accounts['producer:'.$row_producer['producer_id']] = 'Producer '.$row_producer['producer_id'].': '.$row_producer['business_name'].' / '.$row_producer['preferred_name'];
              }
            else
              {
                $wait_for_producer = 1;
              }
            if ($row_member = mysql_fetch_array($result_member))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $first_last = $row_member['first_name'].' '.$row_member['last_name'];
                $first_last2 = $row_member['first_name_2'].' '.$row_member['last_name_2'];
                if (stripos(' '.$row_member['preferred_name'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = 'Member '.$row_member['member_id'].': '.$row_member['preferred_name'];
                elseif (stripos(' '.$first_last, $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = 'Member '.$row_member['member_id'].': '.$first_last.' / '.$first_last2;
                elseif (stripos(' '.$first_last2, $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = 'Member '.$row_member['member_id'].': '.$first_last.' / '.$first_last2;
                elseif (stripos(' '.$row_member['business_name'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = 'Member '.$row_member['member_id'].': '.$row_member['preferred_name'].' / '.$row_member['business_name'];
                elseif (stripos(' m'.$row_member['member_id'], $_REQUEST['query'])) $combined_accounts['member:'.$row_member['member_id']] = 'Member '.$row_member['member_id'].': '.$row_member['preferred_name'].' / '.$row_member['business_name'];
              }
            else
              {
                $wait_for_member = 1;
              }
            if ($row_tax = mysql_fetch_array($result_tax))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $tax_description = $row_tax['region_name'].' ('.$row_tax['postal_code'].') '.$row_tax['region_code'].'';
                if (stripos(' '.$row_tax['region_name'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = 'Tax: '.$tax_description;
                elseif (stripos(' '.$row_tax['postal_code'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = 'Tax: '.$tax_description;
                elseif (stripos(' '.$row_tax['region_code'], $_REQUEST['query'])) $combined_accounts['tax:'.$row_tax['tax_id']] = 'Tax: '.$tax_description;
              }
            else
              {
                $wait_for_tax = 1;
              }
            if ($row_internal = mysql_fetch_array($result_internal))
              {
                // Check a few of the combinations and save the first one that matches
                // The ' ' [space] is prepended to return true without consideration of a '0' [zero] return position
                $account_description = $row_member['account_number'].' / '.$row_member['sub_account_number'].' / '.$row_internal['description'];
                if (stripos(' '.$row_internal['description'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = 'Internal: '.$account_description;
                elseif (stripos(' '.$row_internal['account_number'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = 'Internal: '.$account_description;
                elseif (stripos(' '.$row_internal['sub_account_number'], $_REQUEST['query'])) $combined_accounts['internal:'.$row_internal['account_id']] = 'Internal: '.$account_description;
              }
            else
              {
                $wait_for_internal = 1;
              }
            $wait_for_all_four = $wait_for_producer + $wait_for_member + $wait_for_tax + $wait_for_internal;
          }
        // Now sort and return the final values...
        asort($combined_accounts);
        $response = '{
          query:"'.$_REQUEST['query'].'",
          suggestions:["'.implode ('","', array_values ($combined_accounts)).'"],
          data:["'.implode ('","', array_keys ($combined_accounts)).'"]
          }';
        echo $response;
        exit (0);
      }
    else
      {
        $response = '{
          query:"'.$_REQUEST['query'].'",
          suggestions:["Garbled Query"],
          data:["no result"]
          }';
        echo $response;
        exit (0);
      }
  }
elseif ($_REQUEST['action'] == 'get_account_info')
  {
    // Get the ledger data for whatever account this is
    list($account_type, $account_id) = explode (':',$_REQUEST['account_spec']);

// Here are some "canned" views for the data
// $group_basket = array('producer fee', 'customer fee', 'tax');
// $group_product = array('extra charge', 'each cost', 'weight cost');
// $show_deleted = false;

$query_order_by = 'delivery_id, transaction_group';
$group_similar_products = true;

    $query = '
      SELECT
        '.NEW_TABLE_LEDGER.'.transaction_id,
        '.NEW_TABLE_LEDGER.'.source_type,
        '.NEW_TABLE_LEDGER.'.source_key,
        '.NEW_TABLE_LEDGER.'.target_type,
        '.NEW_TABLE_LEDGER.'.target_key,
        '.NEW_TABLE_LEDGER.'.amount,
        '.NEW_TABLE_LEDGER.'.text_key,
        '.NEW_TABLE_LEDGER.'.effective_datetime,
        '.NEW_TABLE_LEDGER.'.transaction_group,
        '.NEW_TABLE_LEDGER.'.post_by_member_id,
        '.NEW_TABLE_LEDGER.'.timestamp,
      /* Fields from internal accounts */
        COALESCE(source_producer.business_name,"") AS source_business_name,
        COALESCE(target_producer.business_name,"") AS target_business_name,
      /* Fields from internal accounts */
        COALESCE(source_member.preferred_name,"") AS source_preferred_name,
        COALESCE(target_member.preferred_name,"") AS target_preferred_name,
      /* Fields from internal accounts */
        COALESCE(source_coa.account_number,"") AS source_account_number,
        COALESCE(target_coa.account_number,"") AS target_account_number,
        COALESCE(source_coa.description,"") AS source_description,
        COALESCE(target_coa.description,"") AS target_description,
      /* Fields from tax accounts */
        COALESCE(CONCAT_WS(" ", source_tax_rates.region_code, source_tax_rates.region_name, source_tax_rates.postal_code),"") AS source_tax_code,
        COALESCE(CONCAT_WS(" ", target_tax_rates.region_code, target_tax_rates.region_name, target_tax_rates.postal_code),"") AS target_tax_code,
      /* Conditional baskets */
        COALESCE(baskets1.basket_id,'.NEW_TABLE_BASKET_ITEMS.'.basket_id,0) AS basket_id,
      /* Conditional basket_items */
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.quantity, 0) AS quantity,
        COALESCE('.NEW_TABLE_BASKET_ITEMS.'.total_weight, "") AS total_weight,
        COALESCE('.NEW_TABLE_PRODUCTS.'.ordering_unit, "") AS ordering_unit,
        COALESCE('.NEW_TABLE_PRODUCTS.'.pricing_unit, "") AS pricing_unit,
        COALESCE('.NEW_TABLE_PRODUCTS.'.product_id, 0) AS product_id,
        COALESCE('.NEW_TABLE_PRODUCTS.'.product_name, "") AS product_name,
        COALESCE(baskets1.delcode_id, baskets2.delcode_id) AS delcode_id,
        COALESCE(baskets1.delivery_id, baskets2.delivery_id) AS delivery_id,
        COALESCE('.TABLE_ORDER_CYCLES.'.delivery_date, 0) AS delivery_date
      FROM
        '.NEW_TABLE_LEDGER.'
      /* Get the linked basket or basket_item table entries */
      LEFT JOIN
        '.NEW_TABLE_BASKETS.' baskets1 ON
          (referenced_table = "baskets" AND referenced_key = baskets1.basket_id)
      LEFT JOIN
        '.NEW_TABLE_BASKET_ITEMS.' ON
          (referenced_table = "basket_items" AND referenced_key = '.NEW_TABLE_BASKET_ITEMS.'.bpid)
      LEFT JOIN
        '.NEW_TABLE_BASKETS.' baskets2 ON
          ('.NEW_TABLE_BASKET_ITEMS.'.basket_id = baskets2.basket_id)
      LEFT JOIN
        '.TABLE_ORDER_CYCLES.' ON
          (baskets1.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
          OR baskets2.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id)
      LEFT JOIN
        '.NEW_TABLE_PRODUCTS.' ON
          ('.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id)
      LEFT JOIN /* Source is a member */
        '.TABLE_MEMBER.' source_member ON
          (source_type = "member" AND source_key = source_member.member_id)
      LEFT JOIN /* Source is a producer */
        producers source_producer ON
          (source_type = "producer" AND source_key = source_producer.producer_id)
      LEFT JOIN /* Source is internal */
        '.NEW_TABLE_CHART_OF_ACCOUNTS.' source_coa ON
          (source_type = "internal" AND source_key = source_coa.account_id)
      LEFT JOIN /* Source is tax */
        '.NEW_TABLE_TAX_RATES.' source_tax_rates ON
          (source_type = "tax" AND source_key = source_tax_rates.tax_id)
      LEFT JOIN /* Target is a member */
        '.TABLE_MEMBER.' target_member ON
          (target_type = "member" AND target_key = target_member.member_id)
      LEFT JOIN /* Target is a producer */
        producers target_producer ON
          (target_type = "producer" AND target_key = target_producer.producer_id)
      LEFT JOIN /* Target is internal */
        '.NEW_TABLE_CHART_OF_ACCOUNTS.' target_coa ON
          (target_type = "internal" AND target_key = target_coa.account_id)
      LEFT JOIN /* Target is tax */
        '.NEW_TABLE_TAX_RATES.' target_tax_rates ON
          (target_type = "tax" AND target_key = target_tax_rates.tax_id)
      WHERE
        ((source_type = "'.mysql_real_escape_string($account_type).'"
          AND source_key = "'.mysql_real_escape_string($account_id).'")
          OR (target_type = "'.mysql_real_escape_string($account_type).'"
            AND target_key = "'.mysql_real_escape_string($account_id).'"))
        AND replaced_by_transaction = 0
      /* Not sure why we need this GROUP BY condition, but we do! */
      GROUP BY transaction_id
      ORDER BY '.$query_order_by;
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 869373 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    $amount = 0;
    $response = '
        <tr id="pre_insertion_point">
          <td colspan="9">
        </tr>';
    while ($row = mysql_fetch_array($result))
      {
        // Clear the assigned variables
        $other_account_type = '';
        $other_account_key = '';
        $display_to_from = '';
        $display_quantity = '';
        $detail = '';
        // $balance = 0; // this is the running total, so leave it alone!
        $display_balance = '';
          // VARIABLES RETURNED FROM QUERY: $row[...]
          // source_type,
          // source_key,
          // target_type,
          // target_key,
          // amount,
          // /* Fields from internal accounts */
          // source_business_name,
          // target_business_name,
          // /* Fields from internal accounts */
          // source_preferred_name,
          // target_preferred_name,
          // /* Fields from internal accounts */
          // source_account_number,
          // target_account_number,
          // source_description,
          // target_description,
          // /* Fields from tax accounts */
          // source_tax_code,
          // target_tax_code,
        // This account is the SOURCE account
        if ($row['source_type'] == $account_type && $row['source_key'] == $account_id)
          {
            $other_account_type = $row['target_type'];
            $other_account_key = $row['target_key'];
            $other_account = 'target';
            $amount = 0 - $row['amount']; // Invert the sense of payments *from* the account
          }
        // This account is the TARGET account
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
          // ADDITIONAL VARIABLES RETURNED FROM QUERY: $row[...] (MOSTLY FOR DESCRIPTIVE INFORMATION)
          // transaction_id,
          // text_key,
          // post_by_member_id, NOT YET USED
          // timestamp, NOT YET USED
          // /* Conditional basket_items */
          // quantity,
          // total_weight,
          // ordering_unit,
          // pricing_unit,
          // product_id,
          // product_name,
          // /* Conditional baskets */
          // basket_id,
          // delcode_id
        if ($row['text_key'] == 'weight cost')
          {
            $display_quantity = $row['quantity'].' '.$row['ordering_unit'].' @ '.($row['total_weight'] + 0).' '.$row['pricing_unit'];
          }
        elseif ($row['text_key'] == 'each cost')
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
        $transaction_current['transaction_id']     = $row['transaction_id'];
        $transaction_current['other_account_type'] = $other_account_type;
        $transaction_current['other_account_key']  = $other_account_key;
        $transaction_current['transaction_group']  = $row['transaction_group'];
        $transaction_current['basket_id']          = $row['basket_id'];
        $transaction_current['timestamp']          = $row['transaction_id'];                    // ??????????????????????????
        $transaction_current['product_id']         = $row['product_id'];
        $transaction_current['display_to_from']    = $display_to_from;
        $transaction_current['text_key']           = $row['text_key'];
        $transaction_current['quantity']           = $row['quantity'];
        $transaction_current['display_quantity']   = $display_quantity;
        $transaction_current['detail']             = $detail;
        $transaction_current['amount']             = $amount;
        $transaction_current['balance']            = $balance;
        $transaction_current['delivery_id']        = $row['delivery_id'];
        $transaction_current['delcode_id']         = $row['delcode_id'];
        $transaction_current['delivery_date']      = $row['delivery_date'];
        // Set a CSS class for displaying this element
        // Include the detail_group class and the show/hide class
        $transaction_current['display_class']    = 'show';
        $transaction_current['detail_group'] = $detail_group;

$summarize_item_cost = 'by_delivery';
$summarize_item_cost = 'by_product';

$summarize_extra_charge = 'by_delivery';
//$summarize_extra_charge = 'by_product';

$summarize_producer_fee = 'by_delivery';
//$summarize_producer_fee = 'by_product';

$summarize_taxes = 'by_delivery';
//$summarize_taxes = 'by_product';

$summarize_customer_fee = 'by_delivery';
//$summarize_customer_fee = 'by_product';

// echo "<pre>".print_r($transaction_current,true)."</pre>";

//echo "<pre> (".$transaction_current['transaction_id'].")  Product:".$transaction_current['product_id']."(".$transaction_current['text_key'].")     Amount:".$transaction_current['amount']."       Total:".$transaction_current['balance']."</pre>";





// NEED TO CHECK FOR LAST-PASS AND DO SUMMARY ROWS IN THAT EVENT





        // If it is product related...
        if ($transaction_current['product_id'])
          {
            // Check if this row is beginning a new product
            if ($summarize_by_product == true && 
              ($transaction_current['product_id'] != $transaction_prior['product_id'] || // Product changed
              $transaction_current['basket_id'] != $transaction_prior['basket_id']) && // Or member changed
              $transaction_prior['product_id']) // And there was a prior product (prevent false headers)
              {


// If there is only one item, then just display that instead of the summary.


                // Add the other detail rows we have saved up
                $response .= $product_summary;
                // Then add the current (header/summary row)
                $response .= prep_for_display (array(
                  'transaction_id' => 'gid_'.$transaction_prior['transaction_group'], // This is used for the CSS id
                  'display_class' => '',
                  'scope' => 'Product #'.$transaction_prior['product_id'],
                  'timestamp' => '--',
                  'display_to_from' => $static_to_from,
                  'text_key' => '--',
                  'display_quantity' => $static_quantity,
                  'detail' => $transaction_prior['detail'],
                  'detail_group' => 'gidr_'.$transaction_prior['transaction_group'],
                  'amount' => $static_product_cost),
                  $transaction_prior['balance']);
                // And free up the memory
                $static_item_count = 0;
                $static_product_count = 0;
                $product_summary = '';
                $static_detail = '';
                $static_product_cost = 0;
              }
          }
        // If we have moved on to a new delivery, then do the basket summary stuff...
        if ($summarize_by_delivery &&
          $transaction_current['delivery_id'] != $transaction_prior['delivery_id'])
          {
            // Display a summary row for each of the basket_summary items
            if ($summarize_item_cost == 'by_delivery')
              {
                // Correct the charges by adding the fees back in...
                $transaction_current['balance'] += $order_item_cost_balance;
                $transaction_prior['balance'] += $order_item_cost_balance;
                $balance = $transaction_current['balance'];
                // Add the other detail rows we have saved up
                $response .= $order_item_cost_summary;
                // Then add the current (header/summary row)
                $response .= prep_for_display (array(
                  'transaction_id' => 'prid_'.$transaction_prior['transaction_group'], // This is used for the CSS id
                  'display_class' => '',
                  'scope' => 'Delivery #'.$transaction_current['delivery_id'],
                  'timestamp' => '---',
                  'display_to_from' => '---',
                  'text_key' => '---',
                  'display_quantity' => $static_product_count.' products ('.$static_item_count.' items)',
                  'detail' => 'Total purchases/sales for order #'.$transaction_prior['delivery_id'].' '.$transaction_prior['delivery_date'],
                  'detail_group' => 'pridr_'.$transaction_prior['delivery_id'],
                  'amount' => $order_item_cost_balance),
                  $transaction_prior['balance']);
                // And free up the memory
                $static_item_count = 0;
                $static_product_count = 0;
                $order_item_cost_summary = '';
                $order_item_cost_balance = 0;
                $transaction_row_saved = true;
              }
            if ($summarize_extra_charge == 'by_delivery')
              {
                // Correct the charges by adding the fees back in...
                $transaction_current['balance'] += $order_extra_charge_balance;
                $transaction_prior['balance'] += $order_extra_charge_balance;
                $balance = $transaction_current['balance'];
                // Add the other detail rows we have saved up
                $response .= $order_extra_charge_summary;
                // Then add the current (header/summary row)
                $response .= prep_for_display (array(
                  'transaction_id' => 'ecid_'.$transaction_prior['transaction_group'], // This is used for the CSS id
                  'display_class' => '',
                  'scope' => 'Delivery #'.$transaction_current['delivery_id'],
                  'timestamp' => '---',
                  'display_to_from' => '---',
                  'text_key' => '---',
                  'display_quantity' => '---',
                  'detail' => 'Extra charges for order #'.$transaction_prior['delivery_id'].' '.$transaction_prior['delivery_date'],
                  'detail_group' => 'ecidr_'.$transaction_prior['delivery_id'],
                  'amount' => $order_extra_charge_balance),
                  $transaction_prior['balance']);
                // And free up the memory
                $order_item_cost_summary = '';
                $order_extra_charge_balance = 0;
              }
            if ($summarize_customer_fee == 'by_delivery')
              {
                // Correct the charges by adding the fees back in...
                $transaction_current['balance'] += $order_customer_fee_balance;
                $transaction_prior['balance'] += $order_customer_fee_balance;
                $balance = $transaction_current['balance'];
                // Add the other detail rows we have saved up
                $response .= $order_customer_fee_summary;
                // Then add the current (header/summary row)
                $response .= prep_for_display (array(
                  'transaction_id' => 'cfid_'.$transaction_prior['transaction_group'], // This is used for the CSS id
                  'display_class' => '',
                  'scope' => 'Delivery #'.$transaction_current['delivery_id'],
                  'timestamp' => '---',
                  'display_to_from' => '---',
                  'text_key' => '---',
                  'display_quantity' => '---',
                  'detail' => 'Customer fees for order #'.$transaction_prior['delivery_id'].' '.$transaction_prior['delivery_date'],
                  'detail_group' => 'cfidr_'.$transaction_prior['delivery_id'],
                  'amount' => $order_customer_fee_balance),
                  $transaction_prior['balance']);
                // And free up the memory
                $order_customer_fee_summary = '';
                $order_customer_fee_balance = 0;
              }
            if ($summarize_producer_fee == 'by_delivery')
              {
                // Correct the charges by adding the fees back in...
                $transaction_current['balance'] += $order_producer_fee_balance;
                $transaction_prior['balance'] += $order_producer_fee_balance;
                $balance = $transaction_current['balance'];
                // Add the other detail rows we have saved up
                $response .= $order_producer_fee_summary;
                // Then add the current (header/summary row)
                $response .= prep_for_display (array(
                  'transaction_id' => 'pfid_'.$transaction_prior['transaction_group'], // This is used for the CSS id
                  'display_class' => '',
                  'scope' => 'Delivery #'.$transaction_current['delivery_id'],
                  'timestamp' => '---',
                  'display_to_from' => '---',
                  'text_key' => '---',
                  'display_quantity' => '---',
                  'detail' => 'Producer fees for order #'.$transaction_prior['delivery_id'].' '.$transaction_prior['delivery_date'],
                  'detail_group' => 'pfidr_'.$transaction_prior['delivery_id'],
                  'amount' => $order_producer_fee_balance),
                  $transaction_prior['balance']);
                // And free up the memory
                $order_producer_fee_summary = '';
                $order_producer_fee_balance = 0;
              }
            if ($summarize_taxes == 'by_delivery')
              {
                // Correct the charges by adding the fees back in...
                $transaction_current['balance'] += $order_tax_balance;
                $transaction_prior['balance'] += $order_tax_balance;
                $balance = $transaction_current['balance'];
                // Add the other detail rows we have saved up
                $response .= $order_tax_summary;
                // Then add the current (header/summary row)
                $response .= prep_for_display (array(
                  'transaction_id' => 'taxid_'.$transaction_prior['transaction_group'], // This is used for the CSS id
                  'display_class' => '',
                  'scope' => 'Delivery #'.$transaction_current['delivery_id'],
                  'timestamp' => '---',
                  'display_to_from' => '---',
                  'text_key' => '---',
                  'display_quantity' => '---',
                  'detail' => 'Taxes for order #'.$transaction_prior['delivery_id'].' '.$transaction_prior['delivery_date'],
                  'detail_group' => 'taxidr_'.$transaction_prior['transaction_group'],
                  'amount' => $order_tax_balance),
                  $transaction_prior['balance']);
                // And free up the memory
                $order_tax_summary = '';
                $order_tax_balance = 0;
              }
            // Finally, send a row-separator
            $response .= '
              <tr><td colspan="9"><span class="row_sep" /></td></tr>';
          }

        // Summarize various types of charges
        if ($transaction_current['text_key'] == 'weight cost' || $transaction_current['text_key'] == 'each cost')
          {
            if ($summarize_item_cost == 'by_product')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid gidr_'.$transaction_current['transaction_group'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $product_summary .= prep_for_display ($transaction_current, '');
                $static_product_cost += $amount;
                // Keep some more interesting values
                $static_to_from = $transaction_current['display_to_from'];
                $static_quantity = $transaction_current['display_quantity'];
                $static_item_count += $transaction_current['quantity'];
                $static_product_count += 1;
                $static_detail = $transaction_current['detail'];
                $summarize_by_product = true;
                $transaction_row_okay = true;
              }
            elseif ($summarize_item_cost == 'by_delivery')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid pridr_'.$transaction_current['delivery_id'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $order_item_cost_summary .= prep_for_display ($transaction_current, '');
                // Put the total into the basket totals and remove it from the running total
                $static_item_count += $transaction_current['quantity'];
                $static_product_count += 1;
                $order_item_cost_balance += $amount;
                $balance -= $amount;
                $transaction_current['balance'] = $balance; // Keep this current
                $summarize_by_delivery = true;
                $transaction_row_okay = true;
              }
              // Else no summary for weight_cost or each_cost
          }
        if ($transaction_current['text_key'] == 'extra charge')
          {
            if ($summarize_extra_charge == 'by_product')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid gidr_'.$transaction_current['transaction_group'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $product_summary .= prep_for_display ($transaction_current, '');
                $static_product_cost += $amount;
                $summarize_by_product = true;
                $transaction_row_okay = true;
              }
            elseif ($summarize_extra_charge == 'by_delivery')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid ecidr_'.$transaction_current['delivery_id'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $order_extra_charge_summary .= prep_for_display ($transaction_current, '');
                // Put the total into the basket totals and remove it from the running total
                $order_extra_charge_balance += $amount;
                $balance -= $amount;
                $transaction_current['balance'] = $balance; // Keep this current
                $summarize_by_delivery = true;
                $transaction_row_okay = true;
              }
              // Else no summary for extra_charge
          }
        if ($transaction_current['text_key'] == 'customer fee')
          {
            if ($summarize_customer_fee == 'by_product')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid gidr_'.$transaction_current['transaction_group'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $product_summary .= prep_for_display ($transaction_current, '');
                $static_product_cost += $amount;
                $summarize_by_product = true;
                $transaction_row_okay = true;
              }
            elseif ($summarize_customer_fee == 'by_delivery')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid cfidr_'.$transaction_current['delivery_id'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $order_customer_fee_summary .= prep_for_display ($transaction_current, '');
                // Put the total into the basket totals and remove it from the running total
                $order_customer_fee_balance += $amount;
                $balance -= $amount;
                $transaction_current['balance'] = $balance; // Keep this current
                $summarize_by_delivery = true;
                $transaction_row_okay = true;
              }
              // Else no summary for customer_fee
          }
        if ($transaction_current['text_key'] == 'producer fee')
          {
            if ($summarize_producer_fee == 'by_product')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid gidr_'.$transaction_current['transaction_group'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $product_summary .= prep_for_display ($transaction_current, '');
                $static_product_cost += $amount;
                $summarize_by_product = true;
                $transaction_row_okay = true;
              }
            elseif ($summarize_producer_fee == 'by_delivery')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid pfidr_'.$transaction_current['delivery_id'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $order_producer_fee_summary .= prep_for_display ($transaction_current, '');
                // Put the total into the basket totals and remove it from the running total
                $order_producer_fee_balance += $amount;
                $balance -= $amount;
                $transaction_current['balance'] = $balance; // Keep this current
                $summarize_by_delivery = true;
                $transaction_row_okay = true;
              }
              // Else no summary for producer_fee
          }
        if ($transaction_current['text_key'] == 'tax')
          {
            if ($summarize_taxes == 'by_product')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid gidr_'.$transaction_current['transaction_group'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $product_summary .= prep_for_display ($transaction_current, '');
                $static_product_cost += $amount;
                $summarize_by_product = true;
                $transaction_row_okay = true;
              }
            elseif ($summarize_taxes == 'by_delivery')
              {
                // Set up for accessing the entire group with a CSS class
                $transaction_current['display_class'] = 'hid taxidr_'.$transaction_current['delivery_id'];
                $transaction_current['scope'] = 'Basket #'.$transaction_current['basket_id'];
                $order_tax_summary .= prep_for_display ($transaction_current, '');
                // Put the total into the basket totals and remove it from the running total
                $order_tax_balance += $amount;
                $balance -= $amount;
                $transaction_current['balance'] = $balance; // Keep this current
                $summarize_by_delivery = true;
                $transaction_row_okay = true;
              }
              // Else no summary for tax
          }

        // If this transaction is not already summarized somewhere, then just send it through
        if (! $transaction_row_okay)
          {
            $response .= prep_for_display ($transaction_current, '');
            // We will NOT push the $transaction_current to $transaction_prior in this case
            $transaction_row_okay = false;
          }


        // Not sure if this really accomplishes anything but it was in the original design...
        if ($transaction_row_okay == true)
          {
            $transaction_prior = $transaction_current;
          }
//         // Send a row every time (debugging)
//         $response .= prep_for_display ($transaction_current);

      }
    echo $response;
    exit(0);
  }

// A little function to generate the HTML for ledger display rows...
function prep_for_display ($transaction_data, $running_total)
  {
    // Always hide the row and the last column when there is no $running_total

    if ($running_total)
      {
        $running_total = number_format ($running_total, 2);
        $hide_class = '';
        $more_symbol = 'more';
        $show_detail_class = '';
        $more_less_script = '<span class="more_less" onclick="this.innerHTML=show_hide_detail(\''.$transaction_data['detail_group'].'\',this.innerHTML)">'.$more_symbol.'</span>';
      }
    else
      {
        $hide_class = 'hid ';
        $more_symbol = '';
        $show_detail_class = 'show_details ';
        $more_less_script = '';
      }
    $response = '
      <tr id="'.$transaction_data['transaction_id'].'" class="'.$hide_class.$show_detail_class.$transaction_data['display_class'].'">
        <td class="scope">'.$transaction_data['scope'].'</td>
        <td class="timestamp">'.$transaction_data['timestamp'].'</td>
        <td class="from_to">'.$transaction_data['display_to_from'].'</td>
        <td class="text_key">'.$transaction_data['text_key'].'</td>
        <td class="quantity">'.$transaction_data['display_quantity'].'</td>
        <td class="detail'.$transaction_data['special'].'">'.$transaction_data['detail'].'</td>
        <td class="more_less">'.$more_less_script.'</td>
        <td class="amount">'.number_format ($transaction_data['amount'], 2).'</td>
        <td class="'.$hide_class.'balance">'.$running_total.'&nbsp;</td>
      </tr>';
    return ($response);
  }

// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/
$display = '
Begin typing to select an account: <input type="text" name="q" id="load_target" autocomplete="off"/>


        <div align="center">
        <div style="margin:0.5em;padding:0.5em;background-color:#ffe;width:50%;border:1px solid #fda;font-size:140%">'.$member_name.'</div>
        <div style="width:95%;height:450px;overflow-y:scroll;border:1px solid black" id="content_area">
      <table class="ledger">
        <tr id="ledger_header">
          <th>Basket ID</th>
          <th>Date / Time</th>
          <th>To / From</th>
          <th>For</th>
          <th>Qty</th>
          <th>Description</th>
          <th>Detail</th>
          <th>Amount</th>
          <th>Balance</th>
        </tr>
        <tr id="pre_insertion_point">
          <td colspan="9"></td>
        </tr>
        <tr id="post_insertion_point">
          <td colspan="9"></td>
        </tr>
      </table>
        </div>
        <br />
        </div>';

$page_specific_javascript = '
<script type="text/javascript" src="/shop/ajax/jquery.autocomplete.js"></script>
<script type="text/javascript">
// Information on this autocomplete script: http://www.devbridge.com/projects/autocomplete/jquery/
  var options, a;
  jQuery(function(){
    options = {
      serviceUrl:"'.$_SERVER['PHP_SELF'].'",
      minChars:2,
      // delimiter: /(,|;)\s*/, // regex or character
      maxHeight:400,
      width:400,
      zIndex: 9999,
      deferRequestBy: 300,
      // params: { country:"Yes" }, //aditional parameters
      params: { action:"get_account_hint"},
      // noCache: false, //default is false, set to true to disable caching
      onSelect: function(value, data){ get_account_info (data); } // callback function
      // lookup: ["January", "February", "March", "April", "May"] // local lookup values
      };
    a = $("#load_target").autocomplete(options);
  });

function get_account_info (account_spec) {
  $.post("'.$_SERVER['PHP_SELF'].'", {
    action:"get_account_info",
    account_spec:account_spec
    },
  function(account_data) {
    // Taken together, the next two lines will replace the table content
    var ledger_header = document.getElementById("ledger_header");
    document.getElementById("content_area").innerHTML = "<table class=\"ledger\"><tr id=\"ledger_header\"><th>Basket ID</th><th>Date / Time</th><th>To / From</th><th>For</th><th>Qty</th><th>Description</th><th>Detail</th><th>Amount</th><th>Balance</th></tr>"+account_data+"</table>";
    // document.getElementById("content_area").innerHTML = account_data;
    // The next line does a pre-insertion
    // document.getElementById("pre_insertion_point").outerHTML = account_data;
    });
  }

function show_hide_detail (target, operation) {
  var target;
  var operation;
  if (operation == "more") {
    $("."+target).removeClass("hid");
    $("."+target).addClass("detail");
    return("hide");
    }
  else {
    $("."+target).addClass("hid");
    return("more");
    }
  }

</script>';

$page_specific_css = '
  <style type="text/css">
  #load_target {width:40%;}
  .autocomplete-w1 { background:url(/shop/grfx/shadow.png) no-repeat bottom right; position:absolute; top:0px; left:0px; margin:6px 0 0 6px; /* IE6 fix: */ _background:none; _margin:1px 0 0 0; }
  .autocomplete { border:1px solid #999; background:#FFF; cursor:default; text-align:left; max-height:350px; overflow:auto; margin:-6px 6px 6px -6px; /* IE6 specific: */ _height:350px;  _margin:0; _overflow-x:hidden; }
  .autocomplete .selected { background:#F0F0F0; }
  .autocomplete div { padding:2px 5px; white-space:nowrap; overflow:hidden; }
  .autocomplete strong { font-weight:normal; color:#007;text-decoration:underline; }

  table.ledger {width:100%;}
  .ledger tr td {background-color: #ddc; margin:0;font-size:80%;}
  .hid {display:none;}
  .no_back {background-color:#fff;}
  .more_less {font-size:90%;color:#630;cursor:pointer;padding:0;margin:0;}
  .show_details {color:#468;background-color:$eee;}
  .basket_id, .timestamp, .text_key, .more_less {text-align:center;}
  .amount, .balance {text-align:right;}
  .colorme {color:#d00;}
  .row_sep {background-color:#a70;height:1px;display:block;width:100%;}
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Member Balances Lookup</span>';
$page_title = 'Reports: Member Balances Lookup';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
