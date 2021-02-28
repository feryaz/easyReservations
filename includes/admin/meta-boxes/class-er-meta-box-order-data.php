<?php
/**
 * Order Data
 *
 * Functions for displaying the order data meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * ER_Meta_Box_Order_Data Class.
 */
class ER_Meta_Box_Order_Data {

	/**
	 * Billing fields.
	 *
	 * @var array
	 */
	protected static $address_fields = array();

	/**
	 * Init billing and shipping fields we display + save.
	 */
	public static function init_address_fields() {

		self::$address_fields = apply_filters(
			'easyreservations_admin_address_fields', array(
				'first_name' => array(
					'label' => __( 'First name', 'easyReservations' ),
					'show'  => false,
				),
				'last_name'  => array(
					'label' => __( 'Last name', 'easyReservations' ),
					'show'  => false,
				),
				'company'    => array(
					'label' => __( 'Company', 'easyReservations' ),
					'show'  => false,
				),
				'address_1'  => array(
					'label' => __( 'Address line 1', 'easyReservations' ),
					'show'  => false,
				),
				'address_2'  => array(
					'label' => __( 'Address line 2', 'easyReservations' ),
					'show'  => false,
				),
				'city'       => array(
					'label' => __( 'City', 'easyReservations' ),
					'show'  => false,
				),
				'postcode'   => array(
					'label' => __( 'Postcode / ZIP', 'easyReservations' ),
					'show'  => false,
				),
				'country'    => array(
					'label'   => __( 'Country', 'easyReservations' ),
					'show'    => false,
					'class'   => 'js_field-country select short',
					'type'    => 'select',
					'options' => array( '' => __( 'Select a country / region&hellip;', 'easyReservations' ) ) + ER()->countries->get_countries(),
				),
				'state'      => array(
					'label' => __( 'State / County', 'easyReservations' ),
					'class' => 'js_field-state select short',
					'show'  => false,
				),
				'email'      => array(
					'label' => __( 'Email address', 'easyReservations' ),
				),
				'phone'      => array(
					'label' => __( 'Phone', 'easyReservations' ),
				),
			)
		);
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $theorder;

		if ( ! is_object( $theorder ) ) {
			$theorder = er_get_order( absint( $post->ID ) );
		}

		$order = $theorder;

		self::init_address_fields();

		$reservations_approved_and_existing = er_order_reservations_approved_and_existing( $order );

		$payment_gateways = ER()->payment_gateways()->payment_gateways();

		$payment_method = $order->get_payment_method();

		$order_type_object = get_post_type_object( $post->post_type );
		wp_nonce_field( 'easyreservations_save_data', 'easyreservations_meta_nonce' );
		?>
        <style type="text/css">
            #post-body-content, #titlediv {
                display: none
            }
        </style>
        <div class="panel-wrap easyreservations">
            <input name="object_id" id="object_id" type="hidden" value="<?php echo esc_attr( $post->ID ); ?>"/>
            <input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? esc_attr__( 'Order', 'easyReservations' ) : esc_attr( $post->post_title ); ?>"/>
            <input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>"/>
            <div id="order_data" class="panel easyreservations-order-data">
                <h2 class="easyreservations-order-data__heading">
					<?php

					/* translators: 1: order type 2: order number */
					printf(
						esc_html__( '%1$s #%2$s details', 'easyReservations' ),
						esc_html( $order_type_object->labels->singular_name ),
						esc_html( $order->get_order_number() )
					);

					?>
                </h2>
                <p class="easyreservations-order-data__meta order_number">
					<?php

					$meta_list = array();

					if ( $payment_method && 'other' !== $payment_method ) {
						/* translators: %s: payment method */
						$payment_method_string = sprintf(
							__( 'Payment via %s', 'easyreservations' ),
							esc_html( isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ]->get_title() : $payment_method )
						);

