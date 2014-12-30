<?php
include_once ('config_foodcoop.php');
include_once ('general_functions.php');
session_start();
valid_auth('site_admin');


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///      This routine will step through the tab-separated-value file        ///
///      that was generated from UNFI spreadsheet data.  Information        ///
///       on each row will be categoriezed as one of: business name,        ///
///        subcat name, or subheading (used with certain businesses         ///
///                  to provide additional information.                     ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                          INITIALIZE VARIABLES                           ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////


include ('product_list_template.php');
include ('unfi_functions.php');
$max_cols = 14; // Columns used by UNFI spreadsheets

// Path to the unfi-specific files
$unfi_file_path = FILE_PATH.PATH.'members/unfi_data/';
$messages = array();
$option_array = array ();
// Maximum number of sample products to show
$max_product_count = 5;

// Stop if process_step_one is not completed
if (unfi_get_status ('process_step_one') != "complete")
  exit (0);
  array_push ($messages, '<li class="error"><span class="intro">Error: </span>File is not ready to be validated</li>');

if (! unfi_get_status ('process_step_two'))
  {
    // If just starting this process, then set initial values
    unfi_put_status('current_row', 0);
  }

unfi_put_status('current_process_step', 'process_step_two');
unfi_put_status('process_step_two', 'processing');

$current_row = unfi_get_status ('current_row');
$current_data_file = unfi_get_status ('current_data_file');

// Begin with defaults
if (! $business_name = unfi_get_status ('business_name'))
  $business_name = 'UNFI';
if (! $business_name_raw = unfi_get_status ('business_name_raw'))
  $business_name_raw = 'UNFI';
if (! $subcat_name = unfi_get_status ('subcat_name'))
  $subcat_name = '';
if (! $business_subhead = unfi_get_status ('business_subhead'))
  $business_subhead = '';
if (! $category_name = unfi_get_status ('category_name'))
  $category_name = '';

// Load the data file in preparation for processing
$data_array = array ();
$data_array = file($unfi_file_path.$current_data_file);
$total_rows = count ($data_array);
unfi_put_status('total_rows', $total_rows);

// Set these variables in case we don't get a chance to do so later
$business_name_prior = unfi_get_status ('business_name');
$business_subhead_prior = unfi_get_status ('business_subhead');
$subcat_name_prior = unfi_get_status ('subcat_name');
$category_name_prior = unfi_get_status ('category_name');
$major_category_prior = unfi_get_status ('major_category');
$minor_category_prior = unfi_get_status ('minor_category');
$current_row_prior = unfi_get_status ('current_row');

// Preload the business_name translations
$business_name_array = array();
$query = '
  SELECT
    input,
    output
  FROM
    '.TABLE_TRANSLATION.'
  WHERE
    context = "unfi_business"';
$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_object($result) )
  {
    $business_name_array[$row->input] = $row->output;
  }

// Preload the category_name translations
$category_array = array();
$query = '
  SELECT
    input,
    output
  FROM
    '.TABLE_TRANSLATION.'
  WHERE
    context = "unfi_category"';
$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_object($result) )
  {
    $category_array[$row->input] = $row->output;
  }

// Preload the business-subcat translations
$business_subcat_array = array();
$query = '
  SELECT
    input,
    output
  FROM
    '.TABLE_TRANSLATION.'
  WHERE
    context = "unfi_business_subcat"';
$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_object($result) )
  {
    $business_subcat_array[$row->input] = $row->output;
  }

// Preload the business-subhead translations
$business_subhead_array = array();
$query = '
  SELECT
    input,
    output
  FROM
    '.TABLE_TRANSLATION.'
  WHERE
    context = "unfi_business_subhead"';
$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_object($result) )
  {
    $business_subhead_array[$row->input] = $row->output;
  }

// Preload UNFI special code translactions
$special_code_array = array();
$query = '
  SELECT
    input,
    output
  FROM
    '.TABLE_TRANSLATION.'
  WHERE
    context = "unfi_special_code"';
$result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
while ( $row = mysql_fetch_object($result) )
  {
    $special_code_array[$row->input] = $row->output;
  }



///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                               FUNCTIONS                                 ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

// This function gets data from the data_array, indexed by row/column
function get_data ($row, $column)
  {
    global $data_array;
    $column_array = array ();
    $column_array = explode ("\t", $data_array[$row]);
    return ($column_array[$column]);
  }

