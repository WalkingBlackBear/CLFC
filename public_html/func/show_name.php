<?php
if ( $business_name == $first_name.' '.$last_name )
  {
    $show_name = $business_name;
  }
else
  {
    if ( ($first_name || $last_name) && ($first_name_2 || $last_name_2) )
      {
        if ( $last_name == $last_name_2 )
          {
            if ( $business_name )
              {
                $show_name = $business_name.'<br>'.$first_name.' & '.$first_name_2.' '.$last_name_2;
             }
           else
              {
                $show_name = $first_name.' & '.$first_name_2.' '.$last_name_2;
              }
          }
        else
          {
            if ( $business_name )
              {
                $show_name = $business_name.'<br>'.$first_name.' '.$last_name.' & '.$first_name_2.' '.$last_name_2;
              }
            else
              {
                $show_name = $first_name.' '.$last_name.' & '.$first_name_2.' '.$last_name_2;
              }
          }
      }
    elseif ( ($first_name || $last_name) && ( !$first_name_2 && !$last_name_2) )
      {
        if ( $business_name )
          {
            $show_name =  $business_name.'<br>'.$first_name.' '.$last_name;
          }
        else
          {
            $show_name =  $first_name.' '.$last_name;
          }
      }
    else
      {
          $show_name = $business_name;
      }
  }
?>