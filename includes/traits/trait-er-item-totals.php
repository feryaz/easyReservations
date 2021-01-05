<?php
/**
 * This ongoing trait will have shared calculation logic between ER_Receipt and X classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait ER_Item_Totals.
 *
 * Right now this do not have much, but plan is to eventually move all shared calculation logic between Orders and Cart in this file.
 */
trait ER_Item_Totals {

	/**
	 * Line items to calculate. Define in child class.
	 *
	 * @param string $field Field name to calculate upon.
	 *
	 * @return array having `total`|`subtotal` property.
	 */
	abstract protected function get_values_for_total( $field );

	/**
	 * Return rounded total based on settings. Will be used by Cart and Orders.
	 *
	 * @param array $values Values to round. Should be with precision.
	 *
	 * @return float|int Appropriately rounded value.
	 */
	public static function get_rounded_items_total( $values ) {
		return array_sum(
			array_map(
				array( self::class, 'round_item_subtotal' ),
				$values
			)
		);
	}

	/**
	 * Apply rounding to item subtotal before summing.
	 *
	 * @param float $value Item subtotal value.
	 *
	 * @return float
	 */
	public static function round_item_subtotal( $value ) {
		if ( ! self::round_at_subtotal() ) {
			$value = ER_Number_Util::round( $value );
		}

		return $value;
	}

	/**
	 * Should always round at subtotal?
	 *
	 * @return bool
	 */
	protected static function round_at_subtotal() {
		return 'yes' === get_option( 'reservations_tax_round_at_subtotal' );
	}

	/**
	 * Apply rounding to an array of taxes before summing. Rounds to store DP setting, ignoring precision.
	 *
	 * @param float $value Tax value.
	 * @param bool  $in_cents Whether precision of value is in cents.
	 *
	 * @return float
	 */
	protected static function round_line_tax( $value, $in_cents = true ) {
		if ( ! self::round_at_subtotal() ) {
			$value = er_round_tax_total( $value, $in_cents ? 0 : null );
		}

		return $value;
	}

}
