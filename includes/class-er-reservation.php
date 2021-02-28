<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reservations Class
 */
class ER_Reservation extends ER_Receipt {

	/**
	 * Order Data array.
	 *
	 * @var array
	 */
	protected $data = array(
		'resource_id'        => 0,
		'order_id'           => 0,
		'arrival'            => null,
		'departure'          => null,
		'adults'             => 1,
		'children'           => 0,
		'space'              => 0,
		'slot'               => - 1,
		'billing_units'      => 0,
		'frequency_units'    => 0,
		'status'             => ER_Reservation_Status::TEMPORARY,
		'title'              => '',
		'form_template'      => '',
		'prices_include_tax' => false,
		'date_created'       => null,
		'date_modified'      => null,
		'discount_total'     => 0,
		'discount_tax'       => 0,
		'total'              => 0,
		'total_tax'          => 0,
	);

	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'reservations';

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'reservation';

	/**
	 * This is the name of this object type
	 *
	 * @var string
	 */
	protected $object_type = 'reservation';

	/**
	 * Instance of resource
	 *
	 * @var ER_Resource
	 */
	protected $resource = null;

	/**
	 * ER_Reservation constructor.
	 *
	 * @param int|array|ER_Reservation $reservation
	 */
	public function __construct( $reservation ) {
		parent::__construct( $reservation );

		if ( is_numeric( $reservation ) && $reservation > 0 ) {
			$this->set_id( $reservation );
		} elseif ( $reservation instanceof self ) {
			$this->set_id( $reservation->get_id() );
		} elseif ( ! empty( $reservation->ID ) ) {
			$this->set_id( $reservation->ID );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = ER_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Save data to the database.
	 *
	 * @return int order ID
	 */
	public function save() {
		if ( $this->get_id() && array_key_exists( 'resource_id', $this->changes ) ) {
			//Update resource in receipt item
			$items = $this->get_items( 'resource' );

			$resource = $this->get_resource();

			foreach( $items as $item ){
				$item->set_resource_id( $resource ? $resource->get_id() : 0 );
				$item->set_name( $resource ? $resource->get_title() : __( 'No resource selected', 'easyReservations' ) );

				$item->save();
			}
		}

		parent::save();
		$this->status_transition();

		return $this->get_id();
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns all data for this object.
	 *
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			array(
				'id'                   => $this->get_id(),
				'resource_title'       => $this->resource ? $this->resource->get_title() : __( 'No resource selected', 'easyReservations' ),
				'resource_space_title' => $this->resource && $this->get_space() ? $this->resource->get_space_name( $this->get_space() ) : __( 'No space selected', 'easyReservations' ),
				'arrival_string'       => $this->get_arrival( 'edit' )->format( er_datetime_format() ),
				'departure_string'     => $this->get_departure( 'edit' )->format( er_datetime_format() ),
				'billing_units'        => $this->get_billing_units( 'edit' ),
				'billing_units_string' => $this->resource ? $this->get_billing_units( 'edit' ) . ' ' . er_date_get_interval_label( $this->resource->get_billing_interval() ) : '',
			),
			$this->data,
			array( 'meta_data' => $this->get_meta_data() )
		);
	}

	/**
	 * Get parent order ID.
	 *
	 * @param string $context View or edit context.
	 *
	 * @return integer
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Get resource ID
	 *
	 * @param string $context View or edit context.
	 *
	 * @return integer
	 */
	public function get_resource_id( $context = 'view' ) {
		return $this->get_prop( 'resource_id', $context );
	}

	/**
	 * Get resource space
	 *
	 * @param string $context View or edit context.
	 *
	 * @return integer
	 */
	public function get_space( $context = 'view' ) {
		return $this->get_prop( 'space', $context );
	}

	/**
	 * Get arrival
	 *
	 * @param string $context View or edit context.
	 *
	 * @return ER_DateTime|NULL
	 */
	public function get_arrival( $context = 'view' ) {
		return $this->get_prop( 'arrival', $context );
	}

	/**
	 * Get departure
	 *
	 * @param string $context View or edit context.
	 *
	 * @return ER_DateTime|NULL
	 */
	public function get_departure( $context = 'view' ) {
		return $this->get_prop( 'departure', $context );
	}

	/**
	 * Get billing units
	 *
	 * @param string $context View or edit context.
	 *
	 * @return int
	 */
	public function get_billing_units( $context = 'view' ) {
		return $this->get_prop( 'billing_units', $context );
	}

	/**
	 * Get billing units
	 *
	 * @param string $context View or edit context.
	 *
	 * @return int
	 */
	public function get_frequency_units( $context = 'view' ) {
		return $this->get_prop( 'frequency_units', $context );
	}

	/**
	 * Get slot (-1 is no slot)
	 *
	 * @param string $context View or edit context.
	 *
	 * @return integer
	 */
	public function get_slot( $context = 'view' ) {
		return $this->get_prop( 'slot', $context );
	}

	/**
	 * Get adults
	 *
	 * @param string $context View or edit context.
	 *
	 * @return integer
	 */
	public function get_adults( $context = 'view' ) {
		return $this->get_prop( 'adults', $context );
	}

	/**
	 * Get children
	 *
	 * @param string $context View or edit context.
	 *
	 * @return integer
	 */
	public function get_children( $context = 'view' ) {
		return $this->get_prop( 'children', $context );
	}

	/**
	 * Get form template used for reservation
	 *
	 * @param string $context View or edit context.
	 *
	 * @return string
	 */
	public function get_form_template( $context = 'view' ) {
		return $this->get_prop( 'form_template', $context );
	}

	/**
	 * Get title of reservation
	 *
	 * @param string $context View or edit context.
	 *
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		$title = $this->get_prop( 'title', $context );

		return $title;
	}

	/**
	 * Get resource
	 *
	 * @return ER_Resource
	 */
	public function get_resource() {
		return $this->resource;
	}

	/**
	 * Get reservation name - should be generated on runtime
	 *
	 * @return string
	 */
	public function get_name() {
		$reservation_name = apply_filters(
			'easyreservations_reservation_name',
			sanitize_text_field( get_option( 'reservations_reservation_name', '[resource] @[arrival] for [billing_units]d' ) )
		);

		$tags = er_form_template_parser( $reservation_name, true );

		foreach ( $tags as $string ) {
			//prevent endless loop
			if ( $string == 'thename' || $string == 'name' ) {
				continue;
			}

			$tag              = shortcode_parse_atts( $string );
			$reservation_name = str_replace( '[' . $string . ']', er_reservation_parse_tag( $tag, $this ), $reservation_name );
		}

		return $reservation_name;
	}

	/**
	 * Get reservation name.
	 *
	 * @return string
	 */
	public function get_item_label() {
		$item_label = apply_filters(
			'easyreservations_reservation_item_label',
			wp_kses_post( str_replace( "\n", '<br>', get_option( 'reservations_reservation_item_label', '[resource-link]' ) ) )
		);

		$tags = er_form_template_parser( $item_label, true );

		foreach ( $tags as $string ) {
			$tag        = shortcode_parse_atts( $string );
			$item_label = str_replace( '[' . $string . ']', er_reservation_parse_tag( $tag, $this ), $item_label );
		}

		return $item_label;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting order data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object. However, for backwards compatibility pre 3.0.0 some of these
	| setters may handle both.
	|
	*/

	/**
	 * Set order id.
	 *
	 * @param int $value Order ID.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_order_id( $value ) {
		$this->set_prop( 'order_id', absint( $value ) );
	}

	/**
	 * Set resource ID.
	 *
	 * @param int $value resource ID.
	 */
	public function set_resource_id( $value ) {
		$this->set_prop( 'resource_id', absint( $value ) );
		$this->set_resource( $this->get_resource_id() );
	}

	/**
	 * Set resource space.
	 *
	 * @param int $value space.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_space( $value ) {
		$this->set_prop( 'space', intval( $value ) );
	}

	/**
	 * Set slot.
	 *
	 * @param int $value slot.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_slot( $value ) {
		$this->set_prop( 'slot', intval( $value ) );
	}

	/**
	 * Set adults.
	 *
	 * @param int $value adults.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_adults( $value ) {
		$this->set_prop( 'adults', intval( $value ) );
	}

	/**
	 * Set children.
	 *
	 * @param int $value children.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_children( $value ) {
		$this->set_prop( 'children', intval( $value ) );
	}

	/**
	 * Set arrival.
	 *
	 * @param ER_DateTime|string|int $date Max length 22 chars.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_arrival( $date = null ) {
		$this->set_date_prop_without_timezone( 'arrival', $date );
		$this->calculate_billing_units();
	}

	/**
	 * Set departure.
	 *
	 * @param ER_DateTime|string|int $date Max length 22 chars.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_departure( $date = null ) {
		$this->set_date_prop_without_timezone( 'departure', $date );
		$this->calculate_billing_units();
	}

	/**
	 * Set billing units.
	 *
	 * @param int $value billing units.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_billing_units( $value ) {
		$this->set_prop( 'billing_units', absint( $value ) );
	}

	/**
	 * Set frequency units.
	 *
	 * @param int $value frequency units.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_frequency_units( $value ) {
		$this->set_prop( 'frequency_units', absint( $value ) );
	}

	/**
	 * Set form template.
	 *
	 * @param string $value form template.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_form_template( $value ) {
		$this->set_prop( 'form_template', sanitize_text_field( $value ) );
	}

	/**
	 * Set title.
	 *
	 * @param string $title title.
	 *
	 * @throws ER_Data_Exception Throws exception when invalid data is found.
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', apply_filters( 'easyreservations_reservation_title', sanitize_text_field( $title ) ) );
	}

	/**
	 * Set resource
	 *
	 * @param $resource ER_Resource|int
	 */
	protected function set_resource( $resource ) {
		if ( is_integer( $resource ) && $resource > 0 ) {
			$resource_id = $resource;
			$resource    = ER()->resources()->get( $resource_id );

			if ( ! $resource ) {
				er_get_logger()->error(
					sprintf(
						'Reservation #%d set to not existing resource #%d',
						$this->get_id(),
						$resource_id
					)
				);

				return;
			}
		}

		if ( ! is_a( $resource, 'ER_Resource' ) ) {
			$this->resource = null;

			return;
		}

		$this->resource = $resource;

		$this->set_prop( 'resource_id', absint( $resource->get_id() ) );

		$this->calculate_billing_units();
	}

	/*
	|--------------------------------------------------------------------------
	| Other methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * If order is not editable we don't need to check the reservation's status.
	 *
	 * @return bool
	 */
	public function is_order_editable() {
		//If order is not editable we don't need to check the reservation's status
		if ( $this->get_order_id() ) {
			$order = er_get_order( $this->get_order_id() );

			if ( $order && ! $order->is_editable() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if an reservation can be edited, specifically for use on the Edit Reservation screen.
	 *
	 * @return bool
	 */
	public function is_editable() {
		return apply_filters( 'easyreservations_reservation_is_editable', $this->is_order_editable() && in_array( $this->get_status(), array(
				'',
				'trash',
				'pending',
				'temporary',
				'auto-draft'
			), true ), $this );
	}

	/**
	 * Calculate billing units
	 *
	 * @param bool $interval
	 * @param bool $billing_method
	 */
	public function calculate_billing_units( $interval = false, $billing_method = false ) {
		if ( is_null( $this->get_resource() ) || $this->get_arrival() === null || $this->get_departure() === null ) {
			$this->set_billing_units( 1 );
			$this->set_frequency_units( 1 );

			return;
		}

		$this->set_billing_units(
			$this->get_resource()->get_billing_units( $this->get_arrival(), $this->get_departure(), $interval, $billing_method )
		);

		$this->set_frequency_units(
			$this->get_resource()->get_frequency_units( $this->get_arrival(), $this->get_departure() )
		);
	}

	/**
	 * Validate reservation
	 *
	 * @param bool|WP_Error $errors
	 * @param bool|array    $ids
	 * @param bool          $submit wether this is a final submit or just validation
	 * @param bool          $availability wether to check availability
	 *
	 * @return array|bool|int|null|string|WP_Error
	 */
	public function validate( $errors = false, $ids = false, $submit = false, $availability = true ) {
		$errors = is_wp_error( $errors ) ? $errors : new WP_Error();

		if ( $submit || $this->get_arrival() ) {
			if ( empty( $this->get_arrival() ) ) {
				$errors->add(
					'arrival',
					sprintf(
						__( 'Please enter arrival in a valid date format', 'easyReservations' )
					)
				);
			}

			if ( empty( $this->get_departure() ) ) {
				$errors->add(
					'departure',
					sprintf(
						__( 'Please enter departure in a valid date format', 'easyReservations' )
					)
				);
			}

			if ( $this->get_departure() < $this->get_arrival() ) {
				$errors->add(
					'departure',
					sprintf(
						__( 'Please select %s', 'easyReservations' ),
						__( 'a departure after your arrival', 'easyReservations' )
					)
				);
			}
		}

		if ( ! is_integer( $this->get_adults() ) || $this->get_adults() < 1 ) {
			$errors->add(
				'adults',
				sprintf(
					__( '%s has to be positive and numeric', 'easyReservations' ),
					__( 'Adults', 'easyReservations' )
				)
			);
		}

		if ( ! is_integer( $this->get_children() ) || $this->get_children() < 0 ) {
			$errors->add(
				'children',
				sprintf(
					__( '%s has to be positive and numeric', 'easyReservations' ),
					__( 'Children', 'easyReservations' )
				)
			);
		}

		if ( ER()->is_user_request() && ( $submit || $this->get_arrival() ) ) {
			if ( $this->get_arrival() < er_get_datetime() ) {
				$errors->add(
					'arrival',
					sprintf(
						__( 'Please select %s', 'easyReservations' ),
						__( 'your arrival in the future', 'easyReservations' )
					)
				);
			}

			$earliest_possible_arrival = er_date_add_seconds( er_get_datetime(), er_earliest_arrival() );

			if ( $this->get_arrival() < $earliest_possible_arrival ) {
				$errors->add(
					'arrival',
					sprintf(
						__( 'Earliest possible arrival is at %s', 'easyReservations' ),
						$earliest_possible_arrival->format( er_datetime_format() )
					)
				);
			}
		}

		if ( ! $errors->has_errors() ) {
			$resource = $this->get_resource();

			if ( $this->get_slot() < 0 ) {
				$checked    = false;
				$all_filter = $resource->get_filter();

				if ( $all_filter && ! empty( $all_filter ) ) {
					foreach ( $all_filter as $filter ) {
						if ( $filter['type'] == 'req' ) {
							if ( $resource->time_condition( $filter, $this->get_arrival() ) ) {
								$checked = true;
								$errors  = $this->validate_requirements( $filter['req'], $errors, isset( $filter['to'] ) ? $filter['to'] : false, $submit );

								if ( $errors->has_errors() ) {
									return $errors;
								}
							}
						}
					}
				}

				if ( ! $checked ) {
					$resource_req = $resource->get_requirements();
					if ( ! $resource_req || ! is_array( $resource_req ) ) {
						$resource_req = array(
							'nights-min' => 0,
							'nights-max' => 0,
							'pers-min'   => 1,
							'pers-max'   => 0
						);
					}

					$errors = $this->validate_requirements( $resource_req, $errors, false, $submit );
				}
			} else {
				//SLOT REQUIREMENTS - not for now
			}

			if ( ! $errors->has_errors() && $availability ) {
				$errors = $this->check_availability( $ids );
			}
		}

		return $errors;
	}

	/**
	 * Check if the space is free
	 *
	 * @param array|bool $ids array of other reservations that are part of the same order
	 *
	 * @return array|bool|int|null|string|WP_Error
	 */
	public function check_availability( $ids = false ) {
		$availability = new ER_Resource_Availability( $this->get_resource(), $this->get_space(), $this->get_adults(), $this->get_children(), $ids, false, $this->get_id() );

		if ( ER()->is_user_request() ) {
			$check = $availability->check_each_frequency_unit( $this->get_arrival(), $this->get_departure() );

			if ( $check ) {
				return new WP_Error( 'arrival', $check, $availability );
			}
		} else {
			$check = $availability->check_whole_period_or_each_frequency_unit( $this->get_arrival(), $this->get_departure() );

			if ( $check ) {
				return new WP_Error( 'arrival', $check, $availability );
			}
		}

		return $check;
	}

	/**
	 * Check resource or filter requirements against current reservation
	 *
	 * @param array    $req
	 * @param WP_Error $errors
	 * @param bool|int $filter_end
	 * @param bool     $submit
	 *
	 * @return WP_Error
	 */
	private function validate_requirements( $req, $errors, $filter_end = false, $submit = false ) {
		$billing_units = $this->get_billing_units();
		$resource      = $this->get_resource();

		if ( $req['pers-min'] > ( $this->get_adults() + $this->get_children() ) ) {
			$errors->add( 'adults', sprintf( __( 'At least %1$s people in %2$s', 'easyReservations' ), $req['pers-min'], __( $resource->get_title() ) ) );
		}

		if ( $req['pers-max'] > 0 && $req['pers-max'] < ( $this->get_adults() + $this->get_children() ) ) {
			$errors->add( 'adults', sprintf( __( 'Maximum %1$s people in %2$s', 'easyReservations' ), $req['pers-max'], __( $resource->get_title() ) ) );
		}

		if ( ! $submit && ! $this->get_arrival() ) {
			return $errors;
		}

		if ( $req['nights-min'] > $billing_units ) {
			$errors->add( 'departure', sprintf( __( 'At least %1$s %2$s in %3$s', 'easyReservations' ), $req['nights-min'], er_date_get_interval_label( $resource->get_billing_interval(), $req['nights-min'] ), __( $resource->get_title() ) ) );
		}

		if ( $req['nights-max'] > 0 && $req['nights-max'] < $billing_units ) {
			$errors->add( 'departure', sprintf( __( 'Maximum %1$s %2$s in %3$s', 'easyReservations' ), $req['nights-max'], er_date_get_interval_label( $resource->get_billing_interval(), $req['nights-max'] ), __( $resource->get_title() ) ) );
		}

		$day_names = er_date_get_label( 0, 3 );

		if ( isset( $req['start-on'] ) ) {
			if ( $req['start-on'] == 8 ) {
				if ( $filter_end ) {
					$errors->add( 'arrival', sprintf( __( 'Arrival not possible until %s', 'easyReservations' ), date( er_datetime_format(), $filter_end ) ) );
				} else {
					$errors->add( 'arrival', sprintf( __( 'Arrival not possible on %s', 'easyReservations' ), date( er_datetime_format(), $this->get_arrival() ) ) );
				}
			} elseif ( ! empty( $req['start-on'] ) && ! in_array( $this->get_arrival()->format( "N" ), $req['start-on'] ) ) {
				$start_days = '';

				foreach ( $req['start-on'] as $starts ) {
					$start_days .= $day_names[ $starts - 1 ] . ', ';
				}

				$errors->add( 'arrival', sprintf( __( 'Arrival only possible on %s', 'easyReservations' ), substr( $start_days, 0, - 2 ) ) );
			}
		}
		if ( isset( $req['end-on'] ) ) {
			if ( $req['end-on'] == 8 ) {
				if ( $filter_end ) {
					$errors->add( 'departure', sprintf( __( 'Departure not possible until %s', 'easyReservations' ), date( er_datetime_format(), $filter_end ) ) );
				} else {
					$errors->add( 'departure', sprintf( __( 'Departure not possible on %s', 'easyReservations' ), date( er_datetime_format(), $this->get_departure() ) ) );
				}
			} elseif ( ! empty( $req['end-on'] ) && ! in_array( $this->get_departure()->format( "N" ), $req['end-on'] ) ) {
				$end_days = '';

				foreach ( $req['end-on'] as $ends ) {
					$end_days .= $day_names[ $ends - 1 ] . ', ';
				}

				$errors->add( 'departure', sprintf( __( 'Departure only possible on %s', 'easyReservations' ), substr( $end_days, 0, - 2 ) ) );
			}
		}

		$zero = strtotime( '22.2.2222 00:00:00' );

		if ( isset( $req['start-h'] ) && is_array( $req['start-h'] ) ) {
			if ( $this->get_arrival()->format( "G" ) < $req['start-h'][0] ) {
				$errors->add( 'arrival', sprintf( __( 'Arrival only possible after %s', 'easyReservations' ), date( er_time_format(), $zero + ( $req['start-h'][0] * HOUR_IN_SECONDS ) ) ) );
			}

			if ( $this->get_arrival()->format( "G" ) > $req['start-h'][1] ) {
				$errors->add( 'arrival', sprintf( __( 'Arrival only possible until %s', 'easyReservations' ), date( er_time_format(), $zero + ( $req['start-h'][1] * HOUR_IN_SECONDS ) ) ) );
			}
		}

		if ( isset( $req['end-h'] ) && is_array( $req['end-h'] ) ) {
			if ( $this->get_departure()->format( "G.i" ) < $req['end-h'][0] ) {
				$errors->add( 'departure', sprintf( __( 'Departure only possible after %s', 'easyReservations' ), date( er_time_format(), $zero + ( $req['end-h'][0] * HOUR_IN_SECONDS ) ) ) );
			}

			if ( $this->get_departure()->format( "G.i" ) > $req['end-h'][1] ) {
				$errors->add( 'departure', sprintf( __( 'Departure only possible until %s', 'easyReservations' ), date( er_time_format(), $zero + ( $req['end-h'][1] * HOUR_IN_SECONDS ) ) ) );
			}
		}

		return $errors;
	}

	/**
	 * Calculate reservation
	 *
	 * @param bool $return_receipt
	 *
	 * @return float|array calculated grand total.
	 */
	public function calculate_price( $return_receipt = false ) {
		$resource = $this->get_resource();
		$total    = 0;

		if( $resource ){
			$interval       = $resource->get_billing_interval();
			$all_filter     = $resource->get_filter();
			$base_price     = $resource->get_base_price();
			$children_price = $resource->get_children_price();
			$return_receipt = $return_receipt ? array() : false;

			$stay_prices_adults   = array();
			$stay_prices_children = array();
			$multiplier_adults    = 1;
			$multiplier_children  = 0;

			$billing_units = $this->get_slot() < 0 ? $this->get_billing_units() : 1;

			if ( $resource->bill_per_person() == 1 ) {
				$multiplier_adults = $this->get_adults();
				if ( $this->get_children() > 0 ) {
					$multiplier_children = $this->get_children();
				}
			}

			$arrival = clone $this->get_arrival();

			//We check each billing unit for a base price filter and fill the arrays accordingly
			if ( ! empty( $all_filter ) ) {
				foreach ( $all_filter as $key => $filter ) {
					if ( $filter['type'] == 'price' ) {
						if ( $resource->filter( $filter, $this->get_arrival(), $billing_units, $this->get_adults(), $this->get_children(), $this->get_date_created() ) ) {
							for ( $t = 0; $t < $billing_units; $t ++ ) {

								if ( ( $resource->bill_only_once() || $this->get_slot() > - 1 ) && $t > 0 ) {
									break;
								}

								$date = er_date_add_seconds( $arrival, $t * $interval );
								$i    = $date->getTimestamp();

								if ( ! in_array( $i, $stay_prices_adults ) || ( $this->get_children() > 0 && ! in_array( $i, $stay_prices_children ) && isset( $filter['children-price'] ) ) ) {
									if ( ! isset( $filter['cond'] ) || $resource->time_condition( $filter, $date ) ) {
										if ( $this->get_children() > 0 && isset( $filter['children-price'] ) && ! empty( $filter['children-price'] ) && ! in_array( $i, $stay_prices_children ) ) {
											if ( strpos( $filter['children-price'], '%' ) !== false ) {
												$amount = ER_Number_Util::round( $base_price / 100 * str_replace( '%', '', $filter['children-price'] ), er_get_rounding_precision() );
											} else {
												$amount = empty( $filter['children-price'] ) ? 0 : $filter['children-price'];
											}

											$stay_prices_children[ $i ] = $amount;
										}

										if ( ! in_array( $i, $stay_prices_adults ) ) {
											if ( strpos( $filter['price'], '%' ) !== false ) {
												$amount = ER_Number_Util::round( $base_price / 100 * str_replace( '%', '', $filter['price'] ), er_get_rounding_precision() );
											} else {
												$amount = empty( $filter['price'] ) ? 0 : $filter['price'];
											}

											$stay_prices_adults[ $i ] = $amount;
										}
									}
								}
							}
						}
						unset( $all_filter[ $key ] );
					} else {
						break;
					}
				}
			}

			//A slot only has one real billing unit
			for ( $t = 0; $t < $billing_units; $t ++ ) {
				$date = er_date_add_seconds( $arrival, $t * $interval );
				$i    = $date->getTimestamp();

				if ( ( $resource->bill_only_once() || $this->get_slot() > - 1 ) && $t > 0 ) {
					break;
				}

				$t_price_adults   = isset( $stay_prices_adults[ $i ] ) ? $stay_prices_adults[ $i ] : $base_price;
				$t_price_children = isset( $stay_prices_children[ $i ] ) ? $stay_prices_children[ $i ] : $children_price;

				if ( $this->get_slot() > - 1 && ( ! isset( $stay_prices_adults[ $i ] ) || ( ! isset( $stay_prices_children[ $i ] ) && $this->get_children() > 0 ) ) ) {
					if ( $resource->has_slot( $this->get_slot() ) ) {
						$slot = $resource->get_slot( $this->get_slot() );

						if ( ! isset( $stay_prices_adults[ $i ] ) ) {
							$t_price_adults = $slot['base-price'];
						}

						if ( ! isset( $stay_prices_children[ $i ] ) ) {
							$t_price_children = $slot['children-price'];
						}
					}
				}

				$t_total_adults   = $t_price_adults * $multiplier_adults;
				$t_total_children = $t_price_children * $multiplier_children;
				$t_total          = $t_total_adults + $t_total_children;

				if ( is_array( $return_receipt ) ) {
					$return_receipt[] = array(
						'type'           => 'resource',
						'resource_id'    => $resource->get_id(),
						'adult_price'    => $t_price_adults,
						'children_price' => $t_price_children,
						'total'          => $t_total,
						'date'           => $i,
						'name'           => $resource->get_title(),
					);
				}

				$total += $t_total;
			}

			$stay_total = $total;

			if ( ! empty( $all_filter ) ) {
				$full = array();

				foreach ( $all_filter as $filter ) {
					if ( $resource->filter( $filter, $this->get_arrival(), $billing_units, $this->get_adults(), $this->get_children(), $this->get_date_created(), $full ) ) {
						$full[] = $filter['type'];
						$amount = $filter['price'];

						if ( isset( $filter['modus'] ) ) {
							$amount = er_reservation_multiply_amount( $this, $filter['modus'], $filter['price'], $stay_total );
						}

						if ( $amount !== 0 ) {
							if ( is_array( $return_receipt ) ) {
								$return_receipt[] = array(
									'type'        => 'filter',
									'resource_id' => $resource->get_id(),
									'filter_type' => $filter['type'],
									'total'       => $amount,
									'name'        => $filter['name'],
								);
							}

							$total += $amount;
						}
					}
				}
			}
		}

		//$total = ER_Number_Util::round( $total, er_get_price_decimals() );

		if ( empty( $this->get_items( 'resource' ) ) ) {
			$item = new ER_Receipt_Item_Resource();
			$item->set_name( $this->get_resource()->get_title() );
			$item->set_resource_id( $this->get_resource_id() );
			$item->set_subtotal( $total );
			$item->set_total( $total );

			$this->add_item( $item );
		}

		if ( $return_receipt ) {
			return $return_receipt;
		}

		return $total;
	}
}