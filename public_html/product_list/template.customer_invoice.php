<?php

/*******************************************************************************

NOTES ON USING THIS TEMPLATE FILE...

The heredoc convention is used to simplify quoting.
The noteworthy point to remember is to escape the '$' in
variable names.  But functions pass through as expected.

The short php if-else format is also useful in this context
for inline display (or not) of content elements:
([condition] ? [true] : [false])

All variables in this file are loaded at include-time and interpreted later
so there is no required ordering of the assignments.

All system constants from the configuration file are available to this template




********************************************************************************
Model for the overall product list display might look something like this:

 -- OVERALL PRODUCT LIST ----------------
|                                        |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|     -- PRODUCT HEADING SECTION ---     |
|    |                              |    |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     - PRODUCT SUBHEADING SECTION -     |
|    |                              |    |
|     -- PRODUCT LISTING SECTION ---     |
|    |                              |    |
|    |                              |    |
|     ------------------------------     |
|     ----- NAVIGATION SECTION -----     |
|    |                              |    |
|     ------------------------------     |
|                                        |
 ----------------------------------------

*/

/********************** MISC MARKUP AND CALCULATIONS *************************/

function wholesale_text_html()
  { return
    '<br><br><center style="color:#f00;letter-spacing:5px;">** WHOLESALE DISCOUNTED ITEM **</center>';
  };

function no_product_message()
  { return
    '<td align="center" colspan="7">
      <br>
      <br>
      <br>
      <br>
      EMPTY INVOICE
      <br>
      Nothing ordered
      <br>
      <br>
      <br>
    </td>';
  };

// RANDOM_WEIGHT_DISPLAY_CALC
function random_weight_display_calc($data)
  { return
    ($data['random_weight'] == 1 ?
    'You will be billed for exact '.$row['meat_weight_type'].' weight ('.
    ($data['minimum_weight'] == $data['maximum_weight'] ?
    $data['minimum_weight'].' '.Inflect::pluralize_if ($data['minimum_weight'], $data['pricing_unit'])
    :
    'between '.$data['minimum_weight'].' and '.$data['maximum_weight'].' '.Inflect::pluralize ($data['pricing_unit'])).')'
    :
    '');
  };

// TOTAL_DISPLAY_CALC
function total_display_calc($data)
  {
    // Random weight w/o weight gets a special note
    if ($data['random_weight'] == 1 && $data['total_weight'] == 0) $total_display = '
      [pending]';
    elseif ($data['unit_price']  != 0) $total_display .= '
      <span id="customer_adjusted_cost'.$data['bpid'].'">$&nbsp;'.number_format($data['customer_display_cost'], 2).'</span>';
    // If there is content so far, then add a newline
    if ($total_display && $data['extra_charge'] != 0) $total_display .= '
      <br>';
    if ($data['extra_charge'] != 0) $total_display .= '
      <span id="extra_charge'.$data['bpid'].'">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format($data['basket_quantity'] * abs($data['extra_charge']), 2).'</span>';
    // Now clobber everything if this is out-of-stock
    if ($data['out_of_stock'] == $data['basket_quantity']) $total_display = '
      <span id="customer_adjusted_cost'.$data['bpid'].'">$&nbsp;0.00</span>';
    return $total_display;
  };

// PRICING_DISPLAY_CALC
function pricing_display_calc($data)
  { return
    ($data['display_retail_price'] && $data['display_unit_retail_price'] ?
    '<span class="retail">'.($_GET['type'] == 'producer_list' ? 'Retail: ' : '').'$'.number_format($data['display_unit_retail_price'], 2).'/'.Inflect::singularize ($data['pricing_unit']).'</span><br>'
    : '' ).
    ($data['display_wholesale_price'] && $data['display_unit_wholesale_price'] ?
    '<span class="whsle">'.($_GET['type'] == 'producer_list' ? 'Whsle: ' : '').'$'.number_format($data['display_unit_wholesale_price'], 2).'/'.Inflect::singularize ($data['pricing_unit']).'</span><br>'
    : '').
    ($data['extra_charge'] != 0 ?
    '<span class="extra">'.($data['extra_charge'] > 0 ? '+' : '-').'&nbsp;$&nbsp;'.number_format (abs ($data['extra_charge']), 2).'/'.Inflect::singularize ($data['ordering_unit']).'</span><br>'
    : '');
  };

