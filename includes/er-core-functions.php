<?php
defined( 'ABSPATH' ) || exit;

include_once( RESERVATIONS_ABSPATH . 'includes/er-cart-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-conditional-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-date-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-form-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-formatting-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-meta-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-order-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-page-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-receipt-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-receipt-item-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-reservation-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-resource-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-term-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-user-functions.php' );
include_once( RESERVATIONS_ABSPATH . 'includes/er-widget-functions.php' );

/**
 * Short Description (excerpt).
 */
if ( function_exists( 'do_blocks' ) ) {
	add_filter( 'easyreservations_short_description', 'do_blocks', 9 );
}
add_filter( 'easyreservations_short_description', 'wptexturize' );
add_filter( 'easyreservations_short_description', 'convert_smilies' );
add_filter( 'easyreservations_short_description', 'convert_chars' );
add_filter( 'easyreservations_short_description', 'wpautop' );
add_filter( 'easyreservations_short_description', 'shortcode_unautop' );
add_filter( 'easyreservations_short_description', 'prepend_attachment' );
add_filter( 'easyreservations_short_description', 'do_shortcode', 11 ); // After wpautop().
add_filter( 'easyreservations_short_description', 'er_do_oembeds' );
add_filter( 'easyreservations_short_description', array( $GLOBALS['wp_embed'], 'run_shortcode' ), 8 ); // Before wpautop().

/**
 * Define a constant if it is not already defined.
 *
 * @param string $name Constant name.
 * @param mixed  $value Value.
 */
function er_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Is registration required to checkout?
 *
 * @return boolean
 */
function er_is_registration_required() {
	return apply_filters( 'easyreservations_checkout_registration_required', 'yes' !== get_option( 'reservations_enable_guest_checkout' ) );
}

/**
 * Is registration enabled on the checkout page?
 *
 * @return boolean
 */
function er_is_registration_enabled() {
	return apply_filters( 'easyreservations_checkout_registration_enabled', 'yes' === get_option( 'reservations_enable_signup_and_login_from_checkout' ) );
}

/**
 * Date Format - Allows to change date format.
 *
 * @return string
 */
function er_date_format() {
	return apply_filters( 'er_date_format', get_option( 'reservations_date_format', 'd.m.Y' ) );
}

/**
 * Time Format - Allows to change time format.
 *
 * @return string
 */
function er_time_format() {
	return apply_filters( 'er_time_format', get_option( 'reservations_time_format', 'H:i' ) );
}

/**
 * Time Format - Allows to change time format.
 *
 * @param $force_time bool
 *
 * @return string
 */
function er_datetime_format( $force_time = false ) {
	$format = er_date_format();

	if ( $force_time || er_use_time() ) {
		$format .= ' ' . er_time_format();
	}

	return apply_filters( 'er_datetime_format', $format );
}

/**
 * Wether to display and use time throughout the plugin
 *
 * @return bool
 */
function er_use_time() {
	return apply_filters( 'er_use_time', get_option( 'reservations_use_time', 'yes' ) === 'yes' );
}

/**
 * How many seconds from now until arrival is possible.
 *
 * @return int
 */
function er_earliest_arrival() {
	return apply_filters( 'er_earliest_arrival', intval( get_option( 'reservations_earliest_arrival', 0 ) ) * 60 );
}

/**
 * Are taxes enabled.
 *
 * @return bool
 */
function er_tax_enabled() {
	return apply_filters( 'er_tax_enabled', get_option( 'reservations_enable_taxes', 'yes' ) === 'yes' );
}

/**
 * Prices including tax
 *
 * @return bool
 */
function er_prices_include_tax() {
	return apply_filters( 'er_prices_include_tax', get_option( 'reservations_prices_include_tax' ) === 'yes' );
}

/**
 * Return the thousand separator for prices.
 *
 * @return string
 */
function er_get_price_thousand_separator() {
	return stripslashes( apply_filters( 'easyreservations_price_thousand_separator', get_option( 'reservations_price_thousand_sep', '.' ) ) );
}

/**
 * Return the decimal separator for prices.
 *
 * @return string
 */
function er_get_price_decimal_separator() {
	$separator = apply_filters( 'easyreservations_price_decimal_separator', get_option( 'reservations_price_decimal_sep', ',' ) );

	return $separator ? stripslashes( $separator ) : '.';
}

/**
 * Return the position of the currency symbol in prices.
 *
 * @return string
 */
function er_get_price_currency_pos() {
	return apply_filters( 'easyreservations_price_currency_pos', get_option( 'reservations_currency_pos', 'left' ) );
}

/**
 * Return the number of decimals after the decimal point.
 *
 * @return int
 */
function er_get_price_decimals() {
	return absint( apply_filters( 'easyreservations_price_decimals', get_option( 'reservations_price_decimals', 2 ) ) );
}

/**
 * Get the price format depending on the currency position.
 *
 * @return string
 */
function er_get_price_format() {
	$currency_pos = er_get_price_currency_pos();
	$format       = '%1$s%2$s';

	switch ( $currency_pos ) {
		case 'left':
			$format = '%1$s%2$s';
			break;
		case 'right':
			$format = '%2$s%1$s';
			break;
		case 'left_space':
			$format = '%1$s&nbsp;%2$s';
			break;
		case 'right_space':
			$format = '%2$s&nbsp;%1$s';
			break;
	}

	return apply_filters( 'easyreservations_price_format', $format, $currency_pos );
}

/**
 * Return currency code.
 *
 * @return string
 */
function er_get_currency() {
	return apply_filters( 'easyreservations_currency', strtoupper( sanitize_key( get_option( 'reservations_currency', 'USD' ) ) ) );
}

/**
 * Get full list of currency codes.
 *
 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
 *
 * @return array
 */
