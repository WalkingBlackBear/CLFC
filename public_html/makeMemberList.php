<?php
  $currentDate = date('Y-m-d');

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
      	<td>Member ID</td><td>Member Name</td><td>Member Type</td><td>City</td><td>Member since</td><td>Status</td>
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
      
      // create query
      // columns to select
      $sql = "SELECT `member_id`,`preferred_name`,`membership_type_id`,`membership_date`,`city`,`pending`,`how_heard_id` ";
      // table to select from
      $sql .= "FROM  `members` WHERE ";

      // criteria for what rows to select
      // by start and end date --> use these for ALL queries  Start all optional sectiosn with AND
      $sql .= "`membership_date` >= :sdate AND 
              `membership_date` <=  :edate ";
      $placeholders[':sdate'] = $_GET['startDate'];
      $placeholders[':edate'] = $_GET['endDate'];
      if ($_GET['sortCity'] != "---") {
        // by location
        $sql .= "AND `city` = :city ";
        $placeholders[':city'] = $_GET['sortCity'];
      }
      if (isset($_GET['pendingStatus'])) {
        // by pending (0=confirmed 1=pending)
        $sql .= "AND `pending` = :pending ";
        $placeholders[':pending'] = $_GET['pendingStatus'];
      }
      if (isset($_GET['membershipType'])) {
        // by membership type (1=consumer 2=producer 3=institutional)
        $sql .= "AND `membership_type_id` = :memType ";
        $placeholders[':memType'] = $_GET['membershipType'];
      }
      
      // set how we're sorting the results
      if ($_GET['sortBy'] == "city") {
        // by city
        $sql .= "ORDER BY `city`";
      } elseif ($_GET['sortBy'] == "memType") {
        // by membership_type_id ASC
        $sql .= "ORDER BY `membership_type_id`";
      } elseif ($_GET['sortBy'] == "pending") {
        // by pending
        $sql .= "ORDER BY `pending`";
      } else { // default to member #
        // by member ID#
        $sql .= "ORDER BY `member_id`";
      }
      // ASC or DSC?
      switch ($_GET['sortDir']) {
        case "dsc":
          $sql .= " DESC";
        break;
        default:
          $sql .= " ASC";
        break;
      }
//echo $sql."<br />";
      // prepare the completed statement in $sql
      $memberData = $dbh->prepare($sql);
      
      // fill :*** placeholders in prepared statement ($sql)
      // :sdate :edate :city :pending :memType
/*      $memberData->execute(array(':sdate' => $_GET['startDate'],
                                ':edate' => $_GET['endDate'],
                                ':city' => $_GET['sortCity'],
                                ':pending' => $_GET['memPending'],
                                ':memType' => $_GET['memType']));*/
      $memberData->execute($placeholders);