// ORDERING_UNIT_DISPLAY_CALC
function ordering_unit_display_calc($data)
  { return
    ''; // Not used... ignore
  };

// INVENTORY_DISPLAY_CALC
function inventory_display_calc($data)
  { 
    // Use this for the out-of-stock checkmark
    if ($data['out_of_stock'] == 0)
      $out_checkmark = ''; // Fully in stock
    elseif ($data['out_of_stock'] == $data['basket_quantity'])
      $out_checkmark = '<img alt="out of stock" src="grfx/out.png">'; // Fully out of stock
    else
      $out_checkmark = '<img alt="out of stock" src="grfx/part.png">'; // Partly filled
    return $out_checkmark;
  };

// IMAGE_DISPLAY_CALC
function image_display_calc($data)
  { return
    ''; // Not used... ignore
  };

// PRODTYPE_DISPLAY_CALC
function prodtype_display_calc($data)
  { return
    ''; // Not used... ignore
  };

// BUSINESS_NAME_DISPLAY_CALC
function business_name_display_calc($data)
  { return
    '<font face="arial" color="#770000" size="-1"><b>'.$data['producer_name'].'</b></font>';
  };

// ORDERING_LINK_CALC
function row_activity_link_calc($data, $pager)
  { return
    ''; // Invoices are not interactive
  };

// PAGER_DISPLAY_CALC
function pager_display_calc($data)
  { return
    '<a href="'.$_SERVER['PHP_SELF'].'?'.
    ($_GET['type'] ? 'type='.$_GET['type'] : '').
    ($_GET['producer_id'] ? '&producer_id='.$_GET['producer_id'] : '').
    ($_GET['category_id'] ? '&category_id='.$_GET['category_id'] : '').
    ($_GET['delivery_id'] ? '&delivery_id='.$_GET['delivery_id'] : '').
    ($_GET['subcat_id'] ? '&subcat_id='.$_GET['subcat_id'] : '').
    ($_GET['query'] ? '&query='.$_GET['query'] : '').
    ($_GET['a'] ? '&a='.$_GET['a'] : '').
    ($data['page'] ? '&page='.$data['page'] : '').
    '" class="'.($data['this_page_true'] ? 'current' : '').($data['page'] == 1 ? ' first' : '').($data['page'] == $data['last_page'] ? ' last' : '').'">&nbsp;'.$data['page'].'&nbsp;</a>';
  };

/************************* PAGER NAVIGATION SECTION ***************************/

function pager_navigation($data)
  { return
    ($data['last_page'] > 1 ?
    '<div class="pager"><span class="pager_title">Page: </span>'.$data['display'].'</div>
    <div class="clear"></div>'
    : '');
  };

/*********************** OPEN BEGINNING OF PRODUCT LIST *************************/