function er_get_currencies() {
	static $currencies;

	if ( ! isset( $currencies ) ) {
		$currencies = array_unique(
			apply_filters(
				'easyreservations_currencies',
				array(
					'AED' => __( 'United Arab Emirates dirham', 'easyReservations' ),
					'AFN' => __( 'Afghan afghani', 'easyReservations' ),
					'ALL' => __( 'Albanian lek', 'easyReservations' ),
					'AMD' => __( 'Armenian dram', 'easyReservations' ),
					'ANG' => __( 'Netherlands Antillean guilder', 'easyReservations' ),
					'AOA' => __( 'Angolan kwanza', 'easyReservations' ),
					'ARS' => __( 'Argentine peso', 'easyReservations' ),
					'AUD' => __( 'Australian dollar', 'easyReservations' ),
					'AWG' => __( 'Aruban florin', 'easyReservations' ),
					'AZN' => __( 'Azerbaijani manat', 'easyReservations' ),
					'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'easyReservations' ),
					'BBD' => __( 'Barbadian dollar', 'easyReservations' ),
					'BDT' => __( 'Bangladeshi taka', 'easyReservations' ),
					'BGN' => __( 'Bulgarian lev', 'easyReservations' ),
					'BHD' => __( 'Bahraini dinar', 'easyReservations' ),
					'BIF' => __( 'Burundian franc', 'easyReservations' ),
					'BMD' => __( 'Bermudian dollar', 'easyReservations' ),
					'BND' => __( 'Brunei dollar', 'easyReservations' ),
					'BOB' => __( 'Bolivian boliviano', 'easyReservations' ),
					'BRL' => __( 'Brazilian real', 'easyReservations' ),
					'BSD' => __( 'Bahamian dollar', 'easyReservations' ),
					'BTC' => __( 'Bitcoin', 'easyReservations' ),
					'BTN' => __( 'Bhutanese ngultrum', 'easyReservations' ),
					'BWP' => __( 'Botswana pula', 'easyReservations' ),
					'BYR' => __( 'Belarusian ruble (old)', 'easyReservations' ),
					'BYN' => __( 'Belarusian ruble', 'easyReservations' ),
					'BZD' => __( 'Belize dollar', 'easyReservations' ),
					'CAD' => __( 'Canadian dollar', 'easyReservations' ),
					'CDF' => __( 'Congolese franc', 'easyReservations' ),
					'CHF' => __( 'Swiss franc', 'easyReservations' ),
					'CLP' => __( 'Chilean peso', 'easyReservations' ),
					'CNY' => __( 'Chinese yuan', 'easyReservations' ),
					'COP' => __( 'Colombian peso', 'easyReservations' ),
					'CRC' => __( 'Costa Rican col&oacute;n', 'easyReservations' ),
					'CUC' => __( 'Cuban convertible peso', 'easyReservations' ),
					'CUP' => __( 'Cuban peso', 'easyReservations' ),
					'CVE' => __( 'Cape Verdean escudo', 'easyReservations' ),
					'CZK' => __( 'Czech koruna', 'easyReservations' ),
					'DJF' => __( 'Djiboutian franc', 'easyReservations' ),
					'DKK' => __( 'Danish krone', 'easyReservations' ),
					'DOP' => __( 'Dominican peso', 'easyReservations' ),
					'DZD' => __( 'Algerian dinar', 'easyReservations' ),
					'EGP' => __( 'Egyptian pound', 'easyReservations' ),
					'ERN' => __( 'Eritrean nakfa', 'easyReservations' ),
					'ETB' => __( 'Ethiopian birr', 'easyReservations' ),
					'EUR' => __( 'Euro', 'easyReservations' ),
					'FJD' => __( 'Fijian dollar', 'easyReservations' ),
					'FKP' => __( 'Falkland Islands pound', 'easyReservations' ),
					'GBP' => __( 'Pound sterling', 'easyReservations' ),
					'GEL' => __( 'Georgian lari', 'easyReservations' ),
					'GGP' => __( 'Guernsey pound', 'easyReservations' ),
					'GHS' => __( 'Ghana cedi', 'easyReservations' ),
					'GIP' => __( 'Gibraltar pound', 'easyReservations' ),
					'GMD' => __( 'Gambian dalasi', 'easyReservations' ),
					'GNF' => __( 'Guinean franc', 'easyReservations' ),
					'GTQ' => __( 'Guatemalan quetzal', 'easyReservations' ),
					'GYD' => __( 'Guyanese dollar', 'easyReservations' ),
					'HKD' => __( 'Hong Kong dollar', 'easyReservations' ),
					'HNL' => __( 'Honduran lempira', 'easyReservations' ),
					'HRK' => __( 'Croatian kuna', 'easyReservations' ),
					'HTG' => __( 'Haitian gourde', 'easyReservations' ),
					'HUF' => __( 'Hungarian forint', 'easyReservations' ),
					'IDR' => __( 'Indonesian rupiah', 'easyReservations' ),
					'ILS' => __( 'Israeli new shekel', 'easyReservations' ),
					'IMP' => __( 'Manx pound', 'easyReservations' ),
					'INR' => __( 'Indian rupee', 'easyReservations' ),
					'IQD' => __( 'Iraqi dinar', 'easyReservations' ),
					'IRR' => __( 'Iranian rial', 'easyReservations' ),
					'IRT' => __( 'Iranian toman', 'easyReservations' ),
					'ISK' => __( 'Icelandic kr&oacute;na', 'easyReservations' ),
					'JEP' => __( 'Jersey pound', 'easyReservations' ),
					'JMD' => __( 'Jamaican dollar', 'easyReservations' ),
					'JOD' => __( 'Jordanian dinar', 'easyReservations' ),
					'JPY' => __( 'Japanese yen', 'easyReservations' ),
					'KES' => __( 'Kenyan shilling', 'easyReservations' ),
					'KGS' => __( 'Kyrgyzstani som', 'easyReservations' ),
					'KHR' => __( 'Cambodian riel', 'easyReservations' ),
					'KMF' => __( 'Comorian franc', 'easyReservations' ),
					'KPW' => __( 'North Korean won', 'easyReservations' ),
					'KRW' => __( 'South Korean won', 'easyReservations' ),
					'KWD' => __( 'Kuwaiti dinar', 'easyReservations' ),
					'KYD' => __( 'Cayman Islands dollar', 'easyReservations' ),
					'KZT' => __( 'Kazakhstani tenge', 'easyReservations' ),
					'LAK' => __( 'Lao kip', 'easyReservations' ),
					'LBP' => __( 'Lebanese pound', 'easyReservations' ),
					'LKR' => __( 'Sri Lankan rupee', 'easyReservations' ),
					'LRD' => __( 'Liberian dollar', 'easyReservations' ),
					'LSL' => __( 'Lesotho loti', 'easyReservations' ),
					'LYD' => __( 'Libyan dinar', 'easyReservations' ),
					'MAD' => __( 'Moroccan dirham', 'easyReservations' ),
					'MDL' => __( 'Moldovan leu', 'easyReservations' ),
					'MGA' => __( 'Malagasy ariary', 'easyReservations' ),
					'MKD' => __( 'Macedonian denar', 'easyReservations' ),
					'MMK' => __( 'Burmese kyat', 'easyReservations' ),
					'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'easyReservations' ),
					'MOP' => __( 'Macanese pataca', 'easyReservations' ),
					'MRU' => __( 'Mauritanian ouguiya', 'easyReservations' ),
					'MUR' => __( 'Mauritian rupee', 'easyReservations' ),
					'MVR' => __( 'Maldivian rufiyaa', 'easyReservations' ),
					'MWK' => __( 'Malawian kwacha', 'easyReservations' ),
					'MXN' => __( 'Mexican peso', 'easyReservations' ),
					'MYR' => __( 'Malaysian ringgit', 'easyReservations' ),
					'MZN' => __( 'Mozambican metical', 'easyReservations' ),
					'NAD' => __( 'Namibian dollar', 'easyReservations' ),
					'NGN' => __( 'Nigerian naira', 'easyReservations' ),
					'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'easyReservations' ),
					'NOK' => __( 'Norwegian krone', 'easyReservations' ),
					'NPR' => __( 'Nepalese rupee', 'easyReservations' ),
					'NZD' => __( 'New Zealand dollar', 'easyReservations' ),
					'OMR' => __( 'Omani rial', 'easyReservations' ),
					'PAB' => __( 'Panamanian balboa', 'easyReservations' ),
					'PEN' => __( 'Sol', 'easyReservations' ),
					'PGK' => __( 'Papua New Guinean kina', 'easyReservations' ),
					'PHP' => __( 'Philippine peso', 'easyReservations' ),
					'PKR' => __( 'Pakistani rupee', 'easyReservations' ),
					'PLN' => __( 'Polish z&#x142;oty', 'easyReservations' ),
					'PRB' => __( 'Transnistrian ruble', 'easyReservations' ),
					'PYG' => __( 'Paraguayan guaran&iacute;', 'easyReservations' ),
					'QAR' => __( 'Qatari riyal', 'easyReservations' ),
					'RON' => __( 'Romanian leu', 'easyReservations' ),
					'RSD' => __( 'Serbian dinar', 'easyReservations' ),
					'RUB' => __( 'Russian ruble', 'easyReservations' ),
					'RWF' => __( 'Rwandan franc', 'easyReservations' ),
					'SAR' => __( 'Saudi riyal', 'easyReservations' ),
					'SBD' => __( 'Solomon Islands dollar', 'easyReservations' ),
					'SCR' => __( 'Seychellois rupee', 'easyReservations' ),
					'SDG' => __( 'Sudanese pound', 'easyReservations' ),
					'SEK' => __( 'Swedish krona', 'easyReservations' ),
					'SGD' => __( 'Singapore dollar', 'easyReservations' ),
					'SHP' => __( 'Saint Helena pound', 'easyReservations' ),
					'SLL' => __( 'Sierra Leonean leone', 'easyReservations' ),
					'SOS' => __( 'Somali shilling', 'easyReservations' ),
					'SRD' => __( 'Surinamese dollar', 'easyReservations' ),
					'SSP' => __( 'South Sudanese pound', 'easyReservations' ),
					'STN' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'easyReservations' ),
					'SYP' => __( 'Syrian pound', 'easyReservations' ),
					'SZL' => __( 'Swazi lilangeni', 'easyReservations' ),
					'THB' => __( 'Thai baht', 'easyReservations' ),
					'TJS' => __( 'Tajikistani somoni', 'easyReservations' ),
					'TMT' => __( 'Turkmenistan manat', 'easyReservations' ),
					'TND' => __( 'Tunisian dinar', 'easyReservations' ),
					'TOP' => __( 'Tongan pa&#x2bb;anga', 'easyReservations' ),
					'TRY' => __( 'Turkish lira', 'easyReservations' ),
					'TTD' => __( 'Trinidad and Tobago dollar', 'easyReservations' ),
					'TWD' => __( 'New Taiwan dollar', 'easyReservations' ),
					'TZS' => __( 'Tanzanian shilling', 'easyReservations' ),
					'UAH' => __( 'Ukrainian hryvnia', 'easyReservations' ),
					'UGX' => __( 'Ugandan shilling', 'easyReservations' ),
					'USD' => __( 'United States (US) dollar', 'easyReservations' ),
					'UYU' => __( 'Uruguayan peso', 'easyReservations' ),
					'UZS' => __( 'Uzbekistani som', 'easyReservations' ),
					'VEF' => __( 'Venezuelan bol&iacute;var', 'easyReservations' ),
					'VES' => __( 'Bol&iacute;var soberano', 'easyReservations' ),
					'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'easyReservations' ),
					'VUV' => __( 'Vanuatu vatu', 'easyReservations' ),
					'WST' => __( 'Samoan t&#x101;l&#x101;', 'easyReservations' ),
					'XAF' => __( 'Central African CFA franc', 'easyReservations' ),
					'XCD' => __( 'East Caribbean dollar', 'easyReservations' ),
					'XOF' => __( 'West African CFA franc', 'easyReservations' ),
					'XPF' => __( 'CFP franc', 'easyReservations' ),
					'YER' => __( 'Yemeni rial', 'easyReservations' ),
					'ZAR' => __( 'South African rand', 'easyReservations' ),
					'ZMW' => __( 'Zambian kwacha', 'easyReservations' ),
				)
			)
		);
	}

	return $currencies;
}

