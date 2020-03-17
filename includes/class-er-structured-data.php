<?php
/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @package easyReservations/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Structured data class.
 */
class ER_Structured_Data {

	/**
	 * Stores the structured data.
	 *
	 * @var array $_data Array of structured data.
	 */
	private $_data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Generate structured data.
		add_action( 'easyreservations_before_main_content', array( $this, 'generate_website_data' ), 30 );
		add_action( 'easyreservations_single_resource_summary', array( $this, 'generate_resource_data' ), 60 );
		add_action( 'easyreservations_email_order_details', array( $this, 'generate_order_data' ), 20, 3 );

		// Output structured data.
		add_action( 'easyreservations_email_order_details', array( $this, 'output_email_structured_data' ), 30, 3 );
		add_action( 'wp_footer', array( $this, 'output_structured_data' ), 10 );
	}

	/**
	 * Sets data.
	 *
	 * @param array $data Structured data.
	 * @param bool  $reset Unset data (default: false).
	 *
	 * @return bool
	 */
	public function set_data( $data, $reset = false ) {
		if ( ! isset( $data['@type'] ) || ! preg_match( '|^[a-zA-Z]{1,20}$|', $data['@type'] ) ) {
			return false;
		}

		if ( $reset && isset( $this->_data ) ) {
			unset( $this->_data );
		}

		$this->_data[] = $data;

		return true;
	}

	/**
	 * Gets data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->_data;
	}

	/**
	 * Structures and returns data.
	 *
	 * List of types available by default for specific request:
	 *
	 * 'resource',
	 * 'website',
	 * 'order',
	 *
	 * @param array $types Structured data types.
	 *
	 * @return array
	 */
	public function get_structured_data( $types ) {
		$data = array();

		// Put together the values of same type of structured data.
		foreach ( $this->get_data() as $value ) {
			$data[ strtolower( $value['@type'] ) ][] = $value;
		}

		// Wrap the multiple values of each type inside a graph... Then add context to each type.
		foreach ( $data as $type => $value ) {
			$data[ $type ] = count( $value ) > 1 ? array( '@graph' => $value ) : $value[0];
			$data[ $type ] = apply_filters( 'easyreservations_structured_data_context', array( '@context' => 'https://schema.org/' ), $data, $type, $value ) + $data[ $type ];
		}

		// If requested types, pick them up... Finally change the associative array to an indexed one.
		$data = $types ? array_values( array_intersect_key( $data, array_flip( $types ) ) ) : array_values( $data );

		if ( ! empty( $data ) ) {
			if ( 1 < count( $data ) ) {
				$data = apply_filters( 'easyreservations_structured_data_context', array( '@context' => 'https://schema.org/' ), $data, '', '' ) + array( '@graph' => $data );
			} else {
				$data = $data[0];
			}
		}

		return $data;
	}

	/**
	 * Get data types for pages.
	 *
	 * @return array
	 */
	protected function get_data_type_for_page() {
		$types   = array();
		$types[] = is_easyreservations_shop() || is_easyreservations_resource_category() || is_easyreservations_resource() ? 'resource' : '';
		$types[] = is_easyreservations_shop() && is_front_page() ? 'website' : '';
		$types[] = 'order';

		return array_filter( apply_filters( 'easyreservations_structured_data_type_for_page', $types ) );
	}

	/**
	 * Makes sure email structured data only outputs on non-plain text versions.
	 *
	 * @param ER_Order $order Order data.
	 * @param bool     $sent_to_admin Send to admin (default: false).
	 * @param bool     $plain_text Plain text email (default: false).
	 */
	public function output_email_structured_data( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( $plain_text ) {
			return;
		}
		echo '<div style="display: none; font-size: 0; max-height: 0; line-height: 0; padding: 0; mso-hide: all;">';
		$this->output_structured_data();
		echo '</div>';
	}

	/**
	 * Sanitizes, encodes and outputs structured data.
	 *
	 * Hooked into `wp_footer` action hook.
	 * Hooked into `easyreservations_email_order_details` action hook.
	 */
	public function output_structured_data() {
		$types = $this->get_data_type_for_page();
		$data  = $this->get_structured_data( $types );

		if ( $data ) {
			echo '<script type="application/ld+json">' . er_esc_json( wp_json_encode( $data ), true ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Generators
	|--------------------------------------------------------------------------
	|
	| Methods for generating specific structured data types:
	|
	| - Resource
	| - WebSite
	| - Order
	|
	| The generated data is stored into `$this->_data`.
	| See the methods above for handling `$this->_data`.
	|
	*/

	/**
	 * Generates Resource structured data.
	 *
	 * Hooked into `easyreservations_single_resource_summary` action hook.
	 *
	 * @param ER_Resource $resource Resource data (default: null).
	 */
	public function generate_resource_data( $resource = null ) {
		if ( ! is_object( $resource ) ) {
			global $resource;
		}

		if ( ! is_a( $resource, 'ER_Resource' ) ) {
			return;
		}

		$shop_name = get_bloginfo( 'name' );
		$shop_url  = home_url();
		$currency  = er_get_currency();
		$permalink = get_permalink( $resource->get_id() );
		$image     = wp_get_attachment_url( $resource->get_image_id() );

		$markup = array(
			'@type'       => 'Product',
			'@id'         => $permalink . '#resource',
			// Append '#resource' to differentiate between this @id and the @id generated for the Breadcrumblist.
			'name'        => $resource->get_title(),
			'url'         => $permalink,
			'description' => wp_strip_all_tags( do_shortcode( $resource->get_excerpt() ? $resource->get_excerpt() : $resource->get_excerpt() ) ),
		);

		if ( $image ) {
			$markup['image'] = $image;
		}

		// Declare SKU or fallback to ID.
		if ( $resource->get_sku() ) {
			$markup['sku'] = $resource->get_sku();
		} else {
			$markup['sku'] = $resource->get_id();
		}

		if ( '' !== $resource->get_price() ) {
			// Assume prices will be valid until the end of next year, unless on sale and there is an end date.
			$price_valid_until = date( 'Y-12-31', time() + YEAR_IN_SECONDS );

			$markup_offer = array(
				'@type'              => 'Offer',
				'price'              => er_format_decimal( $resource->get_price(), er_get_price_decimals() ),
				'priceValidUntil'    => $price_valid_until,
				'priceSpecification' => array(
					'price'                 => er_format_decimal( $resource->get_price(), er_get_price_decimals() ),
					'priceCurrency'         => $currency,
					'valueAddedTaxIncluded' => er_prices_include_tax() ? 'true' : 'false',
				),
				'priceCurrency'      => $currency,
				'availability'       => 'http://schema.org/' . ( $resource->is_visible() ? 'InStock' : 'OutOfStock' ),
				'url'                => $permalink,
				'seller'             => array(
					'@type' => 'Organization',
					'name'  => $shop_name,
					'url'   => $shop_url,
				),
			);

			$markup['offers'] = array( apply_filters( 'easyreservations_structured_data_resource_offer', $markup_offer, $resource ) );
		}

		// Check we have required data.
		if ( empty( $markup['aggregateRating'] ) && empty( $markup['offers'] ) && empty( $markup['review'] ) ) {
			return;
		}

		$this->set_data( apply_filters( 'easyreservations_structured_data_resource', $markup, $resource ) );
	}

	/**
	 * Generates WebSite structured data.
	 *
	 * Hooked into `easyreservations_before_main_content` action hook.
	 */
	public function generate_website_data() {
		$markup                    = array();
		$markup['@type']           = 'WebSite';
		$markup['name']            = get_bloginfo( 'name' );
		$markup['url']             = home_url();
		$markup['potentialAction'] = array(
			'@type'       => 'SearchAction',
			'target'      => home_url( '?s={search_term_string}&post_type=easy-rooms' ),
			'query-input' => 'required name=search_term_string',
		);

		$this->set_data( apply_filters( 'easyreservations_structured_data_website', $markup ) );
	}

	/**
	 * Generates Order structured data.
	 *
	 * Hooked into `easyreservations_email_order_details` action hook.
	 *
	 * @param ER_Order $order Order data.
	 * @param bool     $sent_to_admin Send to admin (default: false).
	 * @param bool     $plain_text Plain text email (default: false).
	 */
	public function generate_order_data( $order, $sent_to_admin = false, $plain_text = false ) {
		if ( $plain_text || ! is_a( $order, 'ER_Order' ) ) {
			return;
		}

		$shop_name      = get_bloginfo( 'name' );
		$shop_url       = home_url();
		$order_url      = $sent_to_admin ? $order->get_edit_order_url() : $order->get_view_order_url();
		$order_statuses = array(
			'pending'    => 'https://schema.org/OrderPaymentDue',
			'processing' => 'https://schema.org/OrderProcessing',
			'on-hold'    => 'https://schema.org/OrderProblem',
			'completed'  => 'https://schema.org/OrderDelivered',
			'cancelled'  => 'https://schema.org/OrderCancelled',
			'refunded'   => 'https://schema.org/OrderReturned',
			'failed'     => 'https://schema.org/OrderProblem',
		);

		$markup_offers = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! apply_filters( 'easyreservations_order_item_visible', true, $item ) ) {
				continue;
			}

			$resource = false;
			if ( method_exists( $item, 'get_resource_id' ) ) {
				$resource = $item->get_resource();
			}
			$resource_exists = is_object( $resource );
			$is_visible      = $resource_exists && $resource->is_visible();

			$markup_offers[] = array(
				'@type'              => 'Offer',
				'price'              => $order->get_item_subtotal( $item ),
				'priceCurrency'      => er_get_currency(),
				'priceSpecification' => array(
					'price'            => $order->get_item_subtotal( $item ),
					'priceCurrency'    => er_get_currency(),
					'eligibleQuantity' => array(
						'@type' => 'QuantitativeValue',
						'value' => 1,
					),
				),
				'itemOffered'        => array(
					'@type' => 'Product',
					'name'  => apply_filters( 'easyreservations_order_item_name', $item->get_name(), $item, $is_visible ),
					'sku'   => $resource_exists ? $resource->get_sku() : '',
					'image' => $resource_exists ? wp_get_attachment_image_url( $resource->get_image_id() ) : '',
					'url'   => $is_visible ? get_permalink( $resource->get_id() ) : get_home_url(),
				),
				'seller'             => array(
					'@type' => 'Organization',
					'name'  => $shop_name,
					'url'   => $shop_url,
				),
			);
		}

		$markup                       = array();
		$markup['@type']              = 'Order';
		$markup['url']                = $order_url;
		$markup['orderStatus']        = isset( $order_statuses[ $order->get_status() ] ) ? $order_statuses[ $order->get_status() ] : '';
		$markup['orderNumber']        = $order->get_order_number();
		$markup['orderDate']          = $order->get_date_created()->format( 'c' );
		$markup['acceptedOffer']      = $markup_offers;
		$markup['discount']           = $order->get_total_discount();
		$markup['discountCurrency']   = er_get_currency();
		$markup['price']              = $order->get_total();
		$markup['priceCurrency']      = er_get_currency();
		$markup['priceSpecification'] = array(
			'price'                 => $order->get_total(),
			'priceCurrency'         => er_get_currency(),
			'valueAddedTaxIncluded' => 'true',
		);
		$markup['billingAddress']     = array(
			'@type'           => 'PostalAddress',
			'name'            => $order->get_formatted_full_name(),
			'streetAddress'   => $order->get_address_1(),
			'postalCode'      => $order->get_postcode(),
			'addressLocality' => $order->get_city(),
			'addressRegion'   => $order->get_state(),
			'addressCountry'  => $order->get_country(),
			'email'           => $order->get_email(),
			'telephone'       => $order->get_phone(),
		);
		$markup['customer']           = array(
			'@type' => 'Person',
			'name'  => $order->get_formatted_full_name(),
		);
		$markup['merchant']           = array(
			'@type' => 'Organization',
			'name'  => $shop_name,
			'url'   => $shop_url,
		);
		$markup['potentialAction']    = array(
			'@type'  => 'ViewAction',
			'name'   => 'View Order',
			'url'    => $order_url,
			'target' => $order_url,
		);

		$this->set_data( apply_filters( 'easyreservations_structured_data_order', $markup, $sent_to_admin, $order ), true );
	}
}
