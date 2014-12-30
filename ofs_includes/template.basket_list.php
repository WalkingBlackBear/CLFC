<?php

/*******************************************************************************

NOTES ON USING THIS TEMPLATE FILE...

The heredoc convention is used to simplify quoting.
The noteworthy point to remember is to escape the '$' in
variable names.  But functions pass through as expected.

The short php if-else format is also useful in this context
for inline display (or not) of content elements:
([condition] ? [true] : [false])

All variables in this file are loaded at include-time and interpreted later
so there is no required ordering of the assignments.

All system constants from the configuration file are available to this template




********************************************************************************
Model for the overall product list display might look something like this:

 -- OVERALL PRODUCT LIST ----------------
|                                        |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|     -- PRODUCT HEADING SECTION ---     |
|    |                              |    |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|                                        |
 ----------------------------------------

*/

/********************** MISC MARKUP AND CALCULATIONS *************************/

$wholesale_text_html = '<br><br><center style="color:#f00;letter-spacing:5px;">** WHOLESALE DISCOUNTED ITEM **</center>';

$no_product_message = '<h2>No products currently available</h2>';

// RANDOM_WEIGHT_DISPLAY_CALC
$random_weight_display_calc = <<<EOT
(\$random_weight_true ?
    'You will be billed for exact '.\$meat_weight_type.' weight ('.
    (\$minimum_weight == \$maximum_weight ?
      \$minimum_weight.' '.Inflect::pluralize_if (\$minimum_weight, \$pricing_unit)
      :
      'between '.\$minimum_weight.' and '.\$maximum_weight.' '.Inflect::pluralize (\$pricing_unit)
    ).')'
  :
  ''
)
EOT;


// PRICING_DISPLAY_CALC
$pricing_display_calc = <<<EOT
(\$display_retail_price_true && \$display_unit_retail_price ?
  '<span class="retail">'.(\$_GET['type'] == 'producer_list' ? 'Retail: ' : '').'$'.number_format(\$display_unit_retail_price, 2).'/'.Inflect::singularize (\$pricing_unit).'</span><br>'
  : ''
)
.
(\$display_wholesale_price_true && \$display_unit_wholesale_price ?
  '<span class="whsle">'.(\$_GET['type'] == 'producer_list' ? 'Whsle: ' : '').'$'.number_format(\$display_unit_wholesale_price, 2).'/'.Inflect::singularize (\$pricing_unit).'</span><br>'
  : ''
)
.
(((\$display_wholesale_price_true && \$display_unit_wholesale_price) || (\$display_retail_price_true && \$display_unit_retail_price)) && (\$extra_charge != 0) ?
  'plus<br>'
  : ''
)
.
(\$extra_charge != 0 ?
  '<span class="extra">$'.number_format(\$extra_charge, 2).'/'.Inflect::singularize (\$ordering_unit).'</span>'
  : ''
)
EOT;


// ORDERING_UNIT_DISPLAY_CALC
$ordering_unit_display_calc = <<<EOT
(\$inventory_quantity > 0 || !\$inventory_id ?
  'Order number of '.Inflect::pluralize (\$ordering_unit).'. '
  :
  ''
)
EOT;


// INVENTORY_DISPLAY_CALC
$inventory_display_calc = <<<EOT
(\$inventory_id ?
  '<span id="available'.\$product_id.'">'.(\$inventory_quantity == 0 ? '[OUT OF STOCK] 0 ' : \$inventory_quantity).'</span> '.Inflect::pluralize_if (\$inventory_quantity, \$ordering_unit).' available. '
  :
  ''
)
EOT;


// IMAGE_DISPLAY_CALC
$image_display_calc = <<<EOT
(\$image_id ?
  '<img src="'.PATH.'members/getimage.php?image_id='.\$image_id.'" width="100" name="img'.\$image_id.'" onclick="javascript:img'.\$image_id.'.width=300" onMouseOut="javascript:img'.\$image_id.'.width=100" hspace="5" border="1" align="left" alt="Click to enlarge '.htmlentities (\$product_name, ENT_QUOTES).'">'
  : ''
)
EOT;


// PRODTYPE_DISPLAY_CALC
$prodtype_display_calc = <<<EOT
(\$prodtype_id == 5 ?
  ''
  : \$prodtype
)
.
(strtolower (\$_SESSION['producer_id_you']) == \$producer_id ? '<br>['.\$storage_code.']' : '')
EOT;


// BUSINESS_NAME_DISPLAY_CALC
$business_name_display_calc = <<<EOT
'<a href="product_list.php?producer_id='.\$producer_id.'">'.\$business_name.'</a>
'
EOT;

