<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,producer_admin');


include("func/prdcr_labels_admin.php");

$sql = '
  SELECT
    COUNT(producer_id) AS count
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    unlisted_producer = "0"';
$result = mysql_query($sql) or die("Couldn't execute query.");
$row = mysql_fetch_array($result);
$pid_count = $row['count'];
$pid_half = ceil($pid_count/2);

$content_label .= '
<table width="100%" cellspacing="15" cellpadding="1">
  <tr>
    <td colspan="3" align="center">
      <h3>Producer Contact Info for Mailing Labels: '.$pid_count.' Producers</h3>
    </td>
  </tr>
  <tr>
    <td valign="top" align="left" width="50%">'.prdcr_contact_info(0, $pid_half).'</td>
    <td bgcolor="#000000" width="2"></td>
    <td valign="top" align="left" width="50%">'.prdcr_contact_info($pid_half, $pid_count).'</td>
  </tr>
</table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Producer Labels</span>';
$page_title = 'Reports: Producer Labels';
$page_tab = 'producer_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_label.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
