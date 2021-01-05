<?php
/**
 * Resources shortcode
 *
 * @package  easyReservations/Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resources shortcode class.
 */
class ER_Shortcode_Resources {

	/**
	 * Shortcode type.
	 *
	 * @var   string
	 */
	protected $type = 'resources';

	/**
	 * Set custom visibility.
	 *
	 * @var   bool
	 */
	protected $custom_visibility = false;

	/**
	 * Attributes.
	 *
	 * @var   array
	 */
	protected $attributes = array();

	/**
	 * Query args.
	 *
	 * @var   array
	 */
	protected $query_args = array();

	/**
	 * Initialize shortcode.
	 *
	 * @param array  $attributes Shortcode attributes.
	 * @param string $type Shortcode type.
	 */
	public function __construct( $attributes = array(), $type = 'resources' ) {
		$this->type       = $type;
		$this->attributes = $this->parse_attributes( $attributes );
		$this->query_args = $this->parse_query_args();
	}

	/**
	 * Get shortcode attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get query args.
	 *
	 * @return array
	 */
	public function get_query_args() {
		return $this->query_args;
	}

	/**
	 * Get shortcode type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get shortcode content.
	 *
	 * @return string
	 */
	public function get_content() {
		return $this->resource_loop();
	}

	/**
	 * Parse attributes.
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return array
	 */
	protected function parse_attributes( $attributes ) {
		$attributes = shortcode_atts(
			array(
				'limit'      => '-1',      // Results limit.
				'columns'    => '',        // Number of columns.
				'rows'       => '',        // Number of rows. If defined, limit will be ignored.
				'orderby'    => 'title',   // menu_order, title, date, rand, relevance, or ID.
				'order'      => '',        // ASC or DESC.
				'ids'        => '',        // Comma separated IDs.
				'class'      => '',        // HTML class.
				'page'       => 1,         // Page for pagination.
				'paginate'   => false,     // Should results be paginated.
				'visibility' => 'visible',     // Resource visibility setting. Possible values are 'visible', 'catalog', 'search', 'hidden'.
				'cache'      => true,      // Should shortcode output be cached.
			),
			$attributes,
			$this->type
		);

		if ( ! absint( $attributes['columns'] ) ) {
			$attributes['columns'] = er_get_default_resources_per_row();
		}

		return $attributes;
	}

