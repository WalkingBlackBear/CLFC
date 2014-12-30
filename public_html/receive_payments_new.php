<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');

// Get list of delivery dates (default to the current one):
$sql = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  ORDER BY
    delivery_id DESC';
$result = @mysql_query($sql, $connection) or die("Couldn't execute delivery_id query.");
$delivery_id_options = '';
while ( $row = mysql_fetch_object($result) )
  {
    $selected = '';
    if ($row->delivery_id == ActiveCycle::delivery_id()) $selected = ' selected';
    $delivery_id_options .= '
      <option value="'.$row->delivery_id.'"'.$selected.'>'.date ('M d, Y', strtotime($row->delivery_date)).'</option>';
  }

// Get list of delivery codes (default to ALL):
$sql = '
  SELECT
    delcode_id,
    delcode
  FROM
    '.TABLE_DELCODE.'
  ORDER BY
    delcode ASC';
$result = @mysql_query($sql, $connection) or die("Couldn't execute delcode_id query.");
$delcode_id_options = '
      <option value="*" selected>All locations</option>';
while ( $row = mysql_fetch_object($result) )
  {
    $delcode_id_options .= '
      <option value="'.$row->delcode_id.'">'.$row->delcode.'</option>';
  }

$content.= '
  <div id="control_container">
    Delivery Date: 
    <select name="delivery_id" id="select_delivery_id" onChange="get_select_list();">
      '.$delivery_id_options.'
    </select>
    Delivery Location: 
    <select name="delcode_id" id="select_delcode_id" onChange="get_select_list();">
      '.$delcode_id_options.'
    </select>
  </div>
  <div id="select_container">
    Select an order cycle and location from the options above to review accounting information for
    customers who had orders open for those cycles. Then click on the customer to review the detailed
    accounting information.
  </div>
  <div id="review_container">
  </div>
  <div id="process_container">
    Use controls to add/modify accounting information
  </div>';


$page_specific_css .= '
  <style type="text/css">
    a {
      outline:0;
      }

/**** CSS FOR SELECT CONTAINER ****/
    #select_container {
      border:1px solid black;
      padding:0em;
      width:90%;
      margin:1em auto;
      max-height:200px;
      /* height:200px; */
      overflow-y:auto;
      }
    .select_header {
      text-align:left;
      display:table-cell;
      padding:0 1em;
      color:#fe8;
      font-weight:bold;
      }
    .select_header_row {
      background-color:#432
      }
    .select_data_row {
      }
    .select_data_row:hover {
      background-color:#eca;
      }
    .preferred_name {
      width:325px;
      min-width:325px;
      }
    .basket_items {
      width:75px;
      min-width:75px;
      }
    .membership_date,
    .last_renewal_date {
      width:130px;
      min-width:130px;
      }
    .select_data {
      text-align:left;
      display:table-cell;
      padding:0 1em;
      color:#753;
      }

/**** CSS FOR REVIEW CONTAINER ****/
    #review_container {
      border:1px solid black;
      padding:0em;
      width:90%;
      margin:1em auto;
      /* overflow:auto; */
      max-height:500px;
      /* height:500px; */
      overflow-x:auto;
      overflow-y:hidden;
      }
    #review_header {
      text-align:center;
      float:left;
      padding:0em;
      max-height:38px;
      background-color:#234;
      overflow-x:hidden;
      }
    #review_content {
      float:left;
      padding:0em;
      margin:0em auto;
      max-height:342px;
      /* height:342px; */
      border-bottom:1px solid #234;
      overflow-y:auto;
      overflow-x:hidden;
      }
    .header {
      color:#fe8;
      font-weight:bold;
      }
    .review_member {
      text-align:left;
      }
    .review_member:hover {
      background-color:#def;
      }
    .review_data {
      display:table-cell;
      padding:0 1em;
      width:80px;
      min-width:80px;
      }
    .grouped_data {
      border-top:1px solid #eee;
      }
    .ungrouped_data {
      border-top:1px solid #abc;
      }
    .transaction_type {
      display:table-cell;
      padding:0 1em;
      width:80px;
      min-width:80px;
      border:1px solid #eca;
      }
    .transaction_comments,
    .ttype_desc,
    .admin_name,
    .transaction_timestamp {
      width:150px;
      min-width:150px;
      }
    .transaction_delivery_id {
      width:50px;
      min-width:50px;
      }
    .review_ctl {
      width:20px;
      min-width:20px;
      }
    .memo {
      width:120px;
      min-width:120px;
      }
    .comments {
      width:150px;
      min-width:150px;
      }
    .delete_button {
      margin:6px auto;
      }
    .divider {
      height:2px;
      width:100%;
      background-color:#777;
      }
    .sortable {
      text-decoration:underline;
      }

