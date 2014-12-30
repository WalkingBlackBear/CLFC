<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

$total_weight = $_REQUEST['total_weight'];

if( $_REQUEST['updatevalues'] == 'ys' )
  {
    $sqli = '
      SELECT
        '.TABLE_PRODUCT.'.inventory_id,
        '.TABLE_PRODUCT.'.inventory_pull,
        FLOOR('.TABLE_INVENTORY.'.quantity / '.TABLE_PRODUCT.'.inventory_pull) AS inventory
      FROM
        '.TABLE_PRODUCT.'
      LEFT JOIN '.TABLE_INVENTORY.' ON '.TABLE_PRODUCT.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
      WHERE
        product_id = "'.mysql_real_escape_string ($product_id).'"';
    $resulti = @mysql_query($sqli, $connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($resulti) )
      {
        $inventory_id = $row['inventory_id'];
        $inventory = $row['inventory'];
        $inventory_pull = $row['inventory_pull'];
      }
    $sqlq = '
      SELECT
        quantity AS quantity_before_change
      FROM
        '.TABLE_BASKET.'
      WHERE
        basket_id = "'.mysql_real_escape_string ($basket_id).'"
        AND product_id = "'.mysql_real_escape_string ($product_id).'"';
    $resultq = @mysql_query($sqlq,$connection) or die(mysql_error());
    while ( $row = mysql_fetch_array($resultq) )
      {
        $quantity_before_change = $row['quantity_before_change'];
      }
    if ( $quantity < 0 )
      {
        $message2 = "<b>Please enter a quantity for the product.<br>To remove, enter the number 0.</b>";
      }
    elseif ( $inventory_id && $inventory < $quantity - $quantity_before_change && $inventory == 1 )
      {
        $message2 = "<H3>There is only $inventory of Product ID # $product_id available. Please add that quantity or less.</h3>";
      }
    elseif ( $inventory_id && $inventory < $quantity - $quantity_before_change )
      {
        $message2 = "<H3>There are only $inventory of Product ID # $product_id available. Please add that quantity or less.</h3>";
      }
    elseif ( $quantity == 0 )
      {
        $sqld = '
          DELETE FROM
            '.TABLE_BASKET.'
          WHERE
            basket_id = "'.mysql_real_escape_string ($basket_id).'"
            AND product_id = "'.mysql_real_escape_string ($product_id).'"';
        $resultdelete = @mysql_query($sqld, $connection) or die(mysql_error());
        $message4 = '<b>Product was removed from basket.</b>';
        if ( $inventory_id )
          {
            $sqlus = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = quantity + '.mysql_real_escape_string ($quantity_before_change * $inventory_pull).'
              WHERE
                inventory_id = "'.mysql_real_escape_string ($inventory_id).'"';
            $resultus = @mysql_query($sqlus, $connection) or die("Could not execute query updating stock in public product list.");
          }
      }
    elseif ( ! is_numeric ($quantity) )
      {
        $message2 = '<b>Please review the quantity: The quantity must be a number.</b>';
      }
    elseif ( $product_id )
      {
        $sqlu = '
          UPDATE
            '.TABLE_BASKET.'
          SET
            quantity = "'.mysql_real_escape_string ($quantity).'",
            total_weight = "'.mysql_real_escape_string ($total_weight).'",
            customer_notes_to_producer = "'.mysql_real_escape_string ($customer_notes_to_producer).'"
          WHERE
            basket_id = "'.mysql_real_escape_string ($basket_id).'"
            AND product_id = "'.mysql_real_escape_string ($product_id).'"';
        $result = @mysql_query($sqlu, $connection) or die(mysql_error());
        $message2 = '<b>The information has been updated.</b>';
        if ( $inventory_id )
          {
            $sqlus = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = quantity + '.mysql_real_escape_string (($quantity_before_change - $quantity) * $inventory_pull).'
              WHERE
                inventory_id = "'.mysql_real_escape_string ($inventory_id).'"';
            $resultus = @mysql_query($sqlus, $connection) or die("Could not execute query updating stock in public product list.");
          }
      }
    else
      {
        $message4 = 'No product choosen or no basket started. Please go to the <a href="index.php">main order page</a>.';
      }
  }

$display_page .= '
  <table width="695" cellpadding="2" cellspacing="0" border="0">
    <tr>
      <td colspan="9" align="right"><font face="'.$fontface.'">';
if ( $message4 )
  {
    $display_page .= '<div align="right"><font color="#770000">'.$message4.'</font></div>';
  }
