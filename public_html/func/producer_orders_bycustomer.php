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
    business_name
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    producer_id = "'.mysql_real_escape_string ($producer_id).'"
  GROUP BY
    producer_id
  ORDER BY
    business_name ASC';
$resultp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 521637 ", array ($sqlp,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($resultp) )
  {
    $a_business_name = $row['business_name'];
  }
$producer_orders_bycustomer = '
      <table width="100%" cellpadding="4" cellspacing="0" border="0">
        <tr>
          <td colspan="2" align="left">
            <font size=4>Sorted by Customer</font>
            </td><td colspan="6" align="right">
            Click for invoice sorted by <a href="orders_prdcr.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">product</a><br>
            Click for invoice sorted by <a href="orders_prdcr_cust_storage.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">storage/customer</a><br>
            Click for invoice with <a href="orders_prdcr_multi.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">multi-sort/mass-update</a><br>
            Click for <a href="producer_labels.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">labels (one per product/customer)</a><br>
            Click for <a href="producer_labelsc.php?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">labels (one per storage/customer)</a>
          </td>
        </tr>
        <tr bgcolor="#9CA5B5">
          <th valign="bottom" align="center">PrdID</th>
          <th valign="bottom" align="center">Member</th>
          <th valign="bottom" align="center">Quantity</th>
          <th valign="bottom" align="center">Weight</th>
          <th valign="bottom" align="center">In<br>Stock?</th>
          <th valign="bottom" align="center">Item<br>Total</th>
          <th valign="bottom" align="center">Edit<br>Item</th>
        </tr>';
$sqlpr = '
  SELECT
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.producer_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.product_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.quantity,
    '.NEW_TABLE_BASKET_ITEMS.'.subcategory_fee_percent,
    '.NEW_TABLE_BASKET_ITEMS.'.total_weight,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.deltype as ddeltype,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_MESSAGES.'.message AS notes,
    '.NEW_TABLE_PRODUCTS.'.extra_charge,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_PRODUCTS.'.pricing_unit,
    '.NEW_TABLE_PRODUCTS.'.product_description,
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.NEW_TABLE_PRODUCTS.'.random_weight,
    '.NEW_TABLE_PRODUCTS.'.unit_price,
    '.TABLE_DELCODE.'.delcode,
    '.TABLE_DELCODE.'.delcode_id,
    '.TABLE_DELCODE.'.deltype,
    '.TABLE_DELCODE.'.hub,
    '.TABLE_DELCODE.'.truck_code,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.email_address,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.home_phone,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.mem_taxexempt,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code,
    '.TABLE_SUBCATEGORY.'.category_id
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN
    '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
  LEFT JOIN
    '.TABLE_DELCODE.' ON '.TABLE_DELCODE.'.delcode_id = '.NEW_TABLE_BASKETS.'.delcode_id
  LEFT JOIN
    '.TABLE_ROUTE.' ON '.TABLE_ROUTE.'.route_id = '.TABLE_DELCODE.'.route_id
  LEFT JOIN
    '.NEW_TABLE_PRODUCTS.' ON
      ('.NEW_TABLE_PRODUCTS.'.product_id = '.NEW_TABLE_BASKET_ITEMS.'.product_id
      AND '.NEW_TABLE_PRODUCTS.'.product_version = '.NEW_TABLE_BASKET_ITEMS.'.product_version)
  LEFT JOIN
    '.TABLE_SUBCATEGORY.' ON '.TABLE_SUBCATEGORY.'.subcategory_id = '.NEW_TABLE_PRODUCTS.'.subcategory_id
  LEFT JOIN
    '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.NEW_TABLE_PRODUCTS.'.storage_id = '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id
  LEFT JOIN
    '.NEW_TABLE_MESSAGE_TYPES.' ON ('.NEW_TABLE_MESSAGE_TYPES.'.description = "customer notes to producer")
  LEFT JOIN
    '.NEW_TABLE_MESSAGES.' ON
      ('.NEW_TABLE_MESSAGES.'.message_type_id = '.NEW_TABLE_MESSAGE_TYPES.'.message_type_id
      AND referenced_key1 = '.NEW_TABLE_BASKET_ITEMS.'.bpid)
  WHERE
    '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.hide_from_invoice ="0"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_BASKET_ITEMS.'.product_id
  ORDER BY
    '.TABLE_DELCODE.'.delcode_id ASC,
    '.NEW_TABLE_BASKETS.'.member_id ASC,
    '.TABLE_DELCODE.'.hub ASC,
    '.NEW_TABLE_BASKET_ITEMS.'.product_id ASC';

