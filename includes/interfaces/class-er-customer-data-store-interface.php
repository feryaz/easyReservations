<?php
/**
 * Customer Data Store Interface
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Customer Data Store Interface
 *
 * Functions that must be defined by customer store classes.
 */
interface ER_Customer_Data_Store_Interface {

	/**
	 * Gets the customers last order.
	 *
	 * @param ER_Customer $customer Customer object.
	 *
	 * @return ER_Order|false
	 */
	public function get_last_order( &$customer );

	/**
	 * Return the number of orders this customer has.
	 *
	 * @param ER_Customer $customer Customer object.
	 *
	 * @return integer
	 */
	public function get_order_count( &$customer );

	/**
	 * Return how much money this customer has spent.
	 *
	 * @param ER_Customer $customer Customer object.
	 *
	 * @return float
	 */
	public function get_total_spent( &$customer );
}
