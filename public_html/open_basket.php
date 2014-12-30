<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member');


$time_now = time ();
$date_today = date("F j, Y");

///////////////////////////////////////////////////////////////////////////////////
$sql4 = '
  SELECT
    member_id,
    delivery_id,
    basket_id
  FROM
    '.NEW_TABLE_BASKETS.'
  WHERE
    delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id_next()).'"
    AND member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
$result4 = mysql_query($sql4, $connection) or die(debug_print ("ERROR: 646654 ", array ($sql4,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num4 = mysql_numrows($result4);
while ( $row = mysql_fetch_array($result4) )
  {
    $basket_id = $row['basket_id'];
  }
if ( $num4 == "1" )
  {
    $order_started = "yes";
//    $_SESSION['basket_id'] = $basket_id;
  }
else
  {
   $order_started = "";
  }

$sql = '
  SELECT
    '.NEW_TABLE_PRODUCTS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type,
    '.NEW_TABLE_PRODUCTS.'.producer_id,
   '.TABLE_PRODUCER.'.producer_id,
    '.TABLE_PRODUCER.'.unlisted_producer
  FROM
    '.NEW_TABLE_PRODUCTS.',
    '.TABLE_PRODUCER.'
  WHERE
    '.NEW_TABLE_PRODUCTS.'.listing_auth_type = "member"
    AND '.NEW_TABLE_PRODUCTS.'.producer_id = '.TABLE_PRODUCER.'.producer_id
    AND '.TABLE_PRODUCER.'.unlisted_producer = 0
  GROUP BY
    product_id';
$result = @mysql_query($sql, $connection) or die(debug_print ("ERROR: 646654 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$prod_count = mysql_numrows($result);

// Show the following warming for site_admin users if outside an ordering period.
if (( $time_now < strtotime (ActiveCycle::date_open()) || $time_now > strtotime (ActiveCycle::date_closed()) ) && CurrentMember::auth_type('orderex'))
  {
    $display .= '<h1 style="background-color:#d00;color:#fff;padding:0.2em;width:400px;">Admin privilege for ordering is enabled<br><blink>ORDERING IS NOT CURRENTLY OPEN</blink></h1>';
  }

@include ('message.php');


///////////////////////////////////////////////////////////////////////////////////


if (! $order_started
  && (( $time_now > strtotime (ActiveCycle::date_open()) && $time_now < strtotime (ActiveCycle::date_closed()) ) || CurrentMember::auth_type('orderex'))
  && $pending != 1)
  {
    // Sets stuff for $display of the open_basket information
    include("../func/mem_select_delivery.php");
  }
elseif ( $pending == 1 )
  {
    $display .= '<br><font color="#770000"><b>Your membership is pending, please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> with any questions.</b></font><br/>';
  }


if ($order_started == 'yes')
  {
    $display .= '
      <font color="#770000"><h3>You can begin shopping!<br>Select the Shopping menu link to get started.</h3>
      Remember... any products in your basket when shopping closes will be considered an order and you<br>
      will be expected to pay for them.  Please be sure to double-check your basket before leaving the site.</font>';
  }

$display .= '
  <p>Acting as the agent of producer members</b>, the '.SITE_NAME.' posts and publicizes the products the producers have for sale, receive orders, provides a way for products to be delivered to other members of the '.ORGANIZATION_TYPE.', collects from the customers and forwards the payments to the producers.  <b>Acting as the agent for customer members</b>, we provide them a catalog of available local food products that includes information about how and where the product was grown or processed. We receive their orders and notify the appropriate producers, arrange for the food to be delivered, receive and process their payments.  For both producer and customer members, we provide a basic screening of products and producers based on our published parameters, and education and training regarding the use and the advantages of local foods.</p>
  <p>For some of our producer members, we are agents that facilitate farm gate sales of their products.  For other producer members, we facilitate off-farm sales or sales of processed products.</p>
  <p>The essential business of the '.ORGANIZATION_TYPE.' is to provide a marketplace where willing buyers and sellers can meet.</b>  At no time does the '.ORGANIZATION_TYPE.' ever have title to any of the products.  We have no inventory.  The products that go through our distribution system are owned either by the producer, or by the customer who purchases "title" to the product from the producer.  All complaints should first be brought to the attention of the producer, unless it is a situation where the '.ORGANIZATION_TYPE.' itself is at fault (such as broken eggs due to poor packing). If a successful resolution can not be found by the affected producer and customer members, the '.ORGANIZATION_TYPE.'&#146;s arbitration procedure can be invoked.</p>';

$page_title_html = '<span class="title">Member Resources</span>';
$page_subtitle_html = '<span class="subtitle">Open a Shopping Basket</span>';
$page_title = 'Member Resources: Open a Shopping Basket';
$page_tab = 'member_panel';


include("template_header.php");
echo '
<!-- CONTENT BEGINS HERE -->
'.$display.'
<!-- CONTENT ENDS HERE -->';
include("template_footer.php");
