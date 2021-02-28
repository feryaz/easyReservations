<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ER_Tax' ) ) :

	class ER_Tax {

		protected static $tax_rates = array();

		/**
		 * Get all applicable tax rates
		 *
		 * @param int|string $type
		 *
		 * @return array
		 */
		public static function get_rates( $type = false ) {
			$tax_rates         = self::get_tax_rates();
			$matched_tax_rates = array();
			$found_priority    = array();

			if ( $tax_rates && ! empty( $tax_rates ) ) {
				foreach ( $tax_rates as $key => $rate ) {
					if ( in_array( $rate['priority'], $found_priority, true ) || ( $type && $rate['apply'] !== $type && $rate['apply'] !== 'all' && ( ! is_numeric( $type ) || $rate['apply'] !== 'resources' ) ) ) {
						continue;
					}

					$matched_tax_rates[ $key ] = $rate;
					$found_priority[]          = $rate['priority'];
				}
			}

			return apply_filters( 'easyreservations_matched_tax_rates', $matched_tax_rates );
		}

		/**
		 * Get all tax rates
		 *
		 * @return array
		 */
		public static function get_tax_rates() {
			if ( empty( self::$tax_rates ) ) {
				$tax_rates_db = get_option( 'reservations_tax_rates', array() );
				$tax_rates    = array();

				foreach ( $tax_rates_db as $rate ) {
					$id               = isset( $rate['id'] ) ? sanitize_text_field( $rate['id'] ) : '1';
					$tax_rates[ $id ] = array(
						'id'       => $id,
						'apply'    => isset( $rate['apply'] ) ? sanitize_text_field( $rate['apply'] ) : 'resources',
						'title'    => isset( $rate['title'] ) ? sanitize_text_field( $rate['title'] ) : 'Tax',
						'rate'     => isset( $rate['rate'] ) ? floatval( $rate['rate'] ) : 0,
						'flat'     => isset( $rate['flat'] ) ? intval( $rate['flat'] ) : 0,
						'compound' => isset( $rate['compound'] ) ? intval( $rate['compound'] ) : 0,
						'priority' => isset( $rate['priority'] ) ? intval( $rate['priority'] ) : 1,
					);
				}

				self::$tax_rates = $tax_rates;
			}

			return self::$tax_rates;
		}

		/**
		 * Get single tax rate
		 *
		 * @param $code
		 *
		 * @return bool|array
		 */
		public static function get_tax_rate( $code ) {
			$tax_rates = self::get_tax_rates();

			if ( isset( $tax_rates[ $code ] ) ) {
				return $tax_rates[ $code ];
			}

			return false;
		}

		/**
		 * Get rate of tax rate
		 *
		 * @param $tax_rate
		 *
		 * @return float|int
		 */
		public static function get_rate_percent_value( $tax_rate ) {
			return isset( $tax_rate['rate'] ) ? floatval( $tax_rate['rate'] ) : 0;
		}

		/**
		 * Get rate label
		 *
		 * @param $tax_rate
		 *
		 * @return float|int
		 */
		public static function get_rate_label( $tax_rate ) {
			return isset( $tax_rate['title'] ) ? $tax_rate['title'] : 0;
		}

		/**
		 * Return true/false depending on if a rate is a compound rate.
		 *
		 * @param $tax_rate
		 *
		 * @return float|int
		 */
		public static function is_compound( $tax_rate ) {
			return isset( $tax_rate['compound'] ) ? boolval( $tax_rate['compound'] ) : false;
		}

		/**
		 * Return true/false depending on if a rate has a flat value.
		 *
		 * @param $tax_rate
		 *
		 * @return float|int
		 */
		public static function is_flat( $tax_rate ) {
			return isset( $tax_rate['flat'] ) ? boolval( $tax_rate['flat'] ) : false;
		}

		/**
		 * Calculate tax for a line.
		 *
		 * @param float      $price Price to calc tax on.
		 * @param array|bool $rates Rates to apply.
		 * @param bool       $prices_include_tax
		 *
		 * @return array             Array of rates + prices after tax.
		 */
		public static function calc_tax( $price, $rates, $prices_include_tax = false ) {
			if ( $prices_include_tax ) {
				$taxes = self::calc_inclusive_tax( $price, $rates );
			} else {
				$taxes = self::calc_exclusive_tax( $price, $rates );
			}

			return apply_filters( 'easyreservations_calc_tax', $taxes, $price, $rates, $prices_include_tax );
		}

		/**
		 * Round to precision.
		 *
		 * @param float|int $in Value to round.
		 *
		 * @return float
		 */
		public static function round( $in ) {
			return apply_filters( 'easyreservations_tax_round', ER_Number_Util::round( $in, er_get_rounding_precision() ), $in );
		}

		/**
		 * Calc tax from inclusive price.
		 *
		 * @param float      $price Price to calculate tax for.
		 * @param array|bool $rates Array of tax rates.
		 *
		 * @return array
		 */
		public static function calc_inclusive_tax( $price, $rates ) {
			$taxes          = array();
			$compound_rates = array();
			$regular_rates  = array();

			// Index array so taxes are output in correct order and see what compound/regular rates we have to calculate.
			foreach ( $rates as $key => $rate ) {
				$taxes[ $key ] = 0;

				if ( isset( $rate['compound'] ) ) {
					$compound_rates[ $key ] = $rate['rate'];
				} elseif ( isset( $rate['flat'] ) ) {
					$taxes[ $key ] += $rate['flat'];
				} else {
					$regular_rates[ $key ] = $rate['rate'];
				}
			}

			$compound_rates = array_reverse( $compound_rates, true ); // Working backwards.

			$non_compound_price = $price;

			foreach ( $compound_rates as $key => $compound_rate ) {
				$tax_amount         = apply_filters(
					'easyreservations_price_inc_tax_amount',
					$non_compound_price - ( $non_compound_price / ( 1 + ( $compound_rate / 100 ) ) ), $key,
					$rates[ $key ], $price
				);
				$taxes[ $key ]      += $tax_amount;
				$non_compound_price = $non_compound_price - $tax_amount;
			}

			// Regular taxes.
			$regular_tax_rate = 1 + ( array_sum( $regular_rates ) / 100 );

			foreach ( $regular_rates as $key => $regular_rate ) {
				$the_rate      = ( $regular_rate / 100 ) / $regular_tax_rate;
				$net_price     = $price - ( $the_rate * $non_compound_price );
				$tax_amount    = apply_filters(
					'easyreservations_price_inc_tax_amount', $price - $net_price, $key, $rates[ $key ], $price
				);
				$taxes[ $key ] += $tax_amount;
			}

			/**
			 * Round all taxes to precision (4DP) before passing them back. Note, this is not the same rounding
			 * as in the cart calculation class which, depending on settings, will round to 2DP when calculating
			 * final totals. Also unlike that class, this rounds .5 up for all cases.
			 */
			$taxes = array_map( array( __CLASS__, 'round' ), $taxes );

			return $taxes;
		}

		/**
		 * Calc tax from exclusive price.
		 *
		 * @param float $price Price to calculate tax for.
		 * @param array $rates Array of tax rates.
		 *
		 * @return array
		 */
		public static function calc_exclusive_tax( $price, $rates ) {
			$taxes = array();

			if ( ! empty( $rates ) ) {
				foreach ( $rates as $key => $rate ) {
					if ( isset( $rate['compound'] ) ) {
						continue;
					}

					if ( isset( $rate['flat'] ) ) {
						$tax_amount = $price * ( $rate['rate'] / 100 );
					} else {
						$tax_amount = $rate['rate'];
					}
					$tax_amount = apply_filters(
						'easyreservations_price_ex_tax_amount', $tax_amount, $key, $rate, $price
					); // ADVANCED: Allow third parties to modify this rate.

					if ( ! isset( $taxes[ $key ] ) ) {
						$taxes[ $key ] = $tax_amount;
					} else {
						$taxes[ $key ] += $tax_amount;
					}
				}

				$pre_compound_total = array_sum( $taxes );

				// Compound taxes.
				foreach ( $rates as $key => $rate ) {
					if ( ! isset( $rate['compound'] ) ) {
						continue;
					}
					$the_price_inc_tax = $price + ( $pre_compound_total );
					$tax_amount        = $the_price_inc_tax * ( $rate['rate'] / 100 );
					$tax_amount        = apply_filters(
						'easyreservations_price_ex_tax_amount', $tax_amount, $key, $rate, $price, $the_price_inc_tax,
						$pre_compound_total
					); // ADVANCED: Allow third parties to modify this rate.

					if ( ! isset( $taxes[ $key ] ) ) {
						$taxes[ $key ] = $tax_amount;
					} else {
						$taxes[ $key ] += $tax_amount;
					}

					$pre_compound_total = array_sum( $taxes );
				}
			}

			/**
			 * Round all taxes to precision (4DP) before passing them back. Note, this is not the same rounding
			 * as in the cart calculation class which, depending on settings, will round to 2DP when calculating
			 * final totals. Also unlike that class, this rounds .5 up for all cases.
			 */
			$taxes = array_map( array( __CLASS__, 'round' ), $taxes );

			return $taxes;
		}

	}

endif;