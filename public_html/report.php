<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


if ( !$_REQUEST['year'] )
  {
    $year = date('Y');
    $next_year = date('Y') + 1;
  }
else
  {
    $year = $_REQUEST['year'];
    $next_year = $_REQUEST['year'] + 1;
  }
$report = '
  Grand Total = Products Subtotal + Sales tax + Coop Charges + Home Delivery + (-)Missing Ticket Items + (-)Producer discounts<br >
  Coop Charges = The Shipping & Handling line on a customer invoice<br />
  <table border="1" cellpadding="5">
    <tr>
      <th>Delivery Date</th>
      <th>Total Orders</th>
      <th>Grand Total</th>
      <th><a href="salestax.php">Total Taxes</a></th>
      <th>Total Coop Charges</th>
      <th>Donations</th>
    </tr>';
$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.delivery_id,
    DATE_FORMAT(delivery_date, "%M %d, %Y") AS delivery_date,
    SUM(ROUND('.TABLE_CUSTOMER_SALESTAX.'.collected_statetax, 2)) AS sum1,
    SUM(ROUND('.TABLE_CUSTOMER_SALESTAX.'.collected_citytax, 2)) AS sum2,
    SUM(ROUND('.TABLE_CUSTOMER_SALESTAX.'.collected_countytax, 2)) AS sum3,
    count('.TABLE_BASKET_ALL.'.basket_id) AS total_orders
  FROM
    (
      '.TABLE_BASKET_ALL.',
      '.TABLE_ORDER_CYCLES.'
    )
  LEFT JOIN '.TABLE_CUSTOMER_SALESTAX.' ON  '.TABLE_CUSTOMER_SALESTAX.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
    AND '.TABLE_ORDER_CYCLES.'.delivery_date >= "'.mysql_real_escape_string ($year).'"
    AND '.TABLE_ORDER_CYCLES.'.delivery_date <= "'.mysql_real_escape_string ($next_year).'"
  GROUP BY
    '.TABLE_BASKET_ALL.'.delivery_id
  ORDER BY
    '.TABLE_BASKET_ALL.'.delivery_id DESC';
$rs = @mysql_query($sql, $connection) or die(mysql_error());
while ( $row2 = mysql_fetch_array($rs) )
  {
    $total_taxes = $row2['sum1'] + $row2['sum2'] + $row2['sum3'];
    $coop_charges = 0;
    $grand_total = 0;
    $overall_total = 0;
    $totalsql = mysql_query('
      SELECT
        ROUND(collected_statetax, 2) AS collected_statetax,
        ROUND(collected_citytax, 2) AS collected_citytax,
        ROUND(collected_countytax, 2) AS collected_countytax,
        '.TABLE_BASKET_ALL.'.subtotal,
        '.TABLE_BASKET_ALL.'.coopfee,
        '.TABLE_BASKET_ALL.'.transcharge,
        '.TABLE_BASKET_ALL.'.delivery_cost,
        '.TABLE_BASKET_ALL.'.sh,
        '.TABLE_BASKET_ALL.'.basket_id
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN customer_salestax ON  '.TABLE_CUSTOMER_SALESTAX.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($row2["delivery_id"]).'"');
    while ( $totals = mysql_fetch_array($totalsql) )
      {
        // Non-taxed adjustments
        $subtotal_1 = $totals['subtotal'] + $totals['coopfee'] + $totals['transcharge'] + $totals['delivery_cost'] + $totals['sh'] + $totals['collected_statetax'] + $totals['collected_citytax'] + $totals['collected_countytax'];
        if ( $totals['subtotal'] <= 0 )
          {
            $cash_discount = .31;
          }
        else
          {
            //$total_sent_to_paypal = (($subtotal_1 + .30)/ .971);
            $cash_discount = number_format((((($subtotal_1 + .30)/ .971)*.029) + .30),4);
          }
        if ( $subtotal_1 <= 0 )
          {
            $cash_discount = 0;
          }
        $coop_charges = $coop_charges + $totals['sh'] + number_format($cash_discount, 2) + 0;
        $overall_total = $overall_total + $totals['subtotal'] + $totals['collected_statetax'] + $totals['collected_citytax'] + $totals['collected_countytax'] + ($totals['sh'] + number_format($cash_discount,2)) + $totals['delivery_cost'];
      }
    // Donations - for OKFood Coop, it's subcategory 56
    $sql_donations = mysql_query('
      SELECT
        SUM((item_price * quantity) + (extra_charge * quantity)) as product_sum
      FROM
        '.TABLE_BASKET.'
      LEFT JOIN
        '.TABLE_BASKET_ALL.'
        ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($row2["delivery_id"]).'"
        AND '.TABLE_BASKET.'.subcategory_id = "56"
      GROUP BY '.TABLE_BASKET_ALL.'.delivery_id');
    $donations = mysql_fetch_array($sql_donations);
    $grand_total = $overall_total;
    $report .= '
      <tr align="right">
        <td><a href="ctotals_reports.php?delivery_id='.$row2['delivery_id'].'">'.$row2['delivery_date'].'</a></td>
        <td>'.$row2['total_orders'].'</td>
        <td>$'.number_format($grand_total, 2).'</td>
        <td>$'.number_format($total_taxes, 2).'</td>
        <td>$'.number_format($coop_charges, 2).'</td>
        <td>$'.number_format($donations['product_sum'], 2).'</td>
      </tr>';
  }
$report .= '
    </table>';

$content .= '
<table cellpadding="15">
  <tr>
    <td valign="top">
      <h2>'.$year.' Totals</h2>';

$query ='
  SELECT
    MIN(delivery_date) AS delivery_date
  FROM
    '.TABLE_ORDER_CYCLES;
$sql = mysql_query ($query);
$result = mysql_fetch_array($sql);
$first_year = substr ($result['delivery_date'], 0, 4);
for( $yr = $first_year; $yr <= date('Y'); $yr++ )
  {
    if ( $year == $yr )
      {
        $content .= $yr;
      }
    else
      {
        $content .= ' <a href="'.$_SERVER['PHP_SELF'].'?year='.$yr.'">'.$yr.'</a> ';
      }
  }

$content .= '
      <br>
      '.$report.'
    </td>
  </tr>
</table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Order Cycle Report</span>';
$page_title = 'Reports: Order Cycle Report';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

