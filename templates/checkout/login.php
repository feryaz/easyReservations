<?php
/**
 * Checkout login form
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/checkout/login.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates
 * @version 1.0
 */
defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() || 'no' === get_option( 'reservations_enable_checkout_login_reminder' ) ) {
	return;
}

?>
    <div class="easyreservations-form-login-toggle">
		<?php er_print_notice( apply_filters( 'easyreservations_checkout_login_message', __( 'Returning customer?', 'easyReservations' ) ) . ' <a href="#" class="showlogin">' . __( 'Click here to login', 'easyReservations' ) . '</a>', 'notice' ); ?>
    </div>
<?php

easyreservations_login_form(
	array(
		'message'  => __( 'If you have shopped with us before, please enter your details below. If you are a new customer, please proceed to the address section.', 'easyReservations' ),
		'redirect' => er_get_checkout_url(),
		'hidden'   => true,
	)
);
