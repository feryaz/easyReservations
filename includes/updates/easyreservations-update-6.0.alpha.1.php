<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//delete_reservation_meta( 'easy-resource-taxes' );

global $wpdb;

//Only do database preparations if this is the first time
if ( is_null( get_option( 'last_processed_reservation', null ) ) ) {
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations ADD status varchar(10) NOT NULL default ''" );
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations ADD order_id BIGINT UNSIGNED NOT NULL DEFAULT 0" );

	$wpdb->query( "DELETE FROM {$wpdb->prefix}reservations WHERE country = 'ICS'" );
	$wpdb->query( "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key = 'easy-resource-taxes'" );

	$wpdb->query( "UPDATE {$wpdb->prefix}reservations SET status = ( CASE 
    WHEN approve = 'yes' THEN 'approved' 
    WHEN approve = 'no' THEN 'cancelled' 
    WHEN approve = 'del' THEN 'trash' 
    ELSE 'pending' 
  END ) WHERE 1=1" );
}

$reservations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}reservations ORDER BY id ASC", ARRAY_A );
$count = count( $reservations );

$custom_fields = ER_Custom_Data::get_settings();
$start_time    = time();

foreach ( $reservations as $reservation_data ) {
	$id             = absint( $reservation_data['id'] );
	$last_processed = get_option( 'last_processed_reservation', 0 );

	if ( $last_processed < $id ) {
		$reserved   = new ER_DateTime( sanitize_text_field( $reservation_data['reserved'] ) );
		$country    = sanitize_key( $reservation_data['country'] );
		$price      = floatval( $reservation_data['price'] );
		$paid       = floatval( $reservation_data['paid'] );
		$name       = sanitize_text_field( $reservation_data['name'] );
		$explode_name = explode( ' ', $name );
		$last_name  = array_pop( $explode_name );
		$first_name = implode( ' ', $explode_name );
		$street     = false;
		$phone      = false;
		$postcode   = false;
		$city       = false;
		$message    = false;

		$custom_data = get_reservation_meta( $id, 'custom', false );
		if ( ! $price ) {
			$price   = 0;
			$history = get_reservation_meta( $id, 'history', true );

			if ( $history && is_array( $history ) ) {
				foreach ( $history as $line ) {
					if ( isset( $line['price'] ) && is_numeric( $line['price'] ) ) {
						$price += floatval( $line['price'] );
					}
				}
			}
		}

		$new_custom_data = array();

		foreach ( $custom_data as $custom ) {
			$custom_id = intval( $custom['id'] );

			if ( isset( $custom_fields[ $custom_id ] ) ) {
				switch ( $custom_fields[ $custom_id ]['title'] ) {
					case 'address':
					case 'Address':
					case 'street':
					case 'Street':
						$street = sanitize_text_field( $custom['value'] );
						break;
					case 'phone':
					case 'Phone':
						$phone = sanitize_text_field( $custom['value'] );
						break;
					case 'postcode':
					case 'Postcode':
					case 'PostCode':
						$postcode = sanitize_text_field( $custom['value'] );
						break;
					case 'city':
					case 'City':
						$city = sanitize_text_field( $custom['value'] );
						break;
					case 'message':
					case 'Message':
						$message = sanitize_textarea_field( $custom['value'] );
						break;
					default:
						$new_custom = ER_Custom_Data::get_data( $custom_id, $custom['value'] );

						if ( $new_custom ) {
							$new_custom_data[] = $new_custom;
						}
						break;
				}
			}
		}

		$reservation = er_get_reservation( $id );

		if ( $reservation ) {
			if ( ! empty( $new_custom_data ) ) {
				foreach ( $new_custom_data as $custom ) {
					$reservation->add_custom( $custom );
				}
			}

			$order = new ER_Order( 0 );

			$order_status = $reservation_data['status'] === 'trash' ? 'cancelled' : ( $reservation_data['status'] === 'approved' ? 'on-hold' : sanitize_key( $reservation_data['status'] ) );
			$order_status = $order_status === 'on-hold' && $paid > 0 ? 'completed' : $order_status;

			$order->set_props( array(
				'date_created'  => $reserved,
				'date_modified' => $reserved,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'status'        => $order_status,
				'country'       => sanitize_text_field( $reservation_data['country'] ),
				'email'         => sanitize_email( $reservation_data['email'] ),
				'customer_id'   => absint( $reservation_data['user'] )
			) );

			$order->add_reservation( $reservation, false, $price );
			$order->set_total( $price );
			$order->set_paid( $paid );

			if ( $street ) {
				$order->set_address_1( $street );
			}

			if ( $phone ) {
				$order->set_phone( $phone );
			}

			if ( $postcode ) {
				$order->set_postcode( $postcode );
			}

			if ( $city ) {
				$order->set_city( $city );
			}

			if ( $message ) {
				$order->set_customer_note( $message );
			}

			$order_id = $order->save();

			$reservation->set_title( $name );
			$reservation->set_order_id( $order_id );
			$reservation->set_date_created( $reserved );

			$reservation->save();
		}

		update_option( 'last_processed_reservation', $id );

		if ( time() - $start_time > 20 ) {
			echo sprintf(
				esc_html__( 'Updated reservations until #%1$d of %2$d. Stopped to prevent timeout at a bad moment. Please refresh or run the updater again to continue.', 'easyReservations' ),
				$id,
				$count
			);

			exit;
		}
	}
}

