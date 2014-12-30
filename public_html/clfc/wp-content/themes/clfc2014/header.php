<?php
/**
 * The Header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="main">
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @childtheme CLFC
 * @since Twenty Thirteen 1.0
 */
	// Site Configuration constants
	define('HOME_URL','http://www.cloverbeltlocalfoodcoop.com/');
	define('COOP_NAME', 'Cloverbelt Local Food Co-Op');
?>
<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<link rel="icon" type="image/png" href="../images/clover_icon.png">
	<link href='http://fonts.googleapis.com/css?family=Shadows+Into+Light+Two&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
	<!--[if lt IE 9]>
	<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js"></script>
	<![endif]-->
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<div id="page" class="hfeed site">
		<header id="header" class="trans_white_bg shadow">
			<div id="logo">
				<a href="<?php echo HOME_URL; ?>"><img src="/clfc/wp-content/uploads/2014/10/CLFC-HeaderLogoA-2014.png" alt="CLFC" title="<?php echo COOP_NAME; ?>" class="logo" /></a>
<!--<p class="f22 Windsor"><?php echo COOP_NAME; ?></p>-->
			</div>
			<div id="memberSocial">
				<!--<a href="http://www.cloverbeltlocalfoodcoop.com/member_form.php"><img src="images/CLFC.gif" alt="CLFC" title="CLFC" /></a><br />-->
				<a href="http://cloverbeltlocalfoodcoop.com/member_form.php"><img src="/clfc/wp-content/uploads/2014/10/CLFC-BecomeMember-2014.png" alt="Become a CLFC member!" title="Become a CLFC member!" /></a><br />
				<span class="Ubuntu f16">
					<a href="http://www.facebook.com/cloverbelt" target="_blank" class="black"><img src="/images/facebook.png" width="32" height="32" alt="CLFC on Facebook" title="CLFC on Facebook" class="vert_middle" /> Facebook</a>
					&nbsp;&nbsp;
					<a href="mailto:membership@cloverbeltlocalfoodcoop.com" class="black"><img src="/images/email-icon32.png" alt="email" title="Email us" class="vert_middle" /> Email Us&nbsp;</a>
				</span>
			</div>
		</header>
<!--		<div id="navbar" class="navbar">
			<nav id="site-navigation" class="navigation main-navigation" role="navigation">
				<h3 class="menu-toggle"><?php // _e( 'Menu', 'twentythirteen' ); ?></h3>
				<a class="screen-reader-text skip-link" href="#content" title="<?php // esc_attr_e( 'Skip to content', 'twentythirteen' ); ?>"><?php // _e( 'Skip to content', 'twentythirteen' ); ?></a>
				<?php // wp_nav_menu( array( 'theme_location' => 'primary', 'menu_class' => 'nav-menu' ) ); ?>
			</nav><!-- #site-navigation --
		</div><!-- #navbar --
-->		<!-- END OF HEADER -->

		<div id="main" class="site-main">