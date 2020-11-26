<?php
/**
 * Order Notes
 *
 * @package easyReservations/Admin/Meta Boxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Meta_Box_Order_Notes Class.
 */
class ER_Meta_Box_Order_Notes {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function output( $post ) {
		global $post;

		$args = array(
			'order_id' => $post->ID,
		);

		$notes = er_get_order_notes( $args );

		include __DIR__ . '/views/html-order-notes.php';
		?>
        <div class="add_note">
            <p>
                <label for="add_order_note"><?php esc_html_e( 'Add note', 'easyReservations' ); ?><?php echo er_get_help( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'easyReservations' ) ); ?></label>
                <textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
            </p>
            <p>
                <label for="order_note_type" class="screen-reader-text"><?php esc_html_e( 'Note type', 'easyReservations' ); ?></label>
                <select name="order_note_type" id="order_note_type">
                    <option value=""><?php esc_html_e( 'Private note', 'easyReservations' ); ?></option>
                    <option value="customer"><?php esc_html_e( 'Note to customer', 'easyReservations' ); ?></option>
                </select>
                <button type="button" class="add_note button"><?php esc_html_e( 'Add', 'easyReservations' ); ?></button>
            </p>
        </div>
		<?php
	}
}
