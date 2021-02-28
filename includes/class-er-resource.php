<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Resource {
	protected $ID;
	protected $post_title = '';
	protected $post_name = '';
	protected $post_content = '';
	protected $post_excerpt = '';
	protected $post_password = '';
	protected $post_status = '';
	protected $menu_order = 0;
	protected $interval = 86400;
	protected $frequency = 86400;
	protected $quantity = 1;
	protected $base_price = 0;
	protected $children_price = 0;
	protected $billing_method = 0;
	protected $per_person = 0;
	protected $once = 0;
	protected $requirements = null;
	protected $availability_by = 'unit';
	protected $catalog_visibility = 'hidden';
	protected $featured = false;
	protected $onsale = false;
	protected $filter = false;
	protected $slots = false;

	/**
	 * ER_Resource constructor.
	 *
	 * @param WP_Post $post_data
	 */
	public function __construct( $post_data ) {
		$this->ID            = intval( $post_data->ID );
		$this->post_title    = $post_data->post_title;
		$this->menu_order    = $post_data->menu_order;
		$this->post_name     = $post_data->post_name;
		$this->post_content  = $post_data->post_content;
		$this->post_excerpt  = $post_data->post_excerpt;
		$this->post_password = $post_data->post_password;
		$this->post_status   = $post_data->post_status;

		$this->interval       = absint( get_post_meta( $this->get_id(), 'easy-resource-interval', true ) );
		$this->interval       = max( 1800, $this->interval );
		$this->base_price     = floatval( get_post_meta( $this->get_id(), 'reservations_groundprice', true ) );
		$this->children_price = floatval( get_post_meta( $this->get_id(), 'reservations_child_price', true ) );
		$this->billing_method = intval( get_post_meta( $this->get_id(), 'easy-resource-billing-method', true ) );
		$this->requirements   = get_post_meta( $this->get_id(), 'easy-resource-req', true );
		$this->frequency      = intval( get_post_meta( $this->get_id(), 'er_resource_frequency', true ) );

		if ( empty( $this->frequency ) ) {
			$this->frequency = min( DAY_IN_SECONDS, $this->get_billing_interval() );
		}

		$slots = er_clean( get_post_meta( $this->get_id(), 'easy-resource-slots', true ) );
		if ( $slots && ! empty( $slots ) && is_array( $slots ) ) {
			foreach ( $slots as $key => $slot ) {
				if ( is_numeric( $slot['range-from'] ) ) {
					$slots[ $key ]['range-from'] = new ER_DateTime( '@' . $slot['range-from'] );
				} else {
					$slots[ $key ]['range-from'] = new ER_DateTime( $slot['range-from'] );
				}

				if ( is_numeric( $slot['range-to'] ) ) {
					$slots[ $key ]['range-to'] = new ER_DateTime( '@' . $slot['range-to'] );
				} else {
					$slots[ $key ]['range-to'] = new ER_DateTime( $slot['range-to'] );
				}
			}

			$this->slots = $slots;
		}

		$all_filter = er_clean( get_post_meta( $this->get_id(), 'easy_res_filter', true ) );
		if ( $all_filter && ! empty( $all_filter ) && is_array( $all_filter ) ) {
			foreach ( $all_filter as $key => $filter ) {
				if ( isset( $filter['from'] ) ) {
					if ( is_numeric( $filter['from'] ) ) {
						$all_filter[ $key ]['from'] = new ER_DateTime( '@' . $filter['from'] );
					} else {
						$all_filter[ $key ]['from'] = new ER_DateTime( $filter['from'] );
					}
				}

				if ( isset( $filter['to'] ) ) {
					if ( is_numeric( $filter['to'] ) ) {
						$all_filter[ $key ]['to'] = new ER_DateTime( '@' . $filter['to'] );
					} else {
						$all_filter[ $key ]['to'] = new ER_DateTime( $filter['to'] );
					}
				}
			}

			$this->filter = $all_filter;
		}

		$quantity = get_post_meta( $this->get_id(), 'roomcount', true );
		if ( is_array( $quantity ) ) {
			$this->availability_by = sanitize_key( isset( $quantity[1] ) ? $quantity[1] : 'unit' );
			$quantity              = intval( $quantity[0] );
		}

		$this->quantity = is_numeric( $quantity ) ? absint( $quantity ) : 1;

		$price_rules = get_post_meta( $this->get_id(), 'easy-resource-price', true );
		if ( ! $price_rules || ! is_array( $price_rules ) ) {
			$price_rules = array( intval( $price_rules ), 0 );
		}

		$this->per_person = intval( $price_rules[0] );
		$this->once       = intval( $price_rules[1] );

		$this->read_visibility();
	}

	/**
	 * Convert visibility terms to props.
	 * Catalog visibility valid values are 'visible', 'catalog', 'search', and 'hidden'.
	 */
	protected function read_visibility() {
		$terms           = get_the_terms( $this->get_id(), 'resource_visibility' );
		$term_names      = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
		$featured        = in_array( 'featured', $term_names );
		$onsale          = in_array( 'onsale', $term_names );
		$exclude_search  = in_array( 'exclude-from-search', $term_names );
		$exclude_catalog = in_array( 'exclude-from-catalog', $term_names );

		if ( $exclude_search && $exclude_catalog ) {
			$catalog_visibility = 'hidden';
		} elseif ( $exclude_search ) {
			$catalog_visibility = 'catalog';
		} elseif ( $exclude_catalog ) {
			$catalog_visibility = 'search';
		} else {
			$catalog_visibility = 'visible';
		}

		$this->set_catalog_visibility( $catalog_visibility );
		$this->set_featured( $featured );
		$this->set_onsale( $onsale );
	}

	public function get_id() {
		return intval( $this->ID );
	}

	public function get_data() {
		return array(
			'ID'              => $this->get_id(),
			'post_title'      => $this->get_title(),
			'interval'        => $this->get_billing_interval(),
			'frequency'       => $this->get_frequency(),
			'quantity'        => $this->get_quantity(),
			'spaces'          => $this->get_spaces_options(),
			'menu_order'      => intval( $this->menu_order ),
			'slots'           => $this->get_slots(),
			'availability_by' => $this->availability_by(),
		);
	}

	/**
	 * Get default base price
	 *
	 * @return float
	 */
	public function get_base_price() {
		return $this->base_price;
	}

	/**
	 * Get default children price
	 *
	 * @return float
	 */
	public function get_children_price() {
		return $this->children_price;
	}

	public function get_quantity() {
		return $this->quantity;
	}

	public function get_requirements() {
		if ( ! $this->requirements ) {
			return array(
				'nights-min' => intval( 1 ),
				'nights-max' => intval( 30 ),
				'pers-min'   => intval( 1 ),
				'pers-max'   => intval( 30 ),
				'start-on'   => 0,
				'end-on'     => 0
			);
		}

		return $this->requirements;
	}

	public function get_billing_interval() {
		return $this->interval;
	}

	public function get_billing_method() {
		return $this->billing_method;
	}

	public function get_frequency() {
		return $this->frequency;
	}

	public function get_title() {
		return stripslashes( __( $this->post_title ) );
	}

	public function get_post_name() {
		return $this->post_name;
	}

	public function get_excerpt() {
		return $this->post_excerpt;
	}

	public function get_post_password() {
		return $this->post_password;
	}

	public function get_content() {
		return $this->post_content;
	}

	/**
	 * Get resource status.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->post_status;
	}

	public function bill_only_once() {
		return $this->once > 0;
	}

	public function bill_per_person() {
		return $this->per_person > 0;
	}

	public function get_possible_arrival_time() {
		return isset( $this->requirements['start-h'] ) && is_array( $this->requirements['start-h'] ) ? array_map( 'intval', $this->requirements['start-h'] ) : array( 0, 23 );
	}

	public function get_possible_departure_time() {
		return isset( $this->requirements['end-h'] ) && is_array( $this->requirements['end-h'] ) ? array_map( 'intval', $this->requirements['end-h'] ) : array( 0, 23 );
	}

	/**
	 * Get default arrival time
	 *
	 * @param bool $as_seconds
	 *
	 * @return int
	 */
	public function get_default_arrival_time( $as_seconds = false ) {
		$possible_arrival_time = $this->get_possible_arrival_time();
		$default_arrival_hour  = apply_filters( 'easyreservations_resource_default_arrival_hour', $possible_arrival_time[1] );

		if ( $as_seconds ) {
			return $default_arrival_hour * HOUR_IN_SECONDS;
		}

		return $default_arrival_hour;
	}

	/**
	 * Get default departure time
	 *
	 * @param bool $as_seconds
	 *
	 * @return int
	 */
	public function get_default_departure_time( $as_seconds = false ) {
		$possible_departure_time = $this->get_possible_departure_time();
		$default_departure_hour  = apply_filters( 'easyreservations_resource_default_departure_hour', $possible_departure_time[0] );

		if ( $as_seconds ) {
			return $default_departure_hour * HOUR_IN_SECONDS;
		}

		return $default_departure_hour;
	}

	/**
	 * Get availability by (unit/persons/adults/children)
	 *
	 * @param bool|string $check check value
	 *
	 * @return bool|string
	 */
	public function availability_by( $check = false ) {
		if ( $check ) {
			return $check === $this->availability_by;
		}

		return $this->availability_by;
	}

	/**
	 * Get all slots
	 *
	 * @return array|bool
	 */
	public function get_slots() {
		return $this->slots;
	}

	/**
	 * Get a slot
	 *
	 * @param bool $id
	 *
	 * @return array|bool
	 */
	public function get_slot( $id = false ) {
		if ( $id !== false ) {
			if ( isset( $this->slots[ $id ] ) ) {
				return $this->slots[ $id ];
			}
		} else {
			return $this->slots;
		}

		return false;
	}

	/**
	 * Resource permalink.
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Get filters
	 *
	 * @return bool|array
	 */
	public function get_filter() {
		return $this->filter;
	}

	/**
	 * Get main resource image id
	 *
	 * @return int
	 */
	public function get_image_id() {
		return apply_filters( 'easyreservations_resource_get_image_id', get_post_thumbnail_id( $this->get_id() ), $this );
	}

	/**
	 * Get main resource image html
	 *
	 * @param string $size
	 * @param array  $attr
	 * @param bool   $placeholder
	 *
	 * @return string
	 */
	public function get_image( $size = 'easyreservations_thumbnail', $attr = array(), $placeholder = true ) {
		$image = get_the_post_thumbnail( $this->get_id(), $size, $attr );

		if ( ! $image && $placeholder ) {
			$image = er_placeholder_img( $size, $attr );
		}

		return apply_filters( 'easyreservations_resource_get_image', $image, $this, $size, $attr, $placeholder, $image );
	}

	/**
	 * Returns the gallery attachment ids.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array
	 */
	public function get_gallery_image_ids( $context = 'view' ) {
		$meta = get_post_meta( $this->get_id(), 'gallery_image_ids', true );

		return array_map( 'intval', $meta ? $meta : array() );
	}

	/**
	 * Get catalog visibility.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_catalog_visibility( $context = 'view' ) {
		return $this->catalog_visibility;
	}

	/**
	 * Get featured.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_featured( $context = 'view' ) {
		return $this->featured;
	}

	/**
	 * Get onsale.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function is_on_sale( $context = 'view' ) {
		return $this->onsale;
	}

	/**
	 * Get sku.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_sku( $context = 'view' ) {
		$meta = get_post_meta( $this->get_id(), 'sku', true );

		return $meta;
	}

	/**
	 * Get purchase note.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_purchase_note( $context = 'view' ) {
		$meta = get_post_meta( $this->get_id(), 'purchase_note', true );

		return $meta;
	}

	/**
	 * Returns the form template to be used in frontend.
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_form_template( $context = 'view' ) {
		$meta = get_post_meta( $this->get_id(), 'form_template', true );

		return $meta ? sanitize_key( $meta ) : '';
	}

	/**
	 * Check wether a slot has been defined
	 *
	 * @param bool $id
	 *
	 * @return bool
	 */
	public function has_slot( $id = false ) {
		if ( $id ) {
			if ( isset( $this->slots[ $id ] ) ) {
				return true;
			}
		} else {
			return $this->slots ? true : false;
		}

		return false;
	}

	/**
	 * Get current price
	 *
	 * @return float
	 */
	public function get_price() {
		if ( ! empty( $this->filter ) ) {
			foreach ( $this->filter as $num => $filter ) {
				if ( $filter['type'] === 'price' ) {
					$current_time = er_get_datetime();

					if ( $this->filter( $filter, $current_time, 1, 1, 0, $current_time ) ) {
						return floatval( $filter['price'] );
					}
				} else {
					break;
				}
			}
		}

		return $this->get_base_price();
	}

	/**
	 * Get billing units
	 *
	 * @param ER_DateTime      $arrival
	 * @param ER_DateTime|bool $departure
	 * @param bool             $interval
	 * @param bool             $billing_method
	 *
	 * @return int
	 */
	public function get_billing_units( $arrival, $departure, $interval = false, $billing_method = false ) {
		$interval       = $interval ? $interval : $this->get_billing_interval();
		$billing_method = $billing_method ? $billing_method : $this->get_billing_method();

		$number = ( $departure->getTimestamp() - $arrival->getTimestamp() ) / $interval;

		if ( $billing_method == 0 ) {
			$times = is_numeric( $number ) ? ceil( ceil( $number / 0.01 ) * 0.01 ) : false;
		} elseif ( $billing_method == 3 ) {
			$start_day = clone $arrival;
			$end_day   = clone $departure;
			$start_day->setTime( 0, 0, 0 );
			$end_day->setTime( 0, 0, 0 );
			$times = intval( $end_day->diff( $start_day )->format( "%a" ) );
		} else {
			$times = floor( $number );
		}

		//At least one billing unit regardless of length
		return $times < 1 ? 1 : $times;
	}

	/**
	 * Get frequency units
	 *
	 * @param ER_DateTime      $arrival
	 * @param ER_DateTime|bool $departure
	 * @param bool             $interval
	 *
	 * @return int
	 */
	public function get_frequency_units( $arrival, $departure, $interval = false ) {
		$interval = $interval ? $interval : $this->get_frequency();

		$number = ( $departure->getTimestamp() - $arrival->getTimestamp() ) / $interval;

		$times = floor( $number );

		//At least one frequency unit regardless of length
		return $times < 1 ? 1 : $times;
	}

	/**
	 * Get name of a space
	 *
	 * @param int $space
	 *
	 * @return int|string
	 */
	public function get_space_name( $space ) {
		$space = intval( $space ) - 1;

		if ( $space < 0 ) {
			return $space + 1;
		}

		$resource_space_names = get_post_meta( $this->get_id(), 'easy-resource-roomnames', true );

		if ( isset( $resource_space_names[ $space ] ) && ! empty( $resource_space_names[ $space ] ) ) {
			return __( $resource_space_names[ $space ] );
		} else {
			return $space + 1;
		}
	}

	/**
	 * Array of space options
	 *
	 * @param bool $add_resource_to_value
	 *
	 * @return array
	 */
	public function get_spaces_options( $add_resource_to_value = false ) {
		$resource_space_names = get_post_meta( $this->get_id(), 'easy-resource-roomnames', true );
		$options              = array();

		for ( $i = 0; $i < $this->get_quantity(); $i ++ ) {
			$name  = isset( $resource_space_names[ $i ] ) && ! empty( $resource_space_names[ $i ] ) ? __( sanitize_text_field( $resource_space_names[ $i ] ) ) : $i + 1;
			$value = ( $add_resource_to_value ? $this->get_id() . '-' : '' ) . ( $i + 1 );

			$options[ $value ] = addslashes( $name );
		}

		return $options;
	}

	/**
	 * Get the suffix to display after prices > 0.
	 *
	 * @param string  $price to calculate, left blank to just use get_price().
	 * @param integer $qty passed on to get_price_including_tax() or get_price_excluding_tax().
	 *
	 * @return string
	 */
	public function get_price_suffix( $price = '', $qty = 1 ) {
		$html = '';

		if ( ( $suffix = get_option( 'reservations_price_display_suffix' ) ) && er_tax_enabled() ) { // @phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found, WordPress.CodeAnalysis.AssignmentInCondition.Found
			if ( '' === $price ) {
				$price = $this->get_price();
			}

			$replacements = array(
				'{price_including_tax}' => er_price( er_get_price_including_tax( $this, $price ) ),
				'{price_excluding_tax}' => er_price( er_get_price_excluding_tax( $this, $price ) )
			);

			$html = str_replace( array_keys( $replacements ), array_values( $replacements ), ' <small class="easyreservations-price-suffix">' . wp_kses_post( $suffix ) . '</small>' );
		}

		return apply_filters( 'easyreservations_get_price_suffix', $html, $this, $price, $qty );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @return string
	 */
	public function get_price_html() {
		$price = $this->get_price();

		if ( '' === $price ) {
			$price = apply_filters( 'easyreservations_empty_price_html', '', $this );
		} else {
			$price = er_price( er_get_price_to_display( $this, $price ), true ) . $this->get_price_suffix();
		}

		return apply_filters( 'easyreservations_get_price_html', $price, $this );
	}

	public function get_option_name( $option ) {
		$options = array(
			'interval'       => 'easy-resource-interval',
			'requirements'   => 'easy-resource-req',
			'children_price' => 'reservations_child_price',
			'billing_method' => 'easy-resource-billing-method',
			'base_price'     => 'reservations_groundprice',
			'filter'         => 'easy_res_filter',
			'slots'          => 'easy-resource-slots',
			'frequency'      => 'er_resource_frequency'
		);

		return $options[ $option ];
	}

	/**
	 * Returns whether or not the resource is visible in the catalog.
	 *
	 * @return bool
	 */
	public function is_visible() {
		$visible = 'visible' === $this->get_catalog_visibility() || ( is_search() && 'search' === $this->get_catalog_visibility() ) || ( ! is_search() && 'catalog' === $this->get_catalog_visibility() );

		if ( 'trash' === $this->get_status() ) {
			$visible = false;
		} elseif ( 'publish' !== $this->get_status() && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$visible = false;
		}

		return apply_filters( 'easyreservations_resource_is_visible', $visible, $this->get_id() );
	}

	/**
	 * Set resources title
	 *
	 * @param string $title
	 */
	public function set_title( $title ) {
		$this->post_title = sanitize_title( $title );
	}

	/**
	 * Set the gallery attachment ids.
	 *
	 * @param array $attachment_ids
	 */
	public function set_gallery_image_ids( $attachment_ids ) {
		update_post_meta( $this->get_id(), 'gallery_image_ids', $attachment_ids );
	}

	/**
	 * Set catalog visibility.
	 *
	 * @param string $catalog_visibility
	 */
	public function set_catalog_visibility( $catalog_visibility ) {
		$this->catalog_visibility = sanitize_text_field( $catalog_visibility );
	}

	/**
	 * Set featured.
	 *
	 * @param string $featured
	 */
	public function set_featured( $featured ) {
		$this->featured = (bool) $featured;
	}

	/**
	 * Set onsale.
	 *
	 * @param string $onsale
	 */
	public function set_onsale( $onsale ) {
		$this->onsale = (bool) $onsale;
	}

	/**
	 * Set resources content
	 *
	 * @param string $content
	 */
	public function set_content( $content ) {
		$this->post_content = wp_kses_post( $content );
	}

	/**
	 * Is given day possible for arrival
	 *
	 * @param int $day
	 *
	 * @return bool
	 */
	public function is_possible_arrival_day( $day ) {
		$req = isset( $this->requirements['start-on'] ) ? $this->requirements['start-on'] : 0;

		return $req === 0 || ( is_array( $req ) && in_array( $day, $req ) );
	}

	/**
	 * Is given day possible for departure
	 *
	 * @param int $day
	 *
	 * @return bool
	 */
	public function is_possible_departure_day( $day ) {
		$req = isset( $this->requirements['end-on'] ) ? $this->requirements['end-on'] : 0;

		return $req === 0 || ( is_array( $req ) && in_array( $day, $req ) );
	}

	/**
	 * @param array       $filter
	 * @param ER_DateTime $arrival
	 * @param int         $billing_units
	 * @param int         $adults
	 * @param int         $children
	 * @param bool        $reserved
	 * @param bool        $full
	 *
	 * @return bool
	 */
	public function filter( $filter, $arrival, $billing_units, $adults, $children, $reserved = false, $full = false ) {
		if ( $filter['type'] == 'price' ) {
			if ( isset( $filter['cond'] ) ) {
				$time_cond = 'cond';
			}

			if ( isset( $filter['basecond'] ) ) {
				$cond_cond = 'basecond';
			}

			if ( isset( $filter['condtype'] ) ) {
				$cond_type = 'condtype';
			}
		} elseif ( $filter['type'] == 'req' || $filter['type'] == 'unavail' ) {
			return false;
		} else {
			if ( isset( $filter['timecond'] ) ) {
				$time_cond = 'timecond';
			}
			if ( isset( $filter['cond'] ) ) {
				$cond_cond = 'cond';
			}
			if ( isset( $filter['type'] ) ) {
				$cond_type = 'type';
			}
		}

		if ( isset( $cond_cond ) && isset( $cond_type ) ) {
			$discount_add = 0;
			if ( ! $full || empty( $full ) || ( is_array( $full ) && ! in_array( $filter[ $cond_type ], $full ) ) ) {
				if ( $filter[ $cond_type ] == 'stay' ) {
					if ( (int) $filter[ $cond_cond ] <= (int) $billing_units ) {
						$discount_add = 1;
					}
				} elseif ( $filter[ $cond_type ] == 'pers' ) {
					if ( $filter[ $cond_cond ] <= ( $adults + $children ) ) {
						$discount_add = 1;
					}
				} elseif ( $filter[ $cond_type ] == 'adul' ) {
					if ( $filter[ $cond_cond ] <= $adults ) {
						$discount_add = 1;
					}
				} elseif ( $filter[ $cond_type ] == 'child' ) {
					if ( $filter[ $cond_cond ] <= $children ) {
						$discount_add = 1;
					}
				} elseif ( $filter[ $cond_type ] == 'early' ) {// Early Bird Discount Filter
					if ( ! $reserved ) {
						$reserved = er_get_datetime();
					}

					$days_between = ER_Number_Util::round( ( $arrival->getTimestamp() - $reserved->getTimestamp() ) / $this->get_billing_interval(), 2 );
					if ( $filter[ $cond_cond ] <= $days_between ) {
						$discount_add = 1;
					}
				}
			}

			if ( $discount_add == 0 ) {
				return false;
			}
		}

		$use_filter = false;
		if ( $filter['type'] == 'price' ) {

		} elseif ( isset( $time_cond ) ) {
			if ( $this->time_condition( $filter, $arrival, $time_cond ) ) {
				$use_filter = true;
			}

			return $use_filter;
		}

		return true;
	}

	/**
	 * @param ER_DateTime $time
	 * @param int         $billing_units
	 * @param int         $quantity
	 * @param int         $mode
	 *
	 * @return array|int|string
	 */
	public function availability_filter( $time, $billing_units, $quantity = 1, $mode = 0 ) {
		if ( $mode == 1 ) {
			$error = array();
		} else {
			$error = 0;
		}

		if ( ! empty( $this->filter ) ) {
			foreach ( $this->filter as $filter ) {
				if ( $filter['type'] == 'unavail' ) {
					for ( $i = 0; $i < $billing_units; $i ++ ) {
						$date  = er_date_add_seconds( $time, $i * $this->get_billing_interval() );
						$check = $this->time_condition( $filter, $date );

						if ( $check ) {
							if ( $mode == 1 && is_string( $check ) ) {
								$error .= $check;
							} elseif ( $mode == 1 ) {
								$error[] = $date;
							} else {
								$error += $quantity;
							}
						}
					}
				}
			}
		}

		return $error;
	}

	/**
	 * @param array       $filter
	 * @param ER_DateTime $time
	 * @param string      $cond
	 *
	 * @return bool
	 */
	public function time_condition( $filter, $time, $cond = 'cond' ) {
		if ( $filter[ $cond ] == 'unit' ) {
			if ( ! $this->unit_condition( $filter, $time ) ) {
				return false;
			}
		}

		if ( isset( $filter['from'] ) ) {
			if ( isset( $filter['every'] ) ) {

				$from = $filter['from']->format( 'm-d' );
				$to   = $filter['to']->format( 'm-d' );
				$time = $time->format( 'm-d' );

				$start_ts = strtotime( '2000-' . $from );
				$end_ts   = strtotime( '2000-' . $to );
				$test_ts  = strtotime( '2000-' . $time );

				if ( $start_ts > $end_ts ) {
					$end_ts = strtotime( '2001-' . $to );
					if ( $time < $from ) {
						$test_ts = strtotime( '2001-' . $time );
					}
				}

				return $test_ts >= $start_ts && $test_ts <= $end_ts;
			}

			if ( $time >= $filter['from'] && $time <= $filter['to'] ) {
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * @param array       $filter
	 * @param ER_DateTime $time
	 *
	 * @return bool
	 */
	private function unit_condition( $filter, $time ) {
		if ( ! isset( $filter['year'] ) || empty( $filter['year'] ) || in_array( $time->format( "Y" ), explode( ",", $filter['year'] ) ) ) {
			if ( ! isset( $filter['quarter'] ) || empty( $filter['quarter'] ) || in_array( ceil( $time->format( "m" ) / 3 ), explode( ",", $filter['quarter'] ) ) ) {
				if ( ! isset( $filter['month'] ) || empty( $filter['month'] ) || in_array( $time->format( "n" ), explode( ",", $filter['month'] ) ) ) {
					if ( ! isset( $filter['cw'] ) || empty( $filter['cw'] ) || in_array( $time->format( "W" ), explode( ",", $filter['cw'] ) ) ) {
						if ( ! isset( $filter['day'] ) || empty( $filter['day'] ) || in_array( $time->format( "N" ), explode( ",", $filter['day'] ) ) ) {
							if ( ! isset( $filter['hour'] ) || empty( $filter['hour'] ) || in_array( $time->format( "H" ), explode( ",", $filter['hour'] ) ) ) {
								return true;
							}
						}
					}
				}
			}
		}

		return false;
	}

	public function add( $data = array( 'all' ) ) {
		if ( ! empty( $this->post_title ) ) {
			$resource = array(
				'post_title'   => $this->get_title(),
				'post_content' => $this->get_content(),
				'post_excerpt' => $this->get_excerpt(),
				'menu_order'   => $this->menu_order,
				'post_status'  => 'private',
				'post_type'    => 'easy-rooms'
			);

			$this->ID = wp_insert_post( $resource );
			if ( $this->get_id() > 0 ) {
				$all = array(
					'requirements',
					'children_price',
					'billing_method',
					'base_price',
					'permission',
					'filter',
					'interval',
					'frequency',
					'slots'
				);
				if ( ! is_array( $data ) ) {
					$data = array( $data );
				}
				if ( in_array( 'all', $data ) ) {
					$data = $all;
				}
				foreach ( $data as $dat ) {
					if ( isset( $this->$dat ) ) {
						add_post_meta( $this->get_id(), $this->get_option_name( $dat ), $this->$dat );
					}
				}
			} else {
				throw new Exception( 'Resource could  not be created' );
			}
		} else {
			throw new Exception( 'Resource could not be created - empty title' );
		}

		return false;
	}
}

?>