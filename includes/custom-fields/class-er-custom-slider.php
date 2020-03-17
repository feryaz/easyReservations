<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Slider extends ER_Custom {

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
		$input = '<input type="hidden" class="easy-slider-input ' . esc_attr( $class ) . '" ';
		$input .= 'id="er-custom-' . esc_attr( $id ) . '" ';
		$input .= 'name="er-custom-' . esc_attr( $id ) . '" ';

		if ( isset( $field['options'] ) ) {
			foreach ( $field['options'] as $option ) {

				if ( isset( $option['min'] ) ) {
					$input .= 'data-min="' . esc_attr( $option['min'] ) . '" ';
				}

				if ( ! empty( $option['max'] ) ) {
					$input .= 'data-max="' . esc_attr( $option['max'] ) . '" ';
				}

				if ( ! empty( $option['step'] ) ) {
					$input .= 'data-step="' . esc_attr( $option['step'] ) . '" ';
				}

				if ( ! empty( $option['label'] ) ) {
					$input .= 'data-label="' . esc_attr( $option['label'] ) . '" ';
				}
			}
		}

		$input .= 'value="' . esc_attr( isset( $data['value'] ) ? $data['value'] : '' ) . '">';
		$input .= '<span class="easy-slider-label-' . $id . '"></span>';

		return $input;
	}
}