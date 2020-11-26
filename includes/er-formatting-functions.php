<?php
defined( 'ABSPATH' ) || exit;

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * @param string|bool $string String to convert.
 *
 * @return bool
 */
function er_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

/**
 * Converts a bool to a 'yes' or 'no'.
 *
 * @param string|bool $bool String to convert.
 *
 * @return string
 */
function er_bool_to_string( $bool ) {
	if ( ! is_bool( $bool ) ) {
		$bool = er_string_to_bool( $bool );
	}

	return true === $bool ? 'yes' : 'no';
}

/**
 * Sanitize permalink values before insertion into DB.
 *
 * Cannot use er_clean because it sometimes strips % chars and breaks the user's setting.
 *
 * @param string $value Permalink.
 *
 * @return string
 */
function er_sanitize_permalink( $value ) {
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );

	return untrailingslashit( $value );
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param string|array $var Data to sanitize.
 *
 * @return string|array
 */
function er_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'er_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Function wp_check_invalid_utf8 with recursive array support.
 *
 * @param string|array $var Data to sanitize.
 *
 * @return string|array
 */
function er_check_invalid_utf8( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'er_check_invalid_utf8', $var );
	} else {
		return wp_check_invalid_utf8( $var );
	}
}

/**
 * Wrapper for mb_strtoupper which see's if supported first.
 *
 * @param string $string String to format.
 *
 * @return string
 */
function er_strtoupper( $string ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $string ) : strtoupper( $string );
}

/**
 * Make a string lowercase.
 * Try to use mb_strtolower() when available.
 *
 * @param string $string String to format.
 *
 * @return string
 */
function er_strtolower( $string ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string ) : strtolower( $string );
}

/**
 * Trim a string and append a suffix.
 *
 * @param string  $string String to trim.
 * @param integer $chars Amount of characters.
 *                         Defaults to 200.
 * @param string  $suffix Suffix.
 *                         Defaults to '...'.
 *
 * @return string
 */
function er_trim_string( $string, $chars = 200, $suffix = '...' ) {
	if ( strlen( $string ) > $chars ) {
		if ( function_exists( 'mb_substr' ) ) {
			$string = mb_substr( $string, 0, ( $chars - mb_strlen( $suffix ) ) ) . $suffix;
		} else {
			$string = substr( $string, 0, ( $chars - strlen( $suffix ) ) ) . $suffix;
		}
	}

	return $string;
}

/**
 * Repair incorrect input and checks if string can be interpreted as a price
 *
 * @param string $price a string to check
 *
 * @return string or bool if not correct
 */
function er_sanitize_price( $price ) {
	$new = str_replace( ",", ".", $price );

	return preg_match( "/^[\-]{0,1}[0-9]+[\.]?[0-9]*$/", $new ) ? $new : false;
}

/**
 * Trim trailing zeros off prices.
 *
 * @param string|float|int $price Price.
 *
 * @return string
 */
function er_trim_zeros( $price ) {
	return preg_replace( '/' . preg_quote( er_get_price_decimal_separator(), '/' ) . '0++$/', '', $price );
}

/**
 * Sanitize a string destined to be a tooltip.
 * Tooltips are encoded with htmlspecialchars to prevent XSS. Should not be used in conjunction with esc_attr()
 *
 * @param string $var Data to sanitize.
 *
 * @return string
 */
function er_sanitize_tooltip( $var ) {
	return htmlspecialchars(
		wp_kses(
			html_entity_decode( $var ),
			array(
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'small'  => array(),
				'span'   => array(),
				'ul'     => array(),
				'li'     => array(),
				'ol'     => array(),
				'p'      => array(),
			)
		)
	);
}

/**
 * Merge two arrays.
 *
 * @param array $a1 First array to merge.
 * @param array $a2 Second array to merge.
 *
 * @return array
 */
