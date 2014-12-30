<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


if ( !$_REQUEST['delivery_id'] )
  {
    $display .= '
      <table border="0">';
    $sql = '
      SELECT '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.delivery_id,
        '.TABLE_ORDER_CYCLES.'.delivery_date,
        '.TABLE_CUSTOMER_SALESTAX.'.*,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.collected_statetax) AS sum1,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.collected_citytax) AS sum2,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.collected_countytax) AS sum3,
        '.TABLE_SALES_TAX.'.*
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
      LEFT JOIN '.TABLE_CUSTOMER_SALESTAX.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_CUSTOMER_SALESTAX.'.basket_id
      LEFT JOIN '.TABLE_SALES_TAX.' ON '.TABLE_CUSTOMER_SALESTAX.'.copo_county = '.TABLE_SALES_TAX.'.copo
      GROUP BY
        '.TABLE_ORDER_CYCLES.'.delivery_id DESC';
    $result = @mysql_query($sql, $connection) or die("Couldn't execute query 1.");
    $numtotal = mysql_numrows($result);
    while ( $row = mysql_fetch_array($result) )
      {
        $delivery_id = $row['delivery_id'];
        $delivery_date = date ("F d Y", strtotime ($row['delivery_date']));
        $collected_statetax = $row['sum1'];
        $collected_citytax = $row['sum2'];
        $collected_countytax = $row['sum3'];
        $total_statetax = number_format($collected_statetax + $total_statetax + 0, 2);
        $total_countytax = number_format($collected_countytax + $total_countytax + 0, 2);
        $total_citytax = number_format($collected_citytax + $total_citytax + 0, 2);
        $total_tax = number_format(($collected_statetax + $collected_countytax + $collected_citytax), 2);
        $display .= '
          <tr>
            <td colspan="2"><b>Order Cycle: <a href="'.$_SERVER['PHP_SELF'].'?delivery_id='.$delivery_id.'">'.$delivery_date.'</a></b></td>
          </tr>
          <tr>
            <td>State Tax </td><td align=right> $'.$total_statetax.'</td>
          </tr>
          <tr>
            <td>County Taxes </td><td align=right> $'.$total_countytax.'</td>
          </tr>
          <tr>
            <td>City Taxes </td><td align=right> $'.$total_citytax.'</td>
          </tr>
          <tr>
            <td>Total Taxes <br><br></td><td align=right>$'.$total_tax.'<br><br></td>
          </tr>';
        $collected_citytax = '';
        $collected_countytax = '';
        $collected_statetax = '';
        $total_citytax = '';
        $total_countytax = '';
        $total_statetax = '';
        $total_tax = '';
      }
    $display .= '</table>';
    $delivery_id = '';
  }
