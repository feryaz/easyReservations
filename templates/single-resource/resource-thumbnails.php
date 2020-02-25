<?php
/**
 * Single Resource Thumbnails
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource/resource-thumbnail.php.
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

defined( 'ABSPATH' ) || exit;

global $resource;

$attachment_ids = $resource->get_gallery_image_ids();

if ( $attachment_ids ) {
	foreach ( $attachment_ids as $attachment_id ) {
		echo apply_filters( 'easyreservations_single_resource_image_thumbnail_html', er_get_gallery_image_html( $attachment_id ), $attachment_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped
	}
}