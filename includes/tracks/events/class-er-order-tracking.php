<?php
/**
 * WooCommerce Order Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of a WooCommerce Order.
 */
class ER_Order_Tracking {

	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'easyreservations_admin_order_data_after_order_details', array( $this, 'track_order_viewed' ) );
	}

	/**
	 * Send a Tracks event when an order is viewed.
	 *
	 * @param ER_Order $order Order.
	 */
	public function track_order_viewed( $order ) {
		if ( ! $order instanceof ER_Order || ! $order->get_id() ) {
			return;
		}
		$properties = array(
			'current_status' => $order->get_status(),
			'date_created'   => $order->get_date_created() ? $order->get_date_created()->format( DateTime::ATOM ) : '',
			'payment_method' => $order->get_payment_method(),
		);

		ER_Tracks::record_event( 'single_order_view', $properties );
	}
}