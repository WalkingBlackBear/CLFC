<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin,member_admin,cashier,site_admin');

include ('func.delivery_selector.php');

// Set up the default delivery cycle
$delivery_id = ActiveCycle::delivery_id();
// ... but if a targeted delivery is requested then use that.
if (isset ($_GET['delivery_id']))
  $delivery_id = $_GET['delivery_id'];

// Set the sort order
if (isset ($_GET['order']))
  {
    switch ($_GET['order'])
      {
        case 'producer_id':
          $order_by = 'producer_id';
          break;
        case 'business_name':
          $order_by = 'business_name';
          break;
        default:
          $order_by = 'business_name';
          break;
      }
  }
else
  {
    $order_by = 'business_name';
  }

// This next line allow us to include the ajax routine and call it as a function
// without it returning anything on stdout. C.f. the ajax function.
$call_ajax_as_function = true;
$page_data = '';
include_once ('ajax/adjust_report.php');

$query = '
  SELECT
    DISTINCT('.TABLE_PRODUCER.'.producer_id),
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.TABLE_PRODUCER.'.business_name
  FROM
    '.NEW_TABLE_BASKETS.'
  RIGHT JOIN '.NEW_TABLE_BASKET_ITEMS.' USING(basket_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
  LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'"
  ORDER BY
    '.$order_by;
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 672323 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num_orders = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $producer_id = $row['producer_id'];
    $business_name = $row['business_name'];

    $adjust_report_line = adjust_report(array(
      'request' => 'basket_total_and_payments',
      'producer_id' => $producer_id,
      'delivery_id' => $delivery_id));

    $page_data .= '
      <div id="producer_id'.$producer_id.'" class="basket_section">
        <span class="producer_id">'.$producer_id.'</span>
        <span class="business_name">'.$business_name.'</span>
        <span class="controls"><input type="button" value="Post Payment" onclick="show_receive_payment_form('.$producer_id.','.$delivery_id.')"></span>
        <div id="producer_delivery_id'.$producer_id.$delivery_id.'" class="ledger_info">'.
          $adjust_report_line.'
        </div>
      </div>';
  }

$page_specific_javascript = '
  <script src="'.PATH.'post_payments.js" type="text/javascript"></script>';

$page_specific_css = '
  <link href="'.PATH.'post_payments.css" rel="stylesheet" type="text/css">';

$page_title_html = '<span class="title">Delivery Cycle Functions</span>';
$page_subtitle_html = '<span class="subtitle">Post Producer Payments</span>';
$page_title = 'Delivery Cycle Functions: Post Producer Payments';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  <div style="float:right;width:300px;height:26px;margin-bottom:10px;">'.delivery_selector($delivery_id).'</div>
  '.$page_data.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

