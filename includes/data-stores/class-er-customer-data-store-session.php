<?php
/**
 * Class ER_Customer_Data_Store_Session file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER Customer Data Store which stores the data in session.
 */
class ER_Customer_Data_Store_Session extends ER_Data_Store_WP implements ER_Customer_Data_Store_Interface,
	ER_Object_Data_Store_Interface {

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 *
	 * @var array
	 */
	protected $session_keys = array(
		'id',
		'date_modified',
		'address_postcode',
		'address_city',
		'address_address_1',
		'address_address_2',
		'address_state',
		'address_country',
		'is_vat_exempt',
		'address_first_name',
		'address_last_name',
		'address_company',
		'address_phone',
		'address_email',
	);

	/**
	 * Simply update the session.
	 *
	 * @param ER_Customer $customer Customer object.
	 */
	public function create( &$customer ) {
		$this->save_to_session( $customer );
	}

	/**
	 * Simply update the session.
	 *
	 * @param ER_Customer $customer Customer object.
	 */
	public function update( &$customer ) {
		$this->save_to_session( $customer );
	}

	/**
	 * Saves all customer data to the session.
	 *
	 * @param ER_Customer $customer Customer object.
	 */
	public function save_to_session( $customer ) {
		$data = array();
		foreach ( $this->session_keys as $session_key ) {
			$function_key         = $session_key;
			$data[ $session_key ] = (string) $customer->{"get_$function_key"}( 'edit' );
		}
		ER()->session->set( 'customer', $data );
	}

	/**
	 * Read customer data from the session unless the user has logged in, in
	 * which case the stored ID will differ from the actual ID.
	 *
	 * @param ER_Customer $customer Customer object.
	 */
	public function read( &$customer ) {
		$data = (array) ER()->session->get( 'customer' );

		/**
		 * There is a valid session if $data is not empty, and the ID matches the logged in user ID.
		 *
		 * If the user object has been updated since the session was created (based on date_modified) we should not load the session - data should be reloaded.
		 */
		if ( isset( $data['id'], $data['date_modified'] ) && $data['id'] === (string) $customer->get_id() && $data['date_modified'] === (string) $customer->get_date_modified( 'edit' ) ) {
			foreach ( $this->session_keys as $session_key ) {
				if ( in_array( $session_key, array( 'id', 'date_modified' ), true ) ) {
					continue;
				}
				$function_key = $session_key;

				if ( isset( $data[ $session_key ] ) && is_callable( array( $this, "set_{$function_key}" ) ) ) {
					$this->{"set_{$function_key}"}( wp_unslash( $data[ $session_key ] ) );
				}
			}
		}
		$this->set_defaults( $customer );
		$customer->set_object_read( true );
	}

	/**
	 * Load default values if props are unset.
	 *
	 * @param ER_Customer $customer Customer object.
	 */
	protected function set_defaults( &$customer ) {
		try {
			if ( ! $customer->get_address_country() ) {
				$customer->set_address_country( ER()->countries->get_base_country() );
			}

			if ( ! $customer->get_address_email() && is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				$customer->set_address_email( $current_user->user_email );
			}
		} catch ( ER_Data_Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}
	}

	/**
	 * Deletes a customer from the database.
	 *
	 * @param ER_Customer $customer Customer object.
	 * @param array       $args Array of args to pass to the delete method.
	 */
	public function delete( &$customer, $args = array() ) {
		ER()->session->set( 'customer', null );
	}

	/**
	 * Gets the customers last order.
	 *
	 * @param ER_Customer $customer Customer object.
	 *
	 * @return ER_Order|false
	 */
	public function get_last_order( &$customer ) {
		return false;
	}

	/**
	 * Return the number of orders this customer has.
	 *
	 * @param ER_Customer $customer Customer object.
	 *
	 * @return integer
	 */
	public function get_order_count( &$customer ) {
		return 0;
	}

	/**
	 * Return how much money this customer has spent.
	 *
	 * @param ER_Customer $customer Customer object.
	 *
	 * @return float
	 */
	public function get_total_spent( &$customer ) {
		return 0;
	}
}
