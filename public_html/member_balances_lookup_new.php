<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('member_admin,site_admin,cashier');

include("../func/member_balance_function_new.php");

if ( $_POST['member_id'] )
  {
    $member_id = preg_replace('/[^0-9]/', '', $_POST['member_id']);
  }
elseif ( $_REQUEST['m'] )
  {
    $member_id = preg_replace('/[^0-9]/', '', $_REQUEST['m']);
  }
$display = getMemberBalance($member_id, ActiveCycle::delivery_id(), 'display');

$page_specific_css = '
  <style type="text/css">
  table, td, th {
    border: 1px solid #CCCCCC;
    }
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
