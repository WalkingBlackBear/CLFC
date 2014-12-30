<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin');


$query = '
  SELECT
    '.TABLE_ROUTE.'.route_id,
    '.TABLE_ROUTE.'.route_name
  FROM
    '.TABLE_ROUTE.'
  GROUP BY
    '.TABLE_ROUTE.'.route_id
  ORDER BY
    '.TABLE_ROUTE.'.route_name ASC';
$result = @mysql_query($query, $connection) or die("x1".debug_print ("ERROR: 860342 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
while ( $row = mysql_fetch_array($result) )
  {
    $route_id = $row['route_id'];
    $route_name = $row['route_name'];
    $display .= '<tr><td colspan="4" bgcolor="#AEDE86">'.$font.'<b>'.$route_name.'</b></td></tr>';
    $sql = '
      SELECT
        delcode_id,
        delcode,
        route_id,
        hub
      FROM
        '.TABLE_DELCODE.'
      WHERE
        route_id = "'.mysql_real_escape_string ($route_id).'"
      ORDER BY delcode ASC';
    $rs = @mysql_query($sql, $connection) or die(mysql_error("ERROR"));
    while ( $row = mysql_fetch_array($rs) )
      {
        $delcode_id = $row['delcode_id'];
        $delcode = $row['delcode'];
        $hub = $row['hub'];
        if ( $current_delcode_id < 0 )
          {
            $current_delcode_id = $row['delcode_id'];
          }
        while ( $current_delcode_id != $delcode_id )
          {
            $current_delcode_id = $delcode_id;
            $rte_confirmed_total = "";
            $query_confirm = '
              SELECT
                0 AS rte_confirmed,
                /* '.NEW_TABLE_BASKETS.'.rte_confirmed, */
                '.NEW_TABLE_BASKETS.'.basket_id
              FROM
                '.NEW_TABLE_BASKET_ITEMS.'
              LEFT JOIN '.NEW_TABLE_BASKETS.' USING(basket_id)
              LEFT JOIN '.NEW_TABLE_PRODUCTS.' USING(product_id,product_version)
              WHERE
                '.NEW_TABLE_BASKETS.'.delcode_id = "'.mysql_real_escape_string ($delcode_id).'"
                AND '.NEW_TABLE_BASKETS.'.delivery_id = '.mysql_real_escape_string (ActiveCycle::delivery_id()).'
                AND out_of_stock != quantity
                AND '.NEW_TABLE_PRODUCTS.'.tangible = "1"
              GROUP BY '.NEW_TABLE_BASKETS.'.basket_id';
            $result_confirm = @mysql_query($query_confirm, $connection) or die("x2".debug_print ("ERROR: 690430 ", array ($query_confirm,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
            while ( $row_confirm = mysql_fetch_array($result_confirm) )
              {
                $basket_id = $row_confirm['basket_id'];
                $rte_confirmed = $row_confirm['rte_confirmed'];
                $rte_confirmed_total = $rte_confirmed_total + $rte_confirmed + 0;
              }
            $num_orders = mysql_numrows($result_confirm);
            if ( !$num_orders )
              {
                $num_orders = 0;
              }
//             $remaining_to_confirm = $num_orders - $rte_confirmed_total;
//             if ( ($num_orders == $rte_confirmed_total) && $num_orders != 0 )
//               {
//                 $display_confirmed = '<td>All confirmed</td>';
//               }
//             elseif ( $num_orders == 0 )
//               {
//               $display_confirmed = '<td></td>';
//               }
//             else
//               {
//                 $display_confirmed = '<td bgcolor="#ADB6C6">'.$remaining_to_confirm.' awaiting confirmation by route manager</td>';
//               }
          }
        $display .='
          <tr>
            <td>[<a href="delivery_list.php?route_id='.$route_id.'&delcode_id='.$delcode_id.'">By Member</a>]</td>
            <td>[Hub: '.$hub.']</td>
            <td><b>'.$delcode.'</b> ('.$num_orders.' orders)</td>
            '.$display_confirmed.'
          </tr>';
        $total_orders = $total_orders + $num_orders;
      }
    $display .= '<tr><td colspan="3"><br></td></tr>';
  }

$content_delivery = '
  <div align="center">
    <table width="80%">
      <tr>
        <td align="left">
          <b><?php echo $total_orders;?> Total Orders for this Ordering Cycle</b><br>
          <ul>
            <li> <a href="delivery_list_all.php">List of ALL members with orders on each route</a>
          </ul>
          <b>List by Route</b><br>
          <table width="100%" cellpadding="3" cellspacing="0" border="0">
            '.$display.'
          </table>
        </td>
      </tr>
    </table>
  </div>';


$page_title_html = '<span class="title">Route Information</span>';
$page_subtitle_html = '<span class="subtitle">Deliveries and Pickups</span>';
$page_title = 'Route Information: Deliveries and Pickups';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_delivery.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
