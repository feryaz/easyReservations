<?php
/**
 * Email Addresses (plain)
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/plain/email-addresses.php.
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

echo "\n" . esc_html( er_strtoupper( esc_html__( 'Address', 'easyReservations' ) ) ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $order->get_formatted_address() ) . "\n"; // WPCS: XSS ok.

if ( $order->get_phone() ) {
	echo esc_html( $order->get_phone() ) . "\n"; // WPCS: XSS ok.
}

if ( $order->get_email() ) {
	echo esc_html( $order->get_email() ) . "\n"; // WPCS: XSS ok.
}