if ( $_REQUEST['delivery_id'] )
  {
    $detail = 'yes';
    // City sales tax.
    $display_city .= '
      <table cellpadding="3" cellspacing="0" border="1" bordercolor="#dddddd">
        <tr bgcolor="#dddddd">
          <td><b>COPO</b></td>
          <td><b>City</b></td>
          <td><b>Exempt</b></td>
          <td><b>Taxable</td>
          <td><b>Rate</td>
          <td><b>Totals</b></td>
        </tr>';
    $sql2 = '
      SELECT
        '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.delivery_id,
        '.TABLE_ORDER_CYCLES.'.delivery_date,
        '.TABLE_CUSTOMER_SALESTAX.'.*,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.collected_citytax) AS sum2,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.taxable_total) AS taxable_sum,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.exempt_total) AS exempt_sum,
        '.TABLE_SALES_TAX.'.*
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
      LEFT JOIN '.TABLE_CUSTOMER_SALESTAX.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_CUSTOMER_SALESTAX.'.basket_id
      LEFT JOIN '.TABLE_SALES_TAX.' ON '.TABLE_CUSTOMER_SALESTAX.'.copo_city = '.TABLE_SALES_TAX.'.copo
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
      GROUP BY
        '.TABLE_CUSTOMER_SALESTAX.'.copo_city
      ORDER BY
        '.TABLE_CUSTOMER_SALESTAX.'.copo_city ASC';
    $result2 = @mysql_query($sql2, $connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($result2) )
      {
        // $delivery_date = $row['delivery_date'];
        $delivery_date = date ("F d Y", $row['delivery_date']);
        $taxable_sum = number_format($row['taxable_sum'], 2);
        $exempt_sum = number_format($row['exempt_sum'], 2);
        $collected_citytax = number_format($row['sum2'], 2);
        $copo_city = $row['copo_city'];
        $taxrate_city = $row['taxrate_city'] * 100;
        $city_county = $row['city_county'];
        $total_citytax = $collected_citytax + $total_citytax + 0;
        $display_city .= '
          <tr>
            <td align="right">'.$copo_city.'</td>
            <td align="left">'.$city_county.'</td>
            <td align="right">$'.$exempt_sum.'</td>
            <td align="right">$'.$taxable_sum.'</td>
            <td align="right">'.$taxrate_city.' %</td>
            <td align="right"> $'.number_format ($collected_citytax, 2).'</td>
          </tr>';
        $collected_citytax = "";
      }
    $display_city .= '
        <tr>
          <td colspan="5" align="right"><b>City Total</b></td>
          <td align="right">$'.number_format ($total_citytax, 2).'</td>
        </tr>
      </table>';
    // County sales tax.
    $display_county .= '
      <table cellpadding="3" cellspacing="0" border="1" bordercolor="#dddddd">
        <tr bgcolor="#dddddd">
          <td><b>COPO</b></td>
          <td><b>County</b></td>
          <td><b>Exempt</b></td>
          <td><b>Taxable</td>
          <td><b>Rate</td>
          <td><b>Totals</b></td>
        </tr>';
    $sql3 = '
      SELECT '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.delivery_id,
        '.TABLE_ORDER_CYCLES.'.delivery_date,
        '.TABLE_CUSTOMER_SALESTAX.'.*,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.collected_countytax) AS sum3,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.taxable_total) AS taxable_sum,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.exempt_total) AS exempt_sum,
        '.TABLE_SALES_TAX.'.*
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
      LEFT JOIN '.TABLE_CUSTOMER_SALESTAX.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_CUSTOMER_SALESTAX.'.basket_id
      LEFT JOIN '.TABLE_SALES_TAX.' ON '.TABLE_CUSTOMER_SALESTAX.'.copo_county = '.TABLE_SALES_TAX.'.copo
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
      GROUP BY
        '.TABLE_CUSTOMER_SALESTAX.'.copo_county
      ORDER BY
        '.TABLE_CUSTOMER_SALESTAX.'.copo_county ASC';
    $result3 = @mysql_query($sql3, $connection) or die("".mysql_error()."");
    while ( $row = mysql_fetch_array($result3) )
      {
        // $delivery_date = $row['delivery_date'];
        $delivery_date = date ("F d Y", $row['delivery_date']);
        $taxable_sum = number_format($row['taxable_sum'], 2);
        $exempt_sum = number_format($row['exempt_sum'], 2);
        $collected_countytax = number_format($row['sum3'], 2);
        $copo_county = $row['copo_county'];
        $taxrate_county = $row['taxrate_county'] * 100;
        $city_county = $row['city_county'];
        $total_countytax = $collected_countytax + $total_countytax + 0;
        $display_county .= '
          <tr>
            <td align="right">'.$copo_county.'</td>
            <td align="left">'.$city_county.'</td>
            <td align="right">$'.$exempt_sum.'</td>
            <td align="right">$'.$taxable_sum.'</td>
            <td align="right">'.$taxrate_county.' %</td>
            <td align="right"> $'.number_format ($collected_countytax, 2).'</td>
          </tr>';
        $collected_countytax = "";
      }
    $display_county .= '
          <tr>
            <td colspan="5" align="right"><b>County Total</b></td>
            <td align="right">$'.number_format ($total_countytax, 2).'</td>
          </tr>
        </table>';
    // State sales tax.
    $display_state .= '
          <table cellpadding="3" cellspacing="0" border="1" bordercolor="#dddddd">
            <tr bgcolor="#dddddd">
              <td><b>State</b></td>
              <td><b>Exempt</b></td>
              <td><b>Taxable</td>
              <td><b>Rate</td>
              <td><b>State Total</b></td>
            </tr>';
    $sql1 = '
      SELECT '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.delivery_id,
        '.TABLE_ORDER_CYCLES.'.delivery_date,
        '.TABLE_CUSTOMER_SALESTAX.'.*,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.collected_statetax) AS sum1,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.taxable_total) AS taxable_sum,
        SUM('.TABLE_CUSTOMER_SALESTAX.'.exempt_total) AS exempt_sum,
        '.TABLE_SALES_TAX.'.*
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_BASKET_ALL.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
      LEFT JOIN '.TABLE_CUSTOMER_SALESTAX.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_CUSTOMER_SALESTAX.'.basket_id
      LEFT JOIN '.TABLE_SALES_TAX.' ON '.TABLE_CUSTOMER_SALESTAX.'.copo_county = '.TABLE_SALES_TAX.'.copo
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
      GROUP BY
        '.TABLE_BASKET_ALL.'.delivery_id';
    $result1 = @mysql_query($sql1, $connection) or die("".mysql_error()."");
    while ( $row = mysql_fetch_array($result1) )
      {
        // $delivery_date = $row['delivery_date'];
        $delivery_date = date ("F d Y", strtotime ($row['delivery_date']));
        $taxable_sum = number_format($row['taxable_sum'], 2);
        $exempt_sum = number_format($row['exempt_sum'], 2);
        $taxrate_state = $row['taxrate_state'] * 100;
        $collected_statetax = $row['sum1'];
        $total_statetax = $collected_statetax + $total_statetax + 0;
      }
    $display_state .= '
            <tr>
              <td><b>State</b></td>
              <td align="right">$'.$exempt_sum.'</td>
              <td align="right">$'.$taxable_sum.'</td>
              <td align="right">'.$taxrate_state.' %</td>
              <td align="right"> $'.number_format ($collected_statetax, 2).'</td>
            </tr>
          </table>';
    // Display all three side by side.
    $display .= '
      <div align=center>
        <table>
          <tr>
            <td colspan="3" align="right">Return to <a href="'.$_SERVER['PHP_SELF'].'">main sales tax page</a></td>
          </tr>
          <tr>
            <td valign="top">'.$display_city.'</td>
            <td valign="top">'.$display_county.' <br><br>
              <div align="center">
                '.$display_state.'
            </td>
          </tr>
        </table>
      </div>
    </div>
    <br><br>';
  }


$content .= '
<h2>Sales Tax Break Down'.($detail == 'yes' ? ': '.$delivery_date : '').'</h2>
'.$display;

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Sales Tax</span>';
$page_title = 'Reports: Sales Tax';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

