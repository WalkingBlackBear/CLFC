<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin');


$query = '
  SELECT
    '.TABLE_ROUTE.'.*,
    '.TABLE_DELCODE.'.delcode_id,
    '.TABLE_DELCODE.'.delcode,
    '.TABLE_DELCODE.'.hub,
    '.TABLE_DELCODE.'.deldesc,
    '.TABLE_DELCODE.'.route_id,
    '.TABLE_DELCODE.'.truck_code
  FROM
    '.TABLE_ROUTE.',
    '.TABLE_DELCODE.'
  WHERE
    '.TABLE_ROUTE.'.route_id = "'.mysql_real_escape_string ($_GET['route_id']).'"
    AND '.TABLE_DELCODE.'.route_id = '.TABLE_ROUTE.'.route_id
    AND '.TABLE_DELCODE.'.delcode_id = "'.mysql_real_escape_string ($_GET['delcode_id']).'"
  ORDER BY
    route_name ASC';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 769302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $delcode_array = (array) $row;
  }
$query = '
  SELECT
    1 AS rte_confirmed,
    1 AS finalized,
    '.NEW_TABLE_BASKETS.'.deltype as ddeltype,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.TABLE_MEMBER.'.member_id,
    last_name,
    first_name,
    first_name_2,
    last_name_2,
    business_name,
    preferred_name,
    home_phone,
    work_phone,
    mobile_phone,
    fax,
    email_address,
    email_address_2,
    address_line1,
    address_line2,
    city,
    state,
    zip,
    work_address_line1,
    work_address_line2,
    work_city,
    work_state,
    work_zip
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
  LEFT JOIN '.TABLE_MEMBER.' USING(member_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
    AND '.NEW_TABLE_BASKETS.'.delcode_id = "'.mysql_real_escape_string ($delcode_array['delcode_id']).'"
    AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != "1"
    AND '.NEW_TABLE_PRODUCTS.'.tangible = 1
  GROUP BY
    '.NEW_TABLE_BASKETS.'.basket_id
  ORDER BY
    last_name ASC';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 769302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num_orders = mysql_numrows($result);
while ( $row = mysql_fetch_array($result) )
  {
    $member_array = (array) $row;
    $display .= '
      <table cellpadding=0 cellspacing=0 border=0><tr><td id="'.$member_id.'" width="400" valign=top>';
    $display .= '
      <li> <b>'.(convert_route_code(array_merge ($delcode_array, $member_array))).'</b><br>
      <a href="show_report.php?type=customer_invoice&delivery_id='.ActiveCycle::delivery_id().'&member_id='.$member_array['member_id'].'">
      <b>'.$member_array['preferred_name'].' (Mem#'.$member_array['member_id'].')</b></a><!-- - Deliverable Products: '.$product_quantity_of_member.' -->';
    $display .= '   <ul>';
    if ( $member_array['ddeltype'] == 'W' )
      {
        $display .= 'Work address:<br>';
        if ( $member_array['work_address_line1'] )
          {
            $display .= $member_array['work_address_line1'].'<br>';
          }
        else
          {
            $display .= 'No work address available<br>';
          }
        if ( $member_array['work_address_line2'] )
          {
            $display .= $member_array['work_address_line2'].'<br>';
          }
        if ( $member_array['work_city'] || $member_array['work_state'] || $member_array['work_zip'] )
          {
            $display .= $member_array['work_city'].', '.$member_array['work_state'].', '.$member_array['work_zip'].'<br>';
          }
      }
    else
      {
        $display .= 'Home address:<br>';
        $display .= $member_array['address_line1'].'<br>';
        if ( $member_array['address_line2'] )
          {
            $display .= $member_array['address_line2'].'<br>';
          }
        $display .= $member_array['city'].', '.$member_array['state'].', '.$member_array['zip'].'<br>';
      }
    $display .= 'Email: '.$member_array['email_address'].' <br>';
    if ( $member_array['email_address_2'] )
      {
        $display .= 'Email2: '.$member_array['email_address_2'].' <br>';
      }
    if ( $member_array['home_phone'] )
      {
        $display .= 'Home: '.$member_array['home_phone'].' <br>';
      }
    if ( $member_array['work_phone'] )
      {
        $display .= 'Work: '.$member_array['work_phone'].' <br>';
      }
    if ( $member_array['mobile_phone'] )
      {
        $display .= 'Cell: '.$member_array['mobile_phone'].' <br>';
      }
    if ( $member_array['fax'] )
      {
        $display .= 'Fax: '.$member_array['fax'].'<br>';
      }
    $display .= '   </ul><br>';
    $display .= '</td> <!-- <td valign="middle">';
    if ( $member_array['rte_confirmed'] == 1 )
      {
        $display .= '<b>Route Confirmed</b>';
      }
    else
      {
        $display .= '
          <form action="'.$PHP_SELF.'#'.$member_array['member_id'].'" method="post">
          <input type="hidden" name="member_id_passed" value="'.$member_array['member_id'].'">
          <input type="hidden" name="basket_id_passed" value="'.$member_array['basket_id'].'">
          <input type="hidden" name="subtotal" value="'.$member_array['total'].'">
          <input type="hidden" name="route_id" value="'.$_GET['route_id'].'">
          <input type="hidden" name="delcode_id" value="'.$member_array['delcode_id'].'">
          <input type="hidden" name="confirm_route" value="yes">
          <input type="submit" name="where" value="Confirm '.$member_array['first_name'].'&#146;s route info">
          </form>
          <br /><a href="delivery_change.php?member_id='.$member_array['member_id'].'&basket_id='.$member_array['basket_id'].'">Change this delivery/pick-up</a>';
      }
    $display .= '
          </td> -->
        </tr>
      </table>';
  }
$quantity_all = 0;
$query = '
  SELECT
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.NEW_TABLE_BASKETS.'.delcode_id,
    '.NEW_TABLE_BASKET_ITEMS.'.product_id,
    '.NEW_TABLE_PRODUCTS.'.product_name,
    SUM('.NEW_TABLE_BASKET_ITEMS.'.quantity) AS sum_p,
    '.NEW_TABLE_PRODUCTS.'.ordering_unit,
    '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock,
    '.NEW_TABLE_BASKET_ITEMS.'.product_id,
    1 AS future_delivery_id,
    '.TABLE_PRODUCER.'.business_name
  FROM
    '.NEW_TABLE_BASKET_ITEMS.'
  LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
  LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
  LEFT JOIN '.TABLE_PRODUCER.' USING(producer_id)
  WHERE
    '.NEW_TABLE_BASKETS.'.delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
    AND '.NEW_TABLE_BASKETS.'.delcode_id = "'.mysql_real_escape_string ($delcode_array['delcode_id']).'"
    AND '.NEW_TABLE_BASKET_ITEMS.'.out_of_stock != "1"
  GROUP BY
    '.NEW_TABLE_BASKET_ITEMS.'.product_id
  ORDER BY
    business_name,
    sum_p DESC';
$result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 860342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ($row = mysql_fetch_array($result))
  {
    $product_id = $row['product_id'];
    $product_name = $row['product_name'];
    $product_quantity = $row['sum_p'];
    $ordering_unit = $row['ordering_unit'];
    $business_name = $row['business_name'];
    if (strlen ($business_name) > 16)
      {
        $business_name = str_replace (' ', '&nbsp;', substr ($business_name, 0, 9).'...'.substr ($business_name, -4, 4));
      }
    $display_p .= '
      <tr>
        <td align="right">'.$product_quantity.'</td>
        <td align="left">'.str_replace (' ', '&nbsp;', Inflect::pluralize_if ($product_quantity, $ordering_unit)).' </td>
        <td align="left">'.$business_name.'</td>
        <td>&nbsp; '.$product_name.' (#&nbsp;'.$product_id.')</td>
      </tr>';
    $quantity_all += $row['sum_p'];
  }

$content_delivery = '
<div align="center">
  <table width="80%" bgcolor="#FFFFFF" cellspacing="2" cellpadding="2" border="0">
    <tr>
      <td align="left">
        <h3>Route List: '.ActiveCycle::delivery_date().'</h3>
      </td>
    </tr>
    <tr>
      <td align="left" bgcolor="#DDDDDD">
        <b>Delivery Specifics: '.$delcode_array['delcode'].' (Hub: '.$delcode_array['hub'].')</b><br>
        '.$delcode_array['deldesc'].'<br><br>
      </td>
    </tr>
    <tr>
      <td align="left">
        The following information is based on preliminary information since we are still waiting for producer confirmations of products.<br><br>
        <b>Members on this Route ('.$num_orders.' Orders)</b>
        <ul>
          '.$display.'
        </ul><br>
        <b>'.$quantity_all.' Products on this Route</b>
        <blockquote>
          <table>
            '.$display_p.'
          </table>
        </blockquote><br>
      </td>
    </tr>
  </table>
</div>';

$page_title_html = '<span class="title">'.$delcode_array['route_name'].' Route Members</span>';
$page_subtitle_html = '<span class="subtitle">'.date ('F d, Y', strtotime (ActiveCycle::delivery_date())).'</span>';
$page_title = $delcode_array['route_name'].' Route Members: '.date ('F d, Y', strtotime (ActiveCycle::delivery_date()));
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_delivery.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