						if ( $transaction_id = $order->get_transaction_id() ) {
							if ( isset( $payment_gateways[ $payment_method ] ) && ( $url = $payment_gateways[ $payment_method ]->get_transaction_url( $order ) ) ) {
								$payment_method_string .= ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)';
							} else {
								$payment_method_string .= ' (' . esc_html( $transaction_id ) . ')';
							}
						}

						$meta_list[] = $payment_method_string;
					}

					if ( $order->get_date_paid() ) {
						/* translators: 1: date 2: time */
						$meta_list[] = sprintf(
							__( 'Paid on %1$s @ %2$s', 'easyReservations' ),
							er_format_datetime( $order->get_date_paid() ),
							er_format_datetime( $order->get_date_paid(), get_option( 'time_format' ) )
						);
					}

					echo wp_kses_post( implode( '. ', $meta_list ) );

					?>
                </p>
                <div class="order_data_column_container">
                    <div class="order_data_column">
                        <h3><?php esc_html_e( 'General', 'easyReservations' ); ?></h3>

                        <p class="form-field form-field-wide">
                            <label for="order_date"><?php esc_html_e( 'Date created:', 'easyReservations' ); ?></label>
                            <input type="text" class="er-datepicker date-created" name="order_date" maxlength="10" data-format="yy-mm-dd" value="<?php echo esc_attr( date_i18n( 'Y-m-d', strtotime( $post->post_date ) ) ); ?>" pattern="<?php echo esc_attr( apply_filters( 'easyreservations_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>"/>@
                            <input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'easyReservations' ); ?>" name="order_date_hour" min="0" max="23" step="1" value="<?php echo esc_attr( date_i18n( 'H', strtotime( $post->post_date ) ) ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})"/>:
                            <input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'easyReservations' ); ?>" name="order_date_minute" min="0" max="59" step="1" value="<?php echo esc_attr( date_i18n( 'i', strtotime( $post->post_date ) ) ); ?>" pattern="[0-5]{1}[0-9]{1}"/>
                            <input type="hidden" name="order_date_second" value="<?php echo esc_attr( date_i18n( 's', strtotime( $post->post_date ) ) ); ?>"/>
                        </p>

                        <p class="form-field form-field-wide er-order-status">
                            <label for="order_status">
								<?php
								esc_html_e( 'Status:', 'easyReservations' );
								if ( true ) {
									printf(
										'<a href="%s">%s</a>',
										esc_url( $order->get_checkout_payment_url() ),
										__( 'Customer payment page &rarr;', 'easyReservations' )
									);
								}
								?>
                            </label>
                            <select id="order_status" name="order_status" class="er-enhanced-select">
								<?php

								foreach ( ER_Order_Status::get_statuses() as $status => $status_name ) {
									$attr = '';
									if( ! $reservations_approved_and_existing && in_array( $status, er_get_is_accepted_statuses() ) && $status !== $order->get_status() ){
										$attr = ' disabled="disabled"';
									}
									echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, $order->get_status(), false ) . $attr . '>' . esc_html( $status_name ) . '</option>';
								}
								?>
                            </select>
                        </p>

                        <p class="form-field form-field-wide er-customer-user">
                            <label for="customer_user">
								<?php
								esc_html_e( 'Customer:', 'easyReservations' );
								if ( $order->get_user_id() ) {
									$args = array(
										'post_status'    => 'all',
										'post_type'      => 'easy_order',
										'_customer_user' => $order->get_user_id(),
									);
									printf(
										'<a href="%s">%s</a>',
										esc_url( add_query_arg( $args, admin_url( 'edit.php' ) ) ),
										' ' . __( 'View other orders &rarr;', 'easyReservations' )
									);
									printf(
										'<a href="%s">%s</a>',
										esc_url( add_query_arg( 'user_id', $order->get_user_id(), admin_url( 'user-edit.php' ) ) ),
										' ' . __( 'Profile &rarr;', 'easyReservations' )
									);
								}
								?>
                            </label>
							<?php
							$user_string = '';
							$user_id     = '';
							if ( $order->get_user_id() ) {
								$user_id = absint( $order->get_user_id() );
								$user    = get_user_by( 'id', $user_id );
								/* translators: 1: user display name 2: user ID 3: user email */
								$user_string = sprintf(
									esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'easyReservations' ),
									$user->display_name,
									absint( $user->ID ),
									$user->user_email
								);
							}
							?>
                            <select class="er-customer-search" id="customer_user" name="customer_user" data-placeholder="<?php esc_attr_e( 'Guest', 'easyReservations' ); ?>" data-allow_clear="true">
                                <option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $user_string ) ); // htmlspecialchars to prevent XSS when rendered by selectWoo. ?></option>
                            </select>
                            <!--/email_off-->
                        </p>

                        <p class="form-field form-field-wide">
                            <label for="amount_paid"><?php esc_html_e( 'Paid:', 'easyReservations' ); ?></label>
                            <input type="text" class="er_input_price" id="amount_paid" name="amount_paid" value="<?php echo esc_attr( $order->get_paid( 'edit' ) ? $order->get_paid( 'edit' ) : 0 ); ?>">
                        </p>

						<?php do_action( 'easyreservations_admin_order_data_after_order_details', $order ); ?>
                    </div>
                    <div class="order_data_column">
                        <h3>
							<?php esc_html_e( 'Address', 'easyReservations' ); ?>
                            <a href="#" class="edit_address"><?php esc_html_e( 'Edit', 'easyReservations' ); ?></a>
                            <span>
								<a href="#" class="load_customer" style="display:none;"><?php esc_html_e( 'Load address', 'easyReservations' ); ?></a>
							</span>
                        </h3>
                        <div class="address">
							<?php
							// Display values.
							if ( $order->get_formatted_address() ) {
								echo '<p>' . wp_kses( $order->get_formatted_address(), array( 'br' => array() ) ) . '</p>';
							} else {
								echo '<p class="none_set"><strong>' . esc_html__( 'Address:', 'easyReservations' ) . '</strong> ' . esc_html__( 'No address set.', 'easyReservations' ) . '</p>';
							}

							foreach ( self::$address_fields as $key => $field ) {
								if ( isset( $field['show'] ) && false === $field['show'] ) {
									continue;
								}

								if ( isset( $field['value'] ) ) {
									$field_value = $field['value'];
								} elseif ( is_callable( array( $order, 'get_' . $key ) ) ) {
									$field_value = $order->{"get_$key"}( 'edit' );
								} else {
									$field_value = $order->get_meta( '_' . $key );
								}

								if ( 'phone' === $key ) {
									$field_value = er_make_phone_clickable( $field_value );
								} elseif ( 'email' === $key ) {
									$field_value = '<a href="' . esc_url( 'mailto:' . $field_value ) . '">' . $field_value . '</a>';
								} else {
									$field_value = make_clickable( esc_html( $field_value ) );
								}

								if ( $field_value ) {
									echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( $field_value ) . '</p>';
								}
							}
							?>
                        </div>

                        <div class="edit_address">
							<?php

							// Display form.
							foreach ( self::$address_fields as $key => $field ) {
								if ( ! isset( $field['type'] ) ) {
									$field['type'] = 'text';
								}
								if ( ! isset( $field['id'] ) ) {
									$field['id'] = '_' . $key;
								}

								if ( ! isset( $field['value'] ) ) {
									if ( is_callable( array( $order, 'get_' . $key ) ) ) {
										$field['value'] = $order->{"get_$key"}( 'edit' );
									} else {
										$field['value'] = $order->get_meta( '_' . $key );
									}
								}

								switch ( $field['type'] ) {
									case 'select':
										easyreservations_wp_select( $field );
										break;
									default:
										easyreservations_wp_text_input( $field );
										break;
								}
							}
							?>
                            <p class="form-field form-field-wide">
                                <label><?php esc_html_e( 'Payment method:', 'easyReservations' ); ?></label>
                                <select name="_payment_method" id="_payment_method" class="first">
                                    <option value=""><?php esc_html_e( 'N/A', 'easyReservations' ); ?></option>
									<?php
									$found_method = false;

									foreach ( $payment_gateways as $gateway ) {
										if ( 'yes' === $gateway->enabled ) {
											echo '<option value="' . esc_attr( $gateway->id ) . '" ' . selected( $payment_method, $gateway->id, false ) . '>' . esc_html( $gateway->get_title() ) . '</option>';
											if ( $payment_method == $gateway->id ) {
												$found_method = true;
											}
										}
									}

									if ( ! $found_method && ! empty( $payment_method ) ) {
										echo '<option value="' . esc_attr( $payment_method ) . '" selected="selected">' . esc_html__( 'Other', 'easyReservations' ) . '</option>';
									} else {
										echo '<option value="other">' . esc_html__( 'Other', 'easyReservations' ) . '</option>';
									}
									?>
                                </select>
                            </p>
							<?php

							easyreservations_wp_text_input(
								array(
									'id'    => '_transaction_id',
									'label' => __( 'Transaction ID', 'easyReservations' ),
									'value' => $order->get_transaction_id(),
								)
							);
							?>

                        </div>
						<?php do_action( 'easyreservations_admin_order_data_after_address', $order ); ?>
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
							$object = $order;

							include 'views/html-custom-data.php';
							?>
                        </div>
						<?php do_action( 'easyreservations_admin_order_data_after_custom', $order ); ?>
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
	 * @param int $order_id Order ID.
	 */
	public static function save( $order_id ) {
		self::init_address_fields();

		// Ensure gateways are loaded in case they need to insert data into the emails.
		ER()->payment_gateways();

		// Get order object.
		$order = er_get_order( $order_id );

		// Create order key.
		if ( ! $order->get_order_key() ) {
			$order->set_order_key( er_generate_order_key() );
		}

		// Update customer.
		$customer_id = isset( $_POST['customer_user'] ) ? absint( $_POST['customer_user'] ) : 0;
		if ( $customer_id !== $order->get_user_id() ) {
			$order->set_customer_id( $customer_id );
		}

		// Update billing fields.
		if ( ! empty( self::$address_fields ) ) {
			foreach ( self::$address_fields as $key => $field ) {
				if ( ! isset( $field['id'] ) ) {
					$field['id'] = '_' . $key;
				}

				if ( ! isset( $_POST[ $field['id'] ] ) ) {
					continue;
				}

				$order->set_address_prop( $key, er_clean( wp_unslash( $_POST[ $field['id'] ] ) ) );
			}
		}

		if ( isset( $_POST['_transaction_id'] ) ) {
			$order->set_transaction_id( er_clean( wp_unslash( $_POST['_transaction_id'] ) ) );
		}

		if ( isset( $_POST['amount_paid'] ) ) {
			$order->set_paid( er_clean( wp_unslash( $_POST['amount_paid'] ) ) );
		}

		// Payment method handling.
		if ( $order->get_payment_method() !== wp_unslash( $_POST['_payment_method'] ) ) {
			$methods              = ER()->payment_gateways()->payment_gateways();
			$payment_method       = er_clean( wp_unslash( $_POST['_payment_method'] ) );
			$payment_method_title = $payment_method;

			if ( isset( $methods ) && isset( $methods[ $payment_method ] ) ) {
				$payment_method_title = $methods[ $payment_method ]->get_title();
			}

			$order->set_payment_method( $payment_method );
			$order->set_payment_method_title( $payment_method_title );
		}

		// Update date.
		if ( empty( $_POST['order_date'] ) ) {
			$date = time();
		} else {
			$date = gmdate( 'Y-m-d H:i:s', strtotime( $_POST['order_date'] . ' ' . (int) $_POST['order_date_hour'] . ':' . (int) $_POST['order_date_minute'] . ':' . (int) $_POST['order_date_second'] ) );
		}

		$order->set_date_created( $date );

		// Set created via prop if new post.
		if ( isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] === 'auto-draft' ) {
			$order->set_created_via( 'admin' );
		}

		// Save order data.
		$order->set_status( er_clean( wp_unslash( $_POST['order_status'] ) ), '', true );

		$order = apply_filters( 'easyreservations_save_order_data', $order );

		$order->save();
	}
}
