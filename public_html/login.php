<?php
$user_type = 'valid_m';
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();

$show_form = "yes";

// If needing to login, keep track of the page that was initially requested and redirect to it
$success_redirect = 'Location: index.php';
if ($_GET['call'])
  {
    $redirect_call = '?call='.$_GET['call'];
    $success_redirect = 'Location: '.$_GET['call'];
  }

if ( $_POST['gp'] == "ds" && $_POST['username_m'] && $_POST['password'] )
  {
    $query = '
      SELECT
        username_m,
        pending,
        membership_discontinued
      FROM
        '.TABLE_MEMBER.'
      WHERE
        username_m = "'.mysql_real_escape_string($_POST['username_m']).'"
        AND
          (password = md5("'.mysql_real_escape_string($_POST['password']).'")
          OR "'.MD5_MASTER_PASSWORD.'" = md5("'.mysql_real_escape_string($_POST['password']).'"))';
    $result = mysql_query($query, $connection);
    $row = @mysql_fetch_array($result);
    if ( mysql_numrows ($result) != 0 && $row['pending'] != 1 && $row['membership_discontinued'] != 1)
      {
        // Clear any old session variables
        session_destroy();
        // Set session variables here so it doesn't matter what page is accessed next
        session_start ();

        $sqlm = '
          SELECT
            '.TABLE_MEMBER.'.member_id,
            '.TABLE_MEMBER.'.username_m,
            '.TABLE_MEMBER.'.first_name,
            '.TABLE_MEMBER.'.first_name_2,
            '.TABLE_MEMBER.'.last_name,
            '.TABLE_MEMBER.'.last_name_2,
            '.TABLE_MEMBER.'.business_name,
            '.TABLE_MEMBER.'.preferred_name,
            '.TABLE_MEMBER.'.pending,
            '.TABLE_PRODUCER.'.producer_id
          FROM
            '.TABLE_MEMBER.'
          LEFT JOIN '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
         WHERE
            username_m = "'.mysql_real_escape_string ($_POST['username_m']).'"';

        $result = @mysql_query($sqlm, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
        while ( $row = mysql_fetch_array($result) )
          {
            $_SESSION['member_id'] = $row['member_id'];
            $_SESSION['producer_id_you'] = $row['producer_id'];
            $first_name = $row['first_name'];
            $last_name = $row['last_name'];
            $first_name_2 = $row['first_name_2'];
            $last_name_2 = $row['last_name_2'];
            $business_name = $row['business_name'];
            $preferred_name = $row['preferred_name'];
            $pending = $row['pending'];
            $_SESSION["username_m"] = $row['username_m'];
            $_SESSION["valid_m"] = $row['username_m'];
          }
//         include("../func/show_name.php");
        $_SESSION['show_name'] = $preferred_name;
        header($success_redirect);
        exit;
      }
    elseif ($row['pending'] == 1)
      {
        $msg = 'Your membership is pending and you will be unable to log in until it has been approved.';
      }
    elseif ($row['membership_discontinued'] == 1)
      {
        $msg = 'Your membership has been suspended. Please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if you have any questions.';
      }
    else
      {
        $msg = 'Login incorrect. Please re-enter your login information.';
      }
  }

$form_block = '
  <form method="post" action="'.$_SERVER['PHP_SELF'].$redirect_call.'" name="login">
    <table>';

// Display any notification messages
if ( $msg )
  {
    $form_block .= '
      <tr>
        <td colspan="2"><div style="margin:0.7em;width:95%;margin-bottom:1.5em;"><div style="float:left; font-size:3em; font-weight:bold; color:#800;width:1em; text-align:center;margin-top:-0.2em;">!</div><span style="color:#600;font-weight:bold;">'.$msg.'</span></div></td>
      </tr>';
  }

$form_block .= '
      <tr>
        <td>'.$font.'<b>Username</b>:</td>
        <td><input id="load_target" type="text" name="username_m" size="17" maxlength="20"></td>
      </tr>
      <tr>
        <td>'.$font.'<b>Password</b>:</td>
        <td><input type="password" name="password" size="17" maxlength="25"></td>
      </tr>
      <tr>
        <td colspan="2" align="right"><input type="hidden" name="gp" value="ds"><input type="submit" name="submit" value="Login"></td>
      </tr>
    </table>
  </form>

  <div style="text-align:left;font-size:11px;">
    <a href="reset_password.php">Forgot your password?</a>
  </div>
  ';

if ( $show_form == "yes" )
  {
    $display_block = $form_block;
  }

$content_login = '
<div align="center">
  <table cellpadding="0" cellspacing="0" border="0" width="373">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="10" cellspacing="2" border="1" bordercolor="#000000">
          <tr>
            <td bgcolor="#DDDDDD" align="center" colspan="2">'.$font.'<font size="3"><b>Welcome to the '.SITE_NAME.'</b></font></td>
          </tr>
          <tr>
            <td bgcolor="#DDDDDD" align="center">'.$font.'<b>M<br>E<br>M<br>B<br>E<br>R<br>S<br></b></td>
            <td valign="center" align="center">'.$display_block.'</td>
          </tr>
        </table>
      </td>
    </tr>
          <tr bgcolor="#000000">
            <td align="center"><img src="../grfx/shop-welcome.jpg" width="373" height="90" border="1" alt="Welcome"></td>
          </tr>
  </table>
  <br><br>

  <table width=475>
    <tr>
      <td align=left>
        '.$font.'
        If you are member and have lost your user name and password, send an e-mail to <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a>.
        If you have your user name and password, but are having difficulty logging in, make sure cookies are enabled on your internet browser.  If you need assistance with how to do this, or are still unable to log in, please send an e-mail to <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>.
      </td>
    </tr>
  </table>
</div>';

$page_specific_javascript = '
<script language="javascript">
  document.login.username_m.focus();
</script>';


$page_title_html = '<span class="title">'.SITE_NAME.'</span>';
$page_subtitle_html = '<span class="subtitle">Login</span>';
$page_title = 'Login';
$page_tab = 'login';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_login.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");