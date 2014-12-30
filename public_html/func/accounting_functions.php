<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

include_once ('func.get_basket_item.php');
include_once ('func.get_basket.php');
include_once ('func.get_member.php');
include_once ('func.get_producer.php');
include_once ('func.get_product.php');
include_once ('func.update_ledger.php');
include_once ('func.update_basket_item.php');
include_once ('func.open_basket.php');

session_start();
//valid_auth('cashier,site_admin');



// $basket_info = update_basket(array(
//     'basket_id' => '3962',
//     'delivery_id' => '52',
//     'member_id' => '240',
//     'action' => 'checkout',
//     'delcode_id' => 'GRNDI',
//     'deltype' => 'H'
//     ));


// This function is used to update basket information
// Input data is an associative array with values:
// * action                ['checkout'|'un_checkout'|'set_delcode']
// * basket_id             basket_id 
// * delivery_id           delivery_id 
// * member_id             member_id
// * delcode_id            [delcode_id]
// * deltype               [deltype]
function update_basket (array $data)
  {
    global $connection;
    $member_id_you = $_SESSION['member_id'];
    $producer_id_you = $_SESSION['producer_id_you'];
    // Allow admins to override certain checks if the requested action is not for themselves
    $admin_override_not_set = false;
    if ($member_id_you == $data['member_id'] || ! CurrentMember::auth_type('cashier'))
      {
        $admin_override_not_set = true;
      }
    // Set flags for needed validations and operations
    switch ($data['action'])
      {
        case 'checkout':
          $test_for_membership_privilege = true;
          $test_customer_ordering_window = true;
          $checked_out = '1';
          $initiate_checkout = true;
          $update_ledger_items = true;
          break;
        case 'un_checkout':
          $test_customer_ordering_window = true;
          $checked_out = '0';
          $initiate_un_checkout = true;
          break;
        case 'set_delcode':
          $update_delcode = true;
          $update_ledger_items = true;
          break;
        default:
          die(debug_print('ERROR: 101 ', 'unexpected request', basename(__FILE__).' LINE '.__LINE__));
          break;
      }
    // Get  information about the basket for this member
    // Prefer to access basket by basket_id
    if ($data['basket_id'] != 0)
      {
        $basket_info = get_basket ($data['basket_id']);
      }
    // Then try with member_id/delivery_id combination
    elseif ($data['member_id'] != 0 && $data['delivery_id'] != 0)
      {
        $basket_info = get_basket ($data['member_id'], $data['delivery_id']);
      }
    // Otherwise we don't know enough to get the basket
    else
      {
        die(debug_print('ERROR: 509 ', 'incomplete information to locate basket', basename(__FILE__).' LINE '.__LINE__));
      }
    // Check that we actually got some basket information
    if (! is_array ($basket_info))
      {
        die(debug_print('ERROR: 502 ', 'basket does not exist', basename(__FILE__).' LINE '.__LINE__));
      }
    // Check that the member is not pending or discontinued
    if ($test_for_membership_privilege && $admin_override_not_set)
      {
        if ($member_info['pending'] == 1 || $member_info['membership_discontinued'] == 1)
          {
            die(debug_print('ERROR: 803 ', 'incorrect privilege to order', basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Check if shopping is closed for this order
    if ($test_customer_ordering_window && $admin_override_not_set)
      {
        if (ActiveCycle::ordering_window() == 'closed')
          {
            die(debug_print('ERROR: 701 ', 'customer ordering period is not in effect', basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Update the basket with a new delcode and information related related to the new delcode
    if ($update_delcode)
      {
        if ($data['deltype'] == 'H' || $data['deltype'] == 'W') $query_deltype = 'D'; // H[ome] and W[ork] --> D[elivery]
        else $query_deltype = $data['deltype']; // P[ickup]
        // Could check for changes and abort otherwise, but this will force updating
        // delivery_postal_code just in case it might have changed.
        $query_delcode = '
          SELECT
            delcharge,
            delivery_postal_code
          FROM '.TABLE_DELCODE.'
          WHERE
            delcode = "'.mysql_real_escape_string($data['delcode_id']).'"
            AND deltype = "'.$query_deltype.'"
            AND inactive = "0"';
        $result_delcode = @mysql_query($query_delcode, $connection) or die(debug_print ("ERROR: 892573 ", array ($query_delcode,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Got we some information, then post the new information
        if ($row_delcode = mysql_fetch_array($result_delcode))
          {
            $query_update_basket = '
              UPDATE '.NEW_TABLE_BASKETS.'
              SET
                delivery_cost = "'.mysql_real_escape_string($row_delcode['delcharge']).'",
                delivery_postal_code = "'.mysql_real_escape_string($row['delivery_postal_code']).'",
                delcode_id = "'.mysql_real_escape_string($data['delcode_id']).'",
                deltype = "'.mysql_real_escape_string($data['deltype']).'"
              WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
            $result_update_basket = @mysql_query($query_update_basket, $connection) or die(debug_print ("ERROR: 892764 ", array ($query_update_basket,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            // Update the $basket_info with changes
            $basket_info['delivery_cost'] = $row_delcode['delcharge'];
            $initiate_delivery_charge = true;
          }
        // Otherwise error
        else
          {
            die(debug_print('ERROR: 702 ', 'requested delcode does not exist or is not available', basename(__FILE__).' LINE '.__LINE__));
          }
      }
    // Change the checked_out setting on the basket
    // Do this early so the update_basket_item will process the ledger items (only if they are in a checked-out state)
    if ($initiate_checkout)
      {
        $query = '
          UPDATE '.NEW_TABLE_BASKETS.'
          SET checked_out = "1"
          WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 892764 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
    // For checkout, synchronize ledger entries to all basket_items
    if ($initiate_checkout)
      {
        // Get the items currently in the basket
        $query_basket_items = '
          SELECT
            bpid,
            product_id,
            product_version
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
        $result_basket_items = @mysql_query($query_basket_items, $connection) or die(debug_print ("ERROR: 892785 ", array ($query_basket_items,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Go through all the basket items
        while ($row_basket_items = mysql_fetch_array($result_basket_items))
          {
            $basket_item_info = update_basket_item (array(
              'action' => 'synch_ledger',
              'delivery_id' => $data['delivery_id'],
              'member_id' => $data['member_id'],
              'product_id' => $row_basket_items['product_id'],
              'product_version' => $row_basket_items['product_version']
              ));
            if ($basket_item_info != 'synch_ledger:'.$row_basket_items['bpid'])
              {
                return('error 100: expected "synch_ledger:'.$row_basket_items['bpid'].'" but got "'.$basket_item_info.'"');
              }
          }
      }
    // For un_checkout, clear all ledger entries related to the basket and basket_items
    // This will remove or clear the cost of ledger entries for all products in the basket
    if ($initiate_un_checkout)
      {
        // Get the items currently in the basket
        $query_basket_items = '
          SELECT
            bpid,
            product_id,
            product_version
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
        $result_basket_items = @mysql_query($query_basket_items, $connection) or die(debug_print ("ERROR: 892785 ", array ($query_basket_items,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        // Go through all the basket items
        while ($row_basket_items = mysql_fetch_array($result_basket_items))
          {
// Problem here: clear_item removes all quantity from the basket. We would like to leave the basket unchanged.
            $basket_item_info = update_basket_item (array(
              'action' => 'un_checkout',
              'delivery_id' => $data['delivery_id'],
              'member_id' => $data['member_id'],
              'product_id' => $row_basket_items['product_id'],
              'product_version' => $row_basket_items['product_version'],
              'delete_on_zero' => 'YES'
              ));
            if ($basket_item_info != 'clear_item:'.$row_basket_items['bpid'])
              {
                return('error 100: expected "clear_item:'.$row_basket_items['bpid'].'" but got "'.$basket_item_info.'"');
              }
          }
      }
    // Change the checked_out setting on the basket
    // Do this last so the update_basket_item will clear ledger items (only if they are in a checked-out state)
    if ($initiate_un_checkout)
      {
        $query = '
          UPDATE '.NEW_TABLE_BASKETS.'
          SET checked_out = "0"
          WHERE basket_id = "'.mysql_real_escape_string($basket_info['basket_id']).'"';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 892764 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
      }
    // At this point, all basket information has been updated, so we need to consider any changes to the ledger.
    // This is done for any/all changes, so not conditional except for baskets that are not checked-out.
    if ($basket_info['checked_out'] == 1)
      {
        // If there is a delivery charge, then post it (or clear it if wrongly set).
        if ($basket_info['delivery_cost'] != 0 || $initiate_delivery_charge)
          {
            // Add the delivery cost to the ledger for this basket
            $ledger_status = update_ledger(array (
              'account_from_type' => 'member',
              'account_from' => $data['member_id'],
              'sub_account_from' => '',
              'account_to_type' => 'internal',
              'account_to' => 'delivery_cost',
              'sub_account_to' => $basket_info['delcode_id'],
              'amount' => $basket_info['delivery_cost'],
              'referenced_table' => 'baskets',
              'referenced_key' => $basket_info['basket_id'],
              'text_key' => 'delivery cost',
              'post_by_member_id' => $_SESSION['member_id'],
              'transaction_group' => $basket_info['basket_id']
              ));
          }
      }


    // Now cycle through all the products in the basket and handle the fees that aggregate to the basket level
    // which could be taxes, customer fees
    if ($bogus_get_taxes || $bogus_get_customer_fees)
      {
        $query_fields = array (
          // Expose additional parameters as they become needed.
          // COLUMNS FROM BASKET_ITEMS --------------------------------
          'bpid',
          'basket_id',
          'product_id',
          'product_version',
          'quantity',
          'total_weight',
          NEW_TABLE_BASKET_ITEMS.'.product_fee_percent',
          NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent',
          NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent',
          'taxable',
          'out_of_stock',
          // 'future_delivery',
          // 'future_delivery_type',
          'date_added',
          // COLUMNS FROM PRODUCTS ------------------------------------
          // 'xid',
          // 'product_id',                                  // provided by basket_items
          // 'product_version',                             // provided by basket_items
          'producer_id',
          'product_name',
          // 'account_number',
          // 'inventory_pull',
          // 'inventory_id',
          'product_description',
          'subcategory_id',
          // 'future_delivery',
          // 'future_delivery_type',
          // 'production_type_id',
          'unit_price',
          'pricing_unit',
          'ordering_unit',
          'random_weight',
          'meat_weight_type',
          'minimum_weight',
          'maximum_weight',
          'extra_charge',
          'image_id',
          'listing_auth_type',
          // 'confirmed',
          // 'retail_staple',
          // 'staple_type',
          'created',
          'modified',
          'tangible',
          'sticky',
          // 'hide_from_invoice',
          'storage_id',
          // COLUMNS FROM MESSAGES ------------------------------------
          'message_data',
          );
        $query_product_info = '
          SELECT
            '.implode (",\n        ", $query_fields).'
          FROM '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
          LEFT JOIN '.NEW_TABLE_MESSAGES.' ON '.NEW_TABLE_MESSAGES.'.referenced_key1 = '.NEW_TABLE_BASKET_ITEMS.'.bpid
          LEFT JOIN '.NEW_TABLE_MESSAGE_TYPES.' USING(message_type_id)
            WHERE basket_id = "'.mysql_real_escape_string ($data['basket_id']).'"
            AND '.NEW_TABLE_MESSAGE_TYPES.'.key1_target = "basket_items.bpid"';
        $result_product_info = @mysql_query($query_product_info, $connection) or die(debug_print ("ERROR: 92475 ", array ($query_product_info,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $customer_adjust_amount = 0;
        while ($row_product_info = mysql_fetch_array($result_product_info))
          {
            // Check if we will aggregate customer fees at the basket level
            if ($bogus_get_customer_fees && $basket_info['customer_fee_percent'] != 0 && PAYS_CUSTOMER_FEE == 'customer')
              {
                // Cost of the random weight item
                if ($row_product_info['random_weight'] == 1)
                  {
                    $total_price = $row_product_info['total_weight'] * $row_product_info['unit_price'];
                  }
                // Or the cost of a regular item
                else
                  {
                    $total_price = ($row_product_info['quantity'] - $row_product_info['out_of_stock']) * $row_product_info['unit_price'];
                  }
                $customer_adjust_amount = $basket_info['customer_fee_percent'] * $total_price / 100;
                $customer_adjust_amount_total += $customer_adjust_amount;
              }
            // Check if we will aggregate taxes at the basket level
            if ($bogus_get_taxes && ($row_product_info['taxable'] || COOP_FEE_IS_TAXED == 'always'))
              {
                // Only figure the tax array once and skip it otherwise
                if (! is_array($tax_array))
                  {
                    $tax_array = array();
                    // Get the tax information...
                    $query_taxes = '
                      SELECT
                        region_code,
                        region_type,
                        tax_percent
                      FROM '.NEW_TABLE_TAX_RATES.'
                      WHERE
                        postal_code = "'.mysql_real_escape_string ($basket_info['delivery_postal_code']).'"
                        AND order_id_start <= "'.mysql_real_escape_string ($data['delivery_id']).'"
                        AND (
                          order_id_stop >= "'.mysql_real_escape_string ($data['delivery_id']).'"
                          OR order_id_stop = "0"
                          )';
                    $result_taxes = @mysql_query($query_taxes, $connection) or die(debug_print ("ERROR: 890236 ", array ($query_taxes,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                    $tax_array_count = 0;
                    while ($row_taxes = mysql_fetch_array($result_taxes))
                      {
                        $tax_array[$tax_array_count]['region_code'] = $row_taxes['region_code'];
                        $tax_array[$tax_array_count]['region_type'] = $row_taxes['region_type'].' tax'; // e.g. 'county tax'
                        $tax_array[$tax_array_count]['tax_percent'] = $row_taxes['tax_percent'];
                        $tax_array_count++;
                      }
                  }
                // Cycle through the tax array and apply taxes to this product
                for ($count = 0; $count < $tax_array_count; $count++)
                  {
                    $tax_amount = 0;
                    // Just tax the item and not the fees
                    if (COOP_FEE_IS_TAXED == 'never')
                      {
                        $tax_amount = $tax_array[$count]['tax_percent'] * $total_price / 100;
                      }
                    // Tax the item and the fees
                    elseif (COOP_FEE_IS_TAXED == 'on taxable items' ||
                      (COOP_FEE_IS_TAXED == 'always' && $basket_item_info['taxable'] == 1))
                      {
                        $tax_amount = $tax_array[$count]['tax_percent'] * ($total_price + ($total_price * ($basket_info['customer_fee_percent'] + $row_product_info['producer_fee_percent'] + $row_product_info['subcategory_fee_percent'] + $row_product_info['product_fee_percent']) / 100));
                      }
                    // Tax only the fees (does this ever really happen?)
                    elseif (COOP_FEE_IS_TAXED == 'always' && $basket_item_info['taxable'] == 0)
                      {
                        $tax_amount = $tax_array[$count]['tax_percent'] * $total_price * ($basket_info['customer_fee_percent'] + $row_product_info['producer_fee_percent'] + $row_product_info['subcategory_fee_percent'] + $row_product_info['product_fee_percent']) / 100;
                      }
                    $tax_array[$count]['total_tax'] += $tax_amount;
                  }
              }
          }
        // All the aggregated fees are calculated at this point
        // Now send them to the ledger
        if ($bogus_get_customer_fees && PAYS_CUSTOMER_FEE == 'customer')
          {
            $ledger_status = update_ledger(array (
              'account_from_type' => 'member', // Could be producer, but we would not record that in the basket
              'account_from' => $member_info['member_id'],
              'sub_account_from' => '',
              'account_to_type' => 'internal',
              'account_to' => 'customer_fee',
              'sub_account_to' => $member_info['member_id'],
              'amount' => $customer_adjust_amount_total,
              'referenced_table' => 'baskets',
              'referenced_key' => $basket_info['basket_id'],
              'text_key' => 'customer fee',
              'post_by_member_id' => $_SESSION['member_id'],
              'transaction_group' => $basket_info['basket_id'],
              'message' => '',
              'delete_on_zero' => 'YES'
              ));
          }
        if ($bogus_get_taxes)
          {
            for ($count = 0; $count < $tax_array_count; $count++)
              {
                $ledger_status = update_ledger(array (
                  'account_from_type' => 'member',
                  'account_from' => $data['member_id'],
                  'sub_account_from' => '',
                  'account_to_type' => 'tax',
                  'account_to' => $tax_array[$count]['region_code'],
                  'sub_account_to' => $basket_info['delcode_id'],
                  'amount' => $tax_array[$count]['total_tax'],
                  'referenced_table' => 'baskets',
                  'referenced_key' => $basket_info['basket_id'],
                  'text_key' => $tax_array[$count]['region_type'],
                  'post_by_member_id' => $_SESSION['member_id'],
                  'transaction_group' => $basket_info['basket_id'],
                  'message' => '',
                  'delete_on_zero' => 'YES'
                  ));
              }
          }
      }
  }
?>