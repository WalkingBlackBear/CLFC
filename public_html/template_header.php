<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php'); // just in case it got missed from the base page
session_start();
// valid_auth('member');

include_once('func.check_membership.php');
$content_header = '';
$google_analytics = '';

/* Configuration variables */
	define('HOME_URL','http://www.cloverbeltlocalfoodcoop.com/');
	define('COOP_NAME', 'Cloverbelt Local Food Co-Op');
	define('SITE_TITLE', 'CLFC | Member Area');
$content_header = '<!DOCTYPE html>
<html>
<head>
	<title>'.SITE_TITLE.'</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="icon" type="image/png" href="images/clover_icon.png">
	<link rel="stylesheet" type="text/css" href="styles/clfc.css" media="all" />
	<link href="http://fonts.googleapis.com/css?family=Shadows+Into+Light+Two&subset=latin,latin-ext" rel="stylesheet" type="text/css">
	<script src="jquery-1.8.2.js" type="text/javascript"></script>
	<script src="jquery-ui-1.9.0.js" type="text/javascript"></script>';
//$content_header .= '<link href="'.PATH.'stylesheet.css" rel="stylesheet" type="text/css">';

// Include any page-specific CSS directives
if ($page_specific_css)
  {
    $content_header .= '
    '.$page_specific_css;
  }

// Include any page-specific javascript
if ($page_specific_javascript)
  {
    $content_header .= '
    '.$page_specific_javascript;
  }

	
	$content_header .= '<script type="text/javascript">
	function init() {
	  // Do not throw an error if page does not have a load_target element...
	  if (document.getElementById ("load_target")) {
	    var text_input = document.getElementById ("load_target");
	    text_input.focus ();
	    text_input.select ();
	    }
	  }
	window.onload = init;
	</script>
</head>

<body>
	<div id="wrapper">
		<div id="header" class="trans_white_bg shadow">


			<div id="logo">
				<a href="'.HOME_URL.'"><img src="/clfc/wp-content/uploads/2014/10/CLFC-HeaderLogoA-2014.png" alt="CLFC" title="'.COOP_NAME.'" class="logo" /></a>
			</div>
			<div id="memberSocial">
				<!--<a href="http://www.cloverbeltlocalfoodcoop.com/member_form.php"><img src="/clfc/wp-content/uploads/2014/10/CLFC-BecomeMember-2014.png" alt="CLFC" title="CLFC" /></a><br />-->
				<a href="http://www.cloverbeltlocalfoodcoop.com/docs/CLFCMembershipApplication.pdf"><img src="/clfc/wp-content/uploads/2014/10/CLFC-BecomeMember-2014.png" alt="CLFC" title="Become a CLFC member!" /></a><br />
				<span class="Windsor f16">
					<a href="http://www.facebook.com/cloverbelt" target="_blank" class="black"><img src="images/facebook.png" width="32" height="32" alt="CLFC on Facebook" title="CLFC on Facebook" class="vert_middle" /> Facebook</a>
					&nbsp;&nbsp;
					<a href="mailto:membership@cloverbeltlocalfoodcoop.com" class="black"><img src="images/email-icon32.png" alt="email" title="Email us" class="vert_middle" /> Email Us</a>
				</span>
			</div>';

// Site down processing
$site_is_down = false;
$warn_now = false;
$site_down_at_time = '2012-01-18 16:30:00';
$down_time_duration = 40 * 3600; // hours * sec/hr
$down_time_warning = 6 * 3600; // hours * sec/hr
$site_down_message = '
  <div style="border:2px solid #800;width:50%;text-align:center;color:#800;float:right;background-color:#fff;padding:1em;position:absolute;right:10px;top:10px;opacity:0.7;filter:alpha(opacity=70);">
  <h2>NOTE<br>The site will be going down for maintenance '.date('l, F j \a\t g:i a', strtotime($site_down_at_time)).' and may be down until the next order cycle opens.</h2>
  <p>For information email: <a href="mailto:webmaster@cloverbeltlocalfoodcoop.com">webmaster@cloverbeltlocalfoodcoop.com</a>.</p>
  </div>';
if (time() > strtotime($site_down_at_time) && time() < strtotime($site_down_at_time) + $down_time_duration) $site_is_down = true;
if (time() > strtotime($site_down_at_time) - $down_time_warning && time() < strtotime($site_down_at_time) + $down_time_duration) $warn_now = true;

  $content_header .= ($warn_now ? $site_down_message : '').'</div>';

