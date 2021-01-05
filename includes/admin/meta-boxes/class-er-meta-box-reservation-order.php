<?php
/**
 * Reservation Data
 *
 * Functions for displaying the reservation data meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * ER_Meta_Box_Reservation_Order Class.
 */
class ER_Meta_Box_Reservation_Order {

	/**
	 * Output the metabox.
	 *
	 * @param ER_Reservation $reservation
	 */
	public static function output( $reservation ) {

		$order = $reservation->get_order_id();
		if ( $order ) {
			$order = er_get_order( $order );
		}
		?>
        <ul class="reservation_order submitbox">
			<?php do_action( 'easyreservations_reservation_order_start', $reservation ); ?>
            <li class="wide">
				<?php if ( $order ): ?>
                    <table>
                        <tr>
                            <th style="vertical-align: top"><?php esc_html_e( 'Address', 'easyReservations' ); ?>:</th>
                            <td><?php echo $order->get_formatted_address(); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Email', 'easyReservations' ); ?>:</th>
                            <td><?php echo make_clickable( sanitize_email( $order->get_email() ) ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Phone', 'easyReservations' ); ?>:</th>
                            <td><?php echo er_make_phone_clickable( $order->get_phone() ); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Total', 'easyReservations' ); ?>:</th>
                            <td><?php echo $order->get_formatted_total(); ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Reservations', 'easyReservations' ); ?>:</th>
                            <td>
                                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=easy_reservation&order_id=' . $order->get_id() ) ); ?>">
									<?php echo esc_html( count( $order->get_items( 'reservation' ) ) ); ?>
                                </a>
                            </td>
                        </tr>
                    </table>
				<?php else: ?>
					<?php esc_html_e( 'Not attached to any order', 'easyReservations' ); ?>
				<?php endif; ?>
            </li>
            <li class="wide">
				<?php if ( $order ): ?>
                    <a href="#" class="remove-from-order deletion" data-order_id="<?php echo esc_attr( $order->get_id() ); ?>">
						<?php esc_html_e( 'Remove reservation from order', 'easyReservations' ); ?>
                    </a>
				<?php else: ?>
                    <a href="#" class="add-to-order">
						<?php esc_html_e( 'Assign to order', 'easyReservations' ); ?>
                    </a>
                    <script type="text/template" id="tmpl-er-modal-add-to-order">
                        <div class="er-backbone-modal">
                            <div class="er-backbone-modal-content">
                                <section class="er-backbone-modal-main" role="main">
                                    <header class="er-backbone-modal-header">
                                        <h1><?php esc_html_e( 'Add to Order', 'easyReservations' ); ?></h1>
                                        <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                            <span class="screen-reader-text">Close modal panel</span>
                                        </button>
                                    </header>
                                    <article>
                                        <form action="" method="post">
                                            <select class="er-order-search" name="order_id" data-placeholder="<?php esc_attr_e( 'Search for an Order&hellip;', 'easyReservations' ); ?>"></select>
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
                    </script>
				<?php endif; ?>
            </li>
			<?php do_action( 'easyreservations_reservation_order_end', $reservation ); ?>
        </ul>
		<?php
	}
}