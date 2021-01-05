<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$showhide = array( 'show_overview' => 1, 'show_table' => 1, 'show_upcoming' => 1, 'show_new' => 1, 'show_export' => 1, 'show_today' => 1 );
$table    = array( 'table_color' => 1, 'table_id' => 0, 'table_name' => 1, 'table_from' => 1, 'table_to' => 1, 'table_nights' => 1, 'table_email' => 1, 'table_room' => 1, 'table_exactly' => 1, 'table_offer' => 1, 'table_persons' => 1, 'table_childs' => 1, 'table_country' => 1, 'table_message' => 0, 'table_custom' => 0, 'table_paid' => 0, 'table_price' => 1, 'table_filter_month' => 1, 'table_filter_room' => 1, 'table_filter_offer' => 1, 'table_filter_days' => 1, 'table_search' => 1, 'table_bulk' => 1, 'table_onmouseover' => 1, 'table_reservated' => 0, 'table_status' => 1, 'table_fav' => 1 );
$overview = array( 'overview_onmouseover' => 1, 'overview_autoselect' => 1, 'overview_show_days' => 30, 'overview_show_rooms' => '' );
add_option( 'reservations_main_options', array( 'show' => $showhide, 'table' => $table, 'overview' => $overview ), '', 'no' );

$settings_array = array( 'style' => get_option( "reservations_style" ), 'interval' => 86400, 'currency' => get_option( "reservations_currency" ), 'date_format' => get_option( "reservations_date_format" ), 'time_format' => 'H:i', 'time' => 1 );
add_option( "reservations_settings", $settings_array );
delete_option( "reservations_style" );
delete_option( "reservations_interval" );
delete_option( "reservations_currency" );
delete_option( "reservations_date_format" );
$wpdb->query( "DROP TABLE " . $wpdb->prefix . "reservations DROP arrivalDate, nights, special, dat, notes" );
