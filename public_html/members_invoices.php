<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier,member_admin');


$sql = '
  SELECT
    '.NEW_TABLE_BASKETS.'.delivery_id,
    '.TABLE_ORDER_CYCLES.'.*,
    1 AS finalized,
    '.NEW_TABLE_BASKETS.'.basket_id,
    '.TABLE_MEMBER.'.member_id,
    '.TABLE_MEMBER.'.first_name,
    '.TABLE_MEMBER.'.last_name,
    '.TABLE_MEMBER.'.first_name_2,
    '.TABLE_MEMBER.'.last_name_2,
    '.TABLE_MEMBER.'.business_name,
    '.TABLE_MEMBER.'.preferred_name
  FROM
    '.NEW_TABLE_BASKETS.',
    '.TABLE_ORDER_CYCLES.',
    '.TABLE_MEMBER.'
    WHERE
      '.NEW_TABLE_BASKETS.'.member_id = "'.mysql_real_escape_string ($_GET['member_id']).'"
      AND '.TABLE_MEMBER.'.member_id = "'.mysql_real_escape_string ($_GET['member_id']).'"
      AND '.NEW_TABLE_BASKETS.'.delivery_id = '.TABLE_ORDER_CYCLES.'.delivery_id
    ORDER BY '.NEW_TABLE_BASKETS.'.delivery_id DESC';
$rs = @mysql_query($sql, $connection) or die(debug_print ("ERROR: 785033 ", array ($sql,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
$num = mysql_numrows($rs);
while ( $row = mysql_fetch_array($rs) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];
    $basket_id = $row['basket_id'];
    $finalized = $row['finalized'];
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $first_name_2 = $row['first_name_2'];
    $last_name_2 = $row['last_name_2'];
    $business_name = $row['business_name'];
    $preferred_name = $row['preferred_name'];
    include("../func/convert_delivery_date.php");
    if ( $finalized )
      {
        $display .='<li> <a href="invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$_GET['member_id'].'">'.$delivery_date.'</a><br>';
      }
    else
      {
        $display .='<li> <a href="customer_invoice.php?delivery_id='.$delivery_id.'&basket_id='.$basket_id.'&member_id='.$_GET['member_id'].'">'.$delivery_date.'</a> (unfinalized)<br>';
      }
  }
?>
<?php include("template_header.php");?>
<div align="center">
  <table width="60%">
    <tr>
      <td align="left">
<?php
if ( $num )
  {
    echo '
      <h3>Previous Orders for '.$preferred_name.'</h3>
      <ul>
        '.$display.'
      </ul>
      ';
  }
else
  {
    echo "<b>No previous orders on record.</b>";
  }
?>
      </td>
    </tr>
  </table>
</div>
<?php include("template_footer.php");?>
</body>
</html>