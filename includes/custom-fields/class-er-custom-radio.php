<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Radio extends ER_Custom_Options {

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
		$input = '';
		$value = isset( $data['value'] ) ? $data['value'] : '';
		$class = parent::get_class( $field );

		if ( isset( $field['options'] ) ) {
			foreach ( $field['options'] as $key => $option ) {
				$input .= '<label for="er-custom-' . esc_attr( $id . '-' . $key ) . '">';

				$input .= '<input type="radio" ';
				$input .= 'id="er-custom-' . esc_attr( $id . '-' . $key ) . '" ';
				$input .= 'name="er-custom-' . esc_attr( $id ) . '" ';
				$input .= 'value="' . esc_attr( $key ) . '" ';
				$input .= 'class="' . esc_attr( $class ) . '" ';
				$input .= checked( $value === $key || ( empty( $value ) && isset( $option['checked'] ) ), true, false );
				$input .= '>';

				$input .= esc_html( $option['value'] );

				$input .= '</label>';
			}
		}

		$input .= '</select>';

		return $input;
	}
}