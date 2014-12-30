<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


$num_cycles = 20; # should be 1 higher than the actual number of cycles you want

$query = '
  SELECT
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.delivery_date,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_CATEGORY.'.category_name,
    /* (!out_of_stock * if('.TABLE_PRODUCT.'.random_weight = 1, '.TABLE_BASKET.'.item_price * total_weight, '.TABLE_BASKET.'.item_price * quantity)) AS real_price */
    ((1 - out_of_stock) * (('.TABLE_PRODUCT.'.random_weight * '.TABLE_BASKET.'.item_price * total_weight) + ((1 - '.TABLE_PRODUCT.'.random_weight) * '.TABLE_BASKET.'.item_price * quantity))) AS real_price
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN '.TABLE_BASKET.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  LEFT JOIN '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = '.TABLE_BASKET_ALL.'.delivery_id
  LEFT JOIN '.TABLE_PRODUCT.' ON '.TABLE_PRODUCT.'.product_id = '.TABLE_BASKET.'.product_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id < "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
    AND '.TABLE_BASKET_ALL.'.delivery_id > "'.mysql_real_escape_string (ActiveCycle::delivery_id() - $num_cycles).'"
  GROUP BY
    '.TABLE_BASKET.'.bpid';
$main_sql = mysql_query($query);

$categories = array();
while ($row = mysql_fetch_array($main_sql))
{
  if (isset($categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]]))
    $categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]] += $row["real_price"];
  else
    $categories[$row["category_name"]][$row["subcategory_name"]][$row["delivery_date"]] = $row["real_price"];
}

$query = '
  SELECT
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id < "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
    AND delivery_id > "'.mysql_real_escape_string (ActiveCycle::delivery_id() - $num_cycles).'"
  ORDER BY
    delivery_date DESC';
$dates_sql = mysql_query($query);

$delivery_dates = array();
$spreadsheet = "Subcategory";
$date_headers = "";
while ($row = mysql_fetch_array($dates_sql))
  {
    array_push($delivery_dates, $row["delivery_date"]);
    $date_headers .= "<th>".$row["delivery_date"]."</th>\n";
    $spreadsheet .= "\t".$row["delivery_date"];
  }

$table = "";
$spreadsheet .= "\n";
ksort($categories);
foreach ($categories as $cat_name => $cat)
  {
    $table .= '
      <tr>
        <th colspan="'.(1 + count($delivery_dates)).'" style="font-size: 1.5em; padding: 0.5em;">'.$cat_name.'</th>
      </tr>
      <tr>
        <th>Subcategory</th>
        '.$date_headers.'
      </tr>';
    $spreadsheet .= "\n*** $cat_name ***\n";
    ksort($cat);
    foreach ($cat as $subcat_name => $subcat)
      {

        $table .= '
          <tr>
            <th style="text-align: left;">'.$subcat_name.'</th>';
        $spreadsheet .= $subcat_name;
        foreach ($delivery_dates as $date)
          {
            $value = (isset($subcat[$date]) && $subcat[$date] != 0) ? number_format($subcat[$date], 2) : "-";
            $table .= '
              <td style="text-align: right;">'.$value.'</td>';
            $spreadsheet .= "\t".($value == "-" ? "0.00" : $value);
          }
        $table .= '
          </tr>';
        $spreadsheet .= "\n";
      }
  }

$content .= '
  <small>NOTE: This page might scroll horizontally.  Look for the horizontal scroll-bar at the bottom of the page.</small>
  <table width="90%">
    <tr>
      <td align="left">
        <h2>Sales By Subcategory (last '.$num_cycles.' cycles)</h2>
        <form>
          <label for="spreadsheet">Spreadsheet copyable data (click to select all, then copy):</label><br>
          <textarea style="margin-bottom: 1em;" id="spreadsheet" onclick="this.select();">'.$spreadsheet.'</textarea>
        </form>
        <table cellpadding="2" cellspacing="2" border="1">
          '.$table.'
        </table>
      </td>
    </tr>
  </table>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Subcategory Report</span>';
$page_title = 'Reports: Subcategory Report';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

