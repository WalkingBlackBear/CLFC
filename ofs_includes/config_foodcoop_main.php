<?php
// SERVER AND SITE SETUP

                          // Version is used to keep you alerted to new versions and updates of the software
$current_version        = 'OFS 0.9';

                          // Some common timezone options in the United States:
                          //    Pacific:  America/Los_Angeles
                          //    Mountain: America/Denver
                          //    Central:  America/Chicago
                          //    Eastern:  America/New_York
$local_time_zone        = 'America/Chicago';

                          // Base url is used for web references.  Do NOT include a trailing slash.
$site_url               = 'http://www.cloverbeltlocalfoodcoop.com';
$homepage_url           = 'http://www.cloverbeltlocalfoodcoop.com/index_ofs.php';

                          // Directory for the local food coop software on the website (typically /shop/)
$food_coop_store_path   = '/';

                          // Internal file path to document_root
$file_path              = '/home/cloverbe/public_html';

                          // Date format (c.f. php date formatting variables) for order closed text
$date_format_closed     = 'F j, Y';

                          // Internal file path to document_root
$db_backup_root         = '/home/cloverbe';

                          // Domain name -- used for email references
$domainname             = 'cloverbeltlocalfoodcoop.com';

                          // Name of your organization -- used in some textual messages
$site_name              = 'Cloverbelt Local Food Co-Op';

                          // Contact information used for textual reference (HTML code should be okay here)
$site_contact_info      = 'Cloverbelt Local Food Co-Op<br />66 Keith Ave. Unit 2<br />Dryden, ON';

                          // Mailing address (HTML code should be okay here)
$site_mailing_address   = 'Cloverbelt Local Food Co-Op<br />P.O. Box 668<br />Dryden, ON P8N 2Z3';

                          // Directory for graphic files for the coop section of your website
$site_graphics          = '/images/';

                          // Filename of your favicon (in the root directory)
$favicon                = 'images/clover_icon.png';

// CYCLE AND INVOICING CONFIGURATION

                          // Typical period of cycle -- for presetting prep functions
$days_per_cycle         = 35;

                          // End-of-order window for institutional buyers (seconds) Set $institution_window
                          // value high to allow institutional buyers all the time and set to zero to prevent
                          // any use. NOTE: 3600 * 24 = 1 day in seconds.
$institution_window     = (3600 * 24) * 2;

                          // Show actual price vs. show cooperative price (between the producer and customer price)
                          // This is the global value for price-lists. It must be changed in the order_cycles table
                          // for display on invoices of specific order cycles
$show_actual_price      = true;

                          // In public product lists, this will force suppression of zero-inventory items
$exclude_zero_inv       = true;

                          // If this is true, each new member_id will fill in missing values. Otherwise each
                          // will be sequentially higher according to the auto-increment value.
$fill_in_member_id      = false; 

                          // If this is true, producer contact information will be shown on the public pages
                          // otherwise set to false to only shown for logged-in members.
$prdcr_info_public      = false;

                          // Set custom paging directives for htmldoc here
$htmldoc_paging         = '<!-- MEDIA DUPLEX NO --><!-- MEDIA TOP 0.3in --><!-- MEDIA BOTTOM 0.3in -->';

// EXTERNAL FILE SETUP

                          // Note: these pages must be under $site_url
                          // how to join the coop
$page_membership        = '/member_form.php';

                          // membership standards
$page_terms_of_service  = '/docs/CLFCMembershipHandbook.pdf';

                          // pickup and deilvery locations:
$page_locations         = $food_coop_store_path.'locations.php';

                          // list of producers in the coop
$page_coopproducers     = $food_coop_store_path.'prdcr_list.php';

                          // path to invoices
$invoice_web_path       = $food_coop_store_path.'invoices/';
$invoice_file_path      = $file_path.$invoice_web_path;


// DATABASE SETUP

                          // Enter the db host
$db_host                = 'localhost';

                          // Enter the username for db access
$db_user                = 'cloverbe_coop';

                          // Enter the password for db access
$db_pass                = 'LocalFoodC00p';

                          // Enter the database name
$db_name                = 'cloverbe_coop';

                          // This is probably blank
$db_prefix              = '';

                          // If you want to use a master password to access all member accounts
                          // enter the MD5 of master password as generated by mysql. You can do that by
                          // submitting a query like this SELECT MD5("my*SECRET!password@123")
