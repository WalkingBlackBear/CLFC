<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');

// Get a list of members who ordered for a particular delivery_id (date) and delcode_id (location)
$where_condition = 'delivery_id = "'.mysql_real_escape_string ($_POST['delivery_id']).'"';
if ($_POST['delcode_id'] != '*')
  $where_condition .= '
    AND delcode_id = "'.mysql_real_escape_string ($_POST['delcode_id']).'"';

$query = '
  SELECT
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_MEMBER.'.membership_date,
    '.TABLE_MEMBER.'.last_renewal_date,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_BASKET_ALL.'.basket_id,
    COUNT('.TABLE_BASKET.'.bpid) AS basket_items
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
  LEFT JOIN
    '.TABLE_BASKET.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  WHERE
    '.$where_condition.'
  GROUP BY
    '.TABLE_BASKET.'.basket_id
  ORDER BY
    '.TABLE_MEMBER.'.preferred_name';
$result = @mysql_query($query, $connection) or die("Couldn't execute select query.");
$select_list = '
      <div class="select_header_row">
        <span class="preferred_name select_header">Preferred Name / Member ID</span>
        <span class="basket_items select_header">Basket</span>
        <span class="membership_date select_header">Join Date</span>
        <span class="last_renewal_date select_header">Last Renewal</span>
      </div>';
while ( $row = mysql_fetch_object($result) )
  {
    $select_list .= '
      <div id="member_basket_'.$row->member_id.'-'.$row->basket_id.'" class="select_data_row" onClick="review_member('.$row->member_id.','.$row->basket_id.',\'transaction_delivery_id\')">
        <span class="preferred_name select_data">'.$row->preferred_name.' #'.$row->member_id.'</span>
        <span class="basket_items select_data">'.$row->basket_items.' items</span>
        <span class="membership_date select_data">'.$row->membership_date.'</span>
        <span class="last_renewal_date select_data">'.$row->last_renewal_date.'</span>
      </div>';
  }

echo $select_list;
?>