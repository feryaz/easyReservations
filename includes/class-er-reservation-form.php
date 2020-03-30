<?php
/**
 * Reservation form functionality
 *
 * The easyReservations checkout class handles the checkout process, collecting user data and processing the payment.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Reservation_Form class.
 */
class ER_Reservation_Form extends ER_Form {

	/**
	 * The single instance of the class.
	 *
	 * @var ER_Reservation_Form|null
	 */
	protected static $instance = null;

	/**
	 * Gets the main ER_Reservation_Form Instance.
	 *
	 * @static
	 * @return ER_Reservation_Form Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// easyreservations_checkout_init action is ran once when the class is first constructed.
			do_action( 'easyreservations_reservation_form_init', self::$instance );
		}

		return self::$instance;
	}

	/**
	 * Output form
	 *
	 * @param array  $atts
	 * @param string $form_type
	 */
	public function get_form( $atts, $form_type ) {
		$this->enqueue();

		$form_id   = '';
		$form_hash = rand( 0, 99999 );

		if ( isset( $atts[0] ) ) {
			$form_id = sanitize_key( $atts[0] );
		}

		if ( $atts['direct_checkout'] ) {
			wp_enqueue_script( 'er-checkout' );
			do_action( 'easyreservations_before_checkout_form' );

			// If checkout registration is disabled and not logged in, the user cannot checkout.
			if ( ! er_is_registration_enabled() && er_is_registration_required() && ! is_user_logged_in() ) {
				echo esc_html( apply_filters( 'easyreservations_checkout_must_be_logged_in_message', __( 'You must be logged in to reserve.', 'easyReservations' ) ) );

				return;
			}
		} else {
			do_action( 'easyreservations_before_form' );
		}

		er_get_template( 'form/header.php', array(
			'form_hash' => $form_hash,
			'form_id'   => $form_id,
			'form_type' => $form_type,
			'atts'      => $atts
		) );

		echo $this->generate( $form_id, $form_hash );

		if ( $atts['direct_checkout'] ) {
			er_get_template( 'form/direct-checkout.php', array(
				'button_text' => $atts['button_text']
			) );
		} else {
			if ( $atts['price'] == 1 ) {
				er_get_template( 'form/price.php' );
			}

			er_get_template( 'form/submit.php', array(
				'button_text' => $atts['button_text']
			) );
		}

		er_get_template( 'form/footer.php' );
	}

