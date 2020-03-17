<?php

defined( 'ABSPATH' ) || exit;

/**
 * ER_Resource_Availability class.
 */
class ER_Resource_Availability {

	/**
	 * @var ER_Resource
	 */
	private $resource = null;
	private $space = '';
	private $arrival = "arrival";
	private $departure = "departure";
	private $status = '';
	private $resource_query = '';
	private $interval = 86400;
	private $quantity = 1;
	private $per_person = false;
	private $per_person_amt = 0;
	private $arrival_possible_until = 23;
	private $date_pattern = 'd.m.Y';

	/**
	 * ER_Resource_Availability constructor.
	 *
	 * @param ER_Resource $resource
	 * @param int         $space
	 * @param int         $adults
	 * @param int         $children
	 * @param bool        $ids
	 * @param bool        $interval
	 * @param bool        $id
	 */
	public function __construct( $resource, $space = 0, $adults = 1, $children = 0, $ids = false, $interval = false, $id = false ) {
		global $wpdb;

		$this->interval = $interval ? $interval : $resource->get_frequency();

		$this->date_pattern = er_date_format();
		if ( $this->interval < 3601 ) {
			$this->date_pattern .= ' H:00';
		}

		$block_before = get_option( 'reservations_block_before', 0 ) * 60;
		if ( $block_before > 0 ) {
			$this->arrival = $wpdb->prepare( 'arrival - INTERVAL %d SECOND', $block_before );
		}

		$block_after = get_option( 'reservations_block_after', 0 ) * 60;
		if ( $block_after > 0 ) {
			$this->departure = $wpdb->prepare( 'departure + INTERVAL %d SECOND', $block_after );
		}

		$merge_resources = get_option( 'reservations_merge_resources', 0 );
		$requirements    = $resource->get_requirements();

		$this->per_person     = false;
		$this->per_person_amt = 0;
		$this->resource       = $resource;

		if ( $merge_resources > 0 ) {
			$this->quantity = $merge_resources;
		} else {
			$this->resource_query = $wpdb->prepare( 'resource = %d AND', $resource->get_id() );
			$this->quantity       = $resource->get_quantity();
		}

		if ( $resource->availability_by() !== 'unit' ) {
			if ( $resource->availability_by() == 'pers' ) {
				$this->per_person     = 'adults+children';
				$this->per_person_amt = $children + $adults;
			} elseif ( $resource->availability_by() == 'adult' ) {
				$this->per_person     = 'adults';
				$this->per_person_amt = $adults;
			} elseif ( $resource->availability_by() == 'children' ) {
				$this->per_person     = 'children';
				$this->per_person_amt = $children;
			}
		} elseif ( $space > 0 ) {
			$this->space    = $wpdb->prepare( "space=%d AND", $space );
			$this->quantity = 1;
		}

		if ( $requirements && isset( $requirements['start-h'] ) ) {
			$this->arrival_possible_until = $requirements['start-h'][1];
		}

		$this->status = "status IN ('" . implode( "', '", er_reservation_get_approved_statuses() ) . "')";

		if ( $ids ) {
			if ( is_array( $ids ) ) {
				$this->status .= $wpdb->prepare( ' or id in (%s)', implode( ',', $ids ) );
			} else {
				$this->status .= $wpdb->prepare( ' or id = %d', $ids );
			}
		}

		$this->status = '(' . $this->status . ')';

		if ( $id ) {
			$this->resource_query .= $wpdb->prepare( ' id != %d AND', $id );
		}
	}

