<?php

class ER_Shortcode_Cart {

	/**
	 * Get the shortcode content.
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function get( $atts ) {
		return ER_Shortcodes::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	public static function output( $atts ) {
		if ( ( ! in_the_loop() && did_action( 'wp_print_scripts' ) == 0 ) || ! apply_filters( 'easyreservations_output_cart_shortcode_content', true ) ) {
			return;
		}

		$cart = ER()->cart;
		$atts = shortcode_atts( array(), $atts, 'easy_cart' );

		// Check cart items are valid.
		do_action( 'easyreservations_check_cart_items' );

		// Calc totals.
		ER()->cart->calculate_totals();

		if ( ER()->cart->is_empty() ) {
			er_get_template( 'cart/cart-empty.php' );
		} else {
			er_get_template( 'cart/cart.php' );
		}
	}
}