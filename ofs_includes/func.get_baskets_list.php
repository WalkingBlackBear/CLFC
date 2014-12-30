<?php

// This function will get the html markup for a div containing formatted basket history.
// Sample/suggested CSS is given at the end.
function get_baskets_list ()
  {
    global $connection;
    // Get a list of the order cycles since the member joined
    $delivery_id_array = array();
    $delivery_attrib = array ();
    $query = '
      SELECT 
        delivery_id,
        date_open,
        date_closed,
        order_fill_deadline,
        delivery_date
      FROM
        '.TABLE_ORDER_CYCLES.'
      WHERE
        delivery_date > "'.mysql_real_escape_string($_SESSION['renewal_info']['membership_date']).'"
        AND date_open < NOW()
      ORDER BY
        delivery_date DESC';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 898034 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    WHILE ($row = mysql_fetch_array($result))
      {
        array_push ($delivery_id_array, $row['delivery_id']);
        $delivery_attrib[$row['delivery_id']]['date_open'] = $row['date_open'];
        $delivery_attrib[$row['delivery_id']]['time_open'] = strtotime($row['date_open']);
        $delivery_attrib[$row['delivery_id']]['date_closed'] = $row['date_closed'];
        $delivery_attrib[$row['delivery_id']]['time_closed'] = strtotime($row['date_closed']);
        $delivery_attrib[$row['delivery_id']]['order_fill_deadline'] = $row['order_fill_deadline'];
        $delivery_attrib[$row['delivery_id']]['delivery_date'] = $row['delivery_date'];
      }
    // Now get this customer's baskets
    $query = '
      SELECT 
        *
      FROM
        '.NEW_TABLE_BASKETS.'
      WHERE
        member_id = "'.mysql_real_escape_string($_SESSION['member_id']).'"
      ORDER BY
        delivery_id DESC';
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 898034 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    WHILE ($row = mysql_fetch_array($result))
      {
        $delivery_attrib[$row['delivery_id']]['basket_id'] = $row['basket_id'];
        $delivery_attrib[$row['delivery_id']]['delcode_id'] = $row['delcode_id'];
        $delivery_attrib[$row['delivery_id']]['deltype'] = $row['deltype'];
        $delivery_attrib[$row['delivery_id']]['checked_out'] = $row['checked_out'];
      }
    // Display the order cycles and baskets...
    $display .= '
        <div id="basket_dropdown" class="dropdown">
          <a href="'.$_SERVER['PHP_SELF'].'?action=basket_list_only"><h1 class="basket_history">
            Ordering History
          </h1></a>
          <div id="basket_history">
            <ul class="basket_history">';
    foreach ($delivery_id_array as $delivery_id)
      {
        $full_empty = '';
        $open_closed = '';
        $future_past = '';
        // Check if basket for the delivery had any items...
        if ($delivery_attrib[$delivery_id]['checked_out'] != 0)
          {
            $fe = 'f'; // full
            $full_empty = 'full';
          }
        else
          {
            $fe = 'e'; // empty
            $full_empty = 'empty';
          }
        // Check if this basket is currently open...
        if ($delivery_attrib[$delivery_id]['time_open'] < time() &&
            $delivery_attrib[$delivery_id]['time_closed'] > time())
          {
            $cg = 'c'; // colored
            $current = true;
            // Start the after_current counter
            $open_closed = 'open';
            $after_current_count = 1;
          }
        else
          {
            $cg = 'g'; // grey
            $open_closed = 'closed';
          }
        // Check if this is a future delivery...
        if ($delivery_attrib[$delivery_id]['time_open'] > time())
          {
            $is = 'i'; // insubstantial
            $cg = 'c'; // colored
            $current = false;
            $after_current_count ++;
            $future_past = 'future';
          }
        else
          {
            $is = 's'; // substantial
            $future_past = 'past';
          }
        $day_open = date ('j', $delivery_attrib[$delivery_id]['time_open']);
        $month_open = date ('M', $delivery_attrib[$delivery_id]['time_open']);
        $year_open = date ('Y', $delivery_attrib[$delivery_id]['time_open']);
        $day_closed = date ('j', $delivery_attrib[$delivery_id]['time_closed']);
        $month_closed = date ('M', $delivery_attrib[$delivery_id]['time_closed']);
        $year_closed = date ('Y', $delivery_attrib[$delivery_id]['time_closed']);
        if ($day_open == $day_closed) $day_open = '';
        if ($month_open == $month_closed) $month_closed = '';
        if ($year_open == $year_closed) $year_open = '';
        $items_in_basket = abs($delivery_attrib[$delivery_id]['checked_out']);
        // Process basket quantity display
        if ($future_past != 'future')
          {
            if ($items_in_basket) 
              {
                $basket_quantity_text = $items_in_basket.' '.Inflect::pluralize_if($items_in_basket, 'item').'  ordered.';
              }
            else
              {
                $basket_quantity_text = 'Nothing ordered.';
              }
          }
        // Current order... set link for opening or checking basket
        $current_link = '';
        if ($open_closed == 'open')
          {
            // Basket does not exist?
            if (! $delivery_attrib[$delivery_id]['basket_id'])
              {
                $current_link = '
                  <a href="">Start an Order</a>';
              }
            else
              {
//                 $current_link = '
//                   <a href="product_list.php?type=basket&delivery_id='.$delivery_id.'">View Basket</a>';
              }
            $basket_quantity_text = '';
            
          }
        if ($after_current_count <= 2) // Including current
          {
// Need some onclick code for class=view (full baskets)
            $display .= '
              <li class="'.$fe.$cg.$is.($full_empty == 'full' || $current == 'true' ? ' view' : '').'"'.($open_closed == 'open' ? ' id="current"' : '').'>
                <span class="delivery_date">Delivery: '.date('M j, Y', strtotime($delivery_attrib[$delivery_id]['delivery_date'])).'</span>'.
                (CurrentBasket::basket_id() && $current == 'true' ? '
                  <span class="basket_link"><a href="product_list.php?type=basket&delivery_id='.$delivery_id.'">Basket</a></span>
                   &bull; <!--
                   <span class="accounting_link"><a href="member_view_balance.php?account_type=member&delivery_id='.$delivery_id.'">Account</a></span>
                   &bull; -->
                   <span class="accounting_link"><a href="show_report.php?type=customer_invoice&delivery_id='.$delivery_id.'&member_id='.$_SESSION['member_id'].'">Invoice</a></span>'
                : '').'
                <span class="order_dates">'.$month_open.' '.$day_open.' '.$year_open.' &ndash; '.$month_closed.' '.$day_closed.' '.$year_closed.'</span>
                <span class="basket_qty">'.$basket_quantity_text.'</span>
                <span class="basket_action">'.$current_link.'</span>
              </li>';
          }
      }
    $display .= '
            </ul>
          </div>
        </div>';
    return $display;
  }

