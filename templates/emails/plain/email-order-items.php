<?php
/**
 * Email Order Items (plain)
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/emails/plain/email-order-items.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

foreach ( $items as $item_id => $item ) :
	if ( apply_filters( 'easyreservations_order_item_visible', true, $item ) ) {
		$reservation   = false;
		$resource      = false;
		$sku           = '';
		$purchase_note = '';

		if ( method_exists( $item, 'get_resource_id' ) ) {
			$resource = $item->get_resource();
		}

		if ( method_exists( $item, 'get_reservation_id' ) ) {
			$reservation = $item->get_reservation();
		}

		if ( is_object( $resource ) ) {
			$sku           = $resource->get_sku();
			$purchase_note = $resource->get_purchase_note();
		}

		echo apply_filters( 'easyreservations_order_item_name', $reservation ? $reservation->get_name() : $item->get_name(), $item, false );
		if ( $show_sku && $sku ) {
			echo ' (#' . $sku . ')';
		}
		echo ' = ' . $order->get_formatted_item_subtotal( $item ) . "\n";

		// allow other plugins to add additional resource information here
		do_action( 'easyreservations_order_item_meta_start', $item_id, $item, $order, $plain_text );

		echo strip_tags( er_display_meta( $item->get_formatted_meta_data(), array(
			'before'    => "\n- ",
			'separator' => "\n- ",
			'after'     => "",
			'echo'      => false,
			'autop'     => false,
		) ) );

		// allow other plugins to add additional resource information here
		do_action( 'easyreservations_order_item_meta_end', $item_id, $item, $order, $plain_text );
	}
	// Note
	if ( $show_purchase_note && $purchase_note ) {
		echo "\n" . do_shortcode( wp_kses_post( $purchase_note ) );
	}
	echo "\n\n";
endforeach;