function er_array_overlay( $a1, $a2 ) {
	foreach ( $a1 as $k => $v ) {
		if ( ! array_key_exists( $k, $a2 ) ) {
			continue;
		}
		if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
			$a1[ $k ] = er_array_overlay( $v, $a2[ $k ] );
		} else {
			$a1[ $k ] = $a2[ $k ];
		}
	}

	return $a1;
}

/**
 * Notation to numbers.
 *
 * This function transforms the php.ini notation for numbers (like '2M') to an integer.
 *
 * @param string $size Size value.
 *
 * @return int
 */
function er_let_to_num( $size ) {
	$l   = substr( $size, - 1 );
	$ret = (int) substr( $size, 0, - 1 );
	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
		// No break.
		case 'T':
			$ret *= 1024;
		// No break.
		case 'G':
			$ret *= 1024;
		// No break.
		case 'M':
			$ret *= 1024;
		// No break.
		case 'K':
			$ret *= 1024;
		// No break.
	}

	return $ret;
}

/**
 * Format content to display shortcodes.
 *
 * @param string $raw_string Raw string.
 *
 * @return string
 */
function er_format_content( $raw_string ) {
	return apply_filters( 'easyreservations_format_content', apply_filters( 'easyreservations_short_description', $raw_string ), $raw_string );
}

/**
 * Process oEmbeds.
 *
 * @param string $content Content.
 *
 * @return string
 */
function er_do_oembeds( $content ) {
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );

	return $content;
}

/**
 * Array merge and sum function.
 *
 * Source:  https://gist.github.com/Nickology/f700e319cbafab5eaedc
 *
 * @return array
 */
function er_array_merge_recursive_numeric() {
	$arrays = func_get_args();

	// If there's only one array, it's already merged.
	if ( 1 === count( $arrays ) ) {
		return $arrays[0];
	}

	// Remove any items in $arrays that are NOT arrays.
	foreach ( $arrays as $key => $array ) {
		if ( ! is_array( $array ) ) {
			unset( $arrays[ $key ] );
		}
	}

	// We start by setting the first array as our final array.
	// We will merge all other arrays with this one.
	$final = array_shift( $arrays );

	foreach ( $arrays as $b ) {
		foreach ( $final as $key => $value ) {
			// If $key does not exist in $b, then it is unique and can be safely merged.
			if ( ! isset( $b[ $key ] ) ) {
				$final[ $key ] = $value;
			} else {
				// If $key is present in $b, then we need to merge and sum numeric values in both.
				if ( is_numeric( $value ) && is_numeric( $b[ $key ] ) ) {
					// If both values for these keys are numeric, we sum them.
					$final[ $key ] = $value + $b[ $key ];
				} elseif ( is_array( $value ) && is_array( $b[ $key ] ) ) {
					// If both values are arrays, we recursively call ourself.
					$final[ $key ] = er_array_merge_recursive_numeric( $value, $b[ $key ] );
				} else {
					// If both keys exist but differ in type, then we cannot merge them.
					// In this scenario, we will $b's value for $key is used.
					$final[ $key ] = $b[ $key ];
				}
			}
		}

		// Finally, we need to merge any keys that exist only in $b.
		foreach ( $b as $key => $value ) {
			if ( ! isset( $final[ $key ] ) ) {
				$final[ $key ] = $value;
			}
		}
	}

	return $final;
}

/**
 * Convert a float to a string without locale formatting which PHP adds when changing floats to strings.
 *
 * @param float $float Float value to format.
 *
 * @return string
 */
function er_float_to_string( $float ) {
	if ( ! is_float( $float ) ) {
		return $float;
	}

	$locale = localeconv();
	$string = strval( $float );
	$string = str_replace( $locale['decimal_point'], '.', $string );

	return $string;
}

/**
 * Make a refund total negative.
 *
 * @param float $amount Refunded amount.
 *
 * @return float
 */
function er_format_refund_total( $amount ) {
	return $amount * - 1;
}

