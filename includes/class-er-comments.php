<?php
/**
 * Comments
 *
 * Handle comments (reviews and order notes).
 *
 * @package easyReservations/Classes/Resources
 */

defined( 'ABSPATH' ) || exit;

/**
 * Comments class.
 */
class ER_Comments {

	/**
	 * Hook in methods.
	 */
	public static function init() {

		// Secure order notes.
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_order_comments' ), 10, 1 );
		add_filter( 'comment_feed_where', array( __CLASS__, 'exclude_order_comments_from_feed_where' ) );

		// Secure webhook comments.
		add_filter( 'comments_clauses', array( __CLASS__, 'exclude_webhook_comments' ), 10, 1 );
		add_filter( 'comment_feed_where', array( __CLASS__, 'exclude_webhook_comments_from_feed_where' ) );

		// Count comments.
		add_filter( 'wp_count_comments', array( __CLASS__, 'wp_count_comments' ), 10, 2 );

		// Delete comments count cache whenever there is a new comment or a comment status changes.
		add_action( 'wp_insert_comment', array( __CLASS__, 'delete_comments_count_cache' ) );
		add_action( 'wp_set_comment_status', array( __CLASS__, 'delete_comments_count_cache' ) );
	}

	/**
	 * Exclude order comments from queries and RSS.
	 *
	 * This code should exclude easy_order comments from queries. Some queries (like the recent comments widget on the dashboard) are hardcoded.
	 * and are not filtered, however, the code current_user_can( 'read_post', $comment->comment_post_ID ) should keep them safe since only admin and.
	 * shop managers can view orders anyway.
	 *
	 * The frontend view order pages get around this filter by using remove_filter('comments_clauses', array( 'ER_Comments' ,'exclude_order_comments'), 10, 1 );
	 *
	 * @param array $clauses A compacted array of comment query clauses.
	 *
	 * @return array
	 */
	public static function exclude_order_comments( $clauses ) {
		$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . " comment_type != 'er_order_note' ";

		return $clauses;
	}

	/**
	 * Exclude order comments from queries and RSS.
	 *
	 * @param string $where The WHERE clause of the query.
	 *
	 * @return string
	 */
	public static function exclude_order_comments_from_feed_where( $where ) {
		return $where . ( $where ? ' AND ' : '' ) . " comment_type != 'er_order_note' ";
	}

	/**
	 * Exclude webhook comments from queries and RSS.
	 *
	 * @param array $clauses A compacted array of comment query clauses.
	 *
	 * @return array
	 */
	public static function exclude_webhook_comments( $clauses ) {
		$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . " comment_type != 'webhook_delivery' ";

		return $clauses;
	}

	/**
	 * Exclude webhook comments from queries and RSS.
	 *
	 * @param string $where The WHERE clause of the query.
	 *
	 * @return string
	 */
	public static function exclude_webhook_comments_from_feed_where( $where ) {
		return $where . ( $where ? ' AND ' : '' ) . " comment_type != 'webhook_delivery' ";
	}

	/**
	 * Delete comments count cache whenever there is
	 * new comment or the status of a comment changes. Cache
	 * will be regenerated next time ER_Comments::wp_count_comments()
	 * is called.
	 */
	public static function delete_comments_count_cache() {
		delete_transient( 'er_count_comments' );
	}

	/**
	 * Remove order notes and webhook delivery logs from wp_count_comments().
	 *
	 * @param object $stats Comment stats.
	 * @param int    $post_id Post ID.
	 *
	 * @return object
	 */
	public static function wp_count_comments( $stats, $post_id ) {
		global $wpdb;

		if ( 0 === $post_id ) {
			$stats = get_transient( 'er_count_comments' );

			if ( ! $stats ) {
				$stats = array(
					'total_comments' => 0,
					'all'            => 0,
				);

				$count = $wpdb->get_results(
					"
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type NOT IN ('action_log', 'er_order_note', 'webhook_delivery')
					GROUP BY comment_approved
					",
					ARRAY_A
				);

				$approved = array(
					'0'            => 'moderated',
					'1'            => 'approved',
					'spam'         => 'spam',
					'trash'        => 'trash',
					'post-trashed' => 'post-trashed',
				);

				foreach ( (array) $count as $row ) {
					// Don't count post-trashed toward totals.
					if ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash', 'spam' ), true ) ) {
						$stats['all']            += $row['num_comments'];
						$stats['total_comments'] += $row['num_comments'];
					} elseif ( ! in_array( $row['comment_approved'], array( 'post-trashed', 'trash' ), true ) ) {
						$stats['total_comments'] += $row['num_comments'];
					}
					if ( isset( $approved[ $row['comment_approved'] ] ) ) {
						$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
					}
				}

				foreach ( $approved as $key ) {
					if ( empty( $stats[ $key ] ) ) {
						$stats[ $key ] = 0;
					}
				}

				$stats = (object) $stats;
				set_transient( 'er_count_comments', $stats );
			}
		}

		return $stats;
	}
}

ER_Comments::init();