$md5_master_password    = '877bd166351fc9ad6cb1f502adca29d5';


// DISPLAY SETUP

                          // Configure this to reflect your desired routing code template: The following
                          // values will be auto-filled from like-named variables in the scripts used
                          // to create the routing templates.  For example, !BASKET_ID! is replaced
                          // with the contents of the $basket_id variable.
                          //
                          //   !BASKET_ID!       customer basket id
                          //   !MEMBER_ID!       member id number
                          //   !FIRST_NAME!      customer first name
                          //   !LAST_NAME!       customer last name
                          //   !SHOW_MEM!        customer name in "Last, First" format
                          //   !SHOW_MEM2!       customer_name in "First Last" format
                          //   !BUSINESS_NAME!   customer business name -- may not exist
                          //   !HUB!             the delivery hub
                          //   !TRUCK_CODE!      routing truck code
                          //   !DELCODE_ID!      delivery code id (the abbreviation)
                          //   !DELCODE!         delivery code (long form of name)
                          //   !DELTYPE!         delivery type (H:home, W:work, P:pickup)
                          //   !A_BUSINESS_NAME! producer business name
                          //   !PRODUCT_ID!      product numeric id
                          //   !PRODUCT_NAME!    full product name
                          //   !ITEM_PRICE!      item price per pricing-unit (not the total)
                          //   !ORDERING_UNIT!   units used for ordering
                          //   !QUANTITY!        quantity of ordering units that were ordered
                          //   !STORAGE_CODE!    product storage code (may not always apply)
// $route_code_template =    '<span style="border:1px solid #777;">&nbsp;!HUB!&nbsp;</span><span style="border:1px solid #777;background:#777;color:#fff;font-weight:bold;">&nbsp;!DELCODE_ID!&nbsp;</span>&nbsp;#!MEMBER_ID! (!DELCODE!) [!STORAGE_CODE!]';
$route_code_template =    '<span>!HUB!-</span><span style="font-size:120%;font-weight:bold;">!DELCODE_ID!</span>&nbsp; &nbsp;#!MEMBER_ID! (!DELCODE!) [!STORAGE_CODE!]';

                          // Font face used in various locations
$fontface =               'arial';

                          // Another font declaration used in other locations
$font =                   '<font class="default_font">';

                            // Some longer listings use this value for pagination
$default_results_per_page = 25;

                          // Change this if your organization is, i.e. a "partnership".  This is used in
                          // various textual places.  i.e. "Welcome to the ******"
$organization_type =      'co-operative';

                          // Use this to enable producer confirmation settings (NOT FULLY TESTED)
$req_prdcr_confirm =      false;

                          // Use this to control whether paypal fees are passed to customers.  Please note
                          // that it is of questionable legality to pass along paypal or credit-card fees.
                          // Also note that this ability will probably be deprecated in future versions so
                          // it is strongly suggested NOT to use this setting.  If paypal charges will not
                          // be passed on to customers, then set this value to zero.  To always use paypal
                          // surcharges, set this to a very large number -- like 1000000
$delivery_no_paypal =     0;

                          // Don't rely on this to be completely fool-proof, but it is a beginning. Currently
                          // this is only still used by the membership form.
$state_tax =              0.0;

                          // Show logo in the header?
$show_header_logo =       true;

                          // Show site name in the header?
$show_header_sitename =   false;

                          // Enable/disable pdf generation by htmldoc
$use_htmldoc =            true;

                          // 1: if new producers should be pending; 0: if new producers should have immediate access
$new_producer_pending =   '1';

                          // 0: listed; 1: unlisted; 2: suspended
$new_producer_status =   '1';

                          // Possible values for calculating charges for items with random weights:
                          // ZERO : Use a zero charge for the items
                          // AVG  : Use an average cost for the two weights
                          // MAX  : Use maximum costs
                          // MIN :  Use minimum costs
                          // Does not affect DISPLAY (see customer_invoice_template
                          // Only affects calculations of totals/costs
$random_calc =            'ZERO';

                          // true or false if membership should be a taxable quantity
$membership_taxed =       false;

                          // Set according to whether the co-op fee is taxable.  Choose from:
                          // For everything that has a co-op fee:       'always'
                          // Only for things that are already taxed:    'on taxable items'
                          // The coop fee is never taxed for anything:  'never'
$coop_fee_taxed =         'on taxable items';

                          // Who pays the various fees? 'customer' or 'producer' or 'nobody'
