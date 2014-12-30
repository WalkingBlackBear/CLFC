<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

// Update the cart information
if ( $_POST['updatevalues'] == 'ys' )
  {
    // Get inventory amounts
    $sqli = '
      SELECT
        '.TABLE_PRODUCT.'.inventory_id,
        '.TABLE_PRODUCT.'.inventory_pull,
        FLOOR('.TABLE_INVENTORY.'.quantity / '.TABLE_PRODUCT.'.inventory_pull) AS inventory
      FROM
        '.TABLE_PRODUCT.'
      LEFT JOIN '.TABLE_INVENTORY.' ON '.TABLE_PRODUCT.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
      WHERE
        product_id = '.mysql_real_escape_string ($_POST['product_id']);
     $resulti = @mysql_query($sqli, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
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
        basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
        AND product_id = '.mysql_real_escape_string ($_POST['product_id']);
      $resultq = @mysql_query($sqlq,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ( $row = mysql_fetch_array($resultq) )
      {
        $quantity_before_change = $row['quantity_before_change'];
      }

    if ( $_POST['quantity'] < 0 )
      {
        $message2 = '<b>Please enter a quantity for the product.<br>To remove, enter the number 0.</b>';
      }
    elseif ( $inventory_id &&
            $inventory < $_POST['quantity'] - $quantity_before_change &&
            $inventory == 1)
      {
        $message2 = '<h3>There is only '.$inventory.' of Product ID # '.$_POST['product_id'].' available. Please add that quantity or less.</h3>';
      }
    elseif ( $inventory_id &&
            $inventory < $_POST['quantity'] - $quantity_before_change )
      {
        $message2 = '<h3>There are only '.$inventory.' of Product ID # '.$_POST['product_id'].' available. Please add that quantity or less.</h3>';
      }
    elseif ( $_POST['quantity'] == 0)
      {

        $sqld = '
          DELETE FROM
            '.TABLE_BASKET.'
          WHERE
            basket_id = '.mysql_real_escape_string (CurrentBasket::basket_id()).'
            AND product_id = '.mysql_real_escape_string ($_POST['product_id']);
        $resultdelete = @mysql_query($sqld, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        $message4 = "<b>Product was removed from basket.</b>";

        if ( $inventory_id )
          {
            $sqlus = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = quantity + '.mysql_real_escape_string ($quantity_before_change * $inventory_pull).'
              WHERE
                inventory_id = '.mysql_real_escape_string ($inventory_id);
            $resultus = @mysql_query($sqlus, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

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
          }

      }
    elseif ( ! is_numeric ($_POST['quantity']) )
      {
        $message2 = '<b>Please review the quantity: The quantity must be a number.</b>';
      }
    elseif ( $_POST['product_id'] )
      {
        $sqlu = '
          UPDATE
            '.TABLE_BASKET.'
          SET
            quantity = '.mysql_real_escape_string ($_POST['quantity']).',
            customer_notes_to_producer = "'.mysql_real_escape_string ($_POST['customer_notes_to_producer']).'"
          WHERE
            basket_id = '.mysql_real_escape_string (CurrentBasket::basket_id()).'
            AND product_id = '.mysql_real_escape_string ($_POST['product_id']);
        $result = @mysql_query($sqlu, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());

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
        $message2 = "<b>The information has been updated.</b>";
        if ( $inventory_id )
          {
            $sqlus = '
              UPDATE
                '.TABLE_INVENTORY.'
              SET
                quantity = quantity + '.mysql_real_escape_string ($inventory_pull * ($quantity_before_change - $_POST['quantity'])).'
              WHERE
                inventory_id = '.mysql_real_escape_string ($inventory_id);
            $resultus = @mysql_query($sqlus, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
          }
      }
    else
      {
        $message4 = 'No product choosen or no basket started. Please go to the <a href="index.php">main order page</a>.';
      }
  }
$display_page .= '
<table width="695" cellpadding="2" cellspacing="0" border="0">
  <tr><td colspan="9" align="right"><font face="'.$fontface.'">';

if ( $message4 )
  {
    $display_page .= '
      <div align="right"><font color="#770000">'.$message4.'</font></div>';
  }
$display_page .= '
  </td></tr>
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
  </tr>';

$sql = '
  SELECT
    '.TABLE_BASKET.'.*,
    '.TABLE_BASKET_ALL.'.*,
    '.TABLE_PRODUCER.'.*
  FROM
    '.TABLE_BASKET.'
    LEFT JOIN
      '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
    LEFT JOIN
      '.TABLE_PRODUCT.' ON '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
    LEFT JOIN
      '.TABLE_PRODUCER.' ON '.TABLE_BASKET.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
    AND '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id_next()).'"
  GROUP BY
    '.TABLE_BASKET.'.product_id
  ORDER BY
    business_name ASC,
  '.TABLE_PRODUCT.'.product_name ASC';
$result = @mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($result) )
  {
    $product_adjust_fee = $row['product_adjust_fee'] / 100;
    $subcat_adjust_fee = $row['subcat_adjust_fee'] / 100;
    $producer_adjust_fee = $row['producer_adjust_fee'] / 100;
    // Set the coop_markup according to auth_type
    if (CurrentMember::auth_type('institution')) $coop_markup = 1 + ActiveCycle::wholesale_markup();
    else $coop_markup = 1 + ActiveCycle::retail_markup();
    // Set the adjust_markup
    $adjust_markup = 1 + $product_adjust_fee + $subcat_adjust_fee + $producer_adjust_fee;
    $product_id = $row['product_id'];
    $producer_id = $row['producer_id'];
    $member_id_product = $row['member_id'];
    $business_name = $row['business_name'];
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
                $display_weight = '<font color="#770000" face="arial" size="-1">price updated after producer adds weight</font>';
                $message_incomplete = '<font color="#770000">Order Incomplete</font>';
              }
            else
              {
                $display_weight = $total_weight.' '.Inflect::pluralize_if ($display_weight, $pricing_unit);
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

    if ( $current_producer_id < 0 )
      {
        $current_producer_id = $row['producer_id'];
      }
    while ( $current_producer_id != $producer_id )
      {
        $current_producer_id = $producer_id;
        $display_page .= '
          <tr align="left" bgcolor=#DDDDDD>
            <td id="p_'.$producer_id.'"></td>
            <td>____</td>
            <td colspan="7"><br><font face="arial" color="#770000" size="-1"><b>'.$a_business_name.'</b></font></td>
          </tr>';
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
            '.TABLE_BASKET.'.basket_id = "'.mysql_real_escape_string (CurrentBasket::basket_id()).'"
            AND '.TABLE_BASKET.'.product_id = "'.mysql_real_escape_string ($product_id).'"
            AND '.TABLE_FUTURE_DELIVERY.'.future_delivery_id = '.TABLE_BASKET.'.future_delivery_id';
        $rs = @mysql_query($sqlfd, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        while ( $row = mysql_fetch_array($rs) )
          {
            $future_delivery_id = $row['future_delivery_id'];
            $future_delivery_dates = $row['future_delivery_dates'];
          }
        if ( $future_delivery_id )
          {
            $future = 'Delivery date: '.$future_delivery_dates.' <br>';
          }
        else
          {
            $future = '';
          }
        if ( $message2 &&
            ( $product_id == $_POST['product_id_printed']) )
          {
            $display_page .= '
              <tr align="center">
                <td align="right" valign="top" colspan="9"><font face="arial" size="-1"><font color="#770000">'.$message2.'</font></td>
              </tr>';
          }

        $qty_in_basket ++;
        $display_page .= '
          <tr align="center" bgcolor=#EEEEEE>
            <td align="center" valign="top" id="'.$product_id.'"><font face="arial" size="-1">
              <form action="#p_'.$producer_id.'" method="post">'.$display_outofstock.'
            </td>
            <td align="right" valign="top"><font face="arial" size="-1"><b>'.$product_id.'</b>&nbsp;&nbsp;</td>
            <td width="275" align="left" valign="top">
              <font face="arial" size="-1">
              <b>'.$product_name.'</b><br>
              '.$detailed_notes.'<br>
              '.$future.' <u>Notes to Producer</u>:<br>
              <textarea name="customer_notes_to_producer" cols="32" rows="2">'.$notes.'</textarea>
            </td>
            <td align="center" valign="top">
              <font face="arial" size="-1">$'.number_format($item_price, 2).'/'.$pricing_unit.'</td>
            <td align="left" valign="top">
              <font face="arial" size="-1">
              <input type="text" name="quantity" value="'.$quantity.'" size="2" maxlength="11">'.Inflect::pluralize_if ($quantity, $ordering_unit).'</td>
            <td align="center" valign="top"><font face="arial" size="-1">'.$display_weight.'</td>
            <td align="center" valign="top"><font face="arial" size="-1">'.$display_charge.'</td>
            <td align="right" valign="top"><font face="arial" size="-1">$'.number_format($item_total_price, 2).'</td>
            <td align="right" valign="top"><font face="arial" size="-1">
              <input type="hidden" name="updatevalues" value="ys">
              <input type="hidden" name="product_id" value="'.$product_id.'">
              <input type="hidden" name="product_id_printed" value="'.$product_id.'">
              <input type="hidden" name="producer_id" value="'.$producer_id.'">
              <input type="hidden" name="member_id" value="'.$_SESSION['member_id'].'">
              <input type="hidden" name="basket_id" value="'.CurrentBasket::basket_id().'">
              <input name="where" type="submit" value="Update"><br/>
              <font size="-2">[To edit or remove this item click this button for each item after editing.]</font>
              </form>
            </td>
          </tr>';
      }
  }
$display_page .= '
          <tr>
            <td colspan="9">'.$font.'<hr></td>
          </tr>
        </table>';
?>