/**
 * Get all available Currency symbols.
 *
 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
 *
 * @param string $currency Currency. (default: '').
 *
 * @return string
 */
function er_get_currency_symbol( $currency = '' ) {
	if ( $currency === '' ) {
		$currency = er_get_currency();

		if ( ! $currency ) {
			$currency = 'USD';
		}
	}

	$symbols = apply_filters(
		'easyreservations_currency_symbols',
		array(
			'AED' => '&#x62f;.&#x625;',
			'AFN' => '&#x60b;',
			'ALL' => 'L',
			'AMD' => 'AMD',
			'ANG' => '&fnof;',
			'AOA' => 'Kz',
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => 'Afl.',
			'AZN' => 'AZN',
			'BAM' => 'KM',
			'BBD' => '&#36;',
			'BDT' => '&#2547;&nbsp;',
			'BGN' => '&#1083;&#1074;.',
			'BHD' => '.&#x62f;.&#x628;',
			'BIF' => 'Fr',
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => 'Bs.',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTC' => '&#3647;',
			'BTN' => 'Nu.',
			'BWP' => 'P',
			'BYR' => 'Br',
			'BYN' => 'Br',
			'BZD' => '&#36;',
			'CAD' => '&#36;',
			'CDF' => 'Fr',
			'CHF' => '&#67;&#72;&#70;',
			'CLP' => '&#36;',
			'CNY' => '&yen;',
			'COP' => '&#36;',
			'CRC' => '&#x20a1;',
			'CUC' => '&#36;',
			'CUP' => '&#36;',
			'CVE' => '&#36;',
			'CZK' => '&#75;&#269;',
			'DJF' => 'Fr',
			'DKK' => 'DKK',
			'DOP' => 'RD&#36;',
			'DZD' => '&#x62f;.&#x62c;',
			'EGP' => 'EGP',
			'ERN' => 'Nfk',
			'ETB' => 'Br',
			'EUR' => '&euro;',
			'FJD' => '&#36;',
			'FKP' => '&pound;',
			'GBP' => '&pound;',
			'GEL' => '&#x20be;',
			'GGP' => '&pound;',
			'GHS' => '&#x20b5;',
			'GIP' => '&pound;',
			'GMD' => 'D',
			'GNF' => 'Fr',
			'GTQ' => 'Q',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => 'L',
			'HRK' => 'kn',
			'HTG' => 'G',
			'HUF' => '&#70;&#116;',
			'IDR' => 'Rp',
			'ILS' => '&#8362;',
			'IMP' => '&pound;',
			'INR' => '&#8377;',
			'IQD' => '&#x639;.&#x62f;',
			'IRR' => '&#xfdfc;',
			'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
			'ISK' => 'kr.',
			'JEP' => '&pound;',
			'JMD' => '&#36;',
			'JOD' => '&#x62f;.&#x627;',
			'JPY' => '&yen;',
			'KES' => 'KSh',
			'KGS' => '&#x441;&#x43e;&#x43c;',
			'KHR' => '&#x17db;',
			'KMF' => 'Fr',
			'KPW' => '&#x20a9;',
			'KRW' => '&#8361;',
			'KWD' => '&#x62f;.&#x643;',
			'KYD' => '&#36;',
			'KZT' => '&#8376;',
			'LAK' => '&#8365;',
			'LBP' => '&#x644;.&#x644;',
			'LKR' => '&#xdbb;&#xdd4;',
			'LRD' => '&#36;',
			'LSL' => 'L',
			'LYD' => '&#x644;.&#x62f;',
			'MAD' => '&#x62f;.&#x645;.',
			'MDL' => 'MDL',
			'MGA' => 'Ar',
			'MKD' => '&#x434;&#x435;&#x43d;',
			'MMK' => 'Ks',
			'MNT' => '&#x20ae;',
			'MOP' => 'P',
			'MRU' => 'UM',
			'MUR' => '&#x20a8;',
			'MVR' => '.&#x783;',
			'MWK' => 'MK',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => 'MT',
			'NAD' => 'N&#36;',
			'NGN' => '&#8358;',
			'NIO' => 'C&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#x631;.&#x639;.',
			'PAB' => 'B/.',
			'PEN' => 'S/',
			'PGK' => 'K',
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PRB' => '&#x440;.',
			'PYG' => '&#8370;',
			'QAR' => '&#x631;.&#x642;',
			'RMB' => '&yen;',
			'RON' => 'lei',
			'RSD' => '&#1088;&#1089;&#1076;',
			'RUB' => '&#8381;',
			'RWF' => 'Fr',
			'SAR' => '&#x631;.&#x633;',
			'SBD' => '&#36;',
			'SCR' => '&#x20a8;',
			'SDG' => '&#x62c;.&#x633;.',
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&pound;',
			'SLL' => 'Le',
			'SOS' => 'Sh',
			'SRD' => '&#36;',
			'SSP' => '&pound;',
			'STN' => 'Db',
			'SYP' => '&#x644;.&#x633;',
			'SZL' => 'L',
			'THB' => '&#3647;',
			'TJS' => '&#x405;&#x41c;',
			'TMT' => 'm',
			'TND' => '&#x62f;.&#x62a;',
			'TOP' => 'T&#36;',
			'TRY' => '&#8378;',
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => 'Sh',
			'UAH' => '&#8372;',
			'UGX' => 'UGX',
			'USD' => '&#36;',
			'UYU' => '&#36;',
			'UZS' => 'UZS',
			'VEF' => 'Bs F',
			'VES' => 'Bs.S',
			'VND' => '&#8363;',
			'VUV' => 'Vt',
			'WST' => 'T',
			'XAF' => 'CFA',
			'XCD' => '&#36;',
			'XOF' => 'CFA',
			'XPF' => 'Fr',
			'YER' => '&#xfdfc;',
			'ZAR' => '&#82;',
			'ZMW' => 'ZK',
		)
	);

	if ( ! $currency ) {
		return apply_filters( 'easyreservations_currency_symbols', $symbols );
	}

	$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

	return apply_filters( 'easyreservations_currency_symbol', $currency_symbol, $currency );
}