/**
 * Format decimal numbers ready for DB storage.
 *
 * Sanitize, remove decimals, and optionally round + trim off zeros.
 *
 * This function does not remove thousands - this should be done before passing a value to the function.
 *
 * @param float|string $number Expects either a float or a string with a decimal separator only (no thousands).
 * @param mixed        $dp number  Number of decimal points to use, blank to use easyreservations_price_num_decimals, or false to avoid all rounding.
 * @param bool         $trim_zeros From end of string.
 *
 * @return string
 */
function er_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	$locale   = localeconv();
	$decimals = array( er_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'] );

	// Remove locale from string.
	if ( ! is_float( $number ) ) {
		$number = str_replace( $decimals, '.', $number );
		// Convert multiple dots to just one.
		$number = preg_replace( '/\.(?![^.]+$)|[^0-9.-]/', '', er_clean( $number ) );
	}

	if ( false !== $dp ) {
		$dp     = intval( '' === $dp ? er_get_price_decimals() : $dp );
		$number = number_format( floatval( $number ), $dp, '.', '' );
	} elseif ( is_float( $number ) ) {
		// DP is false - don't use number format, just return a string using whatever is given. Remove scientific notation using sprintf.
		$number = str_replace( $decimals, '.', sprintf( '%.' . er_get_rounding_precision() . 'f', $number ) );
		// We already had a float, so trailing zeros are not needed.
		$trim_zeros = true;
	}

	if ( $trim_zeros && strstr( $number, '.' ) ) {
		$number = rtrim( rtrim( $number, '0' ), '.' );
	}

	return $number;
}

/**
 * Format price into currency string
 *
 * @param float|int $amount amount of money to format
 * @param bool      $currency_symbol
 * @param bool      $ex_tax_label
 *
 * @return string
 */
function er_price( $amount = 0, $currency_symbol = false, $ex_tax_label = false ) {
	$decimals           = er_get_price_decimals();
	$thousand_separator = er_get_price_thousand_separator();
	$decimal_separator  = er_get_price_decimal_separator();
	$price_format       = er_get_price_format();

	$unformatted_price = $amount;
	$negative          = $amount < 0;

	$price = apply_filters( 'raw_easyreservations_price', floatval( $negative ? $amount * - 1 : $amount ) );
	$price = apply_filters( 'formatted_easyreservations_price', number_format( $price, $decimals, $decimal_separator, $thousand_separator ), $price, $decimals, $decimal_separator, $thousand_separator );

	if ( apply_filters( 'easyreservations_price_trim_zeros', false ) && $decimals > 0 ) {
		$price = er_trim_zeros( $price );
	}

	$formatted_price = $negative ? '-' : '';

	if ( $currency_symbol ) {
		$formatted_price .= sprintf( $price_format, '<span class="easyreservations-Price-currencySymbol"><bdi>' . er_get_currency_symbol() . '</bdi></span>', esc_html( $price ) );
	} else {
		$formatted_price .= esc_html( $price );
	}

	$return = '<span class="easyreservations-Price-amount amount">' . $formatted_price . '</span>';

	if ( $ex_tax_label && er_tax_enabled() ) {
		$return .= ' <small class="easyreservations-Price-taxLabel tax_label">' . ER()->countries->ex_tax_or_vat() . '</small>';
	}

	return apply_filters( 'easyreservations_price', $return, $price, $unformatted_price );
}

/**
 * Round a tax amount.
 *
 * @param double $value Amount to round.
 * @param int    $precision DP to round. Defaults to er_get_price_decimals.
 *
 * @return float
 */
