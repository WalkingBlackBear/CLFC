<?php

// Check to see if the product is already in the basket
$sqlb = '
  SELECT
    product_id
  FROM
    '.TABLE_BASKET.'
  WHERE
    product_id = "'.mysql_real_escape_string ($_REQUEST['product_id']).'"
    AND basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"';
$resultb = @mysql_query($sqlb, $connection) or die(mysql_error());
$numb = mysql_numrows($resultb);
if ( $numb == 1 )
  {
    $message = '<b><u>Product ID # '.$_REQUEST['product_id'].' is already in your basket</u>.</b><br>Please edit the quantity in <a href="orders_current.php">your shopping cart</a> of the item already listed if you want to add more.';
  }
else
  {
    // Get inventory quantities
    $sqlis = '
      SELECT
        '.TABLE_PRODUCT.'.inventory_id,
        '.TABLE_PRODUCT.'.inventory_pull,
        FLOOR('.TABLE_INVENTORY.'.quantity / '.TABLE_PRODUCT.'.inventory_pull) AS inventory
      FROM
        '.TABLE_PRODUCT.'
      LEFT JOIN '.TABLE_INVENTORY.' ON '.TABLE_PRODUCT.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
      WHERE
        product_id = "'.mysql_real_escape_string ($_REQUEST['product_id']).'"';
    $resultis = @mysql_query($sqlis, $connection) or die("Couldn't execute query s.");
    while ( $row = mysql_fetch_array($resultis) )
      {
        $inventory_id = $row['inventory_id'];
        $inventory = $row['inventory'];
        $inventory_pull = $row['inventory_pull'];
      }
    if( $inventory_id && ($inventory == '' || $inventory == 0) )
      {
        $message = '<b>This product is sold out!</b>';
      }
    else
      {
        if( $inventory_id && $inventory >= 1 )
          {
            $inventory = $inventory - 1;
            $sqlus = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = quantity - '.$inventory_pull.'
              WHERE
                inventory_id = "'.mysql_real_escape_string ($inventory_id).'"';
            $resultus = @mysql_query($sqlus, $connection) or die("Couldn't execute query updating stock in public product list.");
          }
        $sql3 = '
          SELECT
            '.TABLE_PRODUCT.'.*,
            '.TABLE_SUBCATEGORY.'.subcat_adjust_fee,
            '.TABLE_PRODUCER.'.producer_adjust_fee
          FROM
            '.TABLE_PRODUCT.'
          LEFT JOIN
            '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.TABLE_PRODUCT.'.subcategory_id
          LEFT JOIN
            '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_PRODUCT.'.producer_id
          WHERE
            product_id = '.mysql_real_escape_string ($_REQUEST['product_id']);
        $result3 = @mysql_query($sql3, $connection) or die("Couldn't execute query 3.");
        while ( $row = mysql_fetch_array($result3) )
          {
            $producer_id = $row['producer_id'];
            $product_name = $row['product_name'];
            $detailed_notes = $row['detailed_notes'];
            $subcategory_id = $row['subcategory_id'];
            $unit_price = $row['unit_price'];
            $pricing_unit = $row['pricing_unit'];
            $ordering_unit = $row['ordering_unit'];
            $random_weight = $row['random_weight'];
            $extra_charge = $row['extra_charge'];
            $product_adjust_fee = $row['product_adjust_fee'];
            $subcat_adjust_fee = $row['subcat_adjust_fee'];
            $producer_adjust_fee = $row['producer_adjust_fee'];
            $meat_weight_type = $row['meat_weight_type'];
            $minimum_weight = $row['minimum_weight'];
            $maximum_weight = $row['maximum_weight'];
            $future_delivery = $row['future_delivery'];
            $prodtype_id = $row['prodtype_id'];
            $retail_staple = $row['retail_staple'];
            $staple_type = $row['staple_type'];
            $hidefrominvoice = $row['hidefrominvoice'];
            $storage_id = $row['storage_id'];
            $future_delivery_id = $row['future_delivery_id'];
            $tangible = $row['tangible'];
          }
        $sqlc = '
          INSERT INTO
            '.TABLE_BASKET.'
              (
                basket_id,
                product_id,
                producer_id,
                product_name,
                detailed_notes,
                subcategory_id,
                item_price,
                pricing_unit,
                ordering_unit,
                quantity,
                random_weight,
                extra_charge,
                product_adjust_fee,
                subcat_adjust_fee,
                producer_adjust_fee,
                meat_weight_type,
                minimum_weight,
                maximum_weight,
                future_delivery,
                customer_notes_to_producer,
                prodtype_id,
                retail_staple,
                staple_type,
                hidefrominvoice,
                storage_id,
                future_delivery_id,
                tangible,
                date_added,
                item_date
              )
          VALUES
            (
              "'.mysql_real_escape_string (CurrentBasket::basket_id()).'",
              "'.mysql_real_escape_string ($_REQUEST['product_id']).'",
              "'.mysql_real_escape_string ($producer_id).'",
              "'.mysql_real_escape_string ($product_name).'",
              "'.mysql_real_escape_string ($detailed_notes).'",
              "'.mysql_real_escape_string ($subcategory_id).'",
              "'.mysql_real_escape_string ($unit_price).'",
              "'.mysql_real_escape_string ($pricing_unit).'",
              "'.mysql_real_escape_string ($ordering_unit).'",
              "1",
              "'.mysql_real_escape_string ($random_weight).'",
              "'.mysql_real_escape_string ($extra_charge).'",
              "'.mysql_real_escape_string ($product_adjust_fee).'",
              "'.mysql_real_escape_string ($subcat_adjust_fee).'",
              "'.mysql_real_escape_string ($producer_adjust_fee).'",
              "'.mysql_real_escape_string ($meat_weight_type).'",
              "'.mysql_real_escape_string ($minimum_weight).'",
              "'.mysql_real_escape_string ($maximum_weight).'",
              "'.mysql_real_escape_string ($future_delivery).'",
              "'.mysql_real_escape_string ($customer_notes_to_producer).'",
              "'.mysql_real_escape_string ($prodtype_id).'",
              "'.mysql_real_escape_string ($retail_staple).'",
              "'.mysql_real_escape_string ($staple_type).'",
              "'.mysql_real_escape_string ($hidefrominvoice).'",
              "'.mysql_real_escape_string ($storage_id).'",
              "'.mysql_real_escape_string ($future_delivery_id).'",
              "'.mysql_real_escape_string ($tangible).'",
              now(),
              now()
            )';
        $result = @mysql_query($sqlc, $connection) or die(mysql_error());

        // Unfinalize the invoice
        $sql = '
          UPDATE
            '.TABLE_BASKET_ALL.'
          SET
            finalized = 0,
            invoice_content = ""
          WHERE
            basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"';
        $result = @mysql_query($sql, $connection) or die(mysql_error());

        $message = '
          <b># '.$_REQUEST['product_id'].' : '.$product_name.' was added to your cart.<br>
          <a href="orders_current.php">View your Cart</a> to increase the quantity.</b>';
        $unit_price = 0;
        $extra_charge = 0;
      }
  }
