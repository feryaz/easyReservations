<?php
/**
 * Loads resources once
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ER_Resources {

	/**
	 * The single instance of the class.
	 *
	 * @var ER_Resources|null
	 */
	protected static $instance = null;
	protected $resources = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get resources
	 *
	 * @param bool|int|WP_Post $id (optional)
	 *
	 * @return ER_Resource[]|ER_Resource|bool
	 */
	public function get( $id = false ) {
		if ( is_null( $this->resources ) ) {
			$this->resources = $this->prepare();
		}

		if ( is_a( $id, 'WP_Post' ) ) {
			$id = $id->ID;
		}

		if ( $id !== false ) {
			$id = absint( $id );

			if ( isset( $this->resources[ $id ] ) ) {
				return $this->resources[ $id ];
			}

			return false;
		}

		return $this->resources;
	}

	/**
	 * Get only resource accessible by the current user
	 *
	 * @return ER_Resource[]
	 */
	public function get_accessible() {
		$return = array();

		foreach ( $this->get() as $id => $resource ) {
			if ( $resource->is_visible() ) {
				$return[ $id ] = $resource;
			}
		}

		return $return;
	}

	/**
	 * Get only resource visible for the current user
	 *
	 * @return ER_Resource[]
	 */
	public function get_visible() {
		$return = array();

		foreach ( $this->get() as $id => $resource ) {
			if ( $resource->is_visible() ) {
				$return[ $id ] = $resource;
			}
		}

		return $return;
	}

	protected function prepare() {
		return $this->cast( $this->query() );
	}

	protected function cast( $resources_post_data ) {
		$return = array();
		foreach ( $resources_post_data as $post_data ) {
			$return[ $post_data->ID ] = new ER_Resource( $post_data );
		}

		return $return;
	}

	protected function query() {
		global $wpdb;

		//TODO get posts instead
		$resources = $wpdb->get_results(
			"SELECT ID, post_title, menu_order, post_name, post_content, post_excerpt, post_status, post_password
			FROM {$wpdb->prefix}posts 
			WHERE post_type = 'easy-rooms' AND post_status != 'auto-draft' 
			ORDER BY menu_order, ID ASC"
		);

		return $resources;
	}
}
