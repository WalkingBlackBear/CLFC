<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin');

$content_prep .= '<div style="padding:1em;">';

if ($_POST['action'] == "BACKUP")
  {
    $command = "BACKUP";
    $exec_command = 'cd '.DB_BACKUP_ROOT.'; ./do_db_backup';
    exec ($exec_command, $output);
    $output_display .= 'RESULT: '.implode ("\n", $output);
  }
elseif ($_POST['action'] == "RESTORE")
  {
    $command = "RESTORE";
    $exec_command = 'cd '.DB_BACKUP_ROOT.'/db_backup; ./load_data';
    exec ($exec_command, $output);
    $output_display = 'RESULT: '.implode ("\n", $output);
  }



$cycle_time = DAYS_PER_CYCLE * 24 * 3600; // Period in seconds for typical cycles

/////////////////////////////////////////////////////////////////////////////////////
///                                                                               ///
///                                  FUNCTIONS                                    ///
///                                                                               ///
/////////////////////////////////////////////////////////////////////////////////////

function getTableList ()
  {
    $tables = mysql_list_tables(DB_NAME);
    $table_names = array ();
    for($i = 0; $i < mysql_num_rows($tables); $i++)
      {
        $table = mysql_tablename ($tables, $i);
        array_push ($table_names, $table);
      }
    return ($table_names);
  }

function doTextDiff ($string1, $string2, $diff_open, $diff_close)
  {
    // Note... this will not work for words or non-breaking combinations longer than
    // 61 characters.  Limitations: Uses embedded text that must not match any strings
    // being processed.  Using the shell diff... is there a good php way to do this???

    // Use /tmp directory
    // Use file1.tmp and file2.tmp

    $file1 = '/tmp/file1.tmp';
    if (!file_exists($file1)) { touch ($file1); }
    $handle1 = fopen ($file1, 'w');
    //  $string1 = str_replace("<", "&lt;", $string1);
    $string1 = nl2br (htmlspecialchars ($string1, ENT_QUOTES));
    $string1 = str_replace(" ", "\n", $string1);
    $string1 = strip_tags ($string1, '<br>');
    fwrite ($handle1, $string1);
    fclose ($handle1);

    $file2 = '/tmp/file2.tmp';
    if (!file_exists($file2)) { touch ($file2); }
    $handle2 = fopen ($file2, 'w');
    //  $string2 = str_replace("<", "&lt;", $string2);
    $string2 = nl2br (htmlspecialchars ($string2, ENT_QUOTES));
    $string2 = str_replace(" ", "\n", $string2);
    $string2 = strip_tags ($string2, '<br>');
    fwrite ($handle2, $string2);
    fclose ($handle2);

    // Get the diff output
    $raw_diff = explode ("\n", shell_exec("diff -ad --side-by-side $file1 $file2"));

    // Now figure out the results...
    $out1 = array (); $out2 = array ();

    // make sure diffs start out closed
    $diff1 = false;
    $diff2 = false;
    foreach ($raw_diff as $line)
      {
        $word1 = ""; $word2 = ""; $diff = "";
        $line_parts = explode ("\t", $line);
        $word1 = trim (array_shift ($line_parts));
        $word2 = trim (array_pop ($line_parts));
        $diff = trim (array_pop ($line_parts));
        if ($word2 == "<")
          {
            $word2 = "";
            $diff = "<";
          }
        if ($diff == "")
          {
            if ($diff1 == true) { array_push ($out1, "STOP_DIFF"); $diff1 = false; }
            if ($diff2 == true) { array_push ($out2, "STOP_DIFF"); $diff2 = false; }
            array_push ($out1, $word1);
            array_push ($out2, $word2);
          }
        if ($diff == "<")
          {
            if ($diff1 == false) { array_push ($out1, "START_DIFF"); $diff1 = true; }
            array_push ($out1, $word1);
          }
        if ($diff == ">")
          {
            if ($diff2 == false) { array_push ($out2, "START_DIFF"); $diff2 = true; }
            array_push ($out2, $word2);
          }
        if ($diff == "|")
          {
            if ($diff1 == false) { array_push ($out1, "START_DIFF"); $diff1 = true; }
            if ($diff2 == false) { array_push ($out2, "START_DIFF"); $diff2 = true; }
            array_push ($out1, $word1);
            array_push ($out2, $word2);
          }
      }
    // make sure we close any open diffs
    if ($diff1 == true) { array_push ($out1, "STOP_DIFF"); }
    if ($diff2 == true) { array_push ($out2, "STOP_DIFF"); }
    $output1 = implode (" ", $out1);
    $output1 = str_replace ("START_DIFF", $diff_open, $output1);
    $output1 = str_replace ("STOP_DIFF", $diff_close, $output1);
    $output1 = str_replace ("&lt;", "<", $output1);
    $output2 = implode (" ", $out2);
    $output2 = str_replace ("START_DIFF", $diff_open, $output2);
    $output2 = str_replace ("STOP_DIFF", $diff_close, $output2);
    $output2 = str_replace ("&lt;", "<", $output2);

    return (array($output1, $output2));
  }

