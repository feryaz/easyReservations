<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "ALTER TABLE " . $wpdb->prefix . "reservations ADD arrival datetime NOT NULL" );
$wpdb->query( "ALTER TABLE " . $wpdb->prefix . "reservations ADD departure datetime NOT NULL" );
$reservations = $wpdb->get_results( "SELECT id, arrivalDate, nights, notes FROM " . $wpdb->prefix . "reservations" );
foreach ( $reservations as $reservation ) {
	$id          = $reservation->id;
	$arrivalDate = strtotime( $reservation->arrivalDate );
	$nights      = $reservation->nights;
	$arrival     = date( "Y-m-d H:i", $arrivalDate + 43200 );
	$departure   = date( "Y-m-d H:i", $arrivalDate + ( 86400 * $nights ) + 43200 );
	$wpdb->query( "UPDATE " . $wpdb->prefix . "reservations SET arrival='$arrival', departure='$departure' WHERE id='$id' " );
}