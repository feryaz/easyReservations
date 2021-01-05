<?php
/**
 * Single Product Sale Flash
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource/sale-flash.php.
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
	exit; // Exit if accessed directly
}

global $post, $resource;

?>
<?php if ( $resource->is_on_sale() ) : ?>

	<?php echo apply_filters( 'easyreservations_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'easyReservations' ) . '</span>', $post, $resource ); ?>

	<?php
endif;

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
