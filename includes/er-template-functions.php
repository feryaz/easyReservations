<?php
defined( 'ABSPATH' ) || exit;

add_action( 'before_easyreservations_pay', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_before_checkout_form_cart_notices', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_before_cart', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_cart_is_empty', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_before_checkout_form', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_before_form', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_before_single_resource', 'easyreservations_output_all_notices', 10 );
add_action( 'easyreservations_before_shop_loop', 'easyreservations_output_all_notices', 10 );

/**
 * Checkout.
 *
 * @see easyreservations_checkout_login_form()
 * @see easyreservations_order_review()
 * @see easyreservations_checkout_terms()
 * @see easyreservations_checkout_submit()
 * @see er_checkout_privacy_policy_text()
 * @see er_terms_and_conditions_page_content()
 */
add_action( 'easyreservations_before_checkout_form', 'easyreservations_checkout_login_form', 10 );
add_action( 'easyreservations_checkout_order_review', 'easyreservations_order_review', 10 );
add_action( 'easyreservations_checkout_order_submit', 'easyreservations_checkout_terms', 20 );
add_action( 'easyreservations_checkout_order_submit', 'easyreservations_checkout_submit', 30 );
add_action( 'easyreservations_checkout_terms_and_conditions', 'er_checkout_privacy_policy_text', 20 );
add_action( 'easyreservations_checkout_terms_and_conditions', 'er_terms_and_conditions_page_content', 30 );

/**
 * Cart widget
 */
add_action( 'easyreservations_widget_shopping_cart_buttons', 'easyreservations_widget_shopping_cart_button_view_cart', 10 );
add_action( 'easyreservations_widget_shopping_cart_buttons', 'easyreservations_widget_shopping_cart_proceed_to_checkout', 20 );
add_action( 'easyreservations_widget_shopping_cart_total', 'easyreservations_widget_shopping_cart_subtotal', 10 );

/**
 * Cart.
 *
 * @see er_cart_totals()
 * @see er_button_proceed_to_checkout()
 */
add_action( 'easyreservations_cart_totals', 'er_cart_totals', 10 );
add_action( 'easyreservations_proceed_to_checkout', 'er_button_proceed_to_checkout' );

/**
 * Resources Loop.
 */
add_action( 'easyreservations_before_shop_loop', 'easyreservations_result_count', 20 );
//add_action( 'easyreservations_before_shop_loop', 'easyreservations_catalog_ordering', 30 );
add_action( 'easyreservations_no_resources_found', 'er_no_resources_found', 10 );
add_action( 'easyreservations_archive_description', 'easyreservations_resource_archive_description', 10 );

/**
 * Sale flashes.
 *
 * @see easyreservations_template_loop_sale_flash()
 * @see easyreservations_template_single_sale_flash()
 */
add_action( 'easyreservations_before_shop_loop_item_title', 'easyreservations_template_loop_sale_flash', 10 );
add_action( 'easyreservations_before_single_resource_summary', 'easyreservations_template_single_sale_flash', 10 );

/**
 * Pagination after shop loops.
 *
 * @see easyreservations_pagination()
 */
add_action( 'easyreservations_after_shop_loop', 'easyreservations_pagination', 10 );

/**
 * Content Wrappers.
 *
 * @see easyreservations_output_content_wrapper()
 * @see easyreservations_output_content_wrapper_end()
 */
add_action( 'easyreservations_before_main_content', 'easyreservations_output_content_wrapper', 10 );
add_action( 'easyreservations_after_main_content', 'easyreservations_output_content_wrapper_end', 10 );

/**
 * Sidebar.
 *
 * @see easyreservations_get_sidebar()
 */
add_action( 'easyreservations_sidebar', 'easyreservations_get_sidebar', 10 );

/**
 * Order details.
 *
 * @see er_order_details_table()
 */
add_action( 'easyreservations_view_order', 'er_order_details_table', 10 );
add_action( 'easyreservations_thankyou', 'er_order_details_table', 10 );

add_action( 'easyreservations_before_single_resource_summary', 'easyreservations_show_resource_images', 20 );
add_action( 'easyreservations_resource_thumbnails', 'easyreservations_show_resource_thumbnails', 20 );

/**
 * Resource Summary Box.
 *
 * @see easyreservations_template_single_title()
 * @see easyreservations_template_single_price()
 * @see easyreservations_template_single_excerpt()
 * @see easyreservations_template_single_sharing()
 */
add_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_title', 5 );
add_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_price', 10 );
add_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_excerpt', 20 );
add_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_form', 30 );
add_action( 'easyreservations_single_resource_summary', 'easyreservations_template_single_sharing', 50 );
add_action( 'easyreservations_after_single_resource_summary', 'easyreservations_template_single_description', 10 );

/**
 * Resource Loop Items.
 *
 * @see easyreservations_template_loop_resource_link_open()
 * @see easyreservations_template_loop_resource_link_close()
 * @see easyreservations_get_resource_thumbnail()
 * @see easyreservations_template_loop_resource_title()
 * @see easyreservations_template_loop_price()
 */
add_action( 'easyreservations_before_shop_loop_item', 'easyreservations_template_loop_resource_link_open', 10 );
add_action( 'easyreservations_after_shop_loop_item', 'easyreservations_template_loop_resource_link_close', 5 );
add_action( 'easyreservations_before_shop_loop_item_title', 'easyreservations_get_resource_thumbnail', 10, 0 );
add_action( 'easyreservations_shop_loop_item_title', 'easyreservations_template_loop_resource_title', 10 );
add_action( 'easyreservations_after_shop_loop_item_title', 'easyreservations_template_loop_price', 10 );
add_action( 'easyreservations_after_shop_loop_item_title', 'easyreservations_template_loop_button', 20 );

/**
 * Handle redirects before content is output - hooked into template_redirect so is_page works.
 */
function er_template_redirect() {
	global $wp_query, $wp;

	// When on the checkout with an empty cart, redirect to cart page.
	if ( is_page( er_get_page_id( 'checkout' ) ) && er_get_page_id( 'checkout' ) !== er_get_page_id( 'cart' ) && ER()->cart->is_empty() && empty( $wp->query_vars['order-payment'] ) && ! isset( $wp->query_vars['order-received'] ) && ! is_customize_preview() && apply_filters( 'easyreservations_checkout_redirect_empty_cart', true ) ) {
		er_add_notice( __( 'Checkout is not available whilst your cart is empty.', 'easyReservations' ), 'notice' );
		wp_safe_redirect( er_get_cart_url() );
		exit;
	}

	// Logout.
	if ( isset( $wp->query_vars['customer-logout'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'customer-logout' ) ) { // WPCS: input var ok, CSRF ok.
		wp_safe_redirect( str_replace( '&amp;', '&', wp_logout_url( er_get_page_permalink( 'myaccount' ) ) ) );
		exit;
	}

	// Redirect to the correct logout endpoint.
	if ( isset( $wp->query_vars['customer-logout'] ) && 'true' === $wp->query_vars['customer-logout'] ) {
		wp_safe_redirect( esc_url_raw( er_get_account_endpoint_url( 'customer-logout' ) ) );
		exit;
	}

	// Trigger 404 if trying to access an endpoint on wrong page.
	if ( is_er_endpoint_url() && ! is_easyreservations_account_page() && ! is_easyreservations_checkout() && apply_filters( 'easyreservations_account_endpoint_page_not_found', true ) ) {
	    if( !function_exists( 'is_wc_endpoint_url' ) || ( !is_wc_endpoint_url() )){
		    $wp_query->set_404();
		    status_header( 404 );
		    include get_query_template( '404' );
		    exit;
	    }
	}

	// Redirect to the resource page if we have a single resource.
	if ( is_search() && is_post_type_archive( 'easy-rooms' ) && apply_filters( 'easyreservations_redirect_single_search_result', true ) && 1 === absint( $wp_query->found_posts ) ) {
		$resource = ER()->resources()->get( $wp_query->post );

		if ( $resource && $resource->is_visible() ) {
			wp_safe_redirect( get_permalink( $resource->get_id() ), 302 );
			exit;
		}
	}

	// Ensure gateways methods are loaded early.
	if ( is_easyreservations_add_payment_method_page() || is_easyreservations_checkout() ) {
		// Buffer the checkout page.
		ob_start();

		// Ensure gateways  methods are loaded early.
		ER()->payment_gateways();
	}
}

add_action( 'template_redirect', 'er_template_redirect' );

add_filter( 'woocommerce_account_endpoint_page_not_found', 'is_easyreservations_endpoint' );

/**
 * Fix endpoints
 *
 * @param $bool
 * @return bool
 */
function is_easyreservations_endpoint( $bool ){
    if( is_er_endpoint_url() && ( is_easyreservations_account_page() ||  is_easyreservations_checkout()) ){
        return false;
    }
    return $bool;
}

/**
 * When loading sensitive checkout or account pages, send a HTTP header to limit rendering of pages to same origin iframes for security reasons.
 *
 * Can be disabled with: remove_action( 'template_redirect', 'er_send_frame_options_header' );
 */
function er_send_frame_options_header() {
	if ( ( is_easyreservations_checkout() || is_easyreservations_account_page() ) && ! is_customize_preview() ) {
		send_frame_options_header();
	}
}

add_action( 'template_redirect', 'er_send_frame_options_header' );

/**
 * No index our endpoints.
 * Prevent indexing pages like order-received.
 */
function er_prevent_endpoint_indexing() {
	if ( is_er_endpoint_url() ) { // WPCS: input var ok, CSRF ok.
		@header( 'X-Robots-Tag: noindex' ); // @codingStandardsIgnoreLine
	}
}

add_action( 'template_redirect', 'er_prevent_endpoint_indexing' );

/**
 * Remove adjacent_posts_rel_link_wp_head - pointless for resources.
 */
function er_prevent_adjacent_posts_rel_link_wp_head() {
	if ( is_singular( 'easy-rooms' ) ) {
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	}
}

add_action( 'template_redirect', 'er_prevent_adjacent_posts_rel_link_wp_head' );

/**
 * Add body classes for ER pages.
 *
 * @param array $classes Body Classes.
 *
 * @return array
 */
function er_body_class( $classes ) {
	$classes = (array) $classes;

	if ( is_easyreservations() ) {
		$classes[] = 'easyreservations';
		$classes[] = 'easyreservations-page';
	} elseif ( is_easyreservations_checkout() ) {
		$classes[] = 'easyreservations-checkout';
		$classes[] = 'easyreservations-page';
	} elseif ( is_easyreservations_cart() ) {
		$classes[] = 'easyreservations-cart';
		$classes[] = 'easyreservations-page';
	} elseif ( is_easyreservations_search() ) {
		$classes[] = 'easyreservations-search';
		$classes[] = 'easyreservations-page';
	} elseif ( is_easyreservations_account_page() ) {
		$classes[] = 'easyreservations-account';
		$classes[] = 'easyreservations-page';
	}

	foreach ( ER()->query->get_query_vars() as $key => $value ) {
		if ( is_er_endpoint_url( $key ) ) {
			$classes[] = 'easyreservations-' . sanitize_html_class( $key );
		}
	}

	$classes[] = 'easyreservations-no-js';

	add_action( 'wp_footer', 'er_no_js' );

	return array_unique( $classes );
}

add_action( 'body_class', 'er_body_class' );

/**
 * NO JS handling.
 */
function er_no_js() {
	?>
    <script type="text/javascript">
		( function() {
			var c = document.body.className;
			c = c.replace( /easyreservations-no-js/, 'easyreservations-js' );
		    document.body.className = c;
		})();
    </script>
	<?php
}

/**
 * Display the classes for the resource div.
 *
 * @param string|array            $class One or more classes to add to the class list.
 * @param int|WP_Post|ER_Resource $resource_id Resource ID or resource object.
 */
function er_resource_class( $class = '', $resource_id = null ) {
	echo 'class="' . esc_attr( implode( ' ', er_get_resource_class( $class, $resource_id ) ) ) . '"';
}

/**
 * Retrieves the classes for the post div as an array.
 *
 * @param string|array            $class One or more classes to add to the class list.
 * @param int|WP_Post|ER_Resource $resource Resource ID or resource object.
 *
 * @return array
 */
function er_get_resource_class( $class = '', $resource = null ) {
	if ( is_null( $resource ) && ! empty( $GLOBALS['resource'] ) ) {
		// Resource was null so pull from global.
		$resource = $GLOBALS['resource'];
	}

	if ( $resource && ! is_a( $resource, 'ER_Resource' ) ) {
		// Make sure we have a valid resource, or set to false.
		$resource = ER()->resources()->get( $resource );
	}

	if ( $class ) {
		if ( ! is_array( $class ) ) {
			$class = preg_split( '#\s+#', $class );
		}
	} else {
		$class = array();
	}

	$post_classes = array_map( 'esc_attr', $class );

	if ( ! $resource ) {
		return $post_classes;
	}

	// Run through the post_class hook so 3rd parties using this previously can still append classes.
	// Note, to change classes you will need to use the newer easyreservations_post_class filter.
	// @internal This removes the er_resource_post_class filter so classes are not duplicated.
	$filtered = has_filter( 'post_class', 'er_resource_post_class' );

	if ( $filtered ) {
		remove_filter( 'post_class', 'er_resource_post_class', 20 );
	}

	$post_classes = apply_filters( 'post_class', $post_classes, $class, $resource->get_id() );

	if ( $filtered ) {
		add_filter( 'post_class', 'er_resource_post_class', 20, 3 );
	}

	$classes = array_merge(
		$post_classes,
		array(
			'resource',
			'type-resource',
			'post-' . $resource->get_id(),
			er_get_loop_class()
		)
	);

	if ( $resource->get_image_id() ) {
		$classes[] = 'has-post-thumbnail';
	}
	if ( $resource->get_post_password() ) {
		$classes[] = post_password_required( $resource->get_id() ) ? 'post-password-required' : 'post-password-protected';
	}
	if ( $resource->get_featured() ) {
		$classes[] = 'featured';
	}

	if ( $resource->is_on_sale() ) {
		$classes[] = 'onsale';
	}

	/**
	 * easyReservations Post Class filter.
	 *
	 * @param array       $classes Array of CSS classes.
	 * @param ER_Resource $resource Resource object.
	 */
	$classes = apply_filters( 'easyreservations_post_class', $classes, $resource );

	return array_map( 'esc_attr', array_unique( array_filter( $classes ) ) );
}

/**
 * When the_post is called, put resource data into a global.
 *
 * @param mixed $post Post Object.
 *
 * @return ER_Resource
 */
function er_setup_resource_data( $post ) {
	unset( $GLOBALS['resource'] );

	if ( is_int( $post ) ) {
		$post = get_post( $post );
	}

	if ( empty( $post->post_type ) || $post->post_type !== 'easy-rooms' ) {
		return;
	}

	$GLOBALS['resource'] = ER()->resources()->get( $post );

	return $GLOBALS['resource'];
}

add_action( 'the_post', 'er_setup_resource_data' );

/**
 * Sets up the easyreservations_loop global from the passed args or from the main query.
 *
 * @param array $args Args to pass into the global.
 */
function er_setup_loop( $args = array() ) {
	$default_args = array(
		'loop'         => 0,
		'columns'      => er_get_default_resources_per_row(),
		'name'         => '',
		'is_shortcode' => false,
		'is_paginated' => true,
		'is_search'    => false,
		'is_filtered'  => false,
		'total'        => 0,
		'total_pages'  => 0,
		'per_page'     => 0,
		'current_page' => 1,
	);

	// If this is a main ER query, use global args as defaults.
	if ( $GLOBALS['wp_query']->get( 'er_query' ) ) {
		$default_args = array_merge(
			$default_args,
			array(
				'is_search'    => $GLOBALS['wp_query']->is_search(),
				'total'        => $GLOBALS['wp_query']->found_posts,
				'total_pages'  => $GLOBALS['wp_query']->max_num_pages,
				'per_page'     => $GLOBALS['wp_query']->get( 'posts_per_page' ),
				'current_page' => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
			)
		);
	}

	// Merge any existing values.
	if ( isset( $GLOBALS['easyreservations_loop'] ) ) {
		$default_args = array_merge( $default_args, $GLOBALS['easyreservations_loop'] );
	}

	$GLOBALS['easyreservations_loop'] = wp_parse_args( $args, $default_args );
}

add_action( 'easyreservations_before_shop_loop', 'er_setup_loop' );

/**
 * Resets the easyreservations_loop global.
 */
function er_reset_loop() {
	unset( $GLOBALS['easyreservations_loop'] );
}

add_action( 'easyreservations_after_shop_loop', 'er_reset_loop', 999 );

/**
 * Gets a property from the easyreservations_loop global.
 *
 * @param string $prop Prop to get.
 * @param string $default Default if the prop does not exist.
 *
 * @return mixed
 */
function er_get_loop_prop( $prop, $default = '' ) {
	er_setup_loop(); // Ensure shop loop is setup.

	return isset( $GLOBALS['easyreservations_loop'], $GLOBALS['easyreservations_loop'][ $prop ] ) ? $GLOBALS['easyreservations_loop'][ $prop ] : $default;
}

/**
 * Sets a property in the easyreservations_loop global.
 *
 * @param string $prop Prop to set.
 * @param string $value Value to set.
 */
function er_set_loop_prop( $prop, $value = '' ) {
	if ( ! isset( $GLOBALS['easyreservations_loop'] ) ) {
		er_setup_loop();
	}
	$GLOBALS['easyreservations_loop'][ $prop ] = $value;
}

/**
 * Get classname for easyreservations loops.
 *
 * @return string
 */
function er_get_loop_class() {
	$loop_index = er_get_loop_prop( 'loop', 0 );
	$columns    = absint( max( 1, er_get_loop_prop( 'columns', er_get_default_resources_per_row() ) ) );

	$loop_index ++;
	er_set_loop_prop( 'loop', $loop_index );

	if ( 0 === ( $loop_index - 1 ) % $columns || 1 === $columns ) {
		return 'first';
	}

	if ( 0 === $loop_index % $columns ) {
		return 'last';
	}

	return '';
}

/**
 * Get the default columns setting - this is how many resources will be shown per row in loops.
 *
 * @return int
 */
function er_get_default_resources_per_row() {
	$columns       = get_option( 'reservations_catalog_columns', 4 );
	$resource_grid = er_get_theme_support( 'resource_grid' );
	$min_columns   = isset( $resource_grid['min_columns'] ) ? absint( $resource_grid['min_columns'] ) : 0;
	$max_columns   = isset( $resource_grid['max_columns'] ) ? absint( $resource_grid['max_columns'] ) : 0;

	if ( $min_columns && $columns < $min_columns ) {
		$columns = $min_columns;
		update_option( 'reservations_catalog_columns', $columns );
	} elseif ( $max_columns && $columns > $max_columns ) {
		$columns = $max_columns;
		update_option( 'reservations_catalog_columns', $columns );
	}

	$columns = absint( $columns );

	return max( 1, $columns );
}

/**
 * Get the default rows setting - this is how many resource rows will be shown in loops.
 *
 * @return int
 */
function er_get_default_resource_rows_per_page() {
	$rows          = absint( get_option( 'reservations_catalog_rows', 4 ) );
	$resource_grid = er_get_theme_support( 'resource_grid' );
	$min_rows      = isset( $resource_grid['min_rows'] ) ? absint( $resource_grid['min_rows'] ) : 0;
	$max_rows      = isset( $resource_grid['max_rows'] ) ? absint( $resource_grid['max_rows'] ) : 0;

	if ( $min_rows && $rows < $min_rows ) {
		$rows = $min_rows;
		update_option( 'reservations_catalog_rows', $rows );
	} elseif ( $max_rows && $rows > $max_rows ) {
		$rows = $max_rows;
		update_option( 'reservations_catalog_rows', $rows );
	}

	return $rows;
}

/**
 * Reset the resource grid settings when a new theme is activated.
 */
function er_reset_resource_grid_settings() {
	$resource_grid = er_get_theme_support( 'resource_grid' );

	if ( ! empty( $resource_grid['default_rows'] ) ) {
		update_option( 'reservations_catalog_rows', absint( $resource_grid['default_rows'] ) );
	}

	if ( ! empty( $resource_grid['default_columns'] ) ) {
		update_option( 'reservations_catalog_columns', absint( $resource_grid['default_columns'] ) );
	}

	wp_cache_flush(); // Flush any caches which could impact settings or templates.
}

add_action( 'after_switch_theme', 'er_reset_resource_grid_settings' );

/**
 * Output the view cart button.
 */
function easyreservations_widget_shopping_cart_button_view_cart() {
    echo '<a href="' . esc_url( er_get_cart_url() ) . '" class="button er-forward">' . esc_html__( 'View cart', 'easyReservations' ) . '</a>';
}

/**
 * Output the proceed to checkout button.
 */
function easyreservations_widget_shopping_cart_proceed_to_checkout() {
    echo '<a href="' . esc_url( er_get_checkout_url() ) . '" class="button checkout er-forward">' . esc_html__( 'Checkout', 'easyReservations' ) . '</a>';
}

/**
 * Output to view cart subtotal.
 */
function easyreservations_widget_shopping_cart_subtotal() {
    echo '<strong>' . esc_html__( 'Subtotal:', 'easyReservations' ) . '</strong> ' . ER()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Output the Mini-cart - used by cart widget.
 *
 * @param array $args Arguments.
 */
function easyreservations_mini_cart( $args = array() ) {

	$defaults = array(
		'list_class' => '',
	);

	$args = wp_parse_args( $args, $defaults );

	er_get_template( 'cart/mini-cart.php', $args );
}

/**
 * Output the easyReservations Login Form.
 *
 * @param array $args Arguments.
 */
function easyreservations_login_form( $args = array() ) {
	$defaults = array(
		'message'  => '',
		'redirect' => '',
		'hidden'   => false,
	);

	$args = wp_parse_args( $args, $defaults );

	er_get_template( 'global/form-login.php', $args );
}

/**
 * Output the cart totals.
 */
function er_cart_totals() {
	er_get_template( 'cart/cart-totals.php' );
}

/**
 * Output checkout login form.
 */
function easyreservations_checkout_login_form() {
	er_get_template(
		'checkout/login.php'
	);
}

/**
 * Output review order table.
 */
function easyreservations_order_review() {
	er_get_template(
		'checkout/review-order.php'
	);
}

/**
 * Output checkout submit.
 */
function easyreservations_checkout_submit() {
	er_get_template(
		'checkout/submit.php',
		array(
			'button_text' => apply_filters( 'easyreservations_order_button_text', __( 'Place order', 'easyReservations' ) ),
		)
	);
}

/**
 * Output checkout terms.
 */
function easyreservations_checkout_terms() {
	er_get_template(
		'checkout/terms.php'
	);
}

/**
 * Output proceed to checkout button.
 */
function er_button_proceed_to_checkout() {
	er_get_template( 'cart/proceed-to-checkout-button.php' );
}

/**
 * Output the start of the page wrapper.
 */
function easyreservations_output_content_wrapper() {
	er_get_template( 'global/wrapper-start.php' );
}

/**
 * Output the end of the page wrapper.
 */
function easyreservations_output_content_wrapper_end() {
	er_get_template( 'global/wrapper-end.php' );
}

/**
 * Get the shop sidebar template.
 */
function easyreservations_get_sidebar() {
	er_get_template( 'global/sidebar.php' );
}

function easyreservations_show_resource_images() {
	er_get_template( 'single-resource/resource-image.php' );
}

/**
 * Output the resource thumbnails.
 */
function easyreservations_show_resource_thumbnails() {
	er_get_template( 'single-resource/resource-thumbnails.php' );
}

/**
 * Insert the opening anchor tag for resources in the loop.
 */
function easyreservations_template_loop_resource_link_open() {
	global $resource;

	$link = apply_filters( 'easyreservations_loop_resource_link', get_the_permalink(), $resource );

	echo '<a href="' . esc_url( $link ) . '" class="easyreservations-LoopResource-link easyreservations-loop-resource__link">';
}

/**
 * Insert the closing anchor tag for resources in the loop.
 */
function easyreservations_template_loop_resource_link_close() {
	echo '</a>';
}

/**
 * Show the resource title in the resource loop. By default this is an H2.
 */
function easyreservations_template_loop_resource_title() {
	echo '<h2 class="' . esc_attr( apply_filters( 'easyreservations_resource_loop_title_classes', 'easyreservations-loop-resource__title' ) ) . '">' . get_the_title() . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get the resource price for the loop.
 */
function easyreservations_template_loop_price() {
	er_get_template( 'loop/price.php' );
}

/**
 * Get the resource price for the loop.
 */
function easyreservations_template_loop_sale_flash() {
	er_get_template( 'loop/sale-flash.php' );
}

/**
 * Get the resource button for the loop.
 */
function easyreservations_template_loop_button() {
	er_get_template( 'loop/button.php' );
}

/**
 * Get the resource thumbnail, or the placeholder if not set.
 *
 * @param string $size (default: 'easyreservations_thumbnail').
 */
function easyreservations_get_resource_thumbnail( $size = 'easyreservations_thumbnail' ) {
	global $resource;

	$image_size = apply_filters( 'easyreservations_single_resource_archive_thumbnail_size', $size );

	echo $resource ? $resource->get_image( $image_size ) : '';
}

/**
 * Get HTML for a gallery image.
 *
 * Hooks: easyreservations_gallery_thumbnail_size, easyreservations_gallery_image_size and easyreservations_gallery_full_size accept name based image sizes, or an array of width/height values.
 *
 * @param int  $attachment_id Attachment ID.
 * @param bool $main_image Is this the main image or a thumbnail?.
 *
 * @return string
 */
function er_get_gallery_image_html( $attachment_id, $main_image = false ) {
	$flexslider        = (bool) apply_filters( 'easyreservations_single_resource_flexslider_enabled', get_theme_support( 'er-resource-gallery-slider' ) );
	$gallery_thumbnail = er_get_image_size( 'gallery_thumbnail' );
	$thumbnail_size    = apply_filters( 'easyreservations_gallery_thumbnail_size', array(
		$gallery_thumbnail['width'],
		$gallery_thumbnail['height']
	) );
	$image_size        = apply_filters( 'easyreservations_gallery_image_size', $flexslider || $main_image ? 'easyreservations_single' : $thumbnail_size );
	$full_size         = apply_filters( 'easyreservations_gallery_full_size', apply_filters( 'easyreservations_resource_thumbnails_large_size', 'full' ) );
	$thumbnail_src     = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
	$full_src          = wp_get_attachment_image_src( $attachment_id, $full_size );
	$alt_text          = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
	$image             = wp_get_attachment_image(
		$attachment_id,
		$image_size,
		false,
		apply_filters(
			'easyreservations_gallery_image_html_attachment_image_params',
			array(
				'title'                   => _wp_specialchars( get_post_field( 'post_title', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
				'data-caption'            => _wp_specialchars( get_post_field( 'post_excerpt', $attachment_id ), ENT_QUOTES, 'UTF-8', true ),
				'data-src'                => esc_url( $full_src[0] ),
				'data-large_image'        => esc_url( $full_src[0] ),
				'data-large_image_width'  => esc_attr( $full_src[1] ),
				'data-large_image_height' => esc_attr( $full_src[2] ),
				'class'                   => esc_attr( $main_image ? 'wp-post-image' : '' ),
			),
			$attachment_id,
			$image_size,
			$main_image
		)
	);

	return '<div data-thumb="' . esc_url( $thumbnail_src[0] ) . '" data-thumb-alt="' . esc_attr( $alt_text ) . '" class="easyreservations-resource-gallery__image"><a href="' . esc_url( $full_src[0] ) . '">' . $image . '</a></div>';
}

/**
 * Output the resource title.
 */
function easyreservations_template_single_title() {
	er_get_template( 'single-resource/title.php' );
}

/**
 * Output the resource price.
 */
function easyreservations_template_single_price() {
	if ( get_option( 'reservations_resource_page_display_price', 'yes' ) === 'yes' ) {
		er_get_template( 'single-resource/price.php' );
	}
}

/**
 * Output the resource form.
 */
function easyreservations_template_single_sale_flash() {
	er_get_template( 'single-resource/sale-flash.php' );
}

/**
 * Output the resource form.
 */
function easyreservations_template_single_form() {
	er_get_template( 'single-resource/form.php' );
}

/**
 * Output the resource short description (excerpt).
 */
function easyreservations_template_single_excerpt() {
	er_get_template( 'single-resource/short-description.php' );
}

/**
 * Output the resource sharing.
 */
function easyreservations_template_single_sharing() {
	er_get_template( 'single-resource/share.php' );
}

/**
 * Output the resource description.
 */
function easyreservations_template_single_description() {
	er_get_template( 'single-resource/description.php' );
}

function easyreservations_photoswipe() {
	if ( current_theme_supports( 'er-resource-gallery-lightbox' ) ) {
		er_get_template( 'single-resource/photoswipe.php' );
	}
}

/**
 * Loop
 */

/**
 * Page Title function.
 *
 * @param bool $echo Should echo title.
 *
 * @return string
 */
function easyreservations_page_title( $echo = true ) {
	if ( is_search() ) {
		/* translators: %s: search query */
		$page_title = sprintf( esc_html__( 'Search results: &ldquo;%s&rdquo;', 'easyReservations' ), get_search_query() );

		if ( get_query_var( 'paged' ) ) {
			/* translators: %s: page number */
			$page_title .= sprintf( esc_html__( '&nbsp;&ndash; Page %s', 'easyReservations' ), get_query_var( 'paged' ) );
		}
	} else {
		$shop_page_id = er_get_page_id( 'shop' );
		$page_title   = get_the_title( $shop_page_id );
	}

	$page_title = apply_filters( 'easyreservations_page_title', $page_title );

	if ( $echo ) {
		echo $page_title; // WPCS: XSS ok.
	} else {
		return $page_title;
	}
}

/**
 * Output the start of a resource loop. By default this is a UL.
 *
 * @param bool $echo Should echo?.
 *
 * @return string
 */
function easyreservations_resource_loop_start( $echo = true ) {
	ob_start();

	er_set_loop_prop( 'loop', 0 );

	er_get_template( 'loop/loop-start.php' );

	$loop_start = apply_filters( 'easyreservations_resource_loop_start', ob_get_clean() );

	if ( $echo ) {
		echo $loop_start; // WPCS: XSS ok.
	} else {
		return $loop_start;
	}
}

/**
 * Output the end of a resource loop. By default this is a UL.
 *
 * @param bool $echo Should echo?.
 *
 * @return string
 */
function easyreservations_resource_loop_end( $echo = true ) {
	ob_start();

	er_get_template( 'loop/loop-end.php' );

	$loop_end = apply_filters( 'easyreservations_resource_loop_end', ob_get_clean() );

	if ( $echo ) {
		echo $loop_end; // WPCS: XSS ok.
	} else {
		return $loop_end;
	}
}

/**
 * Handles the loop when no resources were found/no resource exist.
 */
function er_no_resources_found() {
	er_get_template( 'loop/no-resources-found.php' );
}

/**
 * Show a shop page description on resource archives.
 */
function easyreservations_resource_archive_description() {
	// Don't display the description on search results page.
	if ( is_search() ) {
		return;
	}

	if ( is_post_type_archive( 'easy-rooms' ) && in_array( absint( get_query_var( 'paged' ) ), array( 0, 1 ), true ) ) {
		$shop_page = get_post( er_get_page_id( 'shop' ) );
		if ( $shop_page ) {
			$description = er_format_content( $shop_page->post_content );
			if ( $description ) {
				echo '<div class="page-description">' . $description . '</div>'; // WPCS: XSS ok.
			}
		}
	}
}

/**
 * Output the result count text (Showing x - x of x results).
 */
function easyreservations_result_count() {
	if ( ! er_get_loop_prop( 'is_paginated' ) || 0 > er_get_loop_prop( 'total', 0 ) ) {
		return;
	}

	$args = array(
		'total'    => er_get_loop_prop( 'total' ),
		'per_page' => er_get_loop_prop( 'per_page' ),
		'current'  => er_get_loop_prop( 'current_page' ),
	);

	er_get_template( 'loop/result-count.php', $args );
}

/**
 * Output the resource sorting options.
 */
function easyreservations_catalog_ordering() {
	if ( ! er_get_loop_prop( 'is_paginated' ) || 0 > er_get_loop_prop( 'total', 0 ) ) {
		return;
	}

	$show_default_orderby    = 'menu_order' === apply_filters( 'easyreservations_default_catalog_orderby', get_option( 'reservations_default_catalog_orderby', 'menu_order' ) );
	$catalog_orderby_options = apply_filters(
		'easyreservations_catalog_orderby',
		array(
			'menu_order' => __( 'Default sorting', 'easyReservations' ),
			'date'       => __( 'Sort by latest', 'easyReservations' ),
			'price'      => __( 'Sort by price: low to high', 'easyReservations' ),
			'price-desc' => __( 'Sort by price: high to low', 'easyReservations' ),
		)
	);

	$default_orderby = er_get_loop_prop( 'is_search' ) ? 'relevance' : apply_filters( 'easyreservations_default_catalog_orderby', get_option( 'reservations_default_catalog_orderby', '' ) );
	$orderby         = isset( $_GET['orderby'] ) ? er_clean( wp_unslash( $_GET['orderby'] ) ) : $default_orderby; // WPCS: sanitization ok, input var ok, CSRF ok.

	if ( er_get_loop_prop( 'is_search' ) ) {
		$catalog_orderby_options = array_merge( array( 'relevance' => __( 'Relevance', 'easyReservations' ) ), $catalog_orderby_options );

		unset( $catalog_orderby_options['menu_order'] );
	}

	if ( ! $show_default_orderby ) {
		unset( $catalog_orderby_options['menu_order'] );
	}

	if ( ! array_key_exists( $orderby, $catalog_orderby_options ) ) {
		$orderby = current( array_keys( $catalog_orderby_options ) );
	}

	er_get_template(
		'loop/orderby.php',
		array(
			'catalog_orderby_options' => $catalog_orderby_options,
			'orderby'                 => $orderby,
			'show_default_orderby'    => $show_default_orderby,
		)
	);
}

/**
 * Output the pagination.
 */
function easyreservations_pagination() {
	if ( ! er_get_loop_prop( 'is_paginated' ) || 0 > er_get_loop_prop( 'total', 0 ) ) {
		return;
	}

	$args = array(
		'total'   => er_get_loop_prop( 'total_pages' ),
		'current' => er_get_loop_prop( 'current_page' ),
		'base'    => esc_url_raw( add_query_arg( 'resource-page', '%#%', false ) ),
		'format'  => '?resource-page=%#%',
	);

	if ( ! er_get_loop_prop( 'is_shortcode' ) ) {
		$args['format'] = '';
		$args['base']   = esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
	}

	er_get_template( 'loop/pagination.php', $args );
}

/**
 * Output t&c page's content (if set). The page can be set from checkout settings.
 */
function er_terms_and_conditions_page_content() {
	$terms_page_id = er_terms_and_conditions_page_id();

	if ( ! $terms_page_id ) {
		return;
	}

	$page = get_post( $terms_page_id );

	if ( $page && 'publish' === $page->post_status && $page->post_content && ! has_shortcode( $page->post_content, 'er_checkout' ) ) {
		echo '<div class="easyreservations-terms-and-conditions" style="display: none; max-height: 200px; overflow: auto;">' . wp_kses_post( er_format_content( $page->post_content ) ) . '</div>';
	}
}

/**
 * Render privacy policy text on the checkout.
 */
function er_checkout_privacy_policy_text() {
	echo '<div class="easyreservations-privacy-policy-text">';
	er_privacy_policy_text( 'checkout' );
	echo '</div>';
}

/**
 * Render privacy policy text on the register forms.
 */
function er_registration_privacy_policy_text() {
	echo '<div class="easyreservations-privacy-policy-text">';
	er_privacy_policy_text( 'registration' );
	echo '</div>';
}

/**
 * Output privacy policy text. This is custom text which can be added via the customizer/privacy settings section.
 *
 * Loads the relevant policy for the current page unless a specific policy text is required.
 *
 * @param string $type Type of policy to load. Valid values include registration and checkout.
 */
function er_privacy_policy_text( $type = 'checkout' ) {
	if ( ! er_privacy_policy_page_id() ) {
		return;
	}
	echo wp_kses_post( wpautop( er_replace_policy_page_link_placeholders( er_get_privacy_policy_text( $type ) ) ) );
}

/**
 * Replaces placeholders with links to ER policy pages.
 *
 * @param string $text Text to find/replace within.
 *
 * @return string
 */
function er_replace_policy_page_link_placeholders( $text ) {
	$privacy_page_id = er_privacy_policy_page_id();
	$terms_page_id   = er_terms_and_conditions_page_id();
	$privacy_link    = $privacy_page_id ? '<a href="' . esc_url( get_permalink( $privacy_page_id ) ) . '" class="easyreservations-privacy-policy-link" target="_blank">' . __( 'privacy policy', 'easyReservations' ) . '</a>' : __( 'privacy policy', 'easyReservations' );
	$terms_link      = $terms_page_id ? '<a href="' . esc_url( get_permalink( $terms_page_id ) ) . '" class="easyreservations-terms-and-conditions-link" target="_blank">' . __( 'terms and conditions', 'easyReservations' ) . '</a>' : __( 'terms and conditions', 'easyReservations' );

	$find_replace = array(
		'[terms]'          => $terms_link,
		'[privacy_policy]' => $privacy_link,
	);

	return str_replace( array_keys( $find_replace ), array_values( $find_replace ), $text );
}

/**
 * Get the privacy policy page ID.
 *
 * @return int
 */
function er_privacy_policy_page_id() {
	$page_id = get_option( 'wp_page_for_privacy_policy', 0 );

	return apply_filters( 'easyreservations_privacy_policy_page_id', 0 < $page_id ? absint( $page_id ) : 0 );
}

/**
 * Get the terms and conditions page ID.
 *
 * @return int
 */
function er_terms_and_conditions_page_id() {
	$page_id = er_get_page_id( 'terms' );

	return apply_filters( 'easyreservations_terms_and_conditions_page_id', 0 < $page_id ? absint( $page_id ) : 0 );
}

/**
 * See if the checkbox is enabled or not based on the existance of the terms page and checkbox text.
 *
 * @return bool
 */
function er_terms_and_conditions_checkbox_enabled() {
	$page_id = er_terms_and_conditions_page_id();
	$page    = $page_id ? get_post( $page_id ) : false;

	return $page && er_get_terms_and_conditions_checkbox_text();
}

/**
 * Get the terms and conditions checkbox text, if set.
 *
 * @return string
 */
function er_get_terms_and_conditions_checkbox_text() {
	/* translators: %s terms and conditions page name and link */
	return trim( apply_filters( 'easyreservations_get_terms_and_conditions_checkbox_text', get_option( 'reservations_checkout_terms_and_conditions_checkbox_text', sprintf( __( 'I have read and agree to the website %s', 'easyReservations' ), '[terms]' ) ) ) );
}

/**
 * Output t&c checkbox text.
 */
function er_terms_and_conditions_checkbox_text() {
	$text = er_get_terms_and_conditions_checkbox_text();

	if ( ! $text ) {
		return;
	}

	echo wp_kses_post( er_replace_policy_page_link_placeholders( $text ) );
}

/**
 * Get the privacy policy text, if set.
 *
 * @param string $type Type of policy to load. Valid values include registration and checkout.
 *
 * @return string
 */
function er_get_privacy_policy_text( $type = '' ) {
	$text = '';

	switch ( $type ) {
		case 'checkout':
			/* translators: %s privacy policy page name and link */
			$text = get_option( 'reservations_checkout_privacy_policy_text', sprintf( __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.', 'easyReservations' ), '[privacy_policy]' ) );
			break;
		case 'registration':
			/* translators: %s privacy policy page name and link */
			$text = get_option( 'reservations_registration_privacy_policy_text', sprintf( __( 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.', 'easyReservations' ), '[privacy_policy]' ) );
			break;
	}

	return trim( apply_filters( 'reservations_get_privacy_policy_text', $text, $type ) );
}

/**
 * Displays order details in a table.
 *
 * @param mixed $order_id Order ID.
 */
function er_order_details_table( $order_id ) {
	if ( ! $order_id ) {
		return;
	}

	er_get_template(
		'order/order-details.php',
		array(
			'order_id' => $order_id,
		)
	);
}

/**
 * Outputs a checkout/address form field.
 *
 * @param string $key Key.
 * @param mixed  $args Arguments.
 * @param string $value (default: null).
 *
 * @return string
 */
function easyreservations_form_field( $key, $args, $value = null ) {
	$defaults = array(
		'type'              => 'text',
		'label'             => '',
		'description'       => '',
		'placeholder'       => '',
		'maxlength'         => false,
		'required'          => false,
		'autocomplete'      => false,
		'id'                => $key,
		'class'             => array(),
		'label_class'       => array(),
		'input_class'       => array(),
		'return'            => false,
		'options'           => array(),
		'custom_attributes' => array(),
		'validate'          => array(),
		'default'           => '',
		'autofocus'         => '',
		'priority'          => '',
	);

	$args = wp_parse_args( $args, $defaults );
	$args = apply_filters( 'easyreservations_form_field_args', $args, $key, $value );

	if ( $args['required'] ) {
		$args['class'][] = 'validate-required';
		$required        = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'easyReservations' ) . '">*</abbr>';
	} else {
		$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'easyReservations' ) . ')</span>';
	}

	if ( is_string( $args['label_class'] ) ) {
		$args['label_class'] = array( $args['label_class'] );
	}

	if ( is_null( $value ) ) {
		$value = $args['default'];
	}

	// Custom attribute handling.
	$custom_attributes         = array();
	$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

	if ( $args['maxlength'] ) {
		$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
	}

	if ( ! empty( $args['autocomplete'] ) ) {
		$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
	}

	if ( true === $args['autofocus'] ) {
		$args['custom_attributes']['autofocus'] = 'autofocus';
	}

	if ( $args['description'] ) {
		$args['custom_attributes']['aria-describedby'] = $args['id'] . '-description';
	}

	if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
		foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
		}
	}

	if ( ! empty( $args['validate'] ) ) {
		foreach ( $args['validate'] as $validate ) {
			$args['class'][] = 'validate-' . $validate;
		}
	}

	$field           = '';
	$label_id        = $args['id'];
	$sort            = $args['priority'] ? $args['priority'] : '';
	$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

	switch ( $args['type'] ) {
		case 'country':
			$countries = ER()->countries->get_countries();

			if ( 1 === count( $countries ) ) {

				$field .= '<strong>' . current( array_values( $countries ) ) . '</strong>';

				$field .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . current( array_keys( $countries ) ) . '" ' . implode( ' ', $custom_attributes ) . ' class="country_to_state" readonly="readonly" />';
			} else {

				$field = '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="country_to_state country_select default-disabled ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ? $args['placeholder'] : esc_attr__( 'Select a country / region&hellip;', 'easyReservations' ) ) . '"><option value="">' . esc_html__( 'Select a country / region&hellip;', 'easyReservations' ) . '</option>';

				foreach ( $countries as $ckey => $cvalue ) {
					$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
				}

				$field .= '</select>';
			}

			break;
		case 'state':
			/* Get country this state field is representing */
			$for_country = isset( $args['country'] ) ? $args['country'] : ER()->checkout()->get_value( 'country' );
			$states      = ER()->countries->get_states( $for_country );

			if ( is_array( $states ) && empty( $states ) ) {

				$field_container = '<p class="form-row %1$s" id="%2$s" style="display: none">%3$s</p>';

				$field .= '<input type="hidden" class="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="" ' . implode( ' ', $custom_attributes ) . ' placeholder="' . esc_attr( $args['placeholder'] ) . '" readonly="readonly" data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"/>';
			} elseif ( ! is_null( $for_country ) && is_array( $states ) ) {

				$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="state_select default-disabled ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ? $args['placeholder'] : esc_html__( 'Select an option&hellip;', 'easyReservations' ) ) . '"  data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '">
						<option value="">' . esc_html__( 'Select an option&hellip;', 'easyReservations' ) . '</option>';

				foreach ( $states as $ckey => $cvalue ) {
					$field .= '<option value="' . esc_attr( $ckey ) . '" ' . selected( $value, $ckey, false ) . '>' . esc_html( $cvalue ) . '</option>';
				}

				$field .= '</select>';
			} else {

				$field .= '<input type="text" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $value ) . '"  placeholder="' . esc_attr( $args['placeholder'] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" ' . implode( ' ', $custom_attributes ) . ' data-input-classes="' . esc_attr( implode( ' ', $args['input_class'] ) ) . '"/>';
			}

			break;
		case 'textarea':
			$field .= '<textarea name="' . esc_attr( $key ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . ( empty( $args['custom_attributes']['rows'] ) ? ' rows="2"' : '' ) . ( empty( $args['custom_attributes']['cols'] ) ? ' cols="5"' : '' ) . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $value ) . '</textarea>';

			break;
		case 'checkbox':
			$field = '<label class="checkbox ' . implode( ' ', $args['label_class'] ) . '" ' . implode( ' ', $custom_attributes ) . '>
						<input type="' . esc_attr( $args['type'] ) . '" class="input-checkbox ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="1" ' . checked( $value, 1, false ) . ' /> ' . $args['label'] . $required . '</label>';

			break;
		case 'text':
		case 'password':
		case 'datetime':
		case 'datetime-local':
		case 'date':
		case 'month':
		case 'time':
		case 'week':
		case 'number':
		case 'email':
		case 'url':
		case 'tel':
			$field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-text ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '"  value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

			break;
		case 'hidden':
			$field .= '<input type="' . esc_attr( $args['type'] ) . '" class="input-hidden ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" value="' . esc_attr( $value ) . '" ' . implode( ' ', $custom_attributes ) . ' />';

			break;
		case 'select':
			$field   = '';
			$options = '';

			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $option_key => $option_text ) {
					if ( '' === $option_key ) {
						// If we have a blank option, select2 needs a placeholder.
						if ( empty( $args['placeholder'] ) ) {
							$args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'easyReservations' );
						}
						$custom_attributes[] = 'data-allow_clear="true"';
					}
					$options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_html( $option_text ) . '</option>';
				}

				$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select default-disabled ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
							' . $options . '
						</select>';
			}

			break;
		case 'radio':
			$label_id .= '_' . current( array_keys( $args['options'] ) );

			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $option_key => $option_text ) {
					$field .= '<input type="radio" class="input-radio ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" value="' . esc_attr( $option_key ) . '" name="' . esc_attr( $key ) . '" ' . implode( ' ', $custom_attributes ) . ' id="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '"' . checked( $value, $option_key, false ) . ' />';
					$field .= '<label for="' . esc_attr( $args['id'] ) . '_' . esc_attr( $option_key ) . '" class="radio ' . implode( ' ', $args['label_class'] ) . '">' . esc_html( $option_text ) . '</label>';
				}
			}

			break;
	}

	if ( ! empty( $field ) ) {
		$field_html = '';

		if ( $args['label'] && 'checkbox' !== $args['type'] ) {
			$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . wp_kses_post( $args['label'] ) . $required . '</label>';
		}

		$field_html .= '<span class="easyreservations-input-wrapper">' . $field;

		if ( $args['description'] ) {
			$field_html .= '<span class="description" id="' . esc_attr( $args['id'] ) . '-description" aria-hidden="true">' . wp_kses_post( $args['description'] ) . '</span>';
		}

		$field_html .= '</span>';

		$container_class = esc_attr( implode( ' ', $args['class'] ) );
		$container_id    = esc_attr( $args['id'] ) . '_field';
		$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
	}

	/**
	 * Filter by type.
	 */
	$field = apply_filters( 'easyreservations_form_field_' . $args['type'], $field, $key, $args, $value );

	/**
	 * General filter on form fields.
	 */
	$field = apply_filters( 'easyreservations_form_field', $field, $key, $args, $value );

	if ( $args['return'] ) {
		return $field;
	} else {
		echo $field; // WPCS: XSS ok.
	}
}

/**
 * Get HTML for the order items to be shown in emails.
 *
 * @param ER_Order $order Order object.
 * @param array    $args Arguments.
 *
 * @return string
 */
function er_get_email_order_items( $order, $args = array() ) {
	ob_start();

	$defaults = array(
		'show_sku'      => false,
		'show_image'    => false,
		'image_size'    => array( 32, 32 ),
		'plain_text'    => false,
		'sent_to_admin' => false,
	);

	$args     = wp_parse_args( $args, $defaults );
	$template = $args['plain_text'] ? 'emails/plain/email-order-items.php' : 'emails/email-order-items.php';

	er_get_template(
		$template,
		apply_filters(
			'easyreservations_email_order_items_args',
			array(
				'order'              => $order,
				'items'              => $order->get_items(),
				'show_sku'           => $args['show_sku'],
				'show_purchase_note' => $order->is_paid() && ! $args['sent_to_admin'],
				'show_image'         => $args['show_image'],
				'image_size'         => $args['image_size'],
				'plain_text'         => $args['plain_text'],
				'sent_to_admin'      => $args['sent_to_admin'],
			)
		)
	);

	return apply_filters( 'easyreservations_email_order_items_table', ob_get_clean(), $order );
}

/**
 * Display item meta data.
 *
 * @param array $data data to display.
 * @param array $args Arguments.
 *
 * @return string|void
 */
function er_display_meta( $data, $args = array() ) {
	$strings = array();
	$html    = '';
	$args    = wp_parse_args(
		$args,
		array(
			'before'       => '<ul class="er-item-meta"><li>',
			'after'        => '</li></ul>',
			'separator'    => '</li><li>',
			'echo'         => true,
			'autop'        => false,
			'label_before' => '<strong class="er-item-meta-label">',
			'label_after'  => ':</strong> ',
		)
	);

	foreach ( $data as $meta_id => $meta ) {
		$value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
		$strings[] = $args['label_before'] . wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;
	}

	if ( $strings ) {
		$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
	}

	$html = apply_filters( 'easyreservations_display_meta', $html, $data, $args );

	if ( $args['echo'] ) {
		echo $html; // WPCS: XSS ok.
	} else {
		return $html;
	}
}

/**
 * Disable search engines indexing core, dynamic, cart/checkout pages.
 * Uses "wp_robots" filter introduced in WP 5.7.
 *
 * @param array $robots Associative array of robots directives.
 *
 * @return array Filtered robots directives.
 */
function er_page_no_robots( $robots ) {
	if ( is_page( er_get_page_id( 'cart' ) ) || is_page( er_get_page_id( 'checkout' ) ) || is_page( er_get_page_id( 'myaccount' ) ) ) {
		return wp_robots_no_robots( $robots );
	}

	return $robots;
}

add_filter( 'wp_robots', 'er_page_no_robots' );
