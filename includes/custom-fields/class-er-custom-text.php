<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Text extends ER_Custom {

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
		$value = isset( $data['value'] ) ? $data['value'] : '';
		$value = is_array( $value ) ? implode( ', ', $value ) : $value;
		$class = parent::get_class( $field );

		$input = '<input type="text" ';
		$input .= 'id="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'name="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'class="input-text ' . esc_attr( $class ) . '" ';
		$input .= 'placeholder="' . esc_attr( isset( $field['value'] ) ? $field['value'] : '' ) . '" ';
		$input .= 'value="' . esc_attr( $value ) . '">';

		return $input;
	}
}