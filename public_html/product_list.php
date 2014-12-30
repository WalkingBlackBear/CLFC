<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
// Validations are done in the product_list/* files

// Items dependent upon the location of this header
$unique = array();
$pager = array();
if (isset ($_POST['action']))
  {
    if (( isset ($_POST['basket_x']) && isset ($_POST['basket_y'])) ||
      ( isset ($_POST['basket_add_x']) && isset ($_POST['basket_add_y'])))
      {
        $_POST['action'] = 'add';
      }
    elseif ( isset ($_POST['basket_sub_x']) && isset ($_POST['basket_sub_y']))
      {
        $_POST['action'] = 'sub';
      }
    $process_type = $_POST['process_type'];
    $non_ajax_query = true;
    // Different back-end for customer_list|basket_list|producer_basket
    include(FILE_PATH.PATH.'ajax/'.$process_type.'.php');
  }

// Determine whether a basket is open or not
$basket_open_true = 0;
if (CurrentBasket::basket_id()) $basket_open_true = 1;

// Set up some variables that might be needed
if ($_SESSION['member_id']) $member_id = $_SESSION['member_id'];
// Allow cashier to override member_id
if ($_GET['member_id'] && CurrentMember::auth_type('cashier')) $member_id = $_GET['member_id'];

if ($_GET['producer_id']) $producer_id = $_GET['producer_id'];
if ($_GET['producer_link']) $producer_link = $_GET['producer_link'];
if ($_SESSION['producer_id_you']) $producer_id_you = $_SESSION['producer_id_you'];
// Allow GET to trump SESSION for producer_id -- but only for admin
if (CurrentMember::auth_type('producer_admin') && isset($_GET['producer_id']))
  $producer_id_you = $_GET['producer_id'];

// Get a delivery_id for pulling current producer "invoices"
if ($_GET['delivery_id']) $delivery_id = mysql_real_escape_string ($_GET['delivery_id']);
else $delivery_id =mysql_real_escape_string (ActiveCycle::delivery_id());
// Get a basket_id in cases where we are looking at baskets or invoices...
if ($_GET['basket_id']) $basket_id = mysql_real_escape_string ($_GET['basket_id']);
else $basket_id = mysql_real_escape_string (CurrentBasket::basket_id());

// Determine whether the order is open or not
$order_open = false;
if ((ActiveCycle::ordering_window() == 'open' && ActiveCycle::delivery_id() == $delivery_id ) ||
  CurrentMember::auth_type('orderex')) $order_open = true;

// Initialize display of wholesale and retail to false
$display_wholesale_price = false;
$display_retail_price = false;
$is_wholesale_item = false;

// SET UP QUERY PARAMETERS THAT APPLY TO MOST LISTS

