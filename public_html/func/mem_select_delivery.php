<?php
echo '
  <script type="text/javascript">
    <!--
    function Load_deltype() {
    var deltype = document.delivery.deltype.options[document.delivery.deltype.selectedIndex].value
    var id_txt = "?deltype="
    location = id_txt + deltype
    }
    -->
  </script>';


/////////////////////////////////////////////////////////////////////////
///                                                                   ///
///                HANDLE REQUESTS FOR OPENING BASKETS                ///
///                                                                   ///
/////////////////////////////////////////////////////////////////////////

if ( $_REQUEST['action'] == 'Click to Start An Order' )
  {
    if ( !$_REQUEST['delcode_id'] )
      {
        $message2 = 'Please choose a Pickup or Delivery Location.';
      }
    elseif ( !$_REQUEST['deltype'] )
      {
        $message2 = 'Please choose Home, Work, or Pick up.';
      }
    elseif ( !$_REQUEST['payment_method'] )
      {
        $message2 = 'Please choose a Payment Method.';
      }
    if ( $_REQUEST['deltype'] && $_REQUEST['delcode_id'] && $_REQUEST['payment_method'] )
      {
        $sql4 = '
          SELECT
            delivery_id,
            member_id,
            basket_id
          FROM
            '.TABLE_BASKET_ALL.'
          WHERE
            delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"
            AND member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
        $result4 = @mysql_query($sql4, $connection) or die(mysql_error());
        $num4 = mysql_numrows($result4);
        while ($row = mysql_fetch_array($result4))
          {
            $basket_id = $row['basket_id'];
          }
//        $_SESSION['basket_id'] = $basket_id;
        if ( $num4 == 1 )
          {
            $message2 = 'This order has already been submitted. Click here to <a href="orders_current.php">edit the order</a>';
          }
        else
          {
            /* =============================================================
             * HERE WE GENERATE A NEW BASKET FOR THE MEMBER
             */

            //GET DELIVERY CHARGE DISCOUNT FOR MEMBER
            $sql5 = '
              SELECT
                mem_delch_discount
              FROM
                '.TABLE_MEMBER.'
              WHERE
                 member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
            $result5 = @mysql_query($sql5, $connection) or die("Couldn't execute query 2.");
            while ( $row = mysql_fetch_array($result5) )
              {
                $mem_delch_discount = $row['mem_delch_discount'];
              }

            // Get per-order cost parameters for this member
            $sql6 = '
              SELECT
                order_cost,
                order_cost_type
              FROM
                '.TABLE_MEMBER.'
              RIGHT JOIN '.TABLE_MEMBERSHIP_TYPES.' ON '.TABLE_MEMBER.'.membership_type_id = '.TABLE_MEMBERSHIP_TYPES.'.membership_type_id
              WHERE
                 member_id = "'.mysql_real_escape_string ($_SESSION['member_id']).'"';
            $result6 = @mysql_query($sql6, $connection) or die("Couldn't execute query 2.");
            while ( $row = mysql_fetch_array($result6) )
              {
                $order_cost = $row['order_cost'];
                $order_cost_type = $row['order_cost_type'];
              }

            $sql2 = '
              SELECT
                delcharge
              FROM
                '.TABLE_DELCODE.'
              WHERE delcode_id = "'.mysql_real_escape_string ($_REQUEST['delcode_id']).'"';
            $result2 = @mysql_query($sql2,$connection) or die("Couldn't execute query 2.");
            while ( $row = mysql_fetch_array($result2) )
              {
                $delcharge = $row['delcharge'];
              }
            if ( $mem_delch_discount == 1 )
              {
                //$delcharge = $delcharge-2.50;
                $delcharge = 0;
              }

            //GET THE COOP FEE FOR CURRENT DELIVERY
            $sqlc = '
              SELECT
                coopfee
              FROM
                '.TABLE_ORDER_CYCLES.'
              WHERE
                delivery_id = "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'"';
            $resultc = @mysql_query($sqlc,$connection) or die("Couldn't execute query coop fee.");
            while ( $row = mysql_fetch_array($resultc) )
              {
                $coopfee = $row['coopfee'];
              }

            //INSERT THE NEW BASKET INFO INTO THE DATABASE
            //TODO: MAYBE THE NEW BASKET INFO SHOULD BE ENTERED IN THE DB *BEFORE* ANNOUNCING THE MEMBER CAN START SHOPPING INCASE THE INSERT FAILS
            $sqlo = '
              INSERT INTO
                '.TABLE_BASKET_ALL.'
                  (
                    member_id,
                    delivery_id,
                    deltype,
                    delcode_id,
                    coopfee,
                    delivery_cost,
                    order_cost,
                    order_cost_type,
                    transcharge,
                    payment_method,
                    order_date
                  )
              VALUES
                (
                  "'.mysql_real_escape_string ($_SESSION['member_id']).'",
                  "'.mysql_real_escape_string (ActiveCycle::delivery_id()).'",
                  "'.mysql_real_escape_string ($_REQUEST['deltype']).'",
                  "'.mysql_real_escape_string ($_REQUEST['delcode_id']).'",
                  "'.mysql_real_escape_string ($coopfee).'",
                  "'.mysql_real_escape_string ($delcharge).'",
                  "'.mysql_real_escape_string ($order_cost).'",
                  "'.mysql_real_escape_string ($order_cost_type).'",
                  "'.mysql_real_escape_string ($transcharge).'",
                  "'.mysql_real_escape_string ($_REQUEST['payment_method']).'",
                  now()
                )';
            $resulto = @mysql_query($sqlo, $connection) or die(mysql_error());
            $basket_id = mysql_insert_id();
//            $_SESSION['basket_id'] = $basket_id;
            $order_started = 'yes';
          }
      }
  }










