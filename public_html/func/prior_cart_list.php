<?php

$prior_to_show = 30;

// NOTE:  The following query references TABLE_PRODUCT because -- if products have changed
// then the customer will want to be adding the correct (changed) item to the cart, so they should
// see the new (changed) values, even though it is not necessarily exactly what they ordered before
$query = '
  SELECT
    '.TABLE_BASKET.'.product_id,
    '.TABLE_PRODUCT.'.product_name,
    '.TABLE_PRODUCT.'.detailed_notes,
    '.TABLE_PRODUCT.'.unit_price,
    '.TABLE_PRODUCT.'.pricing_unit,
    '.TABLE_PRODUCT.'.ordering_unit,
    '.TABLE_PRODUCT.'.inventory_id,
    '.TABLE_PRODUCT.'.inventory_pull,
    FLOOR('.TABLE_INVENTORY.'.quantity / '.TABLE_PRODUCT.'.inventory_pull) AS inventory_quantity,
    '.TABLE_PRODUCT.'.donotlist,
    '.TABLE_BASKET.'.quantity,
    '.TABLE_BASKET.'.customer_notes_to_producer,
    '.TABLE_PRODUCER.'.donotlist_producer,
    '.TABLE_PRODUCER.'.pending
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  LEFT JOIN '.TABLE_PRODUCT.' ON '.TABLE_BASKET.'.product_id = '.TABLE_PRODUCT.'.product_id
  LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN '.TABLE_INVENTORY.' ON '.TABLE_PRODUCT.'.inventory_id = '.TABLE_INVENTORY.'.inventory_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id ='.mysql_real_escape_string ($_SESSION['member_id']).'
    AND '.TABLE_BASKET_ALL.'.delivery_id < '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
  ORDER BY
    '.TABLE_BASKET_ALL.'.delivery_id DESC
  LIMIT 0,'.mysql_real_escape_string ($prior_to_show);

$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ($row = mysql_fetch_array($result))
  {

    $unavailable_pre = '';
    $unavailable_post = '';
    $unavailable_background = 'BaCkGrOuNd';
    $unavailable_disabled = '';
    if (($row['donotlist'] == 1)
      || ($row['inventory_id'] != 0 && $row['inventory_quantity'] < 1)
      || ($row['donotlist_producer'] != 0)
      || ($row['pending'] == 1))
      {
        $unavailable_pre = '<del>';
        $unavailable_post = '</del>';
        $unavailable_pre = '';
        $unavailable_post = '';
        $unavailable_background = ' style="background-color:#dddddd;color:#aaa;padding:0em 1em;"';
        $unavailable_disabled = ' disabled';
      }

    $markup  = '
      <div style="width:100%;">
        <form name="order-'.$row['product_id'].'" action="orders_current.php" method="post">
        <tr '.$unavailable_background.'>
          <td width="10%" style="padding-bottom:5px;border-top:1px solid #888;">
            &nbsp;<img title="click to expand" id="order-'.$row['product_id'].'ic" src="grfx/arrow_closed.png"onClick=\'{document.getElementById("order-'.$row['product_id'].'O").style.display="";document.getElementById("order-'.$row['product_id'].'ic").style.display="none";document.getElementById("order-'.$row['product_id'].'io").style.display="";}\'>
            <img title="click to contract" id="order-'.$row['product_id'].'io" style="display:none;" src="grfx/arrow_open.png"onClick=\'{document.getElementById("order-'.$row['product_id'].'O").style.display="none";document.getElementById("order-'.$row['product_id'].'io").style.display="none";document.getElementById("order-'.$row['product_id'].'ic").style.display="";}\'>
            <strong>'.$unavailable_pre.$row['product_id'].$unavailable_post.'</strong></td>
          <td width="40%" align="left" style="padding-bottom:5px;border-top:1px solid #888;"><strong style="margin:0">'.$unavailable_pre.$row['product_name'].$unavailable_post.'</strong></td>
          <td width="20%" style="padding-bottom:5px;border-top:1px solid #888;">$'.number_format ($row['unit_price'], 2).' / '.$row['pricing_unit'].'</td>
          <td width="20%" style="padding-bottom:5px;border-top:1px solid #888;"><input type="text" name="quantity" value="'.$row['quantity'].'" size=3 maxlength="4"'.$unavailable_disabled.'> '.$row['ordering_unit'].'</td>
          <td width="10%" style="padding-bottom:5px;border-top:1px solid #888;"><input name="where" type="submit" value="Add to Current Order"'.$unavailable_disabled.'>'.'</td>
        </tr>
      </div>
      <input type="hidden" name="product_id" value="'.$row['product_id'].'">
      <tr id="order-'.$row['product_id'].'O" style="display:none;">
        <td colspan="2" width="50%" valign="top"'.$unavailable_background.'>'.$unavailable_pre.$row['detailed_notes'].$unavailable_post.'</td>
        <td colspan="3" width="50%"'.$unavailable_background.'><strong>Note to producer:</strong><br><textarea name="customer_notes_to_producer" cols="40" rows="4"'.$unavailable_disabled.'>'.$row['customer_notes_to_producer'].'</textarea></td>
          <input type="hidden" name="yp" value="ds">
          <input type="hidden" name="source" value="prior">
        </td>
      </tr>
    </form>';

    $product_data[$row['product_id']] = $markup;
  }

