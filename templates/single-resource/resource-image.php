<?php
/**
 * Single Resource Image
 *
 * This template can be overridden by copying it to yourtheme/easyreservations/single-resource/resource-image.php.
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

$columns           = apply_filters( 'easyreservations_resource_thumbnails_columns', 4 );
$post_thumbnail_id = $resource->get_image_id();
$wrapper_classes   = apply_filters( 'easyreservations_single_resource_image_gallery_classes', array(
	'easyreservations-resource-gallery',
	'easyreservations-resource-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
	'easyreservations-resource-gallery--columns-' . absint( $columns ),
	'images',
) );
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity 0.25s ease-in-out;">
    <figure class="easyreservations-resource-gallery__wrapper">
		<?php
		if ( $post_thumbnail_id ) {
			$html = er_get_gallery_image_html( $post_thumbnail_id, true );
		} else {
			$html = '<div class="easyreservations-resource-gallery__image--placeholder">';
			$html .= sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( er_placeholder_img_src( 'easyreservations_single' ) ), esc_html__( 'Awaiting resource image', 'easyReservations' ) );
			$html .= '</div>';
		}

		echo apply_filters( 'easyreservations_single_resource_image_thumbnail_html', $html, $post_thumbnail_id ); // phpcs:disable WordPress.XSS.EscapeOutput.OutputNotEscaped

		do_action( 'easyreservations_resource_thumbnails' );
		?>
    </figure>
</div>
