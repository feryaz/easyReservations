<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Admin class.
 */
class ER_Frontend {

	/**
	 * Constructor.
	 */
	public static function init() {
		if ( get_option( 'reservations_db_version' ) == RESERVATIONS_VERSION ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
			add_action( 'wp_print_scripts', array( __CLASS__, 'localize_scripts' ), 5 );
		}
	}

	/**
	 * Register all ER scripts.
	 */
	public static function register_scripts() {
		global $post;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'jquery-payment', RESERVATIONS_URL . 'assets/js/jquery-payment/jquery.payment' . $suffix . '.js', array( 'jquery' ), '3.0.0' );
		wp_register_script( 'er-country-select', RESERVATIONS_URL . 'assets/js/frontend/country-select' . $suffix . '.js', array( 'jquery' ), '3.0.0' );
		wp_register_script( 'er-form', RESERVATIONS_URL . 'assets/js/frontend/form' . $suffix . '.js', array( 'jquery-blockui', 'jquery-ui-slider', 'easy-ui' ), RESERVATIONS_VERSION );
		wp_register_script( 'js-cookie', RESERVATIONS_URL . 'assets/js/js-cookie/js.cookie' . $suffix . '.js', array(), '2.1.4', true );

		wp_enqueue_script( 'er-frontend', RESERVATIONS_URL . 'assets/js/frontend/frontend' . $suffix . '.js', array( 'jquery', 'js-cookie' ), RESERVATIONS_VERSION );
		wp_enqueue_script( 'er-cart-fragments', RESERVATIONS_URL . 'assets/js/frontend/cart-fragments' . $suffix . '.js', array( 'jquery', 'js-cookie' ), RESERVATIONS_VERSION );

		wp_register_script( 'er-checkout', RESERVATIONS_URL . 'assets/js/frontend/checkout' . $suffix . '.js', array( 'jquery', 'easy-ui', 'er-country-select', 'er-address-i18n', 'er-form'), RESERVATIONS_VERSION );

		wp_register_script( 'er-single-resource', RESERVATIONS_URL . 'assets/js/frontend/single-resource' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION );

		wp_enqueue_style( 'er-frontend', RESERVATIONS_URL . 'assets/css/frontend' . $suffix . '.css', array( 'easy-ui' ), RESERVATIONS_VERSION ); // widget form style
		wp_enqueue_style( 'er-frontend-smallscreen', RESERVATIONS_URL . 'assets/css/frontend-smallscreen' . $suffix . '.css', array(), RESERVATIONS_VERSION, 'only screen and (max-width: ' . apply_filters( 'easyreservations_style_smallscreen_breakpoint', '768px' ) . ')' ); // widget form style

		switch( get_template() ){
			case 'twentyseventeen':
				wp_enqueue_style( 'er-theme-support', RESERVATIONS_URL . 'assets/css/twenty-seventeen' . $suffix . '.css', array( 'easy-ui' ), RESERVATIONS_VERSION );

				break;
			case 'twentynineteen':
				wp_enqueue_style( 'er-theme-support', RESERVATIONS_URL . 'assets/css/twenty-nineteen' . $suffix . '.css', array( 'easy-ui' ), RESERVATIONS_VERSION );

				remove_action( 'easyreservations_sidebar', 'easyreservations_get_sidebar', 10 );

				break;
			case 'twentytwenty':
				wp_enqueue_style( 'er-theme-support', RESERVATIONS_URL . 'assets/css/twenty-twenty' . $suffix . '.css', array( 'easy-ui' ), RESERVATIONS_VERSION );

				remove_action( 'easyreservations_sidebar', 'easyreservations_get_sidebar', 10 );

				break;
			case 'twentytwentyone':
				wp_enqueue_style( 'er-theme-support', RESERVATIONS_URL . 'assets/css/twenty-twenty-one' . $suffix . '.css', array( 'easy-ui' ), RESERVATIONS_VERSION );

				remove_action( 'easyreservations_sidebar', 'easyreservations_get_sidebar', 10 );

				break;
		}

