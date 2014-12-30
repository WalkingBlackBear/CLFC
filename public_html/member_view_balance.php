<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member');

include_once ('ajax/display_ledger.php');

if ($_SESSION['producer_id_you']) $producer_id_you = $_SESSION['producer_id_you'];
if ($_SESSION['member_id']) $member_id = $_SESSION['member_id'];

$delivery_id = ActiveCycle::delivery_id();
if ($_GET['delivery_id']) $delivery_id = $_GET['delivery_id'];

// Since we are calling get_ledger_info.php by inclusion (instead of POST)...
// Need to set the post variables manually
if ($_GET['account_type'] == 'producer' && $producer_id_you) $account_spec = 'producer:'.$producer_id_you;
elseif ($_GET['account_type'] == 'member' && $member_id) $account_spec = 'member:'.$member_id;

// Get the delivery_date for this order
$query = '
  SELECT delivery_date
  FROM '.TABLE_ORDER_CYCLES.'
  WHERE delivery_id = "'.mysql_real_escape_string($delivery_id).'"';
$result = mysql_query($query, $connection) or die(debug_print ("ERROR: 678093 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
if ($row = mysql_fetch_array($result))
  {
    $delivery_date = $row['delivery_date'];
  }

if ($account_spec)
  {
    $display .= '
    <div id="main_content" class="clear">
      <div id="content_area">
        <div id="working_area">
        </div>
        <div id="ledger_container">
          <table id="ledger" class="ledger">
            <thead id="ledger_head">
            </thead>
            <tbody id="ledger_body">';
    $display .= get_ledger_header_markup ();
    $display .= get_ledger_body (array (
      'account_spec' => $account_spec,
      'delivery_id' => $delivery_id
      ));
    $display .= '
            </tbody>
          </table>
        </div>
      </div>
      <br />
    </div>';
  }
else
  {
    $display .= "No information to display";
  }

$page_specific_javascript = '
<script type="text/javascript">
// Expand or contract the detail listings by adding or removing the "hidden" class
function show_hide_detail (target, operation) {
  var target;
  var operation;
  if (operation == "show") {
    $("."+target).removeClass("hid");
    $("."+target).addClass("detail");
    return("hide");
    }
  else {
    $("."+target).addClass("hid");
    return("show");
    }
  }
</script>';

$page_specific_css = '
  <style type="text/css">
  /* #load_target {
    width:40%;
    } */
/* Do not show these columns on the regular member listing */
td.control,
td.scope,
th.control,
th.scope {
  display:none;
  }

  .autocomplete-w1 {
     background:url(/shop/grfx/shadow.png) no-repeat bottom right;
     position:absolute;
     top:0px;
     left:0px;
     margin:6px 0 0 6px;
     /* IE6 fix: */ _background:none;
     _margin:1px 0 0 0;
     }
  .autocomplete {
     border:1px solid #999;
     background:#fff;
     cursor:default;
     text-align:left;
     max-height:350px;
     overflow:auto;
     margin:-6px 6px 6px -6px;
     /* IE6 specific: */ _height:350px;
      _margin:0;
     _overflow-x:hidden;
     }
  .autocomplete .selected {
     background:#f0f0f0;
     }
  .autocomplete div {
     padding:2px 5px;
     white-space:nowrap;
     overflow:hidden;
     }
  .autocomplete strong {
     font-weight:normal;
     color:#007;
    text-decoration:underline;
     }
  table.ledger {
    width:100%;
    border-spacing:0;
    border-collapse:collapse;
    }
  .ledger tr td {
    background-color:#eed;
    margin:0;
    font-size:80%;
    padding:1px 5px;
    }
  .ledger tr.detail td {
    border-bottom:1px solid #ccb
    }
  .ledger tr.detail td.amount {
    border-right:1px solid #ccb
    }
  .ledger tr.summary_row td {
    border-bottom:1px solid #997
    }
  .ledger tr.extra_row td {
    background-color:#bb9;
    border-bottom:1px solid #997
    }
  .ledger tr.summary_delivery_id td,
  .ledger tr.singleton_delivery_id td {
    background-color:#ddb;
    border-bottom:1px solid #997
    }
  .ledger_header {
    background-color:#540;
    color:#ffe;
    text-align:left;
    }
  .hid {
    display:none
    }
  .more_less {
    font-size:90%;
    color:#630;
    cursor:pointer;
    padding:0;
    margin:0;
    }
  .scope, .timestamp, .text_key, .more_less {
    text-align:center;
    }
  .amount, .balance {
    text-align:right;
    }
  tr.row_sep td {
    background-color:#a70;
    max-height:1px;
    border:0;
    }
  tr.order_summary td {
    background-color:#ddb;
    }
  td.for {
    text-align:right;
    padding-left:2em;
    }
  #editor {
    border: 3px solid #360;
    width:100%;
    z-index:200;
    }
  #main_content {
    margin:0px auto;
    }
  #ledger_container {
    margin:0 auto;
    max-height:450px;
    overflow-y:auto;
    }
  #content_area {
    width:95%;
    margin:0 auto;
    } 
  #working_area {
    background-color:#fff;
    width:89.7%;
    max-height:0;
    overflow:hidden;
    position:absolute;
    -moz-transition: max-height 1s ease;
    -ms-transition: max-height 1s ease;  
    -o-transition: max-height 1s ease;  
    transition: max-height 1s ease;  
    -webkit-transition: max-height 1s ease;  
    }
  #working_area.open {
    width:89.7%;
    margin:0 auto;
    max-height:400px;
    overflow:auto;
    }
  fieldset {
    width:0;
    float:left;
    border:0;
    margin:0;
    padding:0 3px;
    }
  input[type=text] {
    }
  label.text_label {
    display:block;
    float:left;
    font-size:0.8em;
    }
  label {
    white-space:nowrap;
    }
  div.label_holder {
    width:0;
    position:relative;
    bottom:1em;
    font-size:0.8em;
    margin-left:1em;
    float:left;
    margin-top:1em;
    }
  .clear {
    clear:left;
    }
  .throbber {
    width:50px;
    height:50px;
    margin: 10px 200px;
    }
  .secondary {
    text-align:right;
    background-color:transparent;
    padding-right:0.5em;
    font-family:verdana;
    }
  .editor {
    width:100%;
    background-color:#ded;
    border:1px solid #750;
    }
  .editor td {
    padding:0;
    margin:0;
    }
  .control {
    cursor:pointer;
    }
  input[type=button] {
    width:120px;
    margin:5px 20px 0;
    text-align:center;
    color:#ffe;
    background-color:#786;
    padding:0.3em 1em 0.1em;
    cursor:pointer;
    border-top:2px solid #ddd;
    border-left:2px solid #ddd;
    border-bottom:2px solid #666;
    border-right:2px solid #666;
    font-family:verdana;
    vertical-align:middle;
    }
  input[type=button]:hover {
    background-color:#675;
    color:#fff;
    border-right:2px solid #444;
    border-bottom:2px solid #444;
    }
  input[type=button]:active {
    background-color:#786;
    border-right:2px solid #ddd;
    border-bottom:2px solid #ddd;
    }
  #customer_message {
    width:300px;
    height:40px;
    }

  #ad_hoc_source,
  #ad_hoc_target,
  #load_target,
  #delivery_date {
    width:200px;
    }
  #edit_source_spec,
  #edit_target_spec,
  #load_spec {
    width:100px;
    }
  #edit_ledger_message {
    width:300px;
    }
  .pad_left {
    padding-left:2em;
    }
  .close_icon {
    display:block;
    float:right;
    margin:0 2px;
    padding:0 2px;
    color:#fff;
    border:1px solid #fff;
    cursor:pointer;
    }
  textarea {
    width:300px;
    height:50px;
  </style>';

$page_title_html = '<span class="title">Reports</span>';
$page_subtitle_html = '<span class="subtitle">Member Balance ('.date('F j, Y', strtotime($delivery_date)).')</span>';
$page_title = 'Reports: Member Balance - '.date('Y-m-d', strtotime($delivery_date));
$page_tab = 'member_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->'.
  $display.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
?>