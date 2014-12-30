<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,site_admin,cashier');


// Display this many rows at a time
if ($_GET['action'] == 'this_order') $group_size = 200;
else $group_size = 50;

if ($_GET['begin_row'])
  {
    $this_begin_row = $_GET['begin_row'];
  }
else
  {
    $this_begin_row = 0;
  }
// Find out how many members there are:
$query='
  SELECT
    MAX(member_id) AS count
  FROM
    '.TABLE_MEMBER;
$sql = mysql_query($query);
$row = mysql_fetch_array($sql);
$number_of_members = $row['count'];

// Either do a member detail lookup or...
if ($_GET['lookup'])
  {
    include("../func/member_balance_function.php");
    $member_id = preg_replace('/[^0-9]/','',$_GET['lookup']);
    $display = getMemberBalance($member_id, ActiveCycle::delivery_id(), 'display');
    $content_balance .= $display;
  }
// ...show totals for a group of members
else
  {
    include("../func/member_balance_function.php");
    $content_balance .= '<table><tr><th>Member Name</th><th>Member_ID / Username</th><th>Balance Total</th></tr>';
    // Cycle through the list of the members for this grouping
    if ($_GET['action'] == 'this_order') // only members with orders this cycle
      {
        $query='
          SELECT
            '.TABLE_MEMBER.'.member_id,
            last_name,
            first_name,
            username 
          FROM
            '.TABLE_MEMBER.'
          LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET_ALL.'.member_id = '.TABLE_MEMBER.'.member_id
          WHERE
            '.TABLE_MEMBER.'.member_id > '.$this_begin_row.'
            AND '.TABLE_MEMBER.'.member_id <= '.($this_begin_row + $group_size).'
            AND basket_id IS NOT NULL
            AND delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
          ORDER BY
            '.TABLE_MEMBER.'.member_id ASC
          LIMIT
            0, '.mysql_real_escape_string ($group_size);
      }
    else // default behavior (all members)
      {
        $query='
          SELECT
            member_id,
            last_name,
            first_name,
            username 
          FROM
            '.TABLE_MEMBER.'
          WHERE
            member_id > '.$this_begin_row.'
            AND member_id <= '.($this_begin_row + $group_size).'
          ORDER BY
            member_id ASC
          LIMIT
            0, '.mysql_real_escape_string ($group_size);
      }

    $sql = mysql_query($query);
    while($row = mysql_fetch_array($sql))
      {
        $member_id = $row['member_id'];
        $member_name = $row['first_name']." ".$row['last_name'];
        $username = $row['username'];
        $return_value = array_pop (getMemberBalance($member_id, ActiveCycle::delivery_id(), ''));
        $balance = number_format ($return_value['balance'], 2);
        $amount_paid = number_format ($return_value['amount_paid'], 2);
        if ($amount_paid != "0.00")
          {
            $paid_value = "[after paying $amount_paid]";
          }
        else
          {
            $paid_value = '';
          }
        $content_balance .= '
          <tr>
            <td>'.$member_name.'</td>
            <td>'.$member_id.' '.$username.'</td>
            <td><a href="member_balances_outstanding.php?lookup='.$member_id.'&begin_row='.$this_begin_row.'">'.$balance.' '.$paid_value.'</a></td>
          </tr>';
      }
  $content_balance .= '</table>';
  }

// Include links to groupings of members
$content_balance .= 'View members: ';
$begin_row = 0;
while ($begin_row < $number_of_members)
  {
    $end_row = $begin_row + $group_size;
    $group_begin = $begin_row + 1;

    // Truncate if over the maximum number of members
    if ($end_row > $number_of_members)
      {
        $end_row = $number_of_members;
      }

    // Provide for bolding the current choice if we are doing a detailed lookup
    $strong = ''; $not_strong = '';
    if ($begin_row == $this_begin_row)
      {
        $strong = '<strong>['; $not_strong = ']</strong>';
      }

    // Show the grouping of members
    if (($begin_row == $this_begin_row) && (! $_GET['lookup']))
      {
        $content_balance .= '
        '.$strong.$group_begin.'-'.$end_row.$not_strong.' &nbsp; ';
      }
    else
      {
        $content_balance .= '
          <a href="'.$_SERVER['PHP_SELF'].'?begin_row='.$begin_row.'&action='.$_GET['action'].'">'.$strong.$group_begin.'-'.$end_row.$not_strong.'</a> &nbsp; ';
      }
    $begin_row = $begin_row + $group_size;
  }
$content_balance .= '<br>';

$page_specific_css = '
  <style type="text/css">
  table {
    width:90%;
    border: 1px solid #000066;
    margin:0;
    }
  td {
    border: 1px solid #dddddd;
    padding:0px 5px 0px 5px;
    }
  a {
    text-decoration: none;
    color: #123456;
    }
  a:hover {
    text-decoration: underline;
    color: #a86420;
    }
  tr:hover {
    background:#f8ffe0;
    }
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Member Balances Outstanding</span>';
$page_title = 'Reports: Member Balances Outstanding';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_balance.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
