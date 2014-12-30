<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
valid_auth('site_admin');


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///      This is a very simple function to take a recently-uploaded         ///
///      Excel-formatted file and convert it to tab-separated-value         ///
///           file for php to handle more easily and quickly.               ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                          INITIALIZE VARIABLES                           ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////


include ('unfi_functions.php');
$max_cols = 14; // Columns used by UNFI spreadsheets

// Path to the unfi-specific files
$unfi_file_path = FILE_PATH.PATH.'members/unfi_data/';
$messages = array();
$cell_data = array ();

$xls_data_file = unfi_get_status ('xls_data_file');
$xls_sheet_number = unfi_get_status ('xls_sheet_number');
$xls_heading_row = unfi_get_status ('xls_heading_row');
unfi_put_status('current_process_step', 'process_step_one');


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                                FUNCTIONS                                ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

function translate_data ($data)
  {
    // Get rid of white-space at beginning and end
    $data = trim ($data);
    // Remove double quotes
    $data = strtr ($data, '"', '');
    // Convert back-ticks to single apostrophes
    $data = strtr ($data, '`', "'");
    // Remove everything except ascii characters (solves non-standard quote problems)
    $data = preg_replace ('/[^[:ascii:]]/','',$data);
    // Whatever is left should be pretty good data
    return ($data);
  }

// Check that the file exists and we can get a good name from it
if (file_exists ($unfi_file_path.$xls_data_file))
  {
    // Get the new file_name
    $tsv_file_name = basename ($xls_data_file, '.xls');
    if ($tsv_file_name == $xls_data_file)
      {
        // Error: Unexpected file name
        array_push ($messages, '<li class="error"><span class="intro">Error:</span> Unexpected spreadsheet file name &quot;'.htmlspecialchars($xls_data_file).'&quot;</li>');
      }
  }
else
  {
    // Error: No such file
    array_push ($messages, '<li class="error"><span class="intro">Error:</span> &quot;'.htmlspecialchars($xls_data_file).'&quot; does not exist.</li>');
  }

// Set attributes for the status file
unfi_put_status('current_data_file', $tsv_file_name);

if (count ($messages) == 0)
  {
    // Open the spreadsheet
    include_once ('excel_reader.php');
    $spreadsheet_data = new Spreadsheet_Excel_Reader();
    $spreadsheet_data->setOutputEncoding('CP1251');
    $spreadsheet_data->read($unfi_file_path.$xls_data_file);
    $number_of_rows = $spreadsheet_data->sheets[$xls_sheet_number - 1]['numRows'];
    // Save information to the status file
    unfi_put_status('total_rows', $number_of_rows);
    unfi_put_status('process_step_one', 'converting');

    // Open new (tsv) spreadsheet file
    $tsv_file = fopen ($unfi_file_path.$tsv_file_name, "wb");

    // Start cycling through the spreadsheet and saving data into the tsv file
    for ($row = $xls_heading_row; $row <= $number_of_rows; $row++)
      {
        for ($col = 1; $col <= $max_cols; $col++)
          {
            // Fill the cell_data array for this row
            $cell_data[$col] = translate_data ($spreadsheet_data->sheets[$xls_sheet_number - 1]['cells'][$row][$col]);
          }
        // Now combine the cell_data array into tsv data
        $cell_data = explode ("\t", rtrim (implode ("\t", $cell_data)));
        $number_of_cells = count ($cell_data);
        $row_data = implode ("\t", $cell_data);
        if (strlen ($row_data) > 0)
          {
            fwrite ($tsv_file, "$row_data\n");
            unfi_put_status('current_row', $row);
            // Only update at most once per time quanta (i.e. once a second)
            if (time() > $time)
              {
                $time = time();
              }
          }
      }
  }

// When done, remove the temporary xls file
unlink ($unfi_file_path.$xls_data_file);


// Set up the new unfi_status array
unfi_put_status('current_row', 0);
unfi_put_status('process_step_one', 'complete');
unfi_put_status('total_rows', count(file($unfi_file_path.$tsv_file_name)));

?>