if ($site_is_down)
  {
    echo $content_header;
    include ('template_footer.php');
    exit (0);
  }

		$content_header .= '<!-- END OF HEADER -->';


if (is_readable (FILE_PATH.PATH.'template_analytics.php'))
  include_once (FILE_PATH.PATH.'template_analytics.php');

$content_header .= ''.$google_analytics;
$content_header .= '
  <!-- BEGIN MENU SECTION -->
  <div id="menu" class="center trans_black_bg">';

// Check if the member is logged in
if ($_SESSION['member_id'])
  {
    // Don't re-query if we already know the $basket_quantity
    if (! isset ($basket_quantity) && ActiveCycle::delivery_id())
      {
        $query = '
          SELECT
            COUNT(product_id) AS basket_quantity,
            '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          FROM
            '.NEW_TABLE_BASKET_ITEMS.'
          LEFT JOIN '.NEW_TABLE_BASKETS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
          WHERE
            '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
            AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
          GROUP BY
            '.NEW_TABLE_BASKETS.'.member_id';
        $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 780934 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $basket_quantity = 0;
        if ($row = mysql_fetch_object($result))
          {
            $basket_quantity = $row->basket_quantity;
            $basket_id = $row->basket_id;
          }
      }

    // Display the page tabs
    if (CurrentMember::auth_type('member'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_member.php" class="left white'.($page_tab == 'member_panel' ? ' current_tab' : '').'">Members</a>';
    if (CurrentMember::auth_type('member'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_shopping.php" class="left white'.($page_tab == 'shopping_panel' ? ' current_tab' : '').'">Shop</a>';
    if (CurrentMember::auth_type('producer'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_producer.php" class="left white'.($page_tab == 'producer_panel' ? ' current_tab' : '').'">Producers</a>';
    if (CurrentMember::auth_type('route_admin'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_route_admin.php" class="left white'.($page_tab == 'route_admin_panel' ? ' current_tab' : '').'">Routes</a>';
    if (CurrentMember::auth_type('producer_admin'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_producer_admin.php" class="left white'.($page_tab == 'producer_admin_panel' ? ' current_tab' : '').'">Producer Admin</a>';
    if (CurrentMember::auth_type('member_admin'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_member_admin.php" class="left white'.($page_tab == 'member_admin_panel' ? ' current_tab' : '').'">Member Admin</a> &mdash;';
    if (CurrentMember::auth_type('site_admin'))
      $content_header .= '<br /> &mdash; 
        <a href="'.PATH.'member_new_form.php" class="left white'.($page_tab == 'shopping_panel' ? ' current_tab' : '').'">Add Member</a>';
    if (CurrentMember::auth_type('cashier'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_cashier.php" class="left white'.($page_tab == 'cashier_panel' ? ' current_tab' : '').'">Cashiers</a>';
    if (CurrentMember::auth_type('site_admin'))
      $content_header .= ' &mdash; 
        <a href="'.PATH.'panel_admin.php" class="left white'.($page_tab == 'admin_panel' ? ' current_tab' : '').'">Site Admin</a>';
			$content_header .= ' &mdash; 
        <a href="'.PATH.'index.php" class="left white">Blog</a>';
			$content_header .= ' &mdash; 
        <a href="'.HOMEPAGE.'?action=logout" class="right white bold'.($page_tab == 'login' ? ' current_tab' : '').'">Logout &mdash; </a>';
//    if (isset ($basket_id) && $basket_id != 0) {
//        if (CurrentMember::auth_type('orderex') || ( ActiveCycle::ordering_window() == 'open')) {
            $content_header .= ' 
        <a href="'.PATH.'product_list.php?type=basket" class="right white">View Basket ['.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').']</a> &mdash; ';
//          }
//      }
  }
else
  {
    $content_header .= '&mdash;
        <a href="'.HOMEPAGE.'?action=login" class="right white'.($page_tab == 'login' ? ' current_tab' : '').'">Login</a> &mdash;';
  }
$content_header .= '
  </div>
  <!-- END MENU SECTION -->

  <div id="content" class="trans_white_bg2">
    '.$page_title_html.'
    '.$page_subtitle_html.'
    <div class="clear"></div>';

echo $content_header;