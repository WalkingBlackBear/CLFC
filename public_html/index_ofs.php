<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
// valid_auth('member');

// THIS FILE MODIFIED FOR CLFC

include_once('func.check_membership.php');

// This is the new member landing page
// It will allow login/logout, handle membership renewals, and site messages.

// if (! [logged_in])
//   {
//     [provide login window]
//   }
// elseif ([membership expired])
//   {
//     [provide membership renewal window]
//   }
// elseif (redirect)
//   {
//     [provide redirect]
//   }
// else
//   {
//     [provide site message(s)]
//     [provide order cycle information]
//   }

// If being asked to logout, then do that first
if ($_GET['action'] == 'logout')
  {
    session_destroy();
    unset ($_SESSION);
    CurrentMember::clear_member_info();
    $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
    $page_subtitle_html = '<span class="subtitle">Logout</span>';
    $page_title = 'Logout';
    $page_tab = 'login';
  }

// echo "<pre>".print_r($_GET,true)."1 SESSION MEMBER_ID ".$_SESSION['member_id']."</pre>";

// Check if the member is not already logged in
if ($_GET['action'] == 'login' && ! $_SESSION['member_id'])
  {
// echo "<pre>1 SESSION MEMBER_ID ".$_SESSION['member_id']."</pre>";
    // Check if we already have a posted username/password combination
    if ($_POST['username'] && $_POST['password'])
      {
        $query_login = '
          SELECT
            member_id,
            username,
            pending,
            membership_discontinued
          FROM
            '.TABLE_MEMBER.'
          WHERE
            username = "'.mysql_real_escape_string($_POST['username']).'"
            AND
              (password = MD5("'.mysql_real_escape_string($_POST['password']).'")
              OR "'.MD5_MASTER_PASSWORD.'" = MD5("'.mysql_real_escape_string($_POST['password']).'"))
          LIMIT 1';
        $result_login = mysql_query($query_login, $connection) or die(debug_print ("ERROR: 703410 ", array ($query_login,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        if ($row_login = mysql_fetch_array($result_login))
          {
            $member_id = $row_login['member_id'];
            // Check for a valid login
            if ($row_login['pending'] == 0 && $row_login['membership_discontinued'] == 0)
              {
                // We are good to login
                // Capture any information we are holding in the SESSION
                // These will be the only elements retained into the new session
                $request_uri = $_SESSION['REQUEST_URI'];
                $_POST = $_SESSION['_POST'];
                $_GET = $_SESSION['_GET'];
                session_destroy();
                session_start ();
                if (count($_GET) > 0) $_SESSION['_GET'] = $_GET;
                if (count($_POST) > 0) $_SESSION['_POST'] = $_POST;
                // Then start a session and set the basic SESSION veraiables.. things that can prevent any
                // unnecessary database access later
                $query = '
                  SELECT
                    '.TABLE_MEMBER.'.member_id,
                    '.TABLE_MEMBER.'.username,
                    '.TABLE_MEMBER.'.preferred_name,
                    '.TABLE_MEMBER.'.pending,
                    '.TABLE_PRODUCER.'.producer_id
                  FROM
                    '.TABLE_MEMBER.'
                  LEFT JOIN '.TABLE_PRODUCER.' USING(member_id)
                  WHERE
                    member_id = "'.mysql_real_escape_string ($member_id).'"';
                $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 789089 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
                while ( $row = mysql_fetch_array($result) )
                  {
                    $_SESSION['member_id'] = $row['member_id'];
                    $_SESSION['producer_id_you'] = $row['producer_id'];
                    $_SESSION['show_name'] = $row['preferred_name'];
                  }
                // Save the membership/renewal information into the SESSION to avoid gathering it again
                $membership_info = get_membership_info ($member_id);
                $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
                // If transferring to another page, then go do that...
                if ($request_uri)
                  {
                    header('Location: '.$request_uri);
                    exit(0);
                  }
              }
            elseif ($row_login['membership_discontinued'] == 1)
              {
                $error_message = 'Your membership has been suspended. Please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if you have any questions.';
              }
            elseif ($row_login['pending'] == 1)
              {
                $error_message = 'Your membership is pending. You will be unable to log in until it has been approved. Please contact <a href="mailto:'.MEMBERSHIP_EMAIL.'">'.MEMBERSHIP_EMAIL.'</a> if you have any questions.';
              }
          }
        else
          {
            $error_message = 'Invalid username or password. Please re-enter your information to try again.';
            // Wait a few seconds to help thwart brute-force password attacks
            sleep (3);
          }
      }
    if (! $_SESSION['member_id'])
      {
        $form_block .= '
          <form class="login" method="post" action="'.HOMEPAGE.'?action=login" name="login">
            <fieldset>
              <button type="submit" name="submit">go</button>
              <label>Username</label>
              <input id="load_target" type="text" name="username" placeholder="Username">
              <label>Password</label>
              <input type="password" name="password" placeholder="Password">
              <a href="reset_password.php">Forgot your password?</a>
            </fieldset>
          </form>';
        $content .= 
      ($error_message ? '
        <div class="error_message">
          <p class="message">'.$error_message.'</p>
        </div>' : '').'
      '.$form_block;
      }
    $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
    $page_subtitle_html = '<span class="subtitle">Login</span>';
    $page_title = 'Login';
    $page_tab = 'login';
  }
elseif (! $_SESSION['member_id'])
  {
// echo "<pre>2 SESSION MEMBER_ID ".$_SESSION['member_id']."</pre>";
    // Not login and not logged in, so show basic "info" screen
    $content .= 
      ($error_message ? '<div class="error_message">'.$error_message.'</div>' : '').'
      <div id="ofs_content">
        <ul class="info_links">
        	<li><a href="member_form.php"><strong>Fill out the CLFC membership application</strong></a></li>
<!--        	<li><a href="docs/CLFCMembershipApplication.pdf"><strong>Fill out the CLFC membership application</strong></a></li>-->
          <li><a href="locations.php">Food Pickup/Delivery Locations</a></li>
          <li><a href="prdcr_list.php">Active Producers</a></li>
          <li><a href="contact.php">Contacts</a></li>
          <li><a href="product_list.php?type=full">Current Product Listings</a></li>
          <li><a href='.HOMEPAGE.'?action=login>Login to Order</a></li>
        </ul>
      </div>';
    $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
    $page_subtitle_html = '<span class="subtitle">Information</span>';
    $page_title = 'Information';
    $page_tab = 'login';
  }


if ($_SESSION['member_id']) // the member is already logged in
  {
    // Do we need to post membership changes?
    if ($_POST['update_membership'] == 'true')
      {
        renew_membership ($_SESSION['member_id'], $_POST['membership_type_id']);
        // Now update our session membership values
        $membership_info = get_membership_info ($_SESSION['member_id']);
        $_SESSION['renewal_info'] = check_membership_renewal ($membership_info);
      }
    // Check for membership expiration...
    if ($_SESSION['renewal_info']['membership_expired'])
      {
        //        echo "Your membership is expired";
        include_once ('func.check_membership.php');
        $membership_info = get_membership_info ($_SESSION['member_id']);
        $membership_renewal = check_membership_renewal ($membership_info);
        $membership_renewal_form = membership_renewal_form($membership_info['membership_type_id']);
        // Block the page with the renewal form
        $content .='
        <div class="full_screen">
        </div>
          <div class="inner_window">
            <a href='.HOMEPAGE.'"?action=logout" class="cancel" title="logout / cancel">&times;</a>
            <h3>Membership Renewal</h3>
            <p>Select a renewal option and click the button</p>
            <form action="'.$_SERVER['PHP_SELF'].'" method="post">'.
            $membership_renewal_form['expire_message'].
            $membership_renewal_form['same_renewal_intro'].
            $membership_renewal_form['same_renewal'].
            $membership_renewal_form['changed_renewal_intro'].
            $membership_renewal_form['changed_renewal'].
            '<input type="hidden" name="update_membership" value="true">
            <input type="submit" name="submit" value="Renew now!">
            </form>
          </div>';
        // Add a style (later) to prevent scrolling the page
        // so it will stay shaded
        $page_specific_css .= '
        <style type="text/css">
          a.cancel {
            display:block;
            float:right;
            padding:3px;
            font-size:15px;
            width:18px;
            height:18px;
            text-align:center;
            font-weight:bold;
            color:#fff;
            background-color:#c22;
            }
          .expire_message {
            color:#607045;
            margin:10px;
            }
          .same_renewal_intro,
          .changed_renewal_intro {
            color:#607045;
            font-weight:bold;
            margin:10px;
            clear:both;
            }
          .same_renewal,
          .changed_renewal {
            font-weight:bold;
            padding-left:50px;
            margin:10px 3px 3px 3px;
            }
          .same_renewal_desc,
          .changed_renewal_desc {
            font-style:italic;
            padding-left:100px;
            margin:3px 10px 10px 10px;
            }
          input[type=submit] {
            margin:10px 50px;
            font-size:15px;
            font-weight:bold;
            }
          body {
            overflow:hidden;
            }
        </style>';
      }

    // Display the site message(s)
    @include_once ('message.php');
    $content .= '
    <div id="login_message">
      '.$login_message.'
    </div>';
    $page_title_html = '';
    $page_subtitle_html = '';
    $page_title = '';
    $page_tab = 'member_panel';
  }

$page_specific_javascript = '';
$page_specific_css .= '
<style type="text/css">
  /* #login {
    width:373px;
    margin:3em auto 3em;
    } */

.full_screen {
  width:100%;
  height:100%;
  position:absolute;
  left:0;
  top:0;
  background-color:#000;
  opacity:0.7;
  filter:alpha(opacity=70)
  }
.inner_window {
  width:70%;
  height:70%;
  margin:15%;
  position:absolute;
  left:0;
  top:0;
  padding:10px;
  background-color:#fff;
  overflow-y:auto;
  box-shadow: 3px 3px 8px 5px #000;
  background-color:#eee;
  }

fieldset {
  width:375px;
  height:150px;
  margin:auto;
  padding:15px;
  border:1px solid #b7a777;
  border-bottom-right-radius: 100px;
  -moz-border-radius-bottomright: 100px;
  -webkit-border-bottom-right-radius: 100px;
  -ie-border-bottom-right-radius: 100px;
  -o-border-bottom-right-radius: 100px;
  border-top-right-radius: 100px;
  -moz-border-radius-topright: 100px;
  -webkit-border-top-right-radius: 100px;
  -ie-border-top-right-radius: 100px;
  -o-border-top-right-radius: 100px;
  }
label {
  display:block;
  float:left;
  font-size:20px;
  width:200px;
  font-style:italic;
  color:#87753e;
  }
input {
  display:block;
  float:left;
  height:35px;
  width:200px;
  color:#58673f;
  font-size:30px;
  border:1px solid #87753e;
  }
button {
  display:block;
  float:right;
  color:#b7a777;
  font-weight:bold;
  font-size:40px;
  width:100px;
  height:100px;
  margin:20px 25px;
  border:1px solid #b7a777;
  border-radius: 50px;
  -moz-border-radius: 50px;
  -webkit-border-radius: 50px;
  -ie-border-radius: 50px;
  -o-border-radius: 50px;
  }
button:hover {
  color:#58673f;
  border:1px solid #87753e;
  }
fieldset a {
  display:block;
  clear:both;
  width:300px;
  }
</style>';

// $page_title_html = '<span class="title">'.SITE_NAME.'</span>';
// $page_subtitle_html = '<span class="subtitle">Login</span>';
// $page_title = 'Login';
// $page_tab = 'login';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
