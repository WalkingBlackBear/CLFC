<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

// Add a product to the cart
if ( $_REQUEST['yp'] == "ds" )
  {
    $sqlp = '
      SELECT
        product_id
      FROM
        '.TABLE_PRODUCT.'
      WHERE
        product_id = "'.mysql_real_escape_string ($product_id).'"';
    $resultp = @mysql_query($sqlp, $connection) or die(mysql_error());
    $nump = mysql_numrows($resultp);
    if ( $nump != 1 )
      {
        $message = '<H3>Product ID # '.$product_id.' does not exist in the system.</h3>';
      }

    // Get the time until the order closes
    $seconds_until_close = strtotime (ActiveCycle::date_closed()) - time();
    // Set up the "donotlist" field condition based on whether the member is an "institution" or not
    // Only institutions (and admins) are allowed to see donotlist=3 (wholesale products)
    if (CurrentMember::auth_type('institution,producer_admin') && $seconds_until_close < INSTITUTION_WINDOW)
      {
        $donotlist_condition = 'AND ('.TABLE_PRODUCT.'.donotlist = "0" OR '.TABLE_PRODUCT.'.donotlist = "3")';
      }
    else
      {
        $donotlist_condition = 'AND '.TABLE_PRODUCT.'.donotlist = "0"';
      }

    // Check to see if this product is available
    $sqldn = '
      SELECT
        product_id
      FROM
        '.TABLE_PRODUCT.'
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
      WHERE
        product_id = "'.mysql_real_escape_string ($product_id).'"
        AND '.TABLE_PRODUCER.'.pending = 0
        AND '.TABLE_PRODUCER.'.donotlist_producer = 0
        '.$donotlist_condition;
    $resultdn = @mysql_query($sqldn, $connection) or die(mysql_error());
    $numdn = mysql_numrows($resultdn);
    // No results means we can't add that product
    if ( $numdn == "0" )
      {
        $message = '<H3>Product ID # '.$product_id.' is currently unavailable.</h3>';
      }

    $sqli = '
      SELECT
        '.TABLE_PRODUCT.'.inventory_id,
        '.TABLE_PRODUCT.'.inventory_pull,
        FLOOR('.TABLE_INVENTORY.'.quantity / '.TABLE_PRODUCT.'.inventory_pull) AS inventory
      FROM
        '.TABLE_PRODUCT.'
      LEFT JOIN
        '.TABLE_INVENTORY.' ON '.TABLE_PRODUCT.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
      WHERE
        '.TABLE_PRODUCT.'.product_id = "'.mysql_real_escape_string ($product_id).'"';
    $resulti = @mysql_query($sqli, $connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($resulti) )
      {
        $inventory_id = $row['inventory_id'];
        $inventory = $row['inventory'];
        $inventory_pull = $row['inventory_pull'];
      }

    // Check to see if the product is available for this customer's delivery_id
    $sqldc = '
      SELECT
        GROUP_CONCAT(CONCAT_WS(",", '.TABLE_AVAILABILITY.'.delcode_id)) AS availability_list
      FROM
        '.TABLE_PRODUCT.'
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_PRODUCT.'.producer_id
      JOIN '.TABLE_AVAILABILITY.' ON '.TABLE_AVAILABILITY.'.producer_id = '.TABLE_PRODUCER.'.producer_id
      WHERE
        product_id LIKE "'.$product_id.'"';
    $resultdc = @mysql_query($sqldc, $connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($resultdc) )
      {
        $availability_list = $row['availability_list']; // List of delcode_id locations where this product is available
        $availability_array = explode (',', $availability_list);
        $delcode_id = CurrentBasket::delcode_id();
        $delcode = CurrentBasket::delcode();
      }
    // Get the availability for this product at this member's chosen delcode_id
    // Two conditions will allow products to be purchased (availability = true):
    //   1. No availibility set for the producer means the product is available everywhere
    //   2. Customer's delivery site is in the set of availabile locations for the producer
    if ($availability_list == '' || in_array ($delcode_id, $availability_array))
      {
        $availability = true;
      }
    // Otherwise the product is not available for this customer to purchase
    else
      {
        $availability = false;
      }

    // Check to see if the product is already in the basket
    $sqlb = '
      SELECT
        product_id
      FROM
        '.TABLE_BASKET.'
      WHERE
        product_id = "'.mysql_real_escape_string ($product_id).'"
        AND basket_id = "'.mysql_real_escape_string ($basket_id).'"';
    $resultb = @mysql_query($sqlb, $connection) or die(mysql_error());
    $numb = mysql_numrows($resultb);
    if ( $numb == "1" )
      {
        $message = '<H3>Product ID # '.$product_id.' is already in the basket.<br>Please edit the quantity of the item already listed if you need to add more.</h3>';
      }
    elseif ( ! $product_id )
      {
        $message = '<h3>Please enter a Product ID.</h3>';
      }
    elseif ( ! preg_match ('/^[0-9]+$/', $product_id) )
      {
        $message = '<h3>Please review the Product ID: The id must only be a number.</h3>';
      }
    elseif ( ! $quantity )
      {
        $message = '<h3>Please review the quantity: Please enter a quantity for the product.</h3>';
      }
    elseif ( ! preg_match ('/^[0-9]*$/', $quantity) )
      {
        $message = '<h3>Please review the quantity: The quantity must be a number.</h3>';
      }
    elseif ( $inventory_id && $inventory < 1 )
      {
        $message = '<h3>Product ID # '.$product_id.' is currently out of stock.</h3>';
      }
    elseif ($inventory_id && $inventory < $quantity)
      {
        $message = '<h3>There are only '.$inventory.' of Product ID # '.$product_id.' available. Please add that quantity or less.</h3>';
      }
    elseif ($availability == false)
      {
        $message = '<h3>Product ID # '.$product_id.' is not available for '.$delcode.'.</h3>';
      }
    elseif (($basket_id) && ($numdn != 0) && ($nump == 1) && ($numb != 1))
      {
        if ( $inventory_id && $inventory >= $quantity )
          {
            $sqlus = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = quantity - '.mysql_real_escape_string ($quantity * $inventory_pull).'
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
            product_id = "'.mysql_real_escape_string ($product_id).'"';
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

        // Insert a cart item with product information
        $sql = '
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
              "'.mysql_real_escape_string ($basket_id).'",
              "'.mysql_real_escape_string ($product_id).'",
              "'.mysql_real_escape_string ($producer_id).'",
              "'.mysql_real_escape_string ($product_name).'",
              "'.mysql_real_escape_string ($detailed_notes).'",
              "'.mysql_real_escape_string ($subcategory_id).'",
              "'.mysql_real_escape_string ($unit_price).'",
              "'.mysql_real_escape_string ($pricing_unit).'",
              "'.mysql_real_escape_string ($ordering_unit).'",
              "'.mysql_real_escape_string ($quantity).'",
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
        $result = @mysql_query($sql, $connection) or die(mysql_error());

        // Unfinalize the invoice
        $sql = '
          UPDATE
            '.TABLE_BASKET_ALL.'
          SET
            finalized = 0,
            invoice_content = ""
          WHERE
            basket_id = "'.mysql_real_escape_string ($basket_id).'"';
        $result = @mysql_query($sql, $connection) or die(mysql_error());
        mysql_free_result($result3);
      }
  }
?>