<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');

//                                                                           //
// This script will add a table of producer totals to be used at the bottom  //
// of producer invoices.  It requires values for these variables to already  //
// be set:                                                                   //
//                 $total                                                    //
//                 $message_incomplete                                       //
//                 $current_delivery_id                                      //
//                 $producer_id_you                                          //
//                                                                           //

// If we were sent finalization information, then post it to the database...
if ( $_POST['submit'] == 'Store Producer Totals' )
  {
    include("../members/producer_finalize.php");
    producer_finalize::finalize($_POST);
    $message = '<h3>The information has been saved.</h3>';
  }

$subtotal = round($total + 0.00000001,2);
if ( $message_incomplete ) 
  {
    $subtotal_display = $message_incomplete;
  }
else
  {
    $subtotal_display = number_format($subtotal - $total_extra, 2);
  }

$producer_fee = round((($subtotal - $total_extra) * $producer_markdown) + 0.00000001,2);
$fee = (100 * $producer_markdown).'%';

// This was originally to show adjustments on the producer invoice. May omit, or at least change to ledger

// $query = '
//   SELECT
//     transaction_name,
//     transaction_comments,
//     transaction_amount
//   FROM
//     '.TABLE_TRANSACTIONS.' AS t,
//     '.TABLE_TRANS_TYPES.' AS tt  
//   WHERE
//     transaction_delivery_id = '.mysql_real_escape_string ($delivery_id).'
//     AND transaction_producer_id = "'.mysql_real_escape_string ($producer_id).'"
//     AND t.transaction_type = tt.ttype_id 
//     AND tt.ttype_parent = 20
//     AND t.transaction_taxed = 0';
// $sqla = mysql_query($query, $connection) or die(debug_print ("ERROR: 434463 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
// $adjustment_display = '';
// $adjustment_header = '
//       <tr>
//         <td colspan="4" bgcolor="#dddddd" align="center"><font size=4>Adjustments</font></td>
//       </tr>';
// while ( $resulta = mysql_fetch_array($sqla) )
//   {
//     $adjustment_display .= '
//       <tr>
//         <td align="left" valign="top" width="25%">'.$resulta['transaction_name'].'</td>
//         <td align="left" valign="top" colspan="2" width="50%">'.$resulta['transaction_comments'].'</td>
//         <td align="right" valign="top" width="25%">$'.number_format($resulta['transaction_amount'], 2).'</td>
//       </tr>';
//     $subtotal_pr = $subtotal_pr + $resulta['transaction_amount'];
//     $total2 = $total2 + $resulta['transaction_amount'];
//   }

$final_total = $subtotal + $total2 - $producer_fee;

// Only allow producer_admins and site_admins to access the finalization button
if (!$display_only && CurrentMember::auth_type('producer_admin,site_admin'))
  {
    $finalize_button_code = '
            <div align="center">
              <form method="POST" action="'.$_SERVER['PHP_SELF'].'?delivery_id='.$delivery_id.'&producer_id='.$producer_id.'">
              <input type="hidden" name="producer_id" value="'.$producer_id.'">
              <input type="hidden" name="delivery_id" value="'.$delivery_id.'">
              <input type="hidden" name="transaction_amount[28]" value="'.number_format($subtotal, 2, '.', '').'">
              <input type="hidden" name="transaction_amount[31]" value="'.number_format($producer_fee, 2, '.', '').'">
              <input type="hidden" name="transaction_amount[35]" value="'.number_format($final_total, 2, '.', '').'">
              <input type="submit" name="submit" value="Store Producer Totals">
              </form>
            </div>
          ';
  }
else
  {
    $finalize_button_code = '';
  }



if ( $message_incomplete )
  {
    $producer_orders_totals = '
      <table border="0" cellpadding="2" cellspacing="0" width="100%">
        '.($adjustment_display? $adjustment_header.$adjustment_display : '').'
        <tr>
          <td colspan="4"height="1" width="100%"><hr noshade size="1"></td>
        </tr>
        <tr>
          <td colspan="2" rowspan="5" width="50%">&nbsp;</td>
          <td align="right" width="25%">Product Total</td>
          <td rowspan="5" valign="middle" align="center" width="25%" bgcolor="#eeeeee">'.$message_incomplete.'</td>
        </tr>
        <tr>
          <td align="right" width="25%"><b>'.$fee.' Coop Fee</b></td>
        </tr>
        <tr>
          <td align="right" width="25%">Total Extra Charges</td>
        </tr>
        <tr>
          <td align="right" width="25%">Adjustments</td>
        </tr>
        <tr>
          <td align="right" width="25%"><b>TOTAL with Coop Fee</b></td>
        </tr>
      </table>';
  }
else
  {
    $producer_orders_totals = '
      <table border="0" cellpadding="2" cellspacing="0" width="100%">
        '.($adjustment_display? $adjustment_header.$adjustment_display : '').'
        <tr>
          <td colspan="4"height="1" width="100%"><hr noshade size="1"></td>
        </tr>
        <tr>
          <td colspan="2" width="50%">&nbsp;</td>
          <td align="right" width="25%">Product Total</td>
          <td align="right" width="25%">$ '.number_format ($subtotal - $total_extra, 2).'</td>
        </tr>
        <tr>
          <td colspan="2" width="50%">&nbsp;</td>
          <td align="right" width="25%">'.$fee.' Coop Fee</td>
          <td align="right" width="25%">$ '.number_format ($producer_fee * -1, 2).'</td>
        </tr>
        <tr>
          <td colspan="2" width="50%">&nbsp;</td>
          <td align="right" width="25%">Total Extra Charges</td>
          <td align="right" width="25%">$ '.number_format ($total_extra, 2).'</td>
        </tr>
        <tr>
          <td colspan="2" width="50%">&nbsp;</td>
          <td align="right" width="25%">Adjustments</td>
          <td align="right" width="25%">$ '.number_format ($total2, 2).'</td>
        </tr>
        <tr>
          <td colspan="2" width="50%"></td>
          <td colspan="2"height="1"><hr noshade size="1"></td>
        </tr>
        <tr>
          <td colspan="2" width="50%">&nbsp;</td>
          <td align="right" width="25%"><b>TOTAL with Coop Fee</b></td>
          <td align="right" width="25%"><b>'.number_format ($final_total, 2).'</b></td>
        </tr>
        <tr>
          <td colspan="4" width="100%">&nbsp;'.$finalize_button_code.'</td>
        </tr>
      </table>';
  }

?>
