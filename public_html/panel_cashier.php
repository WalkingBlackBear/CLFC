<?php
include_once ("config_foodcoop.php");
include_once ('general_functions.php');
session_start();
valid_auth('site_admin,cashier');


$display_cashier = '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
        <img src="grfx/report.png" width="32" height="32" align="left" hspace="2" alt="Manage products"><br>
        <b>Reports</b>
        <ul class="fancyList1">
          <li class="last_of_group"><a href="view_balances3.php">View Ledger</a></li>
<!--          <li><strike><a href="member_balances_lookup.php">Member Balances Look-up</a></strike></li>
          <li><strike><a href="member_balances_outstanding.php">Member Balances Outstanding</a> (slow)</strike></li>
          <li class="last_of_group"><strike><a href="member_balances_outstanding.php?action=this_order">Member Balances (this order)</strike></a></li>
          <li><strike><a href="report_financial.php">Financial Report</a></strike></li>
          <li class="last_of_group"><strike><a href="transaction_report.php">Transaction Report</a></strike></li>
          <li><strike><a href="salestax.php">Sales Tax Breakdown</a></strike></li>
          <li><strike><a href="orders_perhub.php">Orders and Sales per Hub</a></strike></li>
          <li class="last_of_group"><strike><a href="report_per_subcat.php">Sales Per Subcategory</a></strike></li>
          <li><strike><a href="report.php">Sales Reports</a></strike></li>
          <li><strike><a href="totalsbylocation.php">Food Types By Location</a></strike></li>
          <li class="last_of_group"><strike><a href="totals_saved.php?delivery_id='.ActiveCycle::delivery_id().'">Customer Totals Report</a></strike></li>-->
        </ul>
        <img src="grfx/ksirc.png" width="32" height="32" align="left" hspace="2" alt="Helpful PDF Forms for Download"><br>
        <b>Helpful PDF Forms for Download</b>
        <ul class="fancyList1">
          <li><a href="pdf/payments_received.pdf" target="_blank">Payments Received Form</a></li>
          <li class="last_of_group"><a href="pdf/invoice_adjustments.pdf" target="_blank">Invoice Adjustments Chart</a></li>
        </ul>
      </td>
      <td align="left" width="50%">
        <img src="grfx/kspread.png" width="32" height="32" align="left" hspace="2" alt="Treasurer Functions"><br>
        <b>Treasurer Functions</b>
        <ul class="fancyList1">
          <li><strike><a href="finalizep.php?delivery_id='.ActiveCycle::delivery_id().'">Finalize Producer Invoices</a></strike></li>
          <li class="last_of_group"><strike><a href="unfinalized.php">All Previous Unfinalized Invoices</a></strike></li>
          <li><strike><a href="adjustments.php">Invoice Adjustments</a></strike></li>
          <li><a href="receive_payments.php">Receive Payments (for orders)</a>
          <li class="last_of_group"><a href="receive_payments_bymember.php">Receive Payments (by member)</a>
        </ul>
        <img src="grfx/kcron.png" width="32" height="32" align="left" hspace="2" alt="Delivery Cycle Functions"><br>
        <b>Delivery Cycle Functions</b>
        <ul class="fancyList1">
          <li class="last_of_group"><strike><a href="orders_selectmember.php">Open an Order for a Customer</a></strike></li>
          <li><a href="past_customer_invoices.php">Past Customer Invoices</a></li>
          <li class="last_of_group"><a href="past_producer_invoices.php">Past Producer Invoices</a></li>
          <li class="last_of_group"><a href="generate_invoices.php">Generate Invoices</a></li>
        </ul>
<!--        <img src="grfx/foodstamps.png" width="32" height="32" align="left" hspace="2" alt="Food Stamps"><br>
        <b>Food Stamps</b>
        <ul class="fancyList1">
          <li><a href="foodstamps.php?fs=3">Food Stamp Designations</a></li>
          <li><a href="foodstamps_bylocation.php">Food Stamps By Location</a></li>
          <li class="last_of_group"><a href="foodstamps_updatepast.php">Staple/Retail/Nonfood Totals</a> (Very slow)</li>
        </ul>
-->
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Cashier Panel</span>';
$page_title = 'Cashier Panel';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_cashier.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