/**
 * Given a path, this will convert any of the subpaths into their corresponding tokens.
 *
 * @param string $path The absolute path to tokenize.
 * @param array  $path_tokens An array keyed with the token, containing paths that should be replaced.
 *
 * @return string The tokenized path.
 */
function er_tokenize_path( $path, $path_tokens ) {
	// Order most to least specific so that the token can encompass as much of the path as possible.
	uasort(
		$path_tokens,
		function ( $a, $b ) {
			$a = strlen( $a );
			$b = strlen( $b );

			if ( $a > $b ) {
				return - 1;
			}

			if ( $b > $a ) {
				return 1;
			}

			return 0;
		}
	);

	foreach ( $path_tokens as $token => $token_path ) {
		if ( 0 !== strpos( $path, $token_path ) ) {
			continue;
		}

		$path = str_replace( $token_path, '{{' . $token . '}}', $path );
	}

	return $path;
}

/**
 * Given a tokenized path, this will expand the tokens to their full path.
 *
 * @param string $path The absolute path to expand.
 * @param array  $path_tokens An array keyed with the token, containing paths that should be expanded.
 *
 * @return string The absolute path.
 */
function er_untokenize_path( $path, $path_tokens ) {
	foreach ( $path_tokens as $token => $token_path ) {
		$path = str_replace( '{{' . $token . '}}', $token_path, $path );
	}

	return $path;
}

/**
 * Fetches an array containing all of the configurable path constants to be used in tokenization.
 *
 * @return array The key is the define and the path is the constant.
 */
function er_get_path_define_tokens() {
	$defines = array(
		'ABSPATH',
		'WP_CONTENT_DIR',
		'WP_PLUGIN_DIR',
		'WPMU_PLUGIN_DIR',
		'PLUGINDIR',
		'WP_THEME_DIR',
	);

	$path_tokens = array();
	foreach ( $defines as $define ) {
		if ( defined( $define ) ) {
			$path_tokens[ $define ] = constant( $define );
		}
	}

	return apply_filters( 'easyreservations_get_path_define_tokens', $path_tokens );
}
/**
 * Get template part (for templates like the shop-loop).
 *
 * RESERVATIONS_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function er_get_template_part( $slug, $name = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template-part', $slug, $name, RESERVATIONS_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'easyreservations' );

	if ( ! $template ) {
		if ( $name ) {
			$template = RESERVATIONS_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}-{$name}.php",
					ER()->template_path() . "{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = ER()->plugin_path() . "/templates/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/easyreservations/slug.php.
			$template = RESERVATIONS_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}.php",
					ER()->template_path() . "{$slug}.php",
				)
			);
		}

		// Don't cache the absolute path so that it can be shared between web servers with different paths.
		$cache_path = er_tokenize_path( $template, er_get_path_define_tokens() );

		er_set_template_cache( $cache_key, $cache_path );
	} else {
		// Make sure that the absolute path to the template is resolved.
		$template = er_untokenize_path( $template, er_get_path_define_tokens() );
	}
	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'easyreservations_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates.
 *
 * @param string $template_name Template name.
 * @param array  $args Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path Default path. (default: '').
 */
