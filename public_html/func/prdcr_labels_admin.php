<?php

function prdcr_contact_info($start, $half)
  {
    global $connection;
    $query = '
      SELECT
        '.TABLE_PRODUCER.'.producer_id,
        '.TABLE_PRODUCER.'.business_name,
        '.TABLE_PRODUCER.'.member_id,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.first_name,
        '.TABLE_MEMBER.'.last_name,
        '.TABLE_MEMBER.'.address_line1,
        '.TABLE_MEMBER.'.address_line2,
        '.TABLE_MEMBER.'.city,
        '.TABLE_MEMBER.'.state,
        '.TABLE_MEMBER.'.zip,
        '.TABLE_PRODUCER.'.unlisted_producer
      FROM
        '.TABLE_PRODUCER.',
        '.TABLE_MEMBER.'
      WHERE
        '.TABLE_PRODUCER.'.member_id = '.TABLE_MEMBER.'.member_id
        AND '.TABLE_PRODUCER.'.unlisted_producer = "0"
        AND '.TABLE_MEMBER.'.membership_discontinued != "1"
      ORDER BY
        '.TABLE_PRODUCER.'.business_name ASC
      LIMIT '.mysql_real_escape_string ($start).', '.mysql_real_escape_string ($half);
    $result = @mysql_query($query, $connection) or die(debug_print ("ERROR: 869302 ", array ($query,mysql_error()), basename(__FILE__).' LINE '.__LINE__));
    while ( $row = mysql_fetch_array($result) )
      {
        $producer_id = $row['producer_id'];
        $business_name = $row['business_name'];
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $address_line1 = $row['address_line1'];
        $address_line2 = $row['address_line2'];
        $city = $row['city'];
        $state = $row['state'];
        $zip = $row['zip'];
//        include("../func/show_name.php");
        $display .= $business_name.'</b><br>';
        $display .= $first_name.' '.$last_name.'</b><br>';
        $display .= $address_line1.'<br>';
        if($address_line2)
          {
            $display .= $address_line2.'<br>';
          }
        $display .= $city.', '.$state.' '.$zip.'<br>';
        $display .= '<br>';
      }
    return $display;
  }