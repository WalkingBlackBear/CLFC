<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member');


if (! CurrentBasket::basket_id() && ! $_GET['basket_id'])
  {
    header ('Location: index.php');
    exit (1);
  }

if (isset ($_GET['delivery_id']))
  {
    $delivery_id = $_GET['delivery_id'];
  }
else
  {
    $delivery_id = ActiveCycle::delivery_id();
  }

$member_id = $_SESSION['member_id'];
$basket_id = $_SESSION['basket_id'];

// If the auth_type is producer_admin, then we will allow viewing of invoices for other members and orders
// Note these are NOT finalized invoices... these are the in-process ones.
if(CurrentMember::auth_type('site_admin,cashier') && isset ($_GET['member_id']) && isset ($_GET['basket_id']))
  {
    if (isset ($_GET['member_id']))
      {
        $member_id = $_GET['member_id'];
      }
    if (isset ($_GET['basket_id']))
      {
        $basket_id = $_GET['basket_id'];
      }
  }

include("../func/gen_invoice.php");

$display_page = geninvoice($member_id, $basket_id, $delivery_id, "members");

$page_title_html = '<span class="title">'.$show_name.'</span>';
$page_subtitle_html = '<span class="subtitle">In-process Invoice</span>';
$page_title = strip_tags ($_SESSION['show_name']).': In-process Invoice';
$page_tab = 'shopping_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_page.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");