		wp_register_script( 'er-date-selection', RESERVATIONS_URL . 'assets/js/frontend/date-selection' . $suffix . '.js', array(), RESERVATIONS_VERSION );
		wp_register_script( 'er-add-payment-method', RESERVATIONS_URL . 'assets/js/frontend/add-payment-method' . $suffix . '.js', array( 'jquery', 'er-frontend' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-address-i18n', RESERVATIONS_URL . 'assets/js/frontend/address-i18n' . $suffix . '.js', array( 'er-country-select' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-credit-card-form', RESERVATIONS_URL . 'assets/js/frontend/credit-card-form' . $suffix . '.js', array( 'jquery', 'jquery-payment' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-lost-password', RESERVATIONS_URL . 'assets/js/frontend/lost-password' . $suffix . '.js', array( 'jquery', 'er-frontend' ), RESERVATIONS_VERSION );
		wp_register_script( 'zoom', RESERVATIONS_URL . 'assets/js/zoom/jquery.zoom' . $suffix . '.js', array( 'jquery' ), '1.7.21' );
		wp_register_script( 'flexslider', RESERVATIONS_URL . 'assets/js/flexslider/jquery.flexslider' . $suffix . '.js', array(), '2.7.2' );
		wp_register_script( 'photoswipe', RESERVATIONS_URL . 'assets/js/photoswipe/photoswipe' . $suffix . '.js', array(), '4.1.1' );
		wp_register_script( 'photoswipe-ui-default', RESERVATIONS_URL . 'assets/js/photoswipe/photoswipe-ui-default' . $suffix . '.js', array( 'photoswipe' ), '4.1.1' );
		wp_register_style( 'photoswipe', RESERVATIONS_URL . 'assets/css/photoswipe/photoswipe' . $suffix . '.css', array(), RESERVATIONS_VERSION );
		wp_register_style( 'photoswipe-default-skin', RESERVATIONS_URL . 'assets/css/photoswipe/default-skin/default-skin' . $suffix . '.css', array( 'photoswipe' ), RESERVATIONS_VERSION );

		if ( is_easyreservations_add_payment_method_page() ) {
			wp_enqueue_script( 'er-add-payment-method' );
		}

		if ( is_easyreservations_lost_password_page() ) {
			wp_enqueue_script( 'er-lost-password' );
		}

		// Load gallery scripts on resource pages only if supported.
		if ( is_easyreservations_resource() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[resource_page' ) ) ) {
			if ( current_theme_supports( 'er-resource-gallery-zoom' ) ) {
				wp_enqueue_script( 'zoom' );
			}

			if ( current_theme_supports( 'er-resource-gallery-slider' ) ) {
				wp_enqueue_script( 'flexslider' );
			}

			if ( current_theme_supports( 'er-resource-gallery-lightbox' ) ) {
				wp_enqueue_script( 'photoswipe-ui-default' );
				wp_enqueue_style( 'photoswipe-default-skin' );
				add_action( 'wp_footer', 'easyreservations_photoswipe' );
			}

			wp_enqueue_script( 'er-single-resource' );
		}

		// Placeholder style.
		wp_register_style( 'easyreservations-inline', false ); // phpcs:ignore
		wp_enqueue_style( 'easyreservations-inline' );

		if ( true === er_string_to_bool( get_option( 'reservations_checkout_highlight_required_fields', 'yes' ) ) ) {
			wp_add_inline_style( 'easyreservations-inline', '.easyreservations form .form-row .required { visibility: visible; }' );
		} else {
			wp_add_inline_style( 'easyreservations-inline', '.easyreservations form .form-row .required { visibility: hidden; }' );
		}
	}

	/**
	 * Localize scripts
	 */
	public static function localize_scripts() {
		global $wp;

		wp_localize_script( 'er-single-resource', 'er_single_resource_params', array(
			'flexslider'         => apply_filters(
				'easyreservations_single_resource_carousel_options',
				array(
					'rtl'            => is_rtl(),
					'animation'      => 'slide',
					'smoothHeight'   => true,
					'directionNav'   => false,
					'controlNav'     => 'thumbnails',
					'slideshow'      => false,
					'animationSpeed' => 500,
					'animationLoop'  => false, // Breaks photoswipe pagination if true.
					'allowOneSlide'  => false,
				)
			),
			'zoom_enabled'       => apply_filters( 'easyreservations_single_resource_zoom_enabled', get_theme_support( 'er-resource-gallery-zoom' ) ),
			'zoom_options'       => apply_filters( 'easyreservations_single_resource_zoom_options', array() ),
			'photoswipe_enabled' => apply_filters( 'easyreservations_single_resource_photoswipe_enabled', get_theme_support( 'er-resource-gallery-lightbox' ) ),
			'photoswipe_options' => apply_filters(
				'easyreservations_single_resource_photoswipe_options',
				array(
					'shareEl'               => false,
					'closeOnScroll'         => false,
					'history'               => false,
					'hideAnimationDuration' => 0,
					'showAnimationDuration' => 0,
				)
			),
			'flexslider_enabled' => apply_filters( 'easyreservations_single_resource_flexslider_enabled', get_theme_support( 'er-resource-gallery-slider' ) ),
			'resource_id'        => get_the_ID()
		) );

		wp_localize_script( 'er-checkout', 'er_checkout_params', array(
			'ajax_url'                  => admin_url( 'admin-ajax.php', 'relative' ),
			'er_ajax_url'               => ER_AJAX::get_endpoint( '%%endpoint%%' ),
			'update_order_review_nonce' => wp_create_nonce( 'update-order-review' ),
			'apply_coupon_nonce'        => wp_create_nonce( 'apply-coupon' ),
			'remove_coupon_nonce'       => wp_create_nonce( 'remove-coupon' ),
			'option_guest_checkout'     => get_option( 'reservations_enable_guest_checkout' ),
			'checkout_url'              => ER_AJAX::get_endpoint( 'checkout' ),
			'is_checkout'               => is_easyreservations_checkout() && empty( $wp->query_vars['order-payment'] ) && ! isset( $wp->query_vars['order-received'] ) ? 1 : 0,
			'debug_mode'                => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'i18n_checkout_error'       => esc_attr__( 'Error processing checkout. Please try again.', 'easyReservations' ),
		) );

		wp_localize_script( 'er-country-select', 'er_country_select_params', array(
			'countries'                 => wp_json_encode( ER()->countries->get_states() ),
			'i18n_select_state_text'    => esc_attr__( 'Select an option&hellip;', 'easyReservations' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'easyReservations' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'easyReservations' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'easyReservations' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'easyReservations' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'easyReservations' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'easyReservations' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'easyReservations' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'easyReservations' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'easyReservations' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'easyReservations' ),
		) );

		wp_localize_script( 'er-address-i18n', 'er_address_i18n_params', array(
			'locale'             => wp_json_encode( ER()->countries->get_country_locale() ),
			'locale_fields'      => wp_json_encode( ER()->countries->get_country_locale_field_selectors() ),
			'i18n_required_text' => esc_attr__( 'required', 'easyReservations' ),
			'i18n_optional_text' => esc_html__( 'optional', 'easyReservations' ),
		) );

		wp_localize_script( 'er-cart-fragments', 'er_cart_fragments_params', array(
			'ajax_url'        => admin_url( 'admin-ajax.php', 'relative' ),
			'er_ajax_url'     => ER_AJAX::get_endpoint( '%%endpoint%%' ),
			'cart_hash_key'   => apply_filters( 'easyreservations_cart_hash_key', 'er_cart_hash_' . md5( get_current_blog_id() . '_' . get_site_url( get_current_blog_id(), '/' ) . get_template() ) ),
			'fragment_name'   => apply_filters( 'easyreservations_cart_fragment_name', 'er_fragments_' . md5( get_current_blog_id() . '_' . get_site_url( get_current_blog_id(), '/' ) . get_template() ) ),
			'request_timeout' => 5000,
		) );
	}
}

ER_Frontend::init();