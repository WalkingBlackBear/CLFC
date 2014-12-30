<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('producer_admin,site_admin');


$date_today = date("F Y");
$font = '<font face="arial" size="-1">';
$font2 = '<font face="arial" size="-2">';
$display .= '
  <table border="0" cellpadding="0" cellspacing="0" width="100%">';
$sql = '
  SELECT
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCT_PREP.'.subcategory_id,
    '.TABLE_PRODUCT_PREP.'.donotlist
  FROM
    '.TABLE_CATEGORY.',
    '.TABLE_SUBCATEGORY.',
    '.TABLE_PRODUCT_PREP.',
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
    AND '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT_PREP.'.subcategory_id
    AND '.TABLE_PRODUCT_PREP.'.donotlist != "1"
  GROUP BY
    '.TABLE_PRODUCT_PREP.'.subcategory_id
  ORDER BY
    sort_order ASC,
    subcategory_name ASC';
$rs = @mysql_query($sql, $connection) or die("Couldn't execute category query.");
while ( $row = mysql_fetch_array($rs) )
  {
    $category_id = $row['category_id'];
    $category_name = $row['category_name'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];
    if ( $current_category_id < 0 )
      {
        $current_category_id = $row['category_id'];
      }
    while ( $current_category_id != $category_id )
      {
        $current_category_id = $category_id;
        $display .= '
              <tr>
                <td colspan="4"><font face=arial size="+2"><b>'.$category_name.'</b></font></td>
              </tr>';
      }
    $display .= '
              <tr>
                <td colspan="4"><b><font size="+1">'.$subcategory_name.'</b> </font></td>
              </tr>
              <tr>
                <th align="center" width="30">'.$font.'
                  <div style="border: solid 0 #000; border-top-width:2px; border-bottom-width:2px; padding-left:0.0ex">#</div>
                </th>
                <th align="left">'.$font.'
                  <div style="border: solid 0 #000; border-top-width:2px; border-bottom-width:2px; padding-left:0.0ex"> Product</div>
                </th>
                <th align="center">'.$font.'
                  <div style="border: solid 0 #000; border-top-width:2px; border-bottom-width:2px; padding-left:0.0ex;text-align:center;">Price</div>
                </th>
                <th align="right">'.$font.'
                  <div style="border: solid 0 #000; border-top-width:2px; border-bottom-width:2px; padding-left:0.0ex;text-align:center;">Type</div>
                </th>
              </tr>';
    $sqlp = '
      SELECT
        '.TABLE_PRODUCT_PREP.'.subcategory_id,
        '.TABLE_PRODUCT_PREP.'.producer_id,
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_PRODUCER.'.member_id,
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_PRODUCT_PREP.'.donotlist
      FROM
        '.TABLE_PRODUCT_PREP.'
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_PRODUCT_PREP.'.producer_id
      WHERE
        '.TABLE_PRODUCT_PREP.'.subcategory_id = "'.mysql_real_escape_string ($subcategory_id).'"
          AND '.TABLE_PRODUCT_PREP.'.donotlist != "1"
          AND '.TABLE_PRODUCER.'.donotlist_producer = "0"
      GROUP BY
        '.TABLE_PRODUCT_PREP.'.producer_id
      ORDER BY
        '.TABLE_PRODUCER.'.business_name';
    $resultp = @mysql_query($sqlp, $connection) or die("Couldn't execute query 2.");
    while ( $row = mysql_fetch_array($resultp) )
      {
        $producer_id = $row['producer_id'];
        $business_name = $row['business_name'];
        $display .= '
              <tr>
                <td colspan="4"><font size="+1"><b>'.$business_name.'</b></td>
              </tr>';
        $sql = '
          SELECT
            *
          FROM
            '.TABLE_PRODUCT_PREP.',
            '.TABLE_PRODUCT_TYPES.'
          WHERE
            '.TABLE_PRODUCT_PREP.'.subcategory_id = "'.mysql_real_escape_string ($subcategory_id).'"
            AND '.TABLE_PRODUCT_PREP.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
            AND '.TABLE_PRODUCT_PREP.'.prodtype_id = '.TABLE_PRODUCT_TYPES.'.prodtype_id
            AND '.TABLE_PRODUCT_PREP.'.donotlist != "1"
          ORDER BY
            product_name ASC,
            unit_price ASC';
        $result = @mysql_query($sql, $connection) or die("Couldn't execute search query.");
        while ( $row = mysql_fetch_array($result) )
          {
            $product_id = $row['product_id'];
            $product_name = $row['product_name'];
            $unit_price = $row['unit_price'];
            $pricing_unit = $row['pricing_unit'];
            $ordering_unit = $row['ordering_unit'];
            $prodtype_id = $row['prodtype_id'];
            $prodtype = $row['prodtype'];
            $extra_charge = $row['extra_charge'];
            $donotlist = $row['donotlist'];
            $detailed_notes = $row['detailed_notes'];
            if ( $current_product_id < 0 )
              {
                $current_product_id = $row['product_id'];
              }
            if ( $prodtype_id != 5 )
              {
                $show_type = $prodtype;
              }
            else
              {
                $show_type = '';
              }
            $price_display = '';
            if ($unit_price != 0)
              {
                $price_display .= '$ '.number_format($unit_price, 2).'/'.Inflect::singularize ($pricing_unit);
              }
            if ($unit_price != 0 && $extra_charge != 0)
              {
                $price_display .= '<br>';
              }
            if ($extra_charge != 0)
              {
                $price_display .= '$ '.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
              }
            $show_details = $detailed_notes;
            while ( $current_product_id != $product_id )
              {
                $current_product_id = $product_id;
                $display .= '
              <tr>
                <td valign="top">'.$font2.' <b>'.$product_id.'</b></td>
                <td valign="top">'.$font2.' <b>'.$product_name.'</b> - Order number of '.Inflect::pluralize ($ordering_unit).' '.$show_details.'</font></td>
                <td valign="top" align=center>'.$font2.$price_display.'</font></td>
                <td valign="top" align="center">'.$font.' '.$show_type.'</font></td>
              </tr>';
              }
          }
      }
  }
$display .= '</table>';


$content = '
<hr color="#000000" noshade size=2 width="100%">
<font size="+3">'.ucfirst (SITE_NAME).' '.$date_today.' Product List</font>
<hr color="#000000" noshade size=2 width="100%">
'.$display;

$page_title_html = '<span class="title">Printable Product Lists</span>';
$page_subtitle_html = '<span class="subtitle">Full Product List</span>';
$page_title = 'Printable Product Lists: Full Product List';
$page_tab = 'producer_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
