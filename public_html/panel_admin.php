<?php
include_once ("config_foodcoop.php");
include_once ('general_functions.php');
session_start();
valid_auth('site_admin');


// Include messages from the localfoodcoop.org server about this version
// $curl = curl_init();
// curl_setopt ($curl, CURLOPT_URL,'www.localfoodcoop.org/updates/messages.php?version='.CURRENT_VERSION);
// curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
// curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, 5);
// $display_admin .= curl_exec($curl);
// curl_close($curl);

$display_admin .= '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
        <img src="grfx/admin.png" width="32" height="32" align="left" hspace="2" alt="Admin Maintenance"><br>
        <b>Admin Maintenance</b>
        <ul class="fancyList1">
          <!--<li class="last_of_group"><strike><a href="orders_nomatch.php">Matching baskets</a></strike></li>-->
          <!--<li class="last_of_group"><strike><a href="accounting_check.php">Find accounting discrepancies</a></strike></li>-->
          <li class="last_of_group"><a href="add_delivery_cycle.php">Add Delivery Cycle</a></li>
          <li class="last_of_group"><a href="makeMemberList.php" target="_blank">Access Membership List</a></li>
          <li class="last_of_group"><a href="category_list_edit.php">Edit Categories and Subcategories</a></li>
          <!--<li class="last_of_group"><strike><a href="prep_cycle.php">Prep Cycle</a> (be very careful with this function!)</strike></li>-->
          <!--<li><strike><a href="repeat_orders.php">Process Repeating Orders</a></strike></li>-->
          <!--<li class="last_of_group"><strike><a href="unfi_interface.php">Manage UNFI Products</a></strike></li>-->
          <li class="last_of_group"><a href="invoice_edittext.php">Edit Invoice Messages</a></li>
          <li class="last_of_group"><a href="member_form.php">Update member information</a></li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="grfx/launch.png" width="32" height="32" align="left" hspace="2" alt="Current Delivery Cycle Functions"><br>
        <b>Current Delivery Cycle Functions</b>
        <ul class="fancyList1">
          <li><a href="orders_selectmember.php">Open an Order for a Customer</a></li>
          <li><strike><a href="orders_list.php?delivery_id='.ActiveCycle::delivery_id().'">Members with orders this cycle</a></strike></li>
          <li><a href="orders_list_withtotals.php?delivery_id='.ActiveCycle::delivery_id().'">Members with orders this cycle (with totals)</a></li>
          <li><a href="members_list_emailorders.php?delivery_id='.ActiveCycle::delivery_id().'">Customer Email Addresses this cycle</a></li>
          <li><a href="orders_prdcr_list.php?delivery_id='.ActiveCycle::delivery_id().'">Producers with Customers this Cycle</a></li>
          <li class="last_of_group"><strike><a href="query_notes.php">Orders with Customer Notes</a></strike></li>
        </ul>
        <img src="grfx/kcron.png" width="32" height="32" align="left" hspace="2" alt="Previous Delivery Cycle Functions"><br>
        <b>Previous Delivery Cycle Functions</b>
        <ul class="fancyList1">
          <li class="last_of_group"><a href="generate_invoices.php">Generate Invoices</a></li>
        </ul>
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Site Admin Panel</span>';
$page_title = 'Site Admin Panel';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_admin.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
