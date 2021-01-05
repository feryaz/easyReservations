<?php
/**
 * Resource Images
 *
 * Display the resource images meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * ER_Meta_Box_Resource_Images Class.
 */
class ER_Meta_Box_Resource_Images {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $thepostid, $resource_object;

		$thepostid          = $post->ID;
		$resource_object    = $thepostid ? ER()->resources()->get( $thepostid ) : new ER_Resource( $post );
		$current_visibility = $resource_object->get_catalog_visibility();

		$current_featured   = er_bool_to_string( $resource_object->get_featured() );
		$current_onsale   = er_bool_to_string( $resource_object->is_on_sale() );
		$visibility_options = er_get_resource_visibility_options();

		wp_nonce_field( 'easyreservations_save_data', 'easyreservations_meta_nonce' );
		?>
        <div id="catalog-visibility">
            <div class="components-panel__row">
				<?php esc_html_e( 'Catalog', 'easyReservations' ); ?>

                <a href="#" class="edit-catalog-visibility hide-if-no-js">
					<?php

					echo isset( $visibility_options[ $current_visibility ] ) ? esc_html( $visibility_options[ $current_visibility ] ) : esc_html( $current_visibility );

					if ( 'yes' === $current_featured ) {
						echo ', ' . esc_html__( 'Featured', 'easyReservations' );
					}

					if ( 'yes' === $current_onsale ) {
						echo ', ' . esc_html__( 'On-sale', 'easyReservations' );
					}
					?>
                </a>
            </div>

            <div class="components-panel__row" id="catalog-visibility-select" class="hide-if-js" style="display: none">
                <input type="hidden" name="current_visibility" id="current_visibility" value="<?php echo esc_attr( $current_visibility ); ?>"/>
                <input type="hidden" name="current_featured" id="current_featured" value="<?php echo esc_attr( $current_featured ); ?>"/>
                <input type="hidden" name="current_onsale" id="current_onsale" value="<?php echo esc_attr( $current_onsale ); ?>"/>

				<?php
				echo '<p>' . esc_html__( 'This setting determines which shop pages resources will be listed on.', 'easyReservations' ) . '</p>';

				foreach ( $visibility_options as $name => $label ) {
					echo '<input type="radio" name="_visibility" id="_visibility_' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . checked( $current_visibility, $name, false ) . ' data-label="' . esc_attr( $label ) . '" /> <label for="_visibility_' . esc_attr( $name ) . '" class="selectit">' . esc_html( $label ) . '</label><br />';
				}

				echo '<br /><input type="checkbox" name="_featured" id="_featured" ' . checked( $current_featured, 'yes', false ) . ' /> <label for="_featured">' . esc_html__( 'This is a featured resource', 'easyReservations' ) . '</label><br />';
				echo '<br /><input type="checkbox" name="_onsale" id="_onsale" ' . checked( $current_onsale, 'yes', false ) . ' /> <label for="_onsale">' . esc_html__( 'This resource is on sale', 'easyReservations' ) . '</label><br />';
				?>
                <p>
                    <a href="#" class="save-post-visibility hide-if-no-js button"><?php esc_html_e( 'OK', 'easyReservations' ); ?></a>
                    <a href="#" class="cancel-post-visibility hide-if-no-js"><?php esc_html_e( 'Cancel', 'easyReservations' ); ?></a>
                </p>
            </div>
        </div>
        <div id="resource_images_container">
            <input type="hidden" name="resource_visibility" id="resource_visibility" value="<?php echo esc_attr( $current_visibility ); ?>">
            <input type="hidden" name="resource_featured" id="resource_featured" value="<?php echo esc_attr( $current_featured ); ?>"/>
            <input type="hidden" name="resource_onsale" id="resource_onsale" value="<?php echo esc_attr( $current_onsale ); ?>"/>

            <ul class="resource_images">
				<?php
				$resource_image_gallery = $resource_object->get_gallery_image_ids( 'edit' );

				$attachments         = array_filter( $resource_image_gallery );
				$update_meta         = false;
				$updated_gallery_ids = array();

				if ( ! empty( $attachments ) ) {
					foreach ( $attachments as $attachment_id ) {
						$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

						// if attachment is empty skip.
						if ( empty( $attachment ) ) {
							$update_meta = true;
							continue;
						}
						?>
                        <li class="image" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
							<?php echo $attachment; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <ul class="actions">
                                <li>
                                    <a href="#" class="delete tips" data-tip="<?php esc_attr_e( 'Delete image', 'easyReservations' ); ?>"><?php esc_html_e( 'Delete', 'easyReservations' ); ?></a>
                                </li>
                            </ul>
							<?php
							// Allow for extra info to be exposed or extra action to be executed for this attachment.
							do_action( 'easyreservations_admin_after_resource_gallery_item', $thepostid, $attachment_id );
							?>
                        </li>
						<?php

						// rebuild ids to be saved.
						$updated_gallery_ids[] = $attachment_id;
					}

					// need to update resource meta to set new gallery ids
					if ( $update_meta ) {
						update_post_meta( $post->ID, 'gallery_image_ids', implode( ',', $updated_gallery_ids ) );
					}
				}
				?>
            </ul>

            <input type="hidden" id="resource_image_gallery" name="resource_image_gallery" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>"/>
        </div>
        <p class="add_resource_images hide-if-no-js">
            <a href="#" data-choose="<?php esc_attr_e( 'Add images to resource gallery', 'easyReservations' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'easyReservations' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'easyReservations' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'easyReservations' ); ?>"><?php esc_attr_e( 'Add resource gallery images', 'easyReservations' ); ?></a>
        </p>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		$resource       = ER()->resources()->get( $post_id );
		$attachment_ids = isset( $_POST['resource_image_gallery'] ) ? array_filter( explode( ',', er_clean( $_POST['resource_image_gallery'] ) ) ) : array();

		$resource->set_gallery_image_ids( $attachment_ids );

		$resource_visibility = isset( $_POST['resource_visibility'] ) ? sanitize_text_field( $_POST['resource_visibility'] ) : 'hidden';

		switch ( $resource_visibility ) {
			case 'hidden':
				$terms[] = 'exclude-from-search';
				$terms[] = 'exclude-from-catalog';
				break;
			case 'catalog':
				$terms[] = 'exclude-from-search';
				break;
			case 'search':
				$terms[] = 'exclude-from-catalog';
				break;
		}

		$terms = array();

		if ( isset( $_POST['resource_featured'] ) && $_POST['resource_featured'] === 'yes' ) {
			$terms[] = 'featured';
		}

		if ( isset( $_POST['resource_onsale'] ) && $_POST['resource_onsale'] === 'yes' ) {
			$terms[] = 'onsale';
		}

		if ( ! is_wp_error( wp_set_post_terms( $resource->get_id(), $terms, 'resource_visibility', false ) ) ) {
			do_action( 'easyreservations_resource_set_visibility', $resource->get_id(), $resource->get_catalog_visibility() );
		}
	}
}
