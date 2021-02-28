<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Area extends ER_Custom {

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
		return sanitize_textarea_field( $value ? $value : $_POST[ 'er-custom-' . $id ] );
	}

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
		$class = parent::get_class( $field );

		$input = '<textarea ';
		$input .= 'id="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'name="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'class="' . esc_attr( $class ) . '" ';
		$input .= 'placeholder="' . esc_attr( isset( $field['value'] ) ? $field['value'] : '' ) . '">';
		$input .= esc_textarea( isset( $data['value'] ) ? $data['value'] : '' );
		$input .= '</textarea>';

		return $input;
	}
}