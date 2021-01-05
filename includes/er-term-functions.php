<?php
defined( 'ABSPATH' ) || exit;

/**
 * Get full list of resource visibility term ids.
 *
 * @return int[]
 */
function er_get_resource_visibility_term_ids() {
	if ( ! taxonomy_exists( 'resource_visibility' ) ) {
		_doing_it_wrong( __FUNCTION__, 'er_get_resource_visibility_term_ids should not be called before taxonomies are registered (er_resource_register_post_type action).', '6.1' );

		return array();
	}

	return array_map(
		'absint',
		wp_parse_args(
			wp_list_pluck(
				get_terms(
					array(
						'taxonomy'   => 'resource_visibility',
						'hide_empty' => false,
					)
				),
				'term_taxonomy_id',
				'name'
			),
			array(
				'exclude-from-catalog' => 0,
				'exclude-from-search'  => 0,
				'featured'             => 0,
				'onsale'               => 0,
			)
		)
	);
}

/**
 * Function for recounting resource terms, ignoring hidden resources.
 *
 * @param array  $terms List of terms.
 * @param object $taxonomy Taxonomy.
 * @param bool   $callback Callback.
 * @param bool   $terms_are_term_taxonomy_ids If terms are from term_taxonomy_id column.
 */
function _er_term_recount( $terms, $taxonomy, $callback = true, $terms_are_term_taxonomy_ids = true ) {
	global $wpdb;

	// Standard callback.
	if ( $callback ) {
		_update_post_term_count( $terms, $taxonomy );
	}

	$exclude_term_ids             = array();
	$resource_visibility_term_ids = er_get_resource_visibility_term_ids();

	if ( $resource_visibility_term_ids['exclude-from-catalog'] ) {
		$exclude_term_ids[] = $resource_visibility_term_ids['exclude-from-catalog'];
	}

	$query = array(
		'fields' => "
			SELECT COUNT( DISTINCT ID ) FROM {$wpdb->posts} p
		",
		'join'   => '',
		'where'  => "
			WHERE 1=1
			AND p.post_status = 'publish'
			AND p.post_type = 'easy-rooms'

		",
	);

	if ( count( $exclude_term_ids ) ) {
		$query['join']  .= " LEFT JOIN ( SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( " . implode( ',', array_map( 'absint', $exclude_term_ids ) ) . ' ) ) AS exclude_join ON exclude_join.object_id = p.ID';
		$query['where'] .= ' AND exclude_join.object_id IS NULL';
	}

	// Pre-process term taxonomy ids.
	if ( ! $terms_are_term_taxonomy_ids ) {
		// We passed in an array of TERMS in format id=>parent.
		$terms = array_filter( (array) array_keys( $terms ) );
	} else {
		// If we have term taxonomy IDs we need to get the term ID.
		$term_taxonomy_ids = $terms;
		$terms             = array();
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			$term    = get_term_by( 'term_taxonomy_id', $term_taxonomy_id, $taxonomy->name );
			$terms[] = $term->term_id;
		}
	}

	// Exit if we have no terms to count.
	if ( empty( $terms ) ) {
		return;
	}

	// Ancestors need counting.
	if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {
		foreach ( $terms as $term_id ) {
			$terms = array_merge( $terms, get_ancestors( $term_id, $taxonomy->name ) );
		}
	}

	// Unique terms only.
	$terms = array_unique( $terms );

	// Count the terms.
	foreach ( $terms as $term_id ) {
		$terms_to_count = array( absint( $term_id ) );

		if ( is_taxonomy_hierarchical( $taxonomy->name ) ) {
			// We need to get the $term's hierarchy so we can count its children too.
			$children = get_term_children( $term_id, $taxonomy->name );

			if ( $children && ! is_wp_error( $children ) ) {
				$terms_to_count = array_unique( array_map( 'absint', array_merge( $terms_to_count, $children ) ) );
			}
		}

		// Generate term query.
		$term_query         = $query;
		$term_query['join'] .= " INNER JOIN ( SELECT object_id FROM {$wpdb->term_relationships} INNER JOIN {$wpdb->term_taxonomy} using( term_taxonomy_id ) WHERE term_id IN ( " . implode( ',', array_map( 'absint', $terms_to_count ) ) . ' ) ) AS include_join ON include_join.object_id = p.ID';

		// Get the count.
		$count = $wpdb->get_var( implode( ' ', $term_query ) ); // WPCS: unprepared SQL ok.

		// Update the count.
		update_term_meta( $term_id, 'resource_count_' . $taxonomy->name, absint( $count ) );
	}

	delete_transient( 'er_term_counts' );
}