$pays_producer_fee =      'producer';
$pays_customer_fee =      'customer';
$pays_subcategory_fee =   'customer';
$pays_product_fee =       'customer';

                          // How to aggregate charges in the ledger: 'by product' or 'by basket'
// $aggregate_producer_fee = 'by product'; // NOT YET IMPLEMENTED: 'by basket' would require producer baskets
// Currently only "by product" is implemented
$aggregate_taxes =        'by product';
$aggregate_customer_fee = 'by product';
                          // (abbreviation for your organization -- used for identifying internal accounts in ledger display
$internal_designation =   'CLFC';
 

$days_considered_new =    '21'; // Number of days a new product will show up on the "new" listings
$days_considered_changed ='21'; // Number of days a changed product will show up on the "changed" listings

// CONTACT EMAIL SETUP

                          // Set up your site email addresses here.  The software uses all of these email aliases
                          // however you can point them all to just a few (or one) address if you desire.

$email_customer         = 'membership@'.$domainname;
$email_general          = 'info@'.$domainname;
$email_help             = 'kate@'.$domainname;
$email_membership       = 'membership@'.$domainname;
$email_orders           = 'info@'.$domainname;
//$email_paypal           = 'paypal@'.$domainname; // Unset to disable paypal links
$email_pricelist        = 'info@'.$domainname;
$email_problems         = 'webmaster@'.$domainname;
$email_producer_care    = 'info@'.$domainname;
$email_software         = 'webmaster@'.$domainname;
$email_standards        = 'jen@'.$domainname;
$email_treasurer        = 'treasurer@'.$domainname;
$email_volunteer        = 'info@'.$domainname;
$email_webmaster        = 'webmaster@'.$domainname;

                          // The membership form will be sent to these email address(es) -- separate with commas
                          // Use "SELF" to send an email copy to the member who is filling out the form.
$email_member_form      = 'SELF,'.$email_membership;

                          // The producer form will be sent to these email address(es) -- separate with commas
                          // The "SELF" term does not function with this form.
$email_producer_form    = 'SELF,'.$email_standards;              // Where new producer emails notifications are sent

                          // Name of the membership coordinator or other official contact person (plain-text only).
                          // This is used e.g. for signing the member welcome letter (Use double-quotes so the
                          // newline character will be preserved)
$authorized_person      = 'Jen Springett';

$debug                  = true; // Don't show any debug messages

                          // Identify which product fields, when changed, should initiate a new product version and
                          // require confirmation. Comment (with // in front) the fields that should not require
                          // confirmation when changed by a producer.
$fields_req_confirm    =  array (
                          //'product_name',
                          //'product_description',
                            'subcategory_id',
                          //'account_number',       // Normally only set by admin
                          //'inventory_id',
                          //'inventory_pull',
                          //'unit_price',
                          //'pricing_unit',
                          //'ordering_unit',
                          //'production_type_id',   // Organic, natural, etc.
                            'extra_charge',
                          //'product_fee_percent',  // Normally only set by admin
                          //'random_weight',
                          //'minimum_weight',
                          //'maximum_weight',
                          //'meat_weight_type',
                          //'listing_auth_type',    // Retail, wholesale, unlisted, archived, etc.
                          //'sticky',
                          //'tangible',
                          //'storage_id',
                          //'retail_staple',        // Normally only set by admin
                          //'future_delivery',
                          //'future_delivery_type',
                          //'image_id',
                          //'confirmed',            // Normally only set by admin
                          //'staple_type',          // Normally only set by admin
                          //'created',              // Normally only set by the system
                          //'modified'              // Normally only set by the system
                          //'hide_from_invoice'     // Normally only set by admin
                          );

                          // When a producer change to a product forces a new version, this will determine
                          // whether the current (old version) becomes UN-confirmed or not. Benefits of keeping
                          // the old product confirmation are that customers can still buy it -- even before
                          // the change gets validated. Detriments are the same. When the new version is validated
                          // the old version will automatically be unconfirmed.
$keep_old_prod_conf    =  true; // Set true|false

// IF NEEDED, INVOKE A SCRIPT TO MANUALLY REMOVE MAGIC_QUOTES *** This might not work correctly...? -ROYG
// Ideally, your server is configured NOT to use magic_quotes_gpc. Hopefully you won't need it!
if(get_magic_quotes_gpc())
  {
    $_REQUEST = array_map('stripslashes', $_REQUEST);
    $_GET = array_map('stripslashes', $_GET);
    $_POST = array_map('stripslashes', $_POST);
    $_COOKIE = array_map('stripslashes', $_COOKIE);
  }