// Set up the "listing_auth_type" field condition based on whether the member is an "institution" or not
// Only institutions are allowed to see listing_auth_type="institution"
if (CurrentMember::auth_type('institution'))
  {
    $where_auth_type = '
    AND (
      '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"
      OR '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "institution")';
    $display_wholesale_price = true;
  }
else
  {
    $where_auth_type = '
    AND '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"';
    $display_retail_price = true;
  }

// Only show for listed producers -- not unlisted (1) or suspended (2)
$where_unlisted_producer = '
    AND unlisted_producer = "0"';

// Normally, do not show producers that are pending (1)
$where_producer_pending = '
    '.TABLE_PRODUCER.'.pending = 0';

// Set up an exception for hiding zero-inventory products
$where_zero_inventory = '';
if (EXCLUDE_ZERO_INV == true)
  {
    // Can use TABLE_PRODUCT here because this condition is only used on the public product lists
    $where_zero_inventory = '
    AND (
      IF('.NEW_TABLE_PRODUCTS.'.inventory_id > 0, FLOOR('.TABLE_INVENTORY.'.quantity / '.NEW_TABLE_PRODUCTS.'.inventory_pull), 1)
      OR '.NEW_TABLE_BASKET_ITEMS.'.quantity > 0)';
  }

// Set the default subquery_confirmed to look only at confirmed products
$where_confirmed .= '
    AND '.NEW_TABLE_PRODUCTS.'.confirmed = "1"';

//////////////////////////////////////////////////////////////////////////////////////
//                                                                                  //
//                         QUERY AND DISPLAY THE DATA                               //
//                                                                                  //
//////////////////////////////////////////////////////////////////////////////////////

// Include the appropriate list "module" from the product_list directory
$list_type = $_GET['type'];
if (! isset ($list_type)) $list_type = 'by_id';
include_once ('product_list/'.$list_type.'.php');
// Now include the template (specified in the include_file)
//echo $template_type."<br />";
include_once ('product_list/'.$template_type.'_template.php');

// This setting might be overridden below or in included files
$pager['per_page'] = PER_PAGE;
// Labels do not have pages
if ($template_type == 'labels') $pager['per_page'] = 1000000;
// Set up the pager for the output
$list_start = ($_GET['page'] - 1) * $pager['per_page'];
if ($list_start < 0) $list_start = 0;
$query_limit = $list_start.', '.$pager['per_page'];

// Add limits to the query
$query .= '
  LIMIT '.$query_limit;

$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 785033 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Get the total number of rows (for pagination) -- not counting the LIMIT condition
$query_found_rows = '
  SELECT
    FOUND_ROWS() AS found_rows';
$result_found_rows = @mysql_query($query_found_rows, $connection) or die(debug_print ("ERROR: 860342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// Handle pagination for multi-page results
$row_found_rows = mysql_fetch_array($result_found_rows);
$pager['found_rows'] = $row_found_rows['found_rows'];
if ($_GET['page']) $pager['this_page'] = $_GET['page'];
else $pager['this_page'] = 1;
$pager['last_page'] = ceil (($pager['found_rows'] / $pager['per_page']) - 0.00001);
$pager['page'] = 0;
while (++$pager['page'] <= $pager['last_page'])
  {
    if ($pager['page'] == $pager['this_page']) $pager['this_page_true'] = true;
    else $pager['this_page_true'] = false;
    $pager['display'] .= pager_display_calc($pager);
  }
$pager_navigation_display = pager_navigation($pager);


// Iterate through the returned results and display products
while ( $row = mysql_fetch_array($result) )
  {
//  	print_r($row);
    // Add non-database variables to the $row array so they are available in function calls
    $row['display_retail_price'] = $display_retail_price;
    $row['display_wholesale_price'] = $display_wholesale_price;
    $row['is_wholesale_item'] = $is_wholesale_item;
    $row['availability_array'] = explode (',', $row['availability_list']);
    $row['delcode_id_you'] = CurrentBasket::delcode_id();
    $row['delcode_you'] = CurrentBasket::delcode();
    $row['order_open'] = $order_open;

    // Open the product list
    if ($first_time_through++ == 0) $display .= open_list_top($row);

    // Set the various fees:
    $row['customer_customer_adjust_fee'] = 0;
    $row['producer_customer_adjust_fee'] = 0;
    if (PAYS_CUSTOMER_FEE == 'customer') $row['customer_customer_adjust_fee'] = $row['customer_fee_percent'] / 100;
    elseif (PAYS_CUSTOMER_FEE == 'producer') $row['producer_customer_adjust_fee'] = $row['customer_fee_percent'] / 100;
    $row['customer_product_adjust_fee'] = 0;
    $row['producer_product_adjust_fee'] = 0;
    if (PAYS_PRODUCT_FEE == 'customer') $row['customer_product_adjust_fee'] = $row['product_fee_percent'] / 100;
    elseif (PAYS_PRODUCT_FEE == 'producer') $row['producer_product_adjust_fee'] = $row['product_fee_percent'] / 100;
    $row['customer_subcat_adjust_fee'] = 0;
    $row['producer_subcat_adjust_fee'] = 0;
    if (PAYS_SUBCATEGORY_FEE == 'customer') $row['customer_subcat_adjust_fee'] = $row['subcategory_fee_percent'] / 100;
    elseif (PAYS_SUBCATEGORY_FEE == 'producer') $row['producer_subcat_adjust_fee'] = $row['subcategory_fee_percent'] / 100;
    $row['customer_producer_adjust_fee'] = 0;
    $row['producer_producer_adjust_fee'] = 0;
    if (PAYS_PRODUCER_FEE == 'customer') $row['customer_producer_adjust_fee'] = $row['producer_fee_percent'] / 100;
    elseif (PAYS_PRODUCER_FEE == 'producer') $row['producer_producer_adjust_fee'] = $row['producer_fee_percent'] / 100;

    // All this parsing and rounding is to match the line-item breakout in the ledger to prevent roundoff mismatch
    $row['cost_multiplier'] = ($row['random_weight'] == 1 ? $row['total_weight'] : ($row['basket_quantity'] - $row['out_of_stock'])) * $row['unit_price'];
    $row['producer_adjusted_cost'] = round($row['cost_multiplier'], 2)
                                     - round($row['producer_customer_adjust_fee'] * $row['cost_multiplier'], 2)
                                     - round($row['producer_subcat_adjust_fee'] * $row['cost_multiplier'], 2)
                                     - round($row['producer_producer_adjust_fee'] * $row['cost_multiplier'], 2);
    $row['customer_adjusted_cost'] = round($row['cost_multiplier'], 2)
                                     + round($row['customer_customer_adjust_fee'] * $row['cost_multiplier'], 2)
                                     + round($row['customer_product_adjust_fee'] * $row['cost_multiplier'], 2)
                                     + round($row['customer_subcat_adjust_fee'] * $row['cost_multiplier'], 2)
                                     + round($row['customer_producer_adjust_fee'] * $row['cost_multiplier'], 2);
    // Following values are for generalalized -- not-logged-in calculations
    $row['retail_unit_cost'] = round($row['unit_price'], 2)
                               + (PAYS_CUSTOMER_FEE == 'customer' ? round(ActiveCycle::retail_markup_next () * $row['unit_price'], 2) : 0)
                               + round($row['customer_product_adjust_fee'] * $row['unit_price'], 2)
                               + round($row['customer_subcat_adjust_fee'] * $row['unit_price'], 2)
                               + round($row['customer_producer_adjust_fee'] * $row['unit_price'], 2);
    $row['wholesale_unit_cost'] = round($row['unit_price'], 2)
                                + (PAYS_CUSTOMER_FEE == 'customer' ? round(ActiveCycle::wholesale_markup_next () * $row['unit_price'], 2) : 0)
                                + round($row['customer_product_adjust_fee'] * $row['unit_price'], 2)
                                + round($row['customer_subcat_adjust_fee'] * $row['unit_price'], 2)
                                + round($row['customer_producer_adjust_fee'] * $row['unit_price'], 2);

    // These are per-item values baseed on the SHOW_ACTUAL_PRICE setting
    if (SHOW_ACTUAL_PRICE) $row['display_unit_wholesale_price'] = $row['wholesale_unit_cost'];
    else $row['display_unit_wholesale_price'] = $row['unit_price'];
    if (SHOW_ACTUAL_PRICE) $row['display_unit_retail_price'] = $row['retail_unit_cost'];
    else $row['display_unit_retail_price'] = $row['unit_price'];

    // These are line-item totals based on the SHOW_ACTUAL_PRICE setting
    if (SHOW_ACTUAL_PRICE) $row['customer_display_cost'] = $row['customer_adjusted_cost'];
    else $row['customer_display_cost'] = $row['cost_multiplier'];
    if (SHOW_ACTUAL_PRICE) $row['producer_display_cost'] = $row['producer_adjusted_cost'];
    else $row['customer_display_cost'] = $row['cost_multiplier'];

    // Set up wholesale flag
    if ($row['listing_auth_type'] == "institution") $row['is_wholesale_item'] = true;
    else $row['is_wholesale_item'] = false;

    // Get the availability for this product at this member's chosen delcode_id
    // Two conditions will allow products to be purchased (availability = true):
    //   1. No availibility set for the producer means the product is available everywhere
    //   2. Customer's delivery site is in the set of availabile locations for the producer
    if ($row['availability_list'] == '' || in_array ($row['delcode_id_you'], $row['availability_array'])) $row['availability'] = true;
    // Otherwise the product is not available for this customer to purchase
    else $row['availability'] = false;
    $row['row_activity_link'] = row_activity_link_calc($row, $pager, $unique);
    $row['random_weight_display'] = random_weight_display_calc($row);
    $row['business_name_display'] = business_name_display_calc($row);
    $row['pricing_display'] = pricing_display_calc($row);
    $row['total_display'] = total_display_calc($row);
    $row['ordering_unit_display'] = ordering_unit_display_calc($row);
    $row['image_display'] = image_display_calc($row);
    $row['prodtype_display'] = prodtype_display_calc($row);
    $row['inventory_display'] = inventory_display_calc($row);
    // New major division
    if ($row[$major_division] != $$major_division_prior && $show_major_division == true)
      {
        if ($listing_is_open)
          {
            if ($show_minor_division) $display .= minor_division_close($row, $unique);
            $display .= major_division_close($row);
            $listing_is_open = 0;
          }
        $display .= major_division_open($row, $major_division);
        // New major division will force a new minor division
        $$minor_division_prior = -1;
      }

    // New minor division
    if ($row[$minor_division] != $$minor_division_prior && $show_minor_division == true)
      {
        if ($listing_is_open)
          {
            $display .= minor_division_close($row, $unique);
            $listing_is_open = 0;
          }
        $display .= minor_division_open($row, $minor_division);
      }

    $listing_is_open = 1;
    $display .= show_listing_row($row, $row_type);

    // Handle prior values to catch changes
    $$major_division_prior = $row[$major_division];
    $$minor_division_prior = $row[$minor_division];
  }
$unique['completed'] = 'true';
// Close minor
if ($show_minor_division) $display .= minor_division_close($row, $unique);
// Close major
if ($show_major_division) $display .= major_division_close($row);
// Close the product list
$display .= close_list_bottom($row);

// If there was nothing to show, then do this...
if ($pager['found_rows'] == 0) $display .= no_product_message();



// Some product_list types need dynamically generated titles and subtitles
if ($_GET['type'] == 'subcategory')
  {
    $page_title_html = '<span class="title bold">Products &rArr; </span>';
    $page_subtitle_html = '<span class="subtitle">'.$subcategory_name.' Subcategory</span><br /><br />';
    $page_title = 'Products: '.$subcategory_name.' Subcategory';
    $page_tab = 'shopping_panel';
  }
elseif ($_GET['producer_id'] || strpos($_SERVER['PHP_SELF'],'producers'))
  {
    $page_title_html = '<span class="title bold">Products &rArr; </span>';
    $page_subtitle_html = '<span class="subtitle">'.$business_name.'</span><br /><br />';
    $page_title = 'Products: '.$business_name;
    $page_tab = 'shopping_panel';
  }



$content_list = '
<div id="listing_auth_type">
  <h3>';
foreach (array ("retail"=>"Listed Retail", "wholesale"=>"Listed Wholesale", "unlisted"=>"Unlisted", "archived"=>"Archived") as $key=>$value)
  {
    if ($_REQUEST['a'] == $key)
      {
        $content_list .= $value.' ';
        $this_edit = $value;
      }
    else
      {
        $content_list .= '[<a href="producer_product_list.php?a='.$key.'">'.$value.'</a>] ';
      }
  }
$content_list .= '</h3>';



if ($show_search) $search_display = '
  <form action="'.$_SERVER['PHP_SELF'].'" method="get">'.
    ($_REQUEST['a'] ? '<input type="hidden" name="a" value="'.$_REQUEST['a'].'">' : '').
    '<input type="text" name="query" value="'.$search_query.'">
    <input type="submit" name="type" value="search">
  </form>';

if (isset ($pager['found_rows']))
  {
    $search_display .= '
      <span class="found_rows">'.$pager['found_rows'].' '.Inflect::pluralize_if ($pager['found_rows'], 'item').'</span>';
  }

$page_specific_css .= '
<link rel="stylesheet" type="text/css" href="'.PATH.'product_list.css">
<link rel="stylesheet" type="text/css" href="basket_dropdown.css">
<style type="text/css">
#content_top {
  margin-bottom:25px;
  }
.pager a {
  width:'.($pager['last_page'] == 0 ? 0 : number_format(72/$pager['last_page'],2)).'%;
  }
</style>';

$page_specific_javascript .= '
<script type="text/javascript" src="ajax/jquery.js"></script>
<script type="text/javascript">

function AddToCart (product_id, product_version, action) {
  var elem;
  var message = "";
  if (elem = document.getElementById("message"+product_id)) message = elem.value;
  var basket_id = "";
  if (elem = document.getElementById("basket_id")) basket_id = elem.value;
  var member_id = "";
  if (elem = document.getElementById("member_id")) member_id = elem.value;
  var delivery_id = "";
  if (elem = document.getElementById("delivery_id")) delivery_id = elem.value;
  $.post("ajax/'.$template_type.'.php", {
    product_id:product_id,
    product_version:product_version,
    action:action,
    message:message,
    basket_id:basket_id,
    member_id:member_id,
    delivery_id:delivery_id
    },
  function(data) {
    // If no delcode can be determined, then popup a window to set it.
    if (data == "delcode_id not set") {
      var width  = 425;
      var height = 425;
      var left   = (screen.width  - width)/2;
      var top    = (screen.height - height)/2;
      popup=window.open(\'select_delivery_popup.php?first_call=true\', \'popupPage\', \'height=\'+height+\',width=\'+width+\',top=\'+top+\',left=\'+left+\',location=no,directories=no,menubar=no,toolbar=no,status=no,resizeable=no,scrollbars=yes\');
      return false;
      }
    var returned_array = data.split(":");
    var new_quantity = returned_array[0];
    var new_inventory = returned_array[1];
    var checked_out = returned_array[2];
    var alert_text = returned_array[3];
//alert(data);
    if (document.getElementById("basket_qty" + product_id))
      {
        document.getElementById("basket_qty" + product_id).innerHTML = new_quantity;
      }
    // Update the number available
    if (document.getElementById("available" + product_id))
      {
        document.getElementById("available" + product_id).innerHTML = new_inventory;
      }
    // Show/hide the basket controls
    if (new_quantity > 0 && document.getElementById("add" + product_id)) // The item is in the basket
      {
        if (document.getElementById("available" + product_id) && new_inventory == 0)
          {
            document.getElementById("add" + product_id).style.display = "none";
          }
        else
          {
            document.getElementById("add" + product_id).style.display = "";
          }
        document.getElementById("sub"+product_id).style.display = "";
        document.getElementById("basket_empty"+product_id).style.display = "none";
        document.getElementById("basket_full"+product_id).style.display = "";
        document.getElementById("in_basket"+product_id).style.display = "";
        if (elem = document.getElementById("message_area"+product_id)) elem.style.display = "";
      }
    else if (document.getElementById("add"+product_id) || document.getElementById("sub"+product_id)) // The item is not in the basket
      {
        document.getElementById("add"+product_id).style.display = "none";
        document.getElementById("sub"+product_id).style.display = "none";
        document.getElementById("basket_empty"+product_id).style.display = "";
        document.getElementById("basket_full"+product_id).style.display = "none";
        document.getElementById("in_basket"+product_id).style.display = "none";
        document.getElementById("message_area"+product_id).style.display = "none";
      }
    if (checked_out == 1) {
      document.getElementById("checkout"+product_id).innerHTML = "<input type=\"image\"class=\"checkout_check\" src=\"grfx/checkout-ccs.png\" onclick=\"AddToCart("+product_id+","+product_version+",\'no_checkout\'); return false;\"><span class=\"checkout_text\">Ordered!</span>";
      document.getElementById("message_button"+product_id).innerHTML = "";
      document.getElementById("activity"+product_id).innerHTML = "";
      }
    else {
      }

    if (alert_text && alert_text.length > 1) {
      // Uncomment the following line to show alerts
      alert (alert_text);
      }
    });
  }

function SetItem (bpid, action) {
  var elem;
  if (elem = document.getElementById("ship_quantity"+bpid)) var ship_quantity = elem.value;
  if (elem = document.getElementById("weight"+bpid)) var weight = elem.value;
  // Give user indication the function is running
  if (action == "set_quantity") {
    document.getElementById("ship_quantity"+bpid).style.color = "#f80";
    }
  if (action == "set_weight") {
    document.getElementById("weight"+bpid).style.color = "#f80";
    }
  $.post("ajax/producer_basket.php", {
    bpid:bpid,
    ship_quantity:ship_quantity,
    weight:weight,
    action:action
    },
  function(data) {
//alert(data);
    // Function returns [producer_adjusted_cost]:[extra_charge] OR [ERROR:alert_message]
    var returned_array = data.split(":");
    if (returned_array[0] == "ERROR") {
      alert (returned_array[1]);
      }
    else {
      var producer_adjusted_cost = returned_array[0];
      var extra_charge = returned_array[1];
      var shipped = returned_array[2];
      var total_weight = returned_array[3];
      if (elem = document.getElementById("producer_adjusted_cost"+bpid)) elem.innerHTML = producer_adjusted_cost;
      if (elem = document.getElementById("extra_charge"+bpid)) elem.innerHTML = extra_charge;
      }
    if (action == "set_quantity" && (elem = document.getElementById("ship_quantity"+bpid))) {
      elem.style.color = "#000";
      elem.value = shipped;
      // now also set the weight...
      action = "set_weight";
      }
    if (action == "set_weight" && (elem = document.getElementById("weight"+bpid))) {
      elem.style.color = "#000";
      elem.value = total_weight;
      }
    });
  return false;
  }
</script>
';

$csv_link = '
  <!-- <br><a href="'.$_SERVER['REQUEST_URI'].'&csv=true">Download full list as a CSV file</a> -->
  ';

$content_list = 
  ($content_top ? '
    <div id="content_top">
    '.$content_top.'
    </div>' : '').'
  <div class="product_list">
    '.($message ? '<b><font color="#770000">'.$message.'</font></b>' : '').
    $search_display.
    $producer_display.
    $pager_navigation_display. // Only set for pages needing producer info
    $display.
    $pager_navigation_display.
    $csv_link.'
  </div>
';

// $page_title_html = [value set dynamically]
// $page_subtitle_html = [value set dynamically]
// $page_title = [value set dynamically]
// $page_tab = [value set dynamically]

if ($_GET['csv'] == 'true')
  {
    header('Content-Type: text/csv');
    header('Content-disposition: attachment;filename=Product_List.csv');
    echo $display;
  }
elseif ($pdf == true)
  {
    // DISPLAY NOTHING
  }
else
  {
    include("template_header.php");
    echo '
      <!-- CONTENT BEGINS HERE -->
      '.$content_list.'
      <!-- CONTENT ENDS HERE -->';
    include("template_footer.php");
  }