function er_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path, RESERVATIONS_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'easyreservations' );

	if ( ! $template ) {
		$template = er_locate_template( $template_name, $template_path, $default_path );

		// Don't cache the absolute path so that it can be shared between web servers with different paths.
		$cache_path = er_tokenize_path( $template, er_get_path_define_tokens() );

		er_set_template_cache( $cache_key, $cache_path );
	} else {
		// Make sure that the absolute path to the template is resolved.
		$template = er_untokenize_path( $template, er_get_path_define_tokens() );
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'er_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			/* translators: %s template */
			_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'easyReservations' ), '<code>' . $filter_template . '</code>' ), '2.1' );

			return;
		}
		$template = $filter_template;
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		if ( isset( $args['action_args'] ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				__( 'action_args should not be overwritten when calling er_get_template.', 'easyReservations' ),
				'3.6.0'
			);
			unset( $args['action_args'] );
		}
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action( 'easyreservations_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'easyreservations_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

/**
 * Like er_get_template, but returns the HTML instead of outputting.
 *
 * @param string $template_name Template name.
 * @param array  $args Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path Default path. (default: '').
 *
 * @return string
 * @see er_get_template
 */
function er_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	er_get_template( $template_name, $args, $template_path, $default_path );

	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path Default path. (default: '').
 *
 * @return string
 */
function er_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = ER()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = apply_filters(
			'easyreservations_locate_template_default_path',
			ER()->plugin_path() . '/templates/',
			$template_name,
			$template_path
		);
	}

	// Look within passed path within the theme - this is priority.
	$cs_template = str_replace( '_', '-', $template_name );
	$template    = locate_template(
		array(
			trailingslashit( $template_path ) . $cs_template,
			$cs_template,
		)
	);

	// Get default template/.
	if ( ! $template || RESERVATIONS_TEMPLATE_DEBUG_MODE ) {
		if ( empty( $cs_template ) ) {
			$template = $default_path . $template_name;
		} else {
			$template = $default_path . $cs_template;
		}
	}

	// Return what we found.
	return apply_filters( 'easyreservations_locate_template', $template, $template_name, $template_path );
}

/**
 * Add a template to the template cache.
 *
 * @param string $cache_key Object cache key.
 * @param string $template Located template.
 */
function er_set_template_cache( $cache_key, $template ) {
	wp_cache_set( $cache_key, $template, 'easyreservations' );

	$cached_templates = wp_cache_get( 'cached_templates', 'easyreservations' );
	if ( is_array( $cached_templates ) ) {
		$cached_templates[] = $cache_key;
	} else {
		$cached_templates = array( $cache_key );
	}

	wp_cache_set( 'cached_templates', $cached_templates, 'easyreservations' );
}

/**
 * Clear the template cache.
 */
function er_clear_template_cache() {
	$cached_templates = wp_cache_get( 'cached_templates', 'easyreservations' );
	if ( is_array( $cached_templates ) ) {
		foreach ( $cached_templates as $cache_key ) {
			wp_cache_delete( $cache_key, 'easyreservations' );
		}

		wp_cache_delete( 'cached_templates', 'easyreservations' );
	}
}

/**
 * Get transient version.
 *
 * When using transients with unpredictable names, e.g. those containing an md5
 * hash in the name, we need a way to invalidate them all at once.
 *
 * When using default WP transients we're able to do this with a DB query to
 * delete transients manually.
 *
 * With external cache however, this isn't possible. Instead, this function is used
 * to append a unique string (based on time()) to each transient. When transients
 * are invalidated, the transient version will increment and data will be regenerated.
 *
 * Raised in issue https://github.com/woocommerce/woocommerce/issues/5777.
 * Adapted from ideas in http://tollmanz.com/invalidation-schemes/.
 *
 * @param string  $group Name for the group of transients we need to invalidate.
 * @param boolean $refresh true to force a new version.
 *
 * @return string transient version based on time(), 10 digits.
 */
function er_get_transient_version( $group, $refresh = false ) {
	$transient_name  = $group . '-transient-version';
	$transient_value = get_transient( $transient_name );

	if ( false === $transient_value || true === $refresh ) {
		$transient_value = (string) time();

		set_transient( $transient_name, $transient_value );
	}

	return $transient_value;
}

/**
 * Return "theme support" values from the current theme, if set.
 *
 * @param string $prop Name of prop (or key::subkey for arrays of props) if you want a specific value. Leave blank to get all props as an array.
 * @param mixed  $default Optional value to return if the theme does not declare support for a prop.
 *
 * @return mixed  Value of prop(s).
 */
function er_get_theme_support( $prop = '', $default = null ) {
	$theme_support = get_theme_support( 'easyreservations' );
	$theme_support = is_array( $theme_support ) ? $theme_support[0] : false;

	if ( ! $theme_support ) {
		return $default;
	}

	if ( $prop ) {
		$prop_stack = explode( '::', $prop );
		$prop_key   = array_shift( $prop_stack );

		if ( isset( $theme_support[ $prop_key ] ) ) {
			$value = $theme_support[ $prop_key ];

			if ( count( $prop_stack ) ) {
				foreach ( $prop_stack as $prop_key ) {
					if ( is_array( $value ) && isset( $value[ $prop_key ] ) ) {
						$value = $value[ $prop_key ];
					} else {
						$value = $default;
						break;
					}
				}
			}
		} else {
			$value = $default;
		}

		return $value;
	}

	return $theme_support;
}

/**
 * Get an image size by name or defined dimensions.
 *
 * The returned variable is filtered by easyreservations_get_image_size_{image_size} filter to
 * allow 3rd party customisation.
 *
 * Sizes defined by the theme take priority over settings. Settings are hidden when a theme
 * defines sizes.
 *
 * @param array|string $image_size Name of the image size to get, or an array of dimensions.
 *
 * @return array Array of dimensions including width, height, and cropping mode. Cropping mode is 0 for no crop, and 1 for hard crop.
 */
