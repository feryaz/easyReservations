<?php
/**
 * Reservation edit screen
 */

//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Admin_Reservation {

	/**
	 * ER_Admin_Reservation constructor.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'init' ) );
		add_filter( 'parent_file', array( $this, 'highlight_admin_menu' ) );
	}

	/**
	 * Handle submit
	 */
	public function init() {
		$reservation_id = 0;

		if ( isset( $_GET['reservation'] ) ) {
			$reservation_id = absint( $_GET['reservation'] );

			$reservation = ER()->reservation_manager()->get( $reservation_id );

			if ( ! $reservation ) {
				ER_Admin_Notices::add_temporary_error( __( 'Invalid reservation', 'easyReservations' ) );

				return;
			}
		}

		if ( isset( $_GET['action'] ) && ( $_GET['action'] === 'move_to_trash' || $_GET['action'] === 'delete_permanently' ) ) {
			if ( ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'easyreservations-delete-reservation' ) ) {
				wp_die();
			}

			$reservation = ER()->reservation_manager()->get( $reservation_id );

			if ( $reservation && $reservation->is_editable() ) {
				$reservation->delete( $_GET['action'] === 'delete_permanently' );
				wp_safe_redirect( admin_url( 'edit.php?post_type=easy_reservation&changed=1&bulk_action=' . ( $_GET['action'] === 'delete_permanently' ? 'deleted_permanently' : 'moved_to_trash' ) ) );
				exit;
			} else {
				ER_Admin_Notices::add_temporary_error( __( 'Invalid reservation', 'easyReservations' ) );
			}
		}

		if ( isset( $_POST['arrival'], $_POST['resource'] ) ) {
			ER_Meta_Box_Receipt_Items::save( $reservation_id, true );
			ER_Meta_Box_Reservation_Data::save( $reservation_id );
			ER_Meta_Box_Custom_Data::save( $reservation_id );
		} elseif ( isset( $_POST['reservation_status'] ) ) {
			$reservation = ER()->reservation_manager()->get( $reservation_id );
			$new_status  = sanitize_key( $_POST['reservation_status'] );

			if( $reservation->get_status() !== $new_status ){
				$reservation->update_status( $new_status, '', true );

				ER_Admin_Notices::add_temporary_success( __( 'Status of reservation changed.', 'easyReservations' ) );
			}
		} elseif ( isset( $_POST['er_reservation_action'] ) ) {
			ER_Meta_Box_Reservation_Actions::save( $reservation_id );
		}
	}

	/**
	 * Highlight correct submenu item
	 *
	 * @param string $parent_file
	 *
	 * @return string
	 */
	public function highlight_admin_menu( $parent_file ) {
		global $submenu_file;

		$submenu_file = 'edit.php?post_type=easy_reservation';

		return $parent_file;
	}

	/**
	 * Output add/edit reservation page
	 */
	public static function output() {
	    $new = false;

		if ( isset( $_GET['reservation'] ) ) {
			$reservation_id = absint( $_GET['reservation'] );
		} else {
			//create and save a reservation so custom fields work
			$reservation = new ER_Reservation( 0 );
			$now         = er_get_datetime();
			$now->setTime( 0, 0, 0, 0 );
			$reservation->set_arrival( $now );
			$reservation->set_departure( $now );
			$reservation->add_meta_data( 'new_reservation', true, true );
			$resources = ER()->resources()->get();

			reset( $resources );
			$first = key( $resources );

			$reservation->set_resource_id( $first );

			$reservation->calculate_price();
			$reservation->calculate_totals();

			$reservation->save();
			$reservation_id = $reservation->get_id();
		}

		$reservation = er_get_reservation( $reservation_id );

		if ( ! $reservation ) {
			return;
		}

		wp_enqueue_script( 'er-admin-reservation-meta-boxes' );
		wp_enqueue_style( 'er-datepicker' );
		wp_enqueue_style( 'edit' );

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
				<?php
				if ( isset( $_GET['reservation'] ) ) {
					esc_html_e( 'Edit reservation', 'easyReservations' );
				} else {
					esc_html_e( 'Add new reservation', 'easyReservations' );
				}
				?>
            </h1>
			<?php if ( isset( $_GET['reservation'] ) ): ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=reservation&new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add reservation', 'easyReservations' ); ?></a>
			<?php endif; ?>
            <hr class="wp-header-end">

            <form name="post" method="post" id="post" action="<?php echo esc_url( admin_url( 'admin.php?page=reservation&reservation=' . $reservation->get_id() . '&action=edit' ) ); ?>">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="postbox-container-1" class="postbox-container">
                            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                                <div id="easyreservations-order-actions" class="postbox ">
                                    <h2 class="hndle ui-sortable-handle">
                                        <span><?php esc_html_e( 'Reservation actions', 'easyReservations' ); ?></span>
                                    </h2>
                                    <div class="inside">
										<?php ER_Meta_Box_Reservation_Actions::output( $reservation ); ?>
                                    </div>
                                </div>
                                <div id="easyreservations-reservation-order" class="postbox ">
                                    <h2 class="hndle ui-sortable-handle">
                                        <span><?php esc_html_e( 'Order', 'easyReservations' ); ?></span></h2>
                                    <div class="inside">
										<?php ER_Meta_Box_Reservation_Order::output( $reservation ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="postbox-container-2" class="postbox-container">
                            <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                                <div id="easyreservations-order-data" class="postbox ">
                                    <div class="inside">
										<?php ER_Meta_Box_Reservation_Data::output( $reservation ); ?>
                                    </div>
                                </div>
                                <div id="easyreservations-order-items" class="postbox ">
                                    <div class="inside">
										<?php ER_Meta_Box_Receipt_Items::output( $reservation ); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
		<?php
	}
}

return new ER_Admin_Reservation();