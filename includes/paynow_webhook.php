<?php

/**
 * Paynow webhooks processing file (IPN Process file in Tevolution terms)
 * This file will react to positive and negative payment status updates from Paynow
 *
 * Where the plugin exits with an error (wp_die), this is to prompt Paynow to retry another time
 */

global $wpdb;
$transaction_db_table_name = $wpdb->prefix . 'transactions';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (!isset($_POST['reference'])) exit();
require 'paynow/vendor/autoload.php';

// retrieve payment config
$paymentOpts = templatic_get_payment_options( 'paynow' );
$integrationID = $paymentOpts['integration_id'];
$integrationSecret = $paymentOpts['integration_secret'];

// exit with error if the gateway credentias are not configured
if (!$integrationID || !$integrationSecret) wp_die('Gateway credentials not confused');

$paynow = new \Paynow\Paynow( $$integrationID, $integrationSecret );

// exit with error if we cannot process the status update for whatever reason
try {
  $transactionDetails = $paynow->processStatusUpdate( $_POST );
} catch (\Paynow\UpdateProcessingException $e) {
  // todo: Maybe send note to admin
  wp_die();
}

$transactionID = $transactionDetails->reference;
$paynowTransactionID = $transactionDetails->paynowreference;
$paynowTransactionStatus = $transactionDetails->status;
$paynowTransactionAmount = $transactionDetails->amount;

$sql = "select * from {$transaction_db_table_name} where trans_id = %d";
$transaction = $wpdb->get_row( $wpdb->prepare( $sql, $transactionID ) );

// transaction gone 🤷‍
if (!$transaction) return;

// if the transation has been marked as paid already, return clean
if ((int) $transaction->status === 1) return;

$isCancelledOnPaynow = in_array( $paynowTransactionStatus, array( 'Cancelled', 'Disputed', 'Refunded' ) );
$isPaidOnPaynow = in_array( $paynowTransactionStatus, array( 'Paid', 'Awaiting Delivery', 'Delivered' ) );

// mark transaction as cancelled to not lock up the customer from another purchase
// (Tevolution will lock future payments if there is a transaction with status 'Pending')
if ( $isCancelledOnPaynow ) {
  // todo: 'cancel' the transaction
  $wpdb->query( $wpdb->prepare( "UPDATE {$transaction_db_table_name} set status=2, payment_date = %s where trans_id = %d", date( 'Y-m-d H:i:s' ), $transaction->trans_id ) );

  // delete the transaction
  // $wpdb->query( $wpdb->prepare( "delete from {$transaction_db_table_name} where trans_id = %d", $transaction->trans_id ) );
  return;
}

// mark transaction as 'approved'
if ($isPaidOnPaynow)  {
  // $wpdb->query( "UPDATE {$transaction_db_table_name} set status=1,payment_date='" . date( 'Y-m-d H:i:s' ) . ' where trans_id=$sql_data->trans_id' );
  $wpdb->query(
    $wpdb->prepare(
      "UPDATE {$transaction_db_table_name} set status=1, payment_date = %s where trans_id = %d",
      date( 'Y-m-d H:i:s' ),
      $transaction->trans_id
    )
  );

  // publish linked submission (if any)
  if ( (int)$transaction->post_id ) {
    wp_publish_post( $transaction->post_id );
    // update publish time
    $wpdb->query(
      $wpdb->prepare(
        "UPDATE {$wpdb->posts} SET post_date=%s where ID = %d",
        date( 'Y-m-d H:i:s' ),
        $transaction->post_id
      )
    );
  }
}
