<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('site_admin,member_admin');

require_once("classes/mi.class.php");
$content_members .= '<div align="center">';
$mi = new memberInterface;
switch ( $_GET[action] )
  {
    case 'add':
      $mi->buildAddMember();
      break;
    case 'checkMemberForm':
      $error_html = $mi->checkMemberForm();
      if (strlen ($error_html) > 0)
        {
          $mi->editUser($error_html);
        }
      break;
    case 'edit':
      $mi->editUser();
      break;
    case 'find':
      $mi->findForm();
      break;
    case 'displayUsers':
      $mi->findUsers();
      break;
  }
if ( !$_GET[action] )
  {
    $content_members .=  '
      <ul>';
    switch ( $_GET[action] )
      {
        default:
          $mi->mainMenu();
          break;
      }
    $content_members .=  '</ul>
      ';
  }
$content_members .=  '</div>';

$page_title_html = '<span class="title">Membership Information</span>';
$page_subtitle_html = '<span class="subtitle">Find/Edit Members</span>';
$page_title = 'Membership Information: Find/Edit Members';
$page_tab = 'member_admin_panel';


include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_members.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");


