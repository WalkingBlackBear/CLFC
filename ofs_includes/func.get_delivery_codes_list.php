<?php

include_once ('func.open_update_basket.php');
include_once ('func.get_basket.php');

// This function will get the html markup for a div containing formatted basket history.
// Sample/suggested CSS is given at the end.
function get_delivery_codes_list ($request_data)
  {
    global $connection;
    // See if it is okay to open a basket...
    if (ActiveCycle::delivery_id() &&
        ActiveCycle::ordering_window() == 'open')
//        && ! CurrentBasket::basket_id())
      {
        // If requested to open-basket...
        if ($request_data['action'] == 'open_basket')
          {
            if ($request_data['delcode_id'] &&
                $request_data['deltype'])
              {
                $delcode_id = $request_data['delcode_id'];
                $deltype = $request_data['deltype'];
                // First try an assigned delivery_id... then use the current active one
                $delivery_id = $request_data['delivery_id'];
                if (! $delivery_id) $delivery_id = ActiveCycle::delivery_id();
                // First try an assigned member_id... then use the current session one
                $member_id = $request_data['member_id'];
                if (! $member_id) $member_id = $_SESSION['member_id'];
                // Update the basket
                $basket_info = open_update_basket(array(
                  'member_id' => $member_id,
                  'delivery_id' => $delivery_id,
                  'delcode_id' => $delcode_id,
                  'deltype' => $deltype
                  ));
              }
          }
        // Get current basket information
        else
          {
            $basket_info = get_basket($request_data['member_id'], $request_data['delivery_id']);
          }

//         // Ordering is open and there is no basket open yet
//         // Get this member's most recent delivery location
//         $query = '
//           SELECT
//             '.TABLE_DELCODE.'.delcode_id,
//             '.TABLE_DELCODE.'.deltype
//           FROM
//             '.NEW_TABLE_BASKETS.'
//           LEFT JOIN
//             '.TABLE_DELCODE.' USING(delcode_id)
//           WHERE
//             '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string($_SESSION['member_id']).'"
//             AND '.TABLE_DELCODE.'.inactive = "0"
//           ORDER BY
//             delivery_id DESC
//           LIMIT
//             1';
//           $result = mysql_query ($query, $connection) or die(debug_print ("ERROR: 548167 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
//           if ($row = mysql_fetch_array ($result))
//             {
//               $delcode_id_prior = $row['delcode_id'];
//               $deltype_prior = $row['deltype'];
//             }
        // Now get the list of all available delivery codes and flag the one
        // that corresponds to this member's prior order
        $query = '
          SELECT
            '.TABLE_DELCODE.'.delcode_id,
            '.TABLE_DELCODE.'.delcode,
            '.TABLE_DELCODE.'.deltype,
            '.TABLE_DELCODE.'.deldesc,
            '.TABLE_DELCODE.'.delcharge,
            '.TABLE_DELCODE.'.inactive,
            '.TABLE_MEMBER.'.address_line1,
            '.TABLE_MEMBER.'.work_address_line1
          FROM
            '.TABLE_DELCODE.',
            '.TABLE_MEMBER.'
          WHERE
            '.TABLE_DELCODE.'.inactive != "1"
            AND '.TABLE_MEMBER.'.member_id = "'.mysql_real_escape_string($_SESSION['member_id']).'"
          ORDER BY
            delcode';
        $result = mysql_query ($query, $connection) or die(debug_print ("ERROR: 671934 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
        $delcode_id_array = array ();
        $deltype_array = array ();
        $display .= '
            <div id="delivery_dropdown" class="dropdown">
              <a href="'.$_SERVER['PHP_SELF'].'?action=delivery_list_only"><h1 class="delivery_select">'.
                ($basket_info['delcode_id'] ? '['.$basket_info['delcode_id'].'] Change location' : 'Select Location').'
              </h1></a>
              <div id="delivery_select">
                <ul class="delivery_select">';
        while ($row = mysql_fetch_array ($result))
          {
            // Simplify variables
            $delcode_id = $row['delcode_id'];
            $delcode = $row['delcode'];
            $deltype = $row['deltype'];
            $deldesc = $row['deldesc'];
            $delcharge = $row['delcharge'];
            $inactive = $row['inactive'];
            $address = $row['address_line1'];
            $work_address = $row['work_address_line1'];
            // Set up some text for the $delivery type (delivery or pickup)
            if ($deltype == 'P')
              {
                $deltype_text = 'Pickup your order here';
                $deltype_class = 'deltype-p';
              }
            elseif ($deltype == 'D')
              {
                $deltype_text_h = 'HOME delivery';
                $deltype_text_w = 'WORK delivery';
                if ($delcharge)
                  {
                    $deltype_text_h .= ' ($'.number_format($delcharge, 2).' charge)';
                    $deltype_text_w .= ' ($'.number_format($delcharge, 2).' charge)';
                  }
                $deltype_class = 'deltype-d';
              }
            else
              {
                $deltype_text = '';
                $deltype_class = '';
              }
            // Process the inactive options
            if ($inactive == 0)
              {
                $show_site = true;
                $active_class = ' active';
                $select_link = '<a href="'.$_SERVER['PHP_SELF'].'?action=open_basket&delcode_id='.$delcode_id.'&deltype=P">'.$deltype_text.'</a>';
                $select_link_h = '<a href="'.$_SERVER['PHP_SELF'].'?action=open_basket&delcode_id='.$delcode_id.'&deltype=H">'.$deltype_text_h.'</a>';
                $select_link_w = '<a href="'.$_SERVER['PHP_SELF'].'?action=open_basket&delcode_id='.$delcode_id.'&deltype=W">'.$deltype_text_w.'</a>';
                $deltype_class .= 'a'; // color
              }
            elseif ($inactive == 2)
              {
                $show_site = true;
                $active_class = ' inactive';
                $deltype_class .= 'i'; // color
                $select_link = '(Not available for this cycle)'; // clobber the select link
              }
            else // ($inactive == 1)
              {
                $show_site = false;
                $active_class = ' suspended';
                $deltype_class .= 'i'; // color
                $select_link = '(No longer available)'; // clobber the select link
              }
            // Process current selection
            if ($delcode_id == CurrentBasket::delcode_id())
              {
                $selected = true;
                $select_class = ' select';
                $deltype_class .= 'c'; // color
              }
            else
              {
                $selected = 'false';
                $select_class = '';
                $deltype_class .= 'g'; // greyscale
              }
            if ($show_site == true)
              {
                if ($deltype == 'P')
                  {
                    $display .= '
                      <li class="'.$deltype_class.$active_class.$select_class.'">
                        <span class="delcode">'.$delcode.'</span>
                        <span class="delcode_action">'.$select_link.'</span>
                        <span class="deldesc">'.br2nl($deldesc).'</span>
                      </li>';
                  }
                // For deltype = delivery, we will give an option for "home"
                if ($deltype == 'D' && $address)
                  {
                    if ($basket_info['deltype'] != 'H') $select_class = '';
                    $display .= '
                      <li class="'.$deltype_class.$active_class.$select_class.'">
                        <span class="delcode">'.$delcode.'</span>
                        <span class="delcode_action">'.$select_link_h.'</span>
                        <span class="deldesc"><strong>To home address:</strong> '.$address.'<br>'.br2nl($deldesc).'</span>
                      </li>';
                  }
                // For deltype = delivery, we will also give an option for "work"
                if ($deltype == 'D' && $work_address)
                  {
                    if ($basket_info['deltype'] != 'W') $select_class = '';
                    $display .= '
                      <li class="'.$deltype_class.$active_class.$select_class.'">
                        <span class="delcode">'.$delcode.'</span>
                        <span class="delcode_action">'.$select_link_w.'</span>
                        <span class="deldesc"><strong>To work address:</strong> '.$work_address.'<br>'.br2nl($deldesc).'</span>
                      </li>';
                  }
              }
          }
        $display .= '
                </ul>
              </div>
            </div>';
      }
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
// /* Styles for the delivery dropdown */
//   #delivery_dropdown {
//     border:1px solid #000;
//     float:left;
//     width:400px;
//     height:26px;
//     overflow:hidden;
//     }
//   #delivery_dropdown:hover {
//     height:400px;
//     }
//   h1.delivery_select {
//     width:394px;
//     font-size:16px;
//     color:#efe;
//     background-color:#050;
//     position:relative;
//     height:20px;
//     margin:0;
//     padding:3px;
//     }
//   #delivery_select {
//     width:100%;
//     background-color:#fff;
//     overflow:auto;
//     width:400px;
//     height:374px;
//     }
//   ul.delivery_select {
//     list-style-type:none;
//     padding-left:0;
//     }
//   ul.delivery_select li {
//     padding-top:10px;
//     text-align:left;
//     height:120px;
//     padding-left:70px;
//     border-top:1px solid transparent;
//     border-bottom:1px solid transparent;
//     }
//   ul.delivery_select li.active:hover {
//     cursor:pointer;
//     background-color:#ffd;
//     border-top:1px solid #da6;
//     border-bottom:1px solid #da6;
//     }
//   ul.delivery_select li span {
//     vertical-align: middle;
//     }
//   .delcode {
//     display:block;
//     font-size:130%;
//     font-weight:bold;
//     color:#350;
//     }
//   .delcode_action {
//     font-weight:bold;
//     color:#066;
//     }
//   .deldesc {
//     display:block;
//     font-size:90%;
//     color:#530;
//     }
//   li.deltype-dag {
//     background:url(../grfx/deltype-dag.png) no-repeat 7px 7px;
//     }
//   li.deltype-dac {
//     background:url(../grfx/deltype-dac.png) no-repeat 7px 7px;
//     }
//   li.deltype-dig {
//     background:url(../grfx/deltype-dig.png) no-repeat 7px 7px;
//     }
//   li.deltype-pag {
//     background:url(../grfx/deltype-pag.png) no-repeat 7px 7px;
//     }
//   li.deltype-pac {
//     background:url(../grfx/deltype-pac.png) no-repeat 7px 7px;
//     }
//   li.deltype-pig {
//     background:url(../grfx/deltype-pig.png) no-repeat 7px 7px;
//     }
?>