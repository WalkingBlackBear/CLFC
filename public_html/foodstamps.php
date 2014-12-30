<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


$date_today = date("F j, Y");

// JPF: register_globals $fs

$affected_products = array ();

if ( $_REQUEST['update'] == "yes")
  {
    $affected_products = explode ('|', $_REQUEST['affected_products']);
    while ($product_id = array_pop ($affected_products))
      {
        $sqlu = '
          UPDATE
            '.TABLE_PRODUCT_PREP.'
          SET
            retail_staple = "'.mysql_real_escape_string ($_REQUEST['retail_staple_'.$product_id]).'",
            staple_type = "'.mysql_real_escape_string ($_REQUEST['staple_type_'.$product_id]).'"
          WHERE
            product_id = "'.$product_id.'"';
          $result = @mysql_query($sqlu, $connection) or die('<br><br>You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a><br><br><b>Error:</b> Updating ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
      }
  }

$sql = '
  SELECT
    '.TABLE_PRODUCT_PREP.'.product_id,
    '.TABLE_PRODUCT_PREP.'.product_name,
    '.TABLE_PRODUCT_PREP.'.retail_staple,
    '.TABLE_PRODUCT_PREP.'.staple_type,
    '.TABLE_PRODUCT_PREP.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.subcategory_id,
    '.TABLE_SUBCATEGORY.'.subcategory_name
  FROM
    '.TABLE_PRODUCT_PREP.',
    '.TABLE_SUBCATEGORY.'
  WHERE
    '.TABLE_PRODUCT_PREP.'.subcategory_id = '.TABLE_SUBCATEGORY.'.subcategory_id
    AND '.TABLE_PRODUCT_PREP.'.retail_staple = "'.mysql_real_escape_string ($_REQUEST['fs']).'"
  GROUP BY
    product_id
  ORDER BY
    retail_staple DESC,
    subcategory_name ASC,
    product_name ASC';
$result = @mysql_query($sql, $connection) or die("".mysql_error()."");

$num = mysql_numrows($result);

while ( $row = mysql_fetch_array($result) )
  {
    $subcategory_name = $row['subcategory_name'];
    $product_id = $row['product_id'];
    $product_name = $row['product_name'];
    $retail_staple = $row['retail_staple'];
    $staple_type = $row['staple_type'];

    $chks1 = "";
    $chks2 = "";
    $chks3 = "";

    if ( $retail_staple == "1" )
      {
        $chks1 = " checked";
      }
    elseif ( $retail_staple == "2" )
      {
        $chks2 = " checked";
      }
    elseif ( $retail_staple == "3" )
      {
        $chks3 = " checked";
      }

    $chktm = "";
    $chktp = "";
    $chkte = "";
    $type_color="#666";

    if ( $staple_type == "m" )
      {
        $chktm = " selected";
        $type_color="#369";
      }
    elseif ( $staple_type == "p" )
      {
        $chktp = " selected";
        $type_color="#936";
      }
    elseif ( $staple_type == "e" )
      {
        $chkte = " selected";
        $type_color="#693";
      }



    $display .= '
      <tr>
        <td id="'.$product_id.'">'.$font.' <b>#'.$product_id.'</b></td>
        <td>'.$font.' <b>'.$subcategory_name.': '.$product_name.'</b><br></td>
        <td>'.$font.'
            <input type="radio" name="retail_staple_'.$product_id.'" value="2"'.$chks2.'>RFnoS
            <input type="radio" name="retail_staple_'.$product_id.'" value="3"'.$chks3.'>Staple
            <input type="radio" name="retail_staple_'.$product_id.'" value="1"'.$chks1.'>NF ';

    if ( $retail_staple == "3" )
      {
        $display .= '
            <select style="color:'.$type_color.';font-weight:bold;" name=staple_type_'.$product_id.'>
              <option value="">Select Type</option>
              <option value="m" style="color:#369;font-weight:bold;"'.$chktm.'>Meat</option>
              <option value="p" style="color:#936;font-weight:bold;"'.$chktp.'>Produce</option>
              <option value="e" style="color:#693;font-weight:bold;"'.$chkte.'>Eggs</option>
            </select>
          ';
      }

    array_push ($affected_products, $product_id);
    $display .= '
        </td>
        <td>
            <input type="hidden" name="product_id" value="'.$product_id.'">
            <input name="where" type="submit" value="Update">
        </td>
      </tr>';
  }

$content = '
<table width="100%">
  <tr><td align="left">

<h3>Food Stamp Designations</h3>

'.$num.' Entries Found
<br><br>
Go to these pages:
<a href="'.$_SERVER['PHP_SELF'].'?fs=3">Staples</a> |
<a href="'.$_SERVER['PHP_SELF'].'?fs=2">Retail Food but not Staples</a> |
<a href="'.$_SERVER['PHP_SELF'].'?fs=1">Non-food</a> |
<a href="'.$_SERVER['PHP_SELF'].'?fs=0">Unassigned</a>
<br>
<br>

<table width="100%" border="1" cellpadding="5" cellspacing="0">
  <tr>
    <th align="center" bgcolor="#770000">'.$font.'<font color="#FFFFFF">ID</font></th>
    <th align="center" bgcolor="#770000" width="200">'.$font.'<font color="#FFFFFF">Product Name</font></th>
    <th align="center" bgcolor="#770000">'.$font.'<font color="#FFFFFF">Foodstamps</font></th>
    <th align="center" bgcolor="#770000">'.$font.'<font color="#FFFFFF">Update</font></th>
  </tr>

  <form action="'.$_SERVER['PHP_SELF'].'" method="post">
  '.$display.'
  <input type="hidden" name="update" value="yes">
  <input type="hidden" name="fs" value="'.$_REQUEST['fs'].'">
  <input type="hidden" name="affected_products" value="'.implode ('|', $affected_products).'">
  </form>

</table>


  </td></tr>
</table>';

$page_title_html = '<span class="title">Food Stamps</span>';
$page_subtitle_html = '<span class="subtitle">Designations</span>';
$page_title = 'Food Stamps: Designations';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
