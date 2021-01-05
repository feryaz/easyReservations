<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$room_category = get_option( 'reservations_room_category' );
if ( isset( $room_category ) && ! empty( $room_category ) && is_numeric( $room_category ) ) {
	$args   = array( 'category' => $room_category, 'post_type' => 'post', 'post_status' => 'publish|private', 'orderby' => 'post_title', 'order' => 'ASC', 'numberposts' => - 1 );
	$getids = get_posts( $args );
	foreach ( $getids as $post ) {
		$id = $post->ID;
		$wpdb->query( "UPDATE " . $wpdb->prefix . "posts SET post_type='easy-rooms' WHERE ID='$id' " );
		$wpdb->query( "DELETE FROM " . $wpdb->prefix . "term_relationships WHERE object_id='$id'  " );
	}
}
$offer_category = get_option( 'reservations_special_offer_cat' );
if ( isset( $offer_category ) && ! empty( $offer_category ) && is_numeric( $room_category ) ) {
	$args   = array( 'category' => $offer_category, 'post_type' => 'post', 'post_status' => 'publish|private', 'orderby' => 'post_title', 'order' => 'ASC', 'numberposts' => - 1 );
	$getids = get_posts( $args );
	foreach ( $getids as $post ) {
		$id = $post->ID;
		$wpdb->query( "UPDATE " . $wpdb->prefix . "posts SET post_type='easy-offers' WHERE ID='$id' " );
		$wpdb->query( "DELETE FROM " . $wpdb->prefix . "term_relationships WHERE object_id='$id'" );
	}
}
delete_option( 'reservations_room_category' );
delete_option( 'reservations_special_offer_cat' );
$wpdb->query( "ALTER TABLE " . $wpdb->prefix . "reservations CHANGE custom custom longtext" );
$wpdb->query( "ALTER TABLE " . $wpdb->prefix . "reservations CHANGE customp customp longtext" );
$reservations = $wpdb->get_results( "SELECT id, custom, customp FROM " . $wpdb->prefix . "reservations" );
foreach ( $reservations as $reservation ) {
	$id              = $reservation->id;
	$customs         = $reservation->custom;
	$explode_customs = explode( '&;&', $customs );
	$new_customs     = array();
	if ( isset( $explode_customs[1] ) ) {
		foreach ( $explode_customs as $custom ) {
			if ( ! empty( $custom ) ) {
				$explode_the_custom = explode( '&:&', $custom );
				$new_customs[]      = array( 'type' => 'cstm', 'mode' => 'edit', 'title' => $explode_the_custom[0], 'value' => $explode_the_custom[1] );
			}
		}
	} elseif ( isset( $explode_customs[0] ) && strlen( $explode_customs[0] ) > 5 ) {
		$explode_the_custom = explode( '&:&', $explode_customs[0] );
		$new_customs[]      = array( 'type' => 'cstm', 'mode' => 'edit', 'title' => $explode_the_custom[0], 'value' => $explode_the_custom[1] );
	}
	$customsp        = $reservation->customp;
	$explode_customp = explode( '&;&', $customsp );
	$new_customp     = array();
	if ( isset( $explode_customp[1] ) ) {
		foreach ( $explode_customp as $customp ) {
			if ( ! empty( $customp ) ) {
				$explode_the_custom = explode( '&:&', $customp );
				$explode_the_price  = explode( ':', $explode_the_custom[1] );
				$new_customp[]      = array( 'type' => 'cstm', 'mode' => 'edit', 'title' => $explode_the_custom[0], 'value' => $explode_the_price[0], 'amount' => $explode_the_price[1] );
			}
		}
	} elseif ( isset( $explode_customp[0] ) && strlen( $explode_customp[0] ) > 5 ) {
		$explode_the_custom = explode( '&:&', $explode_customp[0] );
		$explode_the_price  = explode( ':', $explode_the_custom[1] );
		$new_customp[]      = array( 'type' => 'cstm', 'mode' => 'edit', 'title' => $explode_the_custom[0], 'value' => $explode_the_price[0], 'amount' => $explode_the_price[1] );
	}
	$save_custom  = ! empty( $new_customs ) ? maybe_serialize( array( $new_customs ) ) : '';
	$save_customp = ! empty( $new_customp ) ? maybe_serialize( array( $new_customp ) ) : '';
	$wpdb->query( "UPDATE " . $wpdb->prefix . "reservations SET custom='$save_custom', customp='$save_customp' WHERE id='$id' " );
	unset( $new_customs );
	unset( $new_customp );
}
$wpdb->query( "ALTER TABLE " . $wpdb->prefix . "reservations ADD user int(10) NOT NULL" );
$wpdb->query( "UPDATE " . $wpdb->prefix . "reservations SET user='0'" );
add_option( 'reservations_uninstall', '1', '', 'no' );
