<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,site_admin,cashier');

include_once ('func.get_ledger_row_markup.php');

// Taken in the following order, these functions should collect all transactions for a 
// particular account_spec

// function get_ledger_delivery_ids ($account_spec)
//   function get_ledger_basket_ids ($account_spec, $delivery_id)
//     function get_ledger_basket_ids ($account_spec, $basket_id)
//     function get_ledger_pvid_no_bpid ($account_spec, $basket_id, $include_replaced = false)
//     function get_ledger_delcode_no_bpid ($account_spec, $basket_id, $include_replaced = false)
//     function get_ledger_pvid_no_bpid ($account_spec, $basket_id, $include_replaced = false)
//   function get_ledger_no_basket_id ($account_spec, $delivery_id, $include_replaced = false)
// function get_ledger_no_delivery_id ($account_spec, $include_replaced = false)


// This is a general list getter...
//$bpid_list_array = get_ledger_list('bpid', array('basket_id'->'14'), 'bpid', 'member:12', false)

// Example: SELECT DISTINCT (bpid) FROM ledger WHERE [target/source = member:12] AND basket_id = "14" ORDER BY transaction_id
// get_ledger_list ('bpid', array('basket_id'->'14'), 'transaction_id', 'member:12', false)
function get_ledger_list ($list_type, $select_array, $order_by, $account_spec, $include_replaced = false)
  {
    global $connection;
    $list_array = array ();
    $where_array = array ();
    list($type, $key) = explode (':', $account_spec);
    foreach ($select_array as $where_key=>$where_value)
      {
        array_push ($where_array, mysql_real_escape_string($where_key).' = "'.mysql_real_escape_string($where_value).'"
        ');
      }
    if ($include_replaced == false)
      {
        $array_push ($where_array, 'replaced_by IS NULL
        ');
      }
    array_push ($where_array, '((source_type = "'.mysql_real_escape_string($type).'"
            AND source_key = "'.mysql_real_escape_string($key).'")
          OR (target_type = "'.mysql_real_escape_string($type).'"
            AND target_key = "'.mysql_real_escape_string($key).'"))
        ').
    $where_condition = implode ('  AND ', $where_array);
    if ($order_by)
      {
        $order_by = 'ORDER BY '.$order_by;
      }
    $query = '
      SELECT
        DISTINCT ('.mysql_real_escape_string($list_type).')
        FROM '.NEW_TABLE_LEDGER.'
        WHERE
          '.$where_condition
        .$where_deleted
        .$order_by;
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 758029 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($list_array, $row[$list_type]);
      }
    if (count($list_array)) return($list_array);
    else return (0);
  }




echo (date ('Y-m-d H:i:s', time()));

$account_spec = 'producer:2';



$delivery_ids = get_ledger_list ('delivery_id', array(), 'delivery_id', $account_spec, true);
// echo "<pre>DELIVERY_ID VALUES: ".print_r($delivery_ids, true)."</pre>";

foreach ($delivery_ids as $delivery_id)
  {
    $basket_ids = get_ledger_list ('basket_id', array('delivery_id'=>$delivery_id), 'delivery_id', $account_spec, true);

    // echo "<pre>BASKET_ID VALUES: ".print_r($basket_ids, true)."</pre>";
    foreach ($basket_ids as $basket_id)
      {












//        $bpids = get_ledger_bpids ($account_spec, $basket_id);
        $bpids = get_ledger_bpid2 ($account_spec, $basket_id);
//        echo "<pre>BPID VALUES: ".print_r($bpids, true)."</pre>";
//             echo "<pre>TRANSACTIONINFO(0): ".print_r($transactions, true)."</pre>";

//         foreach ($bpids as $bpid)
//           {
//             $transactions = get_ledger_bpid ($account_spec, $bpid, true);
//             echo "<pre>TRANSACTIONINFO(0): ".print_r($transactions, true)."</pre>";
//           }
$counter++;

        $transactions = get_ledger_pvid_no_bpid ($account_spec, $basket_id, true);
//        echo "<pre>TRANSACTION_INFO(1): ".print_r($transactions, true)."</pre>";

        $transactions = get_ledger_delcode_no_bpid ($account_spec, $basket_id, true);
//        echo "<pre>TRANSACTION_INFO(2): ".print_r($transactions, true)."</pre>";

        $transactions = get_ledger_no_pvid_no_bpid ($account_spec, $basket_id, true);
//        echo "<pre>TRANSACTION_INFO(3): ".print_r($transactions, true)."</pre>";
      }
    $transactions = get_ledger_no_basket_id($account_spec, $delivery_id, true);
//    echo "<pre>TRANSACTION_INFO(4): ".print_r($transactions, true)."</pre>";
  }

