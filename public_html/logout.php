<?php
$user_type = 'valid_m';
include_once ('config_foodcoop.php');
session_start();

$old_user = $_SESSION['valid_m'];

unset($_SESSION['valid_m']); 
unset($valid_m);
$result_dest = session_destroy();

if ( ! empty($old_user) )
  {
    if ( $_SESSION['valid_m'] )
      {
        $content_logout =  'Could not log you out.';
      }
    else
      {
        $content_logout =  'You are now logged out. <br><br><a href="login.php">Click here to log in again.</a>';
      }
  }
else
  {
    $content_logout = 'You were not logged in so have not been logged out. <br><br><a href="login.php">Click here to log in.</a>';
  }

$content_logout = '
  <div align="center">
    <h3>Thank you.</h3>
    '.$content_logout.'<br><br>
  </div>';

$page_title_html = '<span class="title">'.SITE_NAME.'</span>';
$page_subtitle_html = '<span class="subtitle">Logout</span>';
$page_title = 'Logout';
$page_tab = 'login';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_logout.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");