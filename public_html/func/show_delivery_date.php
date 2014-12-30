<?php
$sql = '
  SELECT
    delivery_id,
    delivery_date
  FROM
    '.TABLE_ORDER_CYCLES.'
  WHERE
    delivery_id = "'.mysql_real_escape_string ($delivery_id).'"';
$rs = @mysql_query($sql, $connection) or die("Couldn't execute query.");
while ( $row = mysql_fetch_array($rs) )
  {
    $delivery_id = $row['delivery_id'];
    $delivery_date = $row['delivery_date'];
  }