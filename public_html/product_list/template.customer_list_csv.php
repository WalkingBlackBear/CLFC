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

$wholesale_text_html = '';

$no_product_message = 'NO PRODUCTS AVAILABLE';

// RANDOM_WEIGHT_DISPLAY_CALC
$random_weight_display_calc = <<<EOT
(\$random_weight_true ?
    'Random '.\$meat_weight_type.' wt. ('.
    (\$minimum_weight == \$maximum_weight ?
      \$minimum_weight.' '.Inflect::pluralize_if (\$minimum_weight, \$pricing_unit)
      :
      \$minimum_weight.' - '.\$maximum_weight.' '.Inflect::pluralize (\$pricing_unit)
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
  ' + '
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
EOT;


// INVENTORY_DISPLAY_CALC
$inventory_display_calc = <<<EOT
(\$inventory_id != 0 ?
  (\$inventory_quantity == 0 ? '[OUT OF STOCK] 0 ' : \$inventory_quantity).' x '.Inflect::pluralize_if (\$inventory_quantity, \$ordering_unit).''
  :
  'Inquire... '
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
EOT;

// ORDERING_LINK_CALC
$ordering_link_calc = <<<EOT
EOT;

// PAGER_DISPLAY_CALC
$pager_display_calc = <<<EOT
EOT;


/************************* PAGER NAVIGATION SECTION ***************************/

$pager_navigation = <<<EOT
EOT;

/****************************** MAJOR HEADING *********************************/

$major_heading_content = <<<EOT
EOT;

/****************************** MINOR HEADING *********************************/

$minor_heading_content = <<<EOT
EOT;


/***************************** PRODUCT LISTING ********************************/

$product_listing_open = <<<EOT
(\$heading_count++ < 1 ? 'Category,Subcategory,Business Name,Product ID,Product Name,Quantity Available,Description,Production Type,Price'."\r\n" : '')
EOT;



$product_listing_item = <<<EOT
'"'.strip_tags (str_replace ('"', '""', trim (\$category_name)).'","'.
                str_replace ('"', '""', trim (\$subcategory_name)).'","'.
                str_replace ('"', '""', trim (\$business_name)).'","'.
                str_replace ('"', '""', trim (\$product_id)).'","'.
                str_replace ('"', '""', trim (\$product_name)).'","'.
                str_replace ('"', '""', trim (\$inventory_display).trim (\$ordering_unit_display).' '.trim (\$random_weight_display)).'","'.
                str_replace ('"', '""', trim (\$detailed_notes)).'","'.
                str_replace ('"', '""', trim (\$prodtype_display)).'","'.
                str_replace ('"', '""', trim (\$pricing_display)).'"')."\r\n"
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
EOT;


/******************************************************************************/
