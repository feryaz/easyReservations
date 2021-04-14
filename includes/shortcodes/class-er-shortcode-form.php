<?php

class ER_Shortcode_Form {

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
		self::form( $atts );
	}

	/**
	 * @param array $atts
	 */
	public static function form( $atts ) {
		if ( ! in_the_loop() && did_action( 'wp_print_scripts' ) === 0 ) {
			return;
		}

		$atts = shortcode_atts( array(
			0                 => '',
			'redirect'        => '',
			'inline'          => false,
			'price'           => 1,
			'direct_checkout' => 0,
			'button_text'     => __( 'Submit', 'easyReservations' ),
		), $atts );

		if ( is_numeric( $atts['redirect'] ) ) {
			$atts['redirect'] = get_permalink( intval( $atts['redirect'] ) );
		}

		$checkout = ER()->checkout();

		ER()->reservation_form()->get_form( $atts, 'form' );
	}
}