	/**
	 * Get form data
	 *
	 * @param bool $submit wether this request is intending to submit to db
	 *
	 * @return array|bool|WP_Error
	 */
	public static function get_posted_data( $submit = false ) {
		if ( isset( $_POST['resource'], $_POST['arrival'] ) ) {
			$resource = ER()->resources()->get( absint( $_POST['resource'] ) );

			if ( ! $resource ) {
				return new WP_Error( 'resource', __( 'Resource not existing', 'easyReservations' ) );
			}

			$array = array(
				'resource_id'   => $resource->get_id(),
				'adults'        => isset( $_POST['adults'] ) ? intval( $_POST['adults'] ) : 1,
				'children'      => isset( $_POST['children'] ) ? intval( $_POST['children'] ) : 0,
				'form_template' => isset( $_POST['easy_form_id'] ) ? sanitize_text_field( $_POST['easy_form_id'] ) : '',
			);

			if ( isset( $_POST['slot'] ) && $_POST['slot'] > - 1 ) {
				$array['slot'] = absint( $_POST['slot'] );
			}

			if ( isset( $_POST['title'] ) ) {
				$array['title'] = sanitize_text_field( $_POST['title'] );
			}

			$arrival = er_date_add_seconds( wp_date( er_date_format() ), $resource->get_frequency() );

			if ( isset( $_POST['arrival'] ) ) {
				if ( empty( $_POST['arrival'] ) ) {
					if ( $submit ) {
						return new WP_Error( 'arrival', sprintf( __( 'Please select %s', 'easyReservations' ), __( 'your arrival date', 'easyReservations' ) ) );
					} else {
						return false;
					}
				} else {
					$arrival = new ER_DateTime( sanitize_text_field( $_POST['arrival'] ) . ' 00:00:00' );
				}
			}

			$arrival_hour     = isset( $_POST['arrival_hour'] ) && ! empty( $_POST['arrival_hour'] ) ? intval( $_POST['arrival_hour'] ) : false;
			$arrival_minute   = isset( $_POST['arrival_minute'] ) && ! empty( $_POST['arrival_minute'] ) ? intval( $_POST['arrival_minute'] ) : false;
			$departure_hour   = isset( $_POST['departure_hour'] ) && ! empty( $_POST['departure_hour'] ) ? intval( $_POST['departure_hour'] ) : false;
			$departure_minute = isset( $_POST['departure_minute'] ) && ! empty( $_POST['departure_minute'] ) ? intval( $_POST['departure_minute'] ) : false;

			if ( $arrival_hour || $arrival_minute ) {
				$arrival->setTime( $arrival_hour ? $arrival_hour : 0, $arrival_minute ? $arrival_minute : 0 );
			} else {
				$arrival->setTime( $resource->get_default_arrival_time(), 0 );
			}

			if ( isset( $_POST['departure'] ) ) {
				if ( empty( $_POST['departure'] ) ) {
					$departure = er_date_add_seconds( $arrival, $resource->get_frequency() );
				} else {
					$departure = new ER_DateTime( sanitize_text_field( $_POST['departure'] ) . ' 00:00:00' );
				}
			} else {
				$departure = clone $arrival;

				if ( isset( $_POST['units'] ) && ! empty( $_POST['units'] ) ) {
					$interval = isset( $_POST['interval'] ) && $_POST['interval'] > 0 ? intval( $_POST['interval'] ) : $resource->get_frequency();
					$departure->add( new DateInterval( 'PT' . absint( floatval( $_POST['units'] ) * $interval ) . 'S' ) );
				} elseif ( ! isset( $_POST['departure_hour'] ) || empty( $_POST['departure_hour'] ) ) {
					$departure->add( new DateInterval( 'PT' . $resource->get_frequency() . 'S' ) );
				}
			}

			if ( $departure_hour || $departure_minute ) {
				$departure->setTime( $departure_hour ? $departure_hour : 0, $departure_minute ? $departure_minute : 0 );
			} elseif ( ! isset( $_POST['units'] ) ) {
				$departure->setTime( $resource->get_default_departure_time(), 0 );
			}

			$array['arrival']   = $arrival;
			$array['departure'] = $departure;

			return $array;
		}

		return false;
	}

	/**
	 * Process reservation submit
	 *
	 * @param bool $submit
	 *
	 * @return bool|ER_Reservation
	 */
	public function process_reservation( $submit = false ) {
		$reservation_data = self::get_posted_data( $submit );

		if ( $reservation_data ) {
			try {
				$nonce_value = er_get_var( $_REQUEST['easyreservations-process-reservation-nonce'], er_get_var( $_REQUEST['_wpnonce'], '' ) ); // @codingStandardsIgnoreLine.
				if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'easyreservations-process-reservation' ) ) {
					ER()->session->set( 'refresh_totals', true );
					throw new Exception( __( 'We were unable to process your reservation, please try again.', 'easyReservations' ) );
				}

				if ( ! is_wp_error( $reservation_data ) ) {
					$errors = new WP_Error();

					//returns manager back or WP_Error
					$reservation = ER()->reservation_manager()->create( $reservation_data );

					if ( $reservation && ! is_wp_error( $reservation ) ) {
						$reservation->calculate_price();

						$customs = $this->get_form_data_custom( $errors, $reservation );

						if ( $customs && ! empty( $customs ) ) {
							foreach ( $customs as $custom ) {
								$reservation->add_custom( $custom );
							}
						}

						$errors = $reservation->validate( $errors, ER()->cart->get_reservations(), $submit );

						if ( ! is_wp_error( $errors ) || ! $errors->has_errors() ) {
							$reservation->calculate_taxes( false );
							$reservation->calculate_totals( false );

							if ( $submit ) {
								$errors = $reservation->save();

								if ( ! is_wp_error( $errors ) ) {
									if ( apply_filters( 'easyreservations_add_reservation_to_cart', true, $reservation ) ) {
										ER()->cart->add_reservation_to_cart( $reservation );
									}

									return $reservation;
								}
							} else {
								return $reservation;
							}
						}
					} else {
						$errors = $reservation;
					}
				} else {
					$errors = $reservation_data;
				}

				foreach ( $errors->errors as $type => $error ) {
					foreach ( $error as $err ) {
						er_add_notice( $err . '<span class="er-error-type" data-type="' . esc_attr( $type ) . '"></span>', 'error' );
					}
				}
			} catch ( Exception $e ) {
				er_add_notice( $e->getMessage(), 'error' );
			}
		}

		$this->send_ajax_failure_response();

		return false;
	}
}
