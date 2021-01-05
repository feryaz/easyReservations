<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_Custom extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'custom';
		$this->label = __( 'Custom', 'easyReservations' );

		add_action( 'easyreservations_no_settings_to_save', array( $this, 'delete_custom' ) );

		parent::__construct();
	}

	/**
	 * Output custom settings
	 */
	public function output() {
		global $hide_save_button;
		$hide_save_button = 'yes';

		$custom_fields = ER_Custom_Data::get_settings();

		$resources = array();
		foreach ( ER()->resources()->get() as $key => $resource ) {
			$resources[ $key ] = stripslashes( $resource->get_title() );
		}

		wp_localize_script(
			'er-settings-custom', 'htmlSettingsCustomLocalizeScript',
			array(
				'currency'          => er_get_currency_symbol(),
				'all_custom_fields' => ER_Custom_Data::get_settings(),
				'resources'         => $resources
			)
		);

		wp_enqueue_script( 'er-settings-custom' );
		wp_enqueue_script( 'er-datepicker' );
		wp_enqueue_style( 'er-datepicker' );

		include 'views/html-admin-settings-custom.php';
	}

	/**
	 * Delete custom field setting
	 */
	public function delete_custom() {
		if ( isset( $_GET["delete-custom"] ) && check_admin_referer( 'easy-delete-custom' ) ) {
			$id            = sanitize_key( $_GET['delete-custom'] );
			$custom_fields = get_option( 'reservations_custom_fields', array() );

			if ( isset( $custom_fields['fields'], $custom_fields['fields'][ $id ] ) ) {
				unset( $custom_fields['fields'][ $id ] );
				update_option( 'reservations_custom_fields', $custom_fields );

				ER_Admin_Notices::add_temporary_success( sprintf( __( '%s deleted', 'easyReservations' ), __( 'Custom field', 'easyReservations' ) ) );
			}
		}
	}

	/**
	 * Add or update custom field setting
	 */
	public function save() {
		if ( empty( $_POST['custom_name'] ) ) {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please enter a name', 'easyReservations' ) ) );

			return;
		}

		$type = sanitize_key( $_POST['custom_field_type'] );
		if ( $type == 'x' ) {
			ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please select %s', 'easyReservations' ), __( 'a type for your custom field', 'easyReservations' ) ) );

			return;
		}

		$custom_fields = get_option( 'reservations_custom_fields', array() );

		$custom = array(
			'title'  => str_replace( array( '\"', "\'" ), '', sanitize_text_field( $_POST['custom_name'] ) ),
			'type'   => $type,
			'unused' => sanitize_text_field( $_POST['custom_field_unused'] )
		);

		if ( $type == 'text' || $type == 'area' ) {
			$custom["value"] = sanitize_text_field( $_POST['custom_field_value'] );
		} else {
			$custom['options'] = array();
			$get_id            = array();
			foreach ( $_POST['id'] as $nr => $id ) {
				$final_id = $id;
				if ( is_numeric( $id ) ) {
					$uid           = uniqid( $id );
					$get_id[ $id ] = $uid;
					$final_id      = $uid;
				}
				$custom['options'][ $final_id ]          = array();
				$custom['options'][ $final_id ]["value"] = sanitize_text_field( $_POST['value'][ $nr ] );
				if ( isset( $_POST['price'] ) ) {
					$custom['options'][ $final_id ]["price"] = sanitize_text_field( $_POST['price'][ $nr ] );
				}
				if ( isset( $_POST['checked'][ $nr ] ) && $_POST['checked'][ $nr ] == 1 ) {
					$custom['options'][ $final_id ]['checked'] = 1;
				}
				if ( isset( $_POST['min'][ $nr ] ) ) {
					$custom['options'][ $final_id ]['min'] = intval( $_POST['min'][ $nr ] );
				}
				if ( isset( $_POST['max'][ $nr ] ) ) {
					$custom['options'][ $final_id ]['max'] = intval( $_POST['max'][ $nr ] );
				}
				if ( isset( $_POST['step'][ $nr ] ) ) {
					$custom['options'][ $final_id ]['step'] = intval( $_POST['step'][ $nr ] );
				}
				if ( isset( $_POST['label'][ $nr ] ) ) {
					$custom['options'][ $final_id ]['label'] = sanitize_text_field( $_POST['label'][ $nr ] );
				}
				if ( isset( $_POST['number-price'][ $nr ] ) && $_POST['number-price'][ $nr ] == 1 ) {
					$custom['options'][ $final_id ]['mode'] = 1;
				}
			}

			if ( isset( $_POST['if_option'] ) ) {
				foreach ( $_POST['if_option'] as $nr => $opt_id ) {
					if ( is_numeric( $opt_id ) ) {
						$opt_id = $get_id[ $opt_id ];
					}

					$option = array(
						'type'     => sanitize_key( $_POST['if_cond_type'][ $nr ] ),
						'operator' => sanitize_key( $_POST['if_cond_operator'][ $nr ] ),
						'cond'     => sanitize_key( $_POST['if_cond'][ $nr ] ),
					);

					if ( $_POST['if_cond_happens'][ $nr ] == "price" ) {
						$option['price'] = sanitize_text_field( $_POST['if_cond_amount'][ $nr ] );
					} else {
						$option['price'] = sanitize_key( $_POST['if_cond_happens'][ $nr ] );
					}

					$option['mult'] = sanitize_key( $_POST['if_cond_mult'][ $nr ] );

					$custom['options'][ $opt_id ]['clauses'][] = $option;
				}
			}
		}

		if ( isset( $_POST['custom_price_field'] ) ) {
			$custom['price'] = 1;
		}
		if ( isset( $_POST['custom_field_required'] ) ) {
			$custom['required'] = 1;
		}
		if ( isset( $_POST['custom_field_admin'] ) ) {
			$custom['admin'] = 1;
		}

		if ( isset( $_POST['custom_id'] ) ) {
			$custom_id = intval( $_POST['custom_id'] );
			ER_Admin_Notices::add_temporary_success( sprintf( __( '%s saved', 'easyReservations' ), __( 'Custom field', 'easyReservations' ) ) );
		} else {
			if ( isset( $custom_fields['id'] ) ) {
				$custom_fields['id'] = $custom_fields['id'] + 1;
			} else {
				$custom_fields['id'] = 1;
			}

			$custom_id = $custom_fields['id'];
			ER_Admin_Notices::add_temporary_success( sprintf( __( '%s added', 'easyReservations' ), __( 'Custom field', 'easyReservations' ) ) );
		}

		if ( ! isset( $custom_fields['fields'] ) ) {
			$custom_fields['fields'] = array();
		}

		$custom_fields['fields'][ $custom_id ] = $custom;
		update_option( 'reservations_custom_fields', $custom_fields );
	}
}

return new ER_Settings_Custom();
