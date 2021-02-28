<?php
/**
 * Show custom data
 *
 * @var ER_Reservation|ER_Order $object The object.
 * @package easyReservations\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$custom_data = $object->get_items( 'custom' );
?>
<div class="custom_data">
	<?php
	if ( $custom_data ) {
		echo '<p>';

		foreach ( $custom_data as $custom_item ) {
			echo esc_html( $custom_item->get_name() ) . ': ' . esc_html( $custom_item->get_custom_display() ) . '<br>';
		}

		echo '</p>';
	} else {
		echo '<p class="none_set">' . esc_html__( 'No custom data set.', 'easyReservations' ) . '</p>';
	}

	if ( $object->get_type() === 'order' && apply_filters( 'easyreservations_enable_order_notes_field', 'yes' == get_option( 'reservations_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
		echo '<p class="order_note"><strong>' . __( 'Customer provided note:', 'easyReservations' ) . '</strong> ' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
	}

	?>
</div>
<div class="edit_custom_data">
	<?php
	if ( $custom_data ) {
		foreach ( $custom_data as $custom_item ) { ?>
            <p class="form-field form-field-wide">
            <input type="hidden" name="custom_data[]" value="<?php echo esc_attr( $custom_item->get_id() ); ?>">
            <input type="hidden" name="er-custom-<?php echo esc_attr( $custom_item->get_custom_id() ); ?>-title" value="<?php echo esc_attr( $custom_item->get_name() ); ?>">
            <input type="hidden" name="er-custom-<?php echo esc_attr( $custom_item->get_custom_id() ); ?>-display" value="<?php echo esc_attr( $custom_item->get_custom_display() ); ?>">
            <label for="er-custom-<?php echo esc_attr( $custom_item->get_custom_id() ); ?>">
				<?php esc_html_e( $custom_item->get_name() ); ?>
                <a class="delete-custom" href="#" data-receipt_item_id="<?php echo esc_attr( $custom_item->get_id() ); ?>"></a>
            </label>
			<?php echo ER_Custom_Data::generate( $custom_item->get_custom_id(), array( 'value' => $custom_item->get_custom_value() ) ); ?>
            </p><?php
		}
	}

	if ( $object->get_type() === 'order' && apply_filters( 'easyreservations_enable_order_notes_field', 'yes' == get_option( 'reservations_enable_order_comments', 'yes' ) ) ) :
		?>
        <p class="form-field form-field-wide">
            <label for="excerpt"><?php _e( 'Customer provided note', 'easyReservations' ); ?>:</label>
            <textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php esc_attr_e( 'Customer notes about the order', 'easyReservations' ); ?>"><?php echo wp_kses_post( $post->post_excerpt ); ?></textarea>
        </p>
	<?php endif; ?>
</div>

<script type="text/template" id="tmpl-er-modal-add-custom">
    <div class="er-backbone-modal">
        <div class="er-backbone-modal-content">
            <section class="er-backbone-modal-main" role="main">
                <header class="er-backbone-modal-header">
                    <h1><?php esc_html_e( 'Add custom data', 'easyReservations' ); ?></h1>
                    <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article>
                    <form action="" method="post" class="order_data_column">
                        <p class="form-field form-field-wide">
                            <label for="custom_field"><?php esc_html_e( 'Type', 'easyReservations' ); ?></label>
                            <select name="custom_field" id="custom_field" class="er-custom-field">
                                <option value=""><?php esc_html_e( 'Select custom data to add', 'easyReservations' ); ?></option>
								<?php
								foreach ( ER_Custom_Data::get_settings() as $custom_field ) {
									echo '<option value="' . esc_attr( $custom_field['id'] ) . '">';
									echo esc_html( $custom_field['title'] );
									echo '</option>';
								}
								?>
                            </select>
                        </p>
                        <div id="custom_field_data" style="display:none">
                            <p class="form-field form-field-wide">
                                <label for="custom_field"><?php esc_html_e( 'Value', 'easyReservations' ); ?></label>
                            </p>
                            <div id="custom_field_value"></div>
                            <div>
                                <label for="custom_field_fee">
                                    <input type="checkbox" name="custom_field_fee" id="custom_field_fee">
									<?php esc_html_e( 'Add fee and recalculate receipt', 'easyReservations' ); ?>
                                </label>
                            </div>
                        </div>
                    </form>
                </article>
                <footer>
                    <div class="inner">
                        <button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', 'easyReservations' ); ?></button>
                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="er-backbone-modal-backdrop modal-close"></div>
    <div style="display: none">
		<?php
		foreach ( ER_Custom_Data::get_settings() as $custom_field ) {
			echo '<div id="prototype-custom-' . esc_attr( $custom_field['id'] ) . '">';
			echo ER_Custom_Data::generate( $custom_field['id'], array() );
			echo '</div>';
		}
		?>
    </div>
</script>