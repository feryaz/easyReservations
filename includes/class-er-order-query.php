<?php
/**
 * Parameter-based Order querying
 * Args and usage: https://github.com/woocommerce/woocommerce/wiki/wc_get_orders-and-WC_Order_Query
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order query class.
 */
class ER_Order_Query extends ER_Object_Query {

	/**
	 * Valid query vars for orders.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'               => array_keys( ER_Order_Status::get_statuses() ),
				'type'                 => array( 'easy_order', 'easy_order_refund' ),
				'prices_include_tax'   => '',
				'date_created'         => '',
				'date_modified'        => '',
				'date_completed'       => '',
				'date_paid'            => '',
				'discount_total'       => '',
				'discount_tax'         => '',
				'total'                => '',
				'total_tax'            => '',
				'customer'             => '',
				'customer_id'          => '',
				'order_key'            => '',
				'first_name'           => '',
				'last_name'            => '',
				'company'              => '',
				'address_1'            => '',
				'address_2'            => '',
				'city'                 => '',
				'state'                => '',
				'postcode'             => '',
				'country'              => '',
				'email'                => '',
				'phone'                => '',
				'payment_method'       => '',
				'payment_method_title' => '',
				'transaction_id'       => '',
				'customer_ip_address'  => '',
				'customer_user_agent'  => '',
				'created_via'          => '',
				'customer_note'        => '',
			)
		);
	}

	/**
	 * Get orders matching the current query vars.
	 *
	 * @return array|object of ER_Order objects
	 *
	 * @throws Exception When ER_Data_Store validation fails.
	 */
	public function get_orders() {
		$args    = apply_filters( 'easyreservations_order_query_args', $this->get_query_vars() );
		$results = ER_Data_Store::load( 'order' )->query( $args );

		return apply_filters( 'easyreservations_order_query', $results, $args );
	}
}