function update_last_seen ($context, $key)
  {
    global $connection;
    if ($key && $context)
      {
        $query = '
          UPDATE
            '.TABLE_TRANSLATION.'
          SET
            last_seen = NOW()
          WHERE
            context = "'.mysql_real_escape_string ($context).'"
            AND input = "'.mysql_real_escape_string ($key).'"';
        $result = @mysql_query($query, $connection) or die(mysql_error() . "<br><b>Error No: </b>" . mysql_errno());
      }
  }


///////////////////////////////////////////////////////////////////////////////
///                                                                         ///
///                                  MAIN                                   ///
///                                                                         ///
///   This routine will work by looking at groups of non-product lines      ///
///   and collecting the possible data from them to use for selecting       ///
///       business_name, subcategory_name, and business_subheading          ///
///    information.  This will be mapped onto the following products,       ///
///   allowing the human operator to evaluate the best fit of the data.     ///
///                                                                         ///
///////////////////////////////////////////////////////////////////////////////

$value_array = array ();
$value_type_array = array ();
$value_key_array = array ();
$value_value_array = array ();
while ($current_row <= $total_rows || unfi_get_status('process_step_two') == 'complete')
  {
    // Get the first bracket of non-product rows
    $value_start_row = $current_row;
    while (get_data ($value_start_row, 4) && $value_start_row <= $total_rows)
      {
        $value_start_row ++;
      }
    $value_end_row = $value_start_row;
    while (! get_data ($value_end_row, 4) && $value_end_row <= $total_rows)
      {
        $value_end_row ++;
      }
    $value_end_row = $value_end_row - 1;
    // Non-product data will be on rows $value_start_row .. $value_end_row (inclusive)
    $unknown_count = 0;
    $product_content = '';
    // Get an array of values from these rows
    for ($this_row = $value_start_row; $this_row <= $value_end_row; $this_row ++)
      {
        // Special note: category is composed of the major category and any minor category
        // that might be in play at the time -- concatenated with a space.
        if ($this_value = chop (get_data ($this_row, 1)))
        // Category values will be in column 1...
          {
            // Column 1 is ALWAYS a subcategory (actually it is more like a full category)
            // Check if it is a known subcat_name (IT SHOULD BE!!!)
            $business_name = 'UNFI';
            $business_name_raw = 'UNFI';
            $value_array[$this_row] = $this_value;
            if (in_array ($this_value, array_keys ($category_array)))
              {
                update_last_seen ('unfi_category', $this_value);
                $value_type_array[$this_row] = 'category_name';
                $value_key_array[$this_row] = $this_value;
                $value_value_array[$this_row] = $category_array[$this_value];
                $major_category_raw = $this_value;
                $major_category = $category_array[$this_value];
                // Record this also, in case there is no additional category name
                $minor_category = '';
                $category_name = $major_category;
              }
            else
              {
                $value_type_array[$this_row] = 'unknown';
                $value_key_array[$this_row] = $this_value;
                $value_value_array[$this_row] = ucwords (strtolower ($this_value));
                $major_category_raw = $this_value;
                $major_category = $this_value;
                // Record this also, in case there is no additional category name
                $minor_category = '';
                $category_name = $major_category;
                $unknown_count ++;
              }
          }
        // Other values will be in column 2 (unless the row is blank)
        elseif (chop (get_data ($this_row, 2)))
          {
            $this_value = chop (get_data ($this_row, 2));
            $value_array[$this_row] = $this_value;
            // Now that we have the value, so see if we know what it is
            // Check if it is a known business_name


            // Check if this row is a secondary category (not a subcategory and
            // not a major category.  This occurs when there are two ALL-CAPS lines
            // in a row, such as:
            //
            // CANDY/SNACKS
            // BARBARA'S BAKERY
            // Fig Bars
            //
            // or...
            //
            // BEANS
            // BEANS
            if ($value_end_row >= $this_row + 1 && // there is at least one more non-product row
                $this_value == strtoupper ($this_value) && // This value is ALL-CAPS
                chop (get_data ($this_row + 1, 2)) == strtoupper (chop (get_data ($this_row + 1, 2)))) // Next row value is ALL-CAPS
              {
                $business_name = 'UNFI';
                $business_name_raw = 'UNFI';
                // Check if we already have this in the category array
                if (in_array ($major_category_raw.' '.$this_value, array_keys ($category_array)))
                  {
                    update_last_seen ('unfi_category', $major_category_raw.' '.$this_value);
                    $value_type_array[$this_row] = 'category_name';
                    $minor_category = $this_value;
                    $category_name = $major_category_raw.' '.$this_value;
                    $value_key_array[$this_row] = $category_name;
                    $value_value_array[$this_row] = $category_array[$category_name];
                  }
                else
                  {
                    $value_type_array[$this_row] = 'unknown';
                    $minor_category = $this_value;
                    $category_name = $major_category_raw.' '.$this_value;
                    $value_key_array[$this_row] = $category_name;
                    $value_value_array[$this_row] = ucwords (strtolower ($category_name));
                    $unknown_count ++;
                  }
              }


            elseif (in_array ($this_value, array_keys ($business_name_array)))
              {
                update_last_seen ('unfi_business', $this_value);
                $value_type_array[$this_row] = 'business_name';
                $value_key_array[$this_row] = $this_value;
                $value_value_array[$this_row] = $business_name_array[$this_value];
                $business_name = $business_name_array[$this_value];
                $business_name_raw = $this_value;
                $subcat_name = '';
                $business_subhead = '';
              }
            // Check if it is a known subcat_name
            elseif (in_array ($business_name_raw.':'.$this_value, array_keys ($business_subcat_array)))
              {
                update_last_seen ('unfi_business_subcat', $business_name_raw.':'.$this_value);
                $value_type_array[$this_row] = 'subcat_name';
                $value_key_array[$this_row] = $business_name_raw.':'.$this_value;
                $value_value_array[$this_row] = $business_subcat_array[$business_name_raw.':'.$this_value];
                $subcat_name = $this_value;
              }
            // Check if it is a known business_subhead
            elseif (in_array ($business_name_raw.':'.$this_value, array_keys ($business_subhead_array)))
              {
                update_last_seen ('unfi_business_subhead', $business_name_raw.':'.$this_value);
                $value_type_array[$this_row] = 'business_subhead';
                $value_key_array[$this_row] = $business_name_raw.':'.$this_value;
                $value_value_array[$this_row] = $business_subhead_array[$business_name_raw.':'.$this_value];
                $business_subhead = $this_value;
              }
            // Otherwise we don't know what it is, so tag it as such
            else
              {
                $value_type_array[$this_row] = 'unknown';
                $value_key_array[$this_row] = '';
                $value_value_array[$this_row] = '';
                $unknown_count ++;
              }
          }
      }

    // Now that we have the possible values, put together those values into the 
    // data for the products that follow them
    $product_row = $value_end_row + 1;
    // If we had any unknown values, then go through a validation query cycle...
    if ($unknown_count)
      {
        $product_content = '';
        // Since we will be pausing calculation for some input, save the current "safe" settings
        // that were taken just prior to finding these questionable settings
        unfi_put_status('business_name', $business_name_prior);
        unfi_put_status('business_subhead', $business_subhead_prior);
        unfi_put_status('subcat_name', $subcat_name_prior);
        unfi_put_status('category_name', $category_name_prior);
        unfi_put_status('major_category', $major_category_prior);
        unfi_put_status('minor_category', $minor_category_prior);
        unfi_put_status('current_row', $current_row_prior);
        // Reset the value_id counter
        $value_id = 1;
        $product_content .= '
              <div id="controls">';
        // Begin with the form for choosing values
        for ($this_row = $value_start_row; $this_row <= $value_end_row; $this_row ++)
          {
            $product_content .= '
              <label><input type="text" id="value_'.$value_id.'" value="'.$value_array[$this_row].'" onChange="product_preview(this.id);" disabled></label>
              <label> is 
                <select id="value_type_'.$value_id.'" onChange="product_preview(this.id);"'.($value_type_array[$this_row] == 'unknown' ? '' : ' disabled').'>
                  <option value=""'.($value_type_array[$this_row] == 'unknown' ? ' selected' : '').'>nothing</option>
                  <option value="business_name"'.($value_type_array[$this_row] == 'business_name' ? ' selected' : '').'>a business name</option>
                  <option value="category_name"'.($value_type_array[$this_row] == 'category_name' ? ' selected' : '').'>a category</option>
                  <option value="subcat_name"'.($value_type_array[$this_row] == 'subcat_name' ? ' selected' : '').'>a subcategory</option>
                  <option value="business_subhead"'.($value_type_array[$this_row] == 'business_subhead' ? ' selected' : '').'>a business subheading</option>
                </select>
              </label>
              <label><input type="hidden" id="value_key_'.$value_id.'" value="'.$value_key_array[$this_row].'" onChange="product_preview(this.id);" disabled></label>
              <br>
              <label> display as: <input type="text" id="value_value_'.$value_id.'" value="'.$value_value_array[$this_row].'" onChange="product_preview(this.id);"'.($value_type_array[$this_row] == 'unknown' ? '' : ' disabled').'></label>
              <br>';
            $value_id ++;
          }
        $product_content .= '
            <input type="hidden" id="end_value_row" value="'.($value_id - 1).'">
            <input type="hidden" id="business_name" value="'.$business_name_raw.'">
            <input type="hidden" id="subcategory_name" value="'.$subcat_name.'">
            <input type="hidden" id="category_name" value="'.$category_name.'">
            <input type="hidden" id="major_category" value="'.$major_category_raw.'">
            <input type="hidden" id="minor_category" value="'.$minor_category.'">
            <input type="hidden" id="business_subhead" value="'.$business_subhead.'">
            ';
        // This will be used by javascript to know over what row_id values to range
        $product_content .= '
          <input type="submit" id="update" value="Update and continue" onClick="send_validation()">
          <input type="hidden" id="start_product_row" value="'.$product_row.'">
          </div>
          <div style="clear:both;"></div>';
        while (get_data ($product_row, 4))
          {
            $product_content .= '
              <div class="unfi_product">
                <span class="unfi_producer" id="business_name_'.$product_row.'">'.$business_name.'</span>
                <span class="unfi_subhead" id="business_subhead_'.$product_row.'">'.$business_subhead.'</span>
                <br>
                <span class="unfi_category" id="category_name_'.$product_row.'">'.$category_name.'</span>
                <span class="unfi_subcat" id="subcat_name_'.$product_row.'">'.$subcat_name.'</span>
                <br>
                <div class="unfi_product_detail">
                  [<span class="unfi_product_id">'.get_data ($product_row, 2).'</span>]
                  <span class="unfi_product_name">'.get_data ($product_row, 4).'</span>
                  <span class="unfi_product_code">'.get_data ($product_row, 3).'</span>
                  <span class="unfi_product_upc">UPC: '.get_data ($product_row, 5).'</span>
                  <span class="unfi_ordering_unit">'.get_data ($product_row, 6).'</span>
                </div>
              </div>';
            $product_row ++;
          }
        // This will be used by javascript to know over what row_id values to range
        $product_content .= '
          <input type="hidden" id="end_product_row" value="'.($product_row - 1).'">';
        // Write to the scratch_pad directory
        // If not too many scratch files already, then add another one
        $temp_file_name = 'validate.html';
        $scratch_file = fopen ($unfi_file_path.$temp_file_name, "wb");
        fwrite ($scratch_file, $product_content);
        fclose ($scratch_file);
        unfi_put_status ('process_step_two', 'validating');
        // Now wait until the file is processed and removed.
        while (unfi_get_status ('process_step_two') == 'validating')
          {
            sleep (1);
            // Keep resetting the timeout to thirty more seconds
            set_time_limit(30);
          }
        $business_name_prior = unfi_get_status ('business_name');
        $business_subhead_prior = unfi_get_status ('business_subhead');
        $subcat_name_prior = unfi_get_status ('subcat_name');
        $category_name_prior = unfi_get_status ('category_name');
        $current_row_prior = unfi_get_status ('current_row');
        $major_category_prior = unfi_get_status ('major_category');
        $minor_category_prior = unfi_get_status ('minor_category');
      }
    // ... otherwise, we know what these values are, so update the products...
    else
      {
        // Add to or update the database with the product information
        while (get_data ($product_row, 4))
          {
            // Register the current known-good values in case we need them
            // These values will be used to pre-set variables when starting mid-process
            $business_name_prior = $business_name;
            $business_name_raw_prior = $business_name_raw;
            $business_subhead_prior = $business_subhead;
            $subcat_name_prior = $subcat_name;
            $category_name_prior = $category_name;
            $major_category_prior = $major_category_raw;
            $minor_category_prior = $minor_category;
            $current_row_prior = $product_row;
            // Increment the product row counter
            $product_row ++;
          }
      }
    // Set up the next loop
    $current_row = $product_row;
    unfi_put_status('current_row', $current_row);
  }
?>