$transactions = get_ledger_no_delivery_id($account_spec, true);
//echo "<pre>TRANSACTION_INFO(5): ".print_r($transactions, true)."</pre>";

echo $counter.'-'.(date ('Y-m-d H:i:s', time()));


// // This function will get a list of delivery_id values for which transactions exist (ordered by delivery_date)
// // RETURNS: array (delivery_id)
// function get_ledger_delivery_ids ($account_spec, $include_replaced = false)
//   {
//     global $connection;
//     $delivery_id_array = array ();
//     list($type, $key) = explode (':', $account_spec);
//     if ($include_replaced == false)
//       {
//         $where_deleted = '
//         AND replaced_by IS NULL';
//       }
//     $query = '
//       SELECT DISTINCT(delivery_id)
//       FROM '.NEW_TABLE_LEDGER.'
//       LEFT JOIN '.TABLE_ORDER_CYCLES.' USING(delivery_id)
//       WHERE
//         ((source_type = "'.mysql_real_escape_string($type).'"
//           AND source_key = "'.mysql_real_escape_string($key).'")
//         OR (target_type = "'.mysql_real_escape_string($type).'"
//           AND target_key = "'.mysql_real_escape_string($key).'"))
//         AND delivery_id IS NOT NULL'.
//         $where_deleted.'
//       ORDER BY delivery_date';
//     $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 753907 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//     while ($row = mysql_fetch_array($result))
//       {
//         array_push ($delivery_id_array, $row['delivery_id']);
//       }
//     if (count($delivery_id_array)) return($delivery_id_array);
//     else return (0);
//   }

// This function will get a list of transactions for which there is no defined delivery_id (NULL)
// RETURNS: array (transaction_info)
function get_ledger_no_delivery_id ($account_spec, $include_replaced = false)
  {
    global $connection;
    $transaction_info = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND delivery_id IS NULL'.
        $where_deleted.'
      ORDER BY timestamp';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 840321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transaction_info, $row);
      }
    if (count($transaction_info)) return($transaction_info);
    else return (0);
  }

// // This function will get a list of all basket_id values for a given delivery_id for which transactions exist
// // RETURNS: array (basket_id)
// function get_ledger_basket_ids ($account_spec, $delivery_id, $include_replaced = false)
//   {
//     global $connection;
//     $basket_id_array = array ();
//     list($type, $key) = explode (':', $account_spec);
//     if ($include_replaced == false)
//       {
//         $where_deleted = '
//         AND replaced_by IS NULL';
//       }
//     $query = '
//       SELECT DISTINCT(basket_id)
//       FROM '.NEW_TABLE_LEDGER.'
//       WHERE
//         ((source_type = "'.mysql_real_escape_string($type).'"
//           AND source_key = "'.mysql_real_escape_string($key).'")
//         OR (target_type = "'.mysql_real_escape_string($type).'"
//           AND target_key = "'.mysql_real_escape_string($key).'"))
//         AND delivery_id = "'.mysql_real_escape_string($delivery_id).'"
//         AND basket_id IS NOT NULL'.
//         $where_deleted;
//     $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 863042 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//     while ($row = mysql_fetch_array($result))
//       {
//         array_push ($basket_id_array, $row['basket_id']);
//       }
//     if (count($basket_id_array)) return($basket_id_array);
//     else return (0);
//   }

// This function will get a list of transactions in a given delivery_id for which there is no defined basket_id (NULL)
// RETURNS: array (transaction_info)
function get_ledger_no_basket_id ($account_spec, $delivery_id, $include_replaced = false)
  {
    global $connection;
    $transaction_info = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND delivery_id = "'.mysql_real_escape_string($delivery_id).'"
        AND basket_id IS NULL'.
        $where_deleted.'
      ORDER BY timestamp';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 840321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transaction_info, $row);
      }
    if (count($transaction_info)) return($transaction_info);
    else return (0);
  }

// This function will get a list of all bpid values for a given basket_id for which transactions exist
// RETURNS: array (bpid)
function get_ledger_bpids ($account_spec, $basket_id, $include_replaced = false)
  {
    global $connection;
    $bpid_array = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT DISTINCT(bpid)
      FROM '.NEW_TABLE_LEDGER.'
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND bpid IS NOT NULL'.
        $where_deleted.'
      ORDER BY bpid';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 753907 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($bpid_array, $row['bpid']);
      }
    if (count($bpid_array)) return($bpid_array);
    else return (0);
  }