$display_page .= '
      </td>
    </tr>
    <tr>
      <td colspan="9"><hr></td>
    </tr>
    <tr>
      <th valign="bottom"><font face="'.$fontface.'" size="-1"></th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">#</th>
      <th valign="bottom" align="left"><font face="'.$fontface.'" size="-1">Product Name</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Price</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Quantity</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Total<br>Weight</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Extra<br>Charge</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Amount</th>
      <th valign="bottom"><font face="'.$fontface.'" size="-1">Edit</th>
    </tr>
    <tr>
      <td colspan="9"><hr></td>
    </tr>
';

// Get the delivery markup/down information
$query = '
  SELECT
    delivery_date,
    producer_markdown,
    wholesale_markup,
    retail_markup
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
$result = @mysql_query($query, $connection) or die(mysql_error());
if ( $row = mysql_fetch_array($result) )
  {
    $producer_markdown = $row['producer_markdown'] / 100;
    $retail_markup = $row['retail_markup'] / 100;
    $wholesale_markup = $row['wholesale_markup'] / 100;
  }

$sql = '
  SELECT
    '.TABLE_BASKET_ALL.'.*,
    '.TABLE_BASKET.'.*,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.auth_type,
    '.TABLE_PRODUCER.'.business_name
  FROM
    '.TABLE_BASKET_ALL.'
  LEFT JOIN
    '.TABLE_BASKET.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  LEFT JOIN
    '.TABLE_PRODUCT.' ON '.TABLE_PRODUCT.'.product_id = '.TABLE_BASKET.'.product_id
  LEFT JOIN
    '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.producer_id = '.TABLE_BASKET.'.producer_id
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($member_id).'"
    AND '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.TABLE_BASKET.'.product_id
  ORDER BY
    '.TABLE_PRODUCER.'.business_name ASC,
    last_name ASC,
    product_name ASC';