function er_round_tax_total( $value, $precision = null ) {
	$precision = is_null( $precision ) ? er_get_price_decimals() : intval( $precision );

	$tax_rounding = er_prices_include_tax() ? 2 : 1;

	$rounded_tax = ER_Number_Util::round( $value, $precision, $tax_rounding ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.round_modeFound

	return apply_filters( 'er_round_tax_total', $rounded_tax, $value, $precision, $tax_rounding );
}

/**
 * Format a price with ER Currency Locale settings.
 *
 * @param string $value Price to localize.
 *
 * @return string
 */
function er_format_localized_price( $value ) {
	return apply_filters( 'easyreservations_format_localized_price', str_replace( '.', er_get_price_decimal_separator(), strval( $value ) ), $value );
}

/**
 * Format a decimal with PHP Locale settings.
 *
 * @param string $value Decimal to localize.
 *
 * @return string
 */
function er_format_localized_decimal( $value ) {
	$locale = localeconv();

	return apply_filters( 'easyreservations_format_localized_decimal', str_replace( '.', $locale['decimal_point'], strval( $value ) ), $value );
}

/**
 * Convert RGB to HEX.
 *
 * @param mixed $color Color.
 *
 * @return array
 */
function er_rgb_from_hex( $color ) {
	if ( empty( $color ) ) {
		return array( 'R' => 255, 'G' => 255, 'B' => 255 );
	}

	$color = str_replace( '#', '', $color );
	// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF".
	$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

	$rgb      = array();
	$rgb['R'] = hexdec( $color[0] . $color[1] );
	$rgb['G'] = hexdec( $color[2] . $color[3] );
	$rgb['B'] = hexdec( $color[4] . $color[5] );

	return $rgb;
}

/**
 * Make HEX color darker.
 *
 * @param mixed $color Color.
 * @param int   $factor Darker factor.
 *                      Defaults to 30.
 *
 * @return string
 */
function er_hex_darker( $color, $factor = 30 ) {
	$base  = er_rgb_from_hex( $color );
	$color = '#';

	foreach ( $base as $k => $v ) {
		$amount      = $v / 100;
		$amount      = ER_Number_Util::round( $amount * $factor );
		$new_decimal = $v - $amount;

		$new_hex_component = dechex( $new_decimal );
		if ( strlen( $new_hex_component ) < 2 ) {
			$new_hex_component = '0' . $new_hex_component;
		}
		$color .= $new_hex_component;
	}

	return $color;
}

/**
 * Make HEX color lighter.
 *
 * @param mixed $color Color.
 * @param int   $factor Lighter factor.
 *                      Defaults to 30.
 *
 * @return string
 */
function er_hex_lighter( $color, $factor = 30 ) {
	$base  = er_rgb_from_hex( $color );
	$color = '#';

	foreach ( $base as $k => $v ) {
		$amount      = 255 - $v;
		$amount      = $amount / 100;
		$amount      = ER_Number_Util::round( $amount * $factor );
		$new_decimal = $v + $amount;

		$new_hex_component = dechex( $new_decimal );
		if ( strlen( $new_hex_component ) < 2 ) {
			$new_hex_component = '0' . $new_hex_component;
		}
		$color .= $new_hex_component;
	}

	return $color;
}

/**
 * Determine whether a hex color is light.
 *
 * @param mixed $color Color.
 *
 * @return bool  True if a light color.
 */
function er_hex_is_light( $color ) {
	$hex = str_replace( '#', '', $color );

	$c_r = hexdec( substr( $hex, 0, 2 ) );
	$c_g = hexdec( substr( $hex, 2, 2 ) );
	$c_b = hexdec( substr( $hex, 4, 2 ) );

	$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

	return $brightness > 155;
}

/**
 * Detect if we should use a light or dark color on a background color.
 *
 * @param mixed  $color Color.
 * @param string $dark Darkest reference.
 *                      Defaults to '#000000'.
 * @param string $light Lightest reference.
 *                      Defaults to '#FFFFFF'.
 *
 * @return string
 */
function er_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
	return er_hex_is_light( $color ) ? $dark : $light;
}

/**
 * Format the postcode according to the country and length of the postcode.
 *
 * @param string $postcode Unformatted postcode.
 * @param string $country Base country.
 *
 * @return string
 */
