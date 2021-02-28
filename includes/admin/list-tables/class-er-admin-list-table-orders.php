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
class ER_Admin_List_Table_Orders extends ER_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'easy_order';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
		add_action( 'admin_footer', array( $this, 'order_preview_template' ) );
		add_action( 'parse_query', array( $this, 'search_custom_fields' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="easyreservations-BlankState">';

		echo '<h2 class="easyreservations-BlankState-message">' . esc_html__( 'When you receive a new order, it will appear here.', 'easyReservations' ) . '</h2>';

		echo '<div class="easyreservations-BlankState-buttons">';
		echo '<a class="easyreservations-BlankState-cta button-primary button" target="_blank" href="https://easyreservations.org/documentation/managing-orders/">' . esc_html__( 'Learn more about orders', 'easyReservations' ) . '</a>';
		echo '</div>';

		echo '</div>';
	}

	/**
	 * Define primary column.
	 *
	 * @return string
	 */
	protected function get_primary_column() {
		return 'order_number';
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
			'address',
			'order_paid',
			'er_actions',
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
			'order_number' => 'ID',
			'order_total'  => 'order_total',
			'order_paid'  => 'paid',
			'order_date'   => 'date',
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
		$show_columns                   = array();
		$show_columns['cb']             = $columns['cb'];
		$show_columns['order_number']   = __( 'Order', 'easyReservations' );
		$show_columns['order_date']     = __( 'Date', 'easyReservations' );
		$show_columns['order_status']   = __( 'Status', 'easyReservations' );
		$show_columns['order_customer'] = __( 'Customer', 'easyReservations' );
		$show_columns['address']        = __( 'Address', 'easyReservations' );
		$show_columns['order_total']    = __( 'Total', 'easyReservations' );
		$show_columns['order_paid']    = __( 'Paid', 'easyReservations' );
		$show_columns['er_actions']     = __( 'Actions', 'easyReservations' );

		wp_enqueue_script( 'er-orders' );

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

		$actions['mark_processing'] = __( 'Change status to processing', 'easyReservations' );
		$actions['mark_on-hold']    = __( 'Change status to on-hold', 'easyReservations' );
		$actions['mark_completed']  = __( 'Change status to completed', 'easyReservations' );

		if ( er_string_to_bool( get_option( 'reservations_allow_bulk_remove_personal_data', 'no' ) ) ) {
			$actions['remove_personal_data'] = __( 'Remove personal data', 'easyReservations' );
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
			$this->object = er_get_order( $post_id );
		}
	}

	/**
	 * Render columm: order_number.
	 */
	protected function render_order_number_column() {
		$buyer = '';

		if ( $this->object->get_first_name() || $this->object->get_last_name() ) {
			/* translators: 1: first name 2: last name */
			$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'easyReservations' ), $this->object->get_first_name(), $this->object->get_last_name() ) );
		} elseif ( $this->object->get_company() ) {
			$buyer = trim( $this->object->get_company() );
		} elseif ( $this->object->get_user_id() ) {
			$user  = get_user_by( 'id', $this->object->get_user_id() );
			$buyer = ucwords( $user->display_name );
		}

		/**
		 * Filter buyer name in list table orders.
		 *
		 * @param string   $buyer Buyer name.
		 * @param ER_Order $order Order data.
		 */
		$buyer = apply_filters( 'easyreservations_admin_order_buyer_name', $buyer, $this->object );

		if ( $this->object->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $this->object->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
		} else {
			echo '<a href="#" class="order-preview" data-order-id="' . absint( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'easyReservations' ) ) . '">' . esc_html( __( 'Preview', 'easyReservations' ) ) . '</a>';
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="order-view"><strong>#' . esc_attr( $this->object->get_order_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
		}
	}

	/**
	 * Render columm: order_status.
	 */
	protected function render_order_status_column() {
		$tooltip                 = '';
		$comment_count           = get_comment_count( $this->object->get_id() );
		$approved_comments_count = absint( $comment_count['approved'] );

		if ( $approved_comments_count ) {
			$latest_notes = er_get_order_notes(
				array(
					'order_id' => $this->object->get_id(),
					'limit'    => 1,
					'orderby'  => 'date_created_gmt',
				)
			);

			$latest_note = current( $latest_notes );

			if ( isset( $latest_note->content ) && 1 === $approved_comments_count ) {
				$tooltip = er_sanitize_tooltip( $latest_note->content );
			} elseif ( isset( $latest_note->content ) ) {
				/* translators: %d: notes count */
				$tooltip = er_sanitize_tooltip( $latest_note->content . '<br/><small style="display:block">' . sprintf( _n( 'Plus %d other note', 'Plus %d other notes', ( $approved_comments_count - 1 ), 'easyReservations' ), $approved_comments_count - 1 ) . '</small>' );
			} else {
				/* translators: %d: notes count */
				$tooltip = er_sanitize_tooltip( sprintf( _n( '%d note', '%d notes', $approved_comments_count, 'easyReservations' ), $approved_comments_count ) );
			}
		}

		if ( $tooltip ) {
			printf( '<mark class="order-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), wp_kses_post( $tooltip ), esc_html( ER_Order_Status::get_title( $this->object->get_status() ) ) );
		} else {
			printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), esc_html( ER_Order_Status::get_title( $this->object->get_status() ) ) );
		}
	}

	/**
	 * Render columm: order_date.
	 */
	protected function render_order_date_column() {
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
	 * Render columm: order_total.
	 */
	protected function render_order_total_column() {
		if ( $this->object->get_payment_method_title() ) {
			/* translators: %s: method */
			echo '<span class="tips" data-tip="' . esc_attr( sprintf( __( 'via %s', 'easyReservations' ), $this->object->get_payment_method_title() ) ) . '">' . wp_kses_post( $this->object->get_formatted_total() ) . '</span>';
		} else {
			echo wp_kses_post( $this->object->get_formatted_total() );
		}
	}

	/**
	 * Render columm: order_paid.
	 */
	protected function render_order_paid_column() {
		if ( $this->object->get_payment_method_title() ) {
			/* translators: %s: method */
			echo '<span class="tips" data-tip="' . esc_attr( sprintf( __( 'via %s', 'easyReservations' ), $this->object->get_payment_method_title() ) ) . '">' . wp_kses_post( er_price( $this->object->get_paid( 'edit' ), true ) ) . '</span>';
		} else {
			echo wp_kses_post( er_price( $this->object->get_paid( 'edit' ), true ) );
		}
	}

	/**
	 * Render columm: address.
	 */
	protected function render_address_column() {
	    echo wp_kses_post( $this->object->get_formatted_address() );
	}

	/**
	 * Render columm: customer.
	 */
	protected function render_order_customer_column() {
		if ( $this->object->get_user_id() ) {

			$user = get_user_by( 'id', $this->object->get_user_id() );

			$user_string = sprintf(
			/* translators: 1: user display name 2: user ID 3: user email */
				esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'easyReservations' ),
				$user->display_name,
				absint( $user->ID ),
				$user->user_email
			);

			/* translators: %s: method */
			printf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array(
					'post_status'    => 'all',
					'post_type'      => 'easy_order',
					'_customer_user' => $this->object->get_user_id(),
				), admin_url( 'edit.php' ) ) ),
				$user_string
			);
		}
	}

	/**
	 * Render columm: er_actions.
	 */
	protected function render_er_actions_column() {
		echo '<p>';

		do_action( 'easyreservations_admin_order_actions_start', $this->object );

		$actions = array();

		if ( $this->object->has_status( array( 'pending', 'on-hold' ) ) ) {
			$actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_order_status&status=processing&order_id=' . $this->object->get_id() ), 'easyreservations-mark-order-status' ),
				'name'   => __( 'Processing', 'easyReservations' ),
				'action' => 'processing',
			);
		}

		if ( $this->object->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_order_status&status=completed&order_id=' . $this->object->get_id() ), 'easyreservations-mark-order-status' ),
				'name'   => __( 'Complete', 'easyReservations' ),
				'action' => 'complete',
			);
		}

		$actions = apply_filters( 'easyreservations_admin_order_actions', $actions, $this->object );

		echo er_render_action_buttons( $actions ); // WPCS: XSS ok.

		do_action( 'easyreservations_admin_order_actions_end', $this->object );

		echo '</p>';
	}

	/**
	 * Template for order preview.
	 */
	public function order_preview_template() {
		?>
        <script type="text/template" id="tmpl-er-modal-view-order">
            <div class="er-backbone-modal er-order-preview">
                <div class="er-backbone-modal-content">
                    <section class="er-backbone-modal-main" role="main">
                        <header class="er-backbone-modal-header">
                            <mark class="order-status status-{{ data.status }}"><span>{{ data.status_name }}</span>
                            </mark>
							<?php /* translators: %s: order ID */ ?>
                            <h1><?php echo esc_html( sprintf( __( 'Order #%s', 'easyReservations' ), '{{ data.order_number }}' ) ); ?></h1>
                            <button class="modal-close modal-close-link dashicons dashicons-no-alt">
                                <span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'easyReservations' ); ?></span>
                            </button>
                        </header>
                        <article>
							<?php do_action( 'easyreservations_admin_order_preview_start' ); ?>

                            <div class="er-order-preview-addresses">

                                <div class="er-order-preview-address">
                                    <h2><?php esc_html_e( 'Order details', 'easyReservations' ); ?></h2>
                                    {{{ data.formatted_address }}}

                                    <# if ( data.data.address.email ) { #>
                                    <strong><?php esc_html_e( 'Email', 'easyReservations' ); ?></strong>
                                    <a href="mailto:{{ data.data.address.email }}">{{ data.data.address.email }}</a>
                                    <# } #>

                                    <# if ( data.data.address.phone ) { #>
                                    <strong><?php esc_html_e( 'Phone', 'easyReservations' ); ?></strong>
                                    <a href="tel:{{ data.data.address.phone }}">{{ data.data.address.phone }}</a>
                                    <# } #>

                                    <# if ( data.payment_via ) { #>
                                    <strong><?php esc_html_e( 'Payment via', 'easyReservations' ); ?></strong>
                                    {{{ data.payment_via }}}
                                    <# } #>
                                </div>

                                <div class="er-order-preview-address">
                                    <h2><?php esc_html_e( 'Custom data', 'easyReservations' ); ?></h2>
                                    {{{ data.formatted_custom }}}
                                </div>

                                <# if ( data.data.customer_note ) { #>
                                <div class="er-order-preview-note">
                                    <strong><?php esc_html_e( 'Note', 'easyReservations' ); ?></strong>
                                    {{ data.data.customer_note }}
                                </div>
                                <# } #>
                            </div>

                            {{{ data.item_html }}}

							<?php do_action( 'easyreservations_admin_order_preview_end' ); ?>
                        </article>
                        <footer>
                            <div class="inner">
                                {{{ data.actions_html }}}

                                <a class="button button-primary button-large" aria-label="<?php esc_attr_e( 'Edit this order', 'easyReservations' ); ?>" href="<?php echo esc_url( admin_url( 'post.php?action=edit' ) ); ?>&post={{ data.data.id }}"><?php esc_html_e( 'Edit', 'easyReservations' ); ?></a>
                            </div>
                        </footer>
                    </section>
                </div>
            </div>
            <div class="er-backbone-modal-backdrop modal-close"></div>
        </script>
		<?php
	}

	/**
	 * Get items to display in the preview as HTML.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return string
	 */
	public static function get_order_preview_item_html( $order ) {
		$hidden_order_itemmeta = apply_filters(
			'easyreservations_hidden_order_itemmeta',
			array(
				'_resource_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'cost',
			)
		);

		$line_items = apply_filters( 'easyreservations_order_preview_line_items', $order->get_items(), $order );
		$columns    = apply_filters(
			'easyreservations_admin_order_preview_line_item_columns',
			array(
				'item'  => __( 'Item', 'easyReservations' ),
				'tax'   => __( 'Tax', 'easyReservations' ),
				'total' => __( 'Total', 'easyReservations' ),
			),
			$order
		);

		if ( ! er_tax_enabled() ) {
			unset( $columns['tax'] );
		}

		$html = '
		<div class="er-order-preview-table-wrapper">
			<table cellspacing="0" class="er-order-preview-table">
				<thead>
					<tr>';

		foreach ( $columns as $column => $label ) {
			$html .= '<th class="er-order-preview-table__column--' . esc_attr( $column ) . '">' . esc_html( $label ) . '</th>';
		}

		$html .= '</tr>
				</thead>
				<tbody>';

		foreach ( $line_items as $item_id => $item ) {

			$resource_object = is_callable( array( $item, 'get_resource' ) ) ? $item->get_resource() : null;
			$row_class       = apply_filters( 'easyreservations_admin_html_order_preview_item_class', '', $item, $order );

			$html .= '<tr class="er-order-preview-table__item er-order-preview-table__item--' . esc_attr( $item_id ) . ( $row_class ? ' ' . esc_attr( $row_class ) : '' ) . '">';

			foreach ( $columns as $column => $label ) {
				$html .= '<td class="er-order-preview-table__column--' . esc_attr( $column ) . '">';
				switch ( $column ) {
					case 'item':
						$html .= wp_kses_post( $item->get_name() );

						if ( $resource_object ) {
							//$html .= '<div class="er-order-item-sku">' . esc_html( $resource_object->get_sku() ) . '</div>';
						}

						$meta_data = $item->get_formatted_meta_data( '' );

						if ( $meta_data ) {
							$html .= '<table cellspacing="0" class="er-order-item-meta">';

							foreach ( $meta_data as $meta_id => $meta ) {
								if ( in_array( $meta->key, $hidden_order_itemmeta, true ) ) {
									continue;
								}
								$html .= '<tr><th>' . wp_kses_post( $meta->display_key ) . ':</th><td>' . wp_kses_post( force_balance_tags( $meta->display_value ) ) . '</td></tr>';
							}
							$html .= '</table>';
						}
						break;
					case 'tax':
						$html .= er_price( $item->get_total_tax(), true );
						break;
					case 'total':
						$html .= er_price( $item->get_total(), true );
						break;
					default:
						$html .= apply_filters( 'easyreservations_admin_order_preview_line_item_column_' . sanitize_key( $column ), '', $item, $item_id, $order );
						break;
				}
				$html .= '</td>';
			}

			$html .= '</tr>';
		}

		$html .= '
				</tbody>
			</table>
		</div>';

		return $html;
	}

	/**
	 * Get actions to display in the preview as HTML.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return string
	 */
	public static function get_order_preview_actions_html( $order ) {
		$actions        = array();
		$status_actions = array();

		if ( $order->has_status( array( 'pending' ) ) ) {
			$status_actions['on-hold'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_order_status&status=on-hold&order_id=' . $order->get_id() ), 'easyreservations-mark-order-status' ),
				'name'   => __( 'On-hold', 'easyReservations' ),
				'title'  => __( 'Change order status to on-hold', 'easyReservations' ),
				'action' => 'on-hold',
			);
		}

		if ( $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			$status_actions['processing'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_order_status&status=processing&order_id=' . $order->get_id() ), 'easyreservations-mark-order-status' ),
				'name'   => __( 'Processing', 'easyReservations' ),
				'title'  => __( 'Change order status to processing', 'easyReservations' ),
				'action' => 'processing',
			);
		}

		if ( $order->has_status( array( 'pending', 'on-hold', 'processing' ) ) ) {
			$status_actions['complete'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=easyreservations_mark_order_status&status=completed&order_id=' . $order->get_id() ), 'easyreservations-mark-order-status' ),
				'name'   => __( 'Completed', 'easyReservations' ),
				'title'  => __( 'Change order status to completed', 'easyReservations' ),
				'action' => 'complete',
			);
		}

		if ( $status_actions ) {
			$actions['status'] = array(
				'group'   => __( 'Change status: ', 'easyReservations' ),
				'actions' => $status_actions,
			);
		}

		return er_render_action_buttons( apply_filters( 'easyreservations_admin_order_preview_actions', $actions, $order ) );
	}

	/**
	 * Get order details to send to the ajax endpoint for previews.
	 *
	 * @param ER_Order $order Order object.
	 *
	 * @return array
	 */
	public static function order_preview_get_order_details( $order ) {
		if ( ! $order ) {
			return array();
		}

		$payment_gateways = ER()->payment_gateways()->payment_gateways();
		$payment_via      = $order->get_payment_method_title();
		$payment_method   = $order->get_payment_method();
		$transaction_id   = $order->get_transaction_id();
		$address          = $order->get_formatted_address();
		$custom           = $order->get_formatted_custom();

		if ( $transaction_id ) {
			$url = isset( $payment_gateways[ $payment_method ] ) ? $payment_gateways[ $payment_method ]->get_transaction_url( $order ) : false;

			if ( $url ) {
				$payment_via .= ' (<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>)';
			} else {
				$payment_via .= ' (' . esc_html( $transaction_id ) . ')';
			}
		}

		return apply_filters(
			'easyreservations_admin_order_preview_get_order_details',
			array(
				'data'              => $order->get_data(),
				'order_number'      => $order->get_order_number(),
				'item_html'         => self::get_order_preview_item_html( $order ),
				'actions_html'      => self::get_order_preview_actions_html( $order ),
				'formatted_address' => $address ? $address : __( 'N/A', 'easyReservations' ),
				'formatted_custom'  => $custom ? er_display_meta( $custom, array(
					'before'    => '',
					'separator' => ', ',
					'after'     => '',
					'echo'      => false,
					'autop'     => false,
				) ) : __( 'N/A', 'easyReservations' ),
				'payment_via'       => $payment_via,
				'status'            => $order->get_status(),
				'status_name'       => ER_Order_Status::get_title( $order->get_status() ),
			),
			$order
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
		$ids     = apply_filters( 'easyreservations_bulk_action_ids', array_reverse( array_map( 'absint', $ids ) ), $action, 'order' );
		$changed = 0;

		if ( 'remove_personal_data' === $action ) {
			$report_action = 'removed_personal_data';

			foreach ( $ids as $id ) {
				$order = er_get_order( $id );

				if ( $order ) {
					do_action( 'easyreservations_remove_order_personal_data', $order );
					$changed ++;
				}
			}
		} elseif ( false !== strpos( $action, 'mark_' ) ) {
			$order_statuses = ER_Order_Status::get_statuses();
			$new_status     = substr( $action, 5 ); // Get the status name from action.
			$report_action  = 'marked_' . $new_status;

			// Sanity check: bail out if this is actually not a status, or is not a registered status.
			if ( isset( $order_statuses[ $new_status ] ) ) {
				foreach ( $ids as $id ) {
					$order = er_get_order( $id );

					$reservations_approved_and_existing = er_order_reservations_approved_and_existing( $order );

					if ( ! $reservations_approved_and_existing && in_array( $new_status, er_get_is_accepted_statuses() ) && $new_status !== $order->get_status() ) {
						continue;
					}

					$order->update_status( $new_status, __( 'Order status changed by bulk edit:', 'easyReservations' ), true );
					do_action( 'easyreservations_order_edit_status', $id, $new_status );
					$changed ++;
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
		if ( 'edit.php' !== $pagenow || 'easy_order' !== $post_type || ! isset( $_REQUEST['bulk_action'] ) ) { // WPCS: input var ok, CSRF ok.
			return;
		}

		$order_statuses = ER_Order_Status::get_statuses();
		$number         = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok, CSRF ok.
		$bulk_action    = er_clean( wp_unslash( $_REQUEST['bulk_action'] ) ); // WPCS: input var ok, CSRF ok.

		// Check if any status changes happened.
		foreach ( $order_statuses as $slug => $name ) {
			if ( 'marked_' . str_replace( 'er-', '', $slug ) === $bulk_action ) { // WPCS: input var ok, CSRF ok.
				/* translators: %d: orders count */
				$message = sprintf( _n( '%d order status changed.', '%d order statuses changed.', $number, 'easyReservations' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
				break;
			}
		}

		if ( 'removed_personal_data' === $bulk_action ) { // WPCS: input var ok, CSRF ok.
			/* translators: %d: orders count */
			$message = sprintf( _n( 'Removed personal data from %d order.', 'Removed personal data from %d orders.', $number, 'easyReservations' ), number_format_i18n( $number ) );
			echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * See if we should render search filters or not.
	 */
	public function restrict_manage_posts() {
		global $typenow;

		//if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
		$this->render_filters();
		//}
	}

	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$user_string = '';
		$user_id     = '';

		if ( ! empty( $_GET['_customer_user'] ) ) { // phpcs:disable  WordPress.Security.NonceVerification.NoNonceVerification
			$user_id = absint( $_GET['_customer_user'] ); // WPCS: input var ok, sanitization ok.
			$user    = get_user_by( 'id', $user_id );

			$user_string = sprintf(
			/* translators: 1: user display name 2: user ID 3: user email */
				esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'easyReservations' ),
				$user->display_name,
				absint( $user->ID ),
				$user->user_email
			);
		}
		?>
        <select class="er-customer-search" name="_customer_user" data-placeholder="<?php esc_attr_e( 'Filter by registered customer', 'easyReservations' ); ?>" data-allow_clear="true" style="width:220px">
            <option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $user_string ) ); // htmlspecialchars to prevent XSS when rendered by selectWoo. ?>
            <option>
        </select>
		<?php
	}

	/**
	 * Handle any filters.
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array
	 */
	public function request_query( $query_vars ) {
		global $typenow;

		//if ( in_array( $typenow, wc_get_order_types( 'order-meta-boxes' ), true ) ) {
		return $this->query_filters( $query_vars );
		//}

		//return $query_vars;
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 *
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		global $wp_post_statuses;

		// Filter the orders by the posted customer.
		if ( ! empty( $_GET['_customer_user'] ) ) { // WPCS: input var ok.
			// @codingStandardsIgnoreStart.
			$query_vars['meta_query'] = array(
				array(
					'key'     => '_customer_user',
					'value'   => (int) $_GET['_customer_user'], // WPCS: input var ok, sanitization ok.
					'compare' => '=',
				),
			);
			// @codingStandardsIgnoreEnd
		}

		// Sorting.
		if ( isset( $query_vars['orderby'] ) ) {
			if ( 'order_total' === $query_vars['orderby'] ) {
				// @codingStandardsIgnoreStart
				$query_vars = array_merge( $query_vars, array(
					'meta_key' => '_order_total',
					'orderby'  => 'meta_value_num',
				) );
				// @codingStandardsIgnoreEnd
			} elseif ( 'order_paid' === $query_vars['orderby'] ) {
				// @codingStandardsIgnoreStart
				$query_vars = array_merge( $query_vars, array(
					'meta_key' => 'paid',
					'orderby'  => 'meta_value_num',
				) );
				// @codingStandardsIgnoreEnd
			}
		}

		// Status.
		if ( empty( $query_vars['post_status'] ) ) {
			$post_statuses = ER_Order_Status::get_statuses();

			foreach ( $post_statuses as $status => $value ) {
				if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
					unset( $post_statuses[ $status ] );
				}
			}

			$query_vars['post_status'] = array_keys( $post_statuses );
		}

		return $query_vars;
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

		if ( 'edit.php' !== $pagenow || 'easy_order' !== $typenow || ! get_query_var( 'easy_order_search' ) || ! isset( $_GET['s'] ) ) { // phpcs:disable  WordPress.Security.NonceVerification.NoNonceVerification
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

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'easy_order' !== $wp->query_vars['post_type'] || ! isset( $_GET['s'] ) ) { // phpcs:disable  WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		$post_ids = er_search_orders( er_clean( wp_unslash( $_GET['s'] ) ) ); // WPCS: input var ok, sanitization ok.

		if ( ! empty( $post_ids ) ) {
			// Remove "s" - we don't want to search order name.
			unset( $wp->query_vars['s'] );

			// so we know we're doing this.
			$wp->query_vars['easy_order_search'] = true;

			// Search by found posts.
			$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
		}
	}
}
