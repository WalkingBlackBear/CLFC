<?php 
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


include("../func/gen_invoice.php");

if ( $_REQUEST['delivery_id'] )
  {
    $delivery_id = preg_replace("/[^0-9]/","",$_REQUEST['delivery_id']);
    $ms = preg_replace("/[^0-9]/","",$_REQUEST['ms']);
    $mf = preg_replace("/[^0-9]/","",$_REQUEST['mf']);
  }
else
  {
    $delivery_id = 0;
    $ms = 0;
    $mf = 0;
  }
include("template_header.php");
if ( $delivery_id > 0 && $ms > 0 && $mf > 0 )
  {
    $sql = '
      SELECT
        basket_id,
        member_id 
      FROM
        '.TABLE_BASKET_ALL.' 
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($delivery_id).'" 
        AND member_id >= "'.mysql_real_escape_string ($ms).'"
        AND member_id <= "'.mysql_real_escape_string ($mf).'"';
    $rs = @mysql_query($sql, $connection) or die(mysql_error());
    $num_orders = mysql_numrows($rs);
    while ( $row = mysql_fetch_array($rs) )
      { 
        geninvoice($row['member_id'],$row['basket_id'],$delivery_id,"adminfinalize");
      }
    echo '<div align="center">'.$num_orders.' invoices finalized.</div>';
  }
else
  {
    echo '<div align="center">No invoices finalized.</div>';
  }
include("template_footer.php");
?>
</body>
</html>