$wpdb->query( "DELETE FROM {$wpdb->prefix}reservationmeta WHERE meta_key in ( 'custom', 'history', 'chat' )" );

$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP approve" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP user" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP name" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP country" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP price" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP paid" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP reserved" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}reservations DROP email" );

ER_Install::remove_roles();
ER_Install::create_roles();

$general_settings = get_option( 'reservations_settings' );

if ( $general_settings && is_array( $general_settings ) ) {
	if ( isset( $general_settings['currency'] ) && is_array( $general_settings['currency'] ) ) {
		if ( $general_settings['currency']['place'] === 0 ) {
			$position = 'right';
		} else {
			$position = 'left';
		}

		if ( $general_settings['currency']['whitespace'] === 1 ) {
			$position .= '_space';
		}

		if ( isset( $general_settings['currency']['locale'] ) ) {
			update_option( 'reservations_currency', sanitize_key( $general_settings['currency']['locale'] ) );
		}
		update_option( 'reservations_price_thousand_sep', sanitize_text_field( $general_settings['currency']['divider1'] ) );
		update_option( 'reservations_price_decimal_sep', sanitize_text_field( $general_settings['currency']['divider2'] ) );
		update_option( 'reservations_price_decimals', intval( $general_settings['currency']['decimal'] ) );
		update_option( 'reservations_currency_pos', $position );
	}

	if ( isset( $general_settings['mergeres'] ) && is_array( $general_settings['mergeres'] ) ) {
		update_option( 'reservations_block_before', intval( $general_settings['mergeres']['blockbefore'] ) );
		update_option( 'reservations_block_after', intval( $general_settings['mergeres']['blockafter'] ) );
		update_option( 'reservations_merge_resources', intval( $general_settings['mergeres']['merge'] ) );
	}

	update_option( 'reservations_prices_include_tax', isset( $general_settings['prices_include_tax'] ) && $general_settings['prices_include_tax'] ? 'yes' : 'no' );
	update_option( 'reservations_use_time', isset( $general_settings['time'] ) && $general_settings['time'] ? 'yes' : 'no' );
	update_option( 'reservations_date_format', sanitize_text_field( $general_settings['date_format'] ) );
	update_option( 'reservations_time_format', sanitize_text_field( $general_settings['time_format'] ) );
}

delete_option( 'reservations_settings' );
delete_option( 'reservations_main_permission' );
delete_option( 'reservations_regular_guests' );
delete_option( 'easyreservations_successful_script' );
delete_option( 'reservations_support_mail' );
delete_option( 'reservations_price_per_persons' );
delete_option( 'reservations_active_modules' );

delete_option( 'reservations_email_to_userapp_subj' );
delete_option( 'reservations_email_to_userapp_msg' );
delete_option( 'reservations_email_to_userdel_subj' );
delete_option( 'reservations_email_to_userdel_msg' );
delete_option( 'reservations_email_to_admin_subj' );
delete_option( 'reservations_email_to_admin_msg' );
delete_option( 'reservations_email_to_user_subj' );
delete_option( 'reservations_email_to_user_msg' );
delete_option( 'reservations_email_to_user_edited_subj' );
delete_option( 'reservations_email_to_user_edited_msg' );
delete_option( 'reservations_email_to_admin_edited_subj' );
delete_option( 'reservations_email_to_admin_edited_msg' );
delete_option( 'reservations_email_to_user_admin_edited_subj' );
delete_option( 'reservations_email_to_user_admin_edited_msg' );
delete_option( 'reservations_email_sendmail_subj' );
delete_option( 'reservations_email_sendmail_msg' );
delete_option( 'reservations_email_sendmail' );
delete_option( 'reservations_email_to_admin' );
delete_option( 'reservations_email_to_admin' );
delete_option( 'reservations_email_to_user' );
delete_option( 'reservations_email_to_userapp' );
delete_option( 'reservations_email_to_userdel' );
delete_option( 'reservations_email_to_user_admin_edited' );
delete_option( 'reservations_email_to_user_edited' );
delete_option( 'reservations_email_to_admin_paypal' );
delete_option( 'reservations_email_to_user_paypal' );

delete_option( 'last_processed_reservation' );