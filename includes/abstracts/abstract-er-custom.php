<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class ER_Custom {

	/**
	 * Get the html form element
	 *
	 * @param int   $id
	 * @param array $field
	 * @param array $data
	 *
	 * @return string
	 */
	public static function get_form_field( $id, $field, $data ) {
		return '';
	}

	/**
	 * Get css class for input
	 *
	 * @param array $field
	 *
	 * @return string
	 */
	public static function get_class( $field ) {
		if ( isset( $field['price'] ) ) {
			return 'validate';
		}

		return '';
	}

	/**
	 * Validate field
	 *
	 * @param WP_Error[] $errors
	 * @param int        $id
	 * @param array      $field
	 * @param null|mixed $value
	 *
	 * @return WP_Error[] mixed
	 */
	public static function validate( $errors, $id, $field, $value = null ) {
		return $errors;
	}

	/**
	 * Calculate amount to calculate price with
	 *
	 * @param int   $id
	 * @param array $field
	 *
	 * @return float|array
	 */
	public static function get_amount( $id, $field ) {
		return 0;
	}

	/**
	 * Get value
	 *
	 * @param int   $id
	 * @param array $field
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public static function get_value( $id, $field, $value = false ) {
		return sanitize_text_field( $value ? $value : $_POST[ 'er-custom-' . $id ] );
	}

	/**
	 * Get value to display
	 *
	 * @param int   $id
	 * @param array $field
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public static function get_display_value( $id, $field, $value = false ) {
		return self::get_value( $id, $field, $value );
	}
}