$sqls = '
  SELECT
    '.TABLE_BASKET_ALL.'.basket_id,
    '.TABLE_BASKET_ALL.'.delivery_id,
    '.TABLE_BASKET.'.basket_id,
    '.TABLE_BASKET.'.product_id,
    '.TABLE_BASKET.'.quantity,
    '.TABLE_BASKET.'.item_price,
    '.TABLE_BASKET.'.out_of_stock,
    '.TABLE_BASKET.'.total_weight,
    '.TABLE_BASKET.'.extra_charge,
    '.TABLE_BASKET.'.random_weight,
    '.TABLE_BASKET.'.product_id
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
  WHERE
    '.TABLE_BASKET_ALL.'.basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
    AND '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
  GROUP BY
    '.TABLE_BASKET.'.product_id';
$results = @mysql_query($sqls, $connection) or die("Couldn't execute query 1.");
while ( $row = mysql_fetch_array($results) )
  {
    $product_id = $row['product_id'];
    $item_price = $row['item_price'];
    $quantity = $row['quantity'];
    $out_of_stock = $row['out_of_stock'];
    $random_weight = $row['random_weight'];
    $total_weight = $row['total_weight'];
    $extra_charge = $row['extra_charge'];
    if( $out_of_stock != 1 )
      {
        if ( $random_weight == 1 )
          {
            if( $total_weight == 0 )
              {
              }
            else
              {
                $display_weight = $total_weight;
              }
            $item_total_3dec = number_format ((($item_price * $total_weight) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round ( $item_total_3dec, 2 );
          }
        else
          {
            $display_weight = "";
            $item_total_3dec = number_format ((($item_price * $quantity) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round ($item_total_3dec, 2);
          }
      }
    else
      {
        $display_weight = '';
        $item_total_price = '0';
      }
    if( $item_total_price )
      {
        $total = $item_total_price + $total;
      }
    $total_pr = $total_pr + $quantity;
    $subtotal_pr = $subtotal_pr + $item_total_price;
  }
mysql_free_result($results);