function er_format_postcode( $postcode, $country ) {
	$postcode = er_normalize_postcode( $postcode );

	switch ( $country ) {
		case 'CA':
		case 'GB':
			$postcode = substr_replace( $postcode, ' ', - 3, 0 );
			break;
		case 'IE':
			$postcode = substr_replace( $postcode, ' ', 3, 0 );
			break;
		case 'BR':
		case 'PL':
			$postcode = substr_replace( $postcode, '-', - 3, 0 );
			break;
		case 'JP':
			$postcode = substr_replace( $postcode, '-', 3, 0 );
			break;
		case 'PT':
			$postcode = substr_replace( $postcode, '-', 4, 0 );
			break;
		case 'US':
			$postcode = rtrim( substr_replace( $postcode, '-', 5, 0 ), '-' );
			break;
		case 'NL':
			$postcode = substr_replace( $postcode, ' ', 4, 0 );
			break;
	}

	return apply_filters( 'easyreservations_format_postcode', trim( $postcode ), $country );
}

/**
 * Normalize postcodes.
 *
 * Remove spaces and convert characters to uppercase.
 *
 * @param string $postcode Postcode.
 *
 * @return string
 */
function er_normalize_postcode( $postcode ) {
	return preg_replace( '/[\s\-]/', '', trim( er_strtoupper( $postcode ) ) );
}

/**
 * Format phone numbers.
 *
 * @param string $phone Phone number.
 *
 * @return string
 */
function er_format_phone_number( $phone ) {
	if ( ! ER_Validation::is_phone( $phone ) ) {
		return '';
	}

	return preg_replace( '/[^0-9\+\-\(\)\s]/', '-', preg_replace( '/[\x00-\x1F\x7F-\xFF]/', '', $phone ) );
}

/**
 * Sanitize phone number.
 * Allows only numbers and "+" (plus sign).
 *
 * @param string $phone Phone number.
 *
 * @return string
 */
function er_sanitize_phone_number( $phone ) {
	return preg_replace( '/[^\d+]/', '', $phone );
}

/**
 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime.
 *
 * Based on wcs_strtotime_dark_knight() from ER Subscriptions by Prospress.
 *
 * @param string   $time_string Time string.
 * @param int|null $from_timestamp Timestamp to convert from.
 *
 * @return int
 */
function er_string_to_timestamp( $time_string, $from_timestamp = null ) {
	$original_timezone = date_default_timezone_get();

	// @codingStandardsIgnoreStart
	date_default_timezone_set( 'UTC' );

	if ( null === $from_timestamp ) {
		$next_timestamp = strtotime( $time_string );
	} else {
		$next_timestamp = strtotime( $time_string, $from_timestamp );
	}

	date_default_timezone_set( $original_timezone );

	// @codingStandardsIgnoreEnd

	return $next_timestamp;
}

/**
 * Convert a date string to a ER_DateTime.
 *
 * @param string $time_string Time string.
 *
 * @return ER_DateTime
 */
function er_string_to_datetime( $time_string ) {
	// Strings are defined in local WP timezone. Convert to UTC.
	if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $time_string, $date_bits ) ) {
		$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : er_timezone_offset();
		$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
	} else {
		$timestamp = er_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', er_string_to_timestamp( $time_string ) ) ) );
	}
	$datetime = new ER_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

	// Set local timezone or offset.
	if ( get_option( 'timezone_string' ) ) {
		$datetime->setTimezone( new DateTimeZone( er_timezone_string() ) );
	} else {
		$datetime->set_utc_offset( er_timezone_offset() );
	}

	return $datetime;
}

/**
 * easyReservations Timezone - helper to retrieve the timezone string for a site until.
 * a WP core method exists (see https://core.trac.wordpress.org/ticket/24730).
 *
 * Adapted from https://secure.php.net/manual/en/function.timezone-name-from-abbr.php#89155.
 *
 * @return string PHP timezone string for the site
 */