// /* Styles for the all dropdowns */
//   .dropdown {
//     -transition:height 0.7s cubic-bezier(1,0,0.5,1);
//     -webkit-transition:height 0.7s cubic-bezier(1,0,0.5,1);
//     -moz-transition:height 0.7s cubic-bezier(1,0,0.5,1);
//     -o-transition:height 0.7s cubic-bezier(1,0,0.5,1);
//     -ie-transition:height 0.7s cubic-bezier(1,0,0.5,1);
//     }
// /* Styles for the Basket Selector */
//   #basket_dropdown {
//     border:1px solid #000;
//     float:left;
//     width:300px;
//     height:26px;
//     overflow:hidden;
//     }
//   #basket_dropdown:hover {
//     height:400px;
//     }
//   h1.basket_history {
//     width:294px;
//     font-size:16px;
//     color:#efe;
//     background-color:#050;
//     position:relative;
//     height:20px;
//     margin:0;
//     padding:3px;
//     }
//   #basket_history {
//     width:100%;
//     background-color:#fff;
//     overflow:auto;
//     width:300px;
//     height:374px;
//     }
//   ul.basket_history {
//     list-style-type:none;
//     padding-left:0;
//     }
//   ul.basket_history li {
//     padding-top:10px;
//     text-align:left;
//     height:55px;
//     padding-left:70px;
//     border-top:1px solid transparent;
//     border-bottom:1px solid transparent;
//     }
//   ul.basket_history li:hover {
//     }
//   ul.basket_history li.view:hover {
//     cursor:pointer;
//     background-color:#efd;
//     border-top:1px solid #ad6;
//     border-bottom:1px solid #ad6;
//     }
//   ul.basket_history li span {
//     vertical-align: middle;
//     }
//   .delivery_date {
//     display:block;
//     font-size:130%;
//     font-weight:bold;
//     color:#350;
//     }
//   .order_dates {
//     display:block;
//     font-size:90%;
//     color:#530;
//     }
//   .basket_qty {
//     color:#000;
//     }
//   li.fcs {
//     background:url(../grfx/basket-fcs.png) no-repeat 7px 7px;
//     }
//   li.fci {
//     background:url(../grfx/basket-fci.png) no-repeat 7px 7px;
//     }
//   li.fgs {
//     background:url(../grfx/basket-fgs.png) no-repeat 7px 7px;
//     }
//   li.fgi {
//     background:url(../grfx/basket-fgi.png) no-repeat 7px 7px;
//     }
//   li.ecs {
//     background:url(../grfx/basket-ecs.png) no-repeat 7px 7px;
//     }
//   li.eci {
//     background:url(../grfx/basket-eci.png) no-repeat 7px 7px;
//     }
//   li.egs {
//     background:url(../grfx/basket-egs.png) no-repeat 7px 7px;
//     }
//   li.egi {
//     background:url(../grfx/basket-egi.png) no-repeat 7px 7px;
//     }
//   li.eci span,
//   li.egs span,
//   li.egi span {
//     color:#999;
//     }
?>