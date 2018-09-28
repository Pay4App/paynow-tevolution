<?php

$paynowWebhooksHandler = WP_PLUGIN_DIR.'/Tevolution-paynow/includes/paynow_webhook.php';

if ( file_exists( $paynowWebhooksHandler ) ) {
  include( $paynowWebhooksHandler );
}
