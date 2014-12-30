<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
// session_start();
// valid_auth('site_admin');

// DO NOT enable session or ajax will not be able to initiate the program
// until all other php sessions for this user are closed.


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///         This is a very simple function to monitor the progress          ///
///         of certain slow processes related to the unfi program           ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                          INITIALIZE VARIABLES                           ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

// Add some latency
sleep (1);

include ('unfi_functions.php');

// Path to the unfi-specific files
$cell_data = array ();
$current_process_step = unfi_get_status ('current_process_step');

// If all the rows have been processed, then set the status to complete
if ((unfi_get_status ('total_rows') > 1 && unfi_get_status ('current_row') >= unfi_get_status ('total_rows'))
    || unfi_get_status ($current_process_step) == 'complete')
  {
    unfi_put_status($current_process_step, "complete");
    echo "complete";
  }
elseif (unfi_get_status ('total_rows'))
  echo number_format (100 * unfi_get_status ('current_row') / unfi_get_status ('total_rows'), 1);

?>