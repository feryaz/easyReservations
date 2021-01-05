<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$edit_text    = get_option( 'reservations_edit_text' );
$edit_options = array( 'login_text' => stripslashes( $edit_text ), 'edit_text' => stripslashes( $edit_text ), 'table_infos' => array( 'date', 'status', 'price', 'room' ), 'table_status' => array( '', 'yes', 'no' ), 'table_time' => array( 'past', 'current', 'future' ), 'table_style' => 1, 'table_more' => 1 );
add_option( 'reservations_edit_options', $edit_options, '', false );
add_option( 'reservations_date_format', 'd.m.Y', '', true );
delete_option( 'reservations_edit_text' );
