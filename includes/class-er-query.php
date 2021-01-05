<?php
/**
 * Contains the query functions for easyReservations which alter the front-end post queries and loops
 *
 * @package easyReservations\Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * ER_Query Class.
 */
class ER_Query {

	/**
	 * Reference to the main resource query on the page.
	 *
	 * @var array
	 */
	private static $resource_query;
	/**
	 * Stores chosen attributes.
	 *
	 * @var array
	 */
	private static $_chosen_attributes;
	/**
	 * Query vars to add to wp.
	 *
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * Constructor for the query class. Hooks in methods.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		if ( ! is_admin() ) {
			add_action( 'wp_loaded', array( $this, 'get_errors' ), 20 );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_filter( 'get_pagenum_link', array( $this, 'remove_add_to_cart_pagination' ), 10, 1 );
			add_filter( 'easyreservations_account_endpoint_page_not_found', array( $this, 'check_for_er_endpoint_url' ) );
		}
		$this->init_query_vars();
	}

	/**
	 * Get any errors from querystring.
	 */
	public function get_errors() {
		$error = ! empty( $_GET['er_error'] ) ? sanitize_text_field( wp_unslash( $_GET['er_error'] ) ) : ''; // WPCS: input var ok, CSRF ok.

		if ( $error && ! er_has_notice( $error, 'error' ) ) {
			er_add_notice( $error, 'error' );
		}
	}