function er_get_image_size( $image_size ) {
	$cache_key = 'size-' . ( is_array( $image_size ) ? implode( '-', $image_size ) : $image_size );
	$size      = wp_cache_get( $cache_key, 'easyreservations' );

	if ( $size ) {
		return $size;
	}

	$size = array(
		'width'  => 600,
		'height' => 600,
		'crop'   => 1,
	);

	if ( is_array( $image_size ) ) {
		$size       = array(
			'width'  => isset( $image_size[0] ) ? absint( $image_size[0] ) : 600,
			'height' => isset( $image_size[1] ) ? absint( $image_size[1] ) : 600,
			'crop'   => isset( $image_size[2] ) ? absint( $image_size[2] ) : 1,
		);
		$image_size = $size['width'] . '_' . $size['height'];
	} else {
		$image_size = str_replace( 'easyreservations_', '', $image_size );

		if ( 'single' === $image_size ) {
			$size['width']  = absint( er_get_theme_support( 'single_image_width', get_option( 'reservations_single_image_width', 600 ) ) );
			$size['height'] = '';
			$size['crop']   = 0;
		} elseif ( 'gallery_thumbnail' === $image_size ) {
			$size['width']  = absint( er_get_theme_support( 'gallery_thumbnail_image_width', 100 ) );
			$size['height'] = $size['width'];
			$size['crop']   = 1;
		} elseif ( 'thumbnail' === $image_size ) {
			$size['width'] = absint( er_get_theme_support( 'thumbnail_image_width', get_option( 'reservations_thumbnail_image_width', 300 ) ) );
			$cropping      = get_option( 'reservations_thumbnail_cropping', '1:1' );

			if ( 'uncropped' === $cropping ) {
				$size['height'] = '';
				$size['crop']   = 0;
			} elseif ( 'custom' === $cropping ) {
				$width          = max( 1, get_option( 'reservations_thumbnail_cropping_custom_width', '4' ) );
				$height         = max( 1, get_option( 'reservations_thumbnail_cropping_custom_height', '3' ) );
				$size['height'] = absint( ER_Number_Util::round( ( $size['width'] / $width ) * $height ) );
				$size['crop']   = 1;
			} else {
				$cropping_split = explode( ':', $cropping );
				$width          = max( 1, current( $cropping_split ) );
				$height         = max( 1, end( $cropping_split ) );
				$size['height'] = absint( ER_Number_Util::round( ( $size['width'] / $width ) * $height ) );
				$size['crop']   = 1;
			}
		}
	}

	$size = apply_filters( 'easyreservations_get_image_size_' . $image_size, $size );

	wp_cache_set( $cache_key, $size, 'easyreservations' );

	return $size;
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code Code.
 */
function er_enqueue_js( $code ) {
	global $er_queued_js;

	if ( empty( $er_queued_js ) ) {
		$er_queued_js = '';
	}

	$er_queued_js .= "\n" . $code . "\n";
}

add_action( 'wp_print_footer_scripts', 'er_print_js', 25 );
add_action( 'admin_print_footer_scripts', 'er_print_js', 25 );

/**
 * Output any queued javascript code in the footer.
 */
function er_print_js() {
	global $er_queued_js;

	if ( ! empty( $er_queued_js ) ) {
		// Sanitize.
		$er_queued_js = wp_check_invalid_utf8( $er_queued_js );
		$er_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $er_queued_js );
		$er_queued_js = str_replace( "\r", '', $er_queued_js );

		$js = "<!-- easyReservations JavaScript -->\n<script type=\"text/javascript\">\n $er_queued_js \n</script>\n";

		/**
		 * Queued jsfilter.
		 *
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'easyreservations_queued_js', $js ); // WPCS: XSS ok.

		unset( $er_queued_js );
	}
}

/**
 * @return int amount of pending reservations
 */
function er_get_pending() {
	global $wpdb;
	$count = $wpdb->get_var(
		"SELECT COUNT(*) as Num FROM " . $wpdb->prefix . "reservations WHERE status='pending' AND arrival > NOW()"
	);

	return intval( $count );
}

/**
 * Nonce without time restraint to somewhat secure email links
 *
 * @param     $nonce
 * @param int $action
 *
 * @return bool|int
 */
function er_verify_nonce( $nonce, $action = - 1 ) {
	$i = wp_nonce_tick();
	// Nonce generated 0-12 hours ago
	if ( hash_equals( substr( wp_hash( $i . '|' . $action . '|0', 'nonce' ), - 12, 10 ), $nonce ) ) {
		return 1;
	}
	// Nonce generated 12-24 hours ago
	if ( hash_equals( substr( wp_hash( ( $i - 1 ) . '|' . $action . '|0', 'nonce' ), - 12, 10 ), $nonce ) ) {
		return 2;
	}

	// Invalid nonce
	return false;
}

/**
 * Get rounding precision for internal ER calculations.
 * Will increase the precision of er_get_price_decimals by 2 decimals, unless ER_ROUNDING_PRECISION is set to a higher number.
 *
 * @return int
 */
function er_get_rounding_precision() {
	$precision = er_get_price_decimals() + 2;
	if ( absint( 6 ) > $precision ) {
		$precision = 6;
	}

	return $precision;
}

/**
 * Round discount.
 *
 * @param double $value Amount to round.
 * @param int    $precision DP to round.
 *
 * @return float
 */
function er_round_discount( $value, $precision ) {
	return ER_Number_Util::round( $value, $precision, 2 );
}

/**
 * Add precision to a number and return a number.
 *
 * @param float $value Number to add precision to.
 * @param bool  $round If should round after adding precision.
 *
 * @return int|float
 */
function er_add_number_precision( $value, $round = true ) {
	$cent_precision = pow( 10, er_get_price_decimals() );
	$value          = $value * $cent_precision;

	return $round ? ER_Number_Util::round( $value, er_get_rounding_precision() - er_get_price_decimals() ) : $value;
}

/**
 * Remove precision from a number and return a float.
 *
 * @param float $value Number to add precision to.
 *
 * @return float
 */
function er_remove_number_precision( $value ) {
	$cent_precision = pow( 10, er_get_price_decimals() );

	return $value / $cent_precision;
}

/**
 * Remove precision from an array of number and return an array of int.
 *
 * @param array $value Number to add precision to.
 *
 * @return int|array
 */
function er_remove_number_precision_deep( $value ) {
	if ( ! is_array( $value ) ) {
		return er_remove_number_precision( $value );
	}

	foreach ( $value as $key => $sub_value ) {
		$value[ $key ] = er_remove_number_precision_deep( $sub_value );
	}

	return $value;
}

/**
 * Get logger
 *
 * @return ER_Logger
 */
function er_get_logger() {
	static $logger = null;

	if ( null !== $logger && is_a( $logger, 'ER_Logger' ) ) {
		return $logger;
	}

	$logger = new ER_Logger();

	return $logger;
}

/**
 * Trigger logging cleanup using the logging class.
 */
function er_cleanup_logs() {
	$logger = er_get_logger();

	if ( is_callable( array( $logger, 'clear_expired_logs' ) ) ) {
		$logger->clear_expired_logs();
	}
}

add_action( 'easyreservations_cleanup_logs', 'er_cleanup_logs' );

/**
 * Flushes rewrite rules when the shop page (or it's children) gets saved.
 */
function er_flush_rewrite_rules_on_shop_page_save() {
	$screen    = get_current_screen();
	$screen_id = $screen ? $screen->id : '';

	// Check if this is the edit page.
	if ( 'page' !== $screen_id ) {
		return;
	}

	// Check if page is edited.
	if ( empty( $_GET['post'] ) || empty( $_GET['action'] ) || ( isset( $_GET['action'] ) && 'edit' !== $_GET['action'] ) ) { // WPCS: input var ok, CSRF ok.
		return;
	}

	$post_id      = intval( $_GET['post'] ); // WPCS: input var ok, CSRF ok.
	$shop_page_id = er_get_page_id( 'shop' );

	if ( $shop_page_id === $post_id ) {
		do_action( 'easyreservations_flush_rewrite_rules' );
	}
}

add_action( 'admin_footer', 'er_flush_rewrite_rules_on_shop_page_save' );

add_action( 'easyreservations_flush_rewrite_rules', 'flush_rewrite_rules' );

/**
 * Flush rules if the event is queued.
 */
function er_maybe_flush_rewrite_rules() {
	if ( 'yes' === get_option( 'reservations_queue_flush_rewrite_rules' ) ) {
		update_option( 'reservations_queue_flush_rewrite_rules', 'no' );
		flush_rewrite_rules();
	}
}

add_action( 'easyreservations_after_register_order_post_type', 'er_maybe_flush_rewrite_rules' );

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments block some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blocked functions.
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 *     If $return is true, a string representation will be returned.
 */
function er_print_r( $expression, $return = false ) {
	$alternatives = array(
		array(
			'func' => 'print_r',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'var_export',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'json_encode',
			'args' => array( $expression ),
		),
		array(
			'func' => 'serialize',
			'args' => array( $expression ),
		),
	);

	$alternatives = apply_filters( 'easyreservations_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = $alternative['func']( ...$alternative['args'] );
			if ( $return ) {
				return $res;
			}

			echo $res; // WPCS: XSS ok.

			return true;
		}
	}

	return false;
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @param int $limit Time limit.
 */
function er_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}

/**
 * Used to sort checkout fields based on priority with uasort.
 *
 * @param array $a First field to compare.
 * @param array $b Second field to compare.
 *
 * @return int
 */
function er_checkout_fields_uasort_comparison( $a, $b ) {
	/*
	 * We are not guaranteed to get a priority
	 * setting. So don't compare if they don't
	 * exist.
	 */
	if ( ! isset( $a['priority'], $b['priority'] ) ) {
		return 0;
	}

	return er_uasort_comparison( $a['priority'], $b['priority'] );
}

/**
 * Used to sort two values with ausort.
 *
 * @param int $a First value to compare.
 * @param int $b Second value to compare.
 *
 * @return int
 */
function er_uasort_comparison( $a, $b ) {
	if ( $a === $b ) {
		return 0;
	}

	return ( $a < $b ) ? - 1 : 1;
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @param string  $name Name of the cookie being set.
 * @param string  $value Value of the cookie.
 * @param integer $expire Expiry of the cookie.
 * @param bool    $secure Whether the cookie should be served only over https.
 * @param bool    $httponly Whether the cookie is only accessible over HTTP, not scripting languages like JavaScript.
 */
function er_set_cookie( $name, $value, $expire = 0, $secure = false, $httponly = false ) {
	if ( ! headers_sent() ) {
		setcookie(
			$name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure,
			apply_filters( 'easyreservations_cookie_httponly', $httponly, $name, $value, $expire, $secure )
		);
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error(
			"{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE
		); // @codingStandardsIgnoreLine
	}
}

/**
 * Flushes all temporary cached data like sessions and carts
 */
function er_flush_all_temp_data() {
	global $wpdb;
	wp_cache_flush();

	$table_name = $wpdb->prefix . 'reservations_sessions';

	$wpdb->query( "TRUNCATE TABLE $table_name" ); // @codingStandardsIgnoreLine.

	$table_name = $wpdb->prefix . 'usermeta';
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM $table_name WHERE meta_key like %s",
			$wpdb->esc_like( "_easyreservations_persistent_cart" ) . '%'
		)
	);
}

