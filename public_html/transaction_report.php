<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


$content_report .= '<div align="center">';
function getTransactions($search_criteria)
  {
    $start_date = $search_criteria['year1'].'-'.$search_criteria['month1'].'-'.$search_criteria['day1'];
    $months28 = array(2);
    $months30 = array(4, 6, 9, 11);
    $months31 = array(1, 3, 5, 7, 8, 10, 12);
    if ( $search_criteria['day2'] == 31 || ($search_criteria['day2'] == 30 && in_array($search_criteria['month2'],$months30)) )
      {
        $end_day = '01';
        $end_month = $search_criteria['month2'] + 1;
      }
    else
      {
        $end_day = $search_criteria['day2'] + 1;
        $end_month = $search_criteria['month2'];
      }
    $end_date = $search_criteria['year2'].'-'.$end_month.'-'.$end_day;
    foreach( $search_criteria as $fieldname=>$value )
      {
        $haystack = array('month1','year1','day1','month2','year2','day2');
        if ( !in_array($fieldname, $haystack) && $value )
          {
            if ( $fieldname == 'transaction_type' )
              {
                if ( $value[0] )
                  {
                    foreach( $value as $transaction_type )
                      {
                        $sql = mysql_query('
                          SELECT
                            ttype_id
                          FROM
                            '.TABLE_TRANS_TYPES.'
                          WHERE
                            ttype_parent="'.mysql_real_escape_string ($transaction_type).'"');
                        if ( mysql_num_rows($sql) > 0 )
                          {
                            while ( $row = mysql_fetch_array($sql) )
                              {
                                $wherestatement .= mysql_real_escape_string ($fieldname).' = "'.mysql_real_escape_string ($row['ttype_id']).'" OR ';
                              }
                          }
                        else
                          {
                            $wherestatement .= mysql_real_escape_string ($fieldname).' = "'.mysql_real_escape_string ($transaction_type).'" OR ';
                          }
                      }
                    $wherestatement = '('.substr($wherestatement, 0, -3).') AND ';
                  }
              }
            else
              {
                $wherestatement .= mysql_real_escape_string ($fieldname).' = "'.mysql_real_escape_string ($value).'" AND ';
              }
          }
      }
    if ( $start_date )
      {
        $wherestatement .= 'transaction_timestamp >= "'.mysql_real_escape_string ($start_date).'" AND ';
      }
    if ( $end_date )
      {
        $wherestatement .= 'transaction_timestamp <= "'.mysql_real_escape_string ($end_date).'" AND ';
      }
    if ( $wherestatement )
      {
        $wherestatement = 'WHERE '.substr($wherestatement, 0, -4);
      }
    $sql = mysql_query('
      SELECT
        *
      FROM
        '.TABLE_TRANSACTIONS.' t
      '.$wherestatement.'
      GROUP BY
        transaction_id');
    while ( $row = mysql_fetch_array($sql) )
      {
        $transactions[] = $row;
      }
    return $transactions;
  }

function getTransactionsTypes()
  {
    if ( $_POST['search_criteria']['transaction_type'] )
      {
        foreach( $_POST['search_criteria']['transaction_type'] as $value )
          {
            $selected[$value] = 'SELECTED';
          }
      }
    $sql = mysql_query('
      SELECT
        *
      FROM
        '.TABLE_TRANS_TYPES.'
      WHERE
        ttype_parent="0"
      ORDER BY
        ttype_name ASC');
    while ( $row = mysql_fetch_array($sql) )
      {
        $transaction_types .= '
          <option value="'.$row['ttype_id'].'" '.$selected[$row['ttype_id']].'>'.$row['ttype_name'].'</option>';
        $sql2 = mysql_query('
          SELECT
            *
          FROM
            '.TABLE_TRANS_TYPES.'
          WHERE
            ttype_parent="'.$row["ttype_id"].'"
          ORDER BY
            ttype_name ASC');
        while ( $row2 = mysql_fetch_array($sql2) )
          {
            $transaction_types .= '
              <option value="'.$row2['ttype_id'].'" '.$selected[$row2['ttype_id']].'> - '.$row2['ttype_name'].'</option>';
          }
      }
    return $transaction_types;
  }
function getTransactionsTypeName($ttype_id)
  {
    $sql = mysql_query('
      SELECT
        ttype_name
      FROM
        '.TABLE_TRANS_TYPES.'
      WHERE
        ttype_id="'.mysql_real_escape_string ($ttype_id).'"
      LIMIT 1');
    $row = mysql_fetch_array($sql);
    return $row['ttype_name'];
  }
function listYears()
  {
    // prints a line for each month 1-12

    $sql = mysql_query('
      SELECT
        MIN(DISTINCT(SUBSTR(delivery_date, 1, 4))) AS first_year
      FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE 1');
    $row = mysql_fetch_array($sql);

    for( $i = $row['first_year']; $i <= date('Y'); $i++ )
      {
        $year_list .= '
          <option value="'.$i.'">'.$i.'</option>';
      }
    return $year_list;
  }
function listMonths()
  {
    // prints a line for each month 1-12
    for( $i = 1;$i <= 12; $i++ )
      {
        if ( $i < 10 )
          {
            $i = '0'.$i;
          }
        $month_list .= '
          <option value="'.$i.'">'.$i.'</option>';
      }
    return $month_list;
  }
function listDays()
  {
    // prints a line for each month 1-12
    for( $i = 1; $i <= 31; $i++ )
      {
        if ( $i < 10 )
          {
            $i = '0'.$i;
          }
        $day_list .= '
          <option value="'.$i.'">'.$i.'</option>';
      }
    return $day_list;
  }
function getPaymentMethods()
  {
    $sql = mysql_query('
      SELECT
        payment_method
      FROM
        payment_method');
    while ( $row = mysql_fetch_array($sql) )
      {
        $payment_methods[] = $row['payment_method'];
      }
    return $payment_methods;
  }
if ( $_POST )
  {
    $transactions = getTransactions($_POST['search_criteria']);
  }
$content_report .= '
<h2>Transaction Report</h2>
<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<table style="border:1px solid;font-family:Verdana;font-size:10pt;">
  <tr style="background-color:#EEEEEE;">
    <td rowspan="2">
      Search by:
      <select name="search_criteria[transaction_type][]" size="6" multiple>
        <option value="">All Transaction Types</option>
        '.getTransactionsTypes().'
      </select>
    </td>
    <td align="center">
      Member ID: <input type="text" name="search_criteria[transaction_member_id]" value="'.$_POST['search_criteria']['transaction_member_id'].'" size="4" maxlength="8">
      Delivery ID: <input type="text" name="search_criteria[transaction_delivery_id]" value="'.$_POST['search_criteria']['transaction_delivery_id'].'" size="4" maxlength="4">
      Batch No.: <input type="text" name="search_criteria[transaction_batchno]" value="'.$_POST['search_criteria']['transaction_batchno'].'" size="6" maxlength="8">';
$payment_methods = getPaymentMethods();
if ( is_array($payment_methods) )
  {
    $content_report .= 'Payment Method:
      <select name="search_criteria[transaction_method]">';
    if ( $_POST['search_criteria']['transaction_method'] )
      {
        $content_report .= '
          <option value="'.$_POST['search_criteria']['transaction_method'].'">'.$_POST['search_criteria']['transaction_method'].'</option>';
      }
    $content_report .= '
      <option value="">All</option>';
    foreach($payment_methods as $key=>$method)
      {
        $content_report .= '
          <option value="'.$method.'">'.$method.'</option>';
      }
    $content_report .= '
    </select>';
  }
$content_report .= '
    </td>
  </tr>
  <tr style="background-color:#EEEEEE;">
    <td align="center">
      Date:
      <select size="1" name="search_criteria[month1]">';
if ( $_POST['search_criteria']['month1'] )
  {
    $content_report .= '
        <option value="'.$_POST['search_criteria']['month1'].'">'.$_POST['search_criteria']['month1'].'</option>';
  }
else
  {
    $content_report .= '
        <option value="'.date('m').'">'.date('m').'</option>';
  }
$content_report .= listMonths().'
      </select>
      <select size="1" name="search_criteria[day1]">';
if ( $_POST['search_criteria']['day1'] )
  {
    $content_report .= '
        <option value="'.$_POST['search_criteria']['day1'].'">'.$_POST['search_criteria']['day1'].'</option>';
  }
else
  {
    $content_report .= '
        <option value="'.date('d').'">'.date('d').'</option>';
  }
$content_report .= listDays().'
      </select>
      <select size="1" name="search_criteria[year1]">';
if ( $_POST['search_criteria']['year1'] )
  {
    $content_report .= '
        <option value="'.$_POST['search_criteria']['year1'].'">'.$_POST['search_criteria']['year1'].'</option>';
  }
else
  {
    $content_report .= '
        <option value="'.date('Y').'">'.date('Y').'</option>';
  }
$content_report .= listYears().'
      </select>
      through
      <select size="1" name="search_criteria[month2]">';
if ( $_POST['search_criteria']['month2'] )
  {
    $content_report .= '  <option value="'.$_POST['search_criteria']['month2'].'">'.$_POST['search_criteria']['month2'].'</option>';
  }
else
  {
    $content_report .= '  <option value="'.date('m').'">'.date('m').'</option>';
  }
$content_report .= listMonths().'
      </select>
      <select size="1" name="search_criteria[day2]">';
if ( $_POST['search_criteria']['day2'] )
  {
    $content_report .= '<option value="'.$_POST['search_criteria']['day2'].'">'.$_POST['search_criteria']['day2'].'</option>';
  }
else
  {
    $content_report .= '<option value="'.date('d').'">'.date('d').'</option>';
  }
$content_report .= listDays().'
      </select>
      <select size="1" name="search_criteria[year2]">';
if ( $_POST['search_criteria']['year2'] )
  {
    $content_report .= '<option value="'.$_POST['search_criteria']['year2'].'">'.$_POST['search_criteria']['year2'].'</option>';
  }
else
  {
    $content_report .= '<option value="'.date('Y').'">'.date('Y').'</option>';
  }
$content_report .= listYears().'
      </select>
      <input type="submit" name="Submit" value="Search">
    </td>
  </tr>
</table>
</form>
<br />
<table style="border:1px solid;">
  <tr style="background-color:#CCCCCC;">
    <th>ID</th>
    <!--<th>Type</th>-->
    <th>Name</th>
    <th>Amount</th>
    <th>Mthd</th>
    <th>MemID</th>
    <th>BasketID</th>
    <th>DeliveryID</th>
    <th>User</th>
    <th>ProducerID</th>
    <th>Taxed</th>
    <th>Time</th>
    <th>BatchNo.</th>
    <th>Memo</th>
    <th>Comments</th>
  </tr>';
$export = "Transaction ID,Name,Amount,Method,MemberID,BasketID,DeliveryID,User,ProducerID,Taxed,Timestamp,BatchNo.,Memo,Comments\n";
if (is_array ($transactions))
  {
    foreach($transactions as $key=>$row)
      {
        //<!--<td>'.getTransactionsTypeName($row['transaction_type']).'</td>-->
        $style = '';
        if ( is_int($key / 2) )
          {
            $style = ' style="background-color:#EEEEEE;" ';
          }
        $content_report .= '
          <tr '.$style.'>
            <td>'.$row['transaction_id'].'</td>
            <td>'.$row['transaction_name'].'</td>
            <td align="right">$ '.$row['transaction_amount'].'</td>
            <td align="right">'.$row['transaction_method'].'</td>
            <td align="right">'.$row['transaction_member_id'].'</td>
            <td align="right">'.$row['transaction_basket_id'].'</td>
            <td align="center">'.$row['transaction_delivery_id'].'</td>
            <td align="right">'.$row['transaction_user'].'</td>
            <td>'.$row['transaction_producer_id'].'</td>
            <td align="center">'.$row['transaction_taxed'].'</td>
            <td>'.$row['transaction_timestamp'].'</td>
            <td align="right">'.$row['transaction_batchno'].'</td>
            <td>'.$row['transaction_memo'].'</td>
            <td>'.$row['transaction_comments'].'</td>
          </tr>';
        $search = array('/\n/','/\r/','/,/');
        $replace = array(' ',' ',' ');
        $export .= $row['transaction_id'].','.preg_replace($search,$replace,$row['transaction_name']).','.$row['transaction_amount'].','.$row['transaction_method'].','.$row['transaction_member_id'].','.$row['transaction_basket_id'].','.$row['transaction_delivery_id'].','.$row['transaction_user'].','.$row['transaction_producer_id'].','.$row['transaction_taxed'].','.$row['transaction_timestamp'].','.$row['transaction_batchno'].','.preg_replace($search,$replace,$row['transaction_memo']).','.preg_replace($search,$replace,$row['transaction_comments'])."\n";
      }
  }
$content_report .= '
</table>
<br/>';
if ( $_POST )
  {
    $content_report .= '
      <form action="export.php" method="POST">
      <input type="hidden" name="export" value="'.$export.'">
      <input type="submit" name="submit" value="Export Results to CSV File">
      </form>';
  }

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Transaction Report</span>';
$page_title = 'Reports: Transaction Report';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_report.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
