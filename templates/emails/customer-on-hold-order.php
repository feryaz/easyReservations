<?php
/**
 * Customer on-hold order email
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/customer-on-hold-order.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates/Emails
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked ER_Emails::email_header() Output the email header
 */
do_action( 'easyreservations_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
    <p><?php printf( esc_html__( 'Hi %s,', 'easyReservations' ), esc_html( $order->get_first_name() ) ); ?></p>
    <p><?php esc_html_e( 'Thanks for your order. It’s on-hold until we confirm that payment has been received. In the meantime, here’s a reminder of what you ordered:', 'easyReservations' ); ?></p>

<?php

/*
 * @hooked ER_Emails::order_details() Shows the order details table.
 * @hooked ER_Structured_Data::generate_order_data() Generates structured data.
 * @hooked ER_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'easyreservations_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked ER_Emails::order_meta() Shows order meta data.
 */
do_action( 'easyreservations_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked ER_Emails::customer_details() Shows customer details
 * @hooked ER_Emails::email_address() Shows email address
 */
do_action( 'easyreservations_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked ER_Emails::email_footer() Output the email footer
 */
do_action( 'easyreservations_email_footer', $email );
