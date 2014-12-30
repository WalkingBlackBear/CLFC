<?php
  function startPage() {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
    	<meta charset="utf-8">
    	<title>CLFC Membership Report</title>
    	<link rel="stylesheet" href="css/style.css">
    	<!--[if IE]>
    		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    	<![endif]-->
    </head>
    
    <body id="home">
      <center><img src="http://cloverbeltlocalfoodcoop.com/clfc/wp-content/uploads/2014/11/CLFC-Logo-2014.png" width="600" alt="CLFC Logo" /></center>
    	<h1 style="text-align: center; margin-top: -20px">Membership List</h1>
    	<center>
    ';
  }
  function tableHead() {
    echo '
    	<table border="1" cellpadding=5>
    	<tr style="font-weight: bold">
      	<td>Member ID</td><td>Member Name</td><td>Member Type</td><td>Member since</td>
      </tr>
    ';
  }
  function getMemberData() {
    try {
      $dsn = 'mysql:host=localhost;dbname=cloverbe_coop';
      $username = 'cloverbe_coop';
      $password = 'LocalFoodC00p';
      $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',); 
      $dbh = new PDO($dsn, $username, $password, $options);
      
      // gives membership within date range
      $sql = "SELECT `member_id`,`preferred_name`,`membership_type_id`,`membership_date` 
              FROM  `members` 
              WHERE  `membership_date` >= :sdate AND 
              `membership_date` <=  :edate 
              ORDER BY `member_id` ASC";
      $memberData = $dbh->prepare($sql);
      if ($_POST['startDate'] != "0000-00-00") {
        $memberData->execute(array(':sdate' => $_POST['startDate'],
                              ':edate' => $_POST['endDate']));
      } else { // default to all since beginning of co-op
        $currentDate = date('n-j-Y');
        $memberData->execute(array(':sdate' => '0000-00-00',
                              ':edate' => $currentDate));
      }
      $memberData->setFetchMode(PDO::FETCH_OBJ);
  
      while ($row = $memberData->fetch()) {
        echo '<tr>';
        echo '<td>';
          echo $row->member_id;
        echo '</td>';
        echo '<td>';
          echo $row->preferred_name;
        echo '</td>';
        echo '<td>';
          switch($row->membership_type_id) {
            case 1:
              echo "Consumer";
            break;
            case 2:
              echo "Producer";
            break;
            case 3:
              echo "Institutional";
            break;
          }
        echo '</td>';
        echo '<td>';
          echo $row->membership_date;
        echo '</td>';
        echo '</tr>';
      }
    } catch (PDOException $sqerr) {
      die ("Error: ".$sqerr->getMessage());
    }
  }
  function tableFoot() {
    echo '
    </table>
    </center>
    ';
  }
  function endPage() {
    echo '
    </body>
    </html>
    ';
  }
  function datePicker() {
    $currentDate = date('Y-m-d');
    echo '<div style="text-align: center">';
      echo '<h1>Select a membership date range</h1>';
      echo '<h3 style="margin-top:-20px">(defaults to view ALL members)</h3>';
      echo '<form method="post" action="makeMembershipList.php">';
      echo '<label for="startDate">Start date: (YYYY-MM-DD) </label><input type="date" name="startDate" id="startDate" value="2013-10-01" /><br />';
      echo '<label for="endDate">End date: (YYYY-MM-DD) </label><input type="date" name="endDate" id="endDate" value="'.$currentDate.'" /><br /><br />';
      echo '<input type="submit" value="Create list" style="padding: 20px; font-size: 2.5em" />';
      echo '</form>';
    echo '</div>';
  }
  startPage();
  if (isset($_POST['startDate'])) { // user has already chosen dates, display list
    tableHead();
    getMemberData();
    tableFoot();
    datePicker();
  } else { // give user a choice to select a date range
    datePicker();
  }
  endPage();
?>