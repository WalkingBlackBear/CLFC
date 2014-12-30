<?php

// valid_auth(''); // anyone can view these pages

// Do not show search on non-shopping pages
$show_search = false;

// This is the producer's public page, so display producer info at the top
include('func/display_producer_page.php');
$producer_display .= prdcr_info($producer_id, $producer_link);

$where_misc = '
    AND '.TABLE_PRODUCER.'.producer_link = "'.mysql_real_escape_string ($producer_link).'"';

$order_by = '
    '.TABLE_CATEGORY.'.category_name ASC,
    '.TABLE_SUBCATEGORY.'.subcategory_name ASC,
    '.TABLE_PRODUCER.'.business_name ASC,
    '.NEW_TABLE_PRODUCTS.'.product_name ASC,
    '.NEW_TABLE_PRODUCTS.'.unit_price ASC';

// Assign page tab and title information
$page_title = 'Products';
// These values are assigned after the database query, when we have more information
// $page_title_html
// $page_title
// $page_tab

// Assign template file
if ($_GET['csv'] == 'true')
  {
    $per_page = 1000000;
    $template_type = 'customer_list_csv';
  }
elseif ($pdf == true)
  {
    $per_page = 1000000;
    $template_type = 'customer_list_pdf';
  }
else
  {
    $per_page = PER_PAGE;
    $template_type = 'customer_list';
  }

// Set display groupings
$major_division = 'category_name';
$major_division_prior = $major_division.'_prior';
$minor_division = 'subcategory_name';
$minor_division_prior = $minor_division.'_prior';
$show_major_division = true;
$show_minor_division = true;
$row_type = 'product'; // Reflects the detail to show on each row (vs. what gets featured in the header)

// Execute the main product_list query
$query = '
  SELECT
    SQL_CALC_FOUND_ROWS
    '.NEW_TABLE_PRODUCTS.'.*,
    '.TABLE_CATEGORY.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.business_name AS producer_name,
    '.TABLE_PRODUCER.'.producer_fee_percent,
    '.TABLE_PRODUCT_TYPES.'.prodtype,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.*,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull) AS inventory_quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity AS basket_quantity,
    (SELECT GROUP_CONCAT(delcode_id) FROM '.TABLE_AVAILABILITY.' WHERE '.TABLE_AVAILABILITY.'.producer_id='.NEW_TABLE_PRODUCTS.'.producer_id) AS availability_list
    /* GROUP_CONCAT(CONCAT_WS(",", '.TABLE_AVAILABILITY.'.delcode_id)) AS availability_list */
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  LEFT JOIN '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN '.TABLE_AVAILABILITY.' ON '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.NEW_TABLE_PRODUCTS.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  LEFT JOIN '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.product_id = '.NEW_TABLE_PRODUCTS.'.product_id AND '.NEW_TABLE_BASKET_ITEMS.'.basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
  WHERE'.
    $where_producer_pending.
    $where_unlisted_producer.
    $where_misc.
    $where_zero_inventory.
    $where_confirmed.
    $where_auth_type.'
  GROUP BY CONCAT('.NEW_TABLE_PRODUCTS.'.product_id, "-", '.NEW_TABLE_PRODUCTS.'.product_version)
  ORDER BY'.
    $order_by;
?>