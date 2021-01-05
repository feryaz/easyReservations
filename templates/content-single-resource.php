<?php
/**
 * The template for displaying resource content in the single-resource.php template
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/content-single-resource.php.
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

/**
 * Hook: easyreservations_before_single_resource.
 *
 * @hooked easyreservations_output_all_notices - 10
 */
do_action( 'easyreservations_before_single_resource' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.

	return;
}

global $resource;

?>
<div id="resource-<?php the_ID(); ?>" <?php er_resource_class( '', $resource->get_id() ); ?>>

	<?php
	/**
	 * Hook: easyreservations_before_single_resource_summary.
	 *
	 * @hooked easyreservations_show_resource_images - 20
	 */
	do_action( 'easyreservations_before_single_resource_summary' );
	?>

    <div class="summary entry-summary">
		<?php
		/**
		 * Hook: easyreservations_single_resource_summary.
		 *
		 * @hooked easyreservations_template_single_title - 5
		 * @hooked easyreservations_template_single_price - 10
		 * @hooked easyreservations_template_single_excerpt - 20
		 * @hooked easyreservations_template_single_sharing - 50
		 */
		do_action( 'easyreservations_single_resource_summary' );
		?>
    </div>

	<?php
	/**
	 * Hook: easyreservations_after_single_resource_summary.
	 *
	 * @hooked easyreservations_template_single_description - 10
	 */
	do_action( 'easyreservations_after_single_resource_summary' );
	?>
</div>

<?php do_action( 'easyreservations_after_single_resource' ); ?>
