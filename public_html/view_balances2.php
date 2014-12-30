<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,site_admin,cashier');

// See documentation for the auto-fill menu at http://api.jqueryui.com/autocomplete/
$display = '
  Begin typing to select an account: <input type="text" name="q" id="load_target" autocomplete="off" /><br>
    <table>
      <tr>
        <th></td>
        <th> product </th>
        <th> order </th>
        <th></td>
        <th> product </th>
        <th> order </th>
        <th></td>
        <th> product </th>
        <th> order </th>
        <td rowspan="3">
          <input type="button" name="Refresh" id="refresh_button" value="Refresh" onclick="get_ledger_info (document.getElementById(\'load_target\').value)">
        </td>
      </tr>
      <tr>
        <td class="for">Group customer fees&nbsp;with</td>
        <td><input type="radio" name="group_customer_fee_with" value="product"></td>
        <td><input type="radio" name="group_customer_fee_with" value="order" checked></td>
        <td class="for">Group random weight products&nbsp;with</td>
        <td><input type="radio" name="group_weight_cost_with" value="product" checked></td>
        <td><input type="radio" name="group_weight_cost_with" value="order"></td>
        <td class="for">Group extra charges&nbsp;with</td>
        <td><input type="radio" name="group_extra_charge_with" value="product" checked></td>
        <td><input type="radio" name="group_extra_charge_with" value="order"></td>
      </tr>
      <tr>
        <td class="for">Group producer fees&nbsp;with</td>
        <td><input type="radio" name="group_producer_fee_with" value="product"></td>
        <td><input type="radio" name="group_producer_fee_with" value="order" checked></td>
        <td class="for">Group regular products&nbsp;with</td>
        <td><input type="radio" name="group_each_cost_with" value="product" checked></td>
        <td><input type="radio" name="group_each_cost_with" value="order"></td>
        <td class="for">Group taxes&nbsp;with</td>
        <td><input type="radio" name="group_taxes_with" value="product"></td>
        <td><input type="radio" name="group_taxes_with" value="order" checked></td>
      </tr>
    </table>


    <div align="center">
    <div style="margin:0.5em;padding:0.5em;background-color:#ffe;width:50%;border:1px solid #fda;font-size:140%">
      '.$member_name.'
    </div>
    <div style="width:95%;height:450px;overflow-y:scroll;border:1px solid black" id="content_area">
  <table class="ledger">
    <tr id="ledger_header">
      <th>Basket ID</th>
      <th>Date / Time</th>
      <th>To / From</th>
      <th>For</th>
      <th>Qty</th>
      <th>Description</th>
      <th></th>
      <th>Amt.</th>
      <th>Bal.</th>
    </tr>
    <tr id="pre_insertion_point">
      <td colspan="9"></td>
    </tr>
    <tr id="post_insertion_point">
      <td colspan="9"></td>
    </tr>
  </table>
    </div>
    <br />
    </div>';

$page_specific_javascript = '
<script type="text/javascript" src="/shop/ajax/jquery.autocomplete.js"></script>
<script type="text/javascript">
// Information on this autocomplete script: http://www.devbridge.com/projects/autocomplete/jquery/
  var options, a;
  jQuery(function(){
    options = {
      serviceUrl:"'.PATH.'ajax/get_account_hint.php'.'",
      minChars:2,
      // delimiter: /(,|;)\s*/, // regex or character
      maxHeight:400,
      width:400,
      zIndex: 9999,
      deferRequestBy: 500,
      // params: { country:"Yes" }, //aditional parameters
      params: { action:"get_account_hint"},
      // noCache: false, //default is false, set to true to disable caching
      onSelect: function(value, data){ document.getElementById("load_target").value=data; get_ledger_info (data); } // callback function
      // lookup: ["January", "February", "March", "April", "May"] // local lookup values
      };
    a = $("#load_target").autocomplete(options);
  });