	/**
	 * Prevent easyreservations from 404 because of similar endpoints when it's our account or checkout page
	 *
	 * @return bool
	 */
	public function check_for_er_endpoint_url() {
		if ( is_er_endpoint_url() && ( is_easyreservations_account_page() || is_easyreservations_checkout() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Init query vars by loading options.
	 */
	public function init_query_vars() {
		// Query vars to add to WP.
		$this->query_vars = array(
			// Checkout actions.
			'order-payment'              => get_option( 'reservations_checkout_pay_endpoint', 'order-payment' ),
			'order-received'             => get_option( 'reservations_checkout_order_received_endpoint', 'order-received' ),
			// My account actions.
			'my-orders'                  => get_option( 'reservations_myaccount_orders_endpoint', 'my-orders' ),
			'view-order'                 => get_option( 'reservations_myaccount_view_order_endpoint', 'view-order' ),
			'edit-account'               => get_option( 'reservations_myaccount_edit_account_endpoint', 'edit-account' ),
			'edit-address'               => get_option( 'reservations_myaccount_edit_address_endpoint', 'edit-address' ),
			'payment-methods'            => get_option( 'reservations_myaccount_payment_methods_endpoint', 'payment-methods' ),
			'lost-password'              => get_option( 'reservations_myaccount_lost_password_endpoint', 'lost-password' ),
			'customer-logout'            => get_option( 'reservations_logout_endpoint', 'customer-logout' ),
			'add-payment-method'         => get_option( 'reservations_myaccount_add_payment_method_endpoint', 'add-payment-method' ),
			'delete-payment-method'      => get_option( 'reservations_myaccount_delete_payment_method_endpoint', 'delete-payment-method' ),
			'set-default-payment-method' => get_option( 'reservations_myaccount_set_default_payment_method_endpoint', 'set-default-payment-method' ),
		);
	}

	/**
	 * Endpoint mask describing the places the endpoint should be added.
	 *
	 * @return int
	 */
	public function get_endpoints_mask() {
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$page_on_front     = get_option( 'page_on_front' );
			$myaccount_page_id = get_option( 'reservations_myaccount_page_id' );
			$checkout_page_id  = get_option( 'reservations_checkout_page_id' );

			if ( in_array( $page_on_front, array( $myaccount_page_id, $checkout_page_id ), true ) ) {
				return EP_ROOT | EP_PAGES;
			}
		}

		return EP_PAGES;
	}

	/**
	 * Add endpoints for query vars.
	 */
	public function add_endpoints() {
		$mask = $this->get_endpoints_mask();

		foreach ( $this->get_query_vars() as $key => $var ) {
			if ( ! empty( $var ) ) {
				add_rewrite_endpoint( $var, $mask );
			}
		}
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		foreach ( $this->get_query_vars() as $key => $var ) {
			$vars[] = $key;
		}

		return $vars;
	}

	/**
	 * Get query vars.
	 *
	 * @return array
	 */
	public function get_query_vars() {
		return apply_filters( 'easyreservations_get_query_vars', $this->query_vars );
	}

	/**
	 * Get query current active query var.
	 *
	 * @return string
	 */
	public function get_current_endpoint() {
		global $wp;

		foreach ( $this->get_query_vars() as $key => $value ) {

			if ( isset( $wp->query_vars[ $key ] ) ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Parse the request and look for query vars - endpoints may not be supported.
	 */
	public function parse_request() {
		global $wp;

		// Map query vars to their keys, or get them if endpoints are not supported.
		foreach ( $this->get_query_vars() as $key => $var ) {

			if ( isset( $_GET[ $var ] ) ) { // WPCS: input var ok, CSRF ok.
				$wp->query_vars[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $var ] ) ); // WPCS: input var ok, CSRF ok.
			} elseif ( isset( $wp->query_vars[ $var ] ) ) {
				$wp->query_vars[ $key ] = $wp->query_vars[ $var ];
			}
		}
	}

	/**
	 * Are we currently on the front page?
	 *
	 * @param WP_Query $q Query instance.
	 *
	 * @return bool
	 */
	private function is_showing_page_on_front( $q ) {
		return ( $q->is_home() && ! $q->is_posts_page ) && 'page' === get_option( 'show_on_front' );
	}

	/**
	 * Is the front page a page we define?
	 *
	 * @param int $page_id Page ID.
	 *
	 * @return bool
	 */
	private function page_on_front_is( $page_id ) {
		return absint( get_option( 'page_on_front' ) ) === absint( $page_id );
	}

	/**
	 * Hook into pre_get_posts to do the main resource query.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query.
		if ( ! $q->is_main_query() ) {
			return;
		}

		// Fixes for queries on static homepages.
		if ( $this->is_showing_page_on_front( $q ) ) {

			// Fix for endpoints on the homepage.
			if ( ! $this->page_on_front_is( $q->get( 'page_id' ) ) ) {
				$_query = wp_parse_args( $q->query );
				if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->get_query_vars() ) ) ) {
					$q->is_page     = true;
					$q->is_home     = false;
					$q->is_singular = true;
					$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
					add_filter( 'redirect_canonical', '__return_false' );
				}
			}

			// When orderby is set, WordPress shows posts on the front-page. Get around that here.
			if ( $this->page_on_front_is( er_get_page_id( 'shop' ) ) ) {
				$_query = wp_parse_args( $q->query );
				if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
					$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
					$q->is_page = true;
					$q->is_home = false;

					// WP supporting themes show post type archive.
					if ( current_theme_supports( 'easyreservations' ) ) {
						$q->set( 'post_type', 'easy-rooms' );
					} else {
						$q->is_singular = true;
					}
				}
			} elseif ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
			}
		}

		// Fix resource feeds.
		if ( $q->is_feed() && $q->is_post_type_archive( 'easy-rooms' ) ) {
			$q->is_comment_feed = false;
		}

		// Special check for shops with the PRODUCT POST TYPE ARCHIVE on front.
		if ( current_theme_supports( 'easyreservations' ) && $q->is_page() && 'page' === get_option( 'show_on_front' ) && absint( $q->get( 'page_id' ) ) === er_get_page_id( 'shop' ) ) {
			// This is a front-page shop.
			$q->set( 'post_type', 'easy-rooms' );
			$q->set( 'page_id', '' );

			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}

			// Define a variable so we know this is the front page shop later on.
			er_maybe_define_constant( 'RESERVATIONS_SHOP_IS_ON_FRONT', true );

			// Get the actual WP page to avoid errors and let us use is_front_page().
			// This is hacky but works. Awaiting https://core.trac.wordpress.org/ticket/21096.
			global $wp_post_types;

			$shop_page = get_post( er_get_page_id( 'shop' ) );

			$wp_post_types['easy-rooms']->ID         = $shop_page->ID;
			$wp_post_types['easy-rooms']->post_title = $shop_page->post_title;
			$wp_post_types['easy-rooms']->post_name  = $shop_page->post_name;
			$wp_post_types['easy-rooms']->post_type  = $shop_page->post_type;
			$wp_post_types['easy-rooms']->ancestors  = get_ancestors( $shop_page->ID, $shop_page->post_type );

			// Fix conditional Functions like is_front_page.
			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

			// Remove post type archive name from front page title tag.
			add_filter( 'post_type_archive_title', '__return_empty_string', 5 );

			// Fix WP SEO.
			if ( class_exists( 'WPSEO_Meta' ) ) {
				add_filter( 'wpseo_metadesc', array( $this, 'wpseo_metadesc' ) );
				add_filter( 'wpseo_metakey', array( $this, 'wpseo_metakey' ) );
			}
		} elseif ( ! $q->is_post_type_archive( 'easy-rooms' ) && ! $q->is_tax( get_object_taxonomies( 'easy-rooms' ) ) ) {
			// Only apply to resource categories, the resource post archive, the shop page, resource tags, and resource attribute taxonomies.
			return;
		}

		$this->resource_query( $q );
	}

	/**
	 * WP SEO meta description.
	 *
	 * Hooked into wpseo_ hook already, so no need for function_exist.
	 *
	 * @return string
	 */
	public function wpseo_metadesc() {
		return WPSEO_Meta::get_value( 'metadesc', er_get_page_id( 'shop' ) );
	}

	/**
	 * WP SEO meta key.
	 *
	 * Hooked into wpseo_ hook already, so no need for function_exist.
	 *
	 * @return string
	 */
	public function wpseo_metakey() {
		return WPSEO_Meta::get_value( 'metakey', er_get_page_id( 'shop' ) );
	}

	/**
	 * Query the resources, applying sorting/ordering etc.
	 * This applies to the main WordPress loop.
	 *
	 * @param WP_Query $q Query instance.
	 */
	public function resource_query( $q ) {
		if ( ! is_feed() ) {
			$q->set( 'orderby', 'menu_order title' );
			$q->set( 'order', 'ASC' );
		}

		// Query vars that affect posts shown.
		$q->set( 'meta_query', $this->get_meta_query( $q->get( 'meta_query' ), true ) );
		$q->set( 'er_query', 'resource_query' );
		$q->set( 'post__in', array_unique( (array) apply_filters( 'loop_shop_post_in', array() ) ) );

		// Work out how many resources to query.
		$q->set( 'posts_per_page', $q->get( 'posts_per_page' ) ? $q->get( 'posts_per_page' ) : apply_filters( 'easyreservations_loop_shop_per_page', er_get_default_resources_per_row() * er_get_default_resource_rows_per_page() ) );

		// Store reference to this query.
		self::$resource_query = $q;

		do_action( 'easyreservations_resource_query', $q, $this );
	}

	/**
	 * Appends meta queries to an array.
	 *
	 * @param array $meta_query Meta query.
	 * @param bool  $main_query If is main query.
	 *
	 * @return array
	 */
	public function get_meta_query( $meta_query = array(), $main_query = false ) {
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		return array_filter( apply_filters( 'easyreservations_resource_query_meta_query', $meta_query, $this ) );
	}

	/**
	 * Appends tax queries to an array.
	 *
	 * @param array $tax_query Tax query.
	 * @param bool  $main_query If is main query.
	 *
	 * @return array
	 */
	public function get_tax_query( $tax_query = array(), $main_query = false ) {
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array(
				'relation' => 'AND',
			);
		}

		// Layered nav filters on terms.
		if ( $main_query ) {
			/*
			foreach ( $this->get_layered_nav_chosen_attributes() as $taxonomy => $data ) {
				$tax_query[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $data['terms'],
					'operator'         => 'and' === $data['query_type'] ? 'AND' : 'IN',
					'include_children' => false,
				);
			}*/
		}

		$resource_visibility_terms  = er_get_resource_visibility_term_ids();
		$resource_visibility_not_in = array( is_search() && $main_query ? $resource_visibility_terms['exclude-from-search'] : $resource_visibility_terms['exclude-from-catalog'] );

		// Filter by rating.
		if ( isset( $_GET['rating_filter'] ) ) { // WPCS: input var ok, CSRF ok.
			$rating_filter = array_filter( array_map( 'absint', explode( ',', $_GET['rating_filter'] ) ) ); // WPCS: input var ok, CSRF ok, Sanitization ok.
			$rating_terms  = array();
			for ( $i = 1; $i <= 5; $i ++ ) {
				if ( in_array( $i, $rating_filter, true ) && isset( $resource_visibility_terms[ 'rated-' . $i ] ) ) {
					$rating_terms[] = $resource_visibility_terms[ 'rated-' . $i ];
				}
			}
			if ( ! empty( $rating_terms ) ) {
				$tax_query[] = array(
					'taxonomy'      => 'resource_visibility',
					'field'         => 'term_taxonomy_id',
					'terms'         => $rating_terms,
					'operator'      => 'IN',
					'rating_filter' => true,
				);
			}
		}

		if ( ! empty( $resource_visibility_not_in ) ) {
			$tax_query[] = array(
				'taxonomy' => 'resource_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $resource_visibility_not_in,
				'operator' => 'NOT IN',
			);
		}

		return array_filter( apply_filters( 'easyreservations_resource_query_tax_query', $tax_query, $this ) );
	}

	/**
	 * Get the main query which resource queries ran against.
	 *
	 * @return array
	 */
	public static function get_main_query() {
		return self::$resource_query;
	}

	/**
	 * Get the tax query which was used by the main query.
	 *
	 * @return array
	 */
	public static function get_main_tax_query() {
		$tax_query = isset( self::$resource_query->tax_query, self::$resource_query->tax_query->queries ) ? self::$resource_query->tax_query->queries : array();

		return $tax_query;
	}

	/**
	 * Get the meta query which was used by the main query.
	 *
	 * @return array
	 */
	public static function get_main_meta_query() {
		$args       = self::$resource_query->query_vars;
		$meta_query = isset( $args['meta_query'] ) ? $args['meta_query'] : array();

		return $meta_query;
	}

	/**
	 * Based on WP_Query::parse_search
	 */
	public static function get_main_search_query_sql() {
		global $wpdb;

		$args         = self::$resource_query->query_vars;
		$search_terms = isset( $args['search_terms'] ) ? $args['search_terms'] : array();
		$sql          = array();

		foreach ( $search_terms as $term ) {
			// Terms prefixed with '-' should be excluded.
			$include = '-' !== substr( $term, 0, 1 );

			if ( $include ) {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			} else {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			}

			$like  = '%' . $wpdb->esc_like( $term ) . '%';
			$sql[] = $wpdb->prepare( "(($wpdb->posts.post_title $like_op %s) $andor_op ($wpdb->posts.post_excerpt $like_op %s) $andor_op ($wpdb->posts.post_content $like_op %s))", $like, $like, $like ); // unprepared SQL ok.
		}

		if ( ! empty( $sql ) && ! is_user_logged_in() ) {
			$sql[] = "($wpdb->posts.post_password = '')";
		}

		return implode( ' AND ', $sql );
	}

	/**
	 * Remove the add-to-cart param from pagination urls.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	public function remove_add_to_cart_pagination( $url ) {
		return remove_query_arg( 'add-to-cart', $url );
	}
}