/**** CSS FOR PROCESS CONTAINER ****/
    #process_container {
      border:1px solid black;
      padding:0em;
      width:90%;
      margin:1em auto;
      }
    .warn {
      color:#600;
      width:75%;
      margin:auto;
      }

/**** CSS FOR OTHER ITEMS ****/
    .activity {
      margin:20px auto;
      }
    .adjustment_form {
      height:2.3em;
      background-color:#aca;
      }
  </style>
';

$page_specific_javascript .= '
  <script type="text/javascript" src="'.PATH.'ajax/jquery.js"></script>
  <script>
  function get_select_list () {
    // Clear contents and show activity indicator
    document.getElementById("select_container").innerHTML = "<img class=\"activity\" src=\"/shop/members/grfx/activity.gif\">";
    $.post("'.PATH.'ajax/receive_payments_select_new.php", {
        delivery_id: $("#select_delivery_id").val(),
        delcode_id: $("#select_delcode_id").val(),
        },
      function(select_list) {
        if (select_list == "error") {
          alert ("Update failed");
          }
        else {
          // This is where we have posted data and it was successful
          document.getElementById("select_container").innerHTML = select_list;
          }
        })
    }

  function review_member (member_id, basket_id, sort_by) {
    // Reset the old active target, if there is one
    if (document.old_target) $("div#"+document.old_target).css({"background-color":"#fff","border":"0"});
    // Highlight the new active target
    document.active_target = "member_basket_"+member_id+"-"+basket_id;
    $("div#"+document.active_target).css({"background-color":"#eca","border-bottom":"1px solid #642","border-top":"1px solid #642"});
    // Set the new "old" target
    document.old_target = document.active_target

    // Clear contents and show activity indicator
    document.getElementById("review_container").innerHTML = "<img class=\"activity\" src=\"/shop/members/grfx/activity.gif\">";
    $.post("'.PATH.'ajax/receive_payments_review_new.php", {
        member_id: member_id,
        basket_id: basket_id,
        sort_by: sort_by
        },
      function(review_data) {
        if (review_data == "error") {
          alert ("Failed to receive member information");
          }
        else {
          // This is where we have posted data and it was successful
          document.getElementById("review_container").innerHTML = review_data;
          }
        })
    }

  function modify_account (member_id, delivery_id) {
//     document.getElementById("review_container").innerHTML = "<img class=\"activity\" src=\"/shop/members/grfx/activity.gif\">";
//     $.post("'.PATH.'ajax/receive_payments_review_new.php", {
//         member_id: member_id,
//         sort_by: sort_by
//         },
//       function(review_data) {
//         if (review_data == "error") {
//           alert ("Failed to receive member information");
//           }
//         else {
//           // This is where we have posted data and it was successful
//           document.getElementById("review_container").innerHTML = review_data;
//           }
//         })
    }

  function delete_transaction (member_id, transaction_id, basket_id, sort_by) {
    okay_to_delete = confirm ( "Confirm deletion of transaction "+transaction_id);
    if (okay_to_delete == true) {
    $.post("'.PATH.'ajax/receive_payments_process_new.php", {
        member_id: member_id,
        transaction_id: transaction_id,
        action: "delete transaction"
        },
      function(process_data) {
        document.getElementById("process_container").innerHTML = process_data;
        })
      review_member (member_id, basket_id, sort_by);
      }
    else {
      document.getElementById("process_container").innerHTML = "Deletion aborted by user";
      }
    }
  </script>';

// Set up the header display information
$page_title = 'Accounting Review/Update';
$page_title_html = '<span class="title">Accounting Review/Update Interface</span>';
$page_subtitle = 'Investigate and modify customer accounts';
$page_subtitle_html = '';

include("template_header.php");
echo $content;
include("template_footer.php");
