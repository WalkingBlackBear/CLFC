<?php
# This include is used to leapfrog to the *real* configuration file, which has
# been renamed from config_foodcoop.php to config_foodcoop_main.php
set_include_path(get_include_path() . PATH_SEPARATOR . '/home/cloverbe/ofs_includes/');
include_once ('config_foodcoop_main.php');
?>
