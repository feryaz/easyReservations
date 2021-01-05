<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class ER_Custom_Options extends ER_Custom {

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
		$value = self::get_value( $id, $field, $value );

		if ( isset( $field['options'], $field['options'][ $value ] ) ) {
			$value = $field['options'][ $value ]['value'];
		}

		return $value;
	}
}