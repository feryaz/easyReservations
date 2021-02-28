<?php
/**
 * Shopping Cart Widget.
 *
 * Displays shopping cart widget.
 *
 * @package easyReservations/Widgets
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget cart class.
 */
class ER_Widget_Cart extends ER_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'easyreservations widget_shopping_cart';
		$this->widget_description = __( 'Display the easyReservations shopping cart.', 'easyReservations' );
		$this->widget_id          = 'easyreservations_widget_cart';
		$this->widget_name        = __( 'easyReservations Cart', 'easyReservations' );
		$this->settings           = array(
			'title'         => array(
				'type'  => 'text',
				'std'   => __( 'Cart', 'easyReservations' ),
				'label' => __( 'Title', 'easyReservations' ),
			),
			'hide_if_empty' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide if cart is empty', 'easyReservations' ),
			),
		);

		if ( is_customize_preview() ) {
			wp_enqueue_script( 'er-cart-fragments' );
		}

		parent::__construct();
	}

	/**
	 * Output widget.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( apply_filters( 'easyreservations_widget_cart_is_hidden', is_easyreservations_cart() || is_easyreservations_checkout() ) ) {
			return;
		}

		$hide_if_empty = empty( $instance['hide_if_empty'] ) ? 0 : 1;

		if ( ! isset( $instance['title'] ) ) {
			$instance['title'] = __( 'Cart', 'easyReservations' );
		}

		$this->widget_start( $args, $instance );

		if ( $hide_if_empty ) {
			echo '<div class="hide_cart_widget_if_empty">';
		}

		// Insert cart widget placeholder - code in easyreservations.js will update this on page load.
		echo '<div class="widget_shopping_cart_content"></div>';

		if ( $hide_if_empty ) {
			echo '</div>';
		}

		$this->widget_end( $args );
	}
}
