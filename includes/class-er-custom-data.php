<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Data {

	/**
	 * Custom fields settings array
	 *
	 * @var array
	 */
	protected static $customs = array();

	/**
	 * Load all custom fields settings from db
	 */
	protected static function load() {
		if ( ! empty( self::$customs ) || self::$customs === null ) {
			return;
		}

		$custom_fields = get_option( 'reservations_custom_fields' );
		$custom_fields = isset( $custom_fields['fields'] ) ? $custom_fields['fields'] : array();

		foreach ( $custom_fields as $custom_id => $custom_field ) {
			if ( isset( $custom_field['type'] ) ) {
				$custom_id = absint( $custom_id );

				self::$customs[ $custom_id ]       = er_clean( $custom_field );
				self::$customs[ $custom_id ]['id'] = $custom_id;
			}
		}

		if ( empty( self::$customs ) ) {
			self::$customs = null;
		}
	}

	/**
	 * If custom field exists
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public static function exists( $id ) {
		$id = absint( $id );

		if ( self::get( $id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get custom fields settings
	 *
	 * @param int $id
	 *
	 * @return bool|mixed
	 */
	public static function get( $id ) {
		self::load();
		$id = absint( $id );

		if ( isset( self::$customs[ $id ] ) ) {
			return self::$customs[ $id ];
		}

		return false;
	}

	/**
	 * Get all custom fields settings
	 *
	 * @param int $id
	 *
	 * @return bool|mixed
	 */
	public static function get_settings() {
		self::load();

		return self::$customs;
	}

	/**
	 * Get name of class to call
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function get_class_name( $type ) {
		return 'ER_Custom_' . ucfirst( sanitize_key( $type ) );
	}

	/**
	 * Validate custom field value
	 *
	 * @param $id
	 * @param $errors
	 *
	 * @return mixed
	 */
	public static function validate( $id, $errors ) {
		$field = self::get( $id );

		if ( $field ) {
			$class_name = self::get_class_name( $field['type'] );

			if ( class_exists( $class_name ) ) {
				return $class_name::validate( $errors, $id, $field, array() );
			}
		}

		return $errors;
	}

	/**
	 * Generate form field
	 *
	 * @param int   $id
	 * @param array $data
	 *
	 * @return string
	 */
	public static function generate( $id, $data ) {
		$id    = absint( $id );
		$field = self::get( $id );

		if ( $field ) {
			$class_name = self::get_class_name( $field['type'] );

			if ( class_exists( $class_name ) ) {
				return $class_name::get_form_field( $id, $field, $data );
			}
		}

		return ER_Custom_Text::get_form_field( $id, $field, $data );
	}

	/**
	 * Get custom fields data
	 *
	 * @param int   $id
	 * @param mixed $value
	 *
	 * @return array|bool
	 */
	public static function get_data( $id, $value = false ) {
		$id    = absint( $id );
		$field = self::get( $id );

		if ( $field ) {
			$class_name = self::get_class_name( $field['type'] );

			if ( class_exists( $class_name ) ) {
				return array(
					'custom_id'      => $id,
					'custom_title'   => $field['title'],
					'custom_value'   => $class_name::get_value( $id, $field, $value ),
					'custom_display' => $class_name::get_display_value( $id, $field, $value )
				);
			}
		}

		return false;
	}

	/**
	 * Calculate custom value
	 *
	 * @param int                     $id
	 * @param string|array            $values
	 * @param array                   $all
	 * @param ER_Order|ER_Reservation $object
	 *
	 * @return float|int|mixed
	 */
	public static function calculate( $id, $values, $all, $object ) {
		$field = self::get( $id );
		$total = 0;

		if ( $field ) {
			if ( ! is_array( $values ) ) {
				$values = array( $values );
			}

			$i = 0;

			foreach ( $values as $value ) {
				if ( isset( $field['options'][ $value ] ) ) {
					$option = (array) $field['options'][ $value ];
				} else {
					$option = (array) current( $field['options'] );
				}

				$option_amount = $option['price'];

				if ( $field['type'] == 'number' || $field['type'] == 'slider' && isset( $option['mode'] ) ) {
					$option_amount = $option_amount * floatval( $value );
				}

				if ( isset( $option['clauses'] ) ) {
					$last_next = false;
					$applied   = false;

					foreach ( $option['clauses'] as $clause ) {
						$true = false;

						if ( $last_next ) {
							if ( $last_next[0] == "and" && ! $last_next[1] ) {
								if ( is_numeric( $clause['price'] ) ) {
									$last_next = false;
								} else {
									$last_next[0] = sanitize_text_field( $clause['price'] );
								}

								continue;
							}

							if ( $last_next[0] == "or" && $last_next[1] ) {
								$true = true;
							}
						}

						if ( ! $true ) {
							if ( $clause['type'] == 'field' ) {
								if ( isset( $all[ $clause['operator'] ] ) ) {
									if ( $clause['cond'] == "any" || $clause['cond'] == $all[ $clause['operator'] ]['custom_value'] ) {
										$true = true;
									}
								} else {
									foreach ( $all as $filter ) {
										if ( isset( $filter['custom_id'] ) && $filter['custom_id'] == $clause['operator'] ) {
											if ( $clause['cond'] == "any" || $clause['cond'] == $filter['custom_value'] ) {
												$true = true;
											}
											break;
										}
									}
								}
							} else {
								$comparator = false;
								if ( $clause['type'] == 'value' ) {
									$comparator = floatval( $value );
								} elseif ( $object instanceof ER_Reservation ) {
									if ( $clause['type'] == 'resource' ) {
										$comparator = $object->get_resource_id();
									} elseif ( $clause['type'] == 'units' ) {
										$comparator = $object->get_billing_units();
									} elseif ( $clause['type'] == 'adult' ) {
										$comparator = $object->get_adults();
									} elseif ( $clause['type'] == 'child' ) {
										$comparator = $object->get_children();
									} elseif ( $clause['type'] == 'time' ) {
										$comparator     = date( 'Y-m-d', er_get_time() );
										$clause['cond'] = date( 'Y-m-d', strtotime( sanitize_text_field( $clause['cond'] ) ) );
									} elseif ( $clause['type'] == 'arrival' ) {
										$comparator     = date( 'Y-m-d', $object->get_arrival() );
										$clause['cond'] = date( 'Y-m-d', strtotime( sanitize_text_field( $clause['cond'] ) ) );
									} elseif ( $clause['type'] == 'departure' ) {
										$comparator     = date( 'Y-m-d', $object->get_departure() );
										$clause['cond'] = date( 'Y-m-d', strtotime( sanitize_text_field( $clause['cond'] ) ) );
									} elseif ( $clause['type'] == 'time_every' ) {
										$comparator = date( 'm.d', er_get_time() );
									} elseif ( $clause['type'] == 'arrival_every' ) {
										$comparator = date( 'm.d', $object->get_arrival() );
									} elseif ( $clause['type'] == 'departure_every' ) {
										$comparator = date( 'm.d', $object->get_departure() );
									}
								} elseif ( $object instanceof ER_Order ) {
								}

								if ( ! $comparator ) {
									break;
								}

								switch ( $clause['operator'] ) {
									case "equal":
										if ( $clause['cond'] == $comparator ) {
											$true = true;
										}
										break;
									case "notequal":
										if ( $clause['cond'] !== $comparator ) {
											$true = true;
										}
										break;
									case "greater":
										if ( $clause['cond'] < $comparator ) {
											$true = true;
										}
										break;
									case "greaterequal":
										if ( $clause['cond'] <= $comparator ) {
											$true = true;
										}
										break;
									case "smaller":
										if ( $clause['cond'] > $comparator ) {
											$true = true;
										}
										break;
									case "smallerequal":
										if ( $clause['cond'] >= $comparator ) {
											$true = true;
										}
										break;
								}
							}
						}

						if ( $true && is_numeric( $clause['price'] ) ) {
							//if ( ( $field['type'] == 'number' || $field['type'] == 'slider' ) && !isset( $option['mode'] ) ) { $amount = floatval( current( $field['options'] ) );
							$clause_amount = floatval( $clause['price'] );
							$applied       = true;

							if ( ( $field['type'] == 'number' || $field['type'] == 'slider' ) && isset( $option['mode'] ) ) {
								$clause_amount = $clause_amount * floatval( $value );
							}

							if ( $clause['mult'] && $clause['mult'] !== 'x' ) {
								if ( $object instanceof ER_Reservation ) {
									$clause_amount = er_reservation_multiply_amount(
										$object, sanitize_key( $clause['mult'] ), $clause_amount
									);
								}
							}

							$total += $clause_amount;
						}

						$last_next = false;

						if ( ! is_numeric( $clause['price'] ) ) {
							$last_next = array( sanitize_text_field( $clause['price'] ), $true );
						}
					}

					if ( ! $applied ) {
						$total += $option_amount;
					}
				} else {
					$total += $option_amount;
				}

				$i ++;
			}
		}

		return $total;
	}
}
