<?php
/**
 * The easyReservations customer class handles storage of the current customer's data
 *
 * @package easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Customer class.
 */
class ER_Customer extends ER_Data {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'user';

	/**
	 * Stores customer data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'       => null,
		'date_modified'      => null,
		'email'              => '',
		'first_name'         => '',
		'last_name'          => '',
		'display_name'       => '',
		'role'               => 'easy_customer',
		'username'           => '',
		'address'            => array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'phone'      => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		),
		'is_paying_customer' => false,
	);

	/**
	 * Stores a password if this needs to be changed. Write-only and hidden from _data.
	 *
	 * @var string
	 */
	protected $password = '';

	/**
	 * Stores if user is VAT exempt for this session.
	 *
	 * @var string
	 */
	protected $is_vat_exempt = false;

	/**
	 * Load customer data based on how ER_Customer is called.
	 *
	 * If $customer is 'new', you can build a new ER_Customer object. If it's empty, some
	 * data will be pulled from the session for the current user/customer.
	 *
	 * @param ER_Customer|int $data Customer ID or data.
	 * @param bool            $is_session True if this is the customer session.
	 *
	 * @throws Exception If customer cannot be read/found and $data is set.
	 */
	public function __construct( $data = 0, $is_session = false ) {
		parent::__construct( $data );

		if ( $data instanceof ER_Customer ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = ER_Data_Store::load( 'customer' );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}

		// If this is a session, set or change the data store to sessions. Changes do not persist in the database.
		if ( $is_session && isset( ER()->session ) ) {
			$this->data_store = ER_Data_Store::load( 'customer-session' );
			$this->data_store->read( $this );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return 'easyreservations_customer_get_';
	}

	/**
	 * Delete a customer and reassign posts..
	 *
	 * @param int $reassign Reassign posts and links to new User ID.
	 *
	 * @return bool
	 */
	public function delete_and_reassign( $reassign = null ) {
		if ( $this->data_store ) {
			$this->data_store->delete(
				$this,
				array(
					'force_delete' => true,
					'reassign'     => $reassign,
				)
			);
			$this->set_id( 0 );

			return true;
		}

		return false;
	}

	/**
	 * Return this customer's avatar.
	 *
	 * @return string
	 */
	public function get_avatar_url() {
		return get_avatar_url( $this->get_email() );
	}

	/**
	 * Is customer VAT exempt?
	 *
	 * @return bool
	 */
	public function is_vat_exempt() {
		return $this->get_is_vat_exempt();
	}

	/**
	 * Get if customer is VAT exempt?
	 *
	 * @return bool
	 */
	public function get_is_vat_exempt() {
		return $this->is_vat_exempt;
	}

	/**
	 * Get password (only used when updating the user object).
	 *
	 * @return string
	 */
	public function get_password() {
		return $this->password;
	}

	/**
	 * Set if customer has tax exemption.
	 *
	 * @param bool $is_vat_exempt If is vat exempt.
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->is_vat_exempt = er_string_to_bool( $is_vat_exempt );
	}

	/**
	 * Set customer's password.
	 *
	 * @param string $password Password.
	 */
	public function set_password( $password ) {
		$this->password = $password;
	}

	/**
	 * Gets the customers last order.
	 *
	 * @return ER_Order|false
	 */
	public function get_last_order() {
		return $this->data_store->get_last_order( $this );
	}

	/**
	 * Return the number of orders this customer has.
	 *
	 * @return integer
	 */
	public function get_order_count() {
		return $this->data_store->get_order_count( $this );
	}

	/**
	 * Return how much money this customer has spent.
	 *
	 * @return float
	 */
	public function get_total_spent() {
		return $this->data_store->get_total_spent( $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Return the customer's username.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_username( $context = 'view' ) {
		return $this->get_prop( 'username', $context );
	}

	/**
	 * Return the customer's email.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Return customer's first name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Return customer's last name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Return customer's display name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_display_name( $context = 'view' ) {
		return $this->get_prop( 'display_name', $context );
	}

	/**
	 * Return customer's user role.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_role( $context = 'view' ) {
		return $this->get_prop( 'role', $context );
	}

	/**
	 * Return the date this customer was created.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return ER_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Return the date this customer was last updated.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return ER_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * @param string $prop Name of prop to get.
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'. What the value is for. Valid values are view and edit.
	 *
	 * @return mixed
	 */
	public function get_address_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( array_key_exists( $prop, $this->data['address'] ) ) {
			$value = isset( $this->changes['address'][ $prop ] ) ? $this->changes['address'][ $prop ] : $this->data['address'][ $prop ];

			if ( 'view' === $context ) {
				$value = apply_filters( $this->get_hook_prefix() . 'address_' . $prop, $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Get address_first_name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_first_name( $context = 'view' ) {
		return $this->get_address_prop( 'first_name', $context );
	}

	/**
	 * Get address_last_name.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_last_name( $context = 'view' ) {
		return $this->get_address_prop( 'last_name', $context );
	}

	/**
	 * Get address_email.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_email( $context = 'view' ) {
		return $this->get_address_prop( 'email', $context );
	}

	/**
	 * Get address_company.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_company( $context = 'view' ) {
		return $this->get_address_prop( 'company', $context );
	}

	/**
	 * Get address_address_1.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_address( $context = 'view' ) {
		return $this->get_address_address_1( $context );
	}

	/**
	 * Get address_address_1.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_address_1( $context = 'view' ) {
		return $this->get_address_prop( 'address_1', $context );
	}

	/**
	 * Get address_address_2.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string $value
	 */
	public function get_address_address_2( $context = 'view' ) {
		return $this->get_address_prop( 'address_2', $context );
	}

	/**
	 * Get address_city.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string $value
	 */
	public function get_address_city( $context = 'view' ) {
		return $this->get_address_prop( 'city', $context );
	}

	/**
	 * Get address_state.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_state( $context = 'view' ) {
		return $this->get_address_prop( 'state', $context );
	}

	/**
	 * Get address_postcode.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_postcode( $context = 'view' ) {
		return $this->get_address_prop( 'postcode', $context );
	}

	/**
	 * Get address_country.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_country( $context = 'view' ) {
		return $this->get_address_prop( 'country', $context );
	}

	/**
	 * Get address_phone.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return string
	 */
	public function get_address_phone( $context = 'view' ) {
		return $this->get_address_prop( 'phone', $context );
	}

	/**
	 * Is the user a paying customer?
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return bool
	 */
	public function get_is_paying_customer( $context = 'view' ) {
		return $this->get_prop( 'is_paying_customer', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set customer's username.
	 *
	 * @param string $username Username.
	 */
	public function set_username( $username ) {
		$this->set_prop( 'username', $username );
	}

	/**
	 * Set customer's email.
	 *
	 * @param string $value Email.
	 */
	public function set_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'customer_invalid_email', __( 'Invalid email address', 'easyReservations' ) );
		}
		$this->set_prop( 'email', sanitize_email( $value ) );
	}

	/**
	 * Set customer's first name.
	 *
	 * @param string $first_name First name.
	 */
	public function set_first_name( $first_name ) {
		$this->set_prop( 'first_name', $first_name );
	}

	/**
	 * Set customer's last name.
	 *
	 * @param string $last_name Last name.
	 */
	public function set_last_name( $last_name ) {
		$this->set_prop( 'last_name', $last_name );
	}

	/**
	 * Set customer's display name.
	 *
	 * @param string $display_name Display name.
	 */
	public function set_display_name( $display_name ) {
		/* translators: 1: first name 2: last name */
		$this->set_prop( 'display_name', is_email( $display_name ) ? sprintf( _x( '%1$s %2$s', 'display name', 'easyReservations' ), $this->get_first_name(), $this->get_last_name() ) : $display_name );
	}

	/**
	 * Set customer's user role(s).
	 *
	 * @param mixed $role User role.
	 */
	public function set_role( $role ) {
		global $wp_roles;

		if ( $role && ! empty( $wp_roles->roles ) && ! in_array( $role, array_keys( $wp_roles->roles ), true ) ) {
			$this->error( 'customer_invalid_role', __( 'Invalid role', 'easyReservations' ) );
		}
		$this->set_prop( 'role', $role );
	}

	/**
	 * Set the date this customer was last updated.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set the date this customer was last updated.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	protected function set_address_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data['address'] ) ) {
			if ( true === $this->object_read ) {
				if ( $value !== $this->data['address'][ $prop ] || ( isset( $this->changes['address'] ) && array_key_exists( $prop, $this->changes['address'] ) ) ) {
					$this->changes['address'][ $prop ] = $value;
				}
			} else {
				$this->data['address'][ $prop ] = $value;
			}
		}
	}

	/**
	 * Set address_first_name.
	 *
	 * @param string $value address first name.
	 */
	public function set_address_first_name( $value ) {
		$this->set_address_prop( 'first_name', $value );
	}

	/**
	 * Set address_last_name.
	 *
	 * @param string $value address last name.
	 */
	public function set_address_last_name( $value ) {
		$this->set_address_prop( 'last_name', $value );
	}

	/**
	 * Set address_company.
	 *
	 * @param string $value address company.
	 */
	public function set_address_company( $value ) {
		$this->set_address_prop( 'company', $value );
	}

	/**
	 * Set address_address_1.
	 *
	 * @param string $value address address line 1.
	 */
	public function set_address_address( $value ) {
		$this->set_address_address_1( $value );
	}

	/**
	 * Set address_address_1.
	 *
	 * @param string $value address address line 1.
	 */
	public function set_address_address_1( $value ) {
		$this->set_address_prop( 'address_1', $value );
	}

	/**
	 * Set address_address_2.
	 *
	 * @param string $value address address line 2.
	 */
	public function set_address_address_2( $value ) {
		$this->set_address_prop( 'address_2', $value );
	}

	/**
	 * Set address_city.
	 *
	 * @param string $value address city.
	 */
	public function set_address_city( $value ) {
		$this->set_address_prop( 'city', $value );
	}

	/**
	 * Set address_state.
	 *
	 * @param string $value address state.
	 */
	public function set_address_state( $value ) {
		$this->set_address_prop( 'state', $value );
	}

	/**
	 * Set address_postcode.
	 *
	 * @param string $value address postcode.
	 */
	public function set_address_postcode( $value ) {
		$this->set_address_prop( 'postcode', $value );
	}

	/**
	 * Set address_country.
	 *
	 * @param string $value address country.
	 */
	public function set_address_country( $value ) {
		$this->set_address_prop( 'country', $value );
	}

	/**
	 * Set address_email.
	 *
	 * @param string $value address email.
	 */
	public function set_address_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'customer_invalid_address_email', __( 'Invalid address email address', 'easyReservations' ) );
		}
		$this->set_address_prop( 'email', sanitize_email( $value ) );
	}

	/**
	 * Set address_phone.
	 *
	 * @param string $value address phone.
	 */
	public function set_address_phone( $value ) {
		$this->set_address_prop( 'phone', $value );
	}

	/**
	 * Set if the user a paying customer.
	 *
	 * @param bool $is_paying_customer If is a paying customer.
	 */
	public function set_is_paying_customer( $is_paying_customer ) {
		$this->set_prop( 'is_paying_customer', (bool) $is_paying_customer );
	}
}
