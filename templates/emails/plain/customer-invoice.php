<?php
/**
 * Customer invoice email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/plain/customer-invoice.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates/Emails/Plain
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'easyReservations' ), esc_html( $order->get_first_name() ) ) . "\n\n";

if ( $order->has_status( 'pending' ) ) {
	echo wp_kses_post(
		     sprintf(
		     /* translators: %1$s: Site title, %2$s: Order pay link */
			     __( 'An order has been created for you on %1$s. Your invoice is below, with a link to make payment when you’re ready: %2$s', 'easyReservations' ),
			     esc_html( get_bloginfo( 'name', 'display' ) ),
			     esc_url( $order->get_checkout_payment_url() )
		     )
	     ) . "\n\n";
} else {
	/* translators: %s: Order date */
	echo sprintf( esc_html__( 'Here are the details of your order placed on %s:', 'easyReservations' ), esc_html( er_format_datetime( $order->get_date_created() ) ) ) . "\n\n";
}

/**
 * Hook for the easyreservations_email_order_details.
 *
 * @hooked ER_Emails::order_details() Shows the order details table.
 * @hooked ER_Structured_Data::generate_order_data() Generates structured data.
 * @hooked ER_Structured_Data::output_structured_data() Outputs structured data.
 */
do_action( 'easyreservations_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Hook for the easyreservations_email_order_meta.
 *
 * @hooked ER_Emails::order_meta() Shows order meta data.
 */
do_action( 'easyreservations_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for easyreservations_email_customer_details
 *
 * @hooked ER_Emails::customer_details() Shows customer details
 * @hooked ER_Emails::email_address() Shows email address
 */
do_action( 'easyreservations_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'easyreservations_email_footer_text', get_option( 'reservations_email_footer_text' ) ) );