/**
 * Add array values together
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array
 */
function er_add_arrays( $array1, $array2 ) {
	foreach ( $array2 as $key => $value ) {
		if ( isset( $array1[ $key ] ) ) {
			$array1[ $key ] += $value;
		} else {
			$array1[ $key ] = $value;
		}
	}

	return $array1;
}

/**
 * Subtract array values together
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array
 */
function er_subtract_arrays( $array1, $array2 ) {
	foreach ( $array2 as $key => $value ) {
		if ( isset( $array1[ $key ] ) ) {
			$array1[ $key ] -= $value;
		} else {
			$array1[ $key ] = $value;
		}
	}

	return $array1;
}

/**
 * Get the resource row price per item.
 *
 * @param ER_Receipt_Item_Line $receipt_item .
 *
 * @return string formatted price
 */
function er_get_display_price( $receipt_item ) {
	if ( ER()->cart->display_prices_including_tax() ) {
		$price = $receipt_item->get_total();
	} else {
		$price = $receipt_item->get_subtotal();
	}

	return apply_filters( 'easyreservations_display_receipt_line_price', er_price( $price, true ), $receipt_item );
}

/**
 * Get the store's base location.
 *
 * @return array
 */
function er_get_default_location() {
	$location = explode( ':', get_option( 'reservations_default_location', 'US' ) );

	return apply_filters( 'easyreservations_get_default_location', array(
		'country' => $location[0],
		'state'   => isset( $location[1] ) ? $location[1] : '',
	) );
}

/**
 * Get user agent string.
 *
 * @return string
 */
function er_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? er_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // @codingStandardsIgnoreLine
}

/**
 * Get current user IP Address.
 *
 * @return string
 */
function er_get_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );  // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // @codingStandardsIgnoreLine
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // @codingStandardsIgnoreLine
	}

	return '';
}

/**
 * Get calling code for a country code.
 *
 * @param string $cc Country code.
 *
 * @return string|array Some countries have multiple. The code will be stripped of - and spaces and always be prefixed with +.
 */
function er_get_country_calling_code( $cc ) {
	$codes = wp_cache_get( 'calling-codes', 'countries' );

	if ( ! $codes ) {
		$codes = include ER()->plugin_path() . '/i18n/phone.php';
		wp_cache_set( 'calling-codes', $codes, 'countries' );
	}

	$calling_code = isset( $codes[ $cc ] ) ? $codes[ $cc ] : '';

	if ( is_array( $calling_code ) ) {
		$calling_code = $calling_code[0];
	}

	return $calling_code;
}

/**
 * Based on wp_list_pluck, this calls a method instead of returning a property.
 *
 * @param array      $list List of objects or arrays.
 * @param int|string $callback_or_field Callback method from the object to place instead of the entire object.
 * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
 *                                      Default null.
 *
 * @return array Array of values.
 */
function er_list_pluck( $list, $callback_or_field, $index_key = null ) {
	// Use wp_list_pluck if this isn't a callback.
	$first_el = current( $list );
	if ( ! is_object( $first_el ) || ! is_callable( array( $first_el, $callback_or_field ) ) ) {
		return wp_list_pluck( $list, $callback_or_field, $index_key );
	}

	if ( ! $index_key ) {
		/*
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			$list[ $key ] = $value->{$callback_or_field}();
		}

		return $list;
	}

	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();

	foreach ( $list as $value ) {
		// Get index. this supports a callback.
		if ( is_callable( array( $value, $index_key ) ) ) {
			$newlist[ $value->{$index_key}() ] = $value->{$callback_or_field}();
		} elseif ( isset( $value->$index_key ) ) {
			$newlist[ $value->$index_key ] = $value->{$callback_or_field}();
		} else {
			$newlist[] = $value->{$callback_or_field}();
		}
	}

	return $newlist;
}

/**
 * Get permalink settings for things like resources and taxonomies.
 *
 * This is more inline with WP core behavior which does not localize slugs.
 *
 * @return array
 */
function er_get_permalink_structure() {
	$saved_permalinks = (array) get_option( 'reservations_permalinks', array() );
	$permalinks       = wp_parse_args(
		array_filter( $saved_permalinks ),
		array(
			'resource_base'          => _x( 'resource', 'slug', 'easyReservations' ),
			'category_base'          => _x( 'resource-category', 'slug', 'easyReservations' ),
			'tag_base'               => _x( 'resource-tag', 'slug', 'easyReservations' ),
			'use_verbose_page_rules' => false,
		)
	);

	if ( $saved_permalinks !== $permalinks ) {
		update_option( 'reservations_permalinks', $permalinks );
	}

	$permalinks['resource_rewrite_slug'] = untrailingslashit( $permalinks['resource_base'] );
	$permalinks['category_rewrite_slug'] = untrailingslashit( $permalinks['category_base'] );
	$permalinks['tag_rewrite_slug']      = untrailingslashit( $permalinks['tag_base'] );

	return $permalinks;
}

/**
 * Switch easyReservations to site language.
 *
 * @param string|bool $locale
 */
function er_switch_to_site_locale( $locale = false ) {
	if ( function_exists( 'switch_to_locale' ) ) {
		if ( ! $locale ) {
			$locale = get_locale();
		}

		switch_to_locale( $locale );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init ER locale.
		ER()->load_plugin_textdomain();
	}
}

/**
 * Switch easyReservations language to original.
 */
function er_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init ER locale.
		ER()->load_plugin_textdomain();
	}
}

