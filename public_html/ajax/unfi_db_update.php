<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
// session_start();
// valid_auth('site_admin');

// DO NOT enable session or ajax will not be able to initiate the program
// until all other php sessions for this user are closed.


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///         This is a very simple function to update the translation        ///
///                            table for unfi data                          ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                          INITIALIZE VARIABLES                           ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

include ('unfi_functions.php');
$unfi_file_path = FILE_PATH.PATH.'members/unfi_data/';

if ($_POST['action'] == 'db_update')
  {
    $context = '';
    if ($_POST['row_type'] == 'business_name') $context = 'unfi_business';
    elseif ($_POST['row_type'] == 'subcat_name') $context = 'unfi_business_subcat';
    elseif ($_POST['row_type'] == 'category_name') $context = 'unfi_category';
    elseif ($_POST['row_type'] == 'business_subhead') $context = 'unfi_business_subhead';

    if ($context)
      {
        $key = $_POST['key'];
        $value = $_POST['value'];
        if ($key && $value && $context)
          {
            $query = '
              INSERT INTO
                '.TABLE_TRANSLATION.'
              SET
                context = "'.mysql_real_escape_string ($context).'",
                input = "'.mysql_real_escape_string ($key).'",
                output = "'.mysql_real_escape_string ($value).'",
                last_seen = NOW()
              ON DUPLICATE KEY UPDATE
                output = "'.mysql_real_escape_string ($value).'",
                last_seen = NOW()';
            $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
          }
        // We even have success with null queries (key = "", value = "", or context = ""
        // for cases when the posting form decides not to make a change: *nothing*
        echo 'success';
      }
  }
if ($_POST['action'] == 'continue' || $_POST['action'] == 'watch')
  {
    // Set the status to "processing".  When the file is ready, this is changed to "validating"
    // and when it is complete, it is changed to "complete"
    unfi_put_status ('process_step_two', 'processing');
    $temp_file_name = 'validate.html';
    while (unfi_get_status ('process_step_two') == 'processing')
      {
        sleep (1);
        // Keep resetting the timeout to thirty more seconds
        set_time_limit(30);
      }
    if (unfi_get_status ('process_step_two') == 'complete')
      {
        if (file_exists ($unfi_file_path.$temp_file_name))
          unlink ($unfi_file_path.$temp_file_name);
      }
    else
      {
        $scratch_file = fopen ($unfi_file_path.$temp_file_name, "rb");
        $validation_content = fread ($scratch_file, filesize ($unfi_file_path.$temp_file_name));
        if ($validation_content)
          echo $validation_content;
    }
  }
?>