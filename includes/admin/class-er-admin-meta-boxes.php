<?php
/**
 * easyReservations Meta Boxes
 *
 * Sets up the write panels used by resources and orders (custom post types).
 *
 * @package easyReservations/Admin/Meta Boxes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Admin_Meta_Boxes.
 */
class ER_Admin_Meta_Boxes {

	/**
	 * Meta box error messages.
	 *
	 * @var array
	 */
	public static $meta_box_errors = array();
	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

		/**
		 * Save Order Meta Boxes.
		 *
		 * In order:
		 *      Save the order items.
		 *      Save order data - also updates status and sends out admin emails if needed. Last to show latest data.
		 *      Save actions - sends out other emails. Last to show latest data.
		 */
		add_action( 'easyreservations_process_easy_order_meta', 'ER_Meta_Box_Receipt_Items::save', 10 );
		add_action( 'easyreservations_process_easy_order_meta', 'ER_Meta_Box_Order_Data::save', 40 );
		add_action( 'easyreservations_process_easy_order_meta', 'ER_Meta_Box_Order_Actions::save', 50, 2 );
		add_action( 'easyreservations_process_easy_order_meta', 'ER_Meta_Box_Custom_Data::save', 50, 2 );

		// Save Resource Meta Boxes.
		add_action( 'easyreservations_process_easy-rooms_meta', 'ER_Meta_Box_Resource_Images::save', 20, 2 );

		// Error handling (for showing errors from meta boxes on next page load).
		add_action( 'admin_notices', array( $this, 'output_errors' ) );
		add_action( 'shutdown', array( $this, 'save_errors' ) );
	}

	/**
	 * Add an error message.
	 *
	 * @param string $text Error to add.
	 */
	public static function add_error( $text ) {
		self::$meta_box_errors[] = $text;
	}

	/**
	 * Save errors to an option.
	 */
	public function save_errors() {
		update_option( 'reservations_meta_box_errors', self::$meta_box_errors );
	}

	/**
	 * Show any stored error messages.
	 */
	public function output_errors() {
		$errors = array_filter( (array) get_option( 'reservations_meta_box_errors' ) );

		if ( ! empty( $errors ) ) {

			echo '<div id="easyreservations_errors" class="error notice is-dismissible">';

			foreach ( $errors as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}

			echo '</div>';

			// Clear.
			delete_option( 'reservations_meta_box_errors' );
		}
	}

	/**
	 * Add ER Meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box( 'easyreservations-resource-images', __( 'Resource gallery', 'easyReservations' ), 'ER_Meta_Box_Resource_Images::output', 'easy-rooms', 'side', 'low' );

		// Orders.
		foreach ( array( 'easy_order', 'easy_order_refund' ) as $type ) {
			$order_type_object = get_post_type_object( $type );
			/* Translators: %s order type name. */
			add_meta_box( 'easyreservations-order-data', sprintf( __( '%s data', 'easyReservations' ), $order_type_object->labels->singular_name ), 'ER_Meta_Box_Order_Data::output', $type, 'normal', 'high' );

			add_meta_box( 'easyreservations-order-items', __( 'Receipt', 'easyReservations' ), 'ER_Meta_Box_Receipt_Items::output', $type, 'normal', 'high' );
			/* Translators: %s order type name. */
			add_meta_box( 'easyreservations-order-notes', sprintf( __( '%s notes', 'easyReservations' ), $order_type_object->labels->singular_name ), 'ER_Meta_Box_Order_Notes::output', $type, 'side', 'default' );
			/* Translators: %s order type name. */
			add_meta_box( 'easyreservations-order-actions', sprintf( __( '%s actions', 'easyReservations' ), $order_type_object->labels->singular_name ), 'ER_Meta_Box_Order_Actions::output', $type, 'side', 'high' );
		}
	}

	/**
	 * Remove bloat.
	 */
	public function remove_meta_boxes() {
		foreach ( array( 'easy_order', 'easy_order_refund' ) as $type ) {
			remove_meta_box( 'commentsdiv', $type, 'normal' );
			remove_meta_box( 'woothemes-settings', $type, 'normal' );
			remove_meta_box( 'commentstatusdiv', $type, 'normal' );
			remove_meta_box( 'slugdiv', $type, 'normal' );
			remove_meta_box( 'submitdiv', $type, 'side' );
		}
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public function save_meta_boxes( $post_id, $post ) {
		$post_id = absint( $post_id );

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Don't save meta boxes for revisions or autosaves.
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce.
		if ( empty( $_POST['easyreservations_meta_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['easyreservations_meta_nonce'] ), 'easyreservations_save_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		// Check user has permission to edit.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops. This would have been perfect:
		// remove_action( current_filter(), __METHOD__ );
		// But cannot be used due to https://github.com/woocommerce/woocommerce/issues/6485
		// When that is patched in core we can use the above.
		self::$saved_meta_boxes = true;

		// Check the post type.
		if ( in_array( $post->post_type, array( 'easy_order', 'easy-rooms', 'easy_coupon' ), true ) ) {
			do_action( 'easyreservations_process_' . $post->post_type . '_meta', $post_id, $post );
		}
	}
}

new ER_Admin_Meta_Boxes();
