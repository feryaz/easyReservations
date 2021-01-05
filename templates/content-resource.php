<?php
/**
 * The template for displaying resource content within loops
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/content-resource.php.
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

// Ensure visibility.
if ( empty( $resource ) ) {
	return;
}
?>
<li class="resource <?php echo er_get_loop_class(); ?>">
	<?php
	/**
	 * Hook: easyreservations_before_shop_loop_item.
	 *
	 * @hooked easyreservations_template_loop_resource_link_open - 10
	 */
	do_action( 'easyreservations_before_shop_loop_item' );

	/**
	 * Hook: easyreservations_before_shop_loop_item_title.
	 *
	 * @hooked easyreservations_template_loop_resource_thumbnail - 10
	 */
	do_action( 'easyreservations_before_shop_loop_item_title' );

	/**
	 * Hook: easyreservations_shop_loop_item_title.
	 *
	 * @hooked easyreservations_template_loop_resource_title - 10
	 */
	do_action( 'easyreservations_shop_loop_item_title' );

	/**
	 * Hook: easyreservations_after_shop_loop_item_title.
	 *
	 * @hooked easyreservations_template_loop_price - 10
	 */
	do_action( 'easyreservations_after_shop_loop_item_title' );

	/**
	 * Hook: easyreservations_after_shop_loop_item.
	 *
	 * @hooked easyreservations_template_loop_resource_link_close - 5
	 */
	do_action( 'easyreservations_after_shop_loop_item' );
	?>
</li>
