<?php
$sql = '
  SELECT
    '.NEW_TABLE_PRODUCTS.'.*,
    '.TABLE_CATEGORY.'.category_id,
    '.TABLE_CATEGORY.'.category_name,
    '.TABLE_SUBCATEGORY.'.subcategory_name,
    '.TABLE_PRODUCT_TYPES.'.prodtype
  FROM
    '.NEW_TABLE_PRODUCTS.'
  LEFT JOIN
    '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT_TYPES.'.production_type_id = '.NEW_TABLE_PRODUCTS.'.production_type_id
  LEFT JOIN
    '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN
    '.TABLE_CATEGORY.' ON '.TABLE_CATEGORY.'.category_id = '.TABLE_SUBCATEGORY.'.category_id
  LEFT JOIN
    '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.NEW_TABLE_PRODUCTS.'.producer_id
  WHERE
      ('.NEW_TABLE_PRODUCTS.'.product_id = "'.mysql_real_escape_string ($_REQUEST['product_id']).'"
      AND '.NEW_TABLE_PRODUCTS.'.product_version = "'.mysql_real_escape_string ($_REQUEST['product_version']).'")
    OR 
      ('.NEW_TABLE_PRODUCTS.'.pvid = "'.mysql_real_escape_string ($_REQUEST['pvid']).'"
      AND '.NEW_TABLE_PRODUCTS.'.pvid != "0")';
$result = @mysql_query($sql, $connection) or die(debug_print ("ERROR: 214075 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));

if (mysql_numrows($result) == 1)
  {
    $row = mysql_fetch_array($result);
    $pvid = $row['pvid'];
    $product_id = $row['product_id'];
    $product_version = $row['product_version'];
    $product_producer_id = $row['producer_id'];
    $product_name = $row['product_name'];
    $product_description = $row['product_description'];
    $category_id = $row['category_id'];
    $category_name = $row['category_name'];
    $subcategory_id = $row['subcategory_id'];
    $subcategory_name = $row['subcategory_name'];
    $account_number = $row['account_number'];
    $inventory_id = $row['inventory_id'];
    $inventory_pull = $row['inventory_pull'];
    $unit_price = $row['unit_price'];
    $pricing_unit = $row['pricing_unit'];
    $ordering_unit = $row['ordering_unit'];
    $production_type_id = $row['production_type_id'];
    $prodtype = $row['prodtype'];
    $extra_charge = $row['extra_charge'];
    $product_fee_rate = $row['product_fee_percent'] / 100;
    $subcategory_fee_rate = $row['subcategory_fee_percent'] / 100;
    $producer_fee_rate = $row['producer_fee_percent'] / 100;
    $random_weight = $row['random_weight'];
    $maximum_weight = $row['maximum_weight'];
    $minimum_weight = $row['minimum_weight'];
    $meat_weight_type = $row['meat_weight_type'];
    $listing_auth_type = $row['listing_auth_type'];
    $sticky = $row['sticky'];
    $tangible = $row['tangible'];
    $storage_id = $row['storage_id'];
    $retail_staple = $row['retail_staple'];
    $created = $row['created'];
    $modified = $row['modified'];
    $hide_from_invoice = $row['hide_from_invoice'];
  }
?>