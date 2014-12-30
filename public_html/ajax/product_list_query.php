<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
validate_user();


















// WARNING:  THIS ROUTINE IS BROKEN FOR ADJUSTED PRODUCER/SUBCATEGORY/PRODUCT FEES.




















$date_today = date("F j, Y");

//	THESE VARIABLES ARE USED TO GENERATE A CSV FILE OF THE PRODUCTS
$product_array = array(); // AN ARRAY OF INDIVIDUAL PRODUCT DATA
$csv_filepath = ""; // LOCATION TO SAVE CSV FILE
$products_array = array(); // AN ARRAY OF INDIVIDUAL PRODUCT ARRAYS
$headers_array = array(); // AN ARRAY OF COLUMN HEADERS FOR THE CSV

$response = '';
$sort_order = $_GET["q"];
$step='setting session';

// Get the time until the order closes
$seconds_until_close = strtotime ( ActiveCycle::date_closed()) - time();
// Set up the "donotlist" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see donotlist=3 (wholesale products)
if (CurrentMember::auth_type('institution') && $seconds_until_close < INSTITUTION_WINDOW)
  {
    $donotlist_condition = 'AND ('.TABLE_PRODUCT.'.donotlist = "0" OR '.TABLE_PRODUCT.'.donotlist = "3")';
  }
else
  {
    $donotlist_condition = 'AND '.TABLE_PRODUCT.'.donotlist = "0"';
  }


/* some variables with default values to handle sorting the table */
$order_by = 'subcategory_name';
$sorted_by = 'id';
$order_direction = 'ASC';
$reverse_direction = 'DESC';
$show_product_description_slider = true;

$step='determining sort order';
if (isset($_GET['order']) && $_GET['order'] != "")
  {
    $order_by = htmlentities($_GET['order']);
  }
if (isset($_GET['sort']) && $_GET['sort'] != "")
  {
    $order_direction = htmlentities($_GET['sort']);
  }
if (isset($_GET['loc']) && $_GET['loc'] != "")
  {
    $location = $_GET['loc'];
    $valid_user = true;
  }
else
  {
    $location = false;
    $valid_user = false;
  }
 if (isset($_GET['slider']) && $_GET['slider']!= "") {
     $show_product_description_slider = $_GET['slider'];
     if($show_product_description_slider=="1") $show_product_description_slider = true;
 }
$valid_user = true;

$step='querying';
$sql = '
  SELECT
    '.TABLE_PRODUCT.'.* ,
    '.TABLE_PRODUCT_TYPES.'.*,
    '.TABLE_PRODUCER.'.*,
    '.TABLE_SUBCATEGORY.'.*,
    '.TABLE_INVENTORY.'.*
  FROM
    '.TABLE_PRODUCT.'
  LEFT JOIN
    '.TABLE_PRODUCER.' ON '.TABLE_PRODUCT.'.producer_id = '.TABLE_PRODUCER.'.producer_id
  LEFT JOIN
    '.TABLE_PRODUCT_TYPES.' ON '.TABLE_PRODUCT.'.prodtype_id = '.TABLE_PRODUCT_TYPES.'.prodtype_id
  LEFT JOIN
    '.TABLE_SUBCATEGORY.' ON '.TABLE_PRODUCT.'.subcategory_id = '.TABLE_SUBCATEGORY.'.subcategory_id
  LEFT JOIN
    '.TABLE_INVENTORY.' ON '.TABLE_INVENTORY.'.inventory_id = '.TABLE_PRODUCT.'.inventory_id
  WHERE
    '.TABLE_PRODUCT.'.donotlist = 3
    AND '.TABLE_PRODUCER.'.pending = 0
    AND '.TABLE_PRODUCER.'.donotlist_producer = 0
  GROUP BY
    product_id
  ORDER BY
    '.mysql_real_escape_string ($order_by).' '.mysql_real_escape_string ($order_direction);
$result = @mysql_query($sql, $connection) or die("Couldn't execute search query.");