// This function will get transactions for a particular bpid
// RETURNS: array (transaction_info)
function get_ledger_bpid ($account_spec, $bpid, $include_replaced = false)
  {
    global $connection;
    $transaction_info = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(bpid)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND bpid = "'.mysql_real_escape_string($bpid).'"'.
        $where_deleted.'
      ORDER BY timestamp';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 840321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transaction_info, $row);
      }
    if (count($transaction_info)) return($transaction_info);
    else return (0);
  }

// This function will get a list of transactions in a given basket_id for which there is no defined bpid (NULL)
// ... but which have values for pvid (ordered by product_id, version_id)
// RETURNS: array (transaction_info)
function get_ledger_pvid_no_bpid ($account_spec, $basket_id, $include_replaced = false)
  {
    global $connection;
    $transaction_info = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(bpid)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND '.NEW_TABLE_LEDGER.'.basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND '.NEW_TABLE_LEDGER.'.bpid IS NULL
        AND '.NEW_TABLE_LEDGER.'.pvid IS NOT NULL'.
        $where_deleted.'
      ORDER BY timestamp';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 840321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transaction_info, $row);
      }
    if (count($transaction_info)) return($transaction_info);
    else return (0);
  }

// This function will get a list of transactions in a given basket_id for which there is no defined bpid (NULL)
// ... and no defined values for pvid... but which have values for delcode_id (ordered by delcode_id)
// RETURNS: array (transaction_info)
function get_ledger_delcode_no_bpid ($account_spec, $basket_id, $include_replaced = false)
  {
    global $connection;
    $transaction_info = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(bpid)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND '.NEW_TABLE_LEDGER.'.basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND '.NEW_TABLE_LEDGER.'.bpid IS NULL
        AND '.NEW_TABLE_LEDGER.'.pvid IS NULL
        AND '.NEW_TABLE_LEDGER.'.delcode_id IS NOT NULL'.
        $where_deleted.'
      ORDER BY timestamp';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 840321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transaction_info, $row);
      }
    if (count($transaction_info)) return($transaction_info);
    else return (0);
  }

// This function will get a list of transactions in a given basket_id for which there is no defined bpid (NULL)
// ... and no defined values for either pvid or delcode_id
// RETURNS: array (transaction_info)
function get_ledger_no_pvid_no_bpid ($account_spec, $basket_id, $include_replaced = false)
  {
    global $connection;
    $transaction_info = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(bpid)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND '.NEW_TABLE_LEDGER.'.basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND '.NEW_TABLE_LEDGER.'.bpid IS NULL
        AND '.NEW_TABLE_LEDGER.'.pvid IS NULL
        AND '.NEW_TABLE_LEDGER.'.delcode_id IS NULL'.
        $where_deleted.'
      ORDER BY timestamp';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 840321 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transaction_info, $row);
      }
    if (count($transaction_info)) return($transaction_info);
    else return (0);
  }




// This function will get a list of all bpid values for a given basket_id for which transactions exist
// RETURNS: array (bpid)
function get_ledger_bpid2 ($account_spec, $basket_id, $include_replaced = false)
  {
    global $connection;
    $transactions = array ();
    list($type, $key) = explode (':', $account_spec);
    if ($include_replaced == false)
      {
        $where_deleted = '
        AND replaced_by IS NULL';
      }
    $query = '
      SELECT *
      FROM '.NEW_TABLE_LEDGER.'
      LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(bpid)
      LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id, product_version)
      WHERE
        ((source_type = "'.mysql_real_escape_string($type).'"
          AND source_key = "'.mysql_real_escape_string($key).'")
        OR (target_type = "'.mysql_real_escape_string($type).'"
          AND target_key = "'.mysql_real_escape_string($key).'"))
        AND '.NEW_TABLE_LEDGER.'.basket_id = "'.mysql_real_escape_string($basket_id).'"
        AND bpid IS NOT NULL'.
        $where_deleted.'
      ORDER BY bpid';
    $result = mysql_query($query, $connection) or die(debug_print ("ERROR: 753907 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ($row = mysql_fetch_array($result))
      {
        array_push ($transactions, $row);
      }
    if (count($transactions)) return($transactions);
    else return (0);
  }

?>