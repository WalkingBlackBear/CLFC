<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');

$delete_window = 6000; // 100 minutes. Change also in "receive_payments_control.php
$member_id = $_POST['member_id'];
$basket_id = $_POST['basket_id'];

$sort_by = $_POST['sort_by'];
if ($sort_by == '') $sort_by = 'transaction_delivery_id';

$transaction_keys = array ();

// Set up a list of database fields for display (un/comment to de/activate fields for display)
$display_fields = array (
  'transaction_name',
  'transaction_amount',
  'running_total',
//  'transaction_basket_id',
  'transaction_delivery_id',
  'delivery_date',
//  'transaction_taxed',
  'transaction_timestamp',
//  'transaction_batchno',
//  'transaction_memo',
  'transaction_comments',
//  'transaction_method',
//  'transaction_id',
//   'transaction_type',
//  'transaction_user',
  'admin_name',
//  'transaction_producer_id',
//  'transaction_member_id',
//  'ttype_id',
//  'ttype_name',
//  'ttype_creditdebit',
//  'ttype_taxed',
  'ttype_desc',
//  'ttype_status',
//  'ttype_whereshow',
//  'ttype_value'
  );


// Review information for a particular member/basket
$query = '
  SELECT
    '.TABLE_TRANSACTIONS.'.*,
    '.TABLE_TRANS_TYPES.'.*,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_MEMBER.'.preferred_name AS admin_name
  FROM
    '.TABLE_TRANSACTIONS.'
  LEFT JOIN
    '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.TABLE_TRANSACTIONS.'.transaction_delivery_id
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.username = '.TABLE_TRANSACTIONS.'.transaction_user
  WHERE
    '.TABLE_TRANSACTIONS.'.transaction_member_id = "'.mysql_real_escape_string($_POST['member_id']).'"
    AND (
      '.TABLE_TRANS_TYPES.'.ttype_parent = "20" /* ADJUSTMENTS */
      OR '.TABLE_TRANS_TYPES.'.ttype_parent = "21" /* PAYMENTS */
      OR '.TABLE_TRANS_TYPES.'.ttype_id = "27" /* BASKET TOTAL */
      OR '.TABLE_TRANS_TYPES.'.ttype_id = "29" /* SALES TAX */
      OR '.TABLE_TRANS_TYPES.'.ttype_id = "30" /* COOP FEE */
      OR '.TABLE_TRANS_TYPES.'.ttype_id = "33"/* DELIVERY */
      OR '.TABLE_TRANS_TYPES.'.ttype_id = "32"/* HANDLING FEE */
      OR '.TABLE_TRANS_TYPES.'.ttype_id = "85"/* CASH DISCOUNT */
      )
    AND '.TABLE_TRANS_TYPES.'.ttype_whereshow = "customer"
    AND '.TABLE_TRANSACTIONS.'.transaction_replaced_by IS NULL
  ORDER BY
    '.TABLE_TRANSACTIONS.'.transaction_id';
$result = @mysql_query($query, $connection) or die("Couldn't execute select query.");
$running_total = 0;
$key_number = 1;
$sort_value = 1;

// Collect the data into a nice array
while ( $row = mysql_fetch_object($result) )
  {
    // We want to only keep the last entry for "invoice" transactions (ttype_id = 22)
    // So key all entries with a unique value except invoice transactions, which will
    // use the basket_id and transaction_type as a key. That will result in later
    // values to overwrite earlier ones. 
    if ($row->ttype_parent == 22)
      {
        $transaction_key = $row->$sort_by.'-'.$row->transaction_basket_id.'-'.$row->ttype_id;
      }
    else
      {
        $transaction_key = $row->$sort_by.'-'.$row->transaction_basket_id.'-'.$row->ttype_id.'-'.$key_number++;
      }
    // The sign of some transactions need to be inverted from the way they are stored in the database
    if ($row->transaction_type == 23) $row->transaction_amount = $row->transaction_amount * -1;

    // Save the transaction keys for later sorting
    array_push ($transaction_keys, $transaction_key);

    // Modify any data that needs it
    $row->transaction_amount = number_format ($row->transaction_amount, 2);

    // Save the data from this row
    foreach (array_values ($display_fields) as $display_field)
      {
        $transaction_data[$display_field][$transaction_key] = $row->$display_field;
      }
    // Always save the transaction_id data
    $transaction_data['transaction_id'][$transaction_key] = $row->transaction_id;
    // And the transaction_timestamp data
    $transaction_data['transaction_timestamp'][$transaction_key] = $row->transaction_timestamp;
  }

// Set up the header row
$review_data = '
      <div id="review_header" class="review_member">
        <span class="review_data header review_ctl">*</span>';
foreach (array_values ($display_fields) as $display_field)
  {
    $header_display = strtr ($display_field, '_', ' ');
    $header_display = str_replace ('transaction ', '', $header_display);
    $header_display = str_replace ('ttype ', '', $header_display);
    $sort_script = '';
    $sortable_class = '';
    if ($display_field != 'running_total' && $display_field != 'transaction_names')
      {
        $sort_script = ' onClick=review_member(\''.$member_id.'\',\''.$basket_id.'\',\''.$display_field.'\')';
        $sortable_class = ' sortable';
      }
    $review_data .= '
        <span class="review_data header '.$display_field.$sortable_class.'"'.$sort_script.'>'.$header_display.'</span>';
  }
$review_data .= '
      </div>
      <div id="review_content">';

// Sort the data differently, depending on the column being sorted
if ($sort_by == 'transaction_amount'
  || $sort_by == 'transaction_delivery_id'
  || $sort_by == 'transaction_basket_id'
  || $sort_by == 'transaction_batchno'
  || $sort_by == 'transaction_id'
  || $sort_by == 'transaction_member_id')
  {
    sort ($transaction_keys, SORT_NUMERIC);
  }
else
  {
    sort ($transaction_keys, SORT_STRING);
  }

// Display the data row(s)
foreach (array_values (array_unique ($transaction_keys)) as $key)
  {
    // Modify any data that needs it
    $running_total = $running_total + $transaction_data['transaction_amount'][$key];
    // This is a generated field
    $transaction_data['running_total'][$key] = number_format (round ($running_total, 2), 2);

    $transaction_id = $transaction_data['transaction_id'][$key];
    $basket_id = $transaction_data['transaction_basket_id'][$key];
    $transaction_delivery_id = $transaction_data['transaction_delivery_id'][$key];
    if ($transaction_delivery_id != $transaction_delivery_id_prior)
      {
        $review_data .= '
          <div class="divider"></div>';
      }
    $review_data .= '
      <div id="transaction_'.$transaction_data['transaction_id'][$key].'" class="review_member" onClick="modify_account(\''.$member_id.'\', \''.$transaction_delivery_id.'\')">
        <span class="review_data review_ctl">'.
        (time() - strtotime ($transaction_data['transaction_timestamp'][$key]) < $delete_window ? '<img class="delete_button" src="grfx/delete_button.png" onClick="delete_transaction(\''.$member_id.'\',\''.$transaction_id.'\',\''.$basket_id.'\',\''.$sort_by.'\')">' : '').
        '</span>';
    foreach (array_values ($display_fields) as $display_field)
      {
        $review_data .= '
        <span class="review_data '.$display_field.'">'.$transaction_data[$display_field][$key].'</span>';
      }
    $review_data .= '
      </div>';
    $transaction_delivery_id_prior = $transaction_delivery_id;
  }
$review_data .= '
      </div>';

echo $review_data;

?>