// Remove any items already ordered this time around...

$query = '
  SELECT
    '.TABLE_BASKET.'.product_id
  FROM
    '.TABLE_BASKET.'
  LEFT JOIN '.TABLE_BASKET_ALL.' ON '.TABLE_BASKET.'.basket_id = '.TABLE_BASKET_ALL.'.basket_id
  WHERE
    '.TABLE_BASKET_ALL.'.member_id ='.mysql_real_escape_string ($_SESSION['member_id']).'
    AND '.TABLE_BASKET_ALL.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id());

$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ($row = mysql_fetch_array($result))
  {
    unset ($product_data[$row['product_id']]);
  }


// Sort by product_id and display
if (is_array ($product_data) && ksort ($product_data))
  {
    foreach (array_keys ($product_data) as $product_id)
      {
        // Set up alternating row colors (BaCkGrOuNd is replaced unless it is already designated by $unavailable_background)
        if ($row_odd != true)
          {
            $row_odd = true;
            $product_data[$product_id] = str_replace ('BaCkGrOuNd', 'style="background-color:#c8d8e8;color:#000;padding:0em 1em;"', $product_data[$product_id]);
          }
        else
          {
            $row_odd = false;
            $product_data[$product_id] = str_replace ('BaCkGrOuNd', 'style="background-color:#deecee;color:#000;padding:0em 1em;"', $product_data[$product_id]);
          }
        $prior_orders .= '<tr><td>'.$product_data[$product_id]."</td></tr>\n";
      }
    if ($_POST['source'] == "prior" || is_string ($_GET['open']))
      {
        $not_display_prior = ' style="display:none;" '; // Don't expand unless posted from this list
      }
    else
      {
        $display_prior = ' style="display:none;" '; // Don't expand unless posted from this list
      }
    $content .= '<div style="font-size:80%;" id="prior"><font style="font-size:150%;font-weight:bold;">';
    $content .= '<img title="click to show" id="prior_baskets-ic" '.$not_display_prior.' src="grfx/arrow_closed.png"onClick=\'{document.getElementById("prior_baskets").style.display="";document.getElementById("prior_baskets-ic").style.display="none";document.getElementById("prior_baskets-io").style.display="";}\'>';
    $content .= '<img title="click to hide" id="prior_baskets-io" '.$display_prior.' src="grfx/arrow_open.png"onClick=\'{document.getElementById("prior_baskets").style.display="none";document.getElementById("prior_baskets-io").style.display="none";document.getElementById("prior_baskets-ic").style.display="";}\'>';
    $content .= '&nbsp;View Items From Previous Orders<br /></font></p>';
    $content .= '<p>(Click on triangles to show or hide the display)</p>
          <p><font style="font-size:120%;">These products are the '.$prior_to_show.' most recent items you&#146;ve ordered.  They are available for easy access to add to your cart.  To add these items to your shopping basket, check the quantity listed and then click &ldquo;Add to Current Order&rdquo;.  If you do not click &ldquo;Add to Current Order&rdquo; these items will not be ordered.  Products that are grayed out are not available for order at this time.</font></p>';
    $content .= '<table border="0" cellspacing="0" cellpadding="0" width="100%" '.$display_prior.' id="prior_baskets">';
    $content .= $prior_orders."</table></div><hr>\n";
  }



?>
 