$num = mysql_numrows($result);
$step='looping on query results';
if($order_by=="subcategory_name")
  {
    $set=array();
    while ( $record = mysql_fetch_object($result) )
      {
        $set[$record->subcategory_name][] = $record;
      }
    foreach ($set as $category => $records)
      {
        $display = $display . '<tr><td colspan=6><h3>' . ${category} . '</h3></td></tr>';
        foreach ($records as $record)
          {
            $product_id = $record->product_id;
            $product_name = $record->product_name;
            $inventory_id = $record->inventory_id;
            $inventory = $record->quantity;
            $unit_price = $record->unit_price;
            $pricing_unit = $record->pricing_unit;
            $ordering_unit = $record->ordering_unit;
            $prodtype_id = $record->prodtype_id;
            $prodtype = $record->prodtype;
            $random_weight = $record->random_weight;
            $minimum_weight = $record->minimum_weight;
            $maximum_weight = $record->maximum_weight;
            $meat_weight_type = $record->meat_weight_type;
            $extra_charge = $record->extra_charge;
            $product_adjust_fee = $record->product_adjust_fee / 100;
            $subcat_adjust_fee = $record->subcat_adjust_fee / 100;
            $producer_adjust_fee = $record->producer_adjust_fee / 100;
            $image_id = $record->image_id;
            $donotlist = $record->donotlist;
            $detailed_notes = $record->detailed_notes;
            $subcategory_id = $record->subcategory_id;
            $subcategory_name = $record->subcategory_name;
            $business_name = $record->business_name;
            $producer_id = $record->producer_id;
            $show_business_link = true;
            if (SHOW_ACTUAL_PRICE)
              {
                if (CurrentMember::auth_type('institution') || strpos($location,'wholesale') !== false)
                  {
                    $coop_markup = 1 + ActiveCycle::wholesale_markup_next ();
                  }
                else
                  {
                    $coop_markup = 1 + ActiveCycle::retail_markup_next ();
                  }
                $coop_markup = $coop_markup + $product_adjust_fee + $subcat_adjust_fee + $producer_adjust_fee;
              }
            else
              {
                $coop_markup = 1;
              }
            $totalPrice = $coop_markup * $unit_price;
            $product_array = array($subcategory_name, $product_id, str_replace('"','',$product_name), $business_name, $prodtype, round($totalPrice, 2), $ordering_unit);
            $products_array[] = $product_array;
            // The next line is a **BAD** workaround and means this ajax script can only be used
            // for /shop/members/listall_wholesale.php successfully.  But without this piece
            // the /shop/func/show_product_info_members.php is confused about where to return
            // control on adding products.
            /* Kevin 11/03/09 - I updated this to take an additional url parameter to set this dynamically
             * based on where it's being called from.  This should make it a little more useable.
             */
            $PHP_SELF = PATH.html_entity_decode($location);
            if($valid_user)
              {
                include("../func/show_product_info_members.php");
              }
            else
              {
                include("../func/show_product_info.php");
              }
          }
      }
  }
else
  {
    while ( $row = mysql_fetch_array($result) )
      {
        $product_id = $row['product_id'];
        $product_name = $row['product_name'];
        $inventory_id = $row['inventory_id'];
        $inventory = $row['quantity'];
        $unit_price = $row['unit_price'];
        $pricing_unit = $row['pricing_unit'];
        $ordering_unit = $row['ordering_unit'];
        $prodtype_id = $row['prodtype_id'];
        $prodtype = $row['prodtype'];
        $random_weight = $row['random_weight'];
        $minimum_weight = $row['minimum_weight'];
        $maximum_weight = $row['maximum_weight'];
        $meat_weight_type = $row['meat_weight_type'];
        $extra_charge = $row['extra_charge'];
        $product_adjust_fee = $row['product_adjust_fee'] / 100;
        $subcat_adjust_fee = $row['subcat_adjust_fee'] / 100;
        $producer_adjust_fee = $row['producer_adjust_fee'] / 100;
        $image_id = $row['image_id'];
        $donotlist = $row['donotlist'];
        $detailed_notes = $row['detailed_notes'];
        $subcategory_id = $row['subcategory_id'];
        $subcategory_name = $row['subcategory_name'];
        $business_name = $row['business_name'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
    //    $prodtype = $row['prodtype'];
        $producer_id = $row['producer_id'];
        $show_business_link = true;
        if (SHOW_ACTUAL_PRICE)
          {
            if (CurrentMember::auth_type('institution') || strpos($location,'wholesale') !== false)
              {
                $coop_markup = 1 + ActiveCycle::wholesale_markup_next ();
              }
            else
              {
                $coop_markup = 1 + ActiveCycle::retail_markup_next ();
              }
            $coop_markup = $coop_markup + $product_adjust_fee + $subcat_adjust_fee + $producer_adjust_fee;
          }
        else
          {
            $coop_markup = 1;
          }
        $totalPrice = $coop_markup * $unit_price;
        $product_array = array($subcategory_name, $product_id, str_replace('"', '', $product_name), $business_name, $prodtype, round($totalPrice, 2), $ordering_unit);
        $products_array[] = $product_array;
        if($valid_user)
          {
            include("../func/show_product_info_members.php");
          }
        else
          {
            include("../func/show_product_info.php");
          }
      }
  }

// CSV COLUMN HEADERS
$headers_array = array("Category","ID","Product Name","Producer","Type","Price","Per Unit");

if($valid_user)
  {
    $response .=  '
        <table border=1 cellpadding=5 cellspacing=0 bordercolor=#DDDDDD>
          <tr>
            <th align="center" bgcolor="#DDDDDD" width="10%"><?php echo $font;?>Order</font></th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>ID</font></th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Product Name</font></th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Producer</th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Type</th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Price</font></th>
          </tr>';
  }
else
  {
  $response .=  '
        <table border=1 cellpadding=5 cellspacing=0 bordercolor=#DDDDDD>
          <tr>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>ID</font></th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Product Name</font></th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Type</th>
            <th align="center" bgcolor="#DDDDDD"><?php echo $font;?>Price</font></th>
          </tr>';
  }
$response .= $display.'
        </table>';
// GENERATE THE CSV
$csv_filename = "wholesale_products.csv";
if ( file_exists(FILE_PATH . PATH . 'pdf/' . $csv_filename ) )
  {
    unlink(FILE_PATH . PATH . 'pdf/' . $csv_filename );
  }
$csv_filepath = FILE_PATH.PATH.'pdf/'.$csv_filename;

mssafe_csv($csv_filepath, $products_array, $headers_array);

echo $response;
?>