// ORDERING_LINK_CALC
$ordering_link_calc = <<<EOT
(strtolower (\$_SESSION['producer_id_you']) == \$producer_id ?
  // SHOW PRODUCT CONTROLS
  '<span class="producer_control">
      <a href="edit_products.php?product_id='.\$product_id.'&product_version='.\$product_version.'&producer_id='.\$_SESSION['producer_id_you'].'&a='.\$_REQUEST['a'].'">Edit&nbsp;Product</a><br>
      <a href="uploadpi.php?product_id='.\$product_id.'&producer_id='.\$_SESSION['producer_id_you'].'&a='.\$_REQUEST['a'].'">New&nbsp;Image</a><br>'.
      (\$_GET['type'] == 'list_versions' ?
        '<a href="product_order_history.php?product_id='.\$product_id.'&product_version='.\$product_version.'&producer_id='.\$_SESSION['producer_id_you'].'&a='.\$_REQUEST['a'].'">Version&nbsp;History</a><br>'
        :
        '<a href="product_order_history.php?product_id='.\$product_id.'&producer_id='.\$_SESSION['producer_id_you'].'&a='.\$_REQUEST['a'].'">Product&nbsp;History</a><br>').
      (\$inventory_id > 0 ?
        '<a href="edit_inventory.php?target_inventory_id='.\$inventory_id.'&producer_id='.\$_SESSION['producer_id_you'].'&a='.\$_REQUEST['a'].'">Inventory</a>'
        :
        '').
      (\$_GET['type'] != 'list_versions' ?
        '<br>
        <a href="product_list.php?type=list_versions&product_id='.\$product_id.'&a='.\$_REQUEST['a'].'">Show&nbsp;Versions</a>'
        :
        '').
  '</span>'
  :
    (\$order_open ?
//      (\$basket_open_true ?
  (\$availability == true ?
        (\$basket_quantity > 0 || !\$inventory_id || \$inventory_quantity > 0 ?
          // ADD PRODUCT TO BASKET
             '<input id="add'.\$product_id.'" class="basket_add" type="image" name="basket_add" src="'.PATH.'/grfx/basket_add.png" width="24" height="24" border="0" alt="Submit" onclick="AddToCart('.\$product_id.','.\$product_version.',\'add\'); return false;" '.(\$basket_quantity > 0 ? (\$inventory_id && \$inventory_quantity == 0 ? 'style="display:none;"' : '') : 'style="display:none;"').'>
             <input id="sub'.\$product_id.'" class="basket_sub" type="image" name="basket_sub" src="'.PATH.'/grfx/basket_sub.png" width="24" height="24" border="0" alt="Submit" onclick="AddToCart('.\$product_id.','.\$product_version.',\'sub\'); return false;" '.(\$basket_quantity > 0 ? '' : 'style="display:none;"').'>
             <input type="hidden" name="product_id" value="'.\$product_id.'">
             <input type="hidden" name="product_version" value="'.\$product_version.'">
             <input type="hidden" name="producer_id" value="'.\$producer_id.'">
             <input type="hidden" name="product_id_printed" value="'.\$product_id.'">
             <input type="hidden" name="product_name" value="'.\$product_name.'">
             <input type="hidden" name="subcategory_id" value="'.\$subcategory_id.'">
             <div style="clear:both; width:100%;"></div>
             <input id="basket_empty'.\$product_id.'" class="basket" type="image" name="basket" src="'.PATH.'/grfx/basket-egi_add.png" width="48" height="48" border="0" alt="Submit" onClick="AddToCart('.\$product_id.','.\$product_version.',\'add\'); return false;" '.(\$basket_quantity > 0 ? 'style="display:none;"' : '').'>
             <img id="basket_full'.\$product_id.'" class="basket" src="'.PATH.'/grfx/basket-fcs.png" width="48" height="48" border="0" '.(\$basket_quantity > 0 ? '' : 'style="display:none;"').'>
           </form>
           <span id="in_basket'.\$product_id.'" class="in_basket" '.(\$basket_quantity > 0 ? '' : 'style="display:none;"').'><span id="basket_qty'.\$product_id.'" class="basket_qty">'.\$basket_quantity.'</span> in basket</span>'
          :
          // NOT ABLE TO ADD ANYTHING
          ''
        )
    :
    'Unavailable for '.\$delcode
  )
//        :
//        ( \$_SESSION['member_id'] ? '<a href="'.PATH.'members/open_basket.php">Begin an order</a>' : '<a href="'.PATH.'members/login.php">Login to order</a>')
//      )
      :
      'Ordering is currently closed'
    )
)
EOT;


