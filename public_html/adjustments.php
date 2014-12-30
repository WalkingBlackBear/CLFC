<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,cashier');


$message = '';
$retail_multiplier = 1;
$wholesale_multiplier = 1;

if ( $_REQUEST['delete_adjustment'] == 'yes' )
  {

    if ( $_REQUEST['transaction_id_passed'] )
      {
        $sql = mysql_query('
          SELECT
            transaction_type,
            transaction_name,
            transaction_amount,
            transaction_producer_id,
            transaction_member_id,
            transaction_basket_id,
            transaction_taxed
          FROM
            transactions
          WHERE
            transaction_id = "'.mysql_real_escape_string ($_REQUEST['transaction_id_passed']).'"
          LIMIT 1');
        $row = mysql_fetch_array($sql);
        if ( strpos($row['transaction_amount'],"-") !== false )
          {
            $amount = preg_replace("/[^0-9\.]/","",$row['transaction_amount']);
          }
        else
          {
            $amount = '-'.$row['transaction_amount'];
          }
        $sqldelete = '
          INSERT INTO
            transactions
              (
                transaction_type,
                transaction_name,
                transaction_amount,
                transaction_user,
                transaction_producer_id,
                transaction_member_id,
                transaction_basket_id,
                transaction_delivery_id,
                transaction_taxed,
                transaction_comments,
                transaction_timestamp
              )
          VALUES
            (
              "'.mysql_real_escape_string ($row['transaction_type']).'",
              "'.mysql_real_escape_string ($row['transaction_name']).'",
              "'.mysql_real_escape_string ($amount).'",
              "'.mysql_real_escape_string ($_SESSION['member_id']).'",
              "'.mysql_real_escape_string ($row['transaction_producer_id']).'",
              "'.mysql_real_escape_string ($row['transaction_member_id']).'",
              "'.mysql_real_escape_string ($row['transaction_basket_id']).'",
              "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'",
              "'.mysql_real_escape_string ($row['transaction_taxed']).'",
              "Adjustment Zeroed Out",
              now()
            )';
        //echo $sqldelete."<br/>";
        $resultdelete = @mysql_query($sqldelete, $connection) or die(mysql_error());
        $message = ': <font color="#FFFFFF">Adjustment Zeroed Out</font>';
      }
  }
elseif ( $_REQUEST['adjustment_submitted'] == "yep" && $_REQUEST['adjt_id'] && (($_REQUEST['basket_id'] && $_REQUEST['adj_type'] == "customer") || ($_REQUEST['producer_id'] && $_REQUEST['adj_type'] == "producer")) )
  {
    $sql_select = '
      SELECT
        ttype_creditdebit,
        ttype_name,
        ttype_taxed
      FROM
        transactions_types
      WHERE
        ttype_id = "'.mysql_real_escape_string ($_REQUEST['adjt_id']).'"
      LIMIT 1';
    $result_select = @mysql_query($sql_select, $connection) or die("".mysql_error()."");
    $row = mysql_fetch_array($result_select);
    if ( ($row['ttype_creditdebit'] == 'credit' && $_REQUEST['adj_type'] == 'customer') || ($row['ttype_creditdebit'] == 'debit' && $_REQUEST['adj_type'] == 'producer') )
      {
        $adj_amount = preg_replace("/[^0-9\.\-]/","",$_REQUEST['adj_amount']);
        $adj_amount = $adj_amount * (-1);
      }
    else
      {
        $adj_amount = preg_replace("/[^0-9\.\-]/","",$_REQUEST['adj_amount']);
      }
    if ( $_REQUEST['adj_type'] == 'customer' )
      {
        $sql2 = mysql_query('
          SELECT
            member_id
          FROM
            '.TABLE_BASKET_ALL.'
          WHERE
            basket_id = "'.mysql_real_escape_string ($_REQUEST['basket_id']).'"
            AND delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"');
        $row2 = mysql_fetch_array($sql2);
      }
    elseif ( $_REQUEST['adj_type'] == "producer" && $_REQUEST['producer_id'] )
      {
        $sql2 = mysql_query('
          SELECT
            member_id
          FROM
            producers
              WHERE producer_id = "'.mysql_real_escape_string ($_REQUEST['producer_id']).'"');
        $row2 = mysql_fetch_array($sql2);
      }
    // check for duplicates
    $sql = mysql_query('
      SELECT
        transaction_id
      FROM
        transactions t
      WHERE
        t.transaction_type="'.mysql_real_escape_string ($_REQUEST['adjt_id']).'"
          AND t.transaction_member_id = "'.mysql_real_escape_string ($row2['member_id']).'"
          AND t.transaction_basket_id = "'.mysql_real_escape_string ($_REQUEST['basket_id']).'"
          AND t.transaction_delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
          AND t.transaction_name = "'.mysql_real_escape_string ($row['ttype_name']).'"
          AND t.transaction_amount = "'.mysql_real_escape_string ($adj_amount).'"
          AND t.transaction_user = "'.mysql_real_escape_string ($_SESSION['member_id']).'"
          AND t.transaction_comments = "'.mysql_real_escape_string ($_REQUEST['adj_desc']).'"');
    if ( mysql_num_rows($sql) < 1 )
      {
        // If an tax condition was sent, then use it!
        if (isset ($_POST['adj_tax']) && ($_POST['adj_tax'] == 1 || $_POST['adj_tax'] == 0))
          {
            $adj_tax = $_POST['adj_tax'];
          }
        else
          {
            $adj_tax = $row['ttype_taxed'];
          }
        $sql_insert = '
          INSERT INTO transactions
            (
              transaction_type,
              transaction_name,
              transaction_amount,
              transaction_user,
              transaction_producer_id,
              transaction_member_id,
              transaction_basket_id,
              transaction_delivery_id,
              transaction_taxed,
              transaction_timestamp,
              transaction_comments,
              transaction_method)
          VALUES
            (
              "'.mysql_real_escape_string ($_REQUEST['adjt_id']).'",
              "'.mysql_real_escape_string ($row["ttype_name"]).'",
              "'.mysql_real_escape_string ($adj_amount).'",
              "'.mysql_real_escape_string ($_SESSION['member_id']).'",
              "'.mysql_real_escape_string ($_REQUEST['producer_id']).'",
              "'.mysql_real_escape_string ($row2['member_id']).'",
              "'.mysql_real_escape_string ($_REQUEST['basket_id']).'",
              "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'",
              "'.mysql_real_escape_string ($adj_tax).'",
              now(),
              "'.mysql_real_escape_string ($_REQUEST['adj_desc']).'",
              "'.mysql_real_escape_string ($_REQUEST['payment_method']).'"
            )';
        //echo $sql_insert."<br/>";
        $result_insert = @mysql_query($sql_insert, $connection) or die(mysql_error());
        $message .= ': <font color="#FFFFFF">Adjustment Added</font>';
      }
    else
      {
        $message .= ': <font color="#FFFFFF">ERROR: Duplicate adjustment!</font>';
      }
  }
elseif ( $_REQUEST['adjustment_submitted'] == "yep" && (!$_REQUEST['adjt_id'] || (!$_REQUEST['basket_id'] && $_REQUEST['adj_type'] == "customer") || ($_REQUEST['adj_type'] == "producer" && !$_REQUEST['producer_id'])) )
  {
    $message = ': <font color="#FFFFFF">Please select an adjustment type and basket.</font>';
  }
if ( $_REQUEST['basket_id'] && ($_REQUEST['delete_adjustment'] == 'yes' || ($_REQUEST['adjustment_submitted'] == 'yep' && $_REQUEST['adjt_id'])) )
  {
    $sqlo = '
      UPDATE
        '.TABLE_BASKET_ALL.'
      SET
        finalized = "0"
      WHERE
        basket_id = "'.mysql_real_escape_string ($_REQUEST['basket_id']).'"';
    //echo $sqlo."<br/>";
    $resulto = @mysql_query($sqlo, $connection) or die(mysql_error());
  }
// END UPDATING QUERY
//Show baskets depending on the delivery date.
$delivery_id = $_GET['delivery_id'];
if ( $_POST['adj_type'] )
  {
    $adj_type = $_POST['adj_type'];
  }
else
  {
    $adj_type = $_GET['adj_type'];
  }
$q = mysql_query('
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  ORDER BY
    delivery_id DESC');
while ( $row = mysql_fetch_array($q) )
  {
    $delivery_date_formated = date('Y: F j',mktime(0,0,0, substr($row['delivery_date'], 5, 2), substr($row['delivery_date'], 8), substr($row['delivery_date'], 0,4)));
    if ($row["delivery_id"] == $_REQUEST['delivery_id'])
      {
        $selected = ' selected';
        $adj_history = '
        <table cellpadding="7" cellspacing="2" border="0" width="100%">
          <tr>
            <td colspan="2" bgcolor="#AEDE86" align="left"><b>Delivery Date: '.date('Y: F j',mktime(0,0,0, substr($row['delivery_date'], 5, 2), substr($row['delivery_date'], 8), substr($row['delivery_date'], 0, 4))).'</b></b></td>
          </tr>';
      }
    else
      {
        $selected = '';
      }
    $display_dates .= '<option value="'.$row['delivery_id'].'"'.$selected.'>'.$delivery_date_formated.'</option>';
  }
if ( $_REQUEST['adj_type'] == 'customer' )
  {
    $query = '
      SELECT
        '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.member_id,
        '.TABLE_MEMBER.'.member_id,
        '.TABLE_BASKET_ALL.'.finalized,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.business_name,
        '.TABLE_ORDER_CYCLES.'.retail_markup,
        '.TABLE_ORDER_CYCLES.'.wholesale_markup
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
      LEFT JOIN
        '.TABLE_ORDER_CYCLES.' ON '.TABLE_ORDER_CYCLES.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
      GROUP BY
        basket_id
      ORDER BY
        last_name ASC';
    $q2 = mysql_query($query);
    while ( $row = mysql_fetch_array($q2) )
      {
        $member_id = $row['member_id'];
        $business_name = $row['business_name'];
        $last_name = $row['last_name'];
        $first_name = $row['first_name'];
        $last_name_2 = $row['last_name_2'];
        $first_name_2 = $row['first_name_2'];
        // retail_multiplier is used to decrement the adjustment an amount
        // so that when the co-op fee is added, the result will be correct.
        $retail_multiplier = 1 + ($row['retail_markup'] / 100);
        $wholesale_multiplier = 1 + ($row['wholesale_markup'] / 100);
        include("../func/show_name_last.php");
        $display_baskets .= '<option value="'.$row['basket_id'].'">'.$show_name.' #'.$member_id.'</option>';
        $sql = mysql_query('
          SELECT
            t.transaction_id,
            t.transaction_amount,
            t.transaction_basket_id,
            t.transaction_comments,
            tt.ttype_name
          FROM
            '.TABLE_TRANSACTIONS.' t,
            '.TABLE_TRANS_TYPES.' tt
          WHERE
            transaction_basket_id = "'.mysql_real_escape_string ($row['basket_id']).'"
            AND t.transaction_type = tt.ttype_id
            AND
              (
                tt.ttype_parent = "20"
                OR tt.ttype_parent = "40"
              )');
        if ( mysql_num_rows($sql) > 0 )
          {
            while ( $row = mysql_fetch_array($sql) )
              {
                $adj_history .= '
                  <tr bgcolor="#CCCCCC">
                    <td align="left">';
                if (!$row['finalized'])
                  {
                    $adj_history .= '
                      <a href="customer_invoice.php?delivery_id='.$_REQUEST['delivery_id'].'&basket_id='.$row['transaction_basket_id'].'&member_id='.$member_id.'">';
                  }
                else
                  {
                    $adj_history .= '
                      (finalized) <a href="invoice.php?basket_id='.$row['transaction_basket_id'].'&member_id='.$member_id.'">';
                  }
                $adj_history .= $show_name.
                    ' (Mem # '.$member_id.')</a>,  Basket id: '.$row['transaction_basket_id'].'<br>
                    '.$row['ttype_name'].': $'.number_format($row['transaction_amount'],2).'<br>
                    '.$row['transaction_comments'].'
                  </td>
                  <td>
                    <form action="'.$PHP_SELF.'?delivery_id='.$_REQUEST['delivery_id'].'&adj_type='.$_REQUEST['adj_type'].'" method="post">
                      <input type="hidden" name="transaction_id_passed" value="'.$row['transaction_id'].'">
                      <input type="hidden" name="delete_adjustment" value="yes">
                      <input type="hidden" name="basket_id" value="'.$row['transaction_basket_id'].'">
                      <input type="submit" name="where" value="Delete">
                    </form>
                  </td>
                </tr>';
              }
          }
      }
    $adj_history .= '
      </table>';
  }
elseif ( $_REQUEST['adj_type'] == 'producer' )
  {
    $q4 = mysql_query('
      SELECT
        '.TABLE_BASKET.'.producer_id,
        '.TABLE_PRODUCER.'.business_name
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN
        '.TABLE_BASKET.' ON '.TABLE_BASKET_ALL.'.basket_id = '.TABLE_BASKET.'.basket_id
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.TABLE_BASKET.'.producer_id = '.TABLE_PRODUCER.'.producer_id
      WHERE
        '.TABLE_BASKET_ALL.'.delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
        AND '.TABLE_BASKET.'.producer_id IS NOT NULL
      GROUP BY
        '.TABLE_BASKET.'.producer_id
      ORDER BY
        '.TABLE_PRODUCER.'.business_name ASC,
        '.TABLE_BASKET.'.producer_id');
    while ( $r4 = mysql_fetch_array($q4) )
      {
        $display_p .= '<option value="'.$r4['producer_id'].'">'.$r4['producer_id'].' : '.$r4['business_name'].'</option>';
      }
    $query2 = '
      SELECT
        '.TABLE_BASKET_ALL.'.basket_id,
        '.TABLE_BASKET_ALL.'.member_id,
        '.TABLE_MEMBER.'.member_id,
        '.TABLE_BASKET_ALL.'.finalized,
        '.TABLE_TRANSACTIONS.'.transaction_id,
        '.TABLE_TRANSACTIONS.'.transaction_amount,
        '.TABLE_TRANSACTIONS.'.transaction_comments,
        '.TABLE_TRANS_TYPES.'.ttype_name,
        '.TABLE_TRANSACTIONS.'.transaction_producer_id AS producer_id
      FROM
        '.TABLE_BASKET_ALL.'
      LEFT JOIN
        '.TABLE_MEMBER.' ON '.TABLE_MEMBER.'.member_id = '.TABLE_BASKET_ALL.'.member_id
      LEFT JOIN
        '.TABLE_PRODUCER.' ON '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
      LEFT JOIN
        '.TABLE_TRANSACTIONS.' ON '.TABLE_TRANSACTIONS.'.transaction_producer_id = '.TABLE_PRODUCER.'.producer_id
      LEFT JOIN
        '.TABLE_TRANS_TYPES.' ON '.TABLE_TRANS_TYPES.'.ttype_id = '.TABLE_TRANSACTIONS.'.transaction_type
      WHERE
        '.TABLE_TRANSACTIONS.'.transaction_delivery_id = "'.mysql_real_escape_string ($_REQUEST['delivery_id']).'"
        AND 
          (
            '.TABLE_TRANS_TYPES.'.ttype_parent = "20"
            OR '.TABLE_TRANS_TYPES.'.ttype_parent = "40"
          )
        AND '.TABLE_TRANSACTIONS.'.transaction_producer_id != ""
      GROUP BY
        '.TABLE_TRANSACTIONS.'.transaction_id
      ORDER BY
        '.TABLE_TRANSACTIONS.'.transaction_producer_id ASC ';
    $sql2 = mysql_query($query2);
    while ( $sql2 && $row = mysql_fetch_array($sql2) )
      {
        $member_id = $row['member_id'];
        $business_name = $row['business_name'];
        $last_name = $row['last_name'];
        $first_name = $row['first_name'];
        $last_name_2 = $row['last_name_2'];
        $first_name_2 = $row['first_name_2'];
        include("../func/show_name_last.php");
        $adj_history .= '
          <tr bgcolor="#CCCCCC">
            <td align="left">
              <a href="orders_prdcr_cust.php?delivery_id='.$_REQUEST['delivery_id'].'&producer_id='.$row['producer_id'].'">
              '.$row['producer_id'].' : '.$row['business_name'].' (Mem # '.$member_id.')</a><br>
              '.$row['ttype_name'].': $'.number_format($row['transaction_amount'],2).'<br>
              '.$row['transaction_comments'].'
            </td>
            <td>
              <form action="'.$PHP_SELF.'?delivery_id='.$_REQUEST['delivery_id'].'" method="post">
                <input type="hidden" name="transaction_id_passed" value="'.$row['transaction_id'].'">
                <input type="hidden" name="delete_adjustment" value="yes">
                <input type="hidden" name="producer_id" value="'.$row['producer_id'].'">
                <input type="submit" name="where" value="Delete">
              </form>
            </td>
          </tr>';
      }
    $adj_history .= '
      </table>';
  }

$sql_adjt = '
  SELECT
    *
  FROM
    '.TABLE_TRANS_TYPES.'
  WHERE
    ttype_status = "1"
    AND
      (
        ttype_parent="20"
        OR ttype_parent="40"
      )
  ORDER BY ttype_whereshow ASC,
    ttype_creditdebit ASC,
    ttype_name ASC';
$result_adjt = @mysql_query($sql_adjt, $connection) or die("".mysql_error()."");
while ( $row = mysql_fetch_array($result_adjt) )
  {
    if ( $row['ttype_taxed'] == 1 )
      {
        $taxed = 'Y';
      }
    else
      {
        $taxed = 'N';
      }
    $display_adjt .= '
      <tr bgcolor="#DDDDDD" style="font-size:9pt;font-family:Arial;">
        <td align="center">'.ucfirst($row['ttype_whereshow']).'</td>
        <td align=center>'.$taxed.'</td>
        <td>'.$row['ttype_name'].'</td>
        <td>'.$row['ttype_desc'].'</td>
        <td align="center">'.$row['ttype_creditdebit'].'</td>
      </tr>';
  }
$sql_adjt = '
  SELECT
    *
  FROM
    '.TABLE_TRANS_TYPES.'
  WHERE
    ttype_status = "1"
    AND
      (
        ttype_parent="20"
        OR ttype_parent="40"
      )
    AND ttype_whereshow = "'.mysql_real_escape_string ($_REQUEST['adj_type']).'"
  ORDER BY
    ttype_name ASC';
$result_adjt = @mysql_query($sql_adjt, $connection) or die("".mysql_error()."");
while ( $row = mysql_fetch_array($result_adjt) )
  {
    $display_adjt_dropdownbox .= '
      <option value="'.$row['ttype_id'].'">'.$row['ttype_name'].'</option>';
  }

$content_adjustment .= '
'.$font.'
<table cellpadding="7" cellspacing="2" border="0" width="100%">
  <tr>
    <td colspan="2" bgcolor="#AE58DA" align="left"><b>Add an Adjustment</b> '.$message.'</td>
  </tr>
  <tr>
    <td align="left" bgcolor="#CCCCCC" valign="top">
      <form action="'.$PHP_SELF.'?delivery_id='.$_REQUEST['delivery_id'].'" method="post" name="adjustments">
      <table cellpadding="1" cellspacing="1" class="adj_table">
        <tr>
          <td>Type of invoice to apply it to:</td>
          <td>
            <select name="adj_type" id="adj_type" onChange="Load_id()">
              <option value="0">Please select a type</option>';

function listEnum($fieldname, $table_name)
  {
    $mysql_datatype_field = 1;
    if (!$result = mysql_query ('
      SHOW COLUMNS
      FROM
        '.mysql_real_escape_string ($table_name).'
      LIKE
        "'.mysql_real_escape_string ($fieldname).'"') )
      {
        $output=0;
        echo mysql_error();
      }
    else
      {
        $mysql_column_data = mysql_fetch_row( $result );
        if ( !$enum_data= $mysql_column_data[$mysql_datatype_field] )
          {
            $output=0;
          }
        elseif ( !$buffer_array=explode("'", $enum_data) )
          {
            $output = 0;
          }
        else
          {
            $i = 0;
            reset ($buffer_array);
            while (list(, $value) = each ($buffer_array))
              {
                if ( $i % 2 ) $output[$value] = $value;
                ++$i;
              }
          }
      }
    return $output;
  }
$types = listEnum('ttype_whereshow','transactions_types');
foreach ( $types as $key => $type )
  {
    if ( $type!='')
      {
        $selected_type = ($type == $_REQUEST['adj_type'])? "SELECTED":"";
        $content_adjustment .= '<option value="'.$key.'" '.$selected_type.'>'.ucfirst($type).'</option>';
      }
  }

$content_adjustment .= '
            </select>
          </td>
        </tr>
        <tr>
          <td>Select Delivery Date: </td>
          <td>
            <select name="delivery_id" onChange="Load_id()">
              <option value="0">Please select a date</option>
              '.$display_dates.'
            </select>
          </td>
        </tr>
        <tr>
          <td>Select '.ucfirst($_REQUEST['adj_type']).' Invoice: </td>
          <td>';
if ( $_REQUEST['adj_type'] == 'customer' )
  {
    $content_adjustment .= '
            <select name="basket_id">
              <option value="0">Please select a basket</option>
              '.$display_baskets.'
            </select>';
  }
elseif ( $_REQUEST['adj_type']=='producer' )
  {
    $content_adjustment .= '
            <select name="producer_id">
              <option value="0">Please select a producer invoice</option>
              '.$display_p.'
            </select>';
  }
$content_adjustment .= '
          </td>
        </tr>
        <tr>
          <td>Type of Adjustment: </td>
          <td>
            <select name="adjt_id">
              <option value="">Select Type of Adjustment</option>
              '.$display_adjt_dropdownbox.'
            </select>
          </td>
        </tr>
        <tr>
          <td>Taxable Adjustment: </td>
          <td>
            <b>Override whether adjustment is taxed:</b><br><br>
            <input type="radio" name="adj_tax" value="1">
            <b>Apply taxes and customer fees:</b> Adjustment will be included in the invoice subtotal as a taxable item.
            Regular customer fees will be calculated on the adjustment amount.<br><br>
            <input type="radio" name="adj_tax" value="0">
            <b>No taxes:</b> Adjustment will be added after all other calculations and is not taxed. Customer fees will be included in the Calculated Actual adj.<br>
          </td>
        </tr>
        <tr>
          <td>Payment Method: </td>
          <td>(Only needed for Membership Payments)<br/>';
$sql = mysql_query('
  SELECT
    payment_method
  FROM
    '.TABLE_PAY);
while ( $row = mysql_fetch_array($sql) )
  {
    $content_adjustment .= '<input type="radio" name="payment_method" value="'.$row['payment_method'].'"> '.$row['payment_method'].' ';
  }
// Following code has added "calculator" to correctly load adj_amount depending upon whether
// the adjustment is taxable or not (i.e. has a co-op fee or not).
$content_adjustment .= '
          </td>
        </tr>
        <tr>
          <td valign="top">Amount: </td>
          <td valign="top">
            <b>Adjustment amount (enter <u>one</u> value):</b><br><br>
    '.(SHOW_ACTUAL_PRICE == true ? '
            Taxable Retail adj: $ <input type="text" id="adj_amount_retail" size="5" maxlength="6" onKeyUp="document.getElementById(\'adj_amount\').value = (this.value / '.$retail_multiplier.').toFixed(2);document.getElementById(\'adj_amount_whsle\').value = \'\';document.getElementById(\'adj_amount_notax\').value = \'\';"><br>
            Taxable Whsle adj: $ <input type="text" id="adj_amount_whsle" size="5" maxlength="6" onKeyUp="document.getElementById(\'adj_amount\').value = (this.value / '.$wholesale_multiplier.').toFixed(2);document.getElementById(\'adj_amount_retail\').value = \'\';document.getElementById(\'adj_amount_notax\').value = \'\';"><br>
            Non-taxable adj: $ <input type="text" id="adj_amount_notax" size="5" maxlength="6" onKeyUp="document.getElementById(\'adj_amount\').value = (this.value * 1).toFixed(2);document.getElementById(\'adj_amount_whsle\').value = \'\';document.getElementById(\'adj_amount_retail\').value = \'\';"><br>'
    : '
            Taxable adj: $ <input type="text" id="adj_amount_tax" size="5" maxlength="6" onKeyUp="document.getElementById(\'adj_amount\').value = (this.value * 1).toFixed(2);document.getElementById(\'adj_amount_whsle\').value = \'\';document.getElementById(\'adj_amount_retail\').value = \'\';"><br>
            Non-taxed Retail adj: $ <input type="text" id="adj_amount_retail" size="5" maxlength="6" onKeyUp="document.getElementById(\'adj_amount\').value = (this.value * '.$retail_multiplier.').toFixed(2);document.getElementById(\'adj_amount_whsle\').value = \'\';document.getElementById(\'adj_amount_tax\').value = \'\';"><br>
            Non-taxed Whsle adj: $ <input type="text" id="adj_amount_whsle" size="5" maxlength="6" onKeyUp="document.getElementById(\'adj_amount\').value = (this.value * '.$wholesale_multiplier.').toFixed(2);document.getElementById(\'adj_amount_retail\').value = \'\';document.getElementById(\'adj_amount_tax\').value = \'\';"><br>'
    ).'
            Calculated Actual adj: $ <input id="adj_amount" type="text" name="adj_amount" size="5" maxlength="6"><br>
            <small style="font-size:0.8em;color:#006;line-height:1;"><strong>NOTE:</strong> Use &quot;Calculated Actual adj&quot; for membership adjustments of all kinds. For all adjustments, there is no need to add a plus or minus unless you wish to invert the normal operation of the transaction.</small>
          </td>
        </tr>
        <tr>
          <td valign=top>Description:</td>
          <td valign=top>
            <textarea name="adj_desc" rows="2" cols="30"></textarea>
            <input type="hidden" name="adjustment_submitted" value="yep">
            <input type="submit" name="where" value="Submit Adjustment">
          </form>
          </td>
        </tr>
      </table>
    </td>
    <td bgcolor="#cccccc">
      <table cellspacing="1" cellpadding="2" border="0">
        <tr bgcolor="#dddddd">
          <td align="center"><b>Type</b></td>
          <td align="center"><b>Tax Calc.</b></td>
          <td align="center"><b>Type</b></td>
          <td align="center"><b>Example</b></td>
          <td align="center"><b>Credit or Debit</b></td></tr>
          '.$display_adjt.'
      </table>
    </td>
  </tr>
</table>'
.$adj_history;

$page_specific_css .= '
  <style type="text/css">
    .adj_table tr td {
      padding:15px 5px;
      }
  </style>';

$page_specific_javascript .= '
<script type="text/javascript">
function Load_id()
  {
    var delivery_id = document.adjustments.delivery_id.options[document.adjustments.delivery_id.selectedIndex].value
    var id_txt = "?delivery_id="
    // var adj_type = document.adjustments.adj_type.options[document.adjustments.adj_type.selectedIndex].value
    var adj_type = document.getElementById("adj_type").value
    var adj_txt = "&adj_type="
    location = id_txt + delivery_id + adj_txt + adj_type
  }
</script>';

$page_title_html = '<span class="title">Treasurer Functions</span>';
$page_subtitle_html = '<span class="subtitle">Invoice Adjustments</span>';
$page_title = 'Treasurer Functions: Invoice Adjustments';
$page_tab = 'cashier_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_adjustment.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