function get_ledger_info (account_spec) {
  var group_customer_fee = "";
  var group_producer_fee = "";
  var group_customer_fee_with = $("input[name=\'group_customer_fee_with\']:checked").val();
  var group_producer_fee_with = $("input[name=\'group_producer_fee_with\']:checked").val();
  var group_weight_cost_with = $("input[name=\'group_weight_cost_with\']:checked").val();
  var group_each_cost_with = $("input[name=\'group_each_cost_with\']:checked").val();
  var group_extra_charge_with = $("input[name=\'group_extra_charge_with\']:checked").val();
  var group_taxes_with = $("input[name=\'group_taxes_with\']:checked").val();
  if (account_spec == "null") { account_spec = documentGetElementById("load_target"); }
  $.post("'.PATH.'ajax/get_ledger_info.php'.'", {
    action:"get_ledger_info",
    account_spec:account_spec,
    group_customer_fee_with:group_customer_fee_with,
    group_producer_fee_with:group_producer_fee_with,
    group_weight_cost_with:group_weight_cost_with,
    group_each_cost_with:group_each_cost_with,
    group_extra_charge_with:group_extra_charge_with,
    group_taxes_with:group_taxes_with
    },
  function(account_data) {
    // Taken together, the next two lines will replace the table content
    var ledger_header = document.getElementById("ledger_header");
    document.getElementById("content_area").innerHTML = "<table class=\"ledger\"><tr id=\"ledger_header\"><th>Basket ID</th><th>Date / Time</th><th>To / From</th><th>For</th><th>Qty</th><th>Description</th><th></th><th>Amt.</th><th>Bal.</th></tr>"+account_data+"</table>";
    // document.getElementById("content_area").innerHTML = account_data;
    // The next line does a pre-insertion
    // document.getElementById("pre_insertion_point").outerHTML = account_data;
    });
  }

function show_hide_detail (target, operation) {
  var target;
  var operation;
  if (operation == "more") {
    $("."+target).removeClass("hid");
    $("."+target).addClass("detail");
    return("less");
    }
  else {
    $("."+target).addClass("hid");
    return("more");
    }
  }

</script>';

$page_specific_css = '
  <style type="text/css">
  #load_target {width:40%;}
  .autocomplete-w1 { background:url(/shop/grfx/shadow.png) no-repeat bottom right; position:absolute; top:0px; left:0px; margin:6px 0 0 6px; /* IE6 fix: */ _background:none; _margin:1px 0 0 0; }
  .autocomplete { border:1px solid #999; background:#fff; cursor:default; text-align:left; max-height:350px; overflow:auto; margin:-6px 6px 6px -6px; /* IE6 specific: */ _height:350px;  _margin:0; _overflow-x:hidden; }
  .autocomplete .selected { background:#f0f0f0; }
  .autocomplete div { padding:2px 5px; white-space:nowrap; overflow:hidden; }
  .autocomplete strong { font-weight:normal; color:#007;text-decoration:underline; }

  table.ledger {width:100%;border-spacing:0;border-collapse:collapse;}
  .ledger tr td {background-color:#eed;margin:0;font-size:80%;padding:1px 5px;}
  .ledger tr.product_summary td {border-bottom:1px solid #997}
  .ledger tr.order_summary td {border-bottom:1px solid #997}
  .ledger tr.detail td {border-bottom:1px solid #ccb}
  #ledger_header {background-color:#540;color:#ffe;}
  .hid {display:none}
  .more_less {font-size:90%;color:#630;cursor:pointer;padding:0;margin:0;}
  .scope, .timestamp, .text_key, .more_less {text-align:center;}
  .amount, .balance {text-align:right;}
  tr.row_sep td {background-color:#a70;max-height:1px;border:0;}
  tr.order_summary td {background-color:#ddb;}
  td.for {text-align:right;padding-left:2em;}
  #refresh_button {background-color:#876;color:#ffe;padding:0.6em 1em 0.5em;margin-left:3em;}
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Member Balances Lookup</span>';
$page_title = 'Reports: Member Balances Lookup';
$page_tab = 'cashier_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
