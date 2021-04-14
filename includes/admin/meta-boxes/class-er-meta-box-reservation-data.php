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
 * ER_Meta_Box_Order_Data Class.
 */
class ER_Meta_Box_Reservation_Data {

	/**
	 * Output the metabox.
	 *
	 * @param ER_Reservation $reservation
	 */
	public static function output( $reservation ) {
		wp_enqueue_script( 'er-timeline' );

		include RESERVATIONS_ABSPATH . "includes/admin/views/html-timeline.php";

		$order        = false;
		$time_options = er_form_time_options();
		$date_created = $reservation->get_date_created();

		$resource_options    = er_form_resources_options();
		$resource_options[0] = __( 'No resource selected', 'easyReservations' );
		ksort( $resource_options );

		if ( $reservation->get_order_id() ) {
			$order = er_get_order( $reservation->get_order_id() );
		}

		if ( ! $date_created ) {
			$date_created = er_get_datetime();
		}

		$disabled = '';
		if ( ! $reservation->is_editable() ) {
			$disabled = 'disabled';
		}

		wp_nonce_field( 'easyreservations_save_data', 'easyreservations_meta_nonce' );
		?>
        <div class="panel-wrap easyreservations easy-ui">
            <input name="object_id" id="object_id" type="hidden" value="<?php echo esc_attr( $reservation->get_id() ); ?>"/>
            <input name="reservation_old_status" type="hidden" value="<?php echo esc_attr( $reservation->get_status() ); ?>"/>
            <input name="reservation_order_id" type="hidden" value="<?php echo esc_attr( $order ? $order->get_id() : 0 ); ?>"/>
            <div id="order_data" class="panel easyreservations-order-data">
                <h2 class="easyreservations-order-data__heading">
					<?php

					/* translators: 1: order type 2: order number */
					printf(
						esc_html__( '%1$s #%2$s details', 'easyReservations' ),
						esc_html( __( 'Reservation', 'easyReservations' ) ),
						esc_html( $reservation->get_id() )
					);

					?>
                </h2>
                <p class="easyreservations-order-data__meta order_number">
					<?php
					if ( $order ) {
						echo sprintf( esc_html__( 'Attached to order %s', 'easyReservations' ), $order->get_edit_link() );
					}
					?>
                </p>
                <div class="order_data_column_container">
                    <div class="order_data_column">
                        <h3><?php esc_html_e( 'General', 'easyReservations' ); ?></h3>
                        <p class="form-field form-field-wide er-order-status">
                            <label for="reservation_title">
								<?php esc_html_e( 'Title:', 'easyReservations' ); ?>
                            </label>
                            <input type="text" id="reservation_title" name="title" value="<?php echo esc_attr( $reservation->get_title() ); ?>" <?php echo esc_attr( $disabled ); ?>>
                        </p>

                        <p class="form-field form-field-wide">
                            <label for="reservation_date"><?php esc_html_e( 'Date created:', 'easyReservations' ); ?></label>
                            <input type="text" class="er-datepicker date-created" name="reservation_date" maxlength="10" data-format="yy-mm-dd" value="<?php echo esc_attr( $date_created->date_i18n( 'Y-m-d' ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'easyreservations_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" <?php echo esc_attr( $disabled ); ?>/>@
                            <input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'easyReservations' ); ?>" name="reservation_date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( $date_created->date_i18n( 'H' ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" <?php echo esc_attr( $disabled ); ?>/>:
                            <input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'easyReservations' ); ?>" name="reservation_date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( $date_created->date_i18n( 'i' ) ); ?>" pattern="[0-5]{1}[0-9]{1}" <?php echo esc_attr( $disabled ); ?>/>
                            <input type="hidden" name="reservation_date_second" value="<?php echo esc_attr( $date_created->date_i18n( 's' ) ); ?>" <?php echo esc_attr( $disabled ); ?>/>
                        </p>

                        <p class="form-field form-field-wide er-order-status">
                            <label for="reservation_status">
								<?php esc_html_e( 'Status:', 'easyReservations' ); ?>
                            </label>
                            <select id="reservation_status" name="reservation_status" class="er-enhanced-select">
								<?php
								foreach ( ER_Reservation_Status::get_statuses() as $status => $status_name ) {
									echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, $reservation->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
								}
								?>
                            </select>
                        </p>

						<?php do_action( 'easyreservations_admin_reservation_data_after_reservation_details', $reservation ); ?>
                    </div>
                    <div class="order_data_column">
                        <h3>
							<?php esc_html_e( 'Reservation', 'easyReservations' ); ?>
                        </h3>
                        <div class="reservation_data_container">
                            <p class="form-field form-field-wide er-reservation-arrival">
                                <label for="arrival">
									<?php esc_html_e( 'Arrival', 'easyReservations' ); ?>:
                                </label>
                                <span class="input-wrapper">
                                    <input id="arrival" type="text" data-target="departure" name="arrival" value="<?php echo esc_attr( $reservation->get_arrival() ? $reservation->get_arrival()->format( er_date_format() ) : '' ); ?>" class="er-datepicker" title="" autocomplete="new-password" <?php echo esc_attr( $disabled ); ?>>
                                    <span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span>
                                </span>
								<?php
								er_form_get_field( array(
									'id'         => 'arrival_hour',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => $time_options,
									'value'      => $reservation->get_arrival() ? gmdate( 'G', $reservation->get_arrival( 'edit' )->getTimestamp() ) : 12
								) );
								er_form_get_field( array(
									'id'         => 'arrival_minute',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => er_form_number_options( "00", 59 ),
									'value'      => $reservation->get_arrival() ? gmdate( 'i', $reservation->get_arrival( 'edit' )->getTimestamp() ) : '00'
								) );
								?>
                            </p>
                            <p class="form-field form-field-wide er-reservation-departure">
                                <label for="departure">
									<?php esc_html_e( 'Departure', 'easyReservations' ); ?>:
                                </label>
                                <span class="input-wrapper">
                                    <input id="departure" type="text" name="departure" value="<?php echo esc_attr( $reservation->get_departure() ? $reservation->get_departure()->format( er_date_format() ) : '' ); ?>" class="er-datepicker" title="" autocomplete="new-password" <?php echo esc_attr( $disabled ); ?>>
                                    <span class="input-box clickable"><span class="dashicons dashicons-calendar-alt"></span></span>
                                </span>
								<?php
								er_form_get_field( array(
									'id'         => 'departure_hour',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => $time_options,
									'value'      => $reservation->get_departure() ? gmdate( 'G', $reservation->get_departure( 'edit' )->getTimestamp() ) : 12
								) );
								er_form_get_field( array(
									'id'         => 'departure_minute',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => er_form_number_options( "00", 59 ),
									'value'      => $reservation->get_departure() ? gmdate( 'i', $reservation->get_departure( 'edit' )->getTimestamp() ) : '00'
								) );
								?>
                            </p>
                            <p class="form-field form-field-wide er-reservation-resource">
                                <label for="resource">
									<?php esc_html_e( 'Resource', 'easyReservations' ); ?>:
                                </label>
								<?php
								er_form_get_field( array(
									'id'         => 'resource',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => $resource_options,
									'value'      => $reservation->get_resource_id()
								) );
								?>
                            </p>
                            <p class="form-field form-field-wide er-reservation-space">
                                <label for="space">
									<?php esc_html_e( 'Space', 'easyReservations' ); ?>:
                                </label>
								<?php
								foreach ( ER()->resources()->get() as $resource ) {
								    if( $resource->availability_by( 'unit' )){
									    echo '<span class="resource-space resource-' . esc_attr( $resource->get_id() ) . '" style="display:none">';

									    er_form_get_field( array(
										    'id'       => 'space-' . $resource->get_id(),
										    'type'     => 'select',
										    'disabled' => true,
										    'options'  => $resource->get_spaces_options(),
										    'value'    => $reservation->get_space()
									    ) );

									    echo '</span>';
								    }
								}
								?>
                            </p>
                            <p class="form-field er-reservation-adults">
                                <label for="adults">
									<?php esc_html_e( 'Adults', 'easyReservations' ); ?>:
                                </label>
								<?php
								er_form_get_field( array(
									'id'         => 'adults',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => er_form_number_options( 1, 100 ),
									'value'      => $reservation->get_adults()
								) );
								?>
                            </p>
                            <p class="form-field er-reservation-children">
                                <label for="children">
									<?php esc_html_e( 'Children', 'easyReservations' ); ?>:
                                </label>
								<?php
								er_form_get_field( array(
									'id'         => 'children',
									'type'       => 'select',
									'attributes' => array( 'disabled' => $disabled ),
									'options'    => er_form_number_options( 0, 100 ),
									'value'      => $reservation->get_children()
								) );
								?>
                            </p>
                        </div>

						<?php do_action( 'easyreservations_admin_reservation_data_after_reservation_data', $reservation ); ?>
                    </div>
                    <div class="order_data_column">
                        <h3>
							<?php esc_html_e( 'Custom data', 'easyReservations' ); ?>
                            <a href="#" class="edit_custom"><?php esc_html_e( 'Edit', 'easyReservations' ); ?></a>
                            <span>
								<a href="#" class="add_custom" style="display:none;"><?php esc_html_e( 'Add custom data', 'easyReservations' ); ?></a>
							</span>
                        </h3>
                        <div class="custom_data_container">
							<?php
							$object = $reservation;

							include 'views/html-custom-data.php';
							?>
                        </div>
						<?php do_action( 'easyreservations_admin_reservation_data_after_custom', $reservation ); ?>
                    </div>
                </div>
                <div class="clear"></div>
            </div>
        </div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $reservation_id Order ID.
	 */
	public static function save( $reservation_id ) {
		// Ensure gateways are loaded in case they need to insert data into the emails.
		$reservation = er_get_reservation( $reservation_id );

		$arrival = new ER_DateTime( sanitize_text_field( $_POST['arrival'] ) );
		$arrival->setTime( $_POST['arrival_hour'], $_POST['arrival_minute'] );

		$departure = new ER_DateTime( sanitize_text_field( $_POST['departure'] ) );
		$departure->setTime( $_POST['departure_hour'], $_POST['departure_minute'] );

		$reservation->set_props( array(
			'title'       => sanitize_text_field( $_POST['title'] ),
			'arrival'     => $arrival,
			'departure'   => $departure,
			'adults'      => intval( $_POST['adults'] ),
			'children'    => intval( $_POST['children'] ),
		) );

		$reservation->set_resource_id( absint( $_POST['resource'] ) );

		if ( isset( $_POST['space'] ) ) {
			$reservation->set_space( $_POST['space'] );
		}

		// Update date.
		if ( empty( $_POST['reservation_date'] ) ) {
			$date = time();
		} else {
			$date = gmdate( 'Y-m-d H:i:s', strtotime( $_POST['reservation_date'] . ' ' . (int) $_POST['reservation_date_hour'] . ':' . (int) $_POST['reservation_date_minute'] . ':' . (int) $_POST['reservation_date_second'] ) );
		}

		$reservation->set_date_created( $date );

		//Update status
		$new_status = er_clean( wp_unslash( $_POST['reservation_status'] ) );
		if ( $new_status !== $reservation->get_status() ) {
			if (
				$reservation->get_resource() &&
				in_array( $new_status, array( ER_Reservation_Status::APPROVED, ER_Reservation_Status::CHECKED, ER_Reservation_Status::COMPLETED ) ) &&
				is_a( $reservation->check_availability(), 'WP_Error' )
			){
				ER_Admin_Notices::add_temporary_error( __( 'Selected time is occupied.', 'easyReservations' ) );
			} else {
				if ( $reservation->get_resource() || in_array( $new_status, array( ER_Reservation_Status::PENDING, ER_Reservation_Status::TEMPORARY, ER_Reservation_Status::TRASH ) ) ) {
					$reservation->set_status( $new_status, '', true );
				} else {
					ER_Admin_Notices::add_temporary_error( __( 'Set a resource before approving the reservation.', 'easyReservations' ) );
				}
			}
		}

        if( ! ER_Admin_Notices::has_errors() ){
	        if ( $reservation->get_meta( 'new_reservation' ) ) {
		        $price = $reservation->calculate_price();

		        $items = $reservation->get_items( 'resource' );

		        foreach ( $items as $item ) {
			        $item->set_resource_id( $reservation->get_resource_id() );
			        $item->set_subtotal( $price );
			        $item->set_total( $price );
			        $item->calculate_taxes( ER_Tax::get_rates( $reservation->get_resource_id() ) );
			        $item->save();
		        }

		        $reservation->calculate_taxes( false );
		        $reservation->calculate_totals( false );
		        $reservation->delete_meta_data( 'new_reservation' );
	        }

	        if ( ! $reservation->get_id() ) {
		        ER_Admin_Notices::add_temporary_success( __( 'Reservation added', 'easyReservations' ) );
	        } else {
		        ER_Admin_Notices::add_temporary_success( sprintf( __( 'Reservation #%d updated', 'easyReservations' ), $reservation->get_id() ) );
	        }

	        $reservation = apply_filters( 'easyreservations_save_reservation_data', $reservation );

	        $reservation->save();
        }

	}
}
