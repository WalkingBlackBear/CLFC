<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
valid_auth('member');

// THIS FILE IS MODIFIED FOR CLOVERBELT LOCAL FOOD COOP

// Set up English grammar for ordering dates

$relative_text = '';
$close_suffix = '';
$open_suffix = '';

if ( strtotime (ActiveCycle::date_open_next()) < time ()  && strtotime (ActiveCycle::date_closed_next()) > time ())
  {
    $relative_text = 'Current&nbsp;';
  }
elseif ( strtotime (ActiveCycle::date_closed_next()) > time () )
  {
    $relative_text = 'Next&nbsp;';
  }
else // strtotime (ActiveCycle::delivery_date_next()) < time ()
  {
    $relative_text = 'Prior&nbsp;';
  }

if ( strtotime (ActiveCycle::date_open_next()) < time () )
  {
    $open_suffix = 'ed'; // Open[ed]
  }
else
  {
    $open_suffix = 's'; // Open[s]
  }

if ( strtotime (ActiveCycle::date_closed_next()) < time () )
  {
    $close_suffix = 'd'; // Close[d]
  }
else
  {
    $close_suffix = 's'; // Close[s]
  }

// echo "<pre>".print_r($_SESSION,true)."</pre>";

// Get basket status information
$query = '
  SELECT
    COUNT(product_id) AS basket_quantity,
    '.NEW_TABLE_BASKETS.'.basket_id
  FROM
    '.NEW_TABLE_BASKETS.'
  LEFT JOIN '.NEW_TABLE_BASKET_ITEMS.' ON '.NEW_TABLE_BASKETS.'.basket_id = '.NEW_TABLE_BASKET_ITEMS.'.basket_id
  WHERE
    '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
    AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
  GROUP BY
    '.NEW_TABLE_BASKETS.'.member_id';
//$result = @mysql_query($query, $connection) or die("X1".debug_print ("ERROR: 670342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$result = @mysql_query($query, $connection);
$basket_quantity = 0;
if ($row = @mysql_fetch_object($result))
  {
    $basket_quantity = $row->basket_quantity;
    $basket_id = $row->basket_id;
  }
// if ( ActiveCycle::ordering_window() == 'open')
//   {
//     if ($basket_id)
//       {
//         $basket_status = 'Ready for shopping<br>'.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').' in basket';
//       }
//     else
//       {
//         $basket_status = '<a href="open_basket.php">Open a shopping basket</a>';
//       }
//   }
// else
//   {
//     $basket_status = 'Ordering is currently closed<br>'.$basket_quantity.' '.Inflect::pluralize_if($basket_quantity, 'item').' in basket';
//   }

/////////////// FINISH PRE-PROCESSING AND BEGIN PAGE GENERATION /////////////////



// Generate the display output
$display .= '
  <table width="100%" class="compact">
    <tr valign="top">
      <td align="left" width="50%">
    <img src="grfx/current.png" width="32" height="32" align="left" hspace="2" alt="Order"><br>
    <strong>'.$relative_text.'Order</strong>
        <ul class="fancyList1">
          <li><strong>Open'.$open_suffix.':</strong>&nbsp;'.date ('M&\\n\b\s\p;j,&\\n\b\s\p;g:i&\\n\b\s\p;A', strtotime (ActiveCycle::date_open_next())).'</li>
          <li><strong>Close'.$close_suffix.':</strong>&nbsp;'.date ('M&\\n\b\s\p;j,&\\n\b\s\p;g:i&\\n\b\s\p;A', strtotime (ActiveCycle::date_closed_next())).'</li>
          <li class="last_of_group"><strong>Delivery:</strong>&nbsp;'.date ('F&\\n\b\s\p;j', strtotime (ActiveCycle::delivery_date_next())).'</li>
        </ul>
<!--
    <img src="grfx/shopping.png" width="32" height="32" align="left" hspace="2" alt="Basket Status"><br>
    <strong>Basket Status</strong>
        <ul class="fancyList1">
          <li class="last_of_group">'.$basket_status.'</li>
        </ul>
-->
    <img src="grfx/type.png" width="32" height="32" align="left" hspace="2" alt="Membership Type"><br>
    <strong>Membership Type</strong>
        <ul class="fancyList1">
          <li><strong>'.$_SESSION['renewal_info']['membership_class'].':</strong> '.$_SESSION['renewal_info']['membership_description'].'<br><br></li>
<!--          <li class="last_of_group">'.$_SESSION['renewal_info']['membership_message'].'</li>-->
        </ul>
    <img src="grfx/time.png" width="32" height="32" align="left" hspace="2" alt="Information"><br>
    <strong>Next Renewal Date</strong>
        <ul class="fancyList1">
<!--          <li class="last_of_group">'.date('F j, Y', strtotime($_SESSION['renewal_info']['standard_renewal_date'])).'</li>-->
          <li class="last_of_group">Membership does not expire.</li>
				</ul>
      </td>
      <td align="left" width="50%">
        <img src="grfx/status.png" width="32" height="32" align="left" hspace="2" alt="Member Resources"><br>
        <b>Member Resources</b>
        <ul class="fancyList1">
          <li><a href="locations.php">Food Pickup/Delivery Locations</a></li>
          <li><a href="contact.php">How to Contact Us with Questions</a></li>
          <li><a href="member_form.php">Update Membership Info.</a></li>
          <li><a href="reset_password.php">Change Password</a></li>
          <li><a href="faq.php">How to Order FAQ</a></li>
          <li class="last_of_group"><a href="producer_form.php?action=new_producer">New Producer Application Form</a></li>
        </ul>
        <img src="grfx/money.png" width="32" height="32" align="left" hspace="2" alt="Payment Options"><br>
        <b>Payment Options</b>
        <ul class="fancyList1">';
$display .= '
					<li class="last_of_group">
						<strong>Bring payment when picking up your order.</strong><br />
						Tuesdays, between 4 and 6pm at the Dryden District Agricultural Society.<br /><br />
					</li>
					<li class="last_of_group">
						<strong>Send an Interac E-mail transfer from your financial institution.</strong><br />
						Send it to membership@cloverbeltlocalfoodcoop.com.  (Be sure to tell us the answer to the security question so we can accept your payment!)<br /><br />
					</li>
';
/*if ( PAYPAL_EMAIL) $display .= '
          <li class="last_of_group">
            <strong>PayPal:</strong><br><br>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="paypal">
              <input type="hidden" name="cmd" value="_xclick">
              <input type="hidden" name="business" value="'.PAYPAL_EMAIL.'">
              <input type="hidden" name="item_name" value="Food Coop: '.$_SESSION['show_name'].' (#'.$_SESSION['member_id'].') Delivery Date: '.ActiveCycle::delivery_date().'">
              <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_buynow_SM.gif" border="0" name="submit" alt="Make payment with PayPal">
            </form>
          </li>';
*/
$display .= '
          <li class="last_of_group">
            <strong>Pay by cheque to :</strong><br />
            '.SITE_MAILING_ADDR.'<br><br>
            (Reference &quot;Member #'.$_SESSION['member_id'].'&quot; on payment)
          </li>
        </ul>
        </td>
      </tr>
    </table>';

$page_title_html = '<span class="title bold">'.$_SESSION['show_name'].' &rArr; </span>';
$page_subtitle_html = '<span class="subtitle">Member Panel</span><br /><br />';
$page_title = 'Member Panel';
$page_tab = 'member_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");