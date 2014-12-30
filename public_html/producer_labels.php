<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('producer,site_admin,producer_admin');


// If we don't have a producer_id then get one from the arguments
if ($_GET['producer_id'] && CurrentMember::auth_type('producer_admin'))
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

$sqlp = '
  SELECT
    business_name
  FROM
    '.TABLE_PRODUCER.'
  WHERE
    producer_id = "'.mysql_real_escape_string ($producer_id).'"';
$resultp = @mysql_query($sqlp, $connection) or die(debug_print ("ERROR: 856020 ", array ($sqlp,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
if ( $row = mysql_fetch_array($resultp) )
  {
    $a_business_name = $row['business_name'];
  }

$display_label .= '<font size="5" face="arial">';

$sqlpr = '
  SELECT
    '.NEW_TABLE_BASKET_ITEMS.'.quantity,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.NEW_TABLE_BASKETS.'.member_id,
    '.NEW_TABLE_PRODUCTS.'.unit_price,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    '.TABLE_DELCODE.'.delcode,
    '.TABLE_DELCODE.'.delcode_id,
    '.TABLE_DELCODE.'.deltype,
    '.TABLE_DELCODE.'.hub,
    '.TABLE_DELCODE.'.truck_code,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.preferred_name,
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN
    '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKET_ITEMS.'.basket_id = '.NEW_TABLE_BASKETS.'.basket_id
  LEFT JOIN
    '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.NEW_TABLE_BASKETS.'.member_id
  LEFT JOIN
    '.TABLE_DELCODE.' ON '.TABLE_DELCODE.'.delcode_id = '.NEW_TABLE_BASKETS.'.delcode_id
  LEFT JOIN
    '.NEW_TABLE_PRODUCTS.' ON
      ('.NEW_TABLE_PRODUCTS.'.product_id = '.NEW_TABLE_BASKET_ITEMS.'.product_id
      AND '.NEW_TABLE_PRODUCTS.'.product_version = '.NEW_TABLE_BASKET_ITEMS.'.product_version)
  LEFT JOIN
    '.TABLE_PRODUCT_STORAGE_TYPES.' ON '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_id = '.NEW_TABLE_PRODUCTS.'.storage_id
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string ($delivery_id).'
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = "'.mysql_real_escape_string ($producer_id).'"
    AND '.NEW_TABLE_PRODUCTS.'.tangible = "1"
    AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != '.NEW_TABLE_BASKET_ITEMS.'.quantity
  GROUP BY
    '.NEW_TABLE_BASKET_ITEMS.'.bpid
  ORDER BY
    '.TABLE_PRODUCT_STORAGE_TYPES.'.storage_code ASC,
    '.NEW_TABLE_PRODUCTS.'.product_id ASC,
    '.TABLE_DELCODE.'.delcode_id ASC,
    '.NEW_TABLE_BASKETS.'.member_id ASC,
    '.TABLE_DELCODE.'.hub ASC';
$resultpr = @mysql_query($sqlpr, $connection) or die(debug_print ("ERROR: 906854 ", array ($sqlpr,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($resultpr) )
  {
    $product_name = $row['product_name'];
    $product_id = $row['product_id'];
    $basket_id = $row['basket_id'];
    $member_id = $row['member_id'];
    $last_name = $row['last_name'];
    $first_name = $row['first_name'];
    $business_name = $row['business_name'];
    $preferred_name = $row['preferred_name'];
    $hub = $row['hub'];
    $delcode_id = $row['delcode_id'];
    $delcode = $row['delcode'];
    $deltype = $row['deltype'];
    $truck_code = $row['truck_code'];
    $storage_code = $row['storage_code'];
    $quantity = $row['quantity'];
    $ordering_unit = $row['ordering_unit'];
    $unit_price = $row['unit_price'];
    // Start a new label
    $route_code_info = &$row;
    $display_label .= '<br />'.(convert_route_code($route_code_info))."<br />";
    $display_label .= $preferred_name."<br>";
    $display_label .= $a_business_name."<br>";
    $display_label .= ' (#'.$row['product_id'].') '.$row['product_name'].' - ('.$row['quantity'].') '.Inflect::pluralize_if ($row['quantity'], $row['ordering_unit']).'<br>';
  }
$display_label .= '</font><br><br>';
  //}
?>


  <!-- CONTENT BEGINS HERE -->

<font face=arial>
<h3>Producer Labels: One Label Per Customer and Storage Type for <?php echo ActiveCycle::delivery_date(); ?> for <?php echo $a_business_name;?></h3><br>

<?php echo $display_label;?>

  <!-- CONTENT ENDS HERE -->