function er_timezone_string() {
	// Added in WordPress 5.3 Ref https://developer.wordpress.org/reference/functions/wp_timezone_string/.
	if ( function_exists( 'wp_timezone_string' ) ) {
		return wp_timezone_string();
	}

	// If site timezone string exists, return it.
	$timezone = get_option( 'timezone_string' );
	if ( $timezone ) {
		return $timezone;
	}

	// Get UTC offset, if it isn't set then return UTC.
	$utc_offset = floatval( get_option( 'gmt_offset', 0 ) );
	if ( ! is_numeric( $utc_offset ) || 0.0 === $utc_offset ) {
		return 'UTC';
	}

	// Adjust UTC offset from hours to seconds.
	$utc_offset = (int) ( $utc_offset * 3600 );

	// Attempt to guess the timezone string from the UTC offset.
	$timezone = timezone_name_from_abbr( '', $utc_offset );
	if ( $timezone ) {
		return $timezone;
	}

	// Last try, guess timezone string manually.
	foreach ( timezone_abbreviations_list() as $abbr ) {
		foreach ( $abbr as $city ) {
			// WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone.
			if ( (bool) date( 'I' ) === (bool) $city['dst'] && $city['timezone_id'] && intval( $city['offset'] ) === $utc_offset ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				return $city['timezone_id'];
			}
		}
	}

	// Fallback to UTC.
	return 'UTC';
}

/**
 * Get timezone offset in seconds.
 *
 * @return float
 */
function er_timezone_offset() {
	$timezone = get_option( 'timezone_string' );

	if ( $timezone ) {
		$timezone_object = new DateTimeZone( $timezone );

		return $timezone_object->getOffset( new DateTime( 'now' ) );
	} else {
		return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
	}
}

/**
 * Format a date for output.
 *
 * @param ER_DateTime $date Instance of ER_DateTime.
 * @param string      $format Data format.
 *                             Defaults to the er_date_format function if not set.
 *
 * @return string
 */
function er_format_datetime( $date, $format = '' ) {
	if ( ! $format ) {
		$format = er_date_format();
	}

	if ( ! is_a( $date, 'ER_DateTime' ) ) {
		return '';
	}

	return $date->date_i18n( $format );
}

/**
 * Callback which can flatten post meta (gets the first value if it's an array).
 *
 * @param array $value Value to flatten.
 *
 * @return mixed
 */
function er_flatten_meta_callback( $value ) {
	return is_array( $value ) ? current( $value ) : $value;
}

/**
 * Implode and escape HTML attributes for output.
 *
 * @param array $raw_attributes Attribute name value pairs.
 *
 * @return string
 */
function er_implode_html_attributes( $raw_attributes ) {
	$attributes = array();
	foreach ( $raw_attributes as $name => $value ) {
		$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}

	return implode( ' ', $attributes );
}

/**
 * Escape JSON for use on HTML or attribute text nodes.
 *
 * @param string $json JSON to escape.
 * @param bool   $html True if escaping for HTML text node, false for attributes. Determines how quotes are handled.
 *
 * @return string Escaped JSON.
 */
function er_esc_json( $json, $html = false ) {
	return _wp_specialchars(
		$json,
		$html ? ENT_NOQUOTES : ENT_QUOTES, // Escape quotes in attribute nodes only.
		'UTF-8',                           // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
		true                               // Double escape entities: `&amp;` -> `&amp;amp;`.
	);
}

/**
 * Parse a relative date option from the settings API into a standard format.
 *
 * @param mixed $raw_value Value stored in DB.
 *
 * @return array Nicely formatted array with number and unit values.
 */
function er_parse_relative_date_option( $raw_value ) {
	$periods = array(
		'days'   => __( 'Day(s)', 'easyReservations' ),
		'weeks'  => __( 'Week(s)', 'easyReservations' ),
		'months' => __( 'Month(s)', 'easyReservations' ),
		'years'  => __( 'Year(s)', 'easyReservations' ),
	);

	$value = wp_parse_args(
		(array) $raw_value,
		array(
			'number' => '',
			'unit'   => 'days',
		)
	);

	$value['number'] = ! empty( $value['number'] ) ? absint( $value['number'] ) : '';

	if ( ! in_array( $value['unit'], array_keys( $periods ), true ) ) {
		$value['unit'] = 'days';
	}

	return $value;
}