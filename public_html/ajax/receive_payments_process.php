<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');

$delete_window = 6000; // 100 minutes. Change also in "receive_payments_review.php
$member_id = $_POST['member_id'];
$transaction_id = $_POST['transaction_id'];
$transaction_user = $_POST['transaction_user'];
$action = $_POST['action'];

if ($action == 'delete transaction')
  {
    $query = '
      DELETE FROM
        '.TABLE_TRANSACTIONS.'
      WHERE
        transaction_id = "'.mysql_real_escape_string($transaction_id).'"
        AND TIMESTAMPDIFF(SECOND, transaction_timestamp, NOW()) < "'.mysql_real_escape_string($delete_window).'"';
//      AND transaction_user = "'.mysql_real_escape_string($transaction_user).'"
    $result = @mysql_query($query, $connection) or die("Couldn't execute select query.");
    $affected_rows = mysql_affected_rows();
    if ($affected_rows == 1)
      {
        $process_data = 'Transaction '.$transaction_id.' was successfully deleted.';
      }
    else
      {
        $process_data = 'ERROR: Transaction '.$transaction_id.' was NOT deleted.';
      }
  }


echo $process_data;
?>