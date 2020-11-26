<?php
/**
 * easyReservations Import Tracking
 *
 * @package easyReservations\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of easyReservations Resources.
 */
class ER_Resources_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'load-edit.php', array( $this, 'track_resource_view' ), 10 );
		add_action( 'edit_post', array( $this, 'track_resource_updated' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'track_resource_published' ), 10, 3 );
		add_action( 'created_resource_cat', array( $this, 'track_resource_category_created' ) );
	}

	/**
	 * Send a Tracks event when the Products page is viewed.
	 */
	public function track_products_view() {
		// We only record Tracks event when no `_wp_http_referer` query arg is set, since
		// when searching, the request gets sent from the browser twice,
		// once with the `_wp_http_referer` and once without it.
		//
		// Otherwise, we would double-record the view and search events.

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
		if (
			isset( $_GET['post_type'] )
			&& 'easy-rooms' === wp_unslash( $_GET['post_type'] )
			&& ! isset( $_GET['_wp_http_referer'] )
		) {
			// phpcs:enable

			ER_Tracks::record_event( 'resources_view' );

			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification
			if (
				isset( $_GET['s'] )
				&& 0 < strlen( sanitize_text_field( wp_unslash( $_GET['s'] ) ) )
			) {
				// phpcs:enable

				ER_Tracks::record_event( 'resources_search' );
			}
		}
	}

	/**
	 * Send a Tracks event when a resource is updated.
	 *
	 * @param int    $resource_id Resource id.
	 * @param object $post WordPress post.
	 */
	public function track_resource_updated( $resource_id, $post ) {
		if ( 'resource' !== $post->post_type ) {
			return;
		}

		$properties = array(
			'resource_id' => $resource_id,
		);

		ER_Tracks::record_event( 'resource_edit', $properties );
	}

	/**
	 * Send a Tracks event when a resource is published.
	 *
	 * @param string $new_status New post_status.
	 * @param string $old_status Previous post_status.
	 * @param object $post WordPress post.
	 */
	public function track_resource_published( $new_status, $old_status, $post ) {
		if (
			'resource' !== $post->post_type ||
			'publish' !== $new_status ||
			'publish' === $old_status
		) {
			return;
		}

		$properties = array(
			'resource_id' => $post->ID,
		);

		ER_Tracks::record_event( 'resource_add_publish', $properties );
	}

	/**
	 * Send a Tracks event when a resource category is created.
	 *
	 * @param int $category_id Category ID.
	 */
	public function track_resource_category_created( $category_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Only track category creation from the edit resource screen or the
		// category management screen (which both occur via AJAX).
		if (
			defined( 'DOING_AJAX' ) ||
			empty( $_POST['action'] ) ||
			(
				// Resource Categories screen.
				'add-tag' !== $_POST['action'] &&
				// Edit Resource screen.
				'add-resource_cat' !== $_POST['action']
			)
		) {
			return;
		}

		$category   = get_term( $category_id, 'resource_cat' );
		$properties = array(
			'category_id' => $category_id,
			'parent_id'   => $category->parent,
			'page'        => ( 'add-tag' === $_POST['action'] ) ? 'categories' : 'resource',
		);
		// phpcs:enable

		ER_Tracks::record_event( 'resource_category_add', $properties );
	}
}