// Set PHP error flags
    // 0                           Only fatal errors (e.g. syntax errors) will be displayed -- unless display_errors directive is off
    // 1     E_ERROR               Fatal run-time errors. Errors that can not be recovered from. Execution of the script is halted
    // 2     E_WARNING             Non-fatal run-time errors. Execution of the script is not halted
    // 4     E_PARSE               Compile-time parse errors. Parse errors should only be generated by the parser
    // 8     E_NOTICE              Run-time notices. The script found something that might be an error, but could also happen when running a script normally
    // 16    E_CORE_ERROR          Fatal errors at PHP startup. This is like an E_ERROR in the PHP core
    // 32    E_CORE_WARNING        Non-fatal errors at PHP startup. This is like an E_WARNING in the PHP core
    // 64    E_COMPILE_ERROR       Fatal compile-time errors. This is like an E_ERROR generated by the Zend Scripting Engine
    // 128   E_COMPILE_WARNING     Non-fatal compile-time errors. This is like an E_WARNING generated by the Zend Scripting Engine
    // 256   E_USER_ERROR          Fatal user-generated error. This is like an E_ERROR set by the programmer using the PHP function trigger_error()
    // 512   E_USER_WARNING        Non-fatal user-generated warning. This is like an E_WARNING set by the programmer using the PHP function trigger_error()
    // 1024  E_USER_NOTICE         User-generated notice. This is like an E_NOTICE set by the programmer using the PHP function trigger_error()
    // 2048  E_STRICT              Run-time notices. PHP suggest changes to your code to help interoperability and compatibility of the code
    // 4096  E_RECOVERABLE_ERROR   Catchable fatal error. This is like an E_ERROR but can be caught by a user defined handle (see also set_error_handler())
    // 8191  E_ALL                 All errors and warnings, except level E_STRICT (E_STRICT will be part of E_ALL as of PHP 6.0)
// Example: $error_flags = 'E_ERROR | E_WARNING | E_PARSE';
// equivalent to:        = '7';
$error_flags = 'E_ERROR | E_WARNING | E_PARSE';

// GATHER CONFIGURATION OVERRIDES FROM AN EXTERNAL FILE
@include_once ("config_override.php"); // Include override values only if the file exists




// ______ DEFNINITION OF CONSTANTS _________

// Highly unlikely that you will need to modify anything below this point

date_default_timezone_set($local_time_zone);
define('CURRENT_VERSION',         $current_version);
define('DB_NAME',                 $db_name);
define('HOST_NAME',               $db_host);
define('MYSQL_USER',              $db_user);
define('MYSQL_PASS',              $db_pass);
define('MD5_MASTER_PASSWORD',     $md5_master_password);
define('ORGANIZATION_TYPE',       $organization_type);
define('REQ_PRDCR_CONFIRM',       $req_prdcr_confirm);
define('DELIVERY_NO_PAYPAL',      $delivery_no_paypal);
define('STATE_TAX',               $state_tax);
define('SHOW_HEADER_LOGO',        $show_header_logo);
define('SHOW_HEADER_SITENAME',    $show_header_sitename);
define('FAVICON',                 $favicon);
define('USE_HTMLDOC',             $use_htmldoc);
define('DAYS_PER_CYCLE',          $days_per_cycle);
define('INSTITUTION_WINDOW',      $institution_window);
define('SHOW_ACTUAL_PRICE',       $show_actual_price);
define('EXCLUDE_ZERO_INV',        $exclude_zero_inv);
define('FILL_IN_MEMBER_ID',       $fill_in_member_id);
define('PRDCR_INFO_PUBLIC',       $prdcr_info_public);
define('HTMLDOC_PAGING',          $htmldoc_paging);
define('NEW_PRODUCER_PENDING',    $new_producer_pending);
define('NEW_PRODUCER_STATUS',     $new_producer_status);
define('RANDOM_CALC',             $random_calc);
define('MEMBERSHIP_IS_TAXED',     $membership_taxed);
define('COOP_FEE_IS_TAXED',       $coop_fee_taxed);
define('DATE_FORMAT_CLOSED',      $date_format_closed);
define('PAYS_PRODUCER_FEE',       $pays_producer_fee);
define('PAYS_CUSTOMER_FEE',       $pays_customer_fee);
define('PAYS_SUBCATEGORY_FEE',    $pays_subcategory_fee);
define('PAYS_PRODUCT_FEE',        $pays_product_fee);
define('AGGREGATE_TAXES',         $aggregate_taxes);          // [by basket] or [by product]
define('AGGREGATE_CUSTOMER_FEE',  $aggregate_customer_fee);   // [by basket] or [by product]
define('INTERNAL_DESIGNATION',    $internal_designation);
define('DAYS_CONSIDERED_NEW',     $days_considered_new);
define('DAYS_CONSIDERED_CHANGED', $days_considered_changed);

