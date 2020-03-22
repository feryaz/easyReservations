<?php
/*
Plugin Name: easyReservations
Plugin URI: http://www.easyreservations.org
Description: This powerful property and reservation management plugin allows you to receive, schedule and handle your bookings easily!
Version: 6.0-alpha.9
Author: Feryaz Beer
Author URI: http://www.feryaz.de
License:GPL2
*/

//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'RESERVATIONS_PLUGIN_FILE' ) ) {
	define( 'RESERVATIONS_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'easyReservations' ) ) {
	include_once dirname( RESERVATIONS_PLUGIN_FILE ) . '/includes/class-easyreservations.php';
}

/**
 * Main instance of easyReservations.
 *
 * Returns the main instance of ER to prevent the need to use globals.
 *
 * @return easyReservations
 */
function ER() {
	return easyReservations::instance();
}

ER();