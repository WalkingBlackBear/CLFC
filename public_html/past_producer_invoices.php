<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


include("func/show_delivery_date_all.php");

$content = '
<table width="80%">
  <tr>
    <td align="left">
      <h3>All Previous and Current Producer Invoices</h3>
      <ul>
        '.$display2.'
      </ul>
    </td>
  </tr>
</table>';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Past Producer Invoices</span>';
$page_title = 'Delivery Cycle Functions: Past Producer Invoices';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