	/**
	 * Parse query args.
	 *
	 * @return array
	 */
	protected function parse_query_args() {
		$query_args = array(
			'post_type'           => 'easy-rooms',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false === er_string_to_bool( $this->attributes['paginate'] ),
			'orderby'             => empty( $_GET['orderby'] ) ? $this->attributes['orderby'] : er_clean( wp_unslash( $_GET['orderby'] ) ), // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
		);

		$orderby_value         = explode( '-', $query_args['orderby'] );
		$orderby               = esc_attr( $orderby_value[0] );
		$order                 = ! empty( $orderby_value[1] ) ? $orderby_value[1] : strtoupper( $this->attributes['order'] );
		$query_args['orderby'] = $orderby;
		$query_args['order']   = $order;

		if ( er_string_to_bool( $this->attributes['paginate'] ) ) {
			$this->attributes['page'] = absint( empty( $_GET['resource-page'] ) ? 1 : $_GET['resource-page'] ); // WPCS: input var ok, CSRF ok.
		}

		if ( ! empty( $this->attributes['rows'] ) ) {
			$this->attributes['limit'] = $this->attributes['columns'] * $this->attributes['rows'];
		}

		$query_args['posts_per_page'] = intval( $this->attributes['limit'] );
		if ( 1 < $this->attributes['page'] ) {
			$query_args['paged'] = absint( $this->attributes['page'] );
		}
		$query_args['meta_query'] = ER()->query->get_meta_query(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_args['tax_query']  = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		// IDs.
		$this->set_ids_query_args( $query_args );

		// Visibility.
		$this->set_visibility_query_args( $query_args );

		// Set specific types query args.
		if ( method_exists( $this, "set_{$this->type}_query_args" ) ) {
			$this->{"set_{$this->type}_query_args"}( $query_args );
		}

		$query_args = apply_filters( 'easyreservations_shortcode_resources_query', $query_args, $this->attributes, $this->type );

		// Always query only IDs.
		$query_args['fields'] = 'ids';

		return $query_args;
	}

	/**
	 * Set visibility as hidden.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_hidden_query_args( &$query_args ) {
		$this->custom_visibility   = true;
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resource_visibility',
			'terms'            => array( 'exclude-from-catalog', 'exclude-from-search' ),
			'field'            => 'name',
			'operator'         => 'AND',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility as catalog.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_catalog_query_args( &$query_args ) {
		$this->custom_visibility   = true;
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resource_visibility',
			'terms'            => 'exclude-from-search',
			'field'            => 'name',
			'operator'         => 'IN',
			'include_children' => false,
		);
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resource_visibility',
			'terms'            => 'exclude-from-catalog',
			'field'            => 'name',
			'operator'         => 'NOT IN',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility as search.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_search_query_args( &$query_args ) {
		$this->custom_visibility   = true;
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resource_visibility',
			'terms'            => 'exclude-from-catalog',
			'field'            => 'name',
			'operator'         => 'IN',
			'include_children' => false,
		);
		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resource_visibility',
			'terms'            => 'exclude-from-search',
			'field'            => 'name',
			'operator'         => 'NOT IN',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility as featured.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_featured_query_args( &$query_args ) {
		$query_args['tax_query'] = array_merge( $query_args['tax_query'], ER()->query->get_tax_query() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		$query_args['tax_query'][] = array(
			'taxonomy'         => 'resource_visibility',
			'terms'            => 'featured',
			'field'            => 'name',
			'operator'         => 'IN',
			'include_children' => false,
		);
	}

	/**
	 * Set visibility query args.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_visibility_query_args( &$query_args ) {
		if ( method_exists( $this, 'set_visibility_' . $this->attributes['visibility'] . '_query_args' ) ) {
			$this->{'set_visibility_' . $this->attributes['visibility'] . '_query_args'}( $query_args );
		} else {
			$query_args['tax_query'] = array_merge( $query_args['tax_query'], ER()->query->get_tax_query() ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}
	}

	/**
	 * Set resource as visible when querying for hidden resources.
	 *
	 * @param bool $visibility Resource visibility.
	 *
	 * @return bool
	 */
	public function set_resource_as_visible( $visibility ) {
		return $this->custom_visibility ? true : $visibility;
	}

	/**
	 * Set ids query args.
	 *
	 * @param array $query_args Query args.
	 */
	protected function set_ids_query_args( &$query_args ) {
		if ( ! empty( $this->attributes['ids'] ) ) {
			$ids = array_map( 'trim', explode( ',', $this->attributes['ids'] ) );

			if ( 1 === count( $ids ) ) {
				$query_args['p'] = $ids[0];
			} else {
				$query_args['post__in'] = $ids;
			}
		}
	}

	/**
	 * Get wrapper classes.
	 *
	 * @param int $columns Number of columns.
	 *
	 * @return array
	 */
	protected function get_wrapper_classes( $columns ) {
		$classes = array( 'easyreservations' );

		if ( 'resource' !== $this->type ) {
			$classes[] = 'columns-' . $columns;
		}

		$classes[] = $this->attributes['class'];

		return $classes;
	}

	/**
	 * Generate and return the transient name for this shortcode based on the query args.
	 *
	 * @return string
	 */
	protected function get_transient_name() {
		$transient_name = 'er_resource_loop_' . md5( wp_json_encode( $this->query_args ) . $this->type );

		if ( 'rand' === $this->query_args['orderby'] ) {
			// When using rand, we'll cache a number of random queries and pull those to avoid querying rand on each page load.
			$rand_index     = wp_rand( 0, max( 1, absint( apply_filters( 'easyreservations_resource_query_max_rand_cache_count', 5 ) ) ) );
			$transient_name .= $rand_index;
		}

		return $transient_name;
	}

	/**
	 * Run the query and return an array of data, including queried ids and pagination information.
	 *
	 * @return object Object with the following props; ids, per_page, found_posts, max_num_pages, current_page
	 */
	protected function get_query_results() {
		$transient_name    = $this->get_transient_name();
		$transient_version = er_get_transient_version( 'resource_query' );
		$cache             = er_string_to_bool( $this->attributes['cache'] ) === true;
		$transient_value   = $cache ? get_transient( $transient_name ) : false;

		if ( isset( $transient_value['value'], $transient_value['version'] ) && $transient_value['version'] === $transient_version ) {
			$results = $transient_value['value'];
		} else {
			$query = new WP_Query( $this->query_args );

			$paginated = ! $query->get( 'no_found_rows' );

			$results = (object) array(
				'ids'          => wp_parse_id_list( $query->posts ),
				'total'        => $paginated ? (int) $query->found_posts : count( $query->posts ),
				'total_pages'  => $paginated ? (int) $query->max_num_pages : 1,
				'per_page'     => (int) $query->get( 'posts_per_page' ),
				'current_page' => $paginated ? (int) max( 1, $query->get( 'paged', 1 ) ) : 1,
			);

			if ( $cache ) {
				$transient_value = array(
					'version' => $transient_version,
					'value'   => $results,
				);
				set_transient( $transient_name, $transient_value, DAY_IN_SECONDS * 30 );
			}
		}

		/**
		 * Filter shortcode products query results.
		 *
		 * @param stdClass               $results Query results.
		 * @param ER_Shortcode_Resources $this ER_Shortcode_Resources instance.
		 */
		return apply_filters( 'easyreservations_shortcode_products_query_results', $results, $this );
	}

	/**
	 * Loop over found resources.
	 *
	 * @return string
	 */
	protected function resource_loop() {
		$columns   = absint( $this->attributes['columns'] );
		$classes   = $this->get_wrapper_classes( $columns );
		$resources = $this->get_query_results();

		ob_start();

		if ( $resources && $resources->ids ) {
			// Prime caches to reduce future queries.
			if ( is_callable( '_prime_post_caches' ) ) {
				_prime_post_caches( $resources->ids );
			}

			// Setup the loop.
			er_setup_loop(
				array(
					'columns'      => $columns,
					'name'         => $this->type,
					'is_shortcode' => true,
					'is_search'    => false,
					'is_paginated' => er_string_to_bool( $this->attributes['paginate'] ),
					'total'        => $resources->total,
					'total_pages'  => $resources->total_pages,
					'per_page'     => $resources->per_page,
					'current_page' => $resources->current_page,
				)
			);

			$original_post = $GLOBALS['post'];

			do_action( "easyreservations_shortcode_before_{$this->type}_loop", $this->attributes );

			// Fire standard shop loop hooks when paginating results so we can show result counts and so on.
			if ( er_string_to_bool( $this->attributes['paginate'] ) ) {
				do_action( 'easyreservations_before_shop_loop' );
			}

			easyreservations_resource_loop_start();

			if ( er_get_loop_prop( 'total' ) ) {
				foreach ( $resources->ids as $resource_id ) {
					$GLOBALS['post'] = get_post( $resource_id ); // WPCS: override ok.
					setup_postdata( $GLOBALS['post'] );

					// Set custom resource visibility when quering hidden resources.
					add_action( 'easyreservations_resource_is_visible', array( $this, 'set_resource_as_visible' ) );

					// Render resource template.
					er_get_template_part( 'content', 'resource' );

					// Restore resource visibility.
					remove_action( 'easyreservations_resource_is_visible', array( $this, 'set_resource_as_visible' ) );
				}
			}

			$GLOBALS['post'] = $original_post; // WPCS: override ok.
			easyreservations_resource_loop_end();

			// Fire standard shop loop hooks when paginating results so we can show result counts and so on.
			if ( er_string_to_bool( $this->attributes['paginate'] ) ) {
				do_action( 'easyreservations_after_shop_loop' );
			}

			do_action( "easyreservations_shortcode_after_{$this->type}_loop", $this->attributes );

			wp_reset_postdata();
			er_reset_loop();
		} else {
			do_action( "easyreservations_shortcode_{$this->type}_loop_no_results", $this->attributes );
		}

		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
	}
}
