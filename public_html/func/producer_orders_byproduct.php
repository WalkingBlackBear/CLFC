<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

//                                                                           //
// This script will create a table of producer orders, by product and        //
// handle forms for the updating of single products.  It requires that the   //
// following variables already be set:                                       //
//                                                                           //
//                 $current_delivery_id                                      //
//                 $producer_id_you                                          //
//                                                                           //


// If we don't have a producer_id then get one from the arguments
if ($_GET['producer_id'] && CurrentMember::auth_type('producer_admin,site_admin'))
  {
    $producer_id = $_GET['producer_id'];
  }
else
  {
    $producer_id = $_SESSION['producer_id_you'];
  }

// If no delivery id was passed, then use the current value
if ($_GET['delivery_id'])
  {
    $delivery_id = $_GET['delivery_id'];
  }
else
  {
    $delivery_id = ActiveCycle::delivery_id();
  }

$total_extra = 0;

$sqlp = '
  SELECT
    '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.member_id,
    '.TABLE_PRODUCER.'.business_name,
    '.TABLE_BASKET.'.producer_id,
    '.TABLE_BASKET.'.product_id
  FROM
    '.TABLE_PRODUCER.'
  LEFT JOIN
    '.TABLE_BASKET.' ON '.TABLE_BASKET.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  WHERE
    '.TABLE_PRODUCER.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
  GROUP BY
    '.TABLE_PRODUCER.'.producer_id
  ORDER BY
    business_name ASC';
$resultp = @mysql_query($sqlp, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($resultp) )
  {
    $a_business_name = $row['business_name'];
  }
$producer_orders_byproduct = '
      <table width="100%" cellpadding="4" cellspacing="0" border="0">
        <tr>
          <td colspan="2" align="left"><font size=4>Sorted by Product</font><br>NOTE: Sections are sorted with the items purchased first at the top</td>
          <td colspan="6" align="right">
            Click for invoice sorted by <a href="orders_prdcr_cust.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">customer</a><br>
            Click for invoice sorted by <a href="orders_prdcr_cust_storage.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">storage/customer</a><br>
            Click for invoice with <a href="orders_prdcr_multi.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">multi-sort/mass-update</a><br>
            Click for <a href="producer_labels.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">labels (one per product/customer)</a><br>
            Click for <a href="producer_labelsc.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">labels (one per storage/customer)</a>
          </td>
        </tr>
        <tr bgcolor="#9CA5B5">
          <th valign="bottom" align="center">Mem.ID</th>
          <th valign="bottom" align="center">Member</th>
          <th valign="bottom" align="center">Quantity</th>
          <th valign="bottom" align="center">Weight</th>
          <th valign="bottom" align="center">'.(SHOW_ACTUAL_PRICE == true? 'Customer<br>' : ucfirst (ORGANIZATION_TYPE).'<br>' ).'Price</th>
          <th valign="bottom" align="center">In<br>Stock?</th>
          <th valign="bottom" align="center">Item<br>Total</th>
          <th valign="bottom" align="center">Edit<br>Item</th>
        </tr>';
$sql = '
  SELECT
    '.TABLE_BASKET.'.*
  FROM
    '.TABLE_BASKET.',
    '.TABLE_BASKET_ALL.'
  WHERE
    '.TABLE_BASKET_ALL.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
    AND '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
    AND '.TABLE_BASKET.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
    AND '.TABLE_BASKET.'.hidefrominvoice = 0
  GROUP BY
    '.TABLE_BASKET.'.product_id
  ORDER BY
    product_name ASC';

