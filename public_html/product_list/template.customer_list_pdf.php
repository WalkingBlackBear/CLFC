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
(\$display_wholesale_price_true && \$display_unit_wholesale_price ?
  '$'.number_format(\$display_unit_wholesale_price, 2).'/'.Inflect::singularize (\$pricing_unit)
  : ''
)
.
(\$display_retail_price_true && \$display_unit_retail_price ?
  '$'.number_format(\$display_unit_retail_price, 2).'/'.Inflect::singularize (\$pricing_unit)
  : ''
)
.
(((\$display_wholesale_price_true && \$display_unit_wholesale_price) || (\$display_retail_price_true && \$display_unit_retail_price)) && \$extra_charge ?
  '<br>and<br>'
  : ''
)
.
(\$extra_charge ?
  '$'.number_format(\$extra_charge, 2).'/'.Inflect::singularize (\$ordering_unit)
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
EOT;


// PRODTYPE_DISPLAY_CALC
$prodtype_display_calc = <<<EOT
(\$prodtype_id == 5 ?
  ''
  : \$prodtype
)
EOT;


// BUSINESS_NAME_DISPLAY_CALC
$business_name_display_calc = <<<EOT
'<a href="product_list.php?producer_id='.\$producer_id.'">'.\$business_name.'</a>
'
EOT;

// ORDERING_LINK_CALC
$ordering_link_calc = <<<EOT
EOT;

// PAGER_DISPLAY_CALC
$pager_display_calc = <<<EOT
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
(\$show_producer_heading_true ?
  '<h3>'.\$business_name_display.'</h3>
  <table border="1" cellpadding="5" cellspacing="0" bgcolor="#ffffff" width="900px">
    <tr>
      <th align="center" bgcolor="#DDDDDD" width="10%">ID</th>
      <th align="center" bgcolor="#DDDDDD" width="55%">Product Name [<a href="product_list.php?producer_id='.\$producer_id.'">About Producer</a>]</th>
      <th align="center" bgcolor="#DDDDDD" width="10%">Type</th>
      <th align="center" bgcolor="#DDDDDD" width="15%">Price</th>
    </tr>'
  :
  '<table border="1" cellpadding="5" cellspacing="0" bgcolor="#ffffff" width="900px">
    <tr>
      <th align="center" bgcolor="#DDDDDD" width="10%">ID</th>
      <th align="center" bgcolor="#DDDDDD" width="45%">Product Name</th>
      <th align="center" bgcolor="#DDDDDD" width="10%">Producer</th>
      <th align="center" bgcolor="#DDDDDD" width="10%">Type</th>
      <th align="center" bgcolor="#DDDDDD" width="15%">Price</th>
    </tr>'
)
EOT;



$product_listing_item = <<<EOT
'
  <tr'.(\$wholesale_item_true ? ' bgcolor="#eeffdd"' : '').'>
    <td>
      <font size="-1" face="arial"><b>#'.\$product_id.'</b></font>
    </td>
    <td align="left" valign="top">
      '.\$image_display.'
      <font size="-1" face="arial"><b>'.\$product_name.'</b></font>
      <br><font size="-1" face="arial">'.\$inventory_display.\$ordering_unit_display.\$random_weight_display.'</font>
      <div id="Y'.\$product_id.'">
        <font size="-1" face="arial">'.\$detailed_notes.'</font>
        '.(\$wholesale_item_true ? \$wholesale_text_html : '').'
      </div>
    </td>'.
(\$show_producer_heading_true ?
  ''
  : '
    <td>
      <font size="-1" face="arial">'.\$business_name_display.'</font>
    </td>'
).'
    <td>
      <font size="-1" face="arial">'.\$prodtype_display.'</font>
    </td>
    <td align="center">
      <font size="-1" face="arial">'.\$pricing_display.'</font>
    </td>
  </tr>'
EOT;




// $inventory_display
//             $inventory_id = $row['inventory_id'];
//             $inventory = $row['inventory'];
// 
// $pricing_display
//             $unit_price = $row['unit_price'];
//             $pricing_unit = $row['pricing_unit'];
//             $extra_charge = $row['extra_charge'];
// 
// $ordering_unit_display
//             $ordering_unit = $row['ordering_unit'];
// 
// $prodtype_display
//             $prodtype_id = $row['prodtype_id'];
//             $prodtype = $row['prodtype'];
// 
// $random_weight_display
//             $random_weight = $row['random_weight'];
//             $minimum_weight = $row['minimum_weight'];
//             $maximum_weight = $row['maximum_weight'];
// 
// $image_display
//             $image_id = $row['image_id'];
// 
// 
// 
// 
//             $meat_weight_type = $row['meat_weight_type'];
//             $donotlist = $row['donotlist'];



$product_listing_close = <<<EOT
'
  </table>'
EOT;


/******************************************************************************/
