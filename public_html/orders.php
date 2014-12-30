<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,cashier,site_admin');

// Set variables that used to be global
$basket_id = $_REQUEST['basket_id'];
$member_id = $_REQUEST['member_id'];
$delivery_id = $_REQUEST['delivery_id'];
$product_id = $_REQUEST['product_id'];
$customer_notes_to_producer = $_REQUEST['customer_notes_to_producer'];
$quantity = $_REQUEST['quantity'];

// Get the delivery_id from the basket_id
if ($basket_id)
  {
    $query = '
      SELECT
        delivery_id,
        preferred_name
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
      WHERE
        basket_id = '.mysql_real_escape_string ($basket_id);
    $result = @mysql_query($query, $connection) or die(mysql_error());
    if ( $row = mysql_fetch_array($result) )
      {
        $delivery_id = $row['delivery_id'];
        $preferred_name = $row['preferred_name'];
      }
  }

$fontface="arial";
include("../func/add_prod.php");
include("../func/mem_edit_invoice_admin.php");

$content_orders = '
<div align="center">
<table cellpadding="4" cellspacing="0" bgcolor="#FFFFFF" border="0" width="695">
  <tr>
    <td bgcolor="#FFFFFF" colspan="2" align="left">
      <font color="#770000">'.$message.'</font>
    </td>
  </tr>
  <tr>
    <th valign="bottom" align="left" bgcolor="#AEDE86"><font face="'.$fontface.'">
      <form name="order" action="orders.php" method="post">
      <b>Entering Orders: Basket # '.$basket_id.', Member # '.$member_id.'</b>
    </th>
    <th></th>
    <th valign="bottom" align="right" bgcolor="#AEDE86"><font face="'.$fontface.'">
      [ <a href="orders_list.php?delivery_id='.$delivery_id.'#'.$basket_id.'">Edit Other Orders</a>
      | <a href="logout.php">Logout</a> ]
    </th>
  </tr>
  <tr>
    <td valign="top" bgcolor="#DDDDDD">
      <table cellspacing="0" cellpadding="0" border="0">
        <tr align="center">
          <td align="right">'.$font.'# <input type="text" name="product_id" size=5 maxlength="6">&nbsp;&nbsp;</td>
          <td align="left">'.$font.'<b>Product ID</td>
          <td align="center">'.$font.'<input type="text" name="quantity" value="1" size=3 maxlength="4"> <b>Quantity</b></td>
        </tr>
        <tr bgcolor="#DDDDDD">
          <td colspan="3" align="left" valign="top">
            <table cellpadding=2 cellspacing=0 border=0>
              <tr>
                <td valign="top" align="right">'.$font.'<b>Notes to Producer about this product:</b></td>
                <td align="left">
                  '.$font.'
                  <textarea name="customer_notes_to_producer" cols="32" rows="2"></textarea>
                  <input type="hidden" name="yp" value="ds">
                  <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
                  <input type="hidden" name="member_id" value="'.$member_id.'">
                  <input type="hidden" name="basket_id" value="'.$basket_id.'">
                  <input name="where" type="submit" value="Add this Product to the Order">
                  </form>
                  <script language=javascript> document.order.product_id.focus(); </script>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
    <td> </td>
    <td align="center" bgcolor="#ADB6C6" valign="top">
      '.$font.'<br>
      <b>Current SubTotal = $'.number_format($total, 2).'</b>*<br>
      <a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">View Invoice</a>
    </td>
  </tr>
  <tr>
    <td colspan="3">'.$font.$display_page.'<br>
      '.$message3.'
  <tr>
    <td colspan="2" valign="top" align="left">
      '.$font.'
      [ To remove an item from your shopping cart,<br>
      &nbsp;&nbsp;enter the number 0 as the quantity. ]
      <br><br>
      * Subtotal doesn&#146;t include fees or items needing weights to calculate price.
    </td>
    <td bgcolor="#ADB6C6" valign="top" align="center">
      '.$font.'
      <br>
      <b>Current SubTotal = $'.number_format($total, 2).'</b>*<br>
      <a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$member_id.'">View Invoice</a>
      ____________________________
    </td>
  </tr>
</table>
<div align="center">
  [ <a href="orders_list.php?delivery_id='.$delivery_id.'#'.$basket_id.'">Edit Other Member Orders</a> ]
</div>';

$page_title_html = '<span class="title">'.$preferred_name.'</span>';
$page_subtitle_html = '<span class="subtitle">Edit Customer Basket</span>';
$page_title = strip_tags ($preferred_name).': Edit Customer Basket';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_orders.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