// CALL CHECK_MEMBERSHIP FUNCTIONS HERE









/////////////////////////////////////////////////////////////////////////
///                                                                   ///
///                  BUILD THE ORDER SELECTION FORM                   ///
///                                                                   ///
/////////////////////////////////////////////////////////////////////////

// Build the order selection form, but don't bother if membership is expired
// because we first need to deal with that first.

if ($membership_expired == false)
  {
    /* ==============================================================
     * QUERY FOR LAST ORDER DELIVERY INFORMATION
     */
    $last_basket_query = '
      SELECT
        delcode_id,
        deltype,
        payment_method
      FROM
        '.TABLE_BASKET_ALL.'
      WHERE
        member_id = '.mysql_real_escape_string ($_SESSION['member_id']).'
      ORDER BY
        delivery_id DESC
      LIMIT 1';
    $last_basket_query_results = mysql_query($last_basket_query);
    $last_basket = @mysql_fetch_array($last_basket_query_results);
    $last_delivery_id = $last_basket['delcode_id'];
    $last_delivery_type = $last_basket['deltype'];
    $last_payment_method = $last_basket['payment_method'];

    /* ==============================================================
     * HERE WE GET THE DELIVERY TYPE.  DEFAULT IS THE SAME AS
     * MEMBER'S LAST ORDER.
     */
    if($_GET['deltype']!='')
      {
        $deltype = $_GET['deltype'];
      }
    else
      {
        $deltype = $last_delivery_type;
      }
    $q = mysql_query('
      SELECT
        *
      FROM
        delivery_types');
    while ( $row = mysql_fetch_array($q) )
      {
        $selected = ($row["deltype"] == $deltype)? ' selected': '';
        $display_deltype .= '
          <option value="'.$row['deltype'].'"'.$selected.'>'.$row['deltype_title'].'</option>';
      }

    /* ================================================================
     * THIS SECTION CREATES THE SELECTABLE DROPDOWN LIST FOR MEMBERS
     * TO SELECT THEIR DELIVERY LOCATION.  IT DEFAULTS TO THE LOCATION
     * THEY USED FOR THEIR LAST ORDER.
     */


    //find all available delivery locations
    $query = '
      SELECT
        *
      FROM
        '.TABLE_DELCODE.',
        '.TABLE_DELTYPE.'
      WHERE delivery_types.deltype = "'.mysql_real_escape_string ($deltype).'"
        AND '.TABLE_DELCODE.'.deltype = '.TABLE_DELTYPE.'.deltype_group
        AND inactive = "0"
      ORDER BY
        delcode ASC'; // CHANGED from != 1 So that inactive = 2 will not show up but can still be used for member sign-ups
    $sql = mysql_query($query);

    //create the options
    while ( $row = mysql_fetch_array($sql) )
      {
      //default to no selection
      $deliver_location_selected = '';

      //if this code matches the last code, mark it selected
      if($row['delcode_id']==$last_delivery_id) {
        $deliver_location_selected = ' selected="yes"';
      }

      $display_delcode .= '
          <option value="'.$row['delcode_id'].'"'.$deliver_location_selected.'>'.$row['delcode'].'</option>';
      }

    /* ====================================================================
     * PAYMENT METHOD SELECTION
     */
    $query = '
      SELECT
        *
      FROM
        '.TABLE_PAY;
    $sql = mysql_query($query);
    while ( $row = mysql_fetch_object($sql) )
      {
        $payment_checked = '';
        if ($row->payment_method == $last_payment_method)
          {
            $payment_checked = ' checked';
          }
        $display_pay .= '
          <input type="radio" name="payment_method" value="'.$row->payment_method.'"'.$payment_checked.'>'.$row->payment_desc;
      }
  }


/////////////////////////////////////////////////////////////////////////
///                                                                   ///
///                         SEND THE OUTPUT                           ///
///                                                                   ///
/////////////////////////////////////////////////////////////////////////

// Prepare some variables

if ($membership_expired == false)
  {
    // Clobber the "same membership" markup because a current member need not
    // change their status to the same thing.  We will, however, allow them
    // to change to other types, if they want.
    $same_renewal_html = '';
  }

// Display the output content

$display .= '
<form action="" method="post" name="delivery">
<div align="center">
<table cellpadding="7" cellspacing="1" border="0" style="border: 5px solid red;margin-top:5px;" width="600">';


// Display any notification messages

if ( $message2 )
  {
    $display .= '
  <tr bgcolor="#ffaa66">
    <td colspan="2"><div style="margin:0.7em;width:95%"><div style="float:left; font-size:3em; font-weight:bold; color:#fff;width:1em; text-align:center;margin-top:-0.2em;">!</div><span style="color:#600;font-weight:bold;">'.$message2.'</span></div></td>
  </tr>';
  }


// Display the order selection section

if ($membership_expired == false)
  {
    $display .= '
  <tr>
    <th colspan="2" bgcolor="#aa0000"><font color="#ffffcc">Select from these options to begin an order</font></th>
  </tr>';
    $display .= '
  <tr bgcolor="#ffaa66">
    <td align="left"><b>1. Delivery Type:</b> </td>
    <td align="left">
      <select name="deltype" onChange="Load_deltype()">
        <option value="0">--- Select a delivery type ---</option>
        '.$display_deltype.'
      </select>
    </td>
  </tr>
  <tr bgcolor="#ffaa66">
    <td align="left"><b>2. Pickup/Delivery Locations:</b><br>&nbsp;&nbsp;&nbsp;&nbsp;Click here for <a href="../locations.php" target="_blank">more details</a>.</td>
    <td align="left">
      <select name="delcode_id">
        <option value="">-- Choose a location ---</option>
        '.$display_delcode.'
      </select>
    </td>
  </tr>
  <tr bgcolor="#ffaa66">
    <td align="left"><b>3. Payment Method:</b></td>
    <td align="left">'.$display_pay.'</td>
  </tr>
  <tr bgcolor="#ffaa66">
    <td colspan="2" align="right">';
// NOT SURE WHAT THIS CODE IS SUPPOSED TO BE DOING HERE
// We shouldn't be here if there is already a basket in existence. -ROYG
//     if ( $basket_id )
//       {
//         $display .= '
//       <input type="hidden" name="basket_id" value="'.$basket_id.'">';
//       }
    $display .= '
      <input name="action" type="submit" value="Click to Start An Order">
    </td>
  </tr>';
  }


// Display the membership status section

$display .= '
  <tr>
    <th colspan="2" bgcolor="#aa0000"><font color="#ffffcc">Membership Status <span id="member_details_ctrl" style="display:'.($membership_expired == false? '' : 'none').'; cursor:pointer;cursor:hand;" onClick=\'{document.getElementById("member_details").style.display="";document.getElementById("member_details_ctrl").style.display="none";}\'>[click for details]</span></font></th>
  </tr>
  <tr bgcolor="#ffaa66">
    <td colspan="2">
      <table width="100%" cellspacing="1" border="0" id="member_details" style="display:'.($membership_expired == false? 'none' : '').';">
        <tr bgcolor="#ffaa66" style="border-bottom:1px solid #fff;">
          <td style="border-bottom:1px solid #fff;">'.$membership_info_html.'<br><br>
          '.$same_renewal_html.'</td>
        </tr>';
if ($changed_renewal_html != '')
  {
    $display .= '
        <tr bgcolor="#ffaa66">
          <td style="border-bottom:1px solid #fff;">'.$changed_renewal_html_intro.'<br>
          '.$changed_renewal_html.'</td>
        </tr>';
  }
$display .= '
        <tr>
          <td align="right">
            <input name="action" type="submit" value="Renew or Change Membership">
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</div>
</form>';

if ($order_started == 'yes')
  {
    // Clobber the display if the order just got started.
    $display = '';
  }