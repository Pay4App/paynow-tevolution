<?php

error_reporting(E_ALL); ini_set("display_errors", 1);

if ( ! defined( 'ABSPATH' ) ) exit;

function tevolution_paynow_gateway_on() {
  // Tevolution configuration options for paynow
  $paynow_opts = array();
  $paynow_opts [] = array(
    'title' => 'Integration ID',
    'fieldname' => 'integration_id',
    'type' => 'text',
    'value' => '',
    'description' => __('Your Paynow integration ID')
  );
  
  $paynow_opts [] = array(
    'title' => 'Integration Secret',
    'fieldname' => 'integration_secret',
    'type' => 'text',
    'value' => '',
    'description' => __('Your Paynow integration secret')
  );
  
  $paynow_method_info = array(
    'name' => 'paynow',
    'key' => 'paynow',
    'isactive' => 1,
    'display_order' => 4,
    'payOpts' => $paynow_opts,
  );

  update_option('payment_method_paynow', $paynow_method_info);

  // check if Paynow webhooks entry point exists 
  $ipnFilePath = TEMPL_PAYMENT_FOLDER_PATH . 'paynow/ipn_process.php';
  if ( ! file_exists( $ipnFilePath ) ) {
    $source = plugin_dir_path( __FILE__ ) . '../templatic';
    if (! copy_dir(
            $source,
            TEMPL_PAYMENT_FOLDER_PATH
          )
    ) {
      ob_clean();
      die('<h1>Failed to copy ipn_process from plugin base</h1>'.$source);
    }
  }
}

function tevolution_paynow_gateway_off() {
  delete_option('payment_method_paynow');
}

/* WordPress plugin hooks suffice for this
if(strtolower($_REQUEST['install']) == 'paynow'){
  // call activation
} elseif($_REQUEST['uninstall'] == 'paynow') {
  // call deactivation
}
*/
