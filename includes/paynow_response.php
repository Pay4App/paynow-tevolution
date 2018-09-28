<?php

if ( ! defined( 'ABSPATH' ) ) exit;

error_reporting(E_ALL); ini_set("display_errors", 1); 

require 'paynow/vendor/autoload.php';

global $General, $Cart, $payable_amount, $post_title, $last_postid, $trans_id, $wpdb;

$paymentOpts = templatic_get_payment_options( $_REQUEST['paymentmethod'] );
$integrationID = $paymentOpts['integration_id'];
$integrationSecret = $paymentOpts['integration_secret'];

/*Price Package info */
$price_package_id = get_post_meta( $last_postid,'package_select',true );

if ( ! $price_package_id ) {
	$trans_detail = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'transactions WHERE trans_id =' . $trans_id );
	/* get package name from package id */
	$price_package_id = $trans_detail->package_id;
}
$package_amount = get_post_meta( $price_package_id,'package_amount',true );
$validity = get_post_meta( $price_package_id,'validity',true );
$validity_per = get_post_meta( $price_package_id,'validity_per',true );
$recurring = get_post_meta( $price_package_id,'recurring',true );
$billing_num = get_post_meta( $price_package_id,'billing_num',true );
$billing_per = get_post_meta( $price_package_id,'billing_per',true );
$billing_cycle = get_post_meta( $price_package_id,'billing_cycle',true );
if ( $integrationID == '' ) {
  ob_clean();
  die('You cannot pay with Paynow at the moment. Please contact support. We apologise for the inconvenience.');
}

//REDIRECTED ON SUCCESS PAGE START.
$suburl = '';
if ( 'upgradenow' == $_REQUEST['page'] ) {
	$suburl = '&upgrade=pkg';
}

$currency_code = templatic_get_currency_type();
$post = get_post( $last_postid );

$post_title = '';

if ($post) {
  $post_title = apply_filters( 'tmpl_trans_title',$post->post_title );
}

global $wpdb;

/* if subscription package is done then show package name in 2CO item name */
if ( $post_title == 'Username' || $last_postid == 0 ) {
	/* get transaction details for getting package id */
	$trans_detail = $wpdb->get_row( 'SELECT * FROM ' . $wpdb->prefix . 'transactions WHERE trans_id =' . $trans_id );
	/* get package name from package id */
	$post_title = get_the_title( $trans_detail->package_id );
}

/* get success page with permalink */
$post_id = tmpl_get_post_id_by_meta_key_and_value( 'is_tevolution_success_page', '1' );
$success_page_url = get_permalink( $post_id );
$returnUrl = apply_filters( 'tmpl_returnUrl', $success_page_url . '?ptype=return&pmethod=paynow&trans_id=' . $trans_id . $suburl );
$cancel_return = apply_filters( 'tmpl_cancel_return', $success_page_url . '?ptype=cancel&pmethod=paynow&trans_id=' . $trans_id . $suburl );
$notify_url = apply_filters( 'tmpl_notify_url', $success_page_url . '?ptype=notifyurl&pmethod=paynow&trans_id=' . $trans_id . $suburl );

$paynow = new \Paynow\Paynow($integrationID, $integrationSecret);
$transaction = $paynow->initiatePayment(
    $trans_id,
    $payable_amount,
    $post_title,
    $returnUrl,
    $notify_url
);

wp_redirect($transaction->browserurl);
exit;
