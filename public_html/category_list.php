<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member');

// Get the time until the order closes
$seconds_until_close = strtotime (ActiveCycle::date_closed()) - time();
// Set up the "listing_auth_type" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see donotlist=3 (wholesale products)
if (CurrentMember::auth_type('institution') && $seconds_until_close < INSTITUTION_WINDOW)
  {
    $listing_auth_type_condition = 'AND ('.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member" OR '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "institution")';
  }
else
  {
    $listing_auth_type_condition = 'AND '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"';
  }

$search_display = '
  <form action="product_list.php" method="get">
    <input type="hidden" name="type" value="search">
    <input id="load_target" type="text" name="query" value="'.$_GET['query'].'">
    <input type="submit" name="action" value="Search">
  </form>';

$sql = '
  SELECT
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.NEW_TABLE_PRODUCTS.'.subcategory_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type
  FROM
    '.TABLE_CATEGORY.',
    '.TABLE_SUBCATEGORY.',
    '.NEW_TABLE_PRODUCTS.',
    '.TABLE_PRODUCER.'
  WHERE
    '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
    AND '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
    '.$listing_auth_type_condition.'
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_PRODUCER.'.pending = 0
    AND '.TABLE_PRODUCER.'.unlisted_producer = 0
  GROUP BY
    '.NEW_TABLE_PRODUCTS.'.subcategory_id
  ORDER BY
    sort_order ASC,
    category_name ASC,
    subcategory_name ASC';
$rs = @mysql_query($sql, $connection) or die(debug_print ("ERROR: 649659 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($rs) )
  {
    $category_id = $row['category_id'];
    $category_name = $row['category_name'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];

    if ( $current_category_id < 0)
      {
        $current_category_id = $row['category_id'];
      }

    while ( $current_category_id != $category_id )
      {
        $current_category_id = $category_id;
        $display .= "<h3>$category_name</h3>";
      }
    $display .= '
      <ul>
        <li><a href="product_list.php?type=subcategory&subcat_id='.$subcategory_id.'">'.$subcategory_name.'</a>
      </ul>';
  }

$content_list = '
  <div align="center">
    <table width="80%">
      <tr>
        <td align="left">
          '.$search_display.'
          '.$display.'
        </td>
      </tr>
    </table>
  </div>';

$page_title_html = '<span class="title">Products</span>';
$page_subtitle_html = '<span class="subtitle">Sorted by Category</span>';
$page_title = 'Products: Sorted by Category';
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_list.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
