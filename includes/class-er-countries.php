<?php

/**
 * easyReservations countries
 */

defined( 'ABSPATH' ) || exit;

/**
 * The easyReservations countries class stores country/state data.
 */
class ER_Countries {

	/**
	 * List of countries.
	 *
	 * @var array
	 */
	public $countries = array();

	/**
	 * Locales list.
	 *
	 * @var array
	 */
	public $locale = array();

	/**
	 * List of address formats for locales.
	 *
	 * @var array
	 */
	public $address_formats = array();

	/**
	 * Get all countries.
	 *
	 * @return array
	 */
	public function get_countries() {
		if ( empty( $this->countries ) ) {
			$this->countries = apply_filters( 'easyreservations_countries', include ER()->plugin_path() . '/i18n/countries.php' );
			if ( apply_filters( 'easyreservations_sort_countries', true ) ) {
				uasort( $this->countries, array( $this, 'sort_countries' ) );
			}
		}

		return $this->countries;
	}

	public function sort_countries( $a, $b ) {
		if ( function_exists( 'iconv' ) && defined( 'ICONV_IMPL' ) && @strcasecmp( ICONV_IMPL, 'unknown' ) !== 0 ) {
			$a = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $a );
			$b = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $b );
		}

		return strcmp( $a, $b );
	}

	/**
	 * Get the states for a country.
	 *
	 * @param string $cc Country code.
	 *
	 * @return false|array of states
	 */
	public function get_states( $cc = null ) {
		if ( ! isset( $this->states ) ) {
			$this->states = apply_filters( 'easyreservations_states', include ER()->plugin_path() . '/i18n/states.php' );
		}

		if ( ! is_null( $cc ) ) {
			return isset( $this->states[ $cc ] ) ? $this->states[ $cc ] : false;
		} else {
			return $this->states;
		}
	}

	/**
	 * Gets an array of countries in the EU.
	 *
	 * @return string[]
	 */
	public function get_european_union_countries() {
		$countries = array(
			'AT',
			'BE',
			'BG',
			'CY',
			'CZ',
			'DE',
			'DK',
			'EE',
			'ES',
			'FI',
			'FR',
			'GR',
			'HU',
			'HR',
			'IE',
			'IT',
			'LT',
			'LU',
			'LV',
			'MT',
			'NL',
			'PL',
			'PT',
			'RO',
			'SE',
			'SI',
			'SK'
		);

		return apply_filters( 'easyreservations_european_union_countries', $countries );
	}

	/**
	 * Gets an array of countries using VAT.
	 *
	 * @return string[] of country codes.
	 */
	public function get_vat_countries() {
		$eu_countries  = $this->get_european_union_countries();
		$vat_countries = array(
			'AE',
			'AL',
			'AR',
			'AZ',
			'BB',
			'BH',
			'BO',
			'BS',
			'BY',
			'CL',
			'CO',
			'EC',
			'EG',
			'ET',
			'FJ',
			'GB',
			'GH',
			'GM',
			'GT',
			'IL',
			'IM',
			'IN',
			'IR',
			'KN',
			'KR',
			'KZ',
			'LK',
			'MC',
			'MD',
			'ME',
			'MK',
			'MN',
			'MU',
			'MX',
			'NA',
			'NG',
			'NO',
			'NP',
			'PS',
			'PY',
			'RS',
			'RU',
			'RW',
			'SA',
			'SV',
			'TH',
			'TR',
			'UA',
			'UY',
			'UZ',
			'VE',
			'VN',
			'ZA'
		);

		return apply_filters( 'easyreservations_vat_countries', array_merge( $eu_countries, $vat_countries ) );
	}

	/**
	 * Correctly name tax in some countries VAT on the frontend.
	 *
	 * @return string
	 */
	public function tax_or_vat() {
		$return = in_array( er_get_default_country(), array_merge( $this->get_european_union_countries(), array( 'NO' ), $this->get_vat_countries() ), true ) ? __( 'VAT', 'easyReservations' ) : __( 'Tax', 'easyReservations' );

		return apply_filters( 'easyreservations_countries_tax_or_vat', $return );
	}

	/**
	 * Include the Inc Tax label.
	 *
	 * @return string
	 */
	public function inc_tax_or_vat() {
		$return = in_array( er_get_default_country(), array_merge( $this->get_european_union_countries(), array( 'NO' ), $this->get_vat_countries() ), true ) ? __( '(incl. VAT)', 'easyReservations' ) : __( '(incl. tax)', 'easyReservations' );

		return apply_filters( 'easyreservations_countries_inc_tax_or_vat', $return );
	}

	/**
	 * Include the Ex Tax label.
	 *
	 * @return string
	 */
	public function ex_tax_or_vat() {
		$return = in_array( er_get_default_country(), array_merge( $this->get_european_union_countries(), array( 'NO' ), $this->get_vat_countries() ), true ) ? __( '(ex. VAT)', 'easyReservations' ) : __( '(ex. tax)', 'easyReservations' );

		return apply_filters( 'easyreservations_countries_ex_tax_or_vat', $return );
	}

	/**
	 * Outputs the list of countries and states for use in dropdown boxes.
	 *
	 * @param string $selected_country Selected country.
	 * @param string $selected_state Selected state.
	 * @param bool   $escape If we should escape HTML.
	 */
	public function country_dropdown_options( $selected_country = '', $selected_state = '', $escape = false ) {
		if ( $this->get_countries() ) {
			foreach ( $this->get_countries() as $key => $value ) {
				$states = $this->get_states( $key );
				if ( $states ) {
					echo '<optgroup label="' . esc_attr( $value ) . '">';
					foreach ( $states as $state_key => $state_value ) {
						echo '<option value="' . esc_attr( $key ) . ':' . esc_attr( $state_key ) . '"';

						if ( $selected_country === $key && $selected_state === $state_key ) {
							echo ' selected="selected"';
						}

						echo '>' . esc_html( $value ) . ' &mdash; ' . ( $escape ? esc_js( $state_value ) : $state_value ) . '</option>'; // WPCS: XSS ok.
					}
					echo '</optgroup>';
				} else {
					echo '<option';
					if ( $selected_country === $key && '*' === $selected_state ) {
						echo ' selected="selected"';
					}
					echo ' value="' . esc_attr( $key ) . '">' . ( $escape ? esc_js( $value ) : $value ) . '</option>'; // WPCS: XSS ok.
				}
			}
		}
	}

	/**
	 * Get country address formats.
	 *
	 * These define how addresses are formatted for display in various countries.
	 *
	 * @return array
	 */
	public function get_address_formats() {
		if ( empty( $this->address_formats ) ) {
			$this->address_formats = apply_filters(
				'easyreservations_localisation_address_formats',
				array(
					'default' => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}",
					'AU'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {state} {postcode}\n{country}",
					'AT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'BE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'CA'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {state_code} {postcode}\n{country}",
					'CH'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'CL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{state}\n{postcode} {city}\n{country}",
					'CN'      => "{country} {postcode}\n{state}, {city}, {address_2}, {address_1}\n{company}\n{name}",
					'CZ'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'DE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'EE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'FI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'DK'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'FR'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city_upper}\n{country}",
					'HK'      => "{company}\n{first_name} {last_name_upper}\n{address_1}\n{address_2}\n{city_upper}\n{state_upper}\n{country}",
					'HU'      => "{name}\n{company}\n{city}\n{address_1}\n{address_2}\n{postcode}\n{country}",
					'IN'      => "{company}\n{name}\n{address_1}\n{address_2}\n{city} {postcode}\n{state}, {country}",
					'IS'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'IT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode}\n{city}\n{state_upper}\n{country}",
					'JP'      => "{postcode}\n{state} {city} {address_1}\n{address_2}\n{company}\n{last_name} {first_name}\n{country}",
					'TW'      => "{company}\n{last_name} {first_name}\n{address_1}\n{address_2}\n{state}, {city} {postcode}\n{country}",
					'LI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'NL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'NZ'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city} {postcode}\n{country}",
					'NO'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'PL'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'PT'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'SK'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'RS'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'SI'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'ES'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city}\n{state}\n{country}",
					'SE'      => "{company}\n{name}\n{address_1}\n{address_2}\n{postcode} {city}\n{country}",
					'TR'      => "{name}\n{company}\n{address_1}\n{address_2}\n{postcode} {city} {state}\n{country}",
					'UG'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}, {country}",
					'US'      => "{name}\n{company}\n{address_1}\n{address_2}\n{city}, {state_code} {postcode}\n{country}",
					'VN'      => "{name}\n{company}\n{address_1}\n{city}\n{country}",
				)
			);
		}

		return $this->address_formats;
	}

	/**
	 * Get country address format.
	 *
	 * @param array  $args Arguments.
	 * @param string $separator How to separate address lines.
	 *
	 * @return string
	 */
	public function get_formatted_address( $args = array(), $separator = '<br/>' ) {
		$default_args = array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
		);

		$args    = array_map( 'trim', wp_parse_args( $args, $default_args ) );
		$state   = $args['state'];
		$country = $args['country'];

		// Get all formats.
		$formats = $this->get_address_formats();

		// Get format for the address' country.
		$format = ( $country && isset( $formats[ $country ] ) ) ? $formats[ $country ] : $formats['default'];

		// Handle full country name.
		$full_country = ( isset( $this->countries[ $country ] ) ) ? $this->countries[ $country ] : $country;

		// Country is not needed if the same as base.
		if ( $country === er_get_default_country() && ! apply_filters( 'easyreservations_formatted_address_force_country_display', false ) ) {
			$format = str_replace( '{country}', '', $format );
		}

		// Handle full state name.
		$full_state = ( $country && $state && isset( $this->states[ $country ][ $state ] ) ) ? $this->states[ $country ][ $state ] : $state;

		// Substitute address parts into the string.
		$replace = array_map(
			'esc_html',
			apply_filters(
				'easyreservations_formatted_address_replacements',
				array(
					'{first_name}'       => $args['first_name'],
					'{last_name}'        => $args['last_name'],
					'{name}'             => $args['first_name'] . ' ' . $args['last_name'],
					'{company}'          => $args['company'],
					'{address_1}'        => $args['address_1'],
					'{address_2}'        => $args['address_2'],
					'{city}'             => $args['city'],
					'{state}'            => $full_state,
					'{postcode}'         => $args['postcode'],
					'{country}'          => $full_country,
					'{first_name_upper}' => er_strtoupper( $args['first_name'] ),
					'{last_name_upper}'  => er_strtoupper( $args['last_name'] ),
					'{name_upper}'       => er_strtoupper( $args['first_name'] . ' ' . $args['last_name'] ),
					'{company_upper}'    => er_strtoupper( $args['company'] ),
					'{address_1_upper}'  => er_strtoupper( $args['address_1'] ),
					'{address_2_upper}'  => er_strtoupper( $args['address_2'] ),
					'{city_upper}'       => er_strtoupper( $args['city'] ),
					'{state_upper}'      => er_strtoupper( $full_state ),
					'{state_code}'       => er_strtoupper( $state ),
					'{postcode_upper}'   => er_strtoupper( $args['postcode'] ),
					'{country_upper}'    => er_strtoupper( $full_country ),
				),
				$args
			)
		);

		$formatted_address = str_replace( array_keys( $replace ), $replace, $format );

		// Clean up white space.
		$formatted_address = preg_replace( '/  +/', ' ', trim( $formatted_address ) );
		$formatted_address = preg_replace( '/\n\n+/', "\n", $formatted_address );

		// Break newlines apart and remove empty lines/trim commas and white space.
		$formatted_address = array_filter( array_map( array(
			$this,
			'trim_formatted_address_line'
		), explode( "\n", $formatted_address ) ) );

		// Add html breaks.
		$formatted_address = implode( $separator, $formatted_address );

		// We're done!
		return $formatted_address;
	}

	/**
	 * Trim white space and commas off a line.
	 *
	 * @param string $line Line.
	 *
	 * @return string
	 */
	private function trim_formatted_address_line( $line ) {
		return trim( $line, ', ' );
	}

	/**
	 * Get JS selectors for fields which are shown/hidden depending on the locale.
	 *
	 * @return array
	 */
	public function get_country_locale_field_selectors() {
		$locale_fields = array(
			'address_1' => '#address_1_field',
			'address_2' => '#address_2_field',
			'state'     => '#state_field',
			'postcode'  => '#postcode_field',
			'city'      => '#city_field',
		);

		return apply_filters( 'easyreservations_country_locale_field_selectors', $locale_fields );
	}

	/**
	 * Returns the fields we show by default. This can be filtered later on.
	 *
	 * @return array
	 */
	public function get_default_address_fields() {
		if ( 'optional' === get_option( 'reservations_checkout_address_2_field', 'optional' ) ) {
			$address_2_placeholder = __( 'Apartment, suite, unit etc. (optional)', 'easyReservations' );
		} else {
			$address_2_placeholder = __( 'Apartment, suite, unit etc.', 'easyReservations' );
		}

		$fields = array(
			'first_name' => array(
				'label'        => __( 'First name', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-first' ),
				'autocomplete' => 'given-name',
				'priority'     => 10,
			),
			'last_name'  => array(
				'label'        => __( 'Last name', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-last' ),
				'autocomplete' => 'family-name',
				'priority'     => 20,
			),
			'company'    => array(
				'label'        => __( 'Company name', 'easyReservations' ),
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'organization',
				'priority'     => 30,
				'required'     => 'required' === get_option( 'reservations_checkout_company_field', 'optional' ),
			),
			'country'    => array(
				'type'         => 'country',
				'label'        => __( 'Country / Region', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
				'autocomplete' => 'country',
				'priority'     => 40,
			),
			'address_1'  => array(
				'label'        => __( 'Street address', 'easyReservations' ),
				/* translators: use local order of street name and house number. */
				'placeholder'  => esc_attr__( 'House number and street name', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'autocomplete' => 'address-line1',
				'priority'     => 50,
			),
			'address_2'  => array(
				'placeholder'  => esc_attr( $address_2_placeholder ),
				'class'        => array( 'form-row-wide', 'address-field' ),
				'autocomplete' => 'address-line2',
				'priority'     => 60,
				'required'     => 'required' === get_option( 'reservations_checkout_address_2_field', 'optional' ),
			),
			'city'       => array(
				'label'        => __( 'Town / City', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'autocomplete' => 'address-level2',
				'priority'     => 70,
			),
			'state'      => array(
				'type'         => 'state',
				'label'        => __( 'State / County', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'validate'     => array( 'state' ),
				'autocomplete' => 'address-level1',
				'priority'     => 80,
			),
			'postcode'   => array(
				'label'        => __( 'Postcode / ZIP', 'easyReservations' ),
				'required'     => true,
				'class'        => array( 'form-row-wide', 'address-field' ),
				'validate'     => array( 'postcode' ),
				'autocomplete' => 'postal-code',
				'priority'     => 90,
			),
		);

		if ( 'hidden' === get_option( 'reservations_checkout_company_field', 'optional' ) ) {
			unset( $fields['company'] );
		}

		if ( 'hidden' === get_option( 'reservations_checkout_address_2_field', 'optional' ) ) {
			unset( $fields['address_2'] );
		}

		$default_address_fields = apply_filters( 'easyreservations_default_address_fields', $fields );
		// Sort each of the fields based on priority.
		uasort( $default_address_fields, 'er_checkout_fields_uasort_comparison' );

		return $default_address_fields;
	}

	/**
	 * Get country locale settings.
	 *
	 * These locales override the default country selections after a country is chosen.
	 *
	 * @return array
	 */
	public function get_country_locale() {
		if ( empty( $this->locale ) ) {
			$this->locale = apply_filters(
				'easyreservations_get_country_locale',
				array(
					'AE' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'AF' => array(
						'state' => array(
							'required' => false,
						),
					),
					'AO' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'AT' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'AU' => array(
						'city'     => array(
							'label' => __( 'Suburb', 'easyReservations' ),
						),
						'postcode' => array(
							'label' => __( 'Postcode', 'easyReservations' ),
						),
						'state'    => array(
							'label' => __( 'State', 'easyReservations' ),
						),
					),
					'AX' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'BD' => array(
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'label' => __( 'District', 'easyReservations' ),
						),
					),
					'BE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Province', 'easyReservations' ),
						),
					),
					'BH' => array(
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'BI' => array(
						'state' => array(
							'required' => false,
						),
					),
					'BO' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'BS' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'CA' => array(
						'postcode' => array(
							'label' => __( 'Postal code', 'easyReservations' ),
						),
						'state'    => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'CH' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Canton', 'easyReservations' ),
							'required' => false,
						),
					),
					'CL' => array(
						'city'     => array(
							'required' => true,
						),
						'postcode' => array(
							'required' => false,
						),
						'state'    => array(
							'label' => __( 'Region', 'easyReservations' ),
						),
					),
					'CN' => array(
						'state' => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'CO' => array(
						'postcode' => array(
							'required' => false,
						),
					),
					'CZ' => array(
						'state' => array(
							'required' => false,
						),
					),
					'DE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'DK' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'EE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'FI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'FR' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'GP' => array(
						'state' => array(
							'required' => false,
						),
					),
					'GF' => array(
						'state' => array(
							'required' => false,
						),
					),
					'GR' => array(
						'state' => array(
							'required' => false,
						),
					),
					'HK' => array(
						'postcode' => array(
							'required' => false,
						),
						'city'     => array(
							'label' => __( 'Town / District', 'easyReservations' ),
						),
						'state'    => array(
							'label' => __( 'Region', 'easyReservations' ),
						),
					),
					'HU' => array(
						'state' => array(
							'label' => __( 'County', 'easyReservations' ),
						),
					),
					'ID' => array(
						'state' => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'IE' => array(
						'postcode' => array(
							'required' => false,
							'label'    => __( 'Eircode', 'easyReservations' ),
						),
						'state'    => array(
							'label' => __( 'County', 'easyReservations' ),
						),
					),
					'IS' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'IL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'IM' => array(
						'state' => array(
							'required' => false,
						),
					),
					'IT' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => true,
							'label'    => __( 'Province', 'easyReservations' ),
						),
					),
					'JP' => array(
						'last_name'  => array(
							'class'    => array( 'form-row-first' ),
							'priority' => 10,
						),
						'first_name' => array(
							'class'    => array( 'form-row-last' ),
							'priority' => 20,
						),
						'postcode'   => array(
							'class'    => array( 'form-row-first' ),
							'priority' => 65,
						),
						'state'      => array(
							'label'    => __( 'Prefecture', 'easyReservations' ),
							'class'    => array( 'form-row-last' ),
							'priority' => 66,
						),
						'city'       => array(
							'priority' => 67,
						),
						'address_1'  => array(
							'priority' => 68,
						),
						'address_2'  => array(
							'priority' => 69,
						),
					),
					'KR' => array(
						'state' => array(
							'required' => false,
						),
					),
					'KW' => array(
						'state' => array(
							'required' => false,
						),
					),
					'LV' => array(
						'state' => array(
							'label'    => __( 'Municipality', 'easyReservations' ),
							'required' => false,
						),
					),
					'LB' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MQ' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MZ' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'NL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Province', 'easyReservations' ),
						),
					),
					'NG' => array(
						'postcode' => array(
							'label'    => __( 'Postcode', 'easyReservations' ),
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'State', 'easyReservations' ),
						),
					),
					'NZ' => array(
						'postcode' => array(
							'label' => __( 'Postcode', 'easyReservations' ),
						),
						'state'    => array(
							'required' => false,
							'label'    => __( 'Region', 'easyReservations' ),
						),
					),
					'NO' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'NP' => array(
						'state'    => array(
							'label' => __( 'State / Zone', 'easyReservations' ),
						),
						'postcode' => array(
							'required' => false,
						),
					),
					'PL' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'PT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'RE' => array(
						'state' => array(
							'required' => false,
						),
					),
					'RO' => array(
						'state' => array(
							'label'    => __( 'County', 'easyReservations' ),
							'required' => true,
						),
					),
					'RS' => array(
						'state' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'SG' => array(
						'state' => array(
							'required' => false,
						),
						'city'  => array(
							'required' => false,
						),
					),
					'SK' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'SI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'SR' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'ES' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'LI' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label'    => __( 'Municipality', 'easyReservations' ),
							'required' => false,
						),
					),
					'LK' => array(
						'state' => array(
							'required' => false,
						),
					),
					'LU' => array(
						'state' => array(
							'required' => false,
						),
					),
					'MD' => array(
						'state' => array(
							'label' => __( 'Municipality / District', 'easyReservations' ),
						),
					),
					'SE' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'required' => false,
						),
					),
					'TR' => array(
						'postcode' => array(
							'priority' => 65,
						),
						'state'    => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'UG' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'city'     => array(
							'label'    => __( 'Town / Village', 'easyReservations' ),
							'required' => true,
						),
						'state'    => array(
							'label'    => __( 'District', 'easyReservations' ),
							'required' => true,
						),
					),
					'US' => array(
						'postcode' => array(
							'label' => __( 'ZIP', 'easyReservations' ),
						),
						'state'    => array(
							'label' => __( 'State', 'easyReservations' ),
						),
					),
					'GB' => array(
						'postcode' => array(
							'label' => __( 'Postcode', 'easyReservations' ),
						),
						'state'    => array(
							'label'    => __( 'County', 'easyReservations' ),
							'required' => false,
						),
					),
					'ST' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
						'state'    => array(
							'label' => __( 'District', 'easyReservations' ),
						),
					),
					'VN' => array(
						'state'     => array(
							'required' => false,
							'hidden'   => true,
						),
						'postcode'  => array(
							'priority' => 65,
							'required' => false,
							'hidden'   => false,
						),
						'address_2' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'WS' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
					'YT' => array(
						'state' => array(
							'required' => false,
						),
					),
					'ZA' => array(
						'state' => array(
							'label' => __( 'Province', 'easyReservations' ),
						),
					),
					'ZW' => array(
						'postcode' => array(
							'required' => false,
							'hidden'   => true,
						),
					),
				)
			);

			$this->locale = array_intersect_key( $this->locale, $this->get_countries() );

			// Default Locale Can be filtered to override fields in get_address_fields(). Countries with no specific locale will use default.
			$this->locale['default'] = apply_filters( 'easyreservations_get_country_locale_default', $this->get_default_address_fields() );

			$base_country = er_get_default_country();

			// Filter default AND shop base locales to allow overides via a single function. These will be used when changing countries on the checkout.
			if ( ! isset( $this->locale[ $base_country ] ) ) {
				$this->locale[ $base_country ] = $this->locale['default'];
			}

			$this->locale['default']       = apply_filters( 'easyreservations_get_country_locale_base', $this->locale['default'] );
			$this->locale[ $base_country ] = apply_filters( 'easyreservations_get_country_locale_base', $this->locale[ $base_country ] );
		}

		return $this->locale;
	}

	/**
	 * Apply locale and get address fields.
	 *
	 * @param mixed $country Country.
	 *
	 * @return array
	 */
	public function get_address_fields( $country = '' ) {
		if ( ! $country ) {
			$country = er_get_default_country();
		}

		$fields = $this->get_default_address_fields();
		$locale = $this->get_country_locale();

		if ( isset( $locale[ $country ] ) ) {
			$fields = er_array_overlay( $fields, $locale[ $country ] );
		}

		// Prepend field keys.
		$address_fields = array();

		foreach ( $fields as $key => $value ) {
			if ( 'state' === $key ) {
				$value['country'] = $country;
			}
			$address_fields[ $key ] = $value;
		}

		// Add email and phone fields.
		if ( 'hidden' !== get_option( 'reservations_checkout_phone_field', 'required' ) ) {
			$address_fields['phone'] = array(
				'label'        => __( 'Phone', 'easyReservations' ),
				'required'     => 'required' === get_option( 'reservations_checkout_phone_field', 'required' ),
				'type'         => 'tel',
				'class'        => array( 'form-row-wide' ),
				'validate'     => array( 'phone' ),
				'autocomplete' => 'tel',
				'priority'     => 100,
			);
		}
		$address_fields['email'] = array(
			'label'        => __( 'Email address', 'easyReservations' ),
			'required'     => true,
			'type'         => 'email',
			'class'        => array( 'form-row-wide' ),
			'validate'     => array( 'email' ),
			'autocomplete' => 'no' === get_option( 'reservations_registration_generate_username' ) ? 'email' : 'email username',
			'priority'     => 110,
		);

		/**
		 * Important note on this filter: Changes to address fields can and will be overridden by
		 * the easyreservations_default_address_fields. The locales/default locales apply on top based
		 * on country selection. If you want to change things like the required status of an
		 * address field, filter easyreservations_default_address_fields instead.
		 */
		$address_fields = apply_filters( 'easyreservations_address_fields', $address_fields, $country );
		// Sort each of the fields based on priority.
		uasort( $address_fields, 'er_checkout_fields_uasort_comparison' );

		return $address_fields;
	}
}