$action = $_POST['action'];

////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                     BEGIN NEW PAGE - NO SUBMITTED DATA                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

// Get the number of products from the previous cycle's orders so later we can
// make them all non-new.
$query = '
  SELECT
    MAX( product_id ) AS max_id
  FROM
    `'.TABLE_PRODUCT_PREV.'`';
$result = @mysql_query($query, $connection) or $error_array[5] = "SQL Error while retrieving maximum former product id!\n";
$row = mysql_fetch_array($result); // Only need the first row
$max_id_notnew = $row['max_id'];

$query = '
  SELECT
    COUNT(product_id) AS count_new_products 
  FROM
    '.TABLE_PRODUCT.'
  WHERE
    product_id > "'.$max_id_notnew.'"';
$result = @mysql_query($query, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
$row = mysql_fetch_array($result); // Only need the first row
$count_new_products = $row['count_new_products'];

if (!$action || $action == "BACKUP" || $action == "RESTORE")
  {
    // Show backup/restore section
//     $content_prep .= '
//       <p>It is highly recommended that you backup your database prior to prepping the order cycle.  If anything goes wrong during the cycle prep immediately do the restore function. DO NOT back it up again or you will overwrite the good data.</p>
//       <table width="60%" border="0" cellpadding="5" style="background-color:#eee; border:1px solid gray;margin:auto;margin-bottom:2em;">
//         <tr style="color:#ffc; background-color:#468;">
//           <th colspan="2" align="center">Database operations</th>
//         </tr>
//           <td align="center">
//             <form action="'.$_SERVER['PHP_SELF'].'" method="post">
//             <input type="submit" name="action" value="BACKUP"></form>
//           </td>
//           <td align="center">
//             <form action="'.$_SERVER['PHP_SELF'].'" method="post">
//             <input type="submit" name="action" value="RESTORE"></form>
//           </td>
//         </tr>
//         <tr>
//           <td colspan="2"><pre>'.$output_display.'</pre></td>
//         </tr>
//       </table>';

    // This is a new page visit so start out with the forms
    $query = '
      SELECT
        * 
      FROM
        '.TABLE_ORDER_CYCLES.'
      ORDER BY
        delivery_id DESC
      LIMIT 0,1';
    $result = @mysql_query($query, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
    $row = mysql_fetch_array($result); // Only need the first row
    $prior_delivery_id = $row['delivery_id'];
    $prior_delivery_date_open = $row['date_open'];
    $prior_delivery_date = $row['delivery_date'];
    $prior_delivery_date_closed = $row['date_closed'];
    $prior_delivery_msg_all = $row['msg_all'];
    $prior_delivery_msg_bottom = $row['msg_bottom'];
    $prior_delivery_coopfee = $row['coopfee'];
    $prior_order_fill_deadline = $row['order_fill_deadline'];
    $prior_delivery_producer_markdown = $row['producer_markdown'];
    $prior_delivery_retail_markup = $row['retail_markup'];
    $prior_delivery_wholesale_markup = $row['wholesale_markup'];
    $prior_delivery = date ("Y-m-d", strtotime($prior_delivery_date));

    $content_prep .= '
      <form action="'.$_SERVER['PHP_SELF'].'" method="POST">
      <p>Enter dates and other parameters for the new order cycle in the table below.  Not all values will be used for every installation.</p>
      <table border="1" style="background-color:#eee;">
        <tr style="color:#ffc; background-color:#468;">
          <th valign="top">Field</th>
          <th valign="top">Prior Value</th>
          <th valign="top">New Value</th>
          <th valign="top">Notes</th>
        </tr>
        <tr>
          <td valign="top">delivery_id</td>
          <td valign="top">'.$prior_delivery_id.'</td>
          <td valign="top"><input size="5" maxlength="4" value="'.($prior_delivery_id + 1).'" disabled>
                           <input type="hidden" name="delivery_id" value="'.($prior_delivery_id + 1).'"></td>
          <td valign="top">This value may not be changed</td>
        </tr>
        <tr>
          <td valign="top">coopfee</td>
          <td valign="top">$ '.number_format ($prior_delivery_coopfee, 2).'</td>
          <td valign="top">$ <input name="coopfee" size="10" maxlength="5" value="'.number_format ($prior_delivery_coopfee, 2).'"></td>
          <td valign="top">Fixed fee charged to each order</td>
        </tr>
        <tr>
          <td valign="top">producer_markdown</td>
          <td valign="top">'.number_format ($prior_delivery_producer_markdown, 2).'&nbsp;%</td>
          <td valign="top"><input name="producer_markdown" size="10" maxlength="10" value="'.number_format ($prior_delivery_producer_markdown, 2).'">&nbsp;%</td>
          <td valign="top">Percent of product price charged to the producer as a service fee.</td>
        </tr>
        <tr>
          <td valign="top">retail_markup</td>
          <td valign="top">'.number_format ($prior_delivery_retail_markup, 2).'&nbsp;%</td>
          <td valign="top"><input name="retail_markup" size="10" maxlength="10" value="'.number_format ($prior_delivery_retail_markup, 2).'">&nbsp;%</td>
          <td valign="top">Percent of product price charged to the retail customer as a service fee.</td>
        </tr>
        <tr>
          <td valign="top">wholesale_markup</td>
          <td valign="top">'.number_format ($prior_delivery_wholesale_markup, 2).'&nbsp;%</td>
          <td valign="top"><input name="wholesale_markup" size="10" maxlength="10" value="'.number_format ($prior_delivery_wholesale_markup, 2).'">&nbsp;%</td>
          <td valign="top">Percent of product price charged to the wholesale_customer as a service fee.</td>
        </tr>
        <tr>
          <td valign="top">date_open</td>
          <td valign="top">'.$prior_delivery_date_open.'</td>
          <td valign="top"><input name="date_open" size="25" maxlength="25" value="'.date ('Y-m-d H:i:s', (strtotime ($prior_delivery_date_open) + $cycle_time)).'"></td>
          <td valign="top">Date-time the order will automatically open. (YYYY-MM-DD hh:mm:ss)</td>
        </tr>
        <tr>
          <td valign="top">date_closed</td>
          <td valign="top">'.$prior_delivery_date_closed.'</td>
          <td valign="top"><input name="date_closed" size="25" maxlength="25" value="'.date ('Y-m-d H:i:s', (strtotime ($prior_delivery_date_closed) + $cycle_time)).'"></td>
          <td valign="top">Date-time the order will automatically close. (YYYY-MM-DD hh:mm:ss)</td>
        </tr>
        <tr>
          <td valign="top">order_fill_deadline</td>
          <td valign="top">'.$prior_order_fill_deadline.'</td>
          <td valign="top"><input name="order_fill_deadline" size="25" maxlength="25" value="'.date ('Y-m-d H:i:s', (strtotime ($prior_order_fill_deadline) + $cycle_time)).'"></td>
          <td valign="top">Date and time producers will need to have their random weights and outs completed. (YYYY-MM-DD hh:mm:ss)</td>
        </tr>
        <tr>
          <td valign="top">delivery_date</td>
          <td valign="top">'.$prior_delivery_date.'</td>
          <td valign="top"><input name="delivery_date" size="10" maxlength="10" value="'.date ('Y-m-d', (strtotime ($prior_delivery_date) + $cycle_time)).'"></td>
          <td valign="top">(YYYY-MM-DD)</td>
        </tr>
        <tr>
          <td valign="top">msg_all</td>
          <td valign="top" colspan="2"><textarea name="msg_all" rows="8" cols="60">'.br2nl ($prior_delivery_msg_all).'</textarea></td>
          <td valign="top">This message is usually configured to appear near the top of the invoice</td>
        </tr>
        <tr>
          <td valign="top">msg_bottom</td>
          <td valign="top" colspan="2"><textarea name="msg_bottom" rows="8" cols="60">'.br2nl ($prior_delivery_msg_bottom).'</textarea></td>
          <td valign="top">This message is usually configured to appear near the bottom of the invoice</td>
        </tr>
      </table>
      <p>Use the following form to change which delivery sites will be available for this ordering cycle.  &quot;Standby&quot; is used for sites that are not available for this order cycle,
      but remain available on the locations page to indicate they are normally in the service area.</p>';

    $query = '
      SELECT
        *
      FROM
        '.TABLE_DELCODE.'
      ORDER BY
        hub,
        delcode;';
    $result = mysql_query($query, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
    $content_prep .= '
        <table border="1" cellpadding="2" style="background-color:#eee; border:1px solid black; border-collapse:separate; font-size:80%">
          <tr style="color:#ffc; background-color:#468;">
            <th>Delcode ID</th>
            <th>Del Code</th>
            <th>Del Type</th>
            <th>Del Desc.</th>
            <th>Hub</th>
            <th>Inactive</th>
          </tr>';
    while ($row = mysql_fetch_object($result))
      {
        if ($row->inactive == 0) // Active delivery site
          {
            $inactive_select = '
              <option value="0" selected>Active Site</option>
              <option value="1">INACTIVE</option>
              <option value="2">Standby Site</option>
              ';
              $inactive_color = '#cfc';
          }
        elseif ($row->inactive == 1) // Inactive delivery site
          {
            $inactive_select = '
              <option value="0">Active Site</option>
              <option value="1" selected>INACTIVE</option>
              <option value="2">Standby Site</option>
              ';
              $inactive_color = '#fcc';
          }
        elseif ($row->inactive == 2) // Inactive delivery site but okay for signups
          {
            $inactive_select = '
              <option value="0">Active Site</option>
              <option value="1">INACTIVE</option>
              <option value="2" selected>Standby Site</option>
              ';
              $inactive_color = '#ffc';
          }
        if ($row->deltype == "P") // Order pickup site
          {
            $deltype_display = "Pickup";
          }
        elseif ($row->deltype == "D") // Delivery choice
          {
            $deltype_display = "Delivery";
          }
        $content_prep .= '
          <tr style="background-color:'.$inactive_color.';">
            <td>'.$row->delcode_id.'</td>
            <td>'.$row->delcode.'</td>
            <td>'.$deltype_display.'</td>
            <td>'.$row->deldesc.'</td>
            <td>'.$row->hub.'</td>
            <td><select name="'.$row->delcode_id.'_inactive">'.$inactive_select.'</select></td>
          </tr>';
      }
    $content_prep .= '
      </table>';

    // Run the query to view changed/unchanged products

    $query = '
      SELECT
        p.product_id,
        p.product_name AS product_name_old,
        pp.product_name AS product_name_new,
        p.unit_price AS unit_price_old,
        pp.unit_price AS unit_price_new,
        p.detailed_notes AS detailed_notes_old,
        pp.detailed_notes AS detailed_notes_new,
        p.pricing_unit AS pricing_unit_old,
        pp.pricing_unit AS pricing_unit_new,
        p.ordering_unit AS ordering_unit_old,
        pp.ordering_unit AS ordering_unit_new,
        p.meat_weight_type AS meat_weight_type_old,
        pp.meat_weight_type AS meat_weight_type_new 
      FROM
        '.TABLE_PRODUCT_PREP.' p,
        '.TABLE_PRODUCT_PREV.' pp 
      WHERE
        p.product_id = pp.product_id 
        AND p.changed = "1"
      GROUP BY
        product_id;';
    $result = mysql_query($query,$connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
    $content_prep .= '
      <p>Unchanged products will be automatically marked as &quot;unchanged&quot;.  The products below do have some sort of change from the way they were listed when the last cycle was prepped.  Please check any products that should be marked as unchanged because they are similar-enough to the prior entry.</p>
      <table border="1" cellpadding="2" style="background-color:#eee; border:1px solid black; border-collapse:separate; font-size:80%">
        <tr style="color:#ffc; background-color:#468;">
          <th>(Count)<br>Prod&nbsp;ID<br>Unchanged?</th>
          <th>Product Name</th>
          <th>Unit Price</th>
          <th>Pricing Unit</th>
          <th>Ordering Unit</th>
          <th>Meat Weight Type</th>
          <th>Detailed Notes</th>
        </tr>';

    while ($row = mysql_fetch_array($result))
      {
        $product_id = $row['product_id'];
        $product_name_old = $row['product_name_old'];
        $product_name_new = $row['product_name_new'];
        $unit_price_old = $row['unit_price_old'];
        $unit_price_new = $row['unit_price_new'];
        $pricing_unit_old = $row['pricing_unit_old'];
        $pricing_unit_new = $row['pricing_unit_new'];
        $ordering_unit_old = $row['ordering_unit_old'];
        $ordering_unit_new = $row['ordering_unit_new'];
        $meat_weight_type_old = $row['meat_weight_type_old'];
        $meat_weight_type_new = $row['meat_weight_type_new'];
        $detailed_notes_old = $row['detailed_notes_old'];
        $detailed_notes_new = $row['detailed_notes_new'];

        if (($product_name_old == $product_name_new) &&
            ($unit_price_old == $unit_price_new) &&
            ($pricing_unit_old == $pricing_unit_new) &&
            ($ordering_unit_old == $ordering_unit_new) &&
            ($meat_weight_type_old == $meat_weight_type_new) &&
            ($detailed_notes_old == $detailed_notes_new))
          { // Case where there is no change
            $changed = "false";
            $product_name_celltype = " style='color:#c0c0c0;'";
            $unit_price_celltype = " style='color:#c0c0c0;'";
            $pricing_unit_celltype = " style='color:#c0c0c0;'";
            $ordering_unit_celltype = " style='color:#c0c0c0;'";
            $meat_weight_type_celltype = " style='color:#c0c0c0;'";
            $detailed_notes_celltype = " style='color:#c0c0c0;'";
          }
        else
          { // Case where *something* changed
            $changed = "true";
            if ($product_name_old != $product_name_new) {
              list ($product_name_old, $product_name_new) = doTextDiff ($product_name_old, $product_name_new, '<font style="color:#A00000;">', '</font>');
              }
            if ($unit_price_old == $unit_price_new) {
              $unit_price_celltype = "";
              }
            else {
              $unit_price_celltype = " style='color:#A00000;'";
              }
            if ($pricing_unit_old == $pricing_unit_new) {
              $pricing_unit_celltype = "";
              }
            else {
              $pricing_unit_celltype = " style='color:#A00000;'";
              }
            if ($ordering_unit_old == $ordering_unit_new) {
              $ordering_unit_celltype = "";
              }
            else {
              $ordering_unit_celltype = " style='color:#A00000;'";
              }
            if ($meat_weight_type_old == $meat_weight_type_new) {
              $meat_weight_type_celltype = "";
              }
            else {
              $meat_weight_type_celltype = " style='color:#A00000;'";
              }
            if ($detailed_notes_old != $detailed_notes_new) {
              list ($detailed_notes_old, $detailed_notes_new) = doTextDiff ($detailed_notes_old, $detailed_notes_new, '<font style="color:#A00000;">', '</font>');
              }
          }
        if ($changed == "true")
          {
            $count_data = ++$count;
          }
        else
          {
            $count_data = '';
          }

        if ($changed != "true")
          {
            // If not changed, then we will later marke the product unchanged...
            $content_prep .= '<input type="hidden" name="product_'.$product_id.'" value="unchanged">';
          }
        else
          {
            // If it is changed, then we give the option to mark the product as unchanged (for minor changes)...
            $content_prep .= '
              <tr>
                <td style="border:1px solid gray; text-align:center;">('.$count_data.')<br>#'.$product_id.'<br>
                  <input type="checkbox" name="product_'.$product_id.'" value="unchanged"></td>
                <td>'.$product_name_old.'<hr>'.$product_name_new.'</td>
                <td'.$unit_price_celltype.'>$'.$unit_price_old.'<hr>$'.$unit_price_new.'</td>
                <td'.$pricing_unit_celltype.'>'.$pricing_unit_old.'<hr>'.$pricing_unit_new.'</td>
                <td'.$ordering_unit_celltype.'>'.$ordering_unit_old.'<hr>'.$ordering_unit_new.'</td>
                <td'.$meat_weight_type_celltype.'>'.$meat_weight_type_old.'<hr>'.$meat_weight_type_new.'</td>
                <td>'.$detailed_notes_old.'<hr>'.$detailed_notes_new.'</td>
              </tr>';
          }
      }

    $content_prep .= '
      </table>
      <br>
      <table border="0" width="100%">
        <tr>
          <td width="33%" align="center"><input type="submit" name="action" value="Process"></td>
          <td width="33%" align="center"><input type="submit" name="action" value="Process and Make Live"></td>
          <td width="33%" align="center"><input type="reset"></td>
        </tr>
      </table>
      <input type="hidden" name="max_id_notnew" value="'.$max_id_notnew.'">
      </form>
      <hr>';
  }


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

elseif ($action == "Process" || $action == "Process and Make Live")
  {
    // Make sure all submitted data looks good...
    // Assume everything will go well
    unset ($error);

    // We should have all values and they should agree.
    $coopfee = $_POST['coopfee'];
    $producer_markdown = $_POST['producer_markdown'];
    $retail_markup = $_POST['retail_markup'];
    $wholesale_markup = $_POST['wholesale_markup'];
    $delivery_id = $_POST['delivery_id'];
    $date_open = date ('Y-m-d H:i:s', strtotime ($_POST['date_open']));
    $delivery_date = date ('Y-m-d', strtotime ($_POST['delivery_date']));
    $date_closed = date ('Y-m-d H:i:s', strtotime ($_POST['date_closed']));
    $msg_all = nl2br2 ($_POST['msg_all']);
    $msg_bottom = nl2br2 ($_POST['msg_bottom']);
    $max_id_notnew = $_POST['max_id_notnew'];
    $order_fill_deadline = $_POST['order_fill_deadline'];

///                                                                          ///
///                                  STEP ONE                                ///
///                                                                          ///

    $query_array[0] = '
      DROP TABLE
        `'.TABLE_PRODUCT_PREV.'`';

///                                                                          ///
///                                  STEP TWO                                ///
///                                                                          ///

    $query_array[1] = '
      CREATE TABLE
        `'.TABLE_PRODUCT_PREV.'`
      SELECT
        *
      FROM `'.TABLE_PRODUCT_PREP.'`';

///                                                                          ///
///                                 STEP THREE                               ///
///                                                                          ///

    if (strtotime ($delivery_date) < 1000) {
      $error_array[2] .= "Invalid delivery date!<br>\n";
        }
    if (! is_numeric ($coopfee)) {
      $error_array[2] .= "Coop fee is not a number!<br>\n";
        }
    if (! is_numeric ($producer_markdown)) {
      $error_array[2] .= "Producer percentage is not a number!<br>\n";
        }
    if (! is_numeric ($retail_markup)) {
      $error_array[2] .= "Retail percentage is not a number!<br>\n";
        }
    if (! is_numeric ($wholesale_markup)) {
      $error_array[2] .= "Wholesale percentage is not a number!<br>\n";
        }
    if (strtotime ($date_open) < 1000) {
      $error_array[2] .= "Invalid date_open date!<br>\n";
      }
    if (strtotime ($date_closed) < 1000) {
      $error_array[2] .= "Invalid date_closed date!<br>\n";
      }
    if (strtotime ($delivery_date) < 1000) {
      $error_array[2] .= "Invalid delivery_date date!<br>\n";
      }

    $query_array[2] = '
      INSERT INTO
        `'.TABLE_ORDER_CYCLES.'`
      SET
        delivery_id = "'.mysql_real_escape_string ($delivery_id).'",
        date_open = "'.mysql_real_escape_string ($date_open).'",
        date_closed = "'.mysql_real_escape_string ($date_closed).'",
        delivery_date = "'.mysql_real_escape_string ($delivery_date).'",
        msg_all = "'.mysql_real_escape_string ($msg_all).'",
        msg_bottom = "'.mysql_real_escape_string ($msg_bottom).'",
        coopfee = "'.mysql_real_escape_string ($coopfee).'",
        producer_markdown = "'.mysql_real_escape_string ($producer_markdown).'",
        retail_markup = "'.mysql_real_escape_string ($retail_markup).'",
        wholesale_markup = "'.mysql_real_escape_string ($wholesale_markup).'",
        order_fill_deadline = "'.mysql_real_escape_string ($order_fill_deadline).'"';

///                                                                          ///
///                                 STEP FOUR                                ///
///                                                                          ///

    $query_array[3] = '
      UPDATE
        '.TABLE_PRODUCT.'
      SET
        new="0"
      WHERE
        product_id <= "'.$max_id_notnew.'"';
    $query_array[4] = '
      UPDATE
        '.TABLE_PRODUCT_PREP.'
      SET
        new="0"
      WHERE
        product_id <= "'.$max_id_notnew.'"';


///                                                                          ///
///                                 STEP FIVE                                ///
///                                                                          ///

    // Get the products that *might* have changed (a shorter list than was shown earlier)
    $change_product_array = array ();
    $query = '
      SELECT
        p.product_id,
        p.product_name,
        p.unit_price,
        p.detailed_notes,
        pp.detailed_notes
      FROM
        '.TABLE_PRODUCT_PREP.' p,
        '.TABLE_PRODUCT_PREV.' pp
      WHERE
        p.product_id = pp.product_id
        AND p.changed = "1"
        AND p.product_name = pp.product_name
        AND p.unit_price = pp.unit_price
        AND p.pricing_unit = pp.pricing_unit
        AND p.ordering_unit = pp.ordering_unit
        AND p.detailed_notes = pp.detailed_notes
        AND p.meat_weight_type = pp.meat_weight_type
      GROUP BY
        product_id';

    $result = @mysql_query($query, $connection) or $error_array[7] = "SQL Error while retrieving potentially changed products!\n";
    while ($row = mysql_fetch_array($result))
      { // Only need the first row
        // Compare this list to see which were asked to be marked as "unchanged"
        $product_id = $row['product_id'];
        if ($_POST['product_'.$product_id] == "unchanged")
          {
            array_push ($change_product_array, "`p`.`product_id` = $product_id");
          }
      }
    $change_product_list = implode (" OR ", $change_product_array);
    if ($change_product_list == '')
      {
        // If there are no values, then make the "WHEN" condition to "1 = 0" so it is never true
        $change_product_list = '1 = 0';
      }

///                                                                          ///
///                                 STEP SIX                                 ///
///                                                                          ///

    $query_array[5] = '
      UPDATE
        '.TABLE_PRODUCT_PREP.' p
      SET
        p.changed = "0"
      WHERE
        '.$change_product_list;

///                                                                          ///
///                                   FINISH                                 ///
///                                                                          ///


    for ($step = 0; $step < 6; $step++ )
      {
        if ($error_array[$step] == '')
          {
            $error_array[$step] = '-- NO ERRORS --';
          }
        $content_prep .= '<font style="color:#a00000;">ERROR ['.$step.']: '.$error_array[$step].'</font><br>'."\n";
        $content_prep .= 'QUERY ['.$step.']: '.$query_array[$step].'<br><hr>'."\n";
      }

    if (count ($error_list) > 0)
      {
        die ("ERRORS WERE FOUND... EXECUTION STOPPED BEFORE IT BEGINS");
      }
    else
      {
        for ($step = 0; $step < 6; $step++ )
          {
            $result = @mysql_query($query_array[$step], $connection) or die ("PROCESS FAILED TO COMPLETE STEP $step!\n");
            // Give mysql a moment to catch it's breath
            $content_prep .= "STEP $step COMPLETED SUCCESSFULLY<br>\n";
            sleep (1);
          }
      }

    // Update the delivery codes to turn them on/off for this cycle.
    $query = '
      SELECT
        *
      FROM
        '.TABLE_DELCODE.'
      ORDER BY
        hub,
        delcode;';
    $result = mysql_query($query, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
    while ($row = mysql_fetch_object($result))
      {
        if ($row->inactive != $_POST[$row->delcode_id.'_inactive'])
          {
            $query2 = '
              UPDATE
                '.TABLE_DELCODE.'
              SET
                inactive = '.$_POST[$row->delcode_id.'_inactive'].'
              WHERE
                delcode_id = "'.$row->delcode_id.'"';
            $content_prep .= 'Updating activity value for '.$row->delcode_id.'.<br>';
            $null = mysql_query($query2, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
          }
      }
  }

if ($action == "Process and Make Live")
  {
    // Now make the new prep list live.
    // Make sure only confirmed products are copied to the product_list table
    if (REQ_PRDCR_CONFIRM)
      {
        $where_confirmed = 'WHERE t1.confirmed = "1"';
      }
    else
      {
        $where_confirmed = '';
      }
    $sqlprep = '
      CREATE TABLE '.TABLE_PRODUCT_TEMP.'
      SELECT t1.*
      FROM '.TABLE_PRODUCT_PREP.' AS t1
      '.$where_confirmed;
    $resultprep = @mysql_query($sqlprep,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultprep)
      {
        $message .= "New product list has been copied.<br>";
      }
    else
      {
        $message .= "New product list not copied. Notify the administrator of this error.";
      }
    $sqldrop = '
      DROP TABLE '.TABLE_PRODUCT;
    $resultdrop = @mysql_query($sqldrop,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultdrop)
      {
        $message .= "Old product list has been dropped.<br>";
      }
    else
      {
        $message .= "Old product list was not dropped. Notify the administrator of this error.<br>";
      }
    $sqlrename = '
      ALTER TABLE '.TABLE_PRODUCT_TEMP.'
      RENAME TO '.TABLE_PRODUCT;
    $resultrename = @mysql_query($sqlrename,$connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
    if($resultrename)
      {
        $message .= "New Product list has been renamed and the CHANGES ARE LIVE.";
      }
    else
      {
        $message .= "Product list was not rename, product list NOT UPDATED. Notify the administrator of this error.";
      }
  }

$content_prep .= '</div>';

$page_title_html = '<span class="title">Admin Maintenance</span>';
$page_subtitle_html = '<span class="subtitle">Prep Order Cycle</span>';
$page_title = 'Admin Maintenance: Prep Order Cycle';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_prep.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