//General page information
define('BASE_URL',              $site_url);
define('PATH',                  $food_coop_store_path);
define('HOMEPAGE',              $homepage_url);
define('FILE_PATH',             $file_path);
define('INVOICE_FILE_PATH',     $invoice_file_path);
define('INVOICE_WEB_PATH',      $invoice_web_path);
define('c',                     $domainname); // cloverbeltlocalfoodcoop.com
define('DOMAIN_NAME',           $domainname);
define('SITE_NAME',             $site_name);
define('SITE_CONTACT_INFO',     $site_contact_info);
define('SITE_MAILING_ADDR',     $site_mailing_address);
define('ERROR_FLAGS',           $error_flags);


// Pages OUTSIDE of the FoodCoop application
define('DB_BACKUP_ROOT',        $db_backup_root);
define('MEMBERSHIP_PAGE',       $page_membership); //to refer membership questions
define('TERMS_OF_SERVICE',      $page_terms_of_service); //to refer membership for terms of use standards
define('LOCATIONS_PAGE',        $page_locations);
define('COOP_PRODUCERS_PAGE',   $page_coopproducers);
define('DIR_GRAPHICS',          $site_graphics);
define('SELF',                  $_SERVER['PHP_SELF']);
define('PER_PAGE',              $default_results_per_page); //default number of search results per page
define('ROUTE_CODE_TEMPLATE',   $route_code_template);

// table names as variables
$table_auth_level       = 'authentication_levels';
$table_availability     = 'availability';
$auth_table_name        = 'auth_users_c';
$table_cat              = 'categories';
$table_basket           = 'customer_basket_items';
$table_basket_all       = 'customer_basket_overall';
$table_customer_tax     = 'customer_salestax';
$table_delcode          = 'delivery_codes';
$table_deltypes         = 'delivery_types'; //new
$table_fdel             = 'future_deliveries';
$table_how_heard        = 'how_heard'; //new
$table_hubs             = 'hubs'; //new
$table_inventory        = 'inventory'; //new
$table_mem              = 'members';
$table_membership_types = 'membership_types';
$table_order_cycles     = 'order_cycles';
$table_pay              = 'payment_method';
$table_prdcr_all        = 'producer_totals'; //not in dB
$table_producers        = 'producers';
$table_prdcr_logos      = 'producers_logos'; //new
$table_prdcr_reg        = 'producers_registration'; //new
$table_prodtype         = 'production_types';
$table_product_img      = 'product_images'; //new
$table_products         = 'product_list'; //new
$table_products_temp    = 'product_list_a'; //new
$table_prep             = 'product_list_prep';
$table_previous         = 'product_list_previous';
$table_product_store    = 'product_storage_types'; //new
$table_product_map      = 'product_list_map'; //new
$table_product_unfi     = 'product_list_unfi'; //new
$table_unfi_status      = 'unfi_status'; //new
$table_rt               = 'routes';
$table_tax              = 'sales_tax';
$table_subcat           = 'subcategories';
$table_trans            = 'transactions';
$table_translation      = 'translation';
$table_trans_type       = 'transactions_types'; //new
$table_zip              = 'zip'; //new
$table_zip_city         = 'zip_citytaxno'; //new
$table_zip_county       = 'zip_countytaxno'; //new

//NEW TABLES WITH ACCOUNTING UPGRADE
$new_table_products               = 'products'; //new
$new_table_ledger                 = 'ledger'; //new
$new_table_transaction_group_enum = 'transaction_group_enum'; //new
$new_table_messages               = 'messages'; //new
$new_table_message_types          = 'message_types'; //new
$new_table_tax_rates              = 'tax_rates'; //new
$new_table_basket_items           = 'basket_items'; //new
$new_table_baskets                = 'baskets'; //new
$new_table_accounts               = 'accounts'; //new

