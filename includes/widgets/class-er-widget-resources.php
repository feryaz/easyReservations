<?php
/**
 * List products. One widget to rule them all.
 *
 * @package easyReservations/Widgets
 */

defined( 'ABSPATH' ) || exit;

/**
 * Widget products.
 */
class ER_Widget_Resources extends ER_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'easyreservations widget_products';
		$this->widget_description = __( "A list of your store's resources.", 'easyReservations' );
		$this->widget_id          = 'easyreservations_products';
		$this->widget_name        = __( 'easyReservations Resources', 'easyReservations' );
		$this->settings           = array(
			'title'       => array(
				'type'  => 'text',
				'std'   => __( 'Resources', 'easyReservations' ),
				'label' => __( 'Title', 'easyReservations' ),
			),
			'number'      => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 5,
				'label' => __( 'Number of resources to show', 'easyReservations' ),
			),
			'show'        => array(
				'type'    => 'select',
				'std'     => '',
				'label'   => __( 'Show', 'easyReservations' ),
				'options' => array(
					''         => __( 'All resources', 'easyReservations' ),
					'featured' => __( 'Featured resources', 'easyReservations' ),
					'onsale'   => __( 'On-sale resources', 'easyReservations' ),
				),
			),
			'orderby'     => array(
				'type'    => 'select',
				'std'     => 'date',
				'label'   => __( 'Order by', 'easyReservations' ),
				'options' => array(
					'date'  => __( 'Date', 'easyReservations' ),
					'price' => __( 'Price', 'easyReservations' ),
					'rand'  => __( 'Random', 'easyReservations' ),
					'sales' => __( 'Sales', 'easyReservations' ),
				),
			),
			'order'       => array(
				'type'    => 'select',
				'std'     => 'desc',
				'label'   => _x( 'Order', 'Sorting order', 'easyReservations' ),
				'options' => array(
					'asc'  => __( 'ASC', 'easyReservations' ),
					'desc' => __( 'DESC', 'easyReservations' ),
				),
			),
			'hide_free'   => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Hide free resources', 'easyReservations' ),
			),
			'show_hidden' => array(
				'type'  => 'checkbox',
				'std'   => 0,
				'label' => __( 'Show hidden resources', 'easyReservations' ),
			),
		);

		parent::__construct();
	}

	/**
	 * Query the resources and return them.
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 *
	 * @return WP_Query
	 */
	public function get_resources( $args, $instance ) {
		$number                      = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];
		$show                        = ! empty( $instance['show'] ) ? sanitize_title( $instance['show'] ) : $this->settings['show']['std'];
		$orderby                     = ! empty( $instance['orderby'] ) ? sanitize_title( $instance['orderby'] ) : $this->settings['orderby']['std'];
		$order                       = ! empty( $instance['order'] ) ? sanitize_title( $instance['order'] ) : $this->settings['order']['std'];
		$resource_visibility_term_ids = er_get_resource_visibility_term_ids();

		$query_args = array(
			'posts_per_page' => $number,
			'post_status'    => 'publish',
			'post_type'      => 'easy-rooms',
			'no_found_rows'  => 1,
			'order'          => $order,
			'meta_query'     => array(),
			'tax_query'      => array(
				'relation' => 'AND',
			),
		); // WPCS: slow query ok.

		if ( empty( $instance['show_hidden'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'resource_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => is_search() ? $resource_visibility_term_ids['exclude-from-search'] : $resource_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			);
			$query_args['post_parent'] = 0;
		}

		if ( ! empty( $instance['hide_free'] ) ) {
			$query_args['meta_query'][] = array(
				'key'     => '_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'DECIMAL',
			);
		}

		switch ( $show ) {
			case 'featured':
				$query_args['tax_query'][] = array(
					'taxonomy' => 'resource_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $resource_visibility_term_ids['featured'],
				);
				break;
			case 'onsale':
				$query_args['tax_query'][] = array(
					'taxonomy' => 'resource_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $resource_visibility_term_ids['onsale'],
				);
				break;
		}

		switch ( $orderby ) {
			case 'price':
				$query_args['meta_key'] = '_price'; // WPCS: slow query ok.
				$query_args['orderby']  = 'meta_value_num';
				break;
			case 'rand':
				$query_args['orderby'] = 'rand';
				break;
			case 'sales':
				$query_args['meta_key'] = 'total_sales'; // WPCS: slow query ok.
				$query_args['orderby']  = 'meta_value_num';
				break;
			default:
				$query_args['orderby'] = 'date';
		}

		return new WP_Query( apply_filters( 'easyreservations_resources_widget_query_args', $query_args ) );
	}

	/**
	 * Output widget.
	 *
	 * @param array $args     Arguments.
	 * @param array $instance Widget instance.
	 *
	 * @see WP_Widget
	 */
	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}

		ob_start();

		$resources = $this->get_resources( $args, $instance );
		if ( $resources && $resources->have_posts() ) {
			$this->widget_start( $args, $instance );

			echo wp_kses_post( apply_filters( 'easyreservations_before_widget_resource_list', '<ul class="resource_list_widget">' ) );

			$template_args = array(
				'widget_id'   => isset( $args['widget_id'] ) ? $args['widget_id'] : $this->widget_id,
				'show_rating' => true,
			);

			while ( $resources->have_posts() ) {
				$resources->the_post();
				er_get_template( 'content-widget-resource.php', $template_args );
			}

			echo wp_kses_post( apply_filters( 'easyreservations_after_widget_resource_list', '</ul>' ) );

			$this->widget_end( $args );
		}

		wp_reset_postdata();

		echo $this->cache_widget( $args, ob_get_clean() ); // WPCS: XSS ok.
	}
}