	public function check_filter( $arrival, $departure, $array = false, $check_arrival_and_departure = true ) {
		if ( $array ) {
			$error = array();
		} else {
			$error = 0;
		}

		$global_filter = get_option( 'reservations_availability_filters', array() );
		if ( $global_filter && ! empty( $global_filter ) && is_array( $global_filter ) ) {
			foreach ( $global_filter as $key => $filter ) {
				if ( isset( $filter['from'] ) ) {
					if ( is_numeric( $filter['from'] ) ) {
						$global_filter[ $key ]['from'] = new ER_DateTime( '@' . $filter['from'] );
					} else {
						$global_filter[ $key ]['from'] = new ER_DateTime( $filter['from'] );
					}
				}

				if ( isset( $filter['to'] ) ) {
					if ( is_numeric( $filter['to'] ) ) {
						$global_filter[ $key ]['to'] = new ER_DateTime( '@' . $filter['to'] );
					} else {
						$global_filter[ $key ]['to'] = new ER_DateTime( $filter['to'] );
					}
				}
			}

			$error = $this->get_filtered_availability( $global_filter, $arrival, $departure, $error, $check_arrival_and_departure );
		}

		if ( ( $error === 0 || is_string( $error ) ) && ! empty( $this->resource->get_filter() ) ) {
			$error = $this->get_filtered_availability( $this->resource->get_filter(), $arrival, $departure, $error, $check_arrival_and_departure );
		}

		return $error;
	}

	public function get_filtered_availability( $all_filter, $arrival, $departure, $error, $check_arrival_and_departure = true ) {
		$frequency_units = $this->resource->get_frequency_units( $arrival, $departure, $this->interval );

		foreach ( $all_filter as $filter ) {
			if ( $filter['type'] == 'unavail' ) {
				$date = clone $arrival;

				for ( $i = 0; $i < $frequency_units; $i ++ ) {
					if ( ( isset( $error[ $i ] ) && ! is_string( $error[ $i ] ) ) || is_string( $error ) ) {
						continue;
					}

					if ( $this->resource->time_condition( $filter, $date ) ) {
						$quantity = 0;

						if ( $check_arrival_and_departure && $check_arrival_and_departure !== 'departure' && $i == 0 && isset( $filter['arrival'] ) ) {
							$quantity = 'arrival';
						} elseif ( ! isset( $filter['arrival'], $filter['departure'] ) ) {
							$quantity = $this->quantity;
						}

						if ( is_array( $error ) && $quantity !== 0 ) {
							$error[ $i ] = is_numeric( $quantity ) ? $date : $quantity;
						} elseif ( ! is_array( $error ) && is_numeric( $quantity ) ) {
							$error = $quantity;
						}
					}

					$date->add( new DateInterval( 'PT' . ( $this->interval ) . 'S' ) );
				}

				if ( $check_arrival_and_departure && $check_arrival_and_departure !== 'arrival' && isset( $filter['departure'] ) && $this->resource->time_condition( $filter, $departure ) ) {
					if ( is_array( $error ) ) {
						$error[ $frequency_units ] = 'departure';
					} else {
						$error = 'departure';
					}
				}
			}
		}

		return $error;
	}

	/**
	 * Check whole period at once if availability is per unit to make sure a space is free the whole duration, else check each frequency unit individually to make sure there's always enough room
	 *
	 * @param $arrival
	 * @param $departure
	 *
	 * @return array|bool|int|null|string
	 */
	public function check_whole_period_or_each_frequency_unit( $arrival, $departure ) {
		if ( $this->per_person ) {
			$check = $this->check_each_frequency_unit( $arrival, $departure );

			if ( $check ) {
				return $check;
			}
		} else {
			$check = $this->check_whole_period( $arrival, $departure, false );

			if ( $check > 0 ) {
				return $check;
			}
		}

		return false;
	}

