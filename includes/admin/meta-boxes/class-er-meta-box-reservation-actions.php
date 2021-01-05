<?php
/**
 * Reservation Actions
 *
 * Functions for displaying the order actions meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ER_Meta_Box_Order_Actions Class.
 */
class ER_Meta_Box_Reservation_Actions {

	/**
	 * Output the metabox.
	 *
	 * @param ER_Reservation $reservation
	 */
	public static function output( $reservation ) {
		$submit_button_value = $reservation->get_id() ? esc_attr__( 'Update', 'easyReservations' ) : esc_attr__( 'Create', 'easyReservations' );
		$reservation_actions = apply_filters(
			'easyreservations_reservation_actions', array()
		);
		?>
        <ul class="order_actions submitbox">
			<?php do_action( 'easyreservations_reservation_actions_start', $reservation ); ?>

            <li class="wide" id="actions">
                <select name="er_reservation_action">
                    <option value=""><?php esc_html_e( 'Choose an action...', 'easyReservations' ); ?></option>
					<?php foreach ( $reservation_actions as $action => $title ) { ?>
                        <option value="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( $title ); ?></option>
					<?php } ?>
                </select>
                <button class="button er-reload"><span><?php esc_html_e( 'Apply', 'easyReservations' ); ?></span>
                </button>
            </li>
            <li class="wide">
                <div id="delete-action">
					<?php
					if ( isset( $_GET['reservation'] ) && $reservation->is_editable() ) {

						if ( $reservation->has_status( 'trash' ) ) {
							$delete_text = __( 'Delete permanently', 'easyReservations' );
							$action      = 'delete_permanently';
						} else {
							$delete_text = __( 'Move to Trash', 'easyReservations' );
							$action      = 'move_to_trash';
						}
						?>
                        <a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=reservation&reservation=' . absint( $_GET['reservation'] ) . '&action=' . $action ), 'easyreservations-delete-reservation' ) ); ?>"><?php echo esc_html( $delete_text ); ?></a>
						<?php
					}
					?>
                </div>

                <button type="submit" class="button save_order button-primary" name="save" value="<?php echo esc_attr( $submit_button_value ); ?>"><?php echo esc_html( $submit_button_value ); ?></button>
            </li>

			<?php do_action( 'easyreservations_reservation_actions_end', $reservation ); ?>
        </ul>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $reservation_id reservation ID.
	 */
	public static function save( $reservation_id ) {
		if ( ! $reservation_id ) {
			return;
		}

		// Order data saved, now get it so we can manipulate status.
		$reservation = er_get_reservation( $reservation_id );

		// Handle button actions.
		if ( ! empty( $_POST['er_reservation_action'] ) ) {

			$action = er_clean( wp_unslash( $_POST['er_reservation_action'] ) );

			if ( 'send_order_details' === $action ) {

			} else {

				if ( ! did_action( 'easyreservations_reservation_action_' . sanitize_title( $action ) ) ) {
					do_action( 'easyreservations_reservation_action_' . sanitize_title( $action ), $reservation );
				}
			}
		}
	}

	/**
	 * Set the correct message ID.
	 *
	 * @param string $location Location.
	 *
	 * @static
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}
}
