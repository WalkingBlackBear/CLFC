<?php
include_once ("config_foodcoop.php");
include_once ('general_functions.php');
session_start();
valid_auth('member_admin');


/******************************************
 * 
 * Display a list of currently "pending" members
 * from "members" table
 * where "pending = 1"
 * 
 ******************************************/
if ($_POST["activateMembers"] == "doIT") {
	// set pending to 0 for $memberID[] array
	$numToUpdate = count($_POST["memberID"]);
	$numUpdated = 0;
	$numProducers = 0;
	$display_admin .= '
    <img src="grfx/bottom.png" width="32" height="32" align="left" hspace="2" alt="Membership Information"><br>
    <h1>Activating Memberships ...</h1>
	';
	$increment = 0;
	foreach ($_POST["memberID"] as $id) {
//		$bizName = $_POST["businessName"][$increment];
//		echo "Business: $bizName<br />";
//UPDATE  `cloverbe_coop`.`members` SET  `pending` =  '0' WHERE  `members`.`member_id` =1001;
		$updateSql = "UPDATE members SET pending='0' WHERE member_id=$id";
//		echo $updateSql;
		$updateQuery = mysql_query($updateSql, $connection) or die(mysql_error());
		if (mysql_affected_rows()) {
			$numUpdated++;
			$display_admin .= "<p>Setting member #$id to active.</p>";
		}
		// if they're a producer, add them to the producer's table too
		// $id and $bizName are the two that need to go into "member_id" and "business_name" in the producers table
//		if ($_POST["isProducer"]) {
//			$prodQuery = "INSERT INTO producers (member_id, business_name) VALUES ('$id', '$bizName')";
//			$prodSql = mysql_query($prodQuery,$connection) or die(mysql_error());
//			if (mysql_affected_rows() > 0) {
//				$numProducers++;
//			}
//		}
		$increment++;
	}
	$display_admin .= "<p class='bold'>Updated $numUpdated of $numToUpdate members.</p>";
//	$display_admin .= "<p class='bold'>Added $numProducers producers as well.</p>";
} else {
	$pendingSql = "SELECT * FROM members WHERE pending=1";
	$pendingQuery = @mysql_query($pendingSql, $connection);
	if (mysql_num_rows($pendingQuery) > 0) {
		$display_admin .= '
	    <img src="grfx/bottom.png" width="32" height="32" align="left" hspace="2" alt="Membership Information"><br>
	    <b>Pending Memberships (Check and submit to activate)</b>
		';
		$display_admin .= "<form method='post' action='panel_member_pending.php'><br />";
		while ($pending = mysql_fetch_object($pendingQuery)) {
			$authTypes = explode(',',$pending->auth_type);
			$isProducer = FALSE;
			if (in_array('producer',$authTypes)) {
				$isProducer = TRUE;
			}
			$display_admin .= "<p> &mdash; <input type='checkbox' name='memberID[]' value='$pending->member_id' checked='checked' /> $pending->preferred_name ($pending->username)</p><br />
<!--			<input type='hidden' name='isProducer[]' value='$isProducer' />
			<input type='hidden' name='businessName[]' value='$pending->preferred_name' />-->
			";
		}
		$display_admin .= "
		<input type='hidden' name='activateMembers' value='doIT' />
		<input type='submit' name='submit' value='Activate member(s)' />
		</form>";
	} else {
		$display_admin .= "<h1>There are no pending memberships.</h1><br />";
	}
}
$page_title_html = '<span class="title">'.$_SESSION['show_name'].'</span>';
$page_subtitle_html = '<span class="subtitle">Pending Member Admin</span>';
$page_title = '- Pending Member Admin';
$page_tab = 'member_admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$display_admin.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
