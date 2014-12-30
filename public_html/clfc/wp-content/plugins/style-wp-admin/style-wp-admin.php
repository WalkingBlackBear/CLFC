<?php
/*
Plugin Name: My Admin Theme
Plugin URI:
Description: Load wp-admin.css from this same folder in order to style the admin area.
Author:
Version: 0.1
Author URI:
*/

function style_wp_admin() {
        echo '<link rel="stylesheet" type="text/css" href="' .plugins_url('wp-admin.css', __FILE__). '">';
}

// Call the function above
add_action('admin_head', 'style_wp_admin');
?>