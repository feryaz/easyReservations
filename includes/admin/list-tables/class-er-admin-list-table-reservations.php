<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ER_Admin_List_Table', false ) ) {
	include_once __DIR__ . '/abstract-class-er-admin-list-table.php';
}

/**
 * ER_Admin_List_Table_Orders Class.
 */
class ER_Admin_List_Table_Reservations extends ER_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'easy_reservation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'posts_request', array( $this, 'posts_request' ), 10, 2 );
		add_filter( 'posts_results', array( $this, 'posts_results' ), 10, 2 );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_filter( 'views_edit-easy_reservation', array( $this, 'views' ), 10, 2 );
		add_filter( 'screen_settings', array( $this, 'screen_settings' ), 10, 2 );

		add_action( 'all_admin_notices', array( $this, 'output_timeline' ) );
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
		add_action( 'admin_footer', array( $this, 'reservation_preview_template' ) );
		add_action( 'parse_query', array( $this, 'search_custom_fields' ) );

		remove_action( 'admin_notices', array( 'WP_Privacy_Policy_Content', 'notice' ) );
	}

	/**
	 * Output timeline html
	 */
	public function output_timeline() {
		include RESERVATIONS_ABSPATH . "includes/admin/views/html-timeline.php";
	}

	/**
	 * Add to screen settings
	 *
	 * @param $rv
	 * @param $screen
	 *
	 * @return string
	 */
	public function screen_settings( $rv, $screen ) {
		$user_id = get_current_user_id();
		ob_start();
		?>
        <fieldset class="metabox-prefs">
            <legend><?php esc_html_e( 'Timeline', 'easyReservations' ); ?></legend>
            <label for="edit_easy_reservation_timeline_hourly">
                <input type="checkbox" name="timeline_hourly" id="edit_easy_reservation_timeline_hourly" value="1" <?php checked( get_user_meta( $user_id, 'timeline_hourly', true ) === 'on' ); ?>><?php esc_html_e( 'Hourly mode as default', 'easyReservations' ); ?>
            </label>
            <label for="edit_easy_reservation_timeline_snapping">
                <input type="checkbox" name="timeline_snapping" class="hide-column-tog" id="edit_easy_reservation_timeline_snapping" value="1" <?php checked( get_user_meta( $user_id, 'timeline_snapping', true ) === 'on' ); ?>><?php esc_html_e( 'Snapping enabled as default', 'easyReservations' ); ?>
            </label>
        </fieldset>
		<?php

		$rv .= ob_get_clean();

		return $rv;
	}

	/**
	 * Manipulate query as reservations are not posts
	 *
	 * @param $where
	 * @param $wp_query
	 *
	 * @return string
	 */
	public function posts_request( $where, $wp_query ) {
		global $wpdb;

		$q = $wp_query->query_vars;

		$where  = '';
		$select = 'id as ID, order_id as post_parent, status as post_status';

		$page = absint( $q['paged'] );
		if ( ! $page ) {
			$page = 1;
		}

		if ( isset( $_REQUEST['order_id'] ) && $_REQUEST['order_id'] !== 'all' ) {
			$where .= $wpdb->prepare( ' AND order_id = %s', absint( $_REQUEST['order_id'] ) );
		}

		if ( isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] !== 'all' ) {
			$post_status = sanitize_key( $_REQUEST['post_status'] );

			if ( $post_status === 'upcoming' ) {
				$post_status = 'approved';
				$where       .= ' AND NOW() < arrival';

				if ( ! isset( $_REQUEST['orderby'] ) ) {
					$_REQUEST['orderby'] = 'date';
					$_REQUEST['order']   = 'ASC';
				}
			}

			$where .= $wpdb->prepare( ' AND status = %s', $post_status );
		} else {
			$where .= " AND status not in ( 'trash', 'temporary' )";
		}

		if ( isset( $_REQUEST['resource'] ) && ! empty( $_REQUEST['resource'] ) ) {
			$where .= $wpdb->prepare( ' AND resource = %d', absint( $_REQUEST['resource'] ) );
		}

		if ( isset( $q['easy_reservation_search'] ) ) {
			$post_in = implode( ',', array_map( 'absint', $q['post__in'] ) );
			$where   .= " AND ID in ($post_in)";
		}

		// If 'offset' is provided, it takes precedence over 'paged'.
		if ( isset( $q['offset'] ) && is_numeric( $q['offset'] ) ) {
			$q['offset'] = absint( $q['offset'] );
			$pgstrt      = $q['offset'] . ', ';
		} else {
			$pgstrt = absint( ( $page - 1 ) * $q['posts_per_page'] ) . ', ';
		}

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'created';

		if ( ! empty( $orderby ) ) {
			switch ( $orderby ) {
				case 'date':
					$orderby = 'arrival';
					break;

				case 'created':
					$orderby = 'id';
					break;

				case 'order':
					$orderby = 'order_id';
					break;
			}

			$orderby = 'ORDER BY ' . $orderby;

			$order = isset( $_REQUEST['order'] ) ? sanitize_key( $_REQUEST['order'] ) : 'DESC';
			if ( ! empty( $order ) ) {
				$orderby .= ' ' . $order;
			}
		}

		$limits = 'LIMIT ' . $pgstrt . $q['posts_per_page'];

		$found_rows = '';
		if ( ! $q['no_found_rows'] && ! empty( $limits ) ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$old_request = "SELECT $found_rows $select FROM {$wpdb->prefix}reservations WHERE 1=1 $where $orderby $limits";

		return $old_request;
	}

	/**
	 * Manipulate results
	 *
	 * @param $posts
	 * @param $wp_query
	 *
	 * @return mixed
	 */
	public function posts_results( $posts, $wp_query ) {
		foreach ( $posts as $post ) {
			$post->post_type = 'easy_reservation';
		}

		return $posts;
	}

	/**
	 * Replaces post status selection list
	 *
	 * @return array
	 */
	public function views() {
		$cache_key = er_get_cache_prefix( 'reservations' );
		$counts    = wp_cache_get( $cache_key, 'counts' );

		if ( false === $counts ) {
			global $wpdb;

			$query   = "SELECT status, COUNT( * ) AS num_posts, COUNT( CASE WHEN ( NOW() < arrival ) then 1 ELSE NULL END ) as upcoming FROM {$wpdb->prefix}reservations GROUP BY status";
			$results = (array) $wpdb->get_results( $query, ARRAY_A );
			$counts  = array();

			foreach ( $results as $row ) {
				$counts[ $row['status'] ] = $row['num_posts'];
				if ( $row['status'] === 'approved' && $row['upcoming'] > 0 ) {
					$counts['upcoming'] = $row['upcoming'];
				}
			}

			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		$total_posts = array_sum( (array) $counts ) - ( isset( $counts['temporary'] ) ? $counts['temporary'] : 0 ) - ( isset( $counts['trash'] ) ? $counts['trash'] : 0 );

		$all_inner_html = sprintf(
		/* translators: %s: Number of posts. */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_posts,
				'easyReservations'
			),
			number_format_i18n( $total_posts )
		);

		$class = '';

		if ( ! isset( $_REQUEST['post_status'] ) ) {
			$class = 'current';
		}

		$views = array( 'all' => $this->get_edit_link( array( 'post_type' => 'easy_reservation' ), $all_inner_html, $class ) );

		$statuses = ER_Reservation_Status::get_statuses();

		if ( isset( $counts['upcoming'] ) ) {
			$statuses['upcoming'] = __( 'Upcoming', 'easyReservations' );
		}

		ksort( $statuses );

		foreach ( $statuses as $status => $label ) {
			if ( ! isset( $counts[ $status ] ) ) {
				continue;
			}

			$class = '';

			if ( isset( $_REQUEST['post_status'] ) && $status === $_REQUEST['post_status'] ) {
				$class = 'current';
			}

			$status_args = array(
				'post_status' => $status,
				'post_type'   => 'easy_reservation',
			);

			$views[ $status ] = $this->get_edit_link( $status_args, sprintf( '%s <span class="count">(%s)</span>', $label, $counts[ $status ] ), $class );
		}

		return $views;
	}

	/**
	 * Helper to create links to edit.php with params.
	 *
	 * @param string[] $args Associative array of URL parameters for the link.
	 * @param string   $label Link text.
	 * @param string   $class Optional. Class attribute. Default empty string.
	 *
	 * @return string The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, 'edit.php' );

		$class_html   = '';
		$aria_current = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * Render blank state.
	 */
	public function maybe_render_blank_state( $which ) {
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'reservation_number';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 *
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		return array();
	}

	/**
	 * Define hidden columns.
	 *
	 * @return array
	 */
	protected function define_hidden_columns() {
		return array(
			'er_actions',
			'reservation_created',
		);
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = array(
			'reservation_number'   => 'ID',
			'reservation_duration' => 'duration',
			'reservation_date'     => 'date',
			'reservation_status'   => 'status',
			'reservation_order'    => 'order',
			'reservation_created'  => 'created',
		);
		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 *
	 * @return array
	 */
	public function define_columns( $columns ) {
		$show_columns                         = array();
		$show_columns['cb']                   = $columns['cb'];
		$show_columns['reservation_number']   = __( 'Reservation', 'easyReservations' );
		$show_columns['reservation_date']     = __( 'Date', 'easyReservations' );
		$show_columns['reservation_resource'] = __( 'Resource', 'easyReservations' );
		$show_columns['reservation_status']   = __( 'Status', 'easyReservations' );
		$show_columns['reservation_order']    = __( 'Order', 'easyReservations' );
		$show_columns['reservation_duration'] = __( 'Duration', 'easyReservations' );
		$show_columns['reservation_created']  = __( 'Created', 'easyReservations' );
		$show_columns['er_actions']           = __( 'Actions', 'easyReservations' );

		wp_enqueue_script( 'er-reservations' );
		wp_enqueue_style( 'jquery-ui-style' );
		wp_enqueue_style( 'er-datepicker' );

		return $show_columns;
	}

	/**
	 * Define bulk actions.
	 *
	 * @param array $actions Existing actions.
	 *
	 * @return array
	 */
	public function define_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		if ( isset( $actions['trash'] ) ) {
			$actions['move_to_trash'] = __( 'Move to Trash', 'easyReservations' );

			unset( $actions['trash'] );
		}

		if ( isset( $actions['untrash'] ) ) {
			$actions['mark_pending'] = __( 'Restore', 'easyReservations' );

			unset( $actions['untrash'] );
		}

		if ( isset( $actions['delete'] ) ) {
			$actions['delete_permanently'] = __( 'Delete Permanently', 'easyReservations' );

			unset( $actions['delete'] );
		} else {
			$actions['mark_approved']  = __( 'Change status to approved', 'easyReservations' );
			$actions['mark_checked']   = __( 'Change status to checked in', 'easyReservations' );
			$actions['mark_completed'] = __( 'Change status to completed', 'easyReservations' );
		}

		return $actions;
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_order global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$this->object = er_get_reservation( $post_id );
		}
	}

	/**
	 * Render columm: reservation_resource.
	 */
	public function render_reservation_resource_column() {
		$resource = $this->object->get_resource();

		if ( ! $resource ) {
			echo '&ndash;';

			return;
		}

		echo '<a href="' . esc_url( admin_url( 'admin.php?page=resource&resource=' . absint( $resource->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>' . esc_html( $resource->get_title() ) . '</strong></a>';

		$space = $this->object->get_space();

		if ( $space ) {
			echo ' ' . esc_html( $resource->get_space_name( $space ) );
		}
	}

	/**
	 * Render columm: reservation_number.
	 */
	public function render_reservation_number_column() {
		$title = $this->object->get_title();

		if ( ! $title ) {
			$title = '&ndash;';
		}

		if ( $this->object->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $this->object->get_id() ) . ' ' . esc_html( $title ) . '</strong>';
		} else {
			echo '<a href="#" class="reservation-preview" data-reservation-id="' . esc_attr( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'easyReservations' ) ) . '">' . esc_html( __( 'Preview', 'easyReservations' ) ) . '</a>';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=reservation&reservation=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $this->object->get_id() ) . ' ' . esc_html( $title ) . '</strong></a>';
		}
	}

	/**
	 * Render columm: reservation_status.
	 */
	protected function render_reservation_status_column() {
		printf( '<mark class="reservation-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), esc_html( ER_Reservation_Status::get_title( $this->object->get_status() ) ) );
	}

	/**
	 * Render columm: reservation_date.
	 */
	protected function render_reservation_created_column() {
		$order_timestamp = $this->object->get_date_created() ? $this->object->get_date_created()->getTimestamp() : '';

		if ( ! $order_timestamp ) {
			echo '&ndash;';

			return;
		}

		// Check if the order was created within the last 24 hours, and not in the future.
		if ( $order_timestamp > strtotime( '-1 day', time() ) && $order_timestamp <= time() ) {
			$show_date = sprintf(
			/* translators: %s: human-readable time difference */
				_x( '%s ago', '%s = human-readable time difference', 'easyReservations' ),
				human_time_diff( $this->object->get_date_created()->getTimestamp(), time() )
			);
		} else {
			$show_date = $this->object->get_date_created()->date_i18n( apply_filters( 'easyreservations_admin_order_date_format', __( 'M j, Y', 'easyReservations' ) ) );
		}

		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $this->object->get_date_created()->date( 'c' ) ),
			esc_html( $this->object->get_date_created()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $show_date )
		);
	}

	/**
	 * Render columm: reservation_date.
	 */
	protected function render_reservation_date_column() {
		$arrival   = $this->object->get_arrival();
		$departure = $this->object->get_departure();

		if ( ! $arrival || ! $departure ) {
			echo '&ndash;';

			return;
		}

		$now         = er_get_datetime();
		$date_format = apply_filters( 'easyreservations_admin_order_date_format', __( 'M j, Y', 'easyReservations' ) );

		if ( er_use_time() ) {
			$date_format .= ' ' . er_time_format();
		}

		if ( $arrival > $now ) {
			$class = 'future';
		} elseif ( $departure < $now ) {
			$class = 'past';
		} else {
			$class = 'present';
		}

		echo '<div>';

		foreach ( array( $arrival, $departure ) as $date ) {
			$time_diff = human_time_diff( $date->getTimestamp(), $now->getTimestamp() );

			if ( $date < $now ) {
				$time_diff = sprintf(
				/* translators: %s: human-readable time difference */
					_x( '%s ago', '%s = human-readable time difference', 'easyReservations' ),
					$time_diff
				);
			} else {
				$time_diff = sprintf(
				/* translators: %s: human-readable time difference */
					_x( 'in %s', '%s = human-readable time difference', 'easyReservations' ),
					$time_diff
				);
			}

			printf(
				'<time datetime="%1$s" title="%2$s" class="reservation-date %3$s">%4$s</time>',
				esc_attr( $date->date( 'c' ) ),
				esc_html( $date->date_i18n( er_datetime_format( true ) ) ) . ' ' . $time_diff,
				esc_html( $class ),
				esc_html( $date->date_i18n( $date_format ) )
			);

			echo '<br>';
		}

		echo '</div>';
	}

	/**
	 * Render columm: reservation_order.
	 */
	protected function render_reservation_order_column() {
		$order_id = $this->object->get_order_id();

		if ( $order_id ) {
			$order = er_get_order( $order_id );

			if ( $order ) {
				echo $order->get_edit_link();
			} else {
				esc_html_e( 'Order not found', 'easyReservations' );
			}
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Render columm: reservation_duration.
	 */
	protected function render_reservation_duration_column() {
		$duration = $this->object->get_billing_units();
		$interval = $this->object->get_resource() ? $this->object->get_resource()->get_billing_interval() : DAY_IN_SECONDS;

		printf( '%d %s', $duration, er_date_get_interval_label( $interval, $duration ) );
	}

	/**
	 * Template for reservation preview.
	 */
	public function reservation_preview_template() {
		er_reservation_preview_template();
	}

	/**
	 * Get actions to display in the preview as HTML.
	 *
	 * @param ER_Reservation $reservation Reservation object.
	 *
	 * @return string
	 */
	public static function get_reservation_preview_actions_html( $reservation ) {
		$actions        = array();
		$status_actions = array();

		if ( $reservation->has_status( array( 'temporary' ) ) ) {
			$status_actions['pending'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_reservation_status&status=pending&reservation_id=' . $reservation->get_id() ), 'easyreservations-mark-reservation-status' ),
				'name'   => __( 'Pending', 'easyReservations' ),
				'title'  => __( 'Change reservation status to pending', 'easyReservations' ),
				'action' => 'pending',
			);
		}

		if ( $reservation->has_status( array( 'pending', 'temporary' ) ) ) {
			$status_actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_reservation_status&status=processing&reservation_id=' . $reservation->get_id() ), 'easyreservations-mark-reservation-status' ),
				'name'   => __( 'Approved', 'easyReservations' ),
				'title'  => __( 'Change reservation status to approved', 'easyReservations' ),
				'action' => 'approved',
			);
		}

		if ( $reservation->has_status( array( 'pending', 'temporary', 'approved' ) ) ) {
			$status_actions['checked'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_reservation_status&status=checked&reservation_id=' . $reservation->get_id() ), 'easyreservations-mark-reservation-status' ),
				'name'   => __( 'Check-In', 'easyReservations' ),
				'title'  => __( 'Change reservation status to checked in', 'easyReservations' ),
				'action' => 'checked',
			);
		}

		if ( $reservation->has_status( array( 'pending', 'temporary', 'approved', 'checked' ) ) ) {
			$status_actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_reservation_status&status=completed&reservation_id=' . $reservation->get_id() ), 'easyreservations-mark-reservation-status' ),
				'name'   => __( 'Completed', 'easyReservations' ),
				'title'  => __( 'Change reservation status to completed', 'easyReservations' ),
				'action' => 'complete',
			);
		}

		if ( $status_actions ) {
			$actions['status'] = array(
				'group'   => __( 'Change status: ', 'easyReservations' ),
				'actions' => $status_actions,
			);
		}

		return er_render_action_buttons( apply_filters( 'easyreservations_admin_reservation_preview_actions', $actions, $reservation ) );
	}

	/**
	 * Get order details to send to the ajax endpoint for previews.
	 *
	 * @param ER_Reservation $reservation Reservation object.
	 *
	 * @return array
	 */
	public static function reservation_preview_get_reservation_details( $reservation ) {
		if ( ! $reservation ) {
			return array();
		}

		$custom = $reservation->get_formatted_custom();

		return apply_filters(
			'easyreservations_admin_reservation_preview_get_reservation_details',
			array(
				'data'             => $reservation->get_data(),
				'reservation_id'   => $reservation->get_id(),
				'resource_name'    => $reservation->get_resource() ? $reservation->get_resource()->get_title() : __( 'No resource selected', 'easyReservations' ),
				'item_html'        => '',//self::get_order_preview_item_html( $order ),
				'actions_html'     => self::get_reservation_preview_actions_html( $reservation ),
				'formatted_custom' => $custom ? er_display_meta( $custom, array(
					'before'    => '',
					'separator' => ', ',
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				) ) : __( 'N/A', 'easyReservations' ),
				'status'           => $reservation->get_status(),
				'status_name'      => ER_Reservation_Status::get_title( $reservation->get_status() ),
			),
			$reservation
		);
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param string $redirect_to URL to redirect to.
	 * @param string $action Action name.
	 * @param array  $ids List of ids.
	 *
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		$ids     = apply_filters( 'easyreservations_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'reservations' );
		$changed = 0;

		if ( 'move_to_trash' === $action ) {
			$report_action = 'moved_to_trash';

			foreach ( $ids as $id ) {
				$reservation = er_get_reservation( $id );

				if ( $reservation && $reservation->is_order_editable() ) {
					$reservation->update_status( 'trash', __( 'Reservation moved to trash by bulk edit:', 'easyReservations' ), true );
					$changed ++;
				}
			}
		} elseif ( 'delete_permanently' === $action ) {
			$report_action = 'deleted_permanently';

			foreach ( $ids as $id ) {
				$reservation = er_get_reservation( $id );

				if ( $reservation && $reservation->is_editable() ) {
					$reservation->delete( true );
					$changed ++;
				}
			}
		} elseif ( false !== strpos( $action, 'mark_' ) ) {
			$order_statuses = ER_Reservation_Status::get_statuses();
			$new_status     = substr( $action, 5 ); // Get the status name from action.
			$report_action  = 'marked_' . $new_status;

			// Sanity check: bail out if this is actually not a status, or is not a registered status.
			if ( isset( $order_statuses[ $new_status ] ) ) {
				foreach ( $ids as $id ) {
					$reservation = er_get_reservation( $id );

					if ( $reservation && $reservation->is_order_editable() ) {
						$reservation->update_status( $new_status, __( 'Reservation status changed by bulk edit:', 'easyReservations' ), true );
						do_action( 'easyreservations_reservation_edit_status', $id, $new_status );
						$changed ++;
					}
				}
			}
		}

		if ( $changed ) {
			$redirect_to = add_query_arg(
				array(
					'post_type'   => $this->list_table_type,
					'bulk_action' => $report_action,
					'changed'     => $changed,
					'ids'         => join( ',', $ids ),
				),
				$redirect_to
			);
		}

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Show confirmation message that order status changed for number of orders.
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page.
		if ( 'edit.php' !== $pagenow || 'easy_reservation' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
			return;
		}

		$order_statuses = ER_Reservation_Status::get_statuses();
		$number         = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
		$bulk_action    = er_clean( wp_unslash( $_REQUEST['bulk_action'] ) ); // WPCS: input var ok, CSRF ok.

		// Check if any status changes happened.
		foreach ( $order_statuses as $slug => $name ) {
			if ( 'marked_' . $slug === $bulk_action ) { // WPCS: input var ok, CSRF ok.
				/* translators: %d: orders count */
				$message = sprintf( _n( '%d reservation status changed.', '%d reservations statuses changed.', $number, 'easyReservations' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
				break;
			}
		}

		if ( 'moved_to_trash' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
			/* translators: %d: reservation count */
			$message = sprintf( _n( 'Moved %d reservation to trash.', 'Moved %d reservations to trash.', $number, 'easyReservations' ), number_format_i18n( $number ) );
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
		} elseif ( 'deleted_permanently' === $bulk_action ) {
			$message = sprintf( _n( 'Deleted %d reservation permanently.', 'Deleted %d reservations permanently.', $number, 'easyReservations' ), number_format_i18n( $number ) );
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$this->months_dropdown();
		$this->resources_dropdown();
	}

	/**
	 * Display a resources dropdown for filtering items
	 */
	protected function resources_dropdown() {
		$options = array( '' => __( 'All resources', 'easyReservations' ) ) + er_form_resources_options();
		?>
        <label for="resource" class="screen-reader-text"><?php esc_html_e( 'Filter by resource', 'easyReservations' ); ?></label>
		<?php er_form_get_field( array(
			'id'      => 'resource',
			'type'    => 'select',
			'options' => $options,
			'value'   => isset( $_GET['resource'] ) ? absint( $_GET['resource'] ) : 0
		) ); ?>
		<?php
	}

	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @global wpdb      $wpdb WordPress database abstraction object.
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 */
	protected function months_dropdown() {
		global $wpdb, $wp_locale;

		$extra_checks = "AND status != 'auto-draft'";
		if ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) {
			$extra_checks .= " AND status != 'trash'";
		} elseif ( isset( $_GET['post_status'] ) ) {
			$extra_checks = $wpdb->prepare( ' AND status = %s', $_GET['post_status'] );
		}

		$months = $wpdb->get_results(
			"SELECT DISTINCT YEAR( arrival ) AS year, MONTH( arrival ) AS month
            FROM {$wpdb->prefix}reservations
            WHERE 1 = 1 $extra_checks
            ORDER BY arrival DESC"
		);

		$month_count = count( $months );

		if ( ! $month_count || ( 1 == $month_count && 0 == $months[0]->month ) ) {
			return;
		}

		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
        <label for="filter-by-date" class="screen-reader-text"><?php esc_html_e( 'Filter by date' ); ?></label>
        <select name="m" id="filter-by-date">
            <option<?php selected( $m, 0 ); ?> value="0"><?php esc_html_e( 'All dates' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf(
					"<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: Month name, 2: 4-digit year. */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
        </select>
		<?php
	}

	/**
	 * Change the label when searching orders.
	 *
	 * @param mixed $query Current search query.
	 *
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'easy_reservation' !== $typenow || ! get_query_var( 'easy_reservation_search' ) || ! isset( $_GET['s'] ) ) { // phpcs:disable  WordPress.Security.NonceVerification.NoNonceVerification
			return $query;
		}

		return er_clean( wp_unslash( $_GET['s'] ) ); // WPCS: input var ok, sanitization ok.
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars Array of query vars.
	 *
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'easy_order_search';

		return $public_query_vars;
	}

	/**
	 * Search custom fields as well as content.
	 *
	 * @param WP_Query $wp Query object.
	 */
	public function search_custom_fields( $wp ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'easy_reservation' !== $wp->query_vars['post_type'] || ! isset( $_GET['s'] ) ) { // phpcs:disable  WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		$term = er_clean( wp_unslash( $_GET['s'] ) );

		if ( is_numeric( $term ) ) {
			$booking_ids = array( $term );
		} else {
			$order_ids   = er_search_orders( $term );
			$booking_ids = $order_ids ? er_reservation_get_ids_from_order_id( $order_ids ) : array( 0 );
		}

		// Remove "s" - we don't want to search order name.
		unset( $wp->query_vars['s'] );

		// so we know we're doing this.
		$wp->query_vars['easy_reservation_search'] = true;

		// Search by found posts.
		$wp->query_vars['post__in'] = array_merge( $booking_ids, array( 0 ) );
	}
}
