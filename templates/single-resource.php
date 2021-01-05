<?php
/**
 * The Template for displaying all single resources
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource.php.
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

get_header( 'shop' ); ?>

<?php
/**
 * easyreservations_before_main_content hook.
 *
 * @hooked easyreservations_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action( 'easyreservations_before_main_content' );
?>

<?php while ( have_posts() ) : the_post(); ?>

	<?php er_get_template_part( 'content', 'single-resource' ); ?>

<?php endwhile; // end of the loop. ?>

<?php
/**
 * easyreservations_after_main_content hook.
 *
 * @hooked easyreservations_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'easyreservations_after_main_content' );
?>

<?php
/**
 * easyreservations_sidebar hook.
 *
 * @hooked easyreservations_get_sidebar - 10
 */
do_action( 'easyreservations_sidebar' );
?>

<?php get_footer( 'shop' );