function open_list_top($data)
  { return
    '     <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align="left" valign="top"><!-- FOOTER LEFT "'.(strpos ($data['auth_type'], 'institution') !== false ? $data['business_name'] : '').$data['last_name'].', '.$data['first_name'].'" -->
                <font size="+2"><b>'.$data['preferred_name'].' '.(strpos ($data['auth_type'], 'institution') !== false ? $data['business_name'].'<br></font>(attn: '.$data['first_name'].' '.$data['last_name'].')<font>' : '').'</b></font>
              </td>
              <td valign="top" align="right">
                <table border="0">
                  <tr>
                    <td align="center">
                      <img src="images/CLFClogo_280x80.png" alt="logo" width="250" height="71">
                    </td>
                  </tr>
                  <tr>
                    <td align="center">
                      <font size="-2">'.SITE_CONTACT_INFO.'</font>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                  <tr>
                    <td align="left">
                      <font size="+2">'.$data['member_id'].'-'.$data['delcode_id'].' ('.$data['delcode'].')</font>
                    </td>
                    <td align="right">
                      <font size="+2">'.date ("F j, Y", strtotime ($data['delivery_date'])).'</font>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td colspan="2" height="20"><img src="'.BASE_URL.PATH.'grfx/black_pixel.gif" width="100%" height="1" alt="divider"></td>
            </tr>
            <tr>
              <td valign="top"><strong>Customer info</strong>'.
($data['deltype'] == 'H' || $data['deltype'] == 'P' ? '
                (home):<br><br>'.$data['address_line1'].''.
($data['address_line2'] != '' ? '
                <br>'.$data['address_line2'].''
: '').'
                <br>'.$data['city'].', '.$data['state'].', '.$data['zip'].'<br>' :
'').
($data['deltype'] == 'W' ? '
                (work):<br><br>'.$data['work_address_line1'].''.
($data['work_address_line2'] != '' ? '
                <br>'.$data['work_address_line2'].''
: '').'
                <br>'.$data['work_city'].', '.$data['work_state'].', '.$data['work_zip'].'<br>'
: '').
($data['email_address'] != '' ? '
                <br><a href="mailto:'.$data['email_address'].'">'.$data['email_address'].'</a>'
: '').
($data['email_address_2'] != '' ? '
                <br><a href="mailto:'.$data['email_address_2'].'">'.$data['email_address_2'].'</a>'
: '').
($data['home_phone'] != '' ? '
                <br>'.$data['home_phone'] .' (home)'
: '').
($data['work_phone'] != '' ? '
                <br>'.$data['work_phone'] .' (work)'
: '').
($data['mobile_phone'] != '' ? '
                <br>'.$data['mobile_phone'] .' (mobile)'
: '').
($data['fax'] != '' ? '
                <br>'.$data['fax'] .' (fax)'
: '').'<br><br>
              </td>
              <td valign="top"><strong>Delivery/pickup details:</strong>
                <dl>
                  <dt><font face="Times New Roman">'.$data['delcode'].'</font></dt>
                  <dd><pre><font face="Times New Roman">'.$data['deldesc'].'</font></pre></dd>
                </dl>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                '.
($data['msg_all'] != '' ? '
                <font color="#990000" size="-1">'.$data['msg_all'].'  E-mail any problems with your order to <a href="mailto:'.PROBLEMS_EMAIL.'">'.PROBLEMS_EMAIL.'</a><br></font>'
: '').
($data['msg_unique'] != '' ? '
                <br><font color="#990000" size="-1">'.$data['msg_unique'].'<br></font>'
: '').'
              </td>
            </tr>
          </table>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">'.
($data['number_of_products'] > 0 ? '
          <tr>
            <td colspan="7"><br></td>
          </tr>
          <tr>
            <th colspan="2" valign="bottom" bgcolor="#444444" width="75"><font color="#ffffff" size="-1">#</font></th>
            <th valign="bottom" bgcolor="#444444" align="left"><font color="#ffffff" size="-1">Product Name</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Price</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Quantity</font></th>
            <th valign="bottom" bgcolor="#444444"><font color="#ffffff" size="-1">Weight</font></th>
            <th valign="bottom" bgcolor="#444444" align=right width="8%"><font color="#ffffff" size="-1">Amount</font></th>
          </tr>'
: '
          <tr>
            <td colspan="7" align="center"><br><br><br><br>EMPTY INVOICE<br>Nothing ordered<br><br><br></td>
          </tr>');
  };

function close_list_bottom(&$data)
  { return
($data['adjustments_exist'] != '' ? '
          <tr align="left">
            <td></td>
            <td>____</td>
            <td colspan="5"><br><font face="arial" color="#770000" size="-1"><b>Adjustments</b></font></td>
          </tr>
          '.$data['adjustment_display_output']
: '').'
<!-- NEED 7 -->
          <tr>
            <td colspan="6" align="right"><br>'.$data['font'].'<b>SUBTOTAL</b></font></td>
            <td align="right" width="8%">'.$data['font'].'<br><b>$&nbsp;'.number_format(round ($data['total_basket_cost'] + $data['taxed_adjustment_cost'], 2), 2).'</b></font></td>
          </tr>'.
($data['delivery_id'] >= DELIVERY_NO_PAYPAL && SHOW_ACTUAL_PRICE != true ? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>+ '.$data['coop_markup_display'].'% Fee</b></font></td>
            <td align="right" width="8%">'.$data['font'].'<b>$&nbsp;'.number_format($data['coop_fee'], 2).'</b></font></td>
          </tr>'
: '').
($data['total_tax'] != 0 ? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>Sales tax on taxable sales'.$data['taxable_product_flag'].'</b></font></td>
            <td align="right" width="8%">'.$data['font'].'<b>$ '.number_format($data['total_tax'], 2).'</b></font></td>
          </tr>'
: '').
($data['exempt_adjustment_cost'] != 0 ? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>Non-taxed Adjustments</b></font></td>
            <td  align="right" width="8%">'.$data['font'].'<b>$&nbsp;'.number_format($data['exempt_adjustment_cost'], 2).'</b></font></td>
          </tr>'
: '').
($data['delivery_id'] < DELIVERY_NO_PAYPAL && SHOW_ACTUAL_PRICE != true ? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>'.($data['delivery_id'] < DELIVERY_NO_PAYPAL ? 'Shipping and Handling' : '').(SHOW_ACTUAL_PRICE != true ? '+ '.number_format($data['coop_markup'] * 100, 0).'% Fee' : '').'</b></font></td>
            <td  align="right" width="8%">'.$data['font'].'<b>$&nbsp;'.number_format($data['customer_fee_total'], 2).'</b></font></td>
          </tr>'
: '').
($data['special_order'] != "1" && $data['delcharge'] != 0? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>Extra Charge for Delivery </b></font></td>
            <td  align="right" width="8%">'.$data['font'].'<b>$&nbsp;'.number_format($data['delcharge'], 2).'</b></font></td>
          </tr>'
: '').'
          <tr>
            <td colspan="6" height="1"></td>
            <td height="1" background="'.BASE_URL.PATH.'grfx/black_pixel.gif"></td>
          </tr>
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>Invoice&nbsp;Total </b></font></td>
            <td align="right" width="8%">'.$data['font'].'<b>$&nbsp;'.number_format($data['subtotal_2'], 2).'</b></font></td>
          </tr>'.
(round($data['membership_cost'], 2) != 0 || $data['order_cost'] != 0 ? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>Membership/Fees</b></font></td>
            <td  align="right" width="8%">'.$data['font'].'<b>$ '.number_format($data['membership_cost'] + $data['order_cost'], 2).'</b></font></td>
          </tr>'
: '').
($data['previous_balance'] != 0 ? '
          <tr>
            <td colspan="6" align="right">'.$data['font'].'<b>(Our records show a previous '.($data['previous_balance'] < 0 ? 'Credit' : 'Balance Due').'</b></font></td>
            <td align="right" width="8%">'.$data['font'].'<b>$&nbsp;'.number_format($data['previous_balance'], 2).')</b></font></td>
          </tr>'
: '').'
          <tr>
            <td colspan="6" height="1"></td>
            <td height="1" background="'.BASE_URL.PATH.'grfx/black_pixel.gif"></td>
          </tr>
          <tr>
            <td colspan="6" align="right"><font size="+2">PLEASE PAY'.($data['payment_method'] == 'P' ? 'PAL' : '').' THIS AMOUNT:</font></td>
            <td align="right"><font size="+2">'.($data['unfilled_random_weight'] ? '<font size="-1">'.$data['display_weight_pending_text'].'</font>' : '$&nbsp;'.number_format ($data['pay_this_amount/*'] - $data['previous_balance*/'], 2)).'</font></td>
          </tr>'.
(round ($data['most_recent_payment_amount'], 2) > 0 ? '
          <tr>
            <td colspan="6" align="right">Thank you for your most recent payment of $&nbsp;'.number_format ($data['most_recent_payment_amount'], 2).'.</td>
            <td></td>
          </tr>'
: '').'
        </table>';
  };



/************************** OPEN MAJOR DIVISION ****************************/

// For invoices, the major division is a change in the producer
function major_division_open($data, $major_division = NULL)
  {
    if ($data['number_of_products'] > 0)
      {
        switch ($major_division)
          {
            // Majory division on category
            case 'category_id':
            case 'category_name':
            case 'subcategory_id':
            case 'subcategory_name':
              $header = '';
              break;
            // Majory division on producer
            case 'producer_id':
            case 'producer_name':
              $header = '
          <tr>
            <td colspan="2" width="75"><!-- <img src="'.BASE_URL.PATH.'grfx/black_pixel.gif" width="70" height="1" alt="divider"> --></td>
            <td colspan="5">'.$data['business_name_display'].'</td>
          </tr>';
              break;
            // Otherwise...
              $header = '
              ';
              break;
          }
      }
    return $header;
  };

function major_division_close ()
  { return
    '
          <tr align="left">
            <td colspan="7" height="10"></td>
          </tr>';
  };

/************************** OPEN MINOR DIVISION ****************************/

function minor_division_open($data, $minor_division = NULL)
  {
    switch ($minor_division)
      {
        // Majory division on category
        case 'category_id':
        case 'category_name':
        case 'subcategory_id':
        case 'subcategory_name':
          $header = '';
          break;
        // Majory division on producer
        case 'producer_id':
        case 'producer_name':
          $header = '';
          break;
        // Majory division on producer
        case 'product_id':
        case 'product_name':
          $header = '';
          break;
        // Otherwise...
          $header = '
          ';
          break;
      }
    return $header;
  };

function minor_division_close ()
  { return
    '';
  };

/************************* LISTING FOR PRODUCT SORTS **************************/

function show_listing_row(&$data, $row_type)
  {
    if ($data['text_key'] == 'customer fee')
      {
        $data['customer_fee_total'] = $data['customer_fee_total'] + $data['amount'];
      }
    $weight_display = 
    $data['basket_quantity'].' '.Inflect::pluralize_if ($data['basket_quantity'], $data['ordering_unit']).
      ($data['random_weight'] ? '</td><td align="center" valign="top"><font face="arial" size="-1">'.
        ($data['total_weight'] ? $data['total_weight'].' '.Inflect::pluralize_if ($data['total_weight'], $data['pricing_unit'])
        :
        '(wt.&nbsp;pending)').'</td>'
      : '</td><td></td>');
    return '
          <tr align="center">
            <td  width="40" align="right" valign="top"><font face="arial" size="-1">'.$data['inventory_display'].$data['customer_fee_total'].'</font></td>
            <td  width="50" align="right" valign="top"><font face="arial" size="-1">'.$data['product_id'].'&nbsp;&nbsp;</font></td>
            <td align="left" valign="top">
              <font face="arial" size="-1">'.$data['product_name'].'</font>'.
($data['customer_message'] != '' ? '<br />
            <font color="#6666aa" face="arial" size="-1"><b>Customer Note: </b>'.$data['customer_message'].'</font>'
: '').'
            </td>
            <td align="center" valign="top"><font face="arial" size="-1">'.$data['pricing_display'].'</font></td>
            <td align="center" valign="top"><font face="arial" size="-1">'.$weight_display.'</font></td>
            <td width="13" align="right" valign="top"><font face="arial" size="-1"><b>'.$data['total_display'].'</b></font></td>
          </tr>';
  };

?>