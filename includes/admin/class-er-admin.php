<?php
//Prevent direct access to file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ER_Admin class.
 */
class ER_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

		if ( get_option( 'reservations_db_version' ) == RESERVATIONS_VERSION ) {
			add_action( 'init', array( $this, 'register_reservation_post_type' ) );
			add_action( 'admin_init', array( $this, 'preview_emails' ) );
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'wp_loaded', array( $this, 'init_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'load_blocks' ) );
			add_action( 'current_screen', array( $this, 'conditional_includes' ) );

			add_filter( 'block_categories', array( $this, 'add_block_category' ), 10, 2 );
			add_filter( 'admin_body_class', array( $this, 'admin_body_class' ), 10, 1 );
		}
	}

	/**
	 * Include files
	 */
	public function includes() {
		include_once( RESERVATIONS_ABSPATH . 'includes/admin/er-admin-functions.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/admin/er-meta-box-functions.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-notices.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-post-types.php' );

		/*
		include_once( RESERVATIONS_ABSPATH . 'includes/tracks/class-er-tracks.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/tracks/class-er-tracks-event.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/tracks/class-er-tracks-client.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/tracks/class-er-tracks-footer-pixel.php' );
		include_once( RESERVATIONS_ABSPATH . 'includes/tracks/class-er-site-tracking.php' );
		*/

		if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) ) {
			switch ( $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'reservation':
					include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-reservation.php' );
					break;

				case 'resource':
					include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-resources.php' );
					break;

				case 'reservation-availability':
					include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-availability.php' );
					break;

				case 'er-settings':
					include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-settings.php' );
					break;

				case 'er-setup':
					include_once( RESERVATIONS_ABSPATH . 'includes/admin/class-er-admin-setup-wizard.php' );
					break;
			}
		}
	}

	/**
	 * Register reservations as post type to use edit screens
	 */
	public function register_reservation_post_type() {
		register_post_type( 'easy_reservation', array(
			'labels'              => array(
				'name'                  => __( 'Reservation', 'easyReservations' ),
				'singular_name'         => _x( 'Reservation', 'easy_reservations post type singular name', 'easyReservations' ),
				'add_new'               => __( 'Add reservation', 'easyReservations' ),
				'add_new_item'          => __( 'Add new reservation', 'easyReservations' ),
				'edit'                  => __( 'Edit', 'easyReservations' ),
				'edit_item'             => __( 'Edit reservation', 'easyReservations' ),
				'new_item'              => __( 'New reservation', 'easyReservations' ),
				'view_item'             => __( 'View reservation', 'easyReservations' ),
				'search_items'          => __( 'Search reservations', 'easyReservations' ),
				'not_found'             => __( 'No reservation found', 'easyReservations' ),
				'not_found_in_trash'    => __( 'No reservation found in trash', 'easyReservations' ),
				'menu_name'             => _x( 'Reservations', 'Admin menu name', 'easyReservations' ),
				'filter_items_list'     => __( 'Filter reservations', 'easyReservations' ),
				'items_list_navigation' => __( 'Reservations navigation', 'easyReservations' ),
				'items_list'            => __( 'Reservations list', 'easyReservations' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'query_var'           => false,
			'rewrite'             => false,
			'show_in_menu'        => current_user_can( 'manage_easyreservations' ) ? 'reservations' : false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'can_export'          => false,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'show_in_nav_menus'   => false,
			'delete_with_user'    => false,
		) );
	}

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		switch ( $screen->id ) {
			case 'easy-rooms':
				//Only allow existing resources to get edited in post editor
				if ( ! isset( $_GET['post'] ) ) {
					wp_safe_redirect( admin_url( 'admin.php?page=resource&add_resource=resource' ) );
				}
				break;
			case 'easy_reservation':
				wp_safe_redirect( admin_url( 'admin.php?page=reservation&new' ) );
				break;
			case 'options-permalink':
				include 'class-er-admin-permalink-settings.php';
				break;
			case 'users':
			case 'user':
			case 'profile':
			case 'user-edit':
				include 'class-er-admin-profile.php';
				break;

			case 'plugins':
			case 'dashboard':
			case 'dashboard-network':
			case 'update-core':
				break;
		}
	}

	/**
	 * Add block category
	 *
	 * @param $categories
	 * @param $post
	 *
	 * @return array
	 */
	public function add_block_category( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'easy-reservations',
					'title' => __( 'easyReservations', 'easyReservations' ),
					'icon'  => 'wordpress',
				),
			)
		);
	}

	/**
	 * Load block scripts
	 */
	public function load_blocks() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script(
			'er-blocks',
			ER()->plugin_url() . '/assets/js/admin/er-blocks' . $suffix . '.js',
			array( 'wp-blocks', 'wp-editor' ),
			true
		);

		$pages = get_pages( array(
			'post_type'   => 'page',
			'post_status' => 'publish,private,draft',
			'child_of'    => 0,
			'parent'      => - 1,
			'sort_order'  => 'asc',
			'sort_column' => 'post_title',
		) );

		$page_choices = array( '' => __( 'No page set', 'easyReservations' ) ) + array_combine( array_map( 'strval', wp_list_pluck( $pages, 'ID' ) ), wp_list_pluck( $pages, 'post_title' ) );

		wp_localize_script(
			'er-blocks',
			'er_blocks_params',
			array(
				'form_templates'   => er_form_template_options(),
				'pages'            => $page_choices,
				'calendar_display' => er_clean( array(
					array( 'value' => '0', 'label' => __( 'Nothing', 'easyReservations' ) ),
					array( 'value' => '2', 'label' => '150' ),
					array( 'value' => '1', 'label' => '150' . er_get_currency_symbol() ),
					array( 'value' => '5', 'label' => er_get_currency_symbol() . '150' ),
					array( 'value' => '3', 'label' => er_price( 150, 1 ) ),
					array( 'value' => '4', 'label' => er_price( 150, 0 ) ),
					array( 'value' => 'avail', 'label' => __( 'Amount of free spaces', 'easyReservations' ) ),
				) ),
				'days'             => er_date_get_label()
			)
		);

		wp_enqueue_script( 'er-blocks' );

		wp_enqueue_style(
			'er-blocks',
			ER()->plugin_url() . '/assets/css/editor' . $suffix . '.css'
		);
	}

	/**
	 * Load admin scripts
	 */
	public function load_scripts() {
		$this->admin_scripts();

		if ( isset( $_GET['page'] ) ) {
			$page = sanitize_key( $_GET['page'] );

			if ( $page == 'resource' ) {  //  Only load Styles and Scripts on Resources Page
				$this->resource_scripts();
			}
		}
	}

	/**
	 * Body class of admin
	 *
	 * @param $classes
	 *
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( $screen_id && in_array( $screen_id, er_get_screen_ids() ) ) {
			$classes .= ' easyreservations';
		}

		return $classes;
	}

	public function admin_scripts() {  //  Load Scripts and Styles
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$user_id   = get_current_user_id();

		// Register admin styles.
		wp_register_style( 'er-admin-style', ER()->plugin_url() . '/assets/css/admin' . $suffix . '.css', array( 'easy-ui' ) );
		wp_register_style( 'easyreservations_admin_privacy_styles', ER()->plugin_url() . '/assets/css/privacy' . $suffix . '.css', array() );

		// Add RTL support for admin styles.
		wp_style_add_data( 'er-admin-style', 'rtl', 'replace' );
		wp_style_add_data( 'easyreservations_admin_privacy_styles', 'rtl', 'replace' );

		// Privacy Policy Guide css for back-compat.
		if ( isset( $_GET['wp-privacy-policy-guide'] ) || in_array( $screen_id, array( 'privacy-policy-guide' ) ) ) {
			wp_enqueue_style( 'easyreservations_admin_privacy_styles' );
		}

		wp_register_script( 'er-enhanced-select', ER()->plugin_url() . '/assets/js/admin/er-enhanced-select' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION );
		wp_localize_script(
			'er-enhanced-select',
			'er_enhanced_select_params',
			array(
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'easyReservations' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'easyReservations' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'easyReservations' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'easyReservations' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'easyReservations' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'easyReservations' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'easyReservations' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'easyReservations' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'easyReservations' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'easyReservations' ),
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
				'search_order_nonce'        => wp_create_nonce( 'search-order' ),
			)
		);

		wp_register_script( 'round', ER()->plugin_url() . '/assets/js/round/round' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION );
		wp_register_script( 'accounting', ER()->plugin_url() . '/assets/js/accounting/accounting' . $suffix . '.js', array( 'jquery' ), '0.4.2' );
		wp_register_script( 'er-backbone-modal', ER()->plugin_url() . '/assets/js/admin/backbone-modal' . $suffix . '.js', array( 'underscore', 'backbone', 'wp-util' ), RESERVATIONS_VERSION, true );
		wp_register_script( 'stupidtable', ER()->plugin_url() . '/assets/js/stupidtable/stupidtable' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION, true );
		wp_register_script( 'jquery-tiptip', ER()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION, true );
		wp_register_script( 'er-clipboard', ER()->plugin_url() . '/assets/js/admin/er-clipboard' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-admin-system-status', ER()->plugin_url() . '/assets/js/admin/settings-views-html-status' . $suffix . '.js', array( 'er-clipboard' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-admin-availability', ER()->plugin_url() . '/assets/js/admin/settings-views-html-availability' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION );
		wp_localize_script(
			'er-admin-availability',
			'er_admin_availability_params',
			array(
				'i18n_headline'    => __( 'Global availability', 'easyReservations' ),
				'i18n_description' => __( 'Availability filters apply to all resources. The resources availability filter only get applied if none of these match. ', 'easyReservations' ),
			)
		);

		wp_register_script( 'er-settings-form', ER()->plugin_url() . '/assets/js/admin/settings-views-html-form' . $suffix . '.js', array( 'jquery' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-settings-custom', ER()->plugin_url() . '/assets/js/admin/settings-views-html-custom' . $suffix . '.js', array( 'jquery', 'jquery-ui-sortable' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-settings-tax', ER()->plugin_url() . '/assets/js/admin/settings-views-html-tax' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone', 'jquery-blockui' ), RESERVATIONS_VERSION );
		wp_register_script( 'er-admin', RESERVATIONS_URL . 'assets/js/admin/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-tiptip', 'wp-util', 'jquery-ui-sortable', 'iris', 'wp-a11y' ), RESERVATIONS_VERSION, true );

		wp_register_script( 'er-admin-receipt-meta-boxes', ER()->plugin_url() . '/assets/js/admin/meta-boxes-receipt' . $suffix . '.js', array(
			'er-backbone-modal',
			'er-clipboard',
			'accounting',
		), RESERVATIONS_VERSION, true );

		wp_register_script( 'er-admin-custom-meta-boxes', ER()->plugin_url() . '/assets/js/admin/meta-boxes-custom' . $suffix . '.js', array(
			'er-backbone-modal',
			'jquery-ui-slider',
			'easy-ui',
			'er-admin-meta-boxes'
		), RESERVATIONS_VERSION, true );

		wp_register_script( 'er-admin-reservation-meta-boxes', ER()->plugin_url() . '/assets/js/admin/meta-boxes-reservation' . $suffix . '.js', array(
			'er-datepicker',
			'er-admin-receipt-meta-boxes',
			'er-admin-custom-meta-boxes',
		), RESERVATIONS_VERSION, true );

		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		wp_localize_script(
			'er-admin',
			'er_admin_params',
			array(
				'mon_decimal_point'                   => er_get_price_decimal_separator(),
				/* translators: %s: decimal */
				'i18n_decimal_error'                  => sprintf( __( 'Please enter with one decimal point (%s) without thousand separators.', 'easyReservations' ), $decimal ),
				/* translators: %s: price decimal separator */
				'i18n_mon_decimal_error'              => sprintf( __( 'Please enter with one monetary decimal point (%s) without thousand separators and currency symbols.', 'easyReservations' ), er_get_price_decimal_separator() ),
				'i18n_country_iso_error'              => __( 'Please enter in country code with two capital letters.', 'easyReservations' ),
				'i18n_nav_warning'                    => __( 'The changes you made will be lost if you navigate away from this page.', 'easyReservations' ),
				'i18n_moved_up'                       => __( 'Item moved up', 'easyReservations' ),
				'i18n_moved_down'                     => __( 'Item moved down', 'easyReservations' ),
				'i18n_no_specific_countries_selected' => __( 'Selecting no country or region to sell to prevents from completing the checkout. Continue anyway?', 'easyReservations' ),
			)
		);

		wp_register_script( 'er-orders', ER()->plugin_url() . '/assets/js/admin/er-orders' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'er-backbone-modal', 'jquery-blockui' ), RESERVATIONS_VERSION );
		wp_localize_script(
			'er-orders',
			'er_orders_params',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'preview_nonce' => wp_create_nonce( 'easyreservations-preview-order' ),
			)
		);

		if ( in_array( $screen_id, er_get_screen_ids() ) ) {
			wp_enqueue_script( 'er-enhanced-select' );
			wp_enqueue_script( 'er-admin' );
			wp_enqueue_script( 'easy-ui' );
			wp_enqueue_style( 'er-admin-style' );
		}

		if ( $screen_id === 'edit-easy_reservation' || $screen_id === 'admin_page_reservation' ) {
			global $wpdb;

			$reservation = isset( $_GET['reservation'] ) ? er_get_reservation( absint( $_GET['reservation'] ) ) : false;

			$pending_reservations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT r.id as id, r.arrival arrival, r.departure as departure, r.resource as resource, r.space as space, r.adults as adults, r.children as children, r.status as status, r.order_id as order_id, m.meta_value as title " .
					"FROM {$wpdb->prefix}reservations as r " .
					"LEFT JOIN {$wpdb->prefix}reservationmeta as m ON m.reservation_id = r.id AND m.meta_key = %s " .
					"WHERE r.arrival >= NOW() AND status IN ('" . implode( "', '", er_reservation_get_pending_statuses() ) . "') " .
					"ORDER BY r.arrival",
					'_title'
				)
			);

			wp_register_script( 'er-timeline', ER()->plugin_url() . '/assets/js/admin/er-timeline' . $suffix . '.js', array( 'moment', 'er-datepicker', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-resizable' ), RESERVATIONS_VERSION );
			wp_localize_script(
				'er-timeline',
				'er_timeline_params',
				array(
					'ajax_url'             => admin_url( 'admin-ajax.php' ),
					'order_url'            => admin_url( 'post.php?post=%s&action=edit' ),
					'i18n_enter_title'     => __( 'Please enter a title for the event.', 'easyReservations' ),
					'i18n_no_resource'     => __( 'No resource selected', 'easyReservations' ),
					'i18n_no_arrivals'     => __( 'No arrivals', 'easyReservations' ),
					'i18n_no_departures'   => __( 'No departures', 'easyReservations' ),
					'i18n_stop_edit'       => __( 'Save changes', 'easyReservations' ),
					'i18n_allow_edit'      => __( 'Allow edit', 'easyReservations' ),
					'i18n_no_pending'      => __( 'No pending reservations', 'easyReservations' ),
					'i18n_no_order'        => __( 'Not attached to any order', 'easyReservations' ),
					'i18n_order'           => __( 'Attached to order %s', 'easyReservations' ),
					'nonce'                => wp_create_nonce( 'easyreservations-timeline' ),
					'resources'            => er_list_pluck( ER()->resources()->get(), 'get_data' ),
					'pending'              => $pending_reservations,
					'default_cells'        => 45,
					'first_hour'           => 0,
					'last_hour'            => 23,
					'reservation_id'       => $reservation ? $reservation->get_id() : 0,
					'reservation_resource' => $reservation ? $reservation->get_resource_id() : 0,
					'reservation_arrival'  => $reservation ? $reservation->get_arrival()->format( 'Y-m-d H:i:s' ) : '',
					'default_interval'     => 86400,
					'default_hourly'       => get_user_meta( $user_id, 'timeline_hourly', true ),
					'default_snapping'     => get_user_meta( $user_id, 'timeline_snapping', true ) === 'on',
				)
			);
		}

		if ( $screen_id === 'edit-easy_reservation' ) {
			wp_register_script( 'er-reservations', ER()->plugin_url() . '/assets/js/admin/er-reservations' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'er-backbone-modal', 'jquery-blockui', 'er-timeline' ), RESERVATIONS_VERSION );
			wp_localize_script(
				'er-reservations',
				'er_reservations_params',
				array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'preview_nonce' => wp_create_nonce( 'easyreservations-preview-reservation' ),
				)
			);
		}

		if ( in_array( str_replace( 'edit-', '', $screen_id ), array( 'easy_order', 'easy-rooms', 'easy_coupon', 'admin_page_reservation' ) ) ) {
			global $post;

			$params = array(
				'remove_item_notice'           => __( 'Are you sure you want to remove the selected items?', 'easyReservations' ),
				'remove_reservation_notice'    => __( 'Are you sure you want to remove the selected reservation from this order?', 'easyReservations' ),
				'i18n_select_items'            => __( 'Please select some items.', 'easyReservations' ),
				'i18n_do_refund'               => __( 'Are you sure you wish to process this refund? This action cannot be undone.', 'easyReservations' ),
				'i18n_delete_refund'           => __( 'Are you sure you wish to delete this refund? This action cannot be undone.', 'easyReservations' ),
				'i18n_delete_tax'              => __( 'Are you sure you wish to delete this tax column? This action cannot be undone.', 'easyReservations' ),
				'remove_item_meta'             => __( 'Remove this item meta?', 'easyReservations' ),
				'name_label'                   => __( 'Name', 'easyReservations' ),
				'remove_label'                 => __( 'Remove', 'easyReservations' ),
				'values_label'                 => __( 'Value(s)', 'easyReservations' ),
				'calc_totals'                  => __( 'Recalculate totals?', 'easyReservations' ),
				'load_address'                 => __( "Load the customer's address information? This will remove any currently entered address information.", 'easyReservations' ),
				'featured_label'               => __( 'Featured', 'easyReservations' ),
				'onsale_label'                 => __( 'On-sale', 'easyReservations' ),
				'prices_include_tax'           => esc_attr( get_option( 'reservations_prices_include_tax' ) ),
				'round_at_subtotal'            => esc_attr( get_option( 'reservations_tax_round_at_subtotal' ) ),
				'no_customer_selected'         => __( 'No customer selected', 'easyReservations' ),
				'plugin_url'                   => ER()->plugin_url(),
				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'custom_nonce'                 => wp_create_nonce( 'custom' ),
				'receipt_item_nonce'           => wp_create_nonce( 'receipt-item' ),
				'preview_nonce'                => wp_create_nonce( 'easyreservations-preview-reservation' ),
				'calc_totals_nonce'            => wp_create_nonce( 'calc-totals' ),
				'get_customer_details_nonce'   => wp_create_nonce( 'get-customer-details' ),
				'add_order_note_nonce'         => wp_create_nonce( 'add-order-note' ),
				'delete_order_note_nonce'      => wp_create_nonce( 'delete-order-note' ),
				'post_id'                      => isset( $post->ID ) ? $post->ID : ( isset( $_GET['reservation'] ) ? absint( $_GET['reservation'] ) : '' ),
				'post_type'                    => isset( $post->ID ) ? 'order' : 'reservation',
				'order'                        => isset( $post->ID ) ? 'yes' : '',
				'reservation'                  => ! isset( $post->ID ) ? 'yes' : '',
				'base_country'                 => ER()->countries->get_base_country(),
				'currency_format_num_decimals' => er_get_price_decimals(),
				'currency_format_symbol'       => er_get_currency_symbol(),
				'currency_format_decimal_sep'  => esc_attr( er_get_price_decimal_separator() ),
				'currency_format_thousand_sep' => esc_attr( er_get_price_thousand_separator() ),
				'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array(
					'%s',
					'%v'
				), er_get_price_format() ) ), // For accounting JS.
				'rounding_precision'           => er_get_rounding_precision(),
				'tax_rounding_mode'            => er_prices_include_tax() ? 2 : 1,
				'i18n_tax_rate_already_exists' => __( 'You cannot add the same tax rate twice!', 'easyReservations' ),
				'i18n_delete_note'             => __( 'Are you sure you wish to delete this note? This action cannot be undone.', 'easyReservations' ),
				'i18n_apply_coupon'            => __( 'Enter a coupon code to apply. Discounts are applied to line totals, before taxes.', 'easyReservations' ),
				'i18n_add_fee'                 => __( 'Enter a fixed amount or percentage to apply as a fee.', 'easyReservations' ),
				'i18n_add_reservation'         => __( 'Enter a reservation id.', 'easyReservations' ),
			);

			wp_register_script( 'er-admin-meta-boxes', ER()->plugin_url() . '/assets/js/admin/meta-boxes' . $suffix . '.js', array(
				'jquery',
				'jquery-ui-sortable',
				'jquery-blockui',
				'round',
				'er-enhanced-select',
				'stupidtable',
				'jquery-tiptip'
			), RESERVATIONS_VERSION );

			wp_localize_script( 'er-admin-meta-boxes', 'easyreservations_admin_meta_boxes', $params );
		}

		if ( in_array( $screen_id, array( 'easy-rooms' ) ) ) {
			wp_enqueue_script( 'er-admin-resource-meta-boxes', ER()->plugin_url() . '/assets/js/admin/meta-boxes-resource' . $suffix . '.js', array(
				'er-admin-meta-boxes',
				'media-models'
			), RESERVATIONS_VERSION );
		}

		if ( str_replace( 'edit-', '', $screen_id ) === 'easy_order' ) {
			$default_location = er_get_default_location();

			wp_register_script( 'er-admin-order-meta-boxes', ER()->plugin_url() . '/assets/js/admin/meta-boxes-order' . $suffix . '.js', array(
				'er-admin-meta-boxes',
				'er-admin-receipt-meta-boxes',
				'er-admin-custom-meta-boxes',
				'selectWoo',
				'er-datepicker',
			), RESERVATIONS_VERSION );

			wp_enqueue_style( 'er-datepicker' );

			wp_localize_script(
				'er-admin-order-meta-boxes',
				'er_admin_meta_boxes_order_params',
				array(
					'countries'              => wp_json_encode( ER()->countries->get_states() ),
					'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'easyReservations' ),
					'default_country'        => isset( $default_location['country'] ) ? $default_location['country'] : '',
					'default_state'          => isset( $default_location['state'] ) ? $default_location['state'] : '',
					'placeholder_name'       => esc_attr__( 'Name (required)', 'easyReservations' ),
					'placeholder_value'      => esc_attr__( 'Value (required)', 'easyReservations' ),
				)
			);

			wp_enqueue_script( 'er-admin-order-meta-boxes' );
		}
	}

	public function resource_scripts() {  //  Load Scripts and Styles
		wp_enqueue_script( 'jquery-ui-datepicker' );

		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
	}

	/*
	 * Add admin menu
	 */
	public function add_menu() {
		global $current_tab, $current_section;
		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // WPCS: input var okay, CSRF ok.
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // WPCS: input var okay, CSRF ok.

		//Setup permissions

		add_menu_page(
			'easyReservations',
			'Reservations',
			'manage_easyreservations',
			'reservations',
			'easyreservations_main_page',
			RESERVATIONS_URL . 'assets/images/logo.png'
		);

		//add_submenu_page( 'reservations', __( 'Dashboard', 'easyReservations' ), __( 'Dashboard', 'easyReservations' ), $dashboard, 'reservations', 'easyreservations_main_page' );

		add_submenu_page( null, __( 'Resources', 'easyReservations' ), __( 'Resources', 'easyReservations' ), 'manage_easyreservations', 'resource', array(
			'ER_Admin_Resources',
			'output'
		) );

		add_submenu_page( null, __( 'Reservations', 'easyReservations' ), __( 'Reservations', 'easyReservations' ), 'manage_easyreservations', 'reservation', array(
			'ER_Admin_Reservation',
			'output'
		) );

		do_action( 'easy-add-submenu-page' );

		add_submenu_page( 'reservations', __( 'Availability', 'easyReservations' ), __( 'Availability', 'easyReservations' ), 'manage_easyreservations', 'reservation-availability', array(
			'ER_Admin_Availability',
			'output'
		) );

		add_submenu_page( 'reservations', __( 'Settings', 'easyReservations' ), __( 'Settings', 'easyReservations' ), 'manage_easyreservations', 'er-settings', array(
			'ER_Admin_Settings',
			'output'
		) );
	}

	/**
	 * Preview email template.
	 */
	public function preview_emails() {

		if ( isset( $_GET['preview_easyreservations_mail'] ) ) {
			if ( ! ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'preview-mail' ) ) ) {
				die( 'Security check' );
			}

			// load the mailer class.
			$mailer = ER()->mailer();

			// get the preview email subject.
			$email_heading = __( 'HTML email template', 'easyReservations' );

			// get the preview email content.
			ob_start();
			include 'views/html-email-template-preview.php';
			$message = ob_get_clean();

			// create a new email.
			$email = new ER_Email();

			// wrap the content with the email template and then add styles.
			$message = apply_filters( 'easyreservations_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );

			// print the preview email.
			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $message;
			// phpcs:enable
			exit;
		}
	}

	/**
	 * Handle saving of settings.
	 *
	 * @return void
	 */
	public function init_settings() {
		global $current_tab, $current_section;

		// We should only save on the settings page.
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'er-settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
			return;
		}

		ER()->payment_gateways();

		// Include settings pages.
		ER_Admin_Settings::get_settings_pages();

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // WPCS: input var okay, CSRF ok.
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // WPCS: input var okay, CSRF ok.

		// Save settings if data has been posted.
		if ( '' !== $current_section && apply_filters( "easyreservations_save_settings_{$current_tab}_{$current_section}", ! empty( $_POST['save'] ) ) ) { // WPCS: input var okay, CSRF ok.
			ER_Admin_Settings::save( $current_tab );
		} elseif ( '' === $current_section && apply_filters( "easyreservations_save_settings_{$current_tab}", ! empty( $_POST['save'] ) ) ) { // WPCS: input var okay, CSRF ok.
			ER_Admin_Settings::save( $current_tab );
		} else {
			do_action( 'easyreservations_no_settings_to_save' );
		}

		do_action( 'easyreservations_settings_page_init' );
	}

	/**
	 * Output admin fields.
	 *
	 * Loops through the easyreservations options array and outputs each field.
	 *
	 * @param array[] $options Opens array to output.
	 */
	public static function output_settings( $options ) {
		foreach ( $options as $value ) {
			if ( ! isset( $value['type'] ) ) {
				continue;
			}
			if ( ! isset( $value['id'] ) ) {
				$value['id'] = '';
			}
			if ( ! isset( $value['class'] ) ) {
				$value['class'] = '';
			}
			if ( ! isset( $value['css'] ) ) {
				$value['css'] = '';
			}
			if ( ! isset( $value['default'] ) ) {
				$value['default'] = '';
			}
			if ( ! isset( $value['desc'] ) ) {
				$value['desc'] = '';
			}
			if ( ! isset( $value['desc_tip'] ) ) {
				$value['desc_tip'] = false;
			}
			if ( ! isset( $value['suffix'] ) ) {
				$value['suffix'] = '';
			}

			if ( isset( $value['option'] ) ) {
				$value['value'] = ER_Admin_Settings::get_option( $value['option'], $value['default'] );
			}

			// Description handling.
			$field_description = self::get_field_description( $value );
			$description       = $field_description['description'];
			$tooltip_html      = $field_description['tooltip_html'];

			if ( isset( $value['title'] ) && $value['type'] !== 'title' && $value['type'] !== 'hidden' ) {
				?><tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $value['id'] ); ?>">
						<?php echo esc_html( $value['title'] ); ?><?php if ( $value['type'] !== 'checkbox' ) {
							echo $tooltip_html;
						} // WPCS: XSS ok.
						?>
                    </label>
                </th>
                <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<?php
			}

			// Switch based on type.
			switch ( $value['type'] ) {

				// Section Titles.
				case 'title':
					if ( ! empty( $value['title'] ) ) {
						echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
					}
					if ( ! empty( $value['desc'] ) ) {
						echo '<div id="' . esc_attr( sanitize_title( $value['id'] ) ) . '-description">';
						echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
						echo '</div>';
					}
					echo '<table class="form-table">' . "\n\n";
					if ( ! empty( $value['id'] ) ) {
						do_action( 'easyreservations_settings_' . sanitize_title( $value['id'] ) );
					}
					break;

				// Section Ends.
				case 'sectionend':
					if ( ! empty( $value['id'] ) ) {
						do_action( 'easyreservations_settings_' . sanitize_title( $value['id'] ) . '_end' );
					}
					echo '</table>';
					if ( ! empty( $value['id'] ) ) {
						do_action( 'easyreservations_settings_' . sanitize_title( $value['id'] ) . '_after' );
					}
					break;

				// Standard text inputs and subtypes like 'number'.
				case 'text':
				case 'price':
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
					?>
					<?php er_form_get_field( $value ); ?>
					<?php echo esc_html( $value['suffix'] ); ?>
					<?php echo $description; // WPCS: XSS ok.
					?>
					<?php
					break;

				case 'hidden':
					?>
					<?php er_form_get_field( $value ); ?>
					<?php
					break;

				// Color picker.
				case 'color':
					?>
                    <span class="colorpickpreview" style="background: <?php echo esc_attr( $value['value'] ); ?>">&nbsp;</span>
					<?php er_form_get_field( $value ); ?>&lrm;
					<?php echo $description; // WPCS: XSS ok.
					?>
                    <div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
					<?php
					break;

				// Textarea.
				case 'textarea':
					?>
					<?php echo $description; // WPCS: XSS ok.
					?>
					<?php er_form_get_field( $value );
					break;

				// Select boxes.
				case 'select':
				case 'multiselect':
					?>
					<?php er_form_get_field( $value ); ?>
					<?php echo $description; // WPCS: XSS ok.

					break;

				// Radio inputs.
				case 'radio':
					?>
                    <fieldset>
						<?php echo $description; // WPCS: XSS ok.
						?>
						<?php er_form_get_field( $value ); ?>
                    </fieldset>
					<?php
					break;

				// Checkbox input.
				case 'checkbox':
					$visibility_class = array();

					if ( ! isset( $value['hide_if_checked'] ) ) {
						$value['hide_if_checked'] = false;
					}
					if ( ! isset( $value['show_if_checked'] ) ) {
						$value['show_if_checked'] = false;
					}
					if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
						$visibility_class[] = 'hidden_option';
					}
					if ( 'option' === $value['hide_if_checked'] ) {
						$visibility_class[] = 'hide_options_if_checked';
					}
					if ( 'option' === $value['show_if_checked'] ) {
						$visibility_class[] = 'show_options_if_checked';
					}

					?>
                    <fieldset class="<?php echo ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ? esc_attr( implode( ' ', $visibility_class ) ) : ''; ?>">
						<?php
						if ( ! empty( $value['title'] ) ) {
							?>
                            <legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span>
                            </legend>
							<?php
						}
						?>
                        <label for="<?php echo esc_attr( $value['id'] ); ?>">
							<?php er_form_get_field( $value ); ?>
							<?php echo $description; // WPCS: XSS ok.
							?>
                        </label> <?php echo $tooltip_html; // WPCS: XSS ok.
						?>
                    </fieldset>
					<?php
					break;

				// Single page selects.
				case 'single_select_page':
					$args = array(
						'name'             => $value['id'],
						'id'               => $value['id'],
						'sort_column'      => 'menu_order',
						'sort_order'       => 'ASC',
						'show_option_none' => ' ',
						'class'            => $value['class'],
						'echo'             => false,
						'selected'         => absint( $value['value'] ),
						'post_status'      => 'publish,private,draft',
					);

					if ( isset( $value['args'] ) ) {
						$args = wp_parse_args( $value['args'], $args );
					}

					echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'easyReservations' ) . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); // WPCS: XSS ok.
					echo $description; // WPCS: XSS ok.
					break;

				// Single country selects.
				case 'single_select_country':
					$country_setting = $value['value'];

					if ( strstr( $country_setting, ':' ) ) {
						$country_setting = explode( ':', $country_setting );
						$country         = current( $country_setting );
						$state           = end( $country_setting );
					} else {
						$country = $country_setting;
						$state   = '*';
					}
					?>
                    <select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php esc_attr_e( 'Choose a country / region&hellip;', 'easyReservations' ); ?>" aria-label="<?php esc_attr_e( 'Country / Region', 'easyReservations' ); ?>" class="er-enhanced-select">
						<?php ER()->countries->country_dropdown_options( $country, $state ); ?>
                    </select> <?php echo $description; // WPCS: XSS ok.

					break;

				// Media upload.
				case 'image_upload':
					$image_id = absint( $value['value'] );

					echo '<div style="padding: 1px">';

					if ( $image = wp_get_attachment_image_src( $image_id ) ) {
						echo '<a href="#" class="easyreservations-upl image"><img src="' . esc_attr( $image[0] ) . '" /></a> ' .
						     '<a href="#" class="button easyreservations-rmv image">' . esc_html__( 'Remove image', 'easyReservations' ) . '</a>' .
						     '<input type="hidden" id="' . esc_attr( $value['id'] ) . '" name="' . esc_attr( $value['id'] ) . '" value="' . esc_attr( $image_id ) . '">';
					} else {
						echo '<a href="#" class="button easyreservations-upl image">' . esc_html__( 'Upload image', 'easyReservations' ) . '</a> ' .
						     '<a href="#" class="button easyreservations-rmv image" style="display:none">' . esc_html__( 'Remove image', 'easyReservations' ) . '</a>' .
						     '<input type="hidden" id="' . esc_attr( $value['id'] ) . '" name="' . esc_attr( $value['id'] ) . '" value="">';
					}

					echo $description . '</div>'; // WPCS: XSS ok.

					break;

                // Media upload.
				case 'media_upload':
					$image_id = absint( $value['value'] );

					echo '<div style="padding: 1px">';

					if ( $image = get_attached_file( $image_id ) ) {
						echo '<a href="#" class="easyreservations-upl file"><input type="text" value="' . esc_attr( substr( $image, strrpos( $image, '/' ) + 1 ) ) . '" readonly="readonly"></a> ' .
						     '<a href="#" class="button easyreservations-rmv file">' . esc_html__( 'Remove file', 'easyReservations' ) . '</a>' .
						     '<input type="hidden" id="' . esc_attr( $value['id'] ) . '" name="' . esc_attr( $value['id'] ) . '" value="' . esc_attr( $image_id ) . '">';
					} else {
						echo '<a href="#" class="easyreservations-upl file"><input type="text" readonly="readonly"></a> ' .
						     '<a href="#" class="button easyreservations-rmv file" style="display:none">' . esc_html__( 'Remove file', 'easyReservations' ) . '</a>' .
						     '<input type="hidden" id="' . esc_attr( $value['id'] ) . '" name="' . esc_attr( $value['id'] ) . '" value="">';
					}

					echo $description . '</div>'; // WPCS: XSS ok.

					break;

				// Days/months/years selector.
				case 'relative_date_selector':
					$periods = array(
						'days'   => __( 'Day(s)', 'easyReservations' ),
						'weeks'  => __( 'Week(s)', 'easyReservations' ),
						'months' => __( 'Month(s)', 'easyReservations' ),
						'years'  => __( 'Year(s)', 'easyReservations' ),
					);
					$option_value = er_parse_relative_date_option( $value['value'] );
					?>
                    <input
                        name="<?php echo esc_attr( $value['id'] ); ?>[number]"
                        id="<?php echo esc_attr( $value['id'] ); ?>"
                        type="number"
                        style="width: 80px;"
                        value="<?php echo esc_attr( $option_value['number'] ); ?>"
                        class="<?php echo esc_attr( $value['class'] ); ?>"
                        placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
                        step="1"
                        min="1"
                    />&nbsp;
                    <select name="<?php echo esc_attr( $value['id'] ); ?>[unit]" style="width: auto;">
						<?php
						foreach ( $periods as $key => $label ) {
							echo '<option value="' . esc_attr( $key ) . '"' . selected( $option_value['unit'], $key, false ) . '>' . esc_html( $label ) . '</option>';
						}
						?>
                    </select> <?php echo $description ? $description : ''; // WPCS: XSS ok.
					?>
					<?php
					break;

				// Default: run an action.
				default:
					do_action( 'easyreservations_admin_field_' . $value['type'], $value );

					echo $description;

					break;
			}

			if ( $value['type'] !== 'title' && ( ! isset( $value['input-group'] ) || $value['input-group'] == 'end' ) ) {
				?>
                </td>
                </tr>
				<?php
			}
		}
	}

	/**
	 * Helper function to get the formatted description and tip HTML for a
	 * given form field. Plugins can call this when implementing their own custom
	 * settings types.
	 *
	 * @param array $value The form field value array.
	 *
	 * @return array The description and tip as a 2 element array.
	 */
	public static function get_field_description( $value ) {
		$description  = '';
		$tooltip_html = '';

		if ( true === $value['desc_tip'] ) {
			$tooltip_html = $value['desc'];
		} elseif ( ! empty( $value['desc_tip'] ) ) {
			$description  = $value['desc'];
			$tooltip_html = $value['desc_tip'];
		} elseif ( ! empty( $value['desc'] ) ) {
			$description = $value['desc'];
		}

		if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
			$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
		} elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
			$description = wp_kses_post( $description );
		} elseif ( $description ) {
			$description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
		}

		if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
			$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
		} elseif ( $tooltip_html ) {
			$tooltip_html = er_get_help( $tooltip_html );
		}

		return array(
			'description'  => $description,
			'tooltip_html' => $tooltip_html,
		);
	}
}

return new ER_Admin();
