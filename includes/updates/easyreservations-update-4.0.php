<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$resources = get_posts( array( 'post_type' => 'easy-rooms', 'orderby' => 'post_title', 'order' => 'ASC', 'numberposts' => - 1, 'post_status' => 'publish|private' ) );
foreach ( $resources as $resource ) {
	$children_discount = get_post_meta( $resource->ID, 'reservations_child_price', true );
	$base_price        = get_post_meta( $resource->ID, 'reservations_groundprice', true );

	if ( $children_discount && ! empty( $children_discount ) && $children_discount !== '' ) {
		$new_price = $base_price - $children_discount;
		update_post_meta( $resource->ID, 'reservations_child_price', $new_price );
	}
}

$dummy    = new easyReservations_form_widget();
$settings = $dummy->get_settings();
$changed  = false;
foreach ( $settings as $k => $setting ) {
	if ( isset( $setting['form_editor'] ) ) {
		$changed = true;
		add_option( 'reservations_form_old-widget-' . $k, html_entity_decode( str_replace( '<br>', "\n", $setting['form_editor'] ) ), false, false );
		$settings[ $k ]['form_template'] = 'old-widget-' . $k;
		unset( $settings[ $k ]['form_editor'] );
	}
}
if ( $changed ) {
	$dummy->save_settings( $settings );
}

$the_search_bar = get_option( 'reservations_search_bar' );
if ( $the_search_bar && ! empty( $the_search_bar ) ) {
	add_option( 'reservations_form_old-search-bar', html_entity_decode( str_replace( '<br>', "\n", $the_search_bar ) ), false, false );
	delete_option( 'reservations_search_bar' );
}

$default = '<label>Arrival:</label>[date-from style="width:95px"] [date-from-hour][arrival_minute]' . "\n" . '<label>Departure:</label>[date-to style="width:95px"] [departure_hour][departure_minute]' . "\n" . '<label>Res:</label> [resources]' . "\n" . '<label>Name:</label> [name]' . "\n" . '<label>Email:</label> [email]' . "\n" . '<label>Country:</label> [country]';
add_option( 'reservations_form_default-widget', $default, false, 'no' );
