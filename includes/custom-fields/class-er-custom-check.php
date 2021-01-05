<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Custom_Check extends ER_Custom {

	/**
	 * Get value
	 *
	 * @param int   $id
	 * @param array $field
	 * @param mixed $value
	 *
	 * @return float|int|array
	 */
	public static function get_value( $id, $field, $value = false ) {
		return er_clean( $value ? $value : $_POST[ 'er-custom-' . $id ] );
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
		$value  = self::get_value( $id, $field, $value );
		$return = '';

		if ( is_array( $value ) ) {
			foreach ( $value as $option_name ) {
				if ( isset( $field['options'], $field['options'][ $option_name ] ) ) {
					$return .= $field['options'][ $option_name ]['value'] . ', ';
				}
			}

			$return = substr( $return, 0, - 2 );
		} else {
			if ( isset( $field['options'], $field['options'][ $value ] ) ) {
				$return = $field['options'][ $value ]['value'];
			}
		}

		return $return;
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
		$values = self::get_value( $id, $field );
		$return = array();

		if ( ! is_array( $values ) ) {
			$values = array( $values );
		}

		foreach ( $values as $option_name ) {
			if ( isset( $field['options'], $field['options'][ $option_name ] ) ) {
				$return[] = floatval( $field['options'][ $option_name ]['price'] );
			}
		}

		return count( $return ) === 1 ? $return[0] : $return;
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
		$input = '';
		$value = isset( $data['value'] ) ? $data['value'] : '';
		$class = parent::get_class( $field );

		if ( isset( $field['options'] ) ) {
			foreach ( $field['options'] as $key => $option ) {
				$checked = checked( $value === $key || ( is_array( $value ) && in_array( $key, $value ) ) || ( empty( $value ) && isset( $option['checked'] ) ), true, false );

				$input .= '<label for="er-custom-' . esc_attr( $id . '-' . $key ) . '">';

				$input .= '<input type="checkbox" ';
				$input .= 'id="er-custom-' . esc_attr( $id . '-' . $key ) . '" ';
				$input .= 'name="er-custom-' . esc_attr( $id ) . '[]" ';
				$input .= 'class="' . esc_attr( $class ) . '" ';
				$input .= 'value="' . esc_attr( $key ) . '" ';
				$input .= $checked . '>';

				$input .= esc_html( $option['value'] );

				$input .= '</label>';
			}
		}

		return $input;
	}
}