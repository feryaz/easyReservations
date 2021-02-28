<?php
/**
 * The template for displaying product widget entries.
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/content-widget-product.php.
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
	exit;
}

global $resource;

if ( ! is_a( $resource, 'ER_Resource' ) ) {
	return;
}

?>
<li>
	<?php do_action( 'easyreservations_widget_resource_item_start', $args ); ?>

	<a href="<?php echo esc_url( $resource->get_permalink() ); ?>">
		<?php echo $resource->get_image(); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="resource-title"><?php echo wp_kses_post( $resource->get_title() ); ?></span>
	</a>

	<?php echo $resource->get_price_html(); // PHPCS:Ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php do_action( 'easyreservations_widget_resource_item_end', $args ); ?>
</li>
