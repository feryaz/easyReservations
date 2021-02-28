<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Number extends ER_Custom {

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

		$input = '<input type="number" ';
		$input .= 'id="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'name="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'class="' . esc_attr( $class ) . '" ';

		if ( isset( $field['options'] ) ) {
			foreach ( $field['options'] as $option ) {

				if ( isset( $option['min'] ) ) {
					$input .= 'min="' . esc_attr( $option['min'] ) . '" ';
				}

				if ( ! empty( $option['max'] ) ) {
					$input .= 'max="' . esc_attr( $option['max'] ) . '" ';
				}

				if ( ! empty( $option['step'] ) ) {
					$input .= 'step="' . esc_attr( $option['step'] ) . '" ';
				}
			}
		}

		$input .= 'placeholder="' . esc_attr( isset( $field['value'] ) ? $field['value'] : '' ) . '" ';
		$input .= 'value="' . esc_attr( isset( $data['value'] ) ? $data['value'] : '' ) . '">';

		return $input;
	}
}