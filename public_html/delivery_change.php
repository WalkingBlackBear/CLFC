<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin');


include("classes/delivery.class.php");
$content_change .= '<div align="center">';
if ( $_POST )
  {
    Delivery::changeUserDeliveryInfo();
    $r = preg_replace("/[^0-9]/","",$_POST['r']);
    $d = preg_replace("/[^a-zA-Z0-9]/","",$_POST['d']);
    $content_change .= '<strong>Delivery updated.</strong> <a href="delivery.php">Return to the delivery list</a>.';
  }
else
  {
    $member_id = preg_replace("/[^0-9]/","",$_REQUEST['member_id']);
    $basket_id = preg_replace("/[^0-9]/","",$_REQUEST['basket_id']);
    if ( $member_id > 0 && $basket_id > 0 )
      {
        $sql = '
          SELECT
            delivery_id,
            preferred_name
          FROM
            '.TABLE_BASKET_ALL.'
          LEFT JOIN
            '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
          WHERE
            basket_id = "'.mysql_real_escape_string ($basket_id).'"
            AND '.TABLE_BASKET_ALL.'.member_id = "'.mysql_real_escape_string ($member_id).'"
          LIMIT 1';
        $result = mysql_query($sql) or die(mysql_error()." ".$sql);
        $row = mysql_fetch_array($result);
        // See if this basket is in the current delivery cycle, otherwise, no updating.
        if  ($row['delivery_id'] > 0 && $row['delivery_id'] == ActiveCycle::delivery_id() )
          {
            $preferred_name = $row['preferred_name'];
            $content_change .= '
              Change the location for basket '.$basket_id.' (Member #'.$member_id.':)<br />';
            $content_change .= Delivery::printChangeDeliveryInfoForm($basket_id,$member_id);
          }
        else
          {
            $content_change .= '
              This basket&#146;s delivery can no longer be changed.
              It was in delivery cycle '.$row['delivery_id'].' and only baskets in delivery cycle '.ActiveCycle::delivery_id().' can be edited at this time.';
          }
      }
  }
$content_change .= '</div>';

$page_title_html = '<span class="title">Change Delivery/Pick-up Location</span>';
$page_subtitle_html = '<span class="subtitle">'.$preferred_name.'</span>';
$page_title = 'Change Delivery/Pick-up Location: '.$preferred_name;
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_change.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
