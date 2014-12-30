<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('producer,producer_admin,site_admin,cashier');


$producer_id = $_SESSION['producer_id_you'];
// cashier and site_admin are allowed to view producer invoices by $_GET directive
if ($_GET['producer_id'] && (CurrentMember::auth_type('site_admin') || CurrentMember::auth_type('cashier')))
  {
    $producer_id = $_GET['producer_id'];
  }

if ( $_GET['delivery_id'] )
  {
    $delivery_id = $_GET['delivery_id'];
  }
else
  {
    $delivery_id = ActiveCycle::delivery_id();
  }

if ($_GET['display_only'] == "true")
  {
    $display_only = true;
  }
else
  {
    $display_only = false;
  }

// Get the target delivery date
$query = '
  SELECT
    delivery_date,
    producer_markdown,
    wholesale_markup,
    retail_markup
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
$result = @mysql_query($query, $connection) or die(mysql_error());
if ( $row = mysql_fetch_array($result) )
  {
    $delivery_date = date ("F j, Y", strtotime ($row['delivery_date']));
    $producer_markdown = $row['producer_markdown'] / 100;
    $retail_markup = $row['retail_markup'] / 100;
    $wholesale_markup = $row['wholesale_markup'] / 100;
  }

include('../func/producer_orders_multi.php');
include ('../func/producer_orders_totals.php');
include("../func/show_businessname.php");

if ($display_only)
  {
    echo '
    '.$producer_orders_multi.'
    '.$producer_orders_totals;
  }
else
  {
    $page_title_html = '<span class="title">'.$business_name.'</span>';
    $page_subtitle_html = '<span class="subtitle">Producer Invoice (multi)</span>';
    $page_title = ''.$business_name.': Producer Invoice (multi)';
    $page_tab = 'producer_admin_panel';

    include("template_header.php");
    echo '
      <!-- CONTENT BEGINS HERE -->
        <div align="center">
          <h3>Producer List for '.$delivery_date.' for '.$a_business_name.'</h3>
          '.$message.'
        </div>
        '.$producer_orders_multi.'
        '.$producer_orders_totals.'
        </div>
      <!-- CONTENT ENDS HERE -->';
    include("template_footer.php");
  }
