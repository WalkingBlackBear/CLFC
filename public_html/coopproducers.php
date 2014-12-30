<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,producer_admin');


include ("func/prdcr_contact_info_admin.php");

$query = '
  SELECT
    COUNT(producer_id) AS count
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    unlisted_producer = "0"';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 427857 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$pid_count = mysql_result($result, 0, 'count');
$pid_half = ceil ($pid_count / 2);

$content_list = '
<table class="center">
  <tr>
    <td colspan="2" align="center">
      <h3>'.$pid_count.' Producers</h3>
      Click here for <a href="prdcr_list.php"><b>Further details about each producer</b></a>
      <br>Contact us at <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if your contact information needs to be updated.
      <br><br>
    </td>
  </tr>
  <tr>
    <td valign="top" align="left">
      '.prdcr_contact_info (0, $pid_half).'
    </td>
    <td valign="top" align="left">
      '.prdcr_contact_info ($pid_half, $pid_count).'
    </td>
  </tr>
</table>';

$page_specific_css .= '
<style type="text/css">
table.center {
  margin:auto;
  }
</style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Producer Contact Info.</span>';
$page_title = 'Reports: Producer Contact Info.';
$page_tab = 'producer_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
