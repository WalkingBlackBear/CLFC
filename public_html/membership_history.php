<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier,member_admin');


$query = '
  SELECT
    '.TABLE_TRANSACTIONS.'.*,
    '.TABLE_MEMBER.'.membership_date AS membership_date,
    '.TABLE_MEMBER.'.last_renewal_date AS last_renewal_date,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name_2,
    '.TABLE_MEMBER.'.last_name_2,
    '.TABLE_MEMBER.'.business_name
  FROM
    '.TABLE_TRANSACTIONS.'
  LEFT JOIN '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
  LEFT JOIN '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_TRANSACTIONS.'.transaction_member_id
  WHERE
    ttype_parent = 40
    AND transaction_member_id = '.$_GET['member_id'].'
  ORDER BY
    transaction_timestamp';

$sql = @mysql_query($query, $connection) or die("You found a bug. <b>Error:</b>
  Membership info query " . mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ($row = mysql_fetch_object ($sql))
  {
    $first_name = $row->first_name;
    $last_name = $row->last_name;
    $first_name_2 = $row->first_name_2;
    $last_name_2 = $row->last_name_2;
    $business_name = $row->business_name;
    include ("../func/show_name.php");
    if ($first_time_through ++ < 1)
      {
        $report .= '
          <table border="0" cellspacing="0" align="center" width="95%">
            <tr>
              <th class="icell" colspan="6">Membership Payment History for<br>'.$show_name.'</th>
            </tr>
            <tr>
              <th class="hcell al">Date Posted</th>
              <th class="hcell al">Type</th>
              <th class="hcell ar">Amount</th>
              <th class="hcell al">Memo</th>
              <th class="hcell al">Comments</th>
              <th class="hcell ar">Running Total</th>
            </tr>';
      }

    $membership_total += $row->transaction_amount;
    $report .= '
    <tr>
      <td class="bcell al">'.date ('M j, Y', strtotime ($row->transaction_timestamp)).'</td>
      <td class="bcell al">'.$row->transaction_name.'</td>
      <td class="bcell ar">'.$row->transaction_amount.'</td>
      <td class="bcell al">'.$row->transaction_memo.'</td>
      <td class="bcell al">'.$row->transaction_comments.'</td>
      <td class="bcell ar">'.number_format ($membership_total, 2).'</td>
    </tr>
    ';
  $membership_date = $row->membership_date;
  $last_renewal_date = $row->last_renewal_date;
  }
$report .= '
    <tr>
      <td class="icell" colspan="6">
        <strong>Original Membership Date:</strong> '.date ('M j, Y', strtotime ($membership_date)).'<br>
        <strong>Last Renewal Date:</strong> '.date ('M j, Y', strtotime ($last_renewal_date)).'<br><br>
      </td>
    </tr>
  </table>';

$page_specific_css = '
  <style type="text/css">
    .hcell {
      background-color:#004;
      color:#ffe;;
      padding:0.2em 1em 0.2em 1em;
      }
    .icell {
      border: 1px solid #888;
      background-color:#ffd;
      padding:1em 1em 1em 1em;
      }
    .bcell {
      border: 1px solid #888;
      background-color:#fff;
      padding:0.2em 1em 0.2em 1em;
      }
    .ar {
      text-align:right;
      }
    .al {
      text-align:left;
      }
    .ac {
      text-align:center;
      }
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Membership History for '.$show_name.'</span>';
$page_title = 'Reports: Membership History for '.strip_tags ($show_name);
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$report.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