$resultpr = @mysql_query($sqlpr, $connection) or die(debug_print ("ERROR: 665464 ", array ($sqlpr,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($resultpr) )
  {
    $product_name = $row['product_name'];
    $product_id = $row['product_id'];
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $preferred_name = $row['preferred_name'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    $hub = $row['hub'];
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    $deltype = $row['deltype'];
    $truck_code = $row['truck_code'];
    $storage_code = $row['storage_code'];
    $quantity = $row['quantity'];
    $ordering_unit = $row['ordering_unit'];
    $unit_price = $row['unit_price'];
    $email_address = $row['email_address'];
    $home_phone = $row['home_phone'];
    $ddeltype = $row['ddeltype'];
    $mem_taxexempt = $row['mem_taxexempt'];
    $category_id = $row['category_id'];
    $random_weight = $row['random_weight'];
    $total_weight = $row['total_weight'];
    $out_of_stock = $row['out_of_stock'];
    $extra_charge = $row['extra_charge'];
    $product_fee_percent = $row['product_adjust_fee'] / 100;
    $subcategory_fee_percent = $row['subcategory_fee_percent'] / 100;
    $producer_fee_percent = $row['producer_fee_percent'] / 100;
    $product_description = $row['product_description'];
    $notes = $row['notes'];
    $pricing_unit = $row['pricing_unit'];

    $future_delivery_id = 0;

    if ( $sc==$row['storage_code'] && $m == $member_id )
      {
        //skip
      }
    else
      {
        $route_code_info = &$row;
        $producer_orders_bycustomer .= '
          <tr bgcolor="#DDDDDD">
            <td colspan="8" id="'.$member_id.'">
              <font size="4"><font color="#770000">'.(convert_route_code($route_code_info)).'</font></font> /
              <b>Producer: '.$a_business_name.'</b><br>
              <font size="4">Member: '.$preferred_name.' Mem# '.$member_id.':</font>
              '.$home_phone.' <a href="mailto:'.$email_address.'">'.$email_address.'</a><br>
          </td>
          </tr>';
        $sc = $row['storage_code'];
        $m = $member_id;
      }
    if ( $out_of_stock == 1 )
      {
        $display_total_price = '$'.number_format(0, 2);
      }
    if ( $future_delivery_id == $delivery_id )
      {
        $display_weight = '';
        $item_total_price = 0;
        $display_total_price = '<font color="#FF0000">Invoiced in a previous order</font>';
      }
    elseif ( $future_delivery_id > $delivery_id )
      {
        $display_weight = '';
        $item_total_price = 0;
        $display_total_price = '<font color="#FF0000">Will be delivered in future order</font>';
      }
    elseif ( $out_of_stock!= 1 )
      {
        if ( $random_weight== 1 )
          {
            if ( $total_weight== 0 )
              {
                //$display_weight = "<input type=\"text\" name=\"total_weight\" value=\"$total_weight\" size=\"2\" maxlength=\"11\"> ".$pricing_unit."s";
                $display_weight = '<input type="text" name="total_weight" value="'.$total_weight.'" size="5" maxlength="11"> '.$pricing_unit;
                $show_update_button = 'yes';
                $item_total_3dec = ($unit_price * $total_weight) + 0.00000001;
                $item_total_price = round($item_total_3dec, 2);
                $display_total_price = '$'.number_format($item_total_price, 2)."";
                $display_unit_price = $item_total_price;
                $message_incomplete = '<font color="#770000">Order Incomplete<font>';
              }
            else
              {
                //$display_weight = "<input type=\"text\" name=\"total_weight\" value=\"$total_weight\" size=\"2\" maxlength=\"11\"> ".$pricing_unit."s";
                $display_weight = '<input type="text" name="total_weight" value="'.$total_weight.'" size="5" maxlength="11"> '.Inflect::pluralize ($pricing_unit);
                $show_update_button = 'yes';
                $item_total_3dec = (($unit_price * $total_weight) + ($extra_charge * $quantity)) + 0.00000001;
                $item_total_price = round($item_total_3dec, 2);
                $display_unit_price = $item_total_price;
                $display_total_price = '$'.number_format($item_total_price, 2);
              }
          }
        else
          {
            $display_weight = '';
            $show_update_button = 'no';
            $item_total_3dec = (($unit_price * $quantity) + ($extra_charge * $quantity)) + 0.00000001;
            $item_total_price = round($item_total_3dec, 2);
            $display_unit_price = $item_total_price;
            $display_total_price = '$'.number_format($item_total_price, 2);
          }
      }
    else
      {
        $display_weight = '';
        $show_update_button = 'no';
        $item_total_price = '0';
      }
    if ( $out_of_stock )
      {
        $display_outofstock = '<img src="'.BASE_URL.DIR_GRAPHICS.'checkmark_wht.gif" align="left">';
        $extra_charge = 0; // If not sold, then no extra charge
        $chk1 = '';
        $chk2 = 'checked';
      }
    else
      {
        $display_outofstock = '';
        $chk1 = 'checked';
        $chk2 = '';
      }
    if ( $extra_charge )
      {
        $extra_charge_calc = $extra_charge * $quantity;
        $total_extra = $total_extra + round ($extra_charge_calc, 2);
        $display_charge = '$'.number_format($extra_charge_calc, 2);
      }
    else
      {
        $display_charge = "";
      }
    $display_stock = '
      <input type="radio" name="out_of_stock" value="0" '.$chk1.'>In<br>
      <input type="radio" name="out_of_stock" value="1" '.$chk2.'>Out';
    if ( $item_total_price )
      {
        $total = $item_total_price + $total;
      }
    $total_pr = $total_pr + $quantity;
    $subtotal_pr = $subtotal_pr + $item_total_price;
    if ( $notes )
      {
        $display_notes = '<br><b>Customer note</b>: '.$notes;
      }
    else
      {
        $display_notes = '';
      }
    if ( $quantity > 1 )
      {
        //$display_ordering_unit = "".$ordering_unit."s";
        $display_ordering_unit = $ordering_unit;
      }
    else
      {
        $display_ordering_unit = $ordering_unit;
      }
    // Set the coop_markup according to auth_type
    if (CurrentMember::auth_type('institution')) $coop_markup = 1 + $wholesale_markup;
    else $coop_markup = 1 + $retail_markup;
    // Set the adjust_markup
    $adjust_markup = 1 + $product_fee_percent + $subcategory_fee_percent + $producer_fee_percent;
    // Set the display_unit_price according to SHOW_ACTUAL_PRICE
    if (SHOW_ACTUAL_PRICE) $display_unit_price = round ($unit_price * $adjust_markup * $coop_markup, 2);
    else $display_unit_price = round ($unit_price * $adjust_markup, 2);
    $display_price = '';
    if ( $display_unit_price != 0 )
      {
        $display_price .= $font.' $'.number_format($display_unit_price, 2).'/'.$pricing_unit.'';
      }
    if ( $display_unit_price != 0 && $extra_charge != 0 ) $display_price .= ' and ';
    if ( $extra_charge != 0 )
      {
        $display_price .= '$'.number_format($extra_charge, 2).'/'.Inflect::singularize ($ordering_unit);
      }
    $producer_orders_bycustomer .= '
      <tr align="center">
        <td align="right" valign="top"><form action="'.$PHP_SELF.'?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'#'.$member_id.'" method="post"><b>#'.$product_id.'</b>&nbsp;&nbsp;</td>
        <td align="left" valign="top"><b>'.$product_name.'</b><br>'.$display_price.'<br>'.$display_notes.'</td>
        <td align="center" valign="top">'.$quantity.' '.Inflect::pluralize_if ($quantity, $display_ordering_unit).'</td>
        <td align="center" valign="top">'.$display_weight.'</td>
        <td align="left" valign="top">'.$display_stock.' '.$display_outofstock.'</td>
        <td align="center" valign="top">'.$display_total_price.'</td>
        <td align="center" valign="top">
          <input type="hidden" name="updatevalues" value="ys">
          <input type="hidden" name="product_id" value="'.$product_id.'">
          <input type="hidden" name="product_id_printed" value="'.$product_id.'">
          <input type="hidden" name="producer_id" value="'.$producer_id.'">
          <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
          <input type="hidden" name="member_id" value="'.$member_id.'">
          <input type="hidden" name="c_member_id" value="'.$member_id.'">
          <input type="hidden" name="c_basket_id" value="'.$basket_id.'">
          <input name="where" type="submit" value="Update">
          </form>';
    if ( $member_id == $c_member_id )
      {
        $producer_orders_bycustomer .= $message2;
      }
    else
      {
        $producer_orders_bycustomer .= '';
      }
    $producer_orders_bycustomer .= '
        </td>
      </tr>';
  }

// This was originally to show adjustments on the producer invoice. May omit, or at least change to ledger

// $querya = '
//   SELECT
//     transaction_name,
//     transaction_comments,
//     transaction_amount
//   FROM
//     '.TABLE_TRANSACTIONS.' t,
//     '.TABLE_TRANS_TYPES.' tt
//   WHERE
//     transaction_delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
//     AND transaction_producer_id = "'.mysql_real_escape_string ($producer_id).'"
//     AND t.transaction_type = tt.ttype_id
//     AND tt.ttype_parent = "20"
//     AND t.transaction_taxed="1"';
// $sqla = mysql_query($querya, $connection) or die(debug_print ("ERROR: 634234 ", array ($sqlpr,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// while ( $resulta = mysql_fetch_array($sqla) )
//   {
//     $producer_orders_bycustomer .= '
//       <tr>
//         <td colspan=8><strong>Adjustments</strong></td>
//       </tr>
//       <tr align="center">
//         <td align="left" valign="top" colspan="2">'.$resulta['transaction_name'].'</td>
//         <td align="left" valign="top" colspan="4">'.$resulta['transaction_comments'].'</td>
//         <td align="right" valign="top">$'.number_format($resulta['transaction_amount'], 2).'</td>
//         <td align="center" valign="top"></td>
//       </tr>';
//     $subtotal_pr = $subtotal_pr + $resulta['transaction_amount'];
//     $total = $total + $resulta['transaction_amount'];
//   }
$producer_orders_bycustomer .= '
      </table>';

?>
