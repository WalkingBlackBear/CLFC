<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


include("../func/show_delivery_date_all.php");

$content_saved = '
<ul>
  <table cellspacing="0" cellpadding="3" border="0">
    '.$display_totals.'
  </table>
</ul>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Previous Customer Totals</span>';
$page_title = 'Reports: Previous Customer Totals';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_saved.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
