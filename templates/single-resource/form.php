<?php
/**
 * Resource form
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource/form.php.
 *
 * HOWEVER, on occasion easyReservations will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package easyReservations/Templates
 * @version 1.0
 */

global $resource;

$redirect = get_option( 'reservations_resource_page_redirect', er_get_page_permalink( 'shop' ) );
if ( $redirect ) {
	$redirect = get_permalink( intval( $redirect ) );
}

$template = $resource->get_form_template();

echo do_shortcode( '[easy_form ' . $template . ' redirect="' . esc_url( $redirect ) . '" inline="1"]' );