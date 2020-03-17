<?php
defined( 'ABSPATH' ) || exit;

/**
 * ER_Settings_General.
 */
class ER_Settings_Form extends ER_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'form';
		$this->label = __( 'Form', 'easyReservations' );

		add_action( 'easyreservations_no_settings_to_save', array( $this, 'delete_form_template' ) );

		parent::__construct();
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $hide_save_button;
		$hide_save_button = 'yes';

		include 'views/html-admin-settings-form.php';
	}

	/**
	 * Save settings.
	 */
	public function delete_form_template() {
		if ( isset( $_GET["delete-form"] ) && check_admin_referer( 'easy-delete-form' ) ) {
			$delete = sanitize_key( $_GET["delete-form"] );

			delete_option( 'reservations_form_' . $delete );
			ER_Admin_Notices::add_temporary_success( sprintf( __( '%s deleted', 'easyReservations' ), sprintf( __( 'Form %s', 'easyReservations' ), '<b>' . $delete . '</b>' ) ) );
		} elseif ( isset( $_POST["form_name"] ) && check_admin_referer( 'easy-add-form' ) ) {
			if ( ! empty( $_POST["form_name"] ) ) {
				$string      = sanitize_text_field( $_POST["form_name"] );
				$option_name = 'reservations_form_' . er_sanitize_form_id( $string );

				if ( ! get_option( $option_name ) ) {
					add_option( $option_name, ' ', '', 'no' );
				} elseif ( ! get_option( $option_name . '_1' ) ) {
					add_option( $option_name . '_1', ' ', '', 'no' );
				} else {
					add_option( $option_name . '_2', ' ', '', 'no' );
				}

				ER_Admin_Notices::add_temporary_success( sprintf( __( '%s added', 'easyReservations' ), sprintf( __( 'Form %s', 'easyReservations' ), '<b>' . $string . '</b>' ) ) );
			} else {
				ER_Admin_Notices::add_temporary_error( sprintf( __( 'Please enter a name', 'easyReservations' ) ) );
			}
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		if ( isset( $_POST['reservations_form_content'] ) ) {
			$explode     = array();
			$option_name = isset( $_GET["form"] ) ? sanitize_key( $_GET["form"] ) : '';

			foreach ( explode( "<br>", $_POST['reservations_form_content'] ) as $v ) {
				$explode[] = str_replace( '<br>', "<br>\r\n", sanitize_textarea_field( $v ) );
			}

			$form_content = implode( "<br>\r\n", $explode );
			$form_content = str_replace( array( '<br>', '</formtag>' ), array( "\n", '' ), $form_content );
			$form_content = preg_replace( '/<formtag.*?>/', '', $form_content );
			$form_content = html_entity_decode( $form_content );
			$form_content = preg_replace( '/(<(font|style)\b[^>]*>).*?(<\/\2>)/is', '', $form_content );

			if ( empty( $option_name ) ) {
				update_option( 'reservations_form', $form_content );
			} else {
				update_option( 'reservations_form_' . $option_name, $form_content );
			}

			ER_Admin_Notices::add_temporary_success( sprintf( __( '%s saved', 'easyReservations' ), sprintf( __( 'Form %s', 'easyReservations' ), '<b>' . $option_name ? $option_name : __( 'default', 'easyReservations' ) . '</b>' ) ) );
		}
	}
}

return new ER_Settings_Form();
