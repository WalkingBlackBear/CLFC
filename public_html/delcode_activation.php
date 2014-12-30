<?php
include_once ("config_foodcoop.php");
include_once ("general_functions.php");
session_start();
valid_auth('route_admin');


$action = $_POST['action'];
$content .= '<div style="padding:1em;">';


////////////////////////////////////////////////////////////////////////////////
///                                                                          ///
///                        BEGIN PROCESSING SUBMITTED PAGE                   ///
///                                                                          ///
////////////////////////////////////////////////////////////////////////////////

if ($action == "Update")
  {

    // Update the delivery codes to turn them on/off.
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
                inactive = '.mysql_real_escape_string ($_POST[$row->delcode_id.'_inactive']).'
              WHERE
                delcode_id = "'.mysql_real_escape_string ($row->delcode_id).'"';
            $null = mysql_query($query2, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
          }
      }
  }

// Always show the form...
$content .= '
  <div align="center"><h3>Activate Delivery Locations</h3></div>
  <p>Use the following form to change which delivery sites are available.  &quot;Standby&quot; is used for sites that are not available for this order cycle,
  but remain available on the locations page to indicate they are normally in the service area.</p>
  <form action="'.$_SERVER['PHP_SELF'].'" method="POST">';

$query = '
  SELECT
    *
  FROM
    '.TABLE_DELCODE.'
  ORDER BY
    hub,
    delcode;';
$result = mysql_query($query, $connection) or die('<br><br>Whoops! You found a bug. If there is an error listed below, please copy and paste the error into an email to <a href="mailto:webmaster@'.$domainname.'">webmaster@'.$domainname.'</a><br><br><b>Error:</b> Current Delivery Cycle ' . mysql_error() . '<br><b>Error No: </b>' . mysql_errno());
$content .= '
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
    $content .= '
      <tr style="background-color:'.$inactive_color.';">
        <td>'.$row->delcode_id.'</td>
        <td>'.$row->delcode.'</td>
        <td>'.$deltype_display.'</td>
        <td>'.$row->deldesc.'</td>
        <td>'.$row->hub.'</td>
        <td><select name="'.$row->delcode_id.'_inactive">'.$inactive_select.'</select></td>
      </tr>';
  }

$content .= '
  </table>
  <br>
  <table border="0" width="100%">
    <tr>
      <td width="33%" align="center"><input type="submit" name="action" value="Update"></td>
      <td width="33%" align="center"><input type="reset"></td>
    </tr>
  </table>
  </form>
  <hr>';

$content .= '</div>';

$page_title_html = '<span class="title">Route Information</span>';
$page_subtitle_html = '<span class="subtitle">Activate Delivery Locations</span>';
$page_title = 'Route Information: Activate Delivery Locations';
$page_tab = 'route_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");?>