// note: $table_prod is sometimes TABLE_PRODUCER_REG and sometimes TABLE_PRODUCT
// these are set in the other config files, as needed.

//Table aliases
define('DB_PREFIX',                   $db_prefix);
define('TABLE_AUTH',                  $db_prefix.$auth_table_name);
// define('TABLE_CHART_OF_ACCOUNTS',     $db_prefix.$table_chart_of_accounts);
define('TABLE_AUTH_LEVELS',           $db_prefix.$table_auth_level);
define('TABLE_AVAILABILITY',          $db_prefix.$table_availability);
define('TABLE_BASKET',                $db_prefix.$table_basket);
define('TABLE_BASKET_ALL',            $db_prefix.$table_basket_all);
define('TABLE_CATEGORY',              $db_prefix.$table_cat);
define('TABLE_CUSTOMER_SALESTAX',     $db_prefix.$table_customer_tax);
define('TABLE_DELCODE',               $db_prefix.$table_delcode);
define('TABLE_DELTYPE',               $db_prefix.$table_deltypes);
define('TABLE_FUTURE_DELIVERY',       $db_prefix.$table_fdel );
define('TABLE_HOW_HEARD',             $db_prefix.$table_how_heard);
define('TABLE_HUBS',                  $db_prefix.$table_hubs);
define('TABLE_INVENTORY',             $db_prefix.$table_inventory);
define('TABLE_MEMBER',                $db_prefix.$table_mem);
define('TABLE_MEMBERSHIP_TYPES',      $db_prefix.$table_membership_types);
define('TABLE_ORDER_CYCLES',          $db_prefix.$table_order_cycles);
define('TABLE_PAY',                   $db_prefix.$table_pay);
define('TABLE_PRODUCER',              $db_prefix.$table_producers);
define('TABLE_PRODUCER_LOGOS',        $db_prefix.$table_prdcr_logos); //new
define('TABLE_PRODUCER_REG',          $db_prefix.$table_prdcr_reg);
define('TABLE_PRODUCER_TOTALS',       $db_prefix.$table_prdcr_all);
define('TABLE_PRODUCT',               $db_prefix.$table_products);
define('TABLE_PRODUCT_IMAGES',        $db_prefix.$table_product_img); //new
define('TABLE_PRODUCT_PREP',          $db_prefix.$table_prep);
define('TABLE_PRODUCT_TEMP',          $db_prefix.$table_products_temp); //new
define('TABLE_PRODUCT_PREV',          $db_prefix.$table_previous);
define('TABLE_PRODUCT_TYPES',         $db_prefix.$table_prodtype);
define('TABLE_PRODUCT_STORAGE_TYPES', $db_prefix.$table_product_store);
define('TABLE_PRODUCT_MAP',           $db_prefix.$table_product_map);
define('TABLE_PRODUCT_UNFI',          $db_prefix.$table_product_unfi);
define('TABLE_UNFI_STATUS',           $db_prefix.$table_unfi_status);
define('TABLE_ROUTE',                 $db_prefix.$table_rt);
define('TABLE_SALES_TAX',             $db_prefix.$table_tax);
define('TABLE_SUBCATEGORY',           $db_prefix.$table_subcat);
define('TABLE_TRANSLATION',           $db_prefix.$table_translation);
define('TABLE_TRANSACTIONS',          $db_prefix.$table_trans);
define('TABLE_TRANS_TYPES',           $db_prefix.$table_trans_type);
define('TABLE_ZIP',                   $db_prefix.$table_zip);
define('TABLE_ZIP_CITYTAXNO',         $db_prefix.$table_zip_city);
define('TABLE_ZIP_COUNTYTAXNO',       $db_prefix.$table_zip_county);

//NEW TABLES WITH ACCOUNTING UPGRADE
define('NEW_TABLE_PRODUCTS',              $db_prefix.$new_table_products);
define('NEW_TABLE_LEDGER',                $db_prefix.$new_table_ledger);
define('NEW_TABLE_ADJUSTMENT_GROUP_ENUM', $db_prefix.$new_table_transaction_group_enum);
define('NEW_TABLE_MESSAGES',              $db_prefix.$new_table_messages);
define('NEW_TABLE_MESSAGE_TYPES',         $db_prefix.$new_table_message_types);
define('NEW_TABLE_TAX_RATES',             $db_prefix.$new_table_tax_rates);
define('NEW_TABLE_BASKET_ITEMS',          $db_prefix.$new_table_basket_items);
define('NEW_TABLE_BASKETS',               $db_prefix.$new_table_baskets);
define('NEW_TABLE_ACCOUNTS',              $db_prefix.$new_table_accounts);

