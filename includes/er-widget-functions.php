<?php
/**
 * easyReservations Widget Functions
 *
 * Widget related functions and widget registration.
 *
 * @package easyReservations/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include widget classes.
require_once dirname( __FILE__ ) . '/abstracts/abstract-er-widget.php';
require_once dirname( __FILE__ ) . '/widgets/class-er-widget-cart.php';
require_once dirname( __FILE__ ) . '/widgets/class-er-widget-resources.php';

/**
 * Register Widgets.
 */
function er_register_widgets() {
	register_widget( 'ER_Widget_Cart' );
	register_widget( 'ER_Widget_Resources' );
}

add_action( 'widgets_init', 'er_register_widgets' );