//var_dump($placeholders);
      // sql statement is prepared, and values entered, perform query
      $memberData->setFetchMode(PDO::FETCH_OBJ);
      $totalConsumers = 0;
      $totalProducers = 0;
      $totalInstitutional = 0;
  
      if ($row = $memberData->fetch()) {
        do {
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
                $totalConsumers++;
              break;
              case 2:
                echo "Producer";
                $totalProducers++;
              break;
              case 3:
                echo "Institutional";
                $totalInstitutional++;
              break;
            }
          echo '</td>';
          echo '<td>';
            echo $row->city;
          echo '</td>';
          echo '<td>';
            echo $row->membership_date;
          echo '</td>';
          echo '<td>';
            switch($row->pending) {
              case 0:
                echo "Active";
              break;
              case 1:
                echo "PENDING";
              break;
            }
            if (isset($_GET['howHeard'])) {
              switch($row->how_heard_id) {
                case 1:
                  echo " (RADIO AD)";
                break;
                case 2:
                  echo " (NEWSPAPER)";
                break;
                case 3:
                  echo " (WEBSITE)";
                break;
                case 4:
                  echo " (FRIEND)";
                break;
                case 5:
                  echo " (FLYER)";
                break;
                case 6:
                  echo " (PRODUCER)";
                break;
                case 7:
                  echo " (MEMBER)";
                break;
                case 60:
                  echo " (OTHER)";
                break;
                case 60:
                  echo " (FAMILY)";
                break;
                case 60:
                  echo " (FACEBOOK)";
                break;
                case 60:
                  echo " (TWITTER)";
                break;
                case 60:
                  echo " (CKDR.NET)";
                break;
                case 60:
                  echo " (COMMUNITY EVENT)";
                break;
                case 60:
                  echo " (CLASSROOM VISIT)";
                break;
                default:
                  echo " (Referral undefined)";
                break;
              }
            }
          echo '</td>';
          echo '</tr>';
        } while ($row = $memberData->fetch());
        echo '<tr><td colspan="2" style="font-weight: bold;text-align: right">Total consumers this report:</td><td colspan="4">'.$totalConsumers.'</td></tr>';
        echo '<tr><td colspan="2" style="font-weight: bold;text-align: right">Total producers this report:</td><td colspan="4">'.$totalProducers.'</td></tr>';
        echo '<tr><td colspan="2" style="font-weight: bold;text-align: right">Total institutions this report:</td><td colspan="4">'.$totalInstitutional.'</td></tr>';
      } else {
        echo '<tr><td colspan="6"><h2 style="text-align: center">No results found.</h4></td></tr>';
      }
    } catch (PDOException $sqerr) {
      die ("Error: ".$sqerr->getMessage());
    }
  }
  function getCityList() {
    try {
      $dsn = 'mysql:host=localhost;dbname=cloverbe_coop';
      $username = 'cloverbe_coop';
      $password = 'LocalFoodC00p';
      $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',); 
      $dbh = new PDO($dsn, $username, $password, $options);
      
      // create query
      // columns to select
      $sql = "SELECT DISTINCT `city` FROM `members` ORDER BY `city` ASC";
      // prepare the completed statement in $sql
      $cityData = $dbh->prepare($sql);
      $cityData->execute();
      $cityData->setFetchMode(PDO::FETCH_OBJ);
      
      // create option list
      $cityList = "<br /><label for='city'>City: </label><select name='sortCity' id='sortCity' required><option value='---'>(optional)</option>";
      while ($row = $cityData->fetch()) {
        $cityList .= '<option value="'.$row->city.'">'.$row->city.'</option>';
      }
      $cityList .= "</select><br />";
      return $cityList;
    } catch (PDOException $sqerr) {
      die ("Error: ".$sqerr->getMessage());
    }
  }
  
  function tableFoot() {
    echo '
    </table>
    <hr />
    </center>
    ';
  }
  function endPage() {
    echo '
    </body>
    </html>
    ';
  }
  function optionPicker() {
    /**********************************************
     * Select membership date range, field options,
     * and sorting options for membership list
     * ********************************************/
    global $currentDate;
    
    echo '<div style="text-align: center">';
      echo '<h1>Select a membership date range</h1>';
      echo '<h3 style="margin-top:-20px">(defaults to view ALL members)</h3>';
      echo '<form method="get" action="makeMemberList.php">';
      echo '<fieldset><legend>Date range:</legend>';
        echo '<label for="startDate">Start date: (YYYY-MM-DD) </label><input type="date" name="startDate" id="startDate" value="2013-10-01" /><br />';
        echo '<label for="endDate">End date: (YYYY-MM-DD) </label><input type="date" name="endDate" id="endDate" value="'.$currentDate.'" /><br />';
        echo '</fieldset>';
        echo '<fieldset><legend>Limit list to:</legend>';
        echo '<input type="radio" name="pendingStatus" value="1"> Pending Only &mdash; ';
        echo '<input type="radio" name="pendingStatus" value="0"> Active Only<br />';
        echo '<input type="radio" name="membershipType" value="1"> Consumers Only &mdash; ';
        echo '<input type="radio" name="membershipType" value="2"> Producers Only &mdash; ';
        echo '<input type="radio" name="membershipType" value="3"> Institutional Only';
        echo getCityList();
      echo '</fieldset>';
      echo '<fieldset><legend>Sort list by:</legend>';
        echo '<input type="radio" name="sortBy" value="id" checked> Member Number &mdash; ';
        echo '<input type="radio" name="sortBy" value="memType"> Membership Type &mdash; ';
        echo '<input type="radio" name="sortBy" value="city"> City &mdash; ';
        echo '<input type="radio" name="sortBy" value="pending"> Pending<br />';
        echo '<input type="radio" name="sortDir" value="asc" checked> A&rarr;Z ';
        echo '<input type="radio" name="sortDir" value="dsc"> Z&rarr;A';
      echo '</fieldset>';
      echo '<fieldset><legend>Optional info:</legend>';
        echo '<input type="checkbox" name="howHeard" value="Referrals"> Include how they heard about us?';
      echo '</fieldset>';
      echo '<input type="submit" value="Create list" style="padding: 20px; font-size: 2.5em" />';
      echo '</form>';
    echo '</div>';
  }
  startPage();
  if (isset($_GET['startDate'])) { // user has already chosen dates, display list
    tableHead();
    getMemberData();
    tableFoot();
    optionPicker();
  } else { // give user a choice to select a date range
    optionPicker();
  }
  endPage();
?>