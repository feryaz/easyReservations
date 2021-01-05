<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Select extends ER_Custom_Options {

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
		$class = parent::get_class( $field );

		$input = '<select ';
		$input .= 'id="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'name="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'class="' . esc_attr( $class ) . ' default-disabled" ';
		$input .= '>';

		if ( isset( $field['options'] ) ) {
			foreach ( $field['options'] as $key => $option ) {
				$input .= '<option value="' . esc_attr( $key ) . '" ' . selected( $value === $key || ( empty( $value ) && isset( $option['checked'] ) ), true, false ) . '>' . esc_html( $option['value'] ) . '</option>';
			}
		}

		$input .= '</select>';

		return $input;
	}
}