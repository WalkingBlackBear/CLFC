<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'cloverbe_wp');

/** MySQL database username */
define('DB_USER', 'cloverbe_wp');

/** MySQL database password */
define('DB_PASSWORD', 'LocalFoodC00p');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'x;RY8`doshkEHX+${4T^)eJ0=(ElQDzXE_w)VjtW](Z/~H)91)ah?)ur|c]t+0a8');
define('SECURE_AUTH_KEY',  '!k-xofFF7nL-!^1lRaC*H<4{iq-Md]u[wTsa[gaE)_t.[kmZ9H;>kwzRsvrG -=g');
define('LOGGED_IN_KEY',    'mU}`lmy$Ba>V @ayL[8H?u5-n5&hPe<#2LqbM%M!S+OR*2$A(m#WGQQ`!E+}tfu_');
define('NONCE_KEY',        'ucFEARSR`++v|nJ$&JURKpnl)C65<6C+.(ryW{lP]9tD9h%|q]v=v,^|+(^G+P3x');
define('AUTH_SALT',        'x/(~%.O8N)UIGa=+C 6.]wY&x+6=hLJ,8E3EHN9A5KaZvW%5zv[V1{-T~8`2;s=5');
define('SECURE_AUTH_SALT', ' *$fA1Y1`)aiYw{dt+MuIowIqE1bo_*)-D9Q~H/Wn>;,}CHN[e_nKR[8RzM7#9_/');
define('LOGGED_IN_SALT',   'Zt{p%YA0hKB<lGQP)Qk#2|$#roOy|5g=C3xaunQG}7#-MS-){]$r5E/Si{Y+s5/|');
define('NONCE_SALT',       '-#En|Z8]v)g,4-W>|HVck,F6(J9(eAOpP(f_+3.ptt?6TA;hJ(0M{G+ac{A@a3)F');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* Multisite 
define( 'WP_ALLOW_MULTISITE', true );

define('MULTISITE', true);
define('SUBDOMAIN_INSTALL', false);
define('DOMAIN_CURRENT_SITE', 'cloverbeltlocalfoodcoop.com');
define('PATH_CURRENT_SITE', '/');
define('SITE_ID_CURRENT_SITE', 1);
define('BLOG_ID_CURRENT_SITE', 1);
*/
/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