$result = @mysql_query($sql, $connection) or die("Couldn't execute query 1.");
while ( $row = mysql_fetch_array($result) )
  {
    $product_adjust_fee = $row['product_adjust_fee'] / 100;
    $subcat_adjust_fee = $row['subcat_adjust_fee'] / 100;
    $producer_adjust_fee = $row['producer_adjust_fee'] / 100;
    // Set the coop_markup according to auth_type
    if (in_array ('institution', explode (',', $row['auth_type']))) $coop_markup = 1 + $wholesale_markup;
    else $coop_markup = 1 + $retail_markup;
    // Set the adjust_markup
    $adjust_markup = 1 + $product_adjust_fee + $subcat_adjust_fee + $producer_adjust_fee;
    $product_id = $row['product_id'];
    $producer_id = $row['producer_id'];
    $member_id_product = $row['member_id'];

    $a_business_name = $row['business_name'];
    $product_name = $row['product_name'];
    // Set the display_unit_price according to SHOW_ACTUAL_PRICE
    if (SHOW_ACTUAL_PRICE) $item_price = round ($row['item_price'] * $adjust_markup * $coop_markup, 2);
    else $item_price = round ($row['item_price'] * $adjust_markup, 2);
    // $item_price = round ($row['item_price'] * $coop_markup, 2);
    $pricing_unit = $row['pricing_unit'];
    $detailed_notes = $row['detailed_notes'];
    $quantity = $row['quantity'];
    $ordering_unit = $row['ordering_unit'];
    $out_of_stock = $row['out_of_stock'];
    $random_weight = $row['random_weight'];
    $total_weight = $row['total_weight'];
    $extra_charge = $row['extra_charge'];
    $notes = $row['customer_notes_to_producer'];
    $future_delivery_id = $row['future_delivery_id'];
    $item_date = $row['item_date'];
    if ( $out_of_stock != 1 )
      {
        if ( $random_weight == 1 )
          {
            if ( $total_weight == 0 || $total_weight == '' )
              {
                $display_weight = '<input type="text" name="total_weight" value="'.$total_weight.'" size="5" maxlength="11">';
                $message_incomplete = '<font color="#770000">Order Incomplete</font>';
              }
            else
              {
                $display_weight = '<input type="text" name="total_weight" value="'.$total_weight.'" size="5" maxlength="11">';
              }
            $item_total_3dec = round ((($item_price * $total_weight) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round ($item_total_3dec, 2);
          }
        else
          {
            $display_weight = '';
            $item_total_3dec = round ((($item_price * $quantity) + ($quantity * $extra_charge)), 3) + 0.00000001;
            $item_total_price = round ($item_total_3dec, 2);
          }
      }
    else
      {
        $display_weight = '';
        $item_total_price = 0;
      }
    if ( $out_of_stock )
      {
        $display_outofstock = '<img src="grfx/checkmark_wht.gif"><br>';
      }
    else
      {
        $display_outofstock = '';
      }
    $display_ordering_unit = Inflect::pluralize_if ($quantity, $ordering_unit);
    $display_pricing_unit = Inflect::pluralize_if ($quantity, $pricing_unit);
    if ( $extra_charge )
      {
        $display_charge = '$'.number_format($extra_charge, 2);
      }
    else
      {
        $display_charge = '';
      }
    if ( $item_total_price )
      {
        $total = $item_total_price + $total;
      }
    $total_pr = $total_pr + $quantity;
    $subtotal_pr = $subtotal_pr + $item_total_price;
    if ( $producer_id_prev < 0 )
      {
        $producer_id_prev = $row['producer_id'];
      }
    if ( $producer_id_prev != $producer_id )
      {
        $producer_id_prev = $producer_id;
        $display_page .= '
          <tr align="left">
            <td id="p_'.$producer_id.'"></td>
            <td>____</td>
            <td colspan="6"><br>';
        $display_page .= '<font face="arial" color="#770000" size="-1"><b>'.$a_business_name.'</b></font></td></tr>';
      }
    if ( $current_product_id < 0 )
      {
        $current_product_id = $row['product_id'];
      }
    while ( $current_product_id != $product_id )
      {
        $current_product_id = $product_id;
        $future_delivery_id = '';
        $sqlfd = '
          SELECT
            '.TABLE_BASKET.'.basket_id,
            '.TABLE_BASKET.'.product_id,
            '.TABLE_BASKET.'.future_delivery_id,
            '.TABLE_FUTURE_DELIVERY.'.*
          FROM
            '.TABLE_BASKET.',
            '.TABLE_FUTURE_DELIVERY.'
          WHERE
            '.TABLE_BASKET.'.basket_id = "'.mysql_real_escape_string ($basket_id).'"
            AND '.TABLE_BASKET.'.product_id = "'.mysql_real_escape_string ($product_id).'"
            AND '.TABLE_FUTURE_DELIVERY.'.future_delivery_id = '.TABLE_BASKET.'.future_delivery_id';
        $rs = @mysql_query($sqlfd,$connection) or die("Could not execute query.");
        while ( $row = mysql_fetch_array($rs) )
          {
            $future_delivery_id = $row['future_delivery_id'];
            $future_delivery_dates = $row['future_delivery_dates'];
          }
        if( $future_delivery_id )
          {
            $future = 'Delivery date: '.$future_delivery_dates.' <br>';
          }
        else
          {
            $future = '';
          }
        if ( ($message2) && ($product_id == $_POST['product_id_printed']) )
          {
            $display_page .= '
              <tr align="center">
                <td align="right" valign="top" colspan="9"><font face="arial" size="-1"><font color="#770000">'.$message2.'</font></td>
              </tr>';
          }
        $display_page .= '
          <tr align="center">
            <td align="center" valign="top" id="'.$product_id.'"><font face="arial" size="-1">
              <form action="#p_'.$producer_id.'" method="post">'.$display_outofstock.'</td>
            <td align="right" valign="top"><font face="arial" size="-1"><b>'.$product_id.'</b>&nbsp;&nbsp;</td>
            <td width="275" align="left" valign="top"><font face="arial" size="-1">
              <b>'.$product_name.'</b><br>'.$detailed_notes.'<br>'.$future.' <u>Notes to Producer</u>:<br>
              <textarea name="customer_notes_to_producer" cols="32" rows="2">'.$notes.'</textarea>';
        $display_page .= '</td>
          <td align="center" valign="top"><font face="arial" size="-1">$'.number_format($item_price, 2).'/'.$pricing_unit.'</td>
          <td align="left" valign="top"><font face="arial" size="-1">
            <input type="text" name="quantity" value="'.$quantity.'" size="3" maxlength="11"> '.$display_ordering_unit.'</td>
          <td align="center" valign="top"><font face="arial" size="-1">'.$display_weight.' '.$display_pricing_unit.'</td>
          <td align="center" valign="top"><font face="arial" size="-1">'.$display_charge.'</td>
          <td align="right" valign="top" class="price"><font face="arial" size="-1">$'.number_format($item_total_price,2).'</td>
          <td align="right" valign="top"><font face="arial" size="-1">
            <input type="hidden" name="updatevalues" value="ys">
            <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
            <input type="hidden" name="product_id" value="'.$product_id.'">
            <input type="hidden" name="product_id_printed" value="'.$product_id.'">
            <input type="hidden" name="producer_id" value="'.$producer_id.'">
            <input type="hidden" name="member_id" value="'.$member_id.'">
            <input type="hidden" name="basket_id" value="'.$basket_id.'">
            <input name="where" type="submit" value="Update">
            </form></td>
            </tr>';
      }
  }
$display_page .= '
  <tr>
  <td colspan="9">'.$font.'
  <hr>
  </td>
  </tr></table>';
