<?php
/**
 * The Template for displaying resource archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/archive-resource.php.
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

get_header( 'shop' );

/**
 * Hook: easyreservations_before_main_content.
 *
 * @hooked easyreservations_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action( 'easyreservations_before_main_content' );

?>
    <header class="easyreservations-resources-header">
		<?php if ( apply_filters( 'easyreservations_show_page_title', true ) ) : ?>
            <h1 class="easyreservations-resources-header__title page-title"><?php easyreservations_page_title(); ?></h1>
		<?php endif; ?>

		<?php
		/**
		 * Hook: easyreservations_archive_description.
		 *
		 * @hooked easyreservations_resource_archive_description - 10
		 */
		do_action( 'easyreservations_archive_description' );
		?>
    </header>
<?php
if ( have_posts() ) {

	/**
	 * Hook: easyreservations_before_shop_loop.
	 *
	 * @hooked easyreservations_output_all_notices - 10
	 * @hooked easyreservations_result_count - 20
	 * @hooked easyreservations_catalog_ordering - 30
	 */
	do_action( 'easyreservations_before_shop_loop' );

	easyreservations_resource_loop_start();

	if ( er_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: easyreservations_shop_loop.
			 */
			do_action( 'easyreservations_shop_loop' );

			er_get_template_part( 'content', 'resource' );
		}
	}

	easyreservations_resource_loop_end();

	/**
	 * Hook: easyreservations_after_shop_loop.
	 *
	 * @hooked easyreservations_pagination - 10
	 */
	do_action( 'easyreservations_after_shop_loop' );
} else {
	/**
	 * Hook: easyreservations_no_resources_found.
	 *
	 * @hooked er_no_resources_found - 10
	 */
	do_action( 'easyreservations_no_resources_found' );
}

/**
 * Hook: easyreservations_after_main_content.
 *
 * @hooked easyreservations_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'easyreservations_after_main_content' );

do_action( 'easyreservations_sidebar' );

get_footer( 'easyreservations-shop' );