//field aliases for Security.class
define('FIELD_USER',        'username');
define('FIELD_PASS',        'password');
define('FIELD_AUTH_TYPE',   'auth_type');

// contact e-mail addresses
define('CUSTOMER_EMAIL',        $email_customer);
define('GENERAL_EMAIL',         $email_general);
define('HELP_EMAIL',            $email_help);
define('MEMBERSHIP_EMAIL',      $email_membership);
define('ORDER_EMAIL',           $email_orders);
// define('PAYPAL_EMAIL',          $email_paypal);
define('PRICELIST_EMAIL',       $email_pricelist);
define('PROBLEMS_EMAIL',        $email_problems);
define('PRODUCER_CARE_EMAIL',   $email_producer_care);
define('SOFTWARE_EMAIL',        $email_software);
define('STANDARDS_EMAIL',       $email_standards);
define('TREASURER_EMAIL',       $email_treasurer);
define('VOLUNTEER_EMAIL',       $email_volunteer);
define('WEBMASTER_EMAIL',       $email_webmaster);

define('MEMBER_FORM_EMAIL',     $email_member_form);
define('PRODUCER_FORM_EMAIL',   $email_producer_form);

define('AUTHORIZED_PERSON',     $authorized_person);
define('DEBUG',                 $debug);
// Constants can not hold arrays, so this must be serialized:
define('FIELDS_REQ_CONFIRM',    serialize ($fields_req_confirm));
define('KEEP_OLD_PROD_CONF',    $keep_old_prod_conf);

$table_prod = TABLE_PRODUCER_REG;

$connection = @mysql_connect(HOST_NAME, MYSQL_USER, MYSQL_PASS, TRUE) or die("Couldn't connect: \n".mysql_error());
$db = @mysql_select_db(DB_NAME, $connection) or die(mysql_error());

// Set error reporting level
error_reporting(ERROR_FLAGS);

function valid_auth ($auth_type)
  {
    // If the current auth_type is not even "member" then go to the login page
    if (! CurrentMember::auth_type('member'))
      {
        session_start();
        if (count($_GET) > 0) $_SESSION['_GET'] = $_GET;
        if (count($_POST) > 0) $_SESSION['_POST'] = $_POST;
        $_SESSION['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
        header( 'Location: index_ofs.php');
        exit(0);
      }
    // Check against all the passed auth_type options to see if any of them is okay
    else
      {
        $auth_fail = true;
        foreach (explode (',', $auth_type) as $test_auth)
          {
            if (CurrentMember::auth_type($test_auth))
              {
                $auth_fail = false;
              }
          }
        if ($auth_fail)
          {
            header( "Location: index_ofs.php");
            exit(0);
          }
        else
          {
            // Restore the $_POST and $_GET variables from the last (failed) access
            // But do not unset any *real* GET or POST values
            if ($_SESSION['_POST'])
              {
                $_POST = $_SESSION['_POST'];
                unset ($_SESSION['_POST']);
              }
            if ($_SESSION['_GET'])
              {
                $_GET = $_SESSION['_GET'];
                unset ($_SESSION['_GET']);
              }
          }
      }
  }

function debug_print ($text, $data, $target = NULL)
  {
    if (DEBUG == true)
      {
        if (substr($text, 0, 6) == 'ERROR:') $color = '#900';
        elseif (substr($text, 0, 5) == 'WARN:') $color = '#009';
        elseif (substr($text, 0, 5) == 'INFO:') $color = '#060';
        else $color = '#000';

        $message = '
          <pre style="color:'.$color.';">'.date('Y-m-d H:i:s',time()).' ['.$_SESSION['member_id'].']<br>'.$text.$target.'<br>'.print_r ($data, true).'</pre>';
        $destination = FILE_PATH.PATH.'errors.html';
        error_log ($message, 3, $destination);

//         $message = '
//           '.$text.$target.'
//           '.print_r ($data, true);
//         error_log ($message, 0);

//         echo $message;
      }
  }

// Include classes for ActiveCycle, CurrentBasket, and CurrentMember
include ('classes_base.php');

?>
