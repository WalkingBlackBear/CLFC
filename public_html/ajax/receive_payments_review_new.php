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
  'name',
  'amount',
  'method',
  'running_total',
//  'grouped_with',
//  'basket_id',
  'delivery_id',
  'delivery_date',
//  'taxed',
//  'timestamp',
//  'batchno',
  'memo',
  'comments',
//  'id',
  'type',
//  'user',
//  'admin_name'
//  'producer_id',
//  'member_id',
  );


// Review information for a particular member/basket
$query = '
  SELECT
    '.'transactions2'.'.*,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_MEMBER.'.preferred_name AS admin_name
  FROM
    '.'transactions2'.'
  LEFT JOIN
    '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.'transactions2'.'.delivery_id
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.username = '.'transactions2'.'.user
  WHERE
    '.'transactions2'.'.member_id = "'.mysql_real_escape_string($_POST['member_id']).'"
    AND '.'transactions2'.'.replaced_by = "0"
  ORDER BY
    transactions2.delivery_id,
    transactions2.basket_id,
    transactions2.grouped_with';
// echo "<pre>$query</pre>";
$result = @mysql_query($query, $connection) or die("Couldn't execute review query.");
$running_total = 0;
$key_number = 1;
$sort_value = 1;

// Collect the data into a nice array
while ( $row = mysql_fetch_object($result) )
  {
    $transaction_key = money_format('%=0#6.0n', $row->delivery_id).'-'.$row->grouped_with.'-'.$row->$sort_by.'-'.$row->basket_id.'-'.$key_number++;

    // The sign of some transactions need to be inverted from the way they are stored in the database
    // if ($row->transaction_type == 23) $row->transaction_amount = $row->transaction_amount * -1;


    // Save the transaction keys for later sorting
    array_push ($transaction_keys, $transaction_key);

    // Modify any data that needs it
    $row->amount = number_format ($row->amount, 2);

    // Save the data from this row
    foreach (array_values ($display_fields) as $display_field)
      {
        $transaction_data[$display_field][$transaction_key] = $row->$display_field;
      }
    // Always save the transaction_id data
    $transaction_data['transaction_id'][$transaction_key] = $row->tid;
    // And the timestamp data
    $transaction_data['timestamp'][$transaction_key] = $row->timestamp;
    // And the grouped_with data
    $transaction_data['grouped_with'][$transaction_key] = $row->grouped_with;
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
// if ($sort_by == 'amount'
//   || $sort_by == 'delivery_id'
//   || $sort_by == 'basket_id'
//   || $sort_by == 'batchno'
//   || $sort_by == 'id'
//   || $sort_by == 'member_id')
//   {
//     sort ($transaction_keys, SORT_NUMERIC);
//   }
// else
//   {
//     sort ($transaction_keys, SORT_STRING);
//   }

sort ($transaction_keys);

// Display the data row(s)
foreach (array_values (array_unique ($transaction_keys)) as $key)
  {
    // Modify any data that needs it
    $running_total = $running_total + $transaction_data['amount'][$key];
    // This is a generated field
    $transaction_data['running_total'][$key] = number_format (round ($running_total, 2), 2);

    $transaction_id = $transaction_data['tid'][$key];
    $basket_id = $transaction_data['basket_id'][$key];
    $transaction_delivery_id = $transaction_data['delivery_id'][$key];
    if ($transaction_delivery_id != $transaction_delivery_id_prior)
      {
        $review_data .= '
          <div class="divider"></div>';
      }
    $grouping_class = 'ungrouped_data';
    if ($transaction_data['grouped_with'][$key] == $group_with_prior)
      {
        $grouping_class = 'grouped_data';
      }
    $review_data .= '
      <div id="transaction_'.$transaction_data['tid'][$key].'" class="review_member" onClick="modify_account(\''.$member_id.'\', \''.$delivery_id.'\')">
        <span class="review_data '.$grouping_class.' review_ctl">'.
        (time() - strtotime ($transaction_data['timestamp'][$key]) < $delete_window ? '<img class="delete_button" src="grfx/delete_button.png" onClick="delete_transaction(\''.$member_id.'\',\''.$tid.'\',\''.$basket_id.'\',\''.$sort_by.'\')">' : '').
//        $transaction_data['grouped_with'][$key].'-'.$group_with_prior.'-'.$grouping_class.
//        $key.
        '</span>';
    foreach (array_values ($display_fields) as $display_field)
      {
        $review_data .= '
        <span class="review_data '.$grouping_class.' '.$display_field.'">'.$transaction_data[$display_field][$key].'</span>';
      }
    $group_with_prior = $transaction_data['grouped_with'][$key];
    $review_data .= '
      </div>';
    $transaction_delivery_id_prior = $transaction_delivery_id;
  }
$review_data .= '
      </div>';

echo $review_data;

?>