	/**
	 * Check the whole period at once (MODE 0)
	 * Should not be used for availability per person for long durations
	 *
	 * @param ER_DateTime     $arrival
	 * @param ER_DateTime|int $departure
	 * @param bool|string     $check_arrivals_and_departures
	 * @param bool            $return
	 *
	 * @return array|int|null|string
	 */
	public function check_whole_period( $arrival, $departure, $check_arrivals_and_departures = true, $return = false ) {
		global $wpdb;

		$check_form = clone $arrival;
		$check_form->add( new DateInterval( 'PT60S' ) );

		if ( is_numeric( $departure ) ) {
			$check_until = clone $arrival;
			$check_until->add( new DateInterval( 'PT' . ( $departure - 60 ) . 'S' ) );
		} else {
			$check_until = clone $departure;
			$check_until->sub( new DateInterval( 'PT60S' ) );
		}

		$error = $this->check_filter( $arrival, $check_until, false, $check_arrivals_and_departures );

		if ( $error !== 0 ) {
			if ( $return ) {
				return $error * - 1;
			}

			return $error;
		}

		if ( $this->per_person ) {
			$sql = $wpdb->prepare(
				"SELECT SUM($this->per_person) FROM {$wpdb->prefix}reservations " .
				"WHERE {$this->status} AND {$this->resource_query} %s <= {$this->departure} AND %s >= {$this->arrival}",
				$check_form->format( 'Y-m-d H:i:s' ),
				$check_until->format( 'Y-m-d H:i:s' )
			);

			$count = $wpdb->get_var( $sql );

			if ( $return ) {
				return $count;
			}

			$count += $this->per_person_amt;

			if ( $count > $this->quantity ) {
				return $count;
			}
		} else {
			$sql = $wpdb->prepare(
				"SELECT COUNT(DISTINCT space) FROM {$wpdb->prefix}reservations " .
				"WHERE {$this->status} AND {$this->resource_query} {$this->space} %s <= {$this->departure} AND %s >= {$this->arrival}",
				$check_form->format( 'Y-m-d H:i:s' ),
				$check_until->format( 'Y-m-d H:i:s' )
			);

			$count = $wpdb->get_var( $sql );

			if ( $return ) {
				return $count;
			}

			if ( ! empty( $this->space ) || $count >= $this->quantity ) {
				return $count;
			}
		}

		return 0;
	}

	/**
	 * Check arrivals and departures (MODE 2)
	 *
	 * @param ER_DateTime $arrival
	 * @param ER_DateTime $departure
	 * @param bool|string $check_arrivals_and_departures
	 *
	 * @return object|int|string
	 */
	public function check_arrivals_and_departures( $arrival, $departure, $check_arrivals_and_departures = true ) {
		global $wpdb;

		$filter = $this->check_filter( $arrival, $departure, false, $check_arrivals_and_departures );
		if ( $filter !== 0 ) {
			return $filter;
		}

		$check_from  = $arrival->format( "Y-m-d H:i:s" );
		$check_until = $departure->format( "Y-m-d H:i:s" );

		if ( $this->per_person ) {
			$sql = $wpdb->prepare(
				"SELECT SUM(CASE WHEN DATE(arrival) = DATE(%s) THEN {$this->per_person} END) AS arrival, " .
				"SUM(CASE WHEN DATE(departure) = DATE(%s) THEN {$this->per_person} END) AS departure, " .
				"SUM({$this->per_person}) AS count_all, " .
				"MAX(CASE WHEN DATE(arrival) = DATE(%s) THEN arrival END) AS max_arrival, " .
				"MIN(CASE WHEN DATE(departure) = DATE(%s) THEN departure END) as min_departure " .
				"FROM {$wpdb->prefix}reservations WHERE {$this->status} AND {$this->resource_query} %s <= {$this->departure} AND %s >= {$this->arrival}",
				$check_until,
				$check_until,
				$check_until,
				$check_until,
				$arrival->format( "Y-m-d" ) . ' 00:00:00',
				$departure->format( "Y-m-d" ) . ' 23:59:59'
			);

			$result = $wpdb->get_row( $sql );
		} else {
			$sql = $wpdb->prepare(
				"SELECT COUNT(DISTINCT(CASE WHEN DATE(arrival) = DATE(%s) THEN space END)) AS arrival, " .
				"COUNT(DISTINCT(CASE WHEN DATE(departure) = DATE(%s) THEN space END)) AS departure, " .
				"COUNT(DISTINCT space) AS count_all, " .
				"MAX(CASE WHEN DATE(arrival) = DATE(%s) THEN arrival END) AS max_arrival, " .
				"MIN(CASE WHEN DATE(departure) = DATE(%s) THEN departure END) as min_departure " .
				"FROM {$wpdb->prefix}reservations WHERE {$this->status} AND {$this->resource_query} %s <= {$this->departure} AND %s >= {$this->arrival}",
				$check_from,
				$check_from,
				$check_from,
				$check_from,
				$check_from,
				$check_until
			);

			$result = $wpdb->get_row( $sql );
		}

		return $result;
	}

	/**
	 * Check each block (MODE 1)
	 *
	 * @param ER_DateTime $arrival
	 * @param ER_DateTime $departure
	 *
	 * @return array|string|bool
	 */
	public function check_each_frequency_unit( $arrival, $departure ) {
		global $wpdb;

		$error           = $this->check_filter( $arrival, $departure, true );
		$excluded_spaces = array();
		$billing_units   = $this->resource->get_frequency_units( $arrival, $departure, $this->interval );

		for ( $i = 0; $i <= $billing_units; $i ++ ) {
			if ( isset( $error[ $i ] ) ) {
				continue;
			}

			//Was brainfart to comment out?
			//if ( $i == $billing_units ) {
			//    $t = er_date_sub_seconds( $departure, 1 );
			//} else {
			$t = er_date_add_seconds( $arrival, $i * $this->interval );//+ 60 );
			//}

			$date_to_check = $t->format( "Y-m-d H:i:s" );

			if ( $this->per_person ) {
				$count = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT SUM({$this->per_person}) FROM {$wpdb->prefix}reservations " .
						"WHERE {$this->status} AND {$this->resource_query} (%s < {$this->departure} AND %s > {$this->arrival})",
						array( $date_to_check, $date_to_check )
					)
				);

				if ( $count < 1 ) {
					$count = 0;
				}

				$count = $count + $this->per_person_amt;

				if ( $count > $this->quantity ) {
					$error[] = $t;
				}
			} else {
				$count = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT(space) as spaces, SUM(adults+children) as persons FROM {$wpdb->prefix}reservations " .
						"WHERE {$this->status} AND {$this->resource_query} {$this->space} (%s < {$this->departure} AND %s > {$this->arrival}) GROUP BY spaces",
						array( $date_to_check, $date_to_check )
					)
				);

				$excluded_spaces = array_unique( array_merge( $count, $excluded_spaces ) );

				if ( count( $count ) >= $this->quantity || count( $excluded_spaces ) >= $this->quantity ) {
					$error[] = $t;
				}
			}
		}

		if ( ! empty( $error ) ) {
			$started      = false;
			$string       = '';
			$requirements = '';

			foreach ( $error as $key => $date ) {
				//If arrival or departure is not possible we get arrival/departure as string
				if ( is_string( $date ) ) {
					if ( $date === 'arrival' ) {
						$requirements .= sprintf( __( 'Arrival not possible at %s', 'easyReservations' ), $arrival->format( er_datetime_format() ) ) . '. ';
					} else {
						$requirements .= sprintf( __( 'Departure not possible at %s', 'easyReservations' ), $departure->format( er_datetime_format() ) ) . '. ';
					}
				} else {
					if ( ! $started ) {
						$string  .= $date->format( $this->date_pattern ) . ' -';
						$started = true;
					} elseif ( ! isset( $error[ $key + 1 ] ) || $error[ $key + 1 ]->getTimestamp() !== er_date_add_seconds( $date, $this->interval )->getTimestamp() ) {
						$string  .= ' ' . $date->format( $this->date_pattern ) . ', ';
						$started = false;
					}
				}
			}

			if ( ! empty( $string ) ) {
				$requirements .= sprintf( __( 'Not available at %s', 'easyReservations' ), substr( $string, 0, - 2 ) );
			}

			return empty( $requirements ) ? false : $requirements;
		}

		return false;
	}

	/**
	 * Check availability for one specific timeslot
	 *
	 * @param ER_DateTime $date
	 *
	 * @return array
	 */
	public function check_timeslot( $date ) {
		global $wpdb;

		$error           = $this->check_filter( $date, er_date_add_seconds( $date, $this->interval ), false, false );
		$date_to_check   = $date->format( 'Y-m-d H:i:s' );
		$departure_query = '';

		if ( $this->per_person ) {
			if ( $this->interval < DAY_IN_SECONDS ) {
				$query           = $wpdb->prepare( "%s BETWEEN {$this->arrival} AND {$this->departure} - INTERVAL 1 SECOND", $date_to_check );
				$departure_query = $wpdb->prepare(
					"AND HOUR({$this->departure}) = HOUR(%s) AND TIMEDIFF({$this->departure}, %s) < %d",
					$date_to_check,
					$date_to_check,
					$this->interval
				);
			} else {
				$query = $wpdb->prepare(
					"DATE(%s) BETWEEN DATE({$this->arrival}) AND DATE({$this->departure}) AND " .
					"(DATE({$this->arrival}) != DATE({$this->departure}) OR HOUR({$this->departure}) >= %d)",
					$date_to_check,
					$this->arrival_possible_until
				);
			}

			$query .= $wpdb->prepare(
				" AND (DATE({$this->departure}) != DATE(%s) %s OR HOUR({$this->departure}) >= %d)",
				$date_to_check,
				$departure_query,
				$this->arrival_possible_until
			);

			$count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT sum({$this->per_person}) as count FROM {$wpdb->prefix}reservations WHERE {$this->status} AND {$this->resource_query} %s",
					$query
				)

			);

			return array( $error + $count, $error + $count, $error + $count );
		} else {
			$arrival_query = '';
			$hour_query    = '';
			if ( $this->interval < DAY_IN_SECONDS ) {
				$query = $wpdb->prepare( "%s BETWEEN {$this->arrival} AND {$this->departure} - INTERVAL 1 SECOND", $date_to_check );

				if ( $this->interval < DAY_IN_SECONDS ) {
					$arrival_query   = $wpdb->prepare(
						"AND HOUR({$this->arrival}) = HOUR(%s) AND TIMEDIFF({$this->arrival}, %s) < %d",
						$date_to_check,
						$date_to_check,
						$this->interval
					);
					$departure_query = $wpdb->prepare(
						"AND HOUR({$this->departure}) = HOUR(%s) AND TIMEDIFF({$this->departure}, %s) < %d",
						$date_to_check,
						$date_to_check,
						$this->interval
					);
					$hour_query      = $wpdb->prepare(
						"AND TIMEDIFF({$this->departure}, %s) < %d",
						$date_to_check,
						$this->interval
					);
				} else {
					$arrival_query   = $wpdb->prepare( "AND HOUR({$this->arrival}) = HOUR(%s)", $date_to_check );
					$departure_query = $wpdb->prepare( "AND HOUR({$this->departure}) = HOUR(%s)", $date_to_check );
					$hour_query      = "AND HOUR({$this->arrival}) != HOUR({$this->departure})";
				}
			} else {
				$query = $wpdb->prepare(
					"DATE(%s) BETWEEN DATE({$this->arrival}) AND DATE({$this->departure}) AND " .
					" (DATE({$this->arrival}) != DATE({$this->departure}) OR HOUR({$this->departure}) >= %d)",
					$date_to_check,
					$this->arrival_possible_until
				);
			}
			$case         = $wpdb->prepare(
				"Case When DATE({$this->departure}) = DATE(%s) {$departure_query} AND HOUR({$this->departure}) <= %s THEN 0 ELSE 1 END",
				$date_to_check,
				$this->arrival_possible_until
			);
			$case_happens = $wpdb->prepare(
				"Case When DATE({$this->departure}) = DATE(%s) {$departure_query} AND DATE({$this->departure}) != DATE({$this->arrival}) {$hour_query} THEN 1 " .
				"When DATE({$this->arrival}) = DATE(%s) {$arrival_query} AND DATE({$this->departure}) != DATE({$this->arrival}) {$hour_query} THEN 1 ELSE 0 END",
				$date_to_check,
				$date_to_check
			);
			$case_shorts  = $wpdb->prepare(
				"DATE({$this->departure}) = DATE({$this->arrival}) AND TIMESTAMPDIFF(SECOND, {$this->arrival}, {$this->departure}) < %d ",
				$this->interval
			);

			$count = $wpdb->get_results(
				"SELECT sum(Case When 1=1 THEN 1 ELSE 0 END) as das, sum({$case}) as count, sum({$case_happens}) as happens, sum({$case_shorts}) as shorts " .
				"FROM {$wpdb->prefix}reservations WHERE {$this->status} AND {$this->resource_query} {$query}",
				ARRAY_A
			);

			return array( $error + $count[0]["count"], $count[0]["happens"], $count[0]["shorts"] );
		}
	}
}