// PAGER_DISPLAY_CALC
$pager_display_calc = <<<EOT
'
<a href="'.\$_SERVER['PHP_SELF'].'?'.(\$_GET['type'] ? '&type='.\$_GET['type'] : '').
  (\$_GET['producer_id'] ? '&producer_id='.\$_GET['producer_id'] : '').
  (\$_GET['category_id'] ? '&category_id='.\$_GET['category_id'] : '').
  (\$_GET['subcat_id'] ? '&subcat_id='.\$_GET['subcat_id'] : '').
  (\$_REQUEST['a'] ? '&a='.\$_REQUEST['a'] : '').
  (\$search_query ? '&query='.\$search_query : '').
  '&page='.\$page.'" class="'.(\$this_page_true ? 'current' : '').(\$page == 1 ? ' first' : '').(\$page == \$last_page ? ' last' : '').'">&nbsp;'.\$page.'&nbsp;</a>'
EOT;


/************************* PAGER NAVIGATION SECTION ***************************/

$pager_navigation = <<<EOT
(\$last_page > 1 ?
  '
  <div class="pager"><span class="title">Go to page: </span>'.\$pager_display.'</div>
  <div class="clear"></div>'
  : ''
)
EOT;

/****************************** MAJOR HEADING *********************************/

$major_heading_content = <<<EOT
'<h2>'.\$category_name.'</h2>'
EOT;

/****************************** MINOR HEADING *********************************/

$minor_heading_content = <<<EOT
'<h3>'.\$subcategory_name.'</h3>'
EOT;


/***************************** PRODUCT LISTING ********************************/

$product_listing_open = <<<EOT
  '<table border="1" cellpadding="5" cellspacing="0" bgcolor="#ffffff" width="100%">
    <tr>
      <th align="center" bgcolor="#DDDDDD" width="10%">Product No.</th>
      <th align="center" bgcolor="#DDDDDD" width="10%"></th>
      <th align="center" bgcolor="#DDDDDD" width="45%">Product Name</th>
      <th align="center" bgcolor="#DDDDDD" width="10%">Producer<br>Product Type</th>
      <th align="center" bgcolor="#DDDDDD" width="10%"></th>
      <th align="center" bgcolor="#DDDDDD" width="15%">Price</th>
    </tr>'
EOT;



$product_listing_item = <<<EOT
'
  <tr'.(\$wholesale_item_true ? ' bgcolor="#eeffdd"' : '').'>'.
  (\$availability == true ? (\$basket_quantity > 0 || !\$inventory_id || \$inventory_quantity > 0 ?
    // OPEN SHOPPING-CART FORM
    '<form action="'.\$_SERVER['PHP_SELF'].'?type='.\$_GET['type'].'#X'.\$product_id.'" method="post">'
  : '') : '').'
    <td valign="top" id="X'.\$product_id.'">
      <font size="-1" face="arial"><b>#'.\$product_id.'</b>'.(\$_GET['type'] == 'list_versions' ? '-'.\$product_version : '').'</font>
      <hr style="margin:5px;">
      <font size="-1" face="arial">'.\$ordering_link.'</font>
    </td>
    <td>
    </td>
    <td align="left" valign="top" class="'.(\$availability == false || (\$inventory_quantity == 0 && \$inventory_id) ? 'inactive' : '').'">
      '.\$image_display.'
      <font size="-1" face="arial"><b>'.\$product_name.'</b></font>
      <br><font size="-1" face="arial">'.\$inventory_display.\$ordering_unit_display.\$random_weight_display.'</font>
      <div id="Y'.\$product_id.'">
        <font size="-1" face="arial">'.\$product_description.'</font>
        '.(\$wholesale_item_true ? \$wholesale_text_html : '').'
      </div>
      <div id="message_area'.\$product_id.'"'.(\$basket_quantity == 0 ? ' style="display:none;"' : '').'>
        <textarea class="message" id="message'.\$product_id.'" name="message" placeholder="Optional message for producer...">'.
          \$customer_message.
        '</textarea>
        <div class="message_button" onclick="AddToCart('.\$product_id.','.\$product_version.',\'message\'); return false;">
          <img alt="save message" src="'.PATH.'grfx/message.png">
          <div class="thumb_descr">Update<br>Message</div>
        </div>
      </div>
    </td>
    <td>
      <font size="-1" face="arial">'.\$business_name_display.'</font>
      <hr style="margin:5px;">
      <font size="-1" face="arial">'.\$prodtype_display.'</font>
    </td>
    <td>
    </td>
    <td align="center">
      <font size="-1" face="arial">'.\$pricing_display.'</font>
    </td>'.
  (\$availability == true ? (\$basket_quantity > 0 || !\$inventory_id || \$inventory_quantity > 0 ?
    // CLOSE SHOPPING-CART FORM
    '</form>'
  : '') : '').'
  </tr>'
EOT;


$product_listing_close = <<<EOT
'
  </table>'
EOT;


/******************************************************************************

Need:
  X display customer_message
    edit/add customer message
    checked_out
    random weight
    total charge(s)
    taxable
    checkout button
    out_of_stock
    ...

******************************************************************************/
?>