<?php
/**
 * Single resource short description
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource/short-description.php.
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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $resource;

$short_description = apply_filters( 'easyreservations_short_description', $resource->get_excerpt() );

if ( ! $short_description ) {
	return;
}

?>
<div class="easyreservations-resource-details__short-description">
	<?php echo $short_description; // WPCS: XSS ok. ?>
</div>