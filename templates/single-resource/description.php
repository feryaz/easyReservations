<?php
/**
 * Resource description
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource/description.php.
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

global $post;

$heading = apply_filters( 'easyreservations_resource_description_heading', __( 'Description', 'easyReservations' ) );

?>

<?php if ( $heading ) : ?>
    <h2><?php echo esc_html( $heading ); ?></h2>
<?php endif; ?>

<?php the_content(); ?>