/**
 * Convert plaintext phone number to clickable phone number.
 *
 * Remove formatting and allow "+".
 * Example and specs: https://developer.mozilla.org/en/docs/Web/HTML/Element/a#Creating_a_phone_link
 *
 * @param string $phone Content to convert phone number.
 *
 * @return string Content with converted phone number.
 */
function er_make_phone_clickable( $phone ) {
	$number = trim( preg_replace( '/[^\d|\+]/', '', $phone ) );

	return $number ? '<a href="tel:' . esc_attr( $number ) . '">' . esc_html( $phone ) . '</a>' : '';
}

/**
 * Get an item of post data if set, otherwise return a default value.
 *
 * @param string $key Meta key.
 * @param string $default Default value.
 *
 * @return mixed Value sanitized by er_clean.
 */
function er_get_post_data_by_key( $key, $default = '' ) {
	return er_clean( wp_unslash( er_get_var( $_POST[ $key ], $default ) ) ); // @codingStandardsIgnoreLine
}

/**
 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
 *
 * @param mixed  $var Variable.
 * @param string $default Default value.
 *
 * @return mixed
 */
function er_get_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * Delete expired transients.
 *
 * Deletes all expired transients. The multi-table delete syntax is used.
 * to delete the transient record from table a, and the corresponding.
 * transient_timeout record from table b.
 *
 * Based on code inside core's upgrade_network() function.
 *
 * @return int Number of transients that were cleared.
 */
function er_delete_expired_transients() {
	global $wpdb;

	$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
	$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) ); // WPCS: unprepared SQL ok.

	$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
		AND b.option_value < %d";
	$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) ); // WPCS: unprepared SQL ok.

	return absint( $rows + $rows2 );
}

add_action( 'easyreservations_installed', 'er_delete_expired_transients' );

/**
 * See if theme/s is activate or not.
 *
 * @param string|array $theme Theme name or array of theme names to check.
 *
 * @return boolean
 */
function er_is_active_theme( $theme ) {
	return is_array( $theme ) ? in_array( get_template(), $theme, true ) : get_template() === $theme;
}

/**
 * Is the site using a default WP theme?
 *
 * @return boolean
 */
function er_is_wp_default_theme_active() {
	return er_is_active_theme(
		array(
			'twentytwenty',
			'twentynineteen',
			'twentyseventeen',
			'twentysixteen',
			'twentyfifteen',
			'twentyfourteen',
			'twentythirteen',
			'twentyeleven',
			'twentytwelve',
			'twentyten',
		)
	);
}

/**
 * Cleans up session data - cron callback.
 */
function er_cleanup_session_data() {
	$session_class = apply_filters( 'easyreservations_session_handler', 'ER_Session_Handler' );
	$session       = new $session_class();

	if ( is_callable( array( $session, 'cleanup_sessions' ) ) ) {
		$session->cleanup_sessions();
	}
}

add_action( 'easyreservations_cleanup_sessions', 'er_cleanup_session_data' );

/**
 * Return the html selected attribute if stringified $value is found in array of stringified $options
 * or if stringified $value is the same as scalar stringified $options.
 *
 * @param string|int       $value Value to find within options.
 * @param string|int|array $options Options to go through when looking for value.
 *
 * @return string
 */
function er_selected( $value, $options ) {
	if ( is_array( $options ) ) {
		$options = array_map( 'strval', $options );

		return selected( in_array( (string) $value, $options, true ), true, false );
	}

	return selected( $value, $options, false );
}

/**
 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
 *
 * @param string $group Group of cache to get.
 *
 * @return string
 */
function er_get_cache_prefix( $group ) {
	// Get cache key - uses cache key er_orders_cache_prefix to invalidate when needed.
	$prefix = wp_cache_get( 'er_' . $group . '_cache_prefix', $group );

	if ( false === $prefix ) {
		$prefix = microtime();
		wp_cache_set( 'er_' . $group . '_cache_prefix', $prefix, $group );
	}

	return 'er_cache_' . $prefix . '_';
}

/**
 * Increment group cache prefix (invalidates cache).
 *
 * @param string $group Group of cache to clear.
 */
function er_incr_cache_prefix( $group ) {
	wp_cache_incr( 'er_' . $group . '_cache_prefix', 1, $group );
}

/**
 * Invalidate cache group.
 *
 * @param string $group Group of cache to clear.
 */
function er_invalidate_cache_group( $group ) {
	wp_cache_set( 'er_' . $group . '_cache_prefix', microtime(), $group );
}

/**
 * Get help message
 *
 * @param string $message
 * @param bool   $allow_html
 *
 * @return string
 */
function er_get_help( $message, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = er_sanitize_tooltip( $message );
	} else {
		$tip = esc_attr( $message );
	}

	return '<span class="easyreservations-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Prints out help message
 *
 * @param string $message
 * @param bool   $allow_html
 */
function er_print_help( $message, $allow_html = false ) {
	echo er_get_help( $message, $allow_html );
}

/**
 * Outputs a "back" link so admin screens can easily jump back a page.
 *
 * @param string $label Title of the page to return to.
 * @param string $url URL of the page to return to.
 */
function er_back_link( $label, $url ) {
	echo '<small class="er-admin-breadcrumb"><a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '">&#x2934;</a></small>';
}

/**
 * Retrieves the MySQL server version. Based on $wpdb.
 *
 * @return array Vesion information.
 */
function er_get_server_database_version() {
	global $wpdb;

	if ( empty( $wpdb->is_mysql ) ) {
		return array(
			'string' => '',
			'number' => '',
		);
	}

	if ( $wpdb->use_mysqli ) {
		$server_info = mysqli_get_server_info( $wpdb->dbh ); // @codingStandardsIgnoreLine.
	} else {
		$server_info = mysql_get_server_info( $wpdb->dbh ); // @codingStandardsIgnoreLine.
	}

	return array(
		'string' => $server_info,
		'number' => preg_replace( '/([^\d.]+).*/', '', $server_info ),
	);
}