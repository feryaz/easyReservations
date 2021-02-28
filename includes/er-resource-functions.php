<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register resource post type
 */
function er_resource_register_post_type() {
	$permalinks   = er_get_permalink_structure();
	$shop_page_id = er_get_page_id( 'shop' );

	if ( current_theme_supports( 'easyreservations' ) ) {
		$has_archive = $shop_page_id && get_post( $shop_page_id ) ? urldecode( get_page_uri( $shop_page_id ) ) : 'shop';
	} else {
		$has_archive = false;
	}

	$labels = array(
		'name'                  => __( 'Resources', 'easyReservations' ),
		'singular_name'         => __( 'Resource', 'easyReservations' ),
		'add_new'               => sprintf( __( 'Add %s', 'easyReservations' ), __( 'resource', 'easyReservations' ) ),
		'add_new_item'          => sprintf( __( 'Add %s', 'easyReservations' ), __( 'resource', 'easyReservations' ) ),
		'edit_item'             => sprintf( __( 'Edit %s', 'easyReservations' ), __( 'resource', 'easyReservations' ) ),
		'new_item'              => sprintf( __( 'New %s', 'easyReservations' ), __( 'resource', 'easyReservations' ) ),
		'view_item'             => sprintf( __( 'View %s', 'easyReservations' ), __( 'resource', 'easyReservations' ) ),
		'featured_image'        => __( 'Resource image', 'easyReservations' ),
		'set_featured_image'    => __( 'Set resource image', 'easyReservations' ),
		'remove_featured_image' => __( 'Remove resource image', 'easyReservations' ),
		'use_featured_image'    => __( 'Use as resource image', 'easyReservations' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => current_user_can( 'manage_easyreservations' ) ? 'reservations' : true,
		'show_in_nav_menus'  => false,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => $permalinks['resource_rewrite_slug'] ? array(
			'slug'       => $permalinks['resource_rewrite_slug'],
			'with_front' => false,
			'feeds'      => true,
		) : false,
		'capability_type'    => 'easy_resource',
		'map_meta_cap'       => true,
		'has_archive'        => $has_archive,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array(
			'title',
			'editor',
			'author',
			'thumbnail',
			'excerpt',
			'comments',
			'custom-fields',
			'page-attributes'
		)
	);

	register_post_type( 'easy-rooms', $args );

	if ( ! is_blog_installed() ) {
		return;
	}

	if ( taxonomy_exists( 'resource_cat' ) ) {
		return;
	}

	$permalinks = er_get_permalink_structure();

	register_taxonomy(
		'resource_visibility',
		apply_filters( 'easyreservations_taxonomy_objects_resource_visibility', array( 'easy-rooms' ) ),
		apply_filters(
			'easyreservations_taxonomy_args_resource_visibility',
			array(
				'hierarchical'      => false,
				'show_ui'           => false,
				'show_in_nav_menus' => false,
				'query_var'         => is_admin(),
				'rewrite'           => false,
				'public'            => false,
			)
		)
	);

	register_taxonomy(
		'resource_cat',
		apply_filters( 'easyreservations_taxonomy_objects_resource_cat', array( 'easy-rooms' ) ),
		apply_filters(
			'easyreservations_taxonomy_args_resource_cat',
			array(
				'hierarchical'          => true,
				'update_count_callback' => '_er_term_recount',
				'label'                 => __( 'Categories', 'easyReservations' ),
				'labels'                => array(
					'name'              => __( 'Resource categories', 'easyReservations' ),
					'singular_name'     => __( 'Category', 'easyReservations' ),
					'menu_name'         => _x( 'Categories', 'Admin menu name', 'easyReservations' ),
					'search_items'      => __( 'Search categories', 'easyReservations' ),
					'all_items'         => __( 'All categories', 'easyReservations' ),
					'parent_item'       => __( 'Parent category', 'easyReservations' ),
					'parent_item_colon' => __( 'Parent category:', 'easyReservations' ),
					'edit_item'         => __( 'Edit category', 'easyReservations' ),
					'update_item'       => __( 'Update category', 'easyReservations' ),
					'add_new_item'      => __( 'Add new category', 'easyReservations' ),
					'new_item_name'     => __( 'New category name', 'easyReservations' ),
					'not_found'         => __( 'No categories found', 'easyReservations' ),
				),
				'show_ui'               => true,
				'query_var'             => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_easy-rooms_terms',
					'edit_terms'   => 'edit_easy-rooms_terms',
					'delete_terms' => 'delete_easy-rooms_terms',
					'assign_terms' => 'assign_easy-rooms_terms',
				),
				'rewrite'               => array(
					'slug'         => $permalinks['category_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		)
	);

	register_taxonomy(
		'resource_tag',
		apply_filters( 'easyreservations_taxonomy_objects_resource_tag', array( 'easy-rooms' ) ),
		apply_filters(
			'easyreservations_taxonomy_args_resource_tag',
			array(
				'hierarchical'          => false,
				'update_count_callback' => '_er_term_recount',
				'label'                 => __( 'Resource tags', 'easyReservations' ),
				'labels'                => array(
					'name'                       => __( 'Resource tags', 'easyReservations' ),
					'singular_name'              => __( 'Tag', 'easyReservations' ),
					'menu_name'                  => _x( 'Tags', 'Admin menu name', 'easyReservations' ),
					'search_items'               => __( 'Search tags', 'easyReservations' ),
					'all_items'                  => __( 'All tags', 'easyReservations' ),
					'edit_item'                  => __( 'Edit tag', 'easyReservations' ),
					'update_item'                => __( 'Update tag', 'easyReservations' ),
					'add_new_item'               => __( 'Add new tag', 'easyReservations' ),
					'new_item_name'              => __( 'New tag name', 'easyReservations' ),
					'popular_items'              => __( 'Popular tags', 'easyReservations' ),
					'separate_items_with_commas' => __( 'Separate tags with commas', 'easyReservations' ),
					'add_or_remove_items'        => __( 'Add or remove tags', 'easyReservations' ),
					'choose_from_most_used'      => __( 'Choose from the most used tags', 'easyReservations' ),
					'not_found'                  => __( 'No tags found', 'easyReservations' ),
				),
				'show_ui'               => true,
				'query_var'             => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_easy-rooms_terms',
					'edit_terms'   => 'edit_easy-rooms_terms',
					'delete_terms' => 'delete_easy-rooms_terms',
					'assign_terms' => 'assign_easy-rooms_terms',
				),
				'rewrite'               => array(
					'slug'       => $permalinks['tag_rewrite_slug'],
					'with_front' => false,
				),
			)
		)
	);
}

add_action( 'init', 'er_resource_register_post_type', 20 );

/**
 * Get resource visibility options.
 *
 * @return array
 */
function er_get_resource_visibility_options() {
	return apply_filters(
		'easyreservations_resource_visibility_options',
		array(
			'visible' => __( 'Catalog and search results', 'easyReservations' ),
			'catalog' => __( 'Catalog only', 'easyReservations' ),
			'search'  => __( 'Search results only', 'easyReservations' ),
			'hidden'  => __( 'Hidden', 'easyReservations' ),
		)
	);
}

/**
 * Get all resource cats for a resource by ID, including hierarchy
 *
 * @param int $resource_id resource ID.
 *
 * @return array
 */
function er_get_resource_cat_ids( $resource_id ) {
	$resource_categories = er_get_resource_term_ids( $resource_id, 'resource_cat' );

	foreach ( $resource_categories as $resource_cat ) {
		$resource_categories = array_merge( $resource_categories, get_ancestors( $resource_cat, 'resource_cat' ) );
	}

	return $resource_categories;
}

/**
 * Retrieves resource term ids for a taxonomy.
 *
 * @param int    $resource_id Resource ID.
 * @param string $taxonomy Taxonomy slug.
 *
 * @return array
 */
function er_get_resource_term_ids( $resource_id, $taxonomy ) {
	$terms = get_the_terms( $resource_id, $taxonomy );

	return ( empty( $terms ) || is_wp_error( $terms ) ) ? array() : wp_list_pluck( $terms, 'term_id' );
}

/**
 * Get slot matrix
 *
 * @param ER_Resource                   $resource
 * @param ER_DateTime                   $date
 * @param ER_Resource_Availability|bool $availability
 * @param bool                          $price
 * @param int                           $adults
 * @param int                           $children
 *
 * @return array|bool
 */
function er_resource_get_slot_matrix( $resource, $date, $availability = false, $price = false, $adults = 1, $children = 0 ) {
	if ( $resource->get_slots() ) {
		$matrix = array();
		$day    = $date->format( 'N' );

		foreach ( $resource->get_slots() as $key => $slot ) {
			if ( $date >= $slot['range-from'] && $date <= $slot['range-to'] ) {
				if ( in_array( $day, $slot['days'] ) ) {
					$arrival  = er_date_add_seconds( $date, $slot['from'] * 60 );
					$duration = $slot['to'] * 60 + ( $slot['duration'] * DAY_IN_SECONDS ) - $slot['from'] * 60;

					$matrix[ $arrival->format( 'H:i' ) ][] = er_resource_check_slot( $resource, $arrival, $duration, $availability, $key, $price, $adults, $children );

					if ( isset( $slot['repeat'] ) ) {
						for ( $i = 1; $i <= $slot['repeat']; $i ++ ) {
							$arrival->addSeconds( $duration );

							if ( isset( $slot['repeat-break'] ) ) {
								$arrival->addSeconds( intval( $slot['repeat-break'] ) * 60 );
							}

							$matrix[ $arrival->format( 'H:i' ) ][] = er_resource_check_slot( $resource, $arrival, $duration, $availability, $key, $price, $adults, $children );
						}
					}
				}
			}
		}

		return $matrix;
	}

	return false;
}

/**
 * Check a specific slot for availability and/or price
 *
 * @param ER_Resource                   $resource
 * @param ER_DateTime                   $arrival
 * @param int                           $duration
 * @param ER_Resource_Availability|bool $availability
 * @param int                           $key
 * @param bool                          $price
 * @param int                           $adults
 * @param int                           $children
 *
 * @return array
 */
function er_resource_check_slot( $resource, $arrival, $duration, $availability, $key, $price, $adults, $children ) {
	$departure = er_date_add_seconds( $arrival, $duration );
	$avail     = $resource->get_quantity();

	if ( $availability ) {
		$check = $availability->check_whole_period( $arrival, $departure );
		$avail = is_numeric( $check ) ? $avail - $check : - 1;
	}

	if ( $price ) {
		$reservation = new ER_Reservation( 0 );
		$reservation->set_arrival( $arrival );
		$reservation->set_departure( $arrival );
		$reservation->set_resource_id( $resource->get_id() );
		$reservation->set_slot( $key );
		$reservation->set_adults( $adults );
		$reservation->set_children( $children );

		$reservation->calculate_price();
		$reservation->calculate_taxes( false );
		$reservation->calculate_totals( false );

		$price = er_price( $reservation->get_total(), true );
	}

	return array(
		'availability' => $avail,
		'price'        => $price,
		'key'          => $key,
		'departure'    => $departure->format( er_date_format() . ' H:i' )
	);
}

/**
 * Register resources to be translatable
 */
function er_resource_register_as_translatable( $array ) {
	$array['easy-rooms'] = get_post_type_object( 'easy-rooms' );

	return $array;
}

add_filter( 'get_translatable_documents', 'er_resource_register_as_translatable', 10, 1 );

/**
 * Get the placeholder image URL either from media, or use the fallback image.
 *
 * @param string $size Thumbnail size to use.
 *
 * @return string
 */
function er_placeholder_img_src( $size = 'easyreservations_thumbnail' ) {
	$src               = ER()->plugin_url() . '/assets/images/placeholder.svg';
	$placeholder_image = get_option( 'reservations_placeholder_image', 0 );

	if ( ! empty( $placeholder_image ) ) {
		if ( is_numeric( $placeholder_image ) ) {
			$image = wp_get_attachment_image_src( $placeholder_image, $size );

			if ( ! empty( $image[0] ) ) {
				$src = $image[0];
			}
		} else {
			$src = $placeholder_image;
		}
	}

	return apply_filters( 'easyreservations_placeholder_img_src', $src );
}

/**
 * Get the placeholder image.
 *
 * Uses wp_get_attachment_image if using an attachment ID to handle responsiveness.
 *
 * @param string       $size Image size.
 * @param string|array $attr Optional. Attributes for the image markup. Default empty.
 *
 * @return string
 */
function er_placeholder_img( $size = 'easyreservations_thumbnail', $attr = '' ) {
	$dimensions        = er_get_image_size( $size );
	$placeholder_image = get_option( 'reservations_placeholder_image', 0 );

	$default_attr = array(
		'class' => 'easyreservations-placeholder wp-post-image',
		'alt'   => __( 'Placeholder', 'easyReservations' ),
	);

	$attr = wp_parse_args( $attr, $default_attr );

	if ( wp_attachment_is_image( $placeholder_image ) ) {
		$image_html = wp_get_attachment_image(
			$placeholder_image,
			$size,
			false,
			$attr
		);
	} else {
		$image      = er_placeholder_img_src( $size );
		$hwstring   = image_hwstring( $dimensions['width'], $dimensions['height'] );
		$attributes = array();

		foreach ( $attr as $name => $value ) {
			$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		$image_html = '<img src="' . esc_url( $image ) . '" ' . $hwstring . implode( ' ', $attributes ) . '/>';
	}

	return apply_filters( 'easyreservations_placeholder_img', $image_html, $size, $dimensions );
}

/**
 * For a given resource, and optionally price/qty, work out the price with tax included, based on store settings.
 *
 * @param ER_Resource $resource
 * @param float       $price
 *
 * @return float
 */
function er_get_price_including_tax( $resource, $price ) {

	$price = empty( $price ) ? $resource->get_price() : max( 0.0, (float) $price );

	$return_price = $price;

	if ( ! er_prices_include_tax() ) {
		$tax_rates = ER_Tax::get_rates( $resource->get_id() );
		$taxes     = ER_Tax::calc_tax( $price, $tax_rates, false );

		if ( 'yes' === get_option( 'reservations_tax_round_at_subtotal' ) ) {
			$taxes_total = array_sum( $taxes );
		} else {
			$taxes_total = array_sum( array_map( 'er_round_tax_total', $taxes ) );
		}

		$return_price = ER_Number_Util::round( $price + $taxes_total, er_get_price_decimals() );
	}

	return apply_filters( 'easyreservations_get_price_including_tax', $return_price, $resource );
}

/**
 * For a given resource, and optionally price/qty, work out the price with tax excluded, based on store settings.
 *
 * @param ER_Resource $resource
 * @param float       $price
 *
 * @return float
 */
function er_get_price_excluding_tax( $resource, $price ) {
	$price = empty( $price ) ? $resource->get_price() : max( 0.0, (float) $price );

	if ( er_prices_include_tax() ) {
		$tax_rates = ER_Tax::get_rates( $resource->get_id() );
		// Unrounded since we're dealing with tax inclusive prices. Matches logic in cart-totals class. @see adjust_non_base_location_price.
		$return_price = $price - array_sum( ER_Tax::calc_tax( $price, $tax_rates, true ) );
	} else {
		$return_price = $price;
	}

	return apply_filters( 'easyreservations_get_price_excluding_tax', $return_price, $resource );
}

/**
 * Returns the price including or excluding tax, based on the 'reservations_tax_display_shop' setting.
 *
 * @param ER_Resource $resource
 * @param float       $price
 *
 * @return float
 */
function er_get_price_to_display( $resource, $price ) {
	$price = empty( $price ) ? $resource->get_price() : max( 0.0, (float) $price );

	return 'incl' === get_option( 'reservations_tax_display_shop' ) ?
		er_get_price_including_tax(
			$resource,
			$price
		) :
		er_get_price_including_tax(
			$resource,
			$price
		);
}