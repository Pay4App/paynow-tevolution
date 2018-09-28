<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Tevolution configuration options for paynow
global $paynow_method_info;

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

function tevolution_paynow_plugin_activation() {
  global $paynow_method_info;
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

function tevolution_paynow_plugin_deactivation() {
  delete_option('payment_method_paynow');
}

if(strtolower($_REQUEST['install']) == 'paynow'){
  tevolution_paynow_plugin_activation();
} elseif($_REQUEST['uninstall'] == 'paynow') {
  tevolution_paynow_plugin_deactivation();
}