$rs = @mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_array($rs) )
  {
    $c_basket_id = $row['basket_id'];
    $product_id = $row['product_id'];
    $product_name = $row['product_name'];
    //$item_price = $row['item_price'];
    $pricing_unit = $row['pricing_unit'];
    $producer_orders_byproduct .= '<tr bgcolor=#dddddd><td colspan="8" id="'.$product_id.'">
    <font size=4>'.$product_name.' (Product ID# '.$product_id.')</font><br>
    <!--<b>$'.number_format((double)$item_price, 2).'/'.$pricing_unit.'</b>-->
    </td></tr>';
    $total_pr = 0;
    $subtotal_pr = 0;
      //ORDER BY '.TABLE_BASKET.'.basket_id ASC
    $sql = '
      SELECT
        '.TABLE_BASKET.'.*,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.business_name,
        '.TABLE_MEMBER.'.preferred_name,
        '.TABLE_MEMBER.'.member_id
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN
        '.TABLE_BASKET.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
      WHERE
        '.TABLE_BASKET.'.product_id = '.mysql_real_escape_string ($product_id).'
        AND
          (
            '.TABLE_BASKET_ALL.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
            OR '.TABLE_BASKET.'.future_delivery_id = '.mysql_real_escape_string ($delivery_id).'
          )
        AND '.TABLE_BASKET.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
      GROUP BY
        '.TABLE_BASKET.'.basket_id
      ORDER BY
        '.TABLE_BASKET.'.item_date';
    $resultpr = @mysql_query($sql, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    while ( $row = mysql_fetch_array($resultpr) )
      {
        $c_basket_id = $row['basket_id'];
        $product_id = $row['product_id'];
        $product_name = $row['product_name'];
        $quantity = $row['quantity'];
        $random_weight = $row['random_weight'];
        $total_weight = $row['total_weight'];
        $item_price = $row['item_price'];
        $out_of_stock = $row['out_of_stock'];
        $ordering_unit = $row['ordering_unit'];
        $extra_charge = $row['extra_charge'];
        $product_adjust_fee = $row['product_adjust_fee'] / 100;
        $subcat_adjust_fee = $row['subcat_adjust_fee'] / 100;
        $producer_adjust_fee = $row['producer_adjust_fee'] / 100;
        //$detailed_notes = $row['detailed_notes'];
        $future_delivery_id = $row['future_delivery_id'];
        $notes = $row['customer_notes_to_producer'];
        $member_id = $row['member_id'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $business_name = $row['business_name'];
        $preferred_name = $row['preferred_name'];
        $item_total_price = "";
        if ( $out_of_stock != 1 )
          {
            $total_pr = $total_pr + $quantity;
            if ( $random_weight == 1 )
              {
                if ( $total_weight == 0 )
                  {
                    //$product_weight = "<input type=\"text\" name=\"total_weight\" value=\"$total_weight\" size=\"2\" maxlength=\"11\"> ".$pricing_unit."s";
                    $product_weight = '<input type="text" name="total_weight" value="'.$total_weight.'" size="5" maxlength="11"> '.$pricing_unit;
                    $show_update_button = 'yes';
                    $item_total_3dec = ($item_price * $total_weight) + 0.00000001;
                    $item_total_price = round($item_total_3dec, 2);
                    // $item_total_price = number_format($item_total_price, 2);
                    $display_unit_price = $item_total_price;
                    $message_incomplete = '<font color="#770000">Order Incomplete<font>';
                  }
                else
                  {
                    //$product_weight = "<input type=\"text\" name=\"total_weight\" value=\"$total_weight\" size=\"2\" maxlength=\"11\"> ".$pricing_unit."s";
                    $product_weight = '<input type="text" name="total_weight" value="'.$total_weight.'" size="5" maxlength="11"> '.Inflect::pluralize ($pricing_unit);
                    $show_update_button = 'yes';
                    $item_total_3dec = (($item_price*$total_weight)+($extra_charge*$quantity)) + 0.00000001;
                    $item_total_price = round($item_total_3dec, 2);
                    // $item_total_price = number_format($item_total_price, 2);
                    $display_unit_price = $item_total_price;
                  }
              }
            else
              {
                $product_weight = "";
                $show_update_button= 'no';
                $item_total_3dec = (($item_price * $quantity) + ($extra_charge * $quantity)) + 0.00000001;
                $item_total_price = round($item_total_3dec, 2);
                // $item_total_price = number_format($item_total_price, 2);
                $display_unit_price = $item_total_price;
              }
          }
        else
          {
             $total_pr = $total_pr;
             $product_weight = '';
             $show_update_button = 'no';
             $item_total_price = number_format(0, 2);
          }
        $product_total_price = '';
        if ( $future_delivery_id == ActiveCycle::delivery_id() )
          {
            $product_weight = '';
            $item_total_price = '';
            $product_total_price = '<font color=#FF0000>Invoiced in a previous order</font>';
          }
        elseif ( $future_delivery_id > ActiveCycle::delivery_id() )
          {
            $product_total_price = '<font color=#FF0000>Will be delivered in future order</font>';
          }
        if ( $out_of_stock == 1 )
          {
            $product_outofstock = '<img src="'.BASE_URL.DIR_GRAPHICS.'checkmark_wht.gif" align=right>';
            $extra_charge = 0; // If not sold, then no extra charge
            $chk1 = '';
            $chk2 = 'checked';
          }
        else
          {
            $product_outofstock = '';
            $chk1 = 'checked';
            $chk2 = '';
          }
        if ( $extra_charge )
          {
            $extra_charge_calc = $extra_charge*$quantity;
            $total_extra = $total_extra + round ($extra_charge_calc, 2);
            $product_charge = '$'.number_format($extra_charge_calc, 2);
          }
        else
          {
            $product_charge = "";
          }
        $product_stock = '
          <input type="radio" name="out_of_stock" value="0" '.$chk1.'>In<br>
          '.$product_outofstock.'
          <input type="radio" name="out_of_stock" value="1" '.$chk2.'>Out';
        if ( $item_total_price )
          {
            $total = $item_total_price + $total;
          }
        $subtotal_pr = $subtotal_pr + $item_total_price;
        if ( $notes )
          {
            $product_notes = '<br>Customer note: '.$notes;
          }
        else
          {
            $product_notes = '';
          }
        if ( $current_product_id < 0 )
          {
            $current_product_id = $row['product_id'];
          }
        while ( $current_product_id != $product_id )
          {
            $current_product_id = $product_id;
          }
        if ( $item_total_price )
          {
            $product_total_price = '$ '.number_format ($item_total_price, 2);
          }
        // Set the coop_markup according to auth_type
        if (CurrentMember::auth_type('institution')) $coop_markup = 1 + $wholesale_markup;
        else $coop_markup = 1 + $retail_markup;
        // Set the adjust_markup
        $adjust_markup = 1 + $product_adjust_fee + $subcat_adjust_fee + $producer_adjust_fee;
        // Set the display_unit_price according to SHOW_ACTUAL_PRICE
        if (SHOW_ACTUAL_PRICE) $display_unit_price = round ($item_price * $adjust_markup * $coop_markup, 2);
        else $display_unit_price = round ($item_price * $adjust_markup, 2);
        $display_price = '';
        if ( $display_unit_price != 0 )
          {
            $display_price .= $font.' $'.number_format($display_unit_price, 2).'/'.$pricing_unit.'';
          }
        if ( $display_unit_price != 0 && $extra_charge != 0 ) $display_price .= '<br>and<br>';
        if ( $extra_charge != 0 )
          {
            $display_price .= '$'.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
          }

        $producer_orders_byproduct .= '
          <tr align="center">
            <td align="right" valign="top"><form action="'.$PHP_SELF.'?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'#'.$product_id.'" method="post"><b># '.$member_id.'</b>&nbsp;&nbsp;</td>
            <td align="left" valign="top"><b>'.$preferred_name.'</b>'.$product_notes.'</td>
            <td align="center" valign="top">'.$quantity.' '.Inflect::pluralize_if ($quantity, $ordering_unit).'</td>
            <td align="center" valign="top">'.$product_weight.'</td>
            <td align="center" valign="top">'.$display_price.'</td>
            <td align="left" valign="top">'.$product_stock.'</td>
            <td align="center" valign="top">'.$product_total_price.'</td>
            <td align="center" valign="top">';
        $producer_orders_byproduct .= '
          <input type="hidden" name="updatevalues" value="ys">
          <input type="hidden" name="product_id" value="'.$product_id.'">
          <input type="hidden" name="product_id_printed" value="'.$product_id.'">
          <input type="hidden" name="producer_id" value="'.$producer_id_you.'">
          <input type="hidden" name="member_id" value="'.$member_id.'">
          <input type="hidden" name="c_member_id" value="'.$member_id.'">
          <input type="hidden" name="c_basket_id" value="'.$c_basket_id.'">
          <input name="where" type="submit" value="Update">
          </form>';
        if ( $product_id == $_POST['product_id_printed'] && $member_id == $c_member_id )
          {
            $producer_orders_byproduct .= '<p>'.$message2.'</p>';
          }
        else
          {
            $producer_orders_byproduct .= '';
          }
        $producer_orders_byproduct .= '
            </td>
          </tr>';
      }
    $producer_orders_byproduct .= '
          <tr>
            <td colspan="8">Product Quantity: '.$total_pr.'</td>
          </tr>
          <tr>
            <td colspan="8">Product subtotal: $'.number_format($subtotal_pr, 2).'</td>
          </tr>';
  }
$producer_orders_byproduct .